<?php
/**
 * Batch Extraction Service
 *
 * Drives sequential per-file text extraction and entity detection for a
 * staged batch. Each call advances the batch by one file, updating the
 * batch's per-file status and transitioning the batch into the "review"
 * phase once every file has been attempted.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Sequentially extracts text and detects entities for files in a batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchExtractionService
{
    /**
     * Constructor for BatchExtractionService
     *
     * @param LoggerInterface      $logger       Logger for error reporting.
     * @param AnonymizationService $anonService  Service that performs text extraction and entity detection.
     * @param BatchStateService    $stateService Service that persists per-batch state between calls.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService,
    ) {

    }//end __construct()

    /**
     * Extract and detect entities for the next pending file in a batch.
     *
     * Locates the first file in the batch that has not yet been extracted,
     * runs extraction against it, and persists the updated batch state.
     * When no pending files remain, the batch transitions to "review".
     *
     * @param string $batchId Identifier of the batch to advance.
     *
     * @return array Progress report for the caller, with shape:
     *   {
     *     batchStatus: string,
     *     fileId?: int|string,
     *     fileName?: string,
     *     entityCount?: int,
     *     error?: ?string,
     *     filesExtracted: int,
     *     totalFiles: int,
     *     message?: string,
     *   }
     *
     * @throws Exception When the batch cannot be found.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
     */
    public function extractNext(string $batchId): array
    {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }

        $idx = null;
        foreach ($batch['files'] as $i => $f) {
            if ($f['status'] === 'uploaded') {
                $idx = $i;
                break;
            }
        }

        if ($idx === null) {
            $batch['status'] = 'review';
            $this->stateService->updateBatch($batchId, $batch);
            return [
                'batchStatus'    => 'review',
                'message'        => 'All files extracted',
                'filesExtracted' => count($batch['files']),
                'totalFiles'     => count($batch['files']),
            ];
        }

        $file = $batch['files'][$idx];
        try {
            $result = $this->anonService->extractAndDetectEntities((int) $file['fileId']);
            $batch['files'][$idx]['status']      = 'extracted';
            $batch['files'][$idx]['entityCount'] = $result['entityCount'];
            $batch['status'] = 'extracting';
        } catch (Exception $e) {
            $this->logger->error('Batch extraction failed', ['fileId' => $file['fileId'], 'exception' => $e]);
            $batch['files'][$idx]['status'] = 'error';
            $batch['files'][$idx]['error']  = $e->getMessage();
        }

        $allDone = true;
        foreach ($batch['files'] as $f) {
            if ($f['status'] === 'uploaded') {
                $allDone = false;
                break;
            }
        }

        if ($allDone === true) {
            $batch['status'] = 'review';
        }

        $this->stateService->updateBatch($batchId, $batch);
        $ext = 0;
        foreach ($batch['files'] as $f) {
            if (in_array($f['status'], ['extracted', 'error'], true) === true) {
                $ext++;
            }
        }

        return [
            'batchStatus'    => $batch['status'],
            'fileId'         => $file['fileId'],
            'fileName'       => $file['fileName'],
            'entityCount'    => $batch['files'][$idx]['entityCount'] ?? 0,
            'error'          => $batch['files'][$idx]['error'] ?? null,
            'filesExtracted' => $ext,
            'totalFiles'     => count($batch['files']),
        ];

    }//end extractNext()
}//end class
