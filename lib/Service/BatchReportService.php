<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
/**
 * Service for generating batch anonymization CSV reports
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchReportService
{
    /**
     * @param BatchStateService $stateService Batch state
     */
    public function __construct(
        private readonly BatchStateService $stateService
    ) {
    }//end __construct()
    /**
     * @param string $batchId Batch ID
     * @return string CSV content
     * @throws Exception If not found/completed
     */
    public function generateReport(string $batchId): string
    {
        $batch = $this->stateService->getBatch(batchId: $batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }
        if ($batch['status'] !== 'completed') {
            throw new Exception('Batch is not yet completed', 409);
        }
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            throw new Exception('Failed to create report output stream', 500);
        }
        fputcsv($output, ['fileName', 'originalFileId', 'anonymizedFileId', 'entityCount', 'replacementCount', 'status', 'timestamp']);
        foreach ($batch['files'] as $file) {
            fputcsv($output, [
                $file['fileName'] ?? '',
                $file['fileId'] ?? '',
                $file['anonymizedFileId'] ?? '',
                $file['entityCount'] ?? 0,
                $file['replacementCount'] ?? 0,
                $file['status'] ?? '',
                date('c', $batch['createdAt'] ?? time()),
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        if ($csv === false) {
            throw new Exception('Failed to read report output', 500);
        }
        return $csv;
    }//end generateReport()
}//end class
