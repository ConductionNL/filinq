<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Controller;
use Exception;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchExtractionService;
use OCA\DocuDesk\Service\BatchReportService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\BatchUploadService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
/**
 * Controller for batch anonymization pipeline endpoints
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 * @psalm-suppress PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class BatchAnonymizationController extends Controller
{
    /**
     * @param string                     $appName        App name
     * @param IRequest                   $request        Request
     * @param LoggerInterface            $logger         Logger
     * @param BatchStateService          $stateService   State
     * @param BatchUploadService         $uploadService  Upload
     * @param BatchExtractionService     $extractService Extract
     * @param BatchAnonymizeService      $anonService    Anonymize
     * @param BatchReportService         $reportService  Report
     * @param EntityConsolidationService $entityService  Entities
     * @param WooProfileService          $profileService Profiles
     * @param IL10N                      $l10n           L10n
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly BatchStateService $stateService,
        private readonly BatchUploadService $uploadService,
        private readonly BatchExtractionService $extractService,
        private readonly BatchAnonymizeService $anonService,
        private readonly BatchReportService $reportService,
        private readonly EntityConsolidationService $entityService,
        private readonly WooProfileService $profileService,
        private readonly IL10N $l10n
    ) {
        parent::__construct(appName: $appName, request: $request);
    }//end __construct()
    /**
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchUpload(): JSONResponse
    {
        try {
            $files = $this->uploadService->collectFiles(request: $this->request);
            if (empty($files) === true) {
                return new JSONResponse(['error' => $this->l10n->t('No files uploaded')], 400);
            }
            $maxFiles = $this->stateService->getMaxFiles();
            if (count($files) > $maxFiles) {
                return new JSONResponse(['error' => $this->l10n->t('Batch size exceeds maximum of %s files', [$maxFiles])], 400);
            }
            $userId = $this->uploadService->getUserId();
            $batch  = $this->uploadService->processBatchUpload(userId: $userId, files: $files);
            return new JSONResponse(['batchId' => $batch['batchId'], 'fileCount' => count($batch['files']), 'files' => $batch['files']]);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Batch upload failed', exception: $e);
        }//end try
    }//end batchUpload()
    /**
     * @param string $batchId Batch ID
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchExtract(string $batchId): JSONResponse
    {
        try {
            return new JSONResponse($this->extractService->extractNext(batchId: $batchId));
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Batch extraction failed', exception: $e);
        }
    }//end batchExtract()
    /**
     * @param string $batchId Batch ID
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchStatus(string $batchId): JSONResponse
    {
        $batch = $this->stateService->getBatch(batchId: $batchId);
        if ($batch === null) {
            return new JSONResponse(['error' => $this->l10n->t('Batch not found or expired')], 404);
        }
        return new JSONResponse($this->buildStatusResponse(batch: $batch));
    }//end batchStatus()
    /**
     * @param string $batchId Batch ID
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchEntities(string $batchId): JSONResponse
    {
        try {
            $batch = $this->stateService->getBatch(batchId: $batchId);
            if ($batch === null) {
                return new JSONResponse(['error' => $this->l10n->t('Batch not found or expired')], 404);
            }
            if ($batch['status'] !== 'review') {
                return new JSONResponse(['error' => $this->l10n->t('Batch extraction is not yet complete')], 409);
            }
            $minConfidence = (float) ($this->request->getParam('minConfidence', '0.0'));
            $entities      = $this->entityService->consolidateEntities(batch: $batch, minConfidence: $minConfidence);
            return new JSONResponse(['entities' => $entities, 'entityCount' => count($entities)]);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get batch entities', exception: $e);
        }//end try
    }//end batchEntities()
    /**
     * @param string $batchId Batch ID
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchAnonymize(string $batchId): JSONResponse
    {
        try {
            $entities = $this->request->getParams()['entities'] ?? [];
            if (is_array($entities) === false || empty($entities) === true) {
                return new JSONResponse(['error' => $this->l10n->t('No entities provided for anonymization')], 400);
            }
            return new JSONResponse($this->anonService->anonymizeBatch(batchId: $batchId, entities: $entities));
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Batch anonymization failed', exception: $e);
        }//end try
    }//end batchAnonymize()
    /**
     * @param string $batchId Batch ID
     * @return JSONResponse|DataDownloadResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchReport(string $batchId): JSONResponse|DataDownloadResponse
    {
        try {
            $csv = $this->reportService->generateReport(batchId: $batchId);
            return new DataDownloadResponse($csv, 'anonymization-report-'.$batchId.'.csv', 'text/csv');
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), exception: $e);
        }
    }//end batchReport()
    /**
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getProfiles(): JSONResponse
    {
        return new JSONResponse($this->profileService->getProfile());
    }//end getProfiles()
    /**
     * @return JSONResponse
     * @NoCSRFRequired
     */
    public function updateProfiles(): JSONResponse
    {
        try {
            $params    = $this->request->getParams();
            $anonymize = $params['anonymize'] ?? [];
            $keep      = $params['keep'] ?? [];
            if (is_array($anonymize) === false || is_array($keep) === false) {
                return new JSONResponse(['error' => $this->l10n->t('Invalid profile format')], 400);
            }
            $this->profileService->saveProfile(profile: ['anonymize' => $anonymize, 'keep' => $keep]);
            return new JSONResponse(['message' => $this->l10n->t('Profile updated')]);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to update profile', exception: $e);
        }//end try
    }//end updateProfiles()
    /**
     * @param array<string, mixed> $batch Batch
     * @return array<string, mixed>
     */
    private function buildStatusResponse(array $batch): array
    {
        $totalEntities = 0;
        $extracted     = 0;
        foreach ($batch['files'] as $file) {
            $totalEntities += ($file['entityCount'] ?? 0);
            if (in_array($file['status'], ['extracted', 'anonymized', 'error'], true) === true) {
                $extracted++;
            }
        }
        $totalFiles = count($batch['files']);
        $progress   = 0;
        if ($totalFiles > 0) {
            $progress = round(($extracted / $totalFiles) * 100, 1);
        }
        return [
            'batchId' => $batch['batchId'], 'batchStatus' => $batch['status'],
            'files' => $batch['files'], 'totalEntities' => $totalEntities,
            'progress' => $progress, 'totalFiles' => $totalFiles,
        ];
    }//end buildStatusResponse()
    /**
     * @param string    $message   Message
     * @param Exception $exception Exception
     * @return JSONResponse
     */
    private function errorResponse(string $message, Exception $exception): JSONResponse
    {
        $code = $exception->getCode();
        if ($code < 400 || $code >= 600) {
            $code = 500;
        }
        $this->logger->error($message.': '.$exception->getMessage(), ['exception' => $exception]);
        return new JSONResponse(['error' => $this->l10n->t($message.': %s', [$exception->getMessage()])], $code);
    }//end errorResponse()
}//end class
