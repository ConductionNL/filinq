<?php

/**
 * OutputLayoutMover
 *
 * Moves a redacted output that OpenRegister wrote as
 * `<source>/<base>_anonymized.<ext>` into the configured output subfolder,
 * `<source>/<subfolder>/<clean-base>.<ext>`, as one Nextcloud move.
 *
 * One mover serves both batch surfaces: the batch-upload flow
 * (`BatchAnonymizeService`) and the folder flow (`FolderExtractionJob`), so
 * the two cannot disagree about where a redacted file lands. The destination
 * itself is computed by {@see OutputLayoutResolver}; this class owns the
 * filesystem half: locate the node, create the subfolder when missing,
 * replace a previous run's file of the same name, and move.
 *
 * A move that fails never fails the anonymisation: the redacted file still
 * exists at the legacy path, so the outcome names that path and carries a
 * `MOVE_FAILED` warning instead of claiming the new layout.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use Exception;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

/**
 * Relocates a redacted output into the configured output subfolder.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
 */
class OutputLayoutMover {

	/**
	 * Warning code recorded when the output stayed at its legacy path.
	 */
	public const MOVE_FAILED = 'MOVE_FAILED';

	/**
	 * Constructor for OutputLayoutMover.
	 *
	 * @param OutputLayoutResolver $layoutResolver Computes the destination path.
	 * @param IRootFolder $rootFolder Root folder for node lookups.
	 * @param LoggerInterface $logger Logger for move failures.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OutputLayoutResolver $layoutResolver,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Move one redacted output into the output subfolder.
	 *
	 * @param string $userId Owning user id.
	 * @param int $anonymizedFileId File id of the redacted output.
	 * @param string $legacyPath Path OpenRegister wrote the output to.
	 *
	 * @return array{path: string, warning: array{code: string, message: string}|null} Where the
	 *         output is now, plus a warning when it stayed at the legacy path.
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-4
	 */
	public function relocate(string $userId, int $anonymizedFileId, string $legacyPath): array {
		try {
			$anonFile = $this->findFile(userId: $userId, fileId: $anonymizedFileId);
			if ($anonFile === null) {
				return $this->stayed(
					legacyPath: $legacyPath,
					message: 'Anonymized output node could not be located; left at legacy path.'
				);
			}

			$sourceFolder = $anonFile->getParent();
			$sourceName   = $anonFile->getName();
			$subfolderName = $this->layoutResolver->readSubfolderName();
			$targetPath    = $this->layoutResolver->resolveBatchDestination(
				$sourceFolder->getPath(),
				pathinfo($sourceName, PATHINFO_FILENAME),
				pathinfo($sourceName, PATHINFO_EXTENSION)
			);

			$subfolder = $this->ensureSubfolder(sourceFolder: $sourceFolder, name: $subfolderName);
			$this->replacePrevious(subfolder: $subfolder, targetName: basename($targetPath));

			$anonFile->move($targetPath);

			return ['path' => $targetPath, 'warning' => null];
		} catch (Exception $e) {
			$this->logger->warning(
				'Filinq: moving the anonymised output into its subfolder failed; it stays at the legacy path',
				['fileId' => $anonymizedFileId, 'error' => $e->getMessage()]
			);
			return $this->stayed(legacyPath: $legacyPath, message: $e->getMessage());
		}//end try
	}//end relocate()

	/**
	 * Look the redacted output up in the user's folder.
	 *
	 * @param string $userId Owning user id.
	 * @param int $fileId File id of the redacted output.
	 *
	 * @return File|null The file node, or null when it cannot be found.
	 */
	private function findFile(string $userId, int $fileId): ?File {
		foreach ($this->rootFolder->getUserFolder($userId)->getById($fileId) as $node) {
			if ($node instanceof File) {
				return $node;
			}
		}

		return null;
	}//end findFile()

	/**
	 * Return the output subfolder, creating it when it does not exist yet.
	 *
	 * @param Folder $sourceFolder Folder holding the source file.
	 * @param string $name Validated subfolder name.
	 *
	 * @return mixed The subfolder node.
	 */
	private function ensureSubfolder(Folder $sourceFolder, string $name): mixed {
		if ($sourceFolder->nodeExists($name) === false) {
			return $sourceFolder->newFolder($name);
		}

		return $sourceFolder->get($name);
	}//end ensureSubfolder()

	/**
	 * Remove a previous run's output of the same name, so the new one replaces it.
	 *
	 * Files in the subfolder that this run does not produce are left alone.
	 *
	 * @param mixed $subfolder The output subfolder node.
	 * @param string $targetName File name the new output will take.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/specs/batch-anonymization/spec.md#requirement-when-the-subfolder-already-exists-files-must-be-overwritten-by-destination-filename
	 */
	private function replacePrevious(mixed $subfolder, string $targetName): void {
		if ($subfolder instanceof Folder && $subfolder->nodeExists($targetName) === true) {
			$subfolder->get($targetName)->delete();
		}
	}//end replacePrevious()

	/**
	 * Outcome for an output that stayed at the legacy path.
	 *
	 * @param string $legacyPath Path OpenRegister wrote the output to.
	 * @param string $message Why the move did not happen.
	 *
	 * @return array{path: string, warning: array{code: string, message: string}} The outcome.
	 */
	private function stayed(string $legacyPath, string $message): array {
		return [
			'path'    => $legacyPath,
			'warning' => ['code' => self::MOVE_FAILED, 'message' => $message],
		];
	}//end stayed()
}//end class
