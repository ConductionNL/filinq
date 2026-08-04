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
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
     */
    public function extractNext(string $batchId): array
    {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }

        $idx = $this->findNextPendingIndex(files: $batch['files']);

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

        $file  = $batch['files'][$idx];
        $batch = $this->extractFileIntoBatch(batch: $batch, index: $idx);

        if ($this->hasPendingFiles(files: $batch['files']) === false) {
            $batch['status'] = 'review';
        }

        $this->stateService->updateBatch($batchId, $batch);

        return [
            'batchStatus'    => $batch['status'],
            'fileId'         => $file['fileId'],
            'fileName'       => $file['fileName'],
            'entityCount'    => $batch['files'][$idx]['entityCount'] ?? 0,
            'error'          => $batch['files'][$idx]['error'] ?? null,
            'filesExtracted' => $this->countProcessedFiles(files: $batch['files']),
            'totalFiles'     => count($batch['files']),
        ];

    }//end extractNext()

    /**
     * Find the key of the first file still awaiting extraction.
     *
     * @param array<int|string, array<string, mixed>> $files The batch's file entries.
     *
     * @return int|string|null The key of the first `uploaded` entry, or null when none remain.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
     */
    private function findNextPendingIndex(array $files): int|string|null
    {
        foreach ($files as $key => $entry) {
            if ($entry['status'] === 'uploaded') {
                return $key;
            }
        }

        return null;

    }//end findNextPendingIndex()

    /**
     * Run extraction for one file entry and fold the outcome back into the batch.
     *
     * On success the entry is marked `extracted` with its entity count and the
     * batch moves to `extracting`; on failure the entry is marked `error` with
     * the exception message and the batch status is left untouched.
     *
     * @param array<string, mixed> $batch The batch state.
     * @param int|string           $index The key of the file entry to extract.
     *
     * @return array<string, mixed> The updated batch state.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
     */
    private function extractFileIntoBatch(array $batch, int|string $index): array
    {
        $file = $batch['files'][$index];

        try {
            $result = $this->anonService->extractAndDetectEntities((int) $file['fileId']);
            $batch['files'][$index]['status']      = 'extracted';
            $batch['files'][$index]['entityCount'] = $result['entityCount'];
            $batch['status'] = 'extracting';
        } catch (Exception $e) {
            $this->logger->error('Batch extraction failed', ['fileId' => $file['fileId'], 'exception' => $e]);
            $batch['files'][$index]['status'] = 'error';
            $batch['files'][$index]['error']  = $e->getMessage();
        }

        return $batch;

    }//end extractFileIntoBatch()

    /**
     * Determine whether any file entry is still awaiting extraction.
     *
     * @param array<int|string, array<string, mixed>> $files The batch's file entries.
     *
     * @return bool True when at least one entry is still `uploaded`.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
     */
    private function hasPendingFiles(array $files): bool
    {
        foreach ($files as $entry) {
            if ($entry['status'] === 'uploaded') {
                return true;
            }
        }

        return false;

    }//end hasPendingFiles()

    /**
     * Count file entries that reached a terminal state (extracted or error).
     *
     * @param array<int|string, array<string, mixed>> $files The batch's file entries.
     *
     * @return int The number of processed entries.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
     */
    private function countProcessedFiles(array $files): int
    {
        $processed = 0;
        foreach ($files as $entry) {
            if (in_array($entry['status'], ['extracted', 'error'], true) === true) {
                $processed++;
            }
        }

        return $processed;

    }//end countProcessedFiles()
}//end class
