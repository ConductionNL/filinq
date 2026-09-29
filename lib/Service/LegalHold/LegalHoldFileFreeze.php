<?php

/**
 * The file freeze: an app lock on the files behind a held record.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\LegalHold
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.6
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCA\Filinq\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use Throwable;

/**
 * The backstop behind the record freeze.
 *
 * A record hold stops OpenRegister destroying the record; it does not stop
 * somebody deleting the file in Files, over WebDAV or from a sync client. An
 * app-scoped lock (files_lock) does. Without files_lock this says so and locks
 * nothing, and the case never claims file protection it does not have.
 *
 * The files behind a record: its `fileId` property, and the files in its
 * OpenRegister folder.
 */
class LegalHoldFileFreeze {

	/**
	 * Constructor.
	 *
	 * @param ILockManager $lockManager The file lock provider (files_lock).
	 * @param IRootFolder  $rootFolder  The file tree.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ILockManager $lockManager,
		private readonly IRootFolder $rootFolder,
	) {

	}//end __construct()

	/**
	 * Whether a lock provider is installed.
	 *
	 * @return bool False when files_lock is absent.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.6
	 */
	public function available(): bool {
		try {
			return $this->lockManager->isLockProviderAvailable();
		} catch (Throwable) {
			return false;
		}

	}//end available()

	/**
	 * Lock the files behind a record.
	 *
	 * @param object $entity The record.
	 *
	 * @return array{file: string, fileIds: array<int, int>, fileError?: string} The outcome.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.6
	 */
	public function lock(object $entity): array {
		if ($this->available() === false) {
			return ['file' => 'unavailable', 'fileIds' => []];
		}

		$files = $this->files(entity: $entity);
		if ($files === []) {
			return ['file' => 'no_files', 'fileIds' => []];
		}

		$locked = [];
		foreach ($files as $file) {
			try {
				$this->lockManager->lock(new LockContext($file, ILock::TYPE_APP, Application::APP_ID));
			} catch (Throwable $e) {
				// Locked already by us (another case) counts; anything else fails.
				if ($this->lockedByUs(fileId: $file->getId()) === false) {
					return ['file' => 'failed', 'fileIds' => $locked, 'fileError' => mb_substr($e->getMessage(), 0, 500)];
				}
			}

			$locked[] = (int) $file->getId();
		}

		return ['file' => 'locked', 'fileIds' => $locked];

	}//end lock()

	/**
	 * Unlock the files behind a record, unless another active case still covers it.
	 *
	 * @param object      $entity       The record.
	 * @param string|null $survivorUuid An other active case covering it, or null.
	 *
	 * @return array{file: string, fileIds: array<int, int>, fileError?: string} The outcome.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.6
	 */
	public function unlock(object $entity, ?string $survivorUuid): array {
		if ($survivorUuid !== null) {
			return ['file' => 'kept_for_other_case', 'fileIds' => []];
		}

		if ($this->available() === false) {
			return ['file' => 'unavailable', 'fileIds' => []];
		}

		$unlocked = [];
		foreach ($this->files(entity: $entity) as $file) {
			if ($this->lockedByUs(fileId: $file->getId()) === false) {
				continue;
			}

			try {
				$this->lockManager->unlock(new LockContext($file, ILock::TYPE_APP, Application::APP_ID));
			} catch (Throwable $e) {
				return ['file' => 'failed', 'fileIds' => $unlocked, 'fileError' => mb_substr($e->getMessage(), 0, 500)];
			}

			$unlocked[] = (int) $file->getId();
		}

		return ['file' => 'unlocked', 'fileIds' => $unlocked];

	}//end unlock()

	/**
	 * Whether this app holds a lock on the file.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when an app lock owned by this app is on it.
	 */
	private function lockedByUs(int $fileId): bool {
		try {
			foreach ($this->lockManager->getLocks($fileId) as $lock) {
				if ($lock->getType() === ILock::TYPE_APP && $lock->getOwner() === Application::APP_ID) {
					return true;
				}
			}
		} catch (Throwable) {
			return false;
		}

		return false;

	}//end lockedByUs()

	/**
	 * The files behind a record: its `fileId` property and the files in its folder.
	 *
	 * @param object $entity The record.
	 *
	 * @return array<int, File> The files, each once.
	 */
	private function files(object $entity): array {
		$files = [];
		$data = [];
		if (method_exists($entity, 'getObject') === true) {
			$data = (array) $entity->getObject();
		}

		$named = $this->node(id: (int) ($data['fileId'] ?? 0));
		if ($named instanceof File) {
			$files[$named->getId()] = $named;
		}

		foreach ($this->folderFiles(entity: $entity) as $file) {
			$files[$file->getId()] = $file;
		}

		return array_values($files);

	}//end files()

	/**
	 * The files directly in the record's OpenRegister folder.
	 *
	 * @param object $entity The record.
	 *
	 * @return array<int, File> The files.
	 */
	private function folderFiles(object $entity): array {
		$folderId = null;
		if (method_exists($entity, 'getFolder') === true) {
			$folderId = $entity->getFolder();
		}

		if (is_numeric($folderId) === false) {
			return [];
		}

		$folder = $this->node(id: (int) $folderId);
		if (($folder instanceof Folder) === false) {
			return [];
		}

		return array_values(array_filter($folder->getDirectoryListing(), static fn ($node): bool => $node instanceof File));

	}//end folderFiles()

	/**
	 * A node by id anywhere in the tree, or null.
	 *
	 * @param int $id The node id.
	 *
	 * @return mixed The node.
	 */
	private function node(int $id): mixed {
		if ($id <= 0) {
			return null;
		}

		try {
			return $this->rootFolder->getFirstNodeById($id);
		} catch (Throwable) {
			return null;
		}

	}//end node()
}//end class
