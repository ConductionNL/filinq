<?php
/**
 * Folder Extraction Background Job
 *
 * Background job that processes all files queued in a folder-based batch:
 * extracts text, detects entities, anonymizes each file with all detected
 * entities, and moves the output to the configured subfolder via
 * FolderBatchService::applyOutputLayout(). Individual file failures are
 * logged and recorded on the file entry without aborting the rest of the batch.
 *
 * @category  BackgroundJob
 * @package   OCA\DocuDesk\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\BackgroundJob;

use Exception;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCA\DocuDesk\Service\FolderBatchService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

/**
 * Background job for folder-driven extract + anonymise: extracts text,
 * detects entities, anonymizes each file, and moves outputs to the configured
 * output subfolder. Processes files sequentially and updates batch state after
 * each file. Individual failures do not abort the batch.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */
class FolderExtractionJob extends QueuedJob
{
    /**
     * Constructor for FolderExtractionJob
     *
     * @param ITimeFactory         $time           Time factory
     * @param AnonymizationService $anonService    Anonymization/extraction service
     * @param BatchStateService    $stateService   Batch state management
     * @param LoggerInterface      $logger         Logger for error reporting
     * @param OutputLayoutResolver $layoutResolver Resolver for source-discovery filter and output subfolder
     * @param IRootFolder          $rootFolder     Root folder for post-process move
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService,
        private readonly LoggerInterface $logger,
        private readonly OutputLayoutResolver $layoutResolver,
        private readonly IRootFolder $rootFolder
    ) {
        parent::__construct(time: $time);

    }//end __construct()

    /**
     * Run the folder extraction + anonymization job
     *
     * Processes each file in the batch sequentially. For each 'uploaded' file:
     * 1. Applies source-discovery filter (skips _anonymized-suffixed files).
     * 2. Extracts text and detects entities.
     * 3. Anonymizes the document with all detected entities.
     * 4. Moves the output to the configured subfolder via
     *    FolderBatchService::applyOutputLayout().
     *
     * Sets batch status to 'completed' when all files have been attempted.
     * Files already in 'anonymized' or other terminal states are skipped
     * (retry-safe: a second job run after the shutdown handler completes is
     * a no-op for those files).
     *
     * @param mixed $argument Job arguments containing batchId
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
     */
    protected function run(mixed $argument): void
    {
        $batchId = $argument['batchId'] ?? '';
        if (empty($batchId) === true) {
            $this->logger->error('FolderExtractionJob: missing batchId');
            return;
        }

        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            $this->logger->error('FolderExtractionJob: batch not found', ['batchId' => $batchId]);
            return;
        }

        $userId = (string) ($batch['userId'] ?? '');

        $batch['status'] = 'extracting';
        $this->stateService->updateBatch($batchId, $batch);

        foreach ($batch['files'] as $i => $file) {
            if ($file['status'] !== 'uploaded') {
                continue;
            }

            // Source-discovery filter: skip legacy _anonymized-suffixed files.
            $fileName = (string) ($file['fileName'] ?? '');
            if ($this->layoutResolver->hasAnonymizedSuffix(fileName: $fileName) === true) {
                $this->logger->debug(
                    'FolderExtractionJob: skipping legacy _anonymized file.',
                    ['batchId' => $batchId, 'fileName' => $fileName]
                );
                $batch['files'][$i]['status'] = 'skipped';
                $this->stateService->updateBatch($batchId, $batch);
                continue;
            }

            $fileId = (int) $file['fileId'];

            try {
                $extractResult = $this->anonService->extractAndDetectEntities($fileId);
                $batch['files'][$i]['entityCount'] = $extractResult['entityCount'];
            } catch (Exception $e) {
                $this->logger->warning(
                    'FolderExtractionJob: extraction failed for file',
                    ['batchId' => $batchId, 'fileId' => $fileId, 'error' => $e->getMessage()]
                );
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
                $this->stateService->updateBatch($batchId, $batch);
                continue;
            }

            try {
                $anonResult = $this->anonService->anonymizeDocument(
                    fileId: $fileId,
                    entities: $extractResult['entities']
                );
                $batch['files'][$i]['replacementCount'] = $anonResult['replacementCount'] ?? 0;
                $batch['files'][$i]['anonymizedFileId'] = $anonResult['anonymizedFileId'] ?? null;

                $moveResult = FolderBatchService::applyOutputLayout(
                    sourceFileId: $fileId,
                    anonymizationResult: $anonResult,
                    userId: $userId,
                    rootFolder: $this->rootFolder,
                    layoutResolver: $this->layoutResolver,
                    logger: $this->logger
                );
                $batch['files'][$i]['anonymizedFilePath'] = $moveResult['anonymizedFilePath'];
                if (isset($moveResult['warning']) === true) {
                    $batch['files'][$i]['warning'] = $moveResult['warning'];
                }

                $batch['files'][$i]['status'] = 'anonymized';
            } catch (Exception $e) {
                $this->logger->warning(
                    'FolderExtractionJob: anonymization failed for file',
                    ['batchId' => $batchId, 'fileId' => $fileId, 'error' => $e->getMessage()]
                );
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
            }//end try

            $this->stateService->updateBatch($batchId, $batch);
        }//end foreach

        $batch['status'] = 'completed';
        $this->stateService->updateBatch($batchId, $batch);

        $anonymized = 0;
        $errors     = 0;
        foreach ($batch['files'] as $f) {
            if ($f['status'] === 'anonymized') {
                $anonymized++;
            } else if ($f['status'] === 'error') {
                $errors++;
            }
        }

        $this->logger->info(
            "FolderExtractionJob completed: {$anonymized} anonymized, {$errors} errors",
            ['batchId' => $batchId]
        );

    }//end run()
}//end class
