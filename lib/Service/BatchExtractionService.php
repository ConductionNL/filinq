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
class BatchExtractionService
{


    public function __construct(private readonly LoggerInterface $logger, private readonly AnonymizationService $anonService, private readonly BatchStateService $stateService)
    {

    }//end __construct()


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
            return ['batchStatus' => 'review', 'message' => 'All files extracted', 'filesExtracted' => count($batch['files']), 'totalFiles' => count($batch['files'])];
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

        if ($allDone) {
            $batch['status'] = 'review';
        }

        $this->stateService->updateBatch($batchId, $batch);
        $ext = 0;
        foreach ($batch['files'] as $f) {
            if (in_array($f['status'], ['extracted', 'error'], true)) {
                $ext++;
            }
        }

        return ['batchStatus' => $batch['status'], 'fileId' => $file['fileId'], 'fileName' => $file['fileName'], 'entityCount' => $batch['files'][$idx]['entityCount'] ?? 0, 'error' => $batch['files'][$idx]['error'] ?? null, 'filesExtracted' => $ext, 'totalFiles' => count($batch['files'])];

    }//end extractNext()


}//end class
