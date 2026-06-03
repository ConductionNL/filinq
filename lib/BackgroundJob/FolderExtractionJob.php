<?php
/**
 * Folder Extraction Background Job
 *
 * Background job that processes all files queued in a folder-based batch:
 * extracts text, detects entities, and updates per-file batch state.
 * Individual file failures are logged and recorded on the file entry
 * without aborting the rest of the batch.
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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Background job for extracting text and detecting entities
 * from all files in a folder-based batch.
 *
 * Processes files sequentially, updating batch state after each file.
 * Individual file failures do not abort the batch.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
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
     * @param OutputLayoutResolver $layoutResolver Output layout resolver for source-discovery filter
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService,
        private readonly LoggerInterface $logger,
        private readonly OutputLayoutResolver $layoutResolver
    ) {
        parent::__construct(time: $time);

    }//end __construct()

    /**
     * Run the folder extraction job
     *
     * Processes each file in the batch sequentially. Updates file status
     * to "extracted" or "error" after each file. Sets batch status to
     * "review" when all files have been attempted.
     *
     * @param mixed $argument Job arguments containing batchId
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
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

        $batch['status'] = 'extracting';
        $this->stateService->updateBatch($batchId, $batch);

        foreach ($batch['files'] as $i => $file) {
            if ($file['status'] !== 'uploaded') {
                continue;
            }

            // Skip legacy _anonymized-suffixed files: these are redacted outputs
            // from pre-layout runs and must not be re-extracted by the job.
            $fileName = $file['fileName'] ?? '';
            if ($this->layoutResolver->hasAnonymizedSuffix(fileName: $fileName) === true) {
                $this->logger->debug(
                    'FolderExtractionJob: skipping legacy _anonymized file.',
                    ['batchId' => $batchId, 'fileName' => $fileName]
                );
                $batch['files'][$i]['status'] = 'skipped';
                $this->stateService->updateBatch($batchId, $batch);
                continue;
            }

            try {
                $result = $this->anonService->extractAndDetectEntities((int) $file['fileId']);
                $batch['files'][$i]['status']      = 'extracted';
                $batch['files'][$i]['entityCount'] = $result['entityCount'];
            } catch (Exception $e) {
                $this->logger->warning(
                    'FolderExtractionJob: extraction failed for file',
                    ['batchId' => $batchId, 'fileId' => $file['fileId'], 'error' => $e->getMessage()]
                );
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
            }

            $this->stateService->updateBatch($batchId, $batch);
        }//end foreach

        $batch['status'] = 'review';
        $this->stateService->updateBatch($batchId, $batch);

        $extracted = 0;
        $errors    = 0;
        foreach ($batch['files'] as $f) {
            if ($f['status'] === 'extracted') {
                $extracted++;
            } else if ($f['status'] === 'error') {
                $errors++;
            }
        }

        $this->logger->info(
            "FolderExtractionJob completed: {$extracted} extracted, {$errors} errors",
            ['batchId' => $batchId]
        );

    }//end run()
}//end class
