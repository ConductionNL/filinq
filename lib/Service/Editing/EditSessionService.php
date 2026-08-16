<?php

/**
 * Edit Session Service
 *
 * Owns the whole read-modify-write of an agent document edit: the lock, the
 * version precondition, the codec pass, the ADR-088 mark and the write. None of
 * these steps is separately agent-callable, and the model never sees a lock.
 *
 * ## Why this is in-process rather than a WOPI client
 *
 * `document-editing-tools`' design specified a WOPI client against
 * `richdocuments`. Reading richdocuments 11.1.0 before writing the code showed
 * that route cannot deliver the guard the design asked of it:
 *
 * - `WopiController::lock()` ignores the `X-WOPI-Lock` value entirely and takes
 *   an `ILockManager` lock of type `TYPE_APP` owned by the literal string
 *   `richdocuments`.
 * - `files_lock`'s `LockService::lock()` EXTENDS an existing lock when the type
 *   and owner both match, and only throws `OwnerLockedException` otherwise.
 *
 * So a WOPI client's lock is indistinguishable from Collabora's own: a document
 * open in the editor would have its lock silently extended rather than
 * refusing, which is precisely the data-loss case the lock exists to prevent.
 * Taking the same `ILockManager` lock in-process under the owner `docudesk`
 * conflicts with Collabora's lock (the refusal we want) while staying distinct
 * from it (which WOPI could not give us). It also drops the self-addressed HTTP
 * call and its bearer token, in line with ADR-041's in-process posture.
 *
 * The in-screen editor still interoperates, because `ILockManager` is the same
 * registry richdocuments writes its WOPI locks into. What is lost is nothing
 * WOPI was providing here.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use OCA\DocuDesk\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\Files\Lock\NoLockProviderException;
use OCP\Files\Lock\OwnerLockedException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Runs an agent's document edit under a lock and a version precondition.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditSessionService {

	/**
	 * Write the change back into the source file, producing a Nextcloud version.
	 *
	 * @var string
	 */
	public const MODE_IN_PLACE = 'inPlace';

	/**
	 * Write the change to a new file beside the source, leaving the source untouched.
	 *
	 * @var string
	 */
	public const MODE_SIBLING = 'sibling';

	/**
	 * App-config key setting the CEILING on output mode.
	 *
	 * A tool argument may narrow this to `sibling`; it may never widen it to
	 * `inPlace`. An agent that can widen its own blast radius has no blast
	 * radius.
	 *
	 * @var string
	 */
	public const CONFIG_OUTPUT_MODE = 'agent_edit_output_mode';

	/**
	 * Largest package this service will load into memory.
	 *
	 * @var int
	 */
	private const MAX_BYTES = 26214400;

	/**
	 * Largest number of blocks returned to a caller in one read.
	 *
	 * @var int
	 */
	private const MAX_BLOCKS = 400;

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder The Nextcloud root folder.
	 * @param ILockManager $lockManager The file lock manager.
	 * @param PackageCodec $codec The document package codec.
	 * @param AgentArtefactMarker $marker The ADR-088 artefact marker.
	 * @param DocumentGuard $guard The standing refusals.
	 * @param IAppConfig $appConfig App configuration.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param PackageMetadataCodec $metadata The document metadata codec.
	 * @param ChartCodec $charts The chart embedding codec.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly ILockManager $lockManager,
		private readonly PackageCodec $codec,
		private readonly AgentArtefactMarker $marker,
		private readonly DocumentGuard $guard,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
		private readonly PackageMetadataCodec $metadata = new PackageMetadataCodec(),
		private readonly ChartCodec $charts = new ChartCodec(),
	) {

	}//end __construct()

	/**
	 * Read a document's anchored blocks.
	 *
	 * The returned `version` is the token the caller must hand back to
	 * {@see self::editForAgent()}. Without it an edit would be applied to
	 * whatever the file happens to be at write time, which is how one writer
	 * silently overwrites another.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> The document outline.
	 *
	 * @throws RuntimeException When the file cannot be read or is not an editable package.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
	 */
	public function openForAgent(string $uid, int $fileId): array {
		$file = $this->resolveFile(uid: $uid, fileId: $fileId);
		$read = $this->codec->readBlocks(
			packageBytes: $this->readBytes(file: $file),
			extension: $file->getExtension()
		);

		$blocks = $read['blocks'];
		$truncated = (count($blocks) > self::MAX_BLOCKS);
		if ($truncated === true) {
			$blocks = array_slice($blocks, 0, self::MAX_BLOCKS);
		}

		return [
			'fileId' => $file->getId(),
			'name' => $file->getName(),
			'path' => $this->userPath(uid: $uid, file: $file),
			'format' => $read['format'],
			'version' => $file->getEtag(),
			'blockCount' => count($read['blocks']),
			'truncated' => $truncated,
			'blocks' => $blocks,
			'editable' => ($file->isUpdateable() === true),
		];

	}//end openForAgent()

	/**
	 * Apply anchored edits to a document.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 * @param array<int, array{anchor: string, action?: string, text?: string}> $edits The edits.
	 * @param string $version The `version` returned by the read that produced these anchors.
	 * @param string|null $requestedMode An output mode narrowing request, or null for the configured mode.
	 *
	 * @return array<string, mixed> The outcome, including the produced file id.
	 *
	 * @throws RuntimeException On any refusal or failure. Nothing is written on a throw.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 * @spec openspec/specs/document-editing/spec.md#requirement-output-mode-may-be-narrowed-by-a-caller-but-never-widened
	 * @spec openspec/specs/document-editing/spec.md#requirement-lock-contention-is-refused-not-waited-out
	 */
	public function editForAgent(
		string $uid,
		int $fileId,
		array $edits,
		string $version,
		?string $requestedMode = null,
	): array {
		if (trim($version) === '') {
			throw new RuntimeException(
				'A version is required. Read the document first and pass back the version it returned.'
			);
		}

		$file = $this->resolveFile(uid: $uid, fileId: $fileId);
		$mode = $this->resolveMode(requested: $requestedMode);

		$this->refuseIfGuarded(file: $file);

		if ($mode === self::MODE_IN_PLACE && $file->isUpdateable() === false) {
			throw new RuntimeException('You do not have permission to change this file.');
		}

		return $this->runSession(
			uid: $uid,
			file: $file,
			transform: fn (string $bytes, string $extension): array => $this->codec->applyEdits(
				packageBytes: $bytes,
				extension: $extension,
				edits: $edits
			),
			version: $version,
			mode: $mode
		);

	}//end editForAgent()

	/**
	 * Read a document's metadata.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> The document's name, format, version and metadata.
	 *
	 * @throws RuntimeException When the file cannot be read or the format is unsupported.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function readMetadataForAgent(string $uid, int $fileId): array {
		$file = $this->resolveFile(uid: $uid, fileId: $fileId);

		return [
			'fileId' => $file->getId(),
			'name' => $file->getName(),
			'path' => $this->userPath(uid: $uid, file: $file),
			'version' => $file->getEtag(),
			'editable' => ($file->isUpdateable() === true),
			'metadata' => $this->metadata->readMetadata(
				packageBytes: $this->readBytes(file: $file),
				extension: $file->getExtension()
			),
		];

	}//end readMetadataForAgent()

	/**
	 * Write a document's metadata.
	 *
	 * Runs through the same session as a body edit -- same lock, same version
	 * recheck immediately before the write, same agent-authored tag applied before
	 * the bytes become visible. Metadata is a smaller change than a paragraph
	 * rewrite, not a less accountable one.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 * @param array<string, string> $values Field name => new value.
	 * @param string $version The version returned by the preceding read.
	 * @param string|null $requestedMode The requested output mode, or null for the configured default.
	 *
	 * @return array<string, mixed> The write result.
	 *
	 * @throws RuntimeException On any refusal or failure.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function setMetadataForAgent(
		string $uid,
		int $fileId,
		array $values,
		string $version,
		?string $requestedMode = null,
	): array {
		if (trim($version) === '') {
			throw new RuntimeException(
				'A version is required. Read the document metadata first and pass back the version it returned.'
			);
		}

		$file = $this->resolveFile(uid: $uid, fileId: $fileId);
		$mode = $this->resolveMode(requested: $requestedMode);

		$this->refuseIfGuarded(file: $file);

		if ($mode === self::MODE_IN_PLACE && $file->isUpdateable() === false) {
			throw new RuntimeException('You do not have permission to change this file.');
		}

		return $this->runSession(
			uid: $uid,
			file: $file,
			transform: fn (string $bytes, string $extension): array => $this->metadata->writeMetadata(
				packageBytes: $bytes,
				extension: $extension,
				values: $values
			),
			version: $version,
			mode: $mode
		);

	}//end setMetadataForAgent()

	/**
	 * Embed a chart into a document.
	 *
	 * Runs through the same session as a text edit -- same lock, same version
	 * recheck, same agent-authored tag. A chart adds package PARTS rather than
	 * rewriting one, which makes the version precondition more important rather
	 * than less: a half-applied multi-part write is a document no suite will open.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 * @param array<string, mixed> $chart The chart definition.
	 * @param string $version The version returned by the preceding read.
	 * @param string|null $afterAnchor The anchor to place the chart after, or null for the end.
	 * @param string|null $requestedMode The requested output mode, or null for the configured default.
	 *
	 * @return array<string, mixed> The write result.
	 *
	 * @throws RuntimeException On any refusal or failure.
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function embedChartForAgent(
		string $uid,
		int $fileId,
		array $chart,
		string $version,
		?string $afterAnchor = null,
		?string $requestedMode = null,
	): array {
		if (trim($version) === '') {
			throw new RuntimeException(
				'A version is required. Read the document first and pass back the version it returned.'
			);
		}

		$file = $this->resolveFile(uid: $uid, fileId: $fileId);
		$mode = $this->resolveMode(requested: $requestedMode);

		$this->refuseIfGuarded(file: $file);

		if ($mode === self::MODE_IN_PLACE && $file->isUpdateable() === false) {
			throw new RuntimeException('You do not have permission to change this file.');
		}

		return $this->runSession(
			uid: $uid,
			file: $file,
			transform: fn (string $bytes, string $extension): array => $this->charts->embedChart(
				packageBytes: $bytes,
				extension: $extension,
				chart: $chart,
				afterAnchor: $afterAnchor
			),
			version: $version,
			mode: $mode
		);

	}//end embedChartForAgent()

	/**
	 * Hold the lock across the whole read-modify-write, and release it on every exit path.
	 *
	 * @param string $uid The acting user id.
	 * @param File $file The source file.
	 * @param callable $transform Given (bytes, extension), returns the rewritten package.
	 * @param string $version The expected version.
	 * @param string $mode The resolved output mode.
	 *
	 * @return array<string, mixed> The outcome.
	 *
	 * @throws RuntimeException On any refusal or failure.
	 */
	private function runSession(string $uid, File $file, callable $transform, string $version, string $mode): array {
		$lock = new LockContext($file, ILock::TYPE_APP, Application::APP_ID);
		$held = $this->acquire(lock: $lock, file: $file);
		$warnings = [];

		if ($held === false) {
			$warnings[] = 'No file-lock provider is available on this instance, so a concurrent '
				. 'editing session could not be excluded. The version check still applies.';
		}

		try {
			// The transform is a parameter rather than a fixed call so that a
			// metadata write gets the IDENTICAL lock, version-recheck, tag-then-write
			// and unlock path as a body edit. Copying this method for metadata would
			// have meant two copies of the only code that stops an agent clobbering
			// a concurrent human edit — and the copy would drift.
			$bytes = $this->readBytes(file: $file);
			$edited = $transform($bytes, $file->getExtension());

			// Re-read the version immediately before the write. The lock excludes
			// another editing SESSION; this closes the remaining window in which
			// the file changed outside one. Refusing is correct -- this codec
			// cannot merge, and guessing would be worse than stopping.
			$current = $file->getEtag();
			if ($current !== $version) {
				throw new RuntimeException(
					'This document changed since you read it, so it was not edited. '
					. 'Read it again and re-apply your changes to the current text.'
				);
			}

			$written = $this->write(uid: $uid, file: $file, bytes: $edited['bytes'], mode: $mode, lock: $lock);

			return ([
				'fileId' => $written['fileId'],
				'name' => $written['name'],
				'path' => $written['path'],
				'outputMode' => $mode,
				'appliedAnchors' => ($edited['applied'] ?? []),
				'metadataWritten' => ($edited['written'] ?? []),
				'version' => $written['version'],
				'agentAuthoredTag' => AgentArtefactMarker::TAG_NAME,
				'warnings' => $warnings,
			]);
		} finally {
			if ($held === true) {
				$this->release(lock: $lock);
			}
		}//end try

	}//end runSession()

	/**
	 * Write the edited bytes, marking the artefact before it becomes visible.
	 *
	 * The mark goes on FIRST and is rolled back if the write then fails, so
	 * neither an unmarked agent artefact nor a mark on an unchanged file can
	 * survive this method.
	 *
	 * @param string $uid The acting user id.
	 * @param File $file The source file.
	 * @param string $bytes The edited package bytes.
	 * @param string $mode The output mode.
	 * @param LockContext $lock The held lock, so our own write is not refused by it.
	 *
	 * @return array{fileId: int, name: string, path: string, version: string}
	 *
	 * @throws RuntimeException When marking or writing fails.
	 */
	private function write(string $uid, File $file, string $bytes, string $mode, LockContext $lock): array {
		$target = $file;
		if ($mode === self::MODE_SIBLING) {
			$target = $this->sibling(file: $file);
		}

		$added = $this->marker->mark(fileId: $target->getId());

		try {
			$this->lockManager->runInScope($lock, static function () use ($target, $bytes): void {
				$target->putContent($bytes);
			});
		} catch (Throwable $e) {
			if ($added === true) {
				$this->marker->unmark(fileId: $target->getId());
			}

			throw new RuntimeException('Could not save the edited document: ' . $e->getMessage(), 0, $e);
		}

		return [
			'fileId' => $target->getId(),
			'name' => $target->getName(),
			'path' => $this->userPath(uid: $uid, file: $target),
			'version' => $target->getEtag(),
		];

	}//end write()

	/**
	 * Create the empty sibling file the edited bytes will be written into.
	 *
	 * @param File $file The source file.
	 *
	 * @return File The new file.
	 *
	 * @throws RuntimeException When the sibling cannot be created.
	 */
	private function sibling(File $file): File {
		try {
			$parent = $file->getParent();
			$extension = $file->getExtension();
			$stem = $file->getName();
			$suffix = '';

			if ($extension !== '') {
				$stem = substr($stem, 0, (-1 * (strlen($extension) + 1)));
				$suffix = '.' . $extension;
			}

			return $parent->newFile($parent->getNonExistingName($stem . ' (agent edit)' . $suffix));
		} catch (Throwable $e) {
			throw new RuntimeException('Could not create a new file beside the original: ' . $e->getMessage(), 0, $e);
		}

	}//end sibling()

	/**
	 * Take the lock, distinguishing "someone else holds it" from "nobody can".
	 *
	 * @param LockContext $lock The lock to take.
	 * @param File $file The file being locked, for the refusal message.
	 *
	 * @return bool True when the lock is held and must be released.
	 *
	 * @throws RuntimeException When another owner holds the lock.
	 */
	private function acquire(LockContext $lock, File $file): bool {
		try {
			$this->lockManager->lock($lock);

			return true;
		} catch (OwnerLockedException $e) {
			// Deliberately no polling, queueing, retry or lock stealing. Taking a
			// lock we did not create is a data-loss primitive.
			$holder = $e->getLock()->getOwner();
			if ($holder === '') {
				$holder = 'someone else';
			}

			throw new RuntimeException(
				sprintf(
					'"%s" is currently open for editing by %s, so it was not changed. Try again once it is closed.',
					$file->getName(),
					$holder
				),
				0,
				$e
			);
		} catch (NoLockProviderException) {
			return false;
		} catch (Throwable $e) {
			$this->logger->warning('DocuDesk could not take an edit lock: ' . $e->getMessage());

			return false;
		}//end try

	}//end acquire()

	/**
	 * Release the lock, never masking the outcome the caller is already reporting.
	 *
	 * @param LockContext $lock The held lock.
	 *
	 * @return void
	 */
	private function release(LockContext $lock): void {
		try {
			$this->lockManager->unlock($lock);
		} catch (Throwable $e) {
			$this->logger->warning('DocuDesk could not release an edit lock: ' . $e->getMessage());
		}

	}//end release()

	/**
	 * Resolve the output mode: configuration sets the ceiling, the argument may only narrow it.
	 *
	 * @param string|null $requested The requested mode, or null.
	 *
	 * @return string The resolved mode.
	 *
	 * @throws RuntimeException When the requested mode is not a mode.
	 */
	private function resolveMode(?string $requested): string {
		$ceiling = $this->appConfig->getValueString(
			Application::APP_ID,
			self::CONFIG_OUTPUT_MODE,
			self::MODE_IN_PLACE
		);

		if ($ceiling !== self::MODE_IN_PLACE) {
			$ceiling = self::MODE_SIBLING;
		}

		if ($requested === null || $requested === '') {
			return $ceiling;
		}

		if (in_array($requested, [self::MODE_IN_PLACE, self::MODE_SIBLING], true) === false) {
			throw new RuntimeException(
				sprintf('Unknown output mode "%s". Use "%s" or "%s".', $requested, self::MODE_IN_PLACE, self::MODE_SIBLING)
			);
		}

		// Narrowing only: a request for in-place against a sibling ceiling is
		// silently held at sibling rather than honoured.
		if ($ceiling === self::MODE_SIBLING) {
			return self::MODE_SIBLING;
		}

		return $requested;

	}//end resolveMode()

	/**
	 * Apply the standing refusals.
	 *
	 * Both apply to sibling output too: a redacted document re-edited into a
	 * near-identical neighbour is the same re-identification risk, and a copy of
	 * a document under signature invites signing the wrong artefact.
	 *
	 * @param File $file The file to check.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When a refusal applies.
	 */
	private function refuseIfGuarded(File $file): void {
		$refusal = ($this->guard->signatureRefusal(file: $file) ?? $this->guard->anonymisationRefusal(file: $file));
		if ($refusal !== null) {
			throw new RuntimeException($refusal);
		}

	}//end refuseIfGuarded()

	/**
	 * Resolve a file id in the acting user's own folder.
	 *
	 * Resolving through the user folder rather than the root is the IDOR
	 * boundary: a file id the user cannot reach does not resolve at all.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File The file.
	 *
	 * @throws RuntimeException When the id names nothing the user can reach, or an uneditable format.
	 */
	private function resolveFile(string $uid, int $fileId): File {
		if (trim($uid) === '') {
			throw new RuntimeException('No acting user; document tools require a signed-in user.');
		}

		try {
			$node = $this->rootFolder->getUserFolder($uid)->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			throw new RuntimeException('Could not open file ' . $fileId . ': ' . $e->getMessage(), 0, $e);
		}

		if (($node instanceof File) === false) {
			throw new RuntimeException('File ' . $fileId . ' was not found in your files.');
		}

		if ($this->codec->supports(extension: $node->getExtension()) === false) {
			throw new RuntimeException(
				sprintf(
					'"%s" is not an editable document format. Editable formats are: %s. '
					. 'Convert the document first if you need to change it.',
					$node->getName(),
					implode(', ', $this->codec->supportedExtensions())
				)
			);
		}

		return $node;

	}//end resolveFile()

	/**
	 * Read a file's bytes, refusing anything too large to hold in memory.
	 *
	 * @param File $file The file.
	 *
	 * @return string The bytes.
	 *
	 * @throws RuntimeException When the file is too large or unreadable.
	 */
	private function readBytes(File $file): string {
		if ($file->getSize() > self::MAX_BYTES) {
			throw new RuntimeException(
				sprintf('"%s" is larger than %d MB and is not opened for editing.', $file->getName(), (int)(self::MAX_BYTES / 1048576))
			);
		}

		$bytes = $file->getContent();
		if (is_string($bytes) === false || $bytes === '') {
			throw new RuntimeException(sprintf('"%s" could not be read.', $file->getName()));
		}

		return $bytes;

	}//end readBytes()

	/**
	 * Express a file's path relative to the acting user's root, for a human-readable answer.
	 *
	 * @param string $uid The acting user id.
	 * @param File $file The file.
	 *
	 * @return string The relative path, or the file name when it cannot be derived.
	 */
	private function userPath(string $uid, File $file): string {
		try {
			return $this->rootFolder->getUserFolder($uid)->getRelativePath($file->getPath()) ?? $file->getName();
		} catch (Throwable) {
			return $file->getName();
		}

	}//end userPath()
}//end class
