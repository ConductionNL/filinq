<?php

/**
 * FolderBatchService
 *
 * Creates anonymization batches from existing Nextcloud folders identified by
 * either ID or path.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-2
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCA\Filinq\Service\Support\BatchQueue;
use OCA\Filinq\Service\Support\FolderEnumerationSources;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Service for creating batches from existing Nextcloud folders.
 *
 * Accepts either a folder path or a folder ID, enumerates direct children of
 * the resolved folder (flat, no recursion), creates a batch via
 * BatchStateService, and fires a FolderExtractionJob immediately for
 * background extraction.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 */
class FolderBatchService implements FolderBatchServiceInterface {

	/**
	 * Resolves the source folder and enumerates its analysable files.
	 *
	 * Composed from the injected layout / confidentiality / app-config
	 * collaborators rather than injected directly, so the constructor's
	 * published shape stays unchanged.
	 *
	 * @var FolderFileEnumerator
	 */
	private readonly FolderFileEnumerator $enumerator;

	/**
	 * Batch state management.
	 *
	 * @var BatchStateService
	 */
	private readonly BatchStateService $stateService;

	/**
	 * Background job list.
	 *
	 * @var JobQueueInterface
	 */
	private readonly JobQueueInterface $jobList;

	/**
	 * Picks the in-request or background path by batch size; null keeps the synchronous cap as the only cap.
	 *
	 * @var BatchRouting|null
	 */
	private readonly ?BatchRouting $routing;

	/**
	 * Constructor for FolderBatchService
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param IRootFolder $rootFolder Root folder for file operations
	 * @param IUserSession $userSession User session for current user
	 * @param BatchQueue $queue The batch state and the job queue
	 * @param AnonymizationService $anonService Anonymization/extraction service
	 * @param FolderEnumerationSources $enumeration The output layout (to leave legacy
	 *                                              `_anonymized` outputs out), the
	 *                                              confidentiality labels (a tie-breaking
	 *                                              sort key when prioritised analysis is
	 *                                              on) and the app configuration that
	 *                                              holds that flag
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		BatchQueue $queue,
		private readonly AnonymizationService $anonService,
		FolderEnumerationSources $enumeration,
	) {
		$this->stateService = $queue->state;
		$this->jobList = $queue->jobs;
		$this->routing = $queue->routing;
		$this->enumerator = new FolderFileEnumerator(
			layout: $enumeration->layout,
			confidentialityLabel: $enumeration->confidentialityLabel,
			appConfig: $enumeration->appConfig,
			renditions: $enumeration->renditions
		);

	}//end __construct()

	/**
	 * Create a batch from an existing Nextcloud folder
	 *
	 * Resolves the folder via either ID or path, enumerates file children
	 * (flat), creates the batch, and fires extraction immediately in the
	 * background. Exactly one of $folderId or $folderPath MUST be provided.
	 *
	 * @param int|null $folderId Node ID of the folder, or null
	 * @param string|null $folderPath Path relative to the user's root folder, or null
	 *
	 * @return array<string, mixed> Batch data with batchId, folderId, folderPath, fileCount, files
	 *
	 * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
	 */
	public function createFolderBatch(?int $folderId = null, ?string $folderPath = null): array {
		return $this->createBatchFromFolder(folderId: $folderId, folderPath: $folderPath, recursive: false);
	}//end createFolderBatch()

	/**
	 * Create a batch from a folder and every subfolder below it.
	 *
	 * As createFolderBatch(), but the files of every subfolder are included
	 * (the anonymised-output subfolder excepted), each recording its path
	 * below the folder; the size cap applies to the whole tree.
	 *
	 * @param int|null    $folderId   Node ID of the folder, or null
	 * @param string|null $folderPath Path relative to the user's root folder, or null
	 *
	 * @return array<string, mixed> Batch data with batchId, folderId, folderPath, fileCount, files
	 *
	 * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-optional-recursive-folder-enumeration-req-ddras-011
	 */
	public function createRecursiveFolderBatch(?int $folderId = null, ?string $folderPath = null): array {
		return $this->createBatchFromFolder(folderId: $folderId, folderPath: $folderPath, recursive: true);
	}//end createRecursiveFolderBatch()

