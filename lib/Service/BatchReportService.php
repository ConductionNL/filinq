<?php
/**
 * Batch Report Service
 *
 * Produces a CSV summary of a completed anonymization batch, listing each
 * file's identifiers, entity/replacement counts, final status, and
 * creation timestamp.
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

/**
 * Generates CSV reports summarizing the outcome of a batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchReportService
{
    /**
     * Constructor for BatchReportService
     *
     * @param BatchStateService $stateService Service that exposes stored batch state.
     *
     * @return void
     */
    public function __construct(private readonly BatchStateService $stateService)
    {

    }//end __construct()

    /**
     * Generate a CSV report for a completed batch.
     *
     * @param string $batchId Identifier of the batch to report on.
     *
     * @return string CSV document (header row + one row per file).
     *
     * @throws Exception When the batch cannot be found or is not yet completed.
     */
    public function generateReport(string $batchId): string
    {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }

        if ($batch['status'] !== 'completed') {
            throw new Exception('Batch is not yet completed', 409);
        }

        $output = fopen('php://temp', 'r+');
        fputcsv(
            $output,
            [
                'fileName',
                'originalFileId',
                'anonymizedFileId',
                'entityCount',
                'replacementCount',
                'status',
                'timestamp',
            ]
        );
        foreach ($batch['files'] as $f) {
            fputcsv(
                    $output,
                    [
                        $f['fileName'] ?? '',
                        $f['fileId'] ?? '',
                        $f['anonymizedFileId'] ?? '',
                        $f['entityCount'] ?? 0,
                        $f['replacementCount'] ?? 0,
                        $f['status'] ?? '',
                        date('c', $batch['createdAt'] ?? time()),
                    ]
                    );
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;

    }//end generateReport()
}//end class
