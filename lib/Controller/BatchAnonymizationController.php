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
use OCA\DocuDesk\Service\FolderBatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @category                                       Controller
 * @package                                        OCA\DocuDesk\Controller
 * @author                                         Conduction B.V. <info@conduction.nl>
 * @license                                        EUPL-1.2
 * @link                                           https://www.DocuDesk.app
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class BatchAnonymizationController extends Controller
{


    public function __construct(string $appName, IRequest $request, private readonly LoggerInterface $logger, private readonly BatchStateService $stateService, private readonly BatchUploadService $uploadService, private readonly BatchExtractionService $extractService, private readonly BatchAnonymizeService $anonService, private readonly BatchReportService $reportService, private readonly EntityConsolidationService $entityService, private readonly WooProfileService $profileService, private readonly FolderBatchService $folderBatchService, private readonly IL10N $l10n)
    {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchUpload(): JSONResponse
    {
        try {
            $files = $this->uploadService->collectFiles($this->request);
            if (empty($files)) {
                return new JSONResponse(['error' => $this->l10n->t('No files uploaded')], 400);
            }

            if (count($files) > $this->stateService->getMaxFiles()) {
                return new JSONResponse(['error' => $this->l10n->t('Batch size exceeds maximum')], 400);
            }

            $batch = $this->uploadService->processBatchUpload($this->uploadService->getUserId(), $files);
            return new JSONResponse(['batchId' => $batch['batchId'], 'fileCount' => count($batch['files']), 'files' => $batch['files']]);
        } catch (Exception $e) {
            return $this->err('Batch upload failed', $e);
        }

    }//end batchUpload()


    /**
     * Create a folder-based batch from either folderId or folderPath.
     *
     * @return JSONResponse Batch metadata or an error payload.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function folderBatch(): JSONResponse
    {
        try {
            $folderId   = self::coerceFolderId(raw: $this->request->getParam('folderId'));
            $folderPath = self::coerceFolderPath(raw: $this->request->getParam('folderPath', ''));

            $validationError = $this->validateFolderParams(folderId: $folderId, folderPath: $folderPath);
            if ($validationError !== null) {
                return $validationError;
            }

            $batch = $this->folderBatchService->createFolderBatch(
                folderId: $folderId,
                folderPath: $folderPath
            );

            return new JSONResponse(
            [
                'batchId'    => $batch['batchId'],
                'folderId'   => $batch['folderId'],
                'folderPath' => $batch['folderPath'],
                'fileCount'  => count($batch['files']),
                'files'      => $batch['files'],
            ]
            );
        } catch (Exception $e) {
            return $this->err(msg: 'Folder batch failed', e: $e);
        }//end try

    }//end folderBatch()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchExtract(string $batchId): JSONResponse
    {
        try {
            return new JSONResponse($this->extractService->extractNext($batchId));
        } catch (Exception $e) {
            return $this->err('Extraction failed', $e);
        }

    }//end batchExtract()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchStatus(string $batchId): JSONResponse
    {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            return new JSONResponse(['error' => $this->l10n->t('Batch not found')], 404);
        }

        $ent = 0;
        $ext = 0;
        foreach ($batch['files'] as $f) {
            $ent += ($f['entityCount'] ?? 0);
            if (in_array($f['status'], ['extracted', 'anonymized', 'error'], true)) {
                $ext++;
            }
        }

        $total = count($batch['files']);
        $prog  = $total > 0 ? round(($ext / $total) * 100, 1) : 0;
        return new JSONResponse(['batchId' => $batch['batchId'], 'batchStatus' => $batch['status'], 'files' => $batch['files'], 'totalEntities' => $ent, 'progress' => $prog, 'totalFiles' => $total]);

    }//end batchStatus()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchEntities(string $batchId): JSONResponse
    {
        try {
            $batch = $this->stateService->getBatch($batchId);
            if ($batch === null) {
                return new JSONResponse(['error' => 'Batch not found'], 404);
            }

            if (in_array($batch['status'], ['extracting', 'review'], true) === false) {
                return new JSONResponse(['error' => $this->l10n->t('Extraction has not started')], 409);
            }

            $mc       = (float) ($this->request->getParam('minConfidence', '0.0'));
            $entities = $this->entityService->consolidateEntities($batch, $mc);
            $filesProcessed = 0;
            foreach ($batch['files'] as $f) {
                if (in_array($f['status'], ['extracted', 'error'], true)) {
                    $filesProcessed++;
                }
            }

            return new JSONResponse(['entities' => $entities, 'entityCount' => count($entities), 'complete' => $batch['status'] === 'review', 'filesProcessed' => $filesProcessed]);
        } catch (Exception $e) {
            return $this->err('Failed to get entities', $e);
        }//end try

    }//end batchEntities()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchAnonymize(string $batchId): JSONResponse
    {
        try {
            $entities = $this->request->getParams()['entities'] ?? [];
            if (!is_array($entities) || empty($entities)) {
                return new JSONResponse(['error' => 'No entities provided'], 400);
            }

            return new JSONResponse($this->anonService->anonymizeBatch($batchId, $entities));
        } catch (Exception $e) {
            return $this->err('Anonymization failed', $e);
        }

    }//end batchAnonymize()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchReport(string $batchId): JSONResponse|DataDownloadResponse
    {
        try {
            $csv = $this->reportService->generateReport($batchId);
            return new DataDownloadResponse($csv, 'anonymization-report-'.$batchId.'.csv', 'text/csv');
        } catch (Exception $e) {
            return $this->err($e->getMessage(), $e);
        }

    }//end batchReport()


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getProfiles(): JSONResponse
    {
        return new JSONResponse($this->profileService->getProfile());

    }//end getProfiles()


    /**
     * @NoCSRFRequired
     */
    public function updateProfiles(): JSONResponse
    {
        try {
            $p = $this->request->getParams();
            if (!is_array($p['anonymize'] ?? null) || !is_array($p['keep'] ?? null)) {
                return new JSONResponse(['error' => 'Invalid format'], 400);
            }

            $this->profileService->saveProfile(['anonymize' => $p['anonymize'], 'keep' => $p['keep']]);
            return new JSONResponse(['message' => 'Profile updated']);
        } catch (Exception $e) {
            return $this->err('Failed to update profile', $e);
        }

    }//end updateProfiles()


    private function err(string $msg, Exception $e): JSONResponse
    {
        $code = $e->getCode();
        if ($code < 400 || $code >= 600) {
            $code = 500;
        }

        $this->logger->error($msg.': '.$e->getMessage(), ['exception' => $e]);
        return new JSONResponse(['error' => $msg.': '.$e->getMessage()], $code);

    }//end err()


    /**
     * Coerce the raw folderId request param to an int, or null when absent/empty.
     *
     * @param mixed $raw Raw param value from the request.
     *
     * @return int|null Integer folder ID, or null when the caller did not supply one.
     */
    private static function coerceFolderId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;

    }//end coerceFolderId()


    /**
     * Coerce the raw folderPath request param to a string, or null when absent/empty.
     *
     * @param mixed $raw Raw param value from the request.
     *
     * @return string|null Path string, or null when the caller did not supply one.
     */
    private static function coerceFolderPath(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (string) $raw;

    }//end coerceFolderPath()


    /**
     * Validate XOR between folderId and folderPath at the controller boundary.
     *
     * @param int|null    $folderId   Coerced folder ID.
     * @param string|null $folderPath Coerced folder path.
     *
     * @return JSONResponse|null Error response when validation fails, null when OK.
     */
    private function validateFolderParams(?int $folderId, ?string $folderPath): ?JSONResponse
    {
        if ($folderId === null && $folderPath === null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Either folderId or folderPath must be provided')],
                400
            );
        }

        if ($folderId !== null && $folderPath !== null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Provide only one of folderId or folderPath')],
                400
            );
        }

        return null;

    }//end validateFolderParams()


}//end class
