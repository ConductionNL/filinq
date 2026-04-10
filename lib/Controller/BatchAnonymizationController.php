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
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class BatchAnonymizationController extends Controller
{
	public function __construct(string $appName, IRequest $request, private readonly LoggerInterface $logger, private readonly BatchStateService $stateService, private readonly BatchUploadService $uploadService, private readonly BatchExtractionService $extractService, private readonly BatchAnonymizeService $anonService, private readonly BatchReportService $reportService, private readonly EntityConsolidationService $entityService, private readonly WooProfileService $profileService, private readonly FolderBatchService $folderBatchService, private readonly IL10N $l10n)
	{
		parent::__construct($appName, $request);
	}

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
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function folderBatch(): JSONResponse
	{
		try {
			$folderPath = $this->request->getParam('folderPath', '');
			if (empty($folderPath)) {
				return new JSONResponse(['error' => $this->l10n->t('No folder path provided')], 400);
			}
			$batch = $this->folderBatchService->createFolderBatch($folderPath);
			return new JSONResponse(['batchId' => $batch['batchId'], 'fileCount' => count($batch['files']), 'files' => $batch['files']]);
		} catch (Exception $e) {
			return $this->err('Folder batch failed', $e);
		}
	}

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
	}

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
		$prog = $total > 0 ? round(($ext / $total) * 100, 1) : 0;
		return new JSONResponse(['batchId' => $batch['batchId'], 'batchStatus' => $batch['status'], 'files' => $batch['files'], 'totalEntities' => $ent, 'progress' => $prog, 'totalFiles' => $total]);
	}

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
			$mc = (float)($this->request->getParam('minConfidence', '0.0'));
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
		}
	}

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
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function batchReport(string $batchId): JSONResponse|DataDownloadResponse
	{
		try {
			$csv = $this->reportService->generateReport($batchId);
			return new DataDownloadResponse($csv, 'anonymization-report-' . $batchId . '.csv', 'text/csv');
		} catch (Exception $e) {
			return $this->err($e->getMessage(), $e);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getProfiles(): JSONResponse
	{
		return new JSONResponse($this->profileService->getProfile());
	}

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
	}

	private function err(string $msg, Exception $e): JSONResponse
	{
		$code = $e->getCode();
		if ($code < 400 || $code >= 600) {
			$code = 500;
		}
		$this->logger->error($msg . ': ' . $e->getMessage(), ['exception' => $e]);
		return new JSONResponse(['error' => $msg . ': ' . $e->getMessage()], $code);
	}
}
