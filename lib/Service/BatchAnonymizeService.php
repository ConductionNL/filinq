<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
use Psr\Log\LoggerInterface;
/**
 * Service for batch anonymization of documents
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchAnonymizeService
{
    /**
     * @param LoggerInterface      $logger       Logger
     * @param AnonymizationService $anonService  Anonymization service
     * @param BatchStateService    $stateService Batch state
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService
    ) {
    }//end __construct()
    /**
     * @param string                           $batchId  Batch ID
     * @param array<int, array<string, mixed>> $entities Entities
     * @return array<string, mixed>
     * @throws Exception If batch not found
     */
    public function anonymizeBatch(string $batchId, array $entities): array
    {
        $batch = $this->stateService->getBatch(batchId: $batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }
        $batch['status'] = 'anonymizing';
        $this->stateService->updateBatch(batchId: $batchId, batch: $batch);
        $skippedFiles   = [];
        $processedCount = 0;
        foreach ($batch['files'] as $index => $file) {
            if ($file['status'] === 'error') {
                $skippedFiles[] = ['fileId' => $file['fileId'], 'reason' => $file['error'] ?? 'Previous error'];
                continue;
            }
            if ($file['status'] !== 'extracted') {
                continue;
            }
            try {
                $result = $this->anonService->anonymizeDocument((int) $file['fileId'], $entities);
                $batch['files'][$index]['status']            = 'anonymized';
                $batch['files'][$index]['replacementCount']  = $result['replacementCount'] ?? 0;
                $batch['files'][$index]['anonymizedFileId']  = $result['anonymizedFileId'] ?? null;
                $batch['files'][$index]['anonymizedFileName'] = $result['anonymizedFileName'] ?? null;
                $batch['files'][$index]['anonymizedFilePath'] = $result['anonymizedFilePath'] ?? null;
                $processedCount++;
            } catch (Exception $e) {
                $this->logger->error('Batch anon failed', ['fileId' => $file['fileId'], 'exception' => $e]);
                $batch['files'][$index]['status'] = 'error';
                $batch['files'][$index]['error']  = $e->getMessage();
                $skippedFiles[] = ['fileId' => $file['fileId'], 'reason' => $e->getMessage()];
            }//end try
        }//end foreach
        $batch['status'] = 'completed';
        $this->stateService->updateBatch(batchId: $batchId, batch: $batch);
        return [
            'batchId'        => $batchId,
            'batchStatus'    => 'completed',
            'processedFiles' => $processedCount,
            'skippedFiles'   => $skippedFiles,
            'totalFiles'     => count($batch['files']),
        ];
    }//end anonymizeBatch()
}//end class
