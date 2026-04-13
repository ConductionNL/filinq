<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
use Psr\Log\LoggerInterface;
/**
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class BatchAnonymizeService
{


    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService,
    ) {

    }//end __construct()


    public function anonymizeBatch(string $batchId, array $entities): array
    {
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
                $result = $this->anonService->anonymizeDocument((int) $file['fileId'], $entities);
                $batch['files'][$i]['status']           = 'anonymized';
                $batch['files'][$i]['replacementCount'] = $result['replacementCount'] ?? 0;
                $batch['files'][$i]['anonymizedFileId'] = $result['anonymizedFileId'] ?? null;
                $processed++;
            } catch (Exception $e) {
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $e->getMessage()];
            }
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
