<?php
/**
 * Batch Anonymize Service
 *
 * Iterates over the files in a reviewed batch and applies the user-approved
 * entity list to each document via AnonymizationService. Failures are
 * recorded on the file entry and reported back to the caller rather than
 * aborting the batch.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Applies a user-reviewed entity list across every file in a batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchAnonymizeService
{


    /**
     * Constructor for BatchAnonymizeService
     *
     * @param LoggerInterface      $logger       Logger for error reporting.
     * @param AnonymizationService $anonService  Service that performs single-document anonymization.
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
     * Anonymize every extracted file in a batch using the approved entity list.
     *
     * When appendBasisSummary is true the flag is forwarded to each per-file
     * anonymization call. Summary failures are collected as per-file `warning`
     * entries and do not abort the batch; the batch always completes as
     * HTTP 200.  Files with a non-extracted status are skipped (previous
     * errors are recorded in the skipped list; other states are ignored).
     *
     * @param string                           $batchId            Identifier of the batch to anonymize.
     * @param array<int, array<string, mixed>> $entities           User-approved entities to anonymize.
     * @param bool                             $appendBasisSummary Whether to append a grondslagen summary per file.
     *
     * @return array Summary of the run, with shape:
     *   {
     *     batchId: string,
     *     batchStatus: string,
     *     processedFiles: int,
     *     skippedFiles: array<int, array{fileId: mixed, reason: string}>,
     *     totalFiles: int,
     *   }
     *
     * @throws Exception When the batch cannot be found.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
     */
    public function anonymizeBatch(
        string $batchId,
        array $entities,
        bool $appendBasisSummary=false
    ): array {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }

        $batch['status'] = 'anonymizing';
        $this->stateService->updateBatch($batchId, $batch);
        $skipped   = [];
        $processed = 0;
        foreach ($batch['files'] as $i => $file) {
            if ($file['status'] === 'error') {
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $file['error'] ?? 'Previous error'];
                continue;
            }

            if ($file['status'] !== 'extracted') {
                continue;
            }

            try {
                $result = $this->anonService->anonymizeDocument(
                    fileId: (int) $file['fileId'],
                    entities: $entities,
                    appendBasisSummary: $appendBasisSummary
                );
                $batch['files'][$i]['status']           = 'anonymized';
                $batch['files'][$i]['replacementCount'] = $result['replacementCount'] ?? 0;
                $batch['files'][$i]['anonymizedFileId'] = $result['anonymizedFileId'] ?? null;
                if (isset($result['warning']) === true) {
                    $batch['files'][$i]['warning'] = $result['warning'];
                }

                if (isset($result['summaryFileId']) === true) {
                    $batch['files'][$i]['summaryFileId']   = $result['summaryFileId'];
                    $batch['files'][$i]['summaryFilePath'] = $result['summaryFilePath'] ?? null;
                }

                $processed++;
            } catch (Exception $e) {
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $e->getMessage()];
            }//end try
        }//end foreach

        $batch['status'] = 'completed';
        $this->stateService->updateBatch($batchId, $batch);
        return [
            'batchId'        => $batchId,
            'batchStatus'    => 'completed',
            'processedFiles' => $processed,
            'skippedFiles'   => $skipped,
            'totalFiles'     => count($batch['files']),
        ];

    }//end anonymizeBatch()


}//end class
