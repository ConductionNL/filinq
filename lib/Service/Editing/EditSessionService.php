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
 * Taking the same `ILockManager` lock in-process under the owner `filinq`
 * conflicts with Collabora's lock (the refusal we want) while staying distinct
 * from it (which WOPI could not give us). It also drops the self-addressed HTTP
 * call and its bearer token, in line with ADR-041's in-process posture.
 *
 * The in-screen editor still interoperates, because `ILockManager` is the same
 * registry richdocuments writes its WOPI locks into. What is lost is nothing
 * WOPI was providing here.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use RuntimeException;
use Throwable;

/**
 * Runs an agent's document edit under a lock and a version precondition.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
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
	 * @param DocumentCodecs $codecs The per-kind document codecs.
	 * @param GuardedWriter $writer The guarded read-modify-write.
	 * @param DocumentGuard $guard The standing refusals.
	 * @param IAppConfig $appConfig App configuration.
	 * @param PackageMetadataCodec $metadata The document metadata codec.
	 * @param ChartCodec $charts The chart embedding codec.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly DocumentCodecs $codecs,
		private readonly GuardedWriter $writer,
		private readonly DocumentGuard $guard,
		private readonly IAppConfig $appConfig,
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
		return $this->openListing(
			request: new AgentFileRequest(uid: $uid, fileId: $fileId),
			listing: new DocumentListing(
				countKey: 'blockCount',
				itemsKey: 'blocks',
				read: function (string $bytes, string $extension): array {
					$read = $this->codecs->text->readBlocks(packageBytes: $bytes, extension: $extension);
					return ['items' => $read['blocks'], 'extra' => ['format' => $read['format']]];
				}
			)
		);
	}//end openForAgent()

	/**
	 * The common shape of every "open for an agent" read.
	 *
	 * All three reads do the same four things — resolve, read, bound, describe
	 * — and differ only in what they call the items. Writing that out three
	 * times is how the truncation rule ends up applied in two of them.
	 *
	 * @param AgentFileRequest $request The acting user, file id and codec kind.
	 * @param DocumentListing $listing The envelope keys and item reader.
	 *
	 * @return array<string, mixed> The envelope.
	 */
	private function openListing(AgentFileRequest $request, DocumentListing $listing): array {
		$file = $this->resolveFile(request: $request);
		$packageBytes = $this->readBytes(file: $file);
		$read = ($listing->read)($packageBytes, $file->getExtension());

		$items = $read['items'];
		$total = count($items);
		$truncated = ($total > self::MAX_BLOCKS);
		if ($truncated === true) {
			$items = array_slice($items, 0, self::MAX_BLOCKS);
		}

		return ([
			'fileId' => $file->getId(),
			'name' => $file->getName(),
			'path' => $this->userPath(uid: $request->uid, file: $file),
			// The token is minted from the BYTES that produced these items, so
			// what the caller hands back names the document it actually read.
			// It was the etag, which does not always move on a write — see
			// GuardedWriter::versionOf().
			'version' => $this->writer->versionOf(packageBytes: $packageBytes),
			$listing->countKey => $total,
			'truncated' => $truncated,
			$listing->itemsKey => $items,
			'editable' => ($file->isUpdateable() === true),
		] + $read['extra']);
	}//end openListing()

	/**
	 * The spreadsheet codec's kind: ods and xlsx.
	 *
	 * @return DocumentKind The kind.
	 */
	private function spreadsheetKind(): DocumentKind {
		return new DocumentKind(
			supports: fn (string $extension): bool => $this->codecs->spreadsheet->supports(extension: $extension),
			formats: 'ods, xlsx'
		);
	}//end spreadsheetKind()

	/**
	 * The presentation codec's kind: pptx and odp.
	 *
	 * @return DocumentKind The kind.
	 */
	private function presentationKind(): DocumentKind {
		return new DocumentKind(
			supports: fn (string $extension): bool => $this->codecs->presentation->supports(extension: $extension),
			formats: 'pptx, odp'
		);
	}//end presentationKind()

	/**
	 * Apply anchored edits to a document.
	 *
	 * @param AgentFileRequest $request The acting user, file id, version from the read
	 *                                  that produced these anchors, and requested output mode.
	 * @param array<int, array{anchor: string, action?: string, text?: string}> $edits The edits.
	 *
	 * @return array<string, mixed> The outcome, including the produced file id.
	 *
	 * @throws RuntimeException On any refusal or failure. Nothing is written on a throw.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 * @spec openspec/specs/document-editing/spec.md#requirement-output-mode-may-be-narrowed-by-a-caller-but-never-widened
	 * @spec openspec/specs/document-editing/spec.md#requirement-lock-contention-is-refused-not-waited-out
	 */
	public function editForAgent(AgentFileRequest $request, array $edits): array {
		return $this->runWrite(
			request: $request,
			transform: fn (string $bytes, string $extension): array => $this->codecs->text->applyEdits(
				packageBytes: $bytes,
				extension: $extension,
				edits: $edits
			)
		);

	}//end editForAgent()

	/**
	 * Read a spreadsheet's cells for an agent.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> The sheet's cells and the version an edit requires.
	 *
	 * @throws RuntimeException When the file cannot be read or is not a spreadsheet.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function openSpreadsheetForAgent(string $uid, int $fileId): array {
		return $this->openListing(
			request: new AgentFileRequest(uid: $uid, fileId: $fileId, kind: $this->spreadsheetKind()),
			listing: new DocumentListing(
				countKey: 'cellCount',
				itemsKey: 'cells',
				read: fn (string $bytes, string $extension): array => [
					'items' => $this->codecs->spreadsheet->readCells(packageBytes: $bytes, extension: $extension),
					'extra' => [],
				]
			)
		);
	}//end openSpreadsheetForAgent()

	/**
	 * Write literal values into a spreadsheet's cells.
	 *
	 * Reuses the SAME lock discipline, version precondition and agent-authored
	 * marking as text editing. None of that is type-specific, and a per-type
	 * write path would eventually re-implement one of them wrongly.
	 *
	 * The requested output mode is ignored: a spreadsheet write uses the configured mode.
	 *
	 * @param AgentFileRequest $request The acting user, file id and the version from
	 *                                  the read that produced these addresses.
	 * @param array $edits Each `{cell, value, replaceFormula?}`.
	 *
	 * @return array<string, mixed> The outcome, including cells whose cached values went stale.
	 *
	 * @throws RuntimeException On any refusal. Nothing is written on a throw.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function editSpreadsheetForAgent(AgentFileRequest $request, array $edits): array {
		$stale = ['staleDependents' => [], 'erroredDependents' => []];
		$result = $this->runWrite(
			request: new AgentFileRequest(
				uid: $request->uid,
				fileId: $request->fileId,
				version: $request->version,
				kind: $this->spreadsheetKind()
			),
			transform: function (string $bytes, string $extension) use ($edits, &$stale): array {
				$applied = $this->codecs->spreadsheet->applyCellEdits(
					packageBytes: $bytes,
					extension: $extension,
					edits: $edits
				);
				$stale = ['staleDependents' => $applied['staleDependents'], 'erroredDependents' => $applied['erroredDependents']];

				return $applied;
			}
		);

		// ⚠️ Surfaced on the RESULT, not merely logged. A dependent cell whose
		// cached value no longer follows from its inputs is a number that looks
		// current and is not, and the caller is the only one who can decide
		// whether that matters.
		$result['staleDependents'] = $stale['staleDependents'];
		$result['erroredDependents'] = $stale['erroredDependents'];

		return $result;
	}//end editSpreadsheetForAgent()

	/**
	 * Read a presentation's shapes for an agent.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> The deck's shapes and the version an edit requires.
	 *
	 * @throws RuntimeException When the file cannot be read or is not a presentation.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function openPresentationForAgent(string $uid, int $fileId): array {
		return $this->openListing(
			request: new AgentFileRequest(uid: $uid, fileId: $fileId, kind: $this->presentationKind()),
			listing: new DocumentListing(
				countKey: 'shapeCount',
				itemsKey: 'shapes',
				read: fn (string $bytes, string $extension): array => [
					'items' => $this->codecs->presentation->readShapes(packageBytes: $bytes, extension: $extension),
					'extra' => [],
				]
			)
		);
	}//end openPresentationForAgent()

	/**
	 * Replace the text of addressed presentation shapes.
	 *
	 * The requested output mode is ignored: a presentation write uses the configured mode.
	 *
	 * @param AgentFileRequest $request The acting user, file id and the version from
	 *                                  the read that produced these ids.
	 * @param array $edits Each `{slide, shape, text, region?}`.
	 *
	 * @return array<string, mixed> The outcome.
	 *
	 * @throws RuntimeException On any refusal. Nothing is written on a throw.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function editPresentationForAgent(AgentFileRequest $request, array $edits): array {
		return $this->runWrite(
			request: new AgentFileRequest(
				uid: $request->uid,
				fileId: $request->fileId,
				version: $request->version,
				kind: $this->presentationKind()
			),
			transform: fn (string $bytes, string $extension): array => $this->codecs->presentation->applyShapeEdits(
				packageBytes: $bytes,
				extension: $extension,
				edits: $edits
			)
		);
	}//end editPresentationForAgent()

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
		$file = $this->resolveFile(request: new AgentFileRequest(uid: $uid, fileId: $fileId));
		$bytes = $this->readBytes(file: $file);

		return [
			'fileId' => $file->getId(),
			'name' => $file->getName(),
			'path' => $this->userPath(uid: $uid, file: $file),
			'version' => $this->writer->versionOf(packageBytes: $bytes),
			'editable' => ($file->isUpdateable() === true),
			'metadata' => $this->metadata->readMetadata(
				packageBytes: $bytes,
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
	 * @param AgentFileRequest $request The acting user, file id, version returned by
	 *                                  the preceding read, and requested output mode.
	 * @param array<string, string> $values Field name => new value.
	 *
	 * @return array<string, mixed> The write result.
	 *
	 * @throws RuntimeException On any refusal or failure.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function setMetadataForAgent(AgentFileRequest $request, array $values): array {
		return $this->runWrite(
			request: $request,
			transform: fn (string $bytes, string $extension): array => $this->metadata->writeMetadata(
				packageBytes: $bytes,
				extension: $extension,
				values: $values
			)
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
	 * @param AgentFileRequest $request The acting user, file id, version returned by
	 *                                  the preceding read, and requested output mode.
	 * @param array<string, mixed> $chart The chart definition.
	 * @param string|null $afterAnchor The anchor to place the chart after, or null for the end.
	 *
	 * @return array<string, mixed> The write result.
	 *
	 * @throws RuntimeException On any refusal or failure.
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function embedChartForAgent(AgentFileRequest $request, array $chart, ?string $afterAnchor = null): array {
		return $this->runWrite(
			request: $request,
			transform: fn (string $bytes, string $extension): array => $this->charts->embedChart(
				packageBytes: $bytes,
				extension: $extension,
				chart: $chart,
				afterAnchor: $afterAnchor
			)
		);

	}//end embedChartForAgent()

	/**
	 * Run one agent write: the shared checks, then the guarded session.
	 *
	 * @param AgentFileRequest $request The acting user, file id, version and requested output mode.
	 * @param callable $transform The codec pass, `fn (string $bytes, string $extension): array`.
	 *
	 * @return array<string, mixed> The write result.
	 *
	 * @throws RuntimeException On any refusal or failure.
	 */
	private function runWrite(AgentFileRequest $request, callable $transform): array {
		[$file, $mode] = $this->prepareWrite(request: $request);

		return $this->writer->runSession(
			uid: $request->uid,
			file: $file,
			transform: $transform,
			version: $request->version,
			mode: $mode
		);
	}//end runWrite()

	/**
	 * The checks every agent write shares, run in the order they must run in.
	 *
	 * Extracted because three entry points -- text edits, metadata and charts --
	 * repeated it verbatim. Triplicated preconditions drift: the third copy is
	 * where someone eventually omits `refuseIfGuarded()` and a document under a
	 * signing request becomes editable through the newest tool only.
	 *
	 * @param AgentFileRequest $request The acting user, file id, codec kind, version the
	 *                                  caller is writing against, and requested output mode.
	 *
	 * @return array{0: File, 1: string} The resolved file and output mode.
	 *
	 * @throws RuntimeException When the version is absent, the file is guarded, or it is not writable.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 */
	private function prepareWrite(AgentFileRequest $request): array {
		if (trim($request->version) === '') {
			throw new RuntimeException(
				'A version is required. Read the document first and pass back the version it returned.'
			);
		}

		$file = $this->resolveFile(request: $request);
		$mode = $this->resolveMode(requested: $request->requestedMode);

		$this->refuseIfGuarded(file: $file);

		if ($mode === self::MODE_IN_PLACE && $file->isUpdateable() === false) {
			throw new RuntimeException('You do not have permission to change this file.');
		}

		return [$file, $mode];
	}//end prepareWrite()

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
			AppIdentity::APP_ID,
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
		// Format first. It is the cheapest check and the only one that answers
		// "should this file EVER be content-edited" — a macro-bearing package
		// is refused before any question about signatures or anonymisation,
		// because those are about this document's state and this is about the
		// bytes being a code-execution vector at all.
		// Final second. It is the strongest statement about this document's
		// state, and it must be the one the user hears: a besluit that is final
		// is refused for being final, not for whatever else is also true of it.
		$refusal = ($this->guard->formatRefusal(file: $file)
			?? $this->guard->finalRefusal(file: $file)
			?? $this->guard->signatureRefusal(file: $file)
			?? $this->guard->anonymisationRefusal(file: $file));
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
	 * @param AgentFileRequest $request The acting user, file id and codec kind.
	 *
	 * @return File The file.
	 *
	 * @throws RuntimeException When the id names nothing the user can reach, or an uneditable format.
	 */
	private function resolveFile(AgentFileRequest $request): File {
		$fileId = $request->fileId;
		if (trim($request->uid) === '') {
			throw new RuntimeException('No acting user; document tools require a signed-in user.');
		}

		try {
			$node = $this->rootFolder->getUserFolder($request->uid)->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			throw new RuntimeException('Could not open file ' . $fileId . ': ' . $e->getMessage(), 0, $e);
		}

		if (($node instanceof File) === false) {
			throw new RuntimeException('File ' . $fileId . ' was not found in your files.');
		}

		$this->refuseUneditableFormat(file: $node, kind: $request->kind);

		return $node;
	}//end resolveFile()

	/**
	 * Refuse a file the calling codec does not edit, naming the formats it does.
	 *
	 * @param File $file The resolved file.
	 * @param DocumentKind|null $kind The codec kind the file must match, or null for the text codec.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the file is not an editable format for this codec.
	 */
	private function refuseUneditableFormat(File $file, ?DocumentKind $kind): void {
		// ⚠️ Validated against the codec that will actually handle this file, not
		// against the text codec for everything. A spreadsheet reaching the
		// spreadsheet path was being refused as "not an editable document
		// format" and told to convert itself — advice that would have destroyed
		// the very cells the caller asked to edit.
		$accepts = ($kind->supports ?? fn (string $extension): bool => $this->codecs->text->supports(extension: $extension));
		$supported = ($kind->formats ?? '');
		if ($supported === '') {
			$supported = implode(', ', $this->codecs->text->supportedExtensions());
		}

		if ($accepts($file->getExtension()) === false) {
			throw new RuntimeException(
				sprintf(
					'"%s" is not an editable document format. Editable formats are: %s. '
					. 'Convert the document first if you need to change it.',
					$file->getName(),
					$supported
				)
			);
		}

	}//end refuseUneditableFormat()

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
