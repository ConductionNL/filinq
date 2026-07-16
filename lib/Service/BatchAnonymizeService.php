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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;

/**
 * Applies a user-reviewed entity list across every file in a batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */
class BatchAnonymizeService
{
    /**
     * Constructor for BatchAnonymizeService
     *
     * @param AnonymizationService $anonService  Service that performs single-document anonymization.
     * @param BatchStateService    $stateService Service that persists per-batch state between calls.
     *
     * @return void
     */
    public function __construct(
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
     * When unredactedEntities is non-empty, the prohibition gate is checked
     * per file. Files with prohibition violations are recorded with a
     * `prohibitionViolation` status and counted in prohibitionSkippedFiles.
     *
     * @param string                           $batchId            Identifier of the batch to anonymize.
     * @param array<int, array<string, mixed>> $entities           User-approved entities to anonymize.
     * @param bool                             $appendBasisSummary Whether to append a grondslagen summary per file.
     * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
     * @param string                           $outputFormat       Per-batch output format gate
     *                                                             ('pdf'|'preserve'). Passed
     *                                                             through to each per-file
     *                                                             anonymise call. Per-file
     *                                                             ConversionFailedException is
     *                                                             recorded as an error on that
     *                                                             file's batch entry and the
     *                                                             batch continues with the
     *                                                             next file.
     * @param string                           $scope              Placeholder-numbering scope forwarded to
     *                                                             OpenRegister for every file. Defaults to
     *                                                             'dossier' because a batch IS a folder/dossier:
     *                                                             a person gets the SAME scope-local number
     *                                                             across all the batch's files.
     *
     * @return array Summary of the run, with shape:
     *   {
     *     batchId: string,
     *     batchStatus: string,
     *     processedFiles: int,
     *     skippedFiles: array<int, array{fileId: mixed, reason: string}>,
     *     prohibitionSkippedFiles: int,
     *     totalFiles: int,
     *   }
     *
     * @throws Exception When the batch cannot be found.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
     */
    public function anonymizeBatch(
        string $batchId,
        array $entities,
        bool $appendBasisSummary=false,
        array $unredactedEntities=[],
        string $outputFormat='pdf',
        string $scope='dossier'
    ): array {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }

        $batch['status'] = 'anonymizing';
        $this->stateService->updateBatch($batchId, $batch);
        $skipped            = [];
        $processed          = 0;
        $prohibitionSkipped = 0;

        foreach ($batch['files'] as $i => $file) {
            if ($file['status'] === 'error') {
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $file['error'] ?? 'Previous error'];
                continue;
            }

            if ($file['status'] !== 'extracted') {
                continue;
            }

            if (empty($unredactedEntities) === false) {
                $violations = $this->anonService->checkUnredactedProhibitions(
                    unredactedEntities: $unredactedEntities
                );
                if (empty($violations) === false) {
                    $batch['files'][$i]['status']            = 'prohibitionViolation';
                    $batch['files'][$i]['prohibitedEntries'] = $violations;
                    $skipped[] = [
                        'fileId'     => $file['fileId'],
                        'reason'     => 'Prohibition violation on unredacted entities',
                        'httpStatus' => 422,
                    ];
                    $prohibitionSkipped++;
                    continue;
                }
            }

            try {
                // dossierKey: null → OpenRegister falls back to each file's
                // parent folder as the dossier (= the batch's folder), so a
                // person is numbered consistently across the batch.
                $result = $this->anonService->anonymizeDocument(
                    fileId: (int) $file['fileId'],
                    entities: $entities,
                    appendBasisSummary: $appendBasisSummary,
                    unredactedEntities: $unredactedEntities,
                    outputFormat: $outputFormat,
                    scope: $scope,
                    dossierKey: null
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

                if (isset($result['createdConsents']) === true) {
                    $batch['files'][$i]['createdConsents'] = $result['createdConsents'];
                }

                $processed++;
            } catch (\OCA\DocuDesk\Exception\ConversionFailedException $e) {
                // PDF conversion exhausted the cascade for this file —
                // mark this file as error, attach the attempts surface
                // for the batch caller to inspect, and continue with
                // the next file.
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
                $batch['files'][$i]['conversionAttempts'] = $e->getAttempts();
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $e->getMessage()];
            } catch (Exception $e) {
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $e->getMessage()];
            }//end try
        }//end foreach

        $batch['status'] = 'completed';
        $this->stateService->updateBatch($batchId, $batch);
        return [
            'batchId'                 => $batchId,
            'batchStatus'             => 'completed',
            'processedFiles'          => $processed,
            'skippedFiles'            => $skipped,
            'prohibitionSkippedFiles' => $prohibitionSkipped,
            'totalFiles'              => count($batch['files']),
        ];

    }//end anonymizeBatch()
}//end class
