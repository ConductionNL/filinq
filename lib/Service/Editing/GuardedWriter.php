<?php

/**
 * DocuDesk GuardedWriter
 *
 * Performs the guarded read-modify-write: take the lock, check the version
 * precondition, run the caller's transform, mark the artefact, write, release.
 *
 * 🔑 Extracted from EditSessionService rather than suppressed. That class had
 * grown to orchestrate three document kinds plus metadata and charts, and the
 * complexity measurement was telling the truth: "which codec handles this file"
 * and "how a write is made safe" are two jobs. The lock discipline and the
 * version precondition are the controls that make an in-place write safe, and
 * a per-kind write path would eventually re-implement one of them wrongly —
 * so there is exactly one of them, here.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
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
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Runs one guarded write.
 */
class GuardedWriter {

	/**
	 * Largest package this writer will load into memory.
	 *
	 * @var int
	 */
	private const MAX_BYTES = 26214400;

	/**
	 * Write the change to a new file beside the source, leaving it untouched.
	 *
	 * Defined here rather than reached for on EditSessionService: the writer is
	 * what acts on the mode, and a class that reaches back into its caller for
	 * a constant is a circular dependency waiting to be discovered.
	 *
	 * @var string
	 */
	public const MODE_SIBLING = 'sibling';

	/**
	 * Constructor.
	 *
	 * @param ILockManager        $lockManager The file lock manager.
	 * @param AgentArtefactMarker $marker      The ADR-088 artefact marker.
	 * @param LoggerInterface     $logger      Logger for diagnostics.
	 * @param IRootFolder         $rootFolder  The Nextcloud root folder.
	 */
	public function __construct(
		private readonly ILockManager $lockManager,
		private readonly AgentArtefactMarker $marker,
		private readonly LoggerInterface $logger,
		private readonly IRootFolder $rootFolder,
	) {
	}//end __construct()


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
	public function runSession(string $uid, File $file, callable $transform, string $version, string $mode): array {
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