	/**
	 * Create a folder batch, flat or over the whole tree.
	 *
	 * @param int|null    $folderId   Node ID of the folder, or null
	 * @param string|null $folderPath Path relative to the user's root folder, or null
	 * @param bool        $recursive  Whether subfolders are included
	 *
	 * @return array<string, mixed> The batch
	 *
	 * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
	 */
	private function createBatchFromFolder(?int $folderId, ?string $folderPath, bool $recursive): array {
		$userId = $this->getCurrentUserId();
		$userFolder = $this->rootFolder->getUserFolder($userId);

		$node = $this->enumerator->resolveFolder(
			folderId: $folderId,
			folderPath: $folderPath,
			userFolder: $userFolder
		);

		$files = $this->requireFiles(folder: $node, recursive: $recursive);
		$path = $this->pathFor(fileCount: count($files));
		$batchFiles = $this->startingFileRecords(files: $files, root: $node, recursive: $recursive);

		// Canonical identifiers captured from the resolved node — stored and
		// returned regardless of which input method the caller used.
		$folderFields = [
			'sourceType' => 'folder',
			'folderId' => $node->getId(),
			'folderPath' => ($userFolder->getRelativePath($node->getPath()) ?? $node->getPath()),
		];

		if ($recursive === true) {
			$folderFields['recursive'] = true;
		}

		$batch = array_merge($this->stateService->createBatch($userId, $batchFiles), $folderFields);
		if ($path === BatchRouting::BACKGROUND) {
			return $this->startInBackground(batch: $batch);
		}

		$this->stateService->updateBatch($batch['batchId'], $batch);

		// Run extraction asynchronously: the shutdown function fires after the
		// HTTP response has been flushed to the client (via fastcgi_finish_request).
		// The IJobList entry serves as a fallback if the shutdown handler is
		// interrupted (e.g. process kill, memory limit).
		$this->jobList->add(JobQueueInterface::FOLDER_EXTRACTION, ['batchId' => $batch['batchId']]);
		$this->scheduleExtraction(batchId: $batch['batchId']);

		$inputMethod = 'path';
		if ($folderId !== null) {
			$inputMethod = 'id';
		}

		$this->logger->info(
			'Folder batch created, extraction job fired',
			['batchId' => $batch['batchId'], 'folderId' => $batch['folderId'], 'folderPath' => $batch['folderPath']]
				+ ['fileCount' => count($batchFiles), 'inputMethod' => $inputMethod]
		);

		return $batch;
	}//end createBatchFromFolder()

	/**
	 * The folder's files, refused when there are none.
	 *
	 * @param Folder $folder    The resolved folder.
	 * @param bool   $recursive Whether subfolders are included.
	 *
	 * @return array<int, \OCP\Files\File> The files.
	 *
	 * @throws Exception Code 400 when the folder is empty.
	 */
	private function requireFiles(Folder $folder, bool $recursive): array {
		$files = [];
		if ($recursive === true) {
			$files = $this->enumerator->enumerateRecursive(folder: $folder);
		}

		if ($recursive === false) {
			$files = $this->enumerator->enumerate(folder: $folder);
		}

		if (empty($files) === true) {
			throw new Exception('No files found in folder', 400);
		}

		return $files;
	}//end requireFiles()

