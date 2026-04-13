<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
/**
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class BatchReportService
{


    public function __construct(private readonly BatchStateService $stateService)
    {

    }//end __construct()


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
        fputcsv($output, ['fileName', 'originalFileId', 'anonymizedFileId', 'entityCount', 'replacementCount', 'status', 'timestamp']);
        foreach ($batch['files'] as $f) {
            fputcsv($output, [$f['fileName'] ?? '', $f['fileId'] ?? '', $f['anonymizedFileId'] ?? '', $f['entityCount'] ?? 0, $f['replacementCount'] ?? 0, $f['status'] ?? '', date('c', $batch['createdAt'] ?? time())]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;

    }//end generateReport()


}//end class
