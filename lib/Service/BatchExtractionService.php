<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
use Psr\Log\LoggerInterface;
/**
 * Service for extracting entities from batch files sequentially
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchExtractionService
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
     * @param string $batchId Batch ID
     * @return array<string, mixed>
     * @throws Exception If batch not found
     */
    public function extractNext(string $batchId): array
    {
        $batch = $this->stateService->getBatch(batchId: $batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }
        $targetIndex = $this->findNextUploaded(batch: $batch);
        if ($targetIndex === null) {
            return $this->finishExtraction(batchId: $batchId, batch: $batch);
        }
        $batch = $this->extractFile(batchId: $batchId, batch: $batch, index: $targetIndex);
        return $this->buildResponse(batch: $batch, index: $targetIndex);
    }//end extractNext()
    /**
     * @param array<string, mixed> $batch Batch
     * @return int|null
     */
    private function findNextUploaded(array $batch): ?int
    {
        foreach ($batch['files'] as $index => $file) {
            if ($file['status'] === 'uploaded') {
                return $index;
            }
        }
        return null;
    }//end findNextUploaded()
    /**
     * @param string               $batchId Batch ID
     * @param array<string, mixed> $batch   Batch
     * @return array<string, mixed>
     */
    private function finishExtraction(string $batchId, array $batch): array
    {
        $batch['status'] = 'review';
        $this->stateService->updateBatch(batchId: $batchId, batch: $batch);
        return [
            'batchStatus'    => 'review',
            'message'        => 'All files extracted',
            'filesExtracted' => count($batch['files']),
            'totalFiles'     => count($batch['files']),
        ];
    }//end finishExtraction()
    /**
     * @param string               $batchId Batch ID
     * @param array<string, mixed> $batch   Batch
     * @param int                  $index   File index
     * @return array<string, mixed>
     */
    private function extractFile(string $batchId, array $batch, int $index): array
    {
        $file = $batch['files'][$index];
        try {
            $result = $this->anonService->extractAndDetectEntities(fileId: (int) $file['fileId']);
            $batch['files'][$index]['status']      = 'extracted';
            $batch['files'][$index]['entityCount']  = $result['entityCount'];
            $batch['status'] = 'extracting';
        } catch (Exception $e) {
            $this->logger->error(
                'Batch extraction failed for file',
                ['batchId' => $batchId, 'fileId' => $file['fileId'], 'exception' => $e]
            );
            $batch['files'][$index]['status'] = 'error';
            $batch['files'][$index]['error']  = $e->getMessage();
        }//end try
        if ($this->allProcessed(batch: $batch) === true) {
            $batch['status'] = 'review';
        }
        $this->stateService->updateBatch(batchId: $batchId, batch: $batch);
        return $batch;
    }//end extractFile()
    /**
     * @param array<string, mixed> $batch Batch
     * @return bool
     */
    private function allProcessed(array $batch): bool
    {
        foreach ($batch['files'] as $file) {
            if ($file['status'] === 'uploaded') {
                return false;
            }
        }
        return true;
    }//end allProcessed()
    /**
     * @param array<string, mixed> $batch Batch
     * @param int                  $index File index
     * @return array<string, mixed>
     */
    private function buildResponse(array $batch, int $index): array
    {
        $extracted = 0;
        foreach ($batch['files'] as $file) {
            if (in_array($file['status'], ['extracted', 'error'], true) === true) {
                $extracted++;
            }
        }
        return [
            'batchStatus'    => $batch['status'],
            'fileId'         => $batch['files'][$index]['fileId'],
            'fileName'       => $batch['files'][$index]['fileName'],
            'entityCount'    => $batch['files'][$index]['entityCount'] ?? 0,
            'error'          => $batch['files'][$index]['error'] ?? null,
            'filesExtracted' => $extracted,
            'totalFiles'     => count($batch['files']),
        ];
    }//end buildResponse()
}//end class