	/**
	 * The path a folder batch of this size takes, refused above the cap that applies.
	 *
	 * Checked against the whole (recursive) count before anything is created.
	 *
	 * @param int $fileCount The number of files.
	 *
	 * @return string BatchRouting::SYNC or BatchRouting::BACKGROUND.
	 *
	 * @throws Exception Code 400, naming the maximum, when the batch is too large.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	private function pathFor(int $fileCount): string {
		if ($this->routing !== null) {
			return $this->routing->route(fileCount: $fileCount);
		}

		$maxFiles = $this->stateService->getMaxFiles();
		if ($fileCount > $maxFiles) {
			throw new Exception(
				'Folder contains too many files (found: ' . $fileCount . ', maximum: ' . $maxFiles . ')',
				400
			);
		}

		return BatchRouting::SYNC;
	}//end pathFor()

	/**
	 * Hand a folder batch above the synchronous cap to the cron coordinator.
	 *
	 * No shutdown extraction and no queued job: the coordinator's work units
	 * extract it (REQ-DDRAS-002).
	 *
	 * @param array<string, mixed> $batch The created batch.
	 *
	 * @return array<string, mixed> The batch, marked background and extracting.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	private function startInBackground(array $batch): array {
		$batch['background'] = true;
		$batch['status'] = 'extracting';
		$batch['totalFiles'] = count($batch['files']);
		$this->stateService->updateBatch($batch['batchId'], $batch);
		$this->logger->info(
			'Folder batch created for background processing',
			['batchId' => $batch['batchId'], 'fileCount' => $batch['totalFiles']]
		);
		return $batch;
	}//end startInBackground()

	/**
	 * Each file's record as a new batch starts it: uploaded, nothing found yet.
	 *
	 * @param array<int, \OCP\Files\File> $files     The files.
	 * @param Folder                       $root      The batch's folder.
	 * @param bool                         $recursive Whether to record each file's path below the folder.
	 *
	 * @return array<int, array<string, mixed>> The records.
	 */
	private function startingFileRecords(array $files, Folder $root, bool $recursive): array {
		$records = [];
		foreach ($files as $file) {
			$record = [
				'fileId' => $file->getId(),
				'fileName' => $file->getName(),
				'status' => 'uploaded',
				'entityCount' => 0,
				'replacementCount' => 0,
				'error' => null,
			];
			if ($recursive === true) {
				$record['relativePath'] = ltrim(($root->getRelativePath($file->getPath()) ?? $file->getName()), '/');
			}

			$records[] = $record;
		}

		return $records;
	}//end startingFileRecords()

	/**
	 * Schedule extraction to run after the HTTP response is flushed
	 *
	 * Registers a shutdown function that flushes the response to the client
	 * (via fastcgi_finish_request on PHP-FPM) and then runs extraction
	 * inline. If the extraction completes, the queued background job is
	 * removed since it is no longer needed.
	 *
	 * @param string $batchId The batch ID to extract
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
	 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
	 */
	private function scheduleExtraction(string $batchId): void {
		register_shutdown_function(
			function () use ($batchId): void {
				// Flush the response to the client before doing heavy work.
				if (function_exists('fastcgi_finish_request') === true) {
					fastcgi_finish_request();
				}

				$this->extractBatch(batchId: $batchId);
			}
		);

	}//end scheduleExtraction()

	/**
	 * Extract and detect entities in every uploaded file of a batch, then hand it to review.
	 *
	 * A file that fails is marked `error` with its message; the others go on.
	 * The fallback background job is removed once the batch reaches review.
	 *
	 * @param string $batchId The batch.
	 *
	 * @return void
	 */
	private function extractBatch(string $batchId): void {
		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			return;
		}

		$batch['status'] = 'extracting';
		$this->stateService->updateBatch($batchId, $batch);

		foreach ($batch['files'] as $i => $file) {
			if ($file['status'] !== 'uploaded') {
				continue;
			}

			try {
				$result = $this->anonService->extractAndDetectEntities((int)$file['fileId']);
				$batch['files'][$i]['status'] = 'extracted';
				$batch['files'][$i]['entityCount'] = $result['entityCount'];
			} catch (\Exception $e) {
				$this->logger->warning(
					'Shutdown extraction failed for file',
					['batchId' => $batchId, 'fileId' => $file['fileId'], 'error' => $e->getMessage()]
				);
				$batch['files'][$i]['status'] = 'error';
				$batch['files'][$i]['error'] = $e->getMessage();
			}

			$this->stateService->updateBatch($batchId, $batch);
		}//end foreach

		$batch['status'] = 'review';
		$this->stateService->updateBatch($batchId, $batch);

		// Extraction completed — remove the fallback background job.
		$this->jobList->remove(JobQueueInterface::FOLDER_EXTRACTION, ['batchId' => $batchId]);

	}//end extractBatch()

	/**
	 * Get the current user ID
	 *
	 * @return string The current user ID
	 *
	 * @throws Exception If no user is logged in
	 */
	private function getCurrentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new Exception('No user is currently logged in.', 401);
		}

		return $user->getUID();
	}//end getCurrentUserId()
}//end class
