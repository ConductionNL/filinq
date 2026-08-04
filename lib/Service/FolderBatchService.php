<?php
/**
 * FolderBatchService
 *
 * Creates anonymization batches from existing Nextcloud folders identified by
 * either ID or path.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
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

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\BackgroundJob\IJobList;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
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
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 */
class FolderBatchService
{

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
     * Constructor for FolderBatchService
     *
     * @param LoggerInterface             $logger               Logger for error reporting
     * @param IRootFolder                 $rootFolder           Root folder for file operations
     * @param IUserSession                $userSession          User session for current user
     * @param BatchStateService           $stateService         Batch state management
     * @param IJobList                    $jobList              Background job list
     * @param AnonymizationService        $anonService          Anonymization/extraction service
     * @param OutputLayoutResolver        $layout               Output-layout helper (used here to
     *                                                          identify legacy `_anonymized` outputs
     *                                                          and exclude them from source discovery).
     * @param ConfidentialityLabelService $confidentialityLabel Read-only files_confidential signal,
     *                                                          used (only when
     *                                                          `docudesk.confidentiality.prioritise_analysis`
     *                                                          is on) as a secondary, tie-breaking
     *                                                          sort key so higher-confidentiality
     *                                                          files are analysed sooner
     *                                                          (files-confidential-labels).
     * @param IAppConfig                  $appConfig            App configuration for the priority-hint flag
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly BatchStateService $stateService,
        private readonly IJobList $jobList,
        private readonly AnonymizationService $anonService,
        OutputLayoutResolver $layout,
        ConfidentialityLabelService $confidentialityLabel,
        IAppConfig $appConfig
    ) {
        $this->enumerator = new FolderFileEnumerator(
            layout: $layout,
            confidentialityLabel: $confidentialityLabel,
            appConfig: $appConfig
        );

    }//end __construct()

    /**
     * Create a batch from an existing Nextcloud folder
     *
     * Resolves the folder via either ID or path, enumerates file children
     * (flat), creates the batch, and fires extraction immediately in the
     * background. Exactly one of $folderId or $folderPath MUST be provided.
     *
     * @param int|null    $folderId   Node ID of the folder, or null
     * @param string|null $folderPath Path relative to the user's root folder, or null
     *
     * @return array<string, mixed> Batch data with batchId, folderId, folderPath, fileCount, files
     *
     * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
     */
    public function createFolderBatch(?int $folderId=null, ?string $folderPath=null): array
    {
        $userId     = $this->getCurrentUserId();
        $userFolder = $this->rootFolder->getUserFolder($userId);

        $node = $this->enumerator->resolveFolder(
            folderId: $folderId,
            folderPath: $folderPath,
            userFolder: $userFolder
        );

        $files = $this->enumerator->enumerate(folder: $node);

        if (empty($files) === true) {
            throw new Exception('No files found in folder', 400);
        }

        $maxFiles = $this->stateService->getMaxFiles();
        if (count($files) > $maxFiles) {
            throw new Exception(
                'Folder contains too many files (found: '.count($files).', maximum: '.$maxFiles.')',
                400
            );
        }

        // Canonical identifiers captured from the resolved node — stored and
        // returned regardless of which input method the caller used.
        $resolvedFolderId   = $node->getId();
        $resolvedFolderPath = $userFolder->getRelativePath($node->getPath()) ?? $node->getPath();
        $inputMethod        = 'path';
        if ($folderId !== null) {
            $inputMethod = 'id';
        }

        $batchFiles = [];
        foreach ($files as $file) {
            $batchFiles[] = [
                'fileId'           => $file->getId(),
                'fileName'         => $file->getName(),
                'status'           => 'uploaded',
                'entityCount'      => 0,
                'replacementCount' => 0,
                'error'            => null,
            ];
        }

        $batch = $this->stateService->createBatch($userId, $batchFiles);

        // Store folder metadata on the batch — both identifiers regardless of input.
        $batch['sourceType'] = 'folder';
        $batch['folderId']   = $resolvedFolderId;
        $batch['folderPath'] = $resolvedFolderPath;
        $this->stateService->updateBatch($batch['batchId'], $batch);

        // Run extraction asynchronously: the shutdown function fires after the
        // HTTP response has been flushed to the client (via fastcgi_finish_request).
        // The IJobList entry serves as a fallback if the shutdown handler is
        // interrupted (e.g. process kill, memory limit).
        $this->jobList->add(FolderExtractionJob::class, ['batchId' => $batch['batchId']]);
        $this->scheduleExtraction(batchId: $batch['batchId']);

        $this->logger->info(
            'Folder batch created, extraction job fired',
            [
                'batchId'     => $batch['batchId'],
                'folderId'    => $resolvedFolderId,
                'folderPath'  => $resolvedFolderPath,
                'fileCount'   => count($batchFiles),
                'inputMethod' => $inputMethod,
            ]
        );

        return $batch;

    }//end createFolderBatch()

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
    private function scheduleExtraction(string $batchId): void
    {
        $stateService = $this->stateService;
        $anonService  = $this->anonService;
        $jobList      = $this->jobList;
        $logger       = $this->logger;

        register_shutdown_function(
            static function () use ($batchId, $stateService, $anonService, $jobList, $logger): void {
                // Flush the response to the client before doing heavy work.
                if (function_exists('fastcgi_finish_request') === true) {
                    fastcgi_finish_request();
                }

                $batch = $stateService->getBatch($batchId);
                if ($batch === null) {
                    return;
                }

                $batch['status'] = 'extracting';
                $stateService->updateBatch($batchId, $batch);

                foreach ($batch['files'] as $i => $file) {
                    if ($file['status'] !== 'uploaded') {
                        continue;
                    }

                    try {
                        $result = $anonService->extractAndDetectEntities((int) $file['fileId']);
                        $batch['files'][$i]['status']      = 'extracted';
                        $batch['files'][$i]['entityCount'] = $result['entityCount'];
                    } catch (\Exception $e) {
                        $logger->warning(
                            'Shutdown extraction failed for file',
                            ['batchId' => $batchId, 'fileId' => $file['fileId'], 'error' => $e->getMessage()]
                        );
                        $batch['files'][$i]['status'] = 'error';
                        $batch['files'][$i]['error']  = $e->getMessage();
                    }

                    $stateService->updateBatch($batchId, $batch);
                }//end foreach

                $batch['status'] = 'review';
                $stateService->updateBatch($batchId, $batch);

                // Extraction completed — remove the fallback background job.
                $jobList->remove(FolderExtractionJob::class, ['batchId' => $batchId]);
            }
        );

    }//end scheduleExtraction()

    /**
     * Get the current user ID
     *
     * @return string The current user ID
     *
     * @throws Exception If no user is logged in
     */
    private function getCurrentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new Exception('No user is currently logged in.', 401);
        }

        return $user->getUID();

    }//end getCurrentUserId()
}//end class
