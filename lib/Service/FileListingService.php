<?php

/**
 * File Listing Service
 *
 * Service for listing processed files in the user's Filinq folder.
 * Delegates entity stats to FileEntityStatsService and upload logic
 * to FileUploadService.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-3.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\Service\Ocr\OcrResultRepository;
use OCA\Filinq\Service\Ocr\OcrRunService;
use Psr\Log\LoggerInterface;

/**
 * Service for listing processed files with entity and risk data
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-3.5
 */
class FileListingService {
	/**
	 * Constructor for FileListingService
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param FileUploadService $fileUploadService Upload and folder management
	 * @param FileEntityStatsService $entityStatsService Entity counts and risk levels
	 * @param OcrResultRepository $ocrResults The ocrResult rows, for the real OCR status
	 * @param OcrRunService $ocrRuns Which MIME types OCR reads
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly FileUploadService $fileUploadService,
		private readonly FileEntityStatsService $entityStatsService,
		private readonly OcrResultRepository $ocrResults,
		private readonly OcrRunService $ocrRuns,
	) {

	}//end __construct()

	/**
	 * Build info array for a single file
	 *
	 * @param \OCP\Files\File $file The file
	 * @param \OCA\OpenRegister\Db\EntityRelationMapper|null $entityRelationMapper The mapper
	 * @param \OCA\OpenRegister\Service\RiskLevelService|null $riskLevelService The service
	 *
	 * @return array<string, mixed> File info
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	private function buildFileInfo(
		\OCP\Files\File $file,
		?\OCA\OpenRegister\Db\EntityRelationMapper $entityRelationMapper,
		?\OCA\OpenRegister\Service\RiskLevelService $riskLevelService,
	): array {
		$fileId = $file->getId();
		$entityStats = $this->entityStatsService->getEntityStats($fileId, $entityRelationMapper);
		$riskLevel = $this->entityStatsService->getFileRiskLevel($fileId, $riskLevelService);
		$mimeType = $file->getMimeType();

		// OCR status comes from the ocrResult row a real run wrote, not from
		// the MIME type: a born-digital PDF that was only extracted was never
		// OCR'd, and saying so hid the scans that needed it.
		$ocr = $this->ocrStatus(fileId: $fileId, mimeType: $mimeType);

		return [
			'fileId' => $fileId,
			'fileName' => $file->getName(),
			'filePath' => $file->getPath(),
			'fileSize' => $file->getSize(),
			'mimeType' => $mimeType,
			'entityCount' => $entityStats['entityCount'],
			'anonymizedCount' => $entityStats['anonymizedCount'],
			'status' => $entityStats['status'],
			'riskLevel' => $riskLevel,
			'modified' => $file->getMTime(),
			'ocrProcessed' => $ocr['ocrProcessed'],
			'ocrConfidence' => $ocr['ocrConfidence'],
			'ocrAvailable' => $ocr['ocrAvailable'],
		];

	}//end buildFileInfo()

	/**
	 * A file's OCR status from its ocrResult row.
	 *
	 * @param int $fileId The file id.
	 * @param string $mimeType The file's MIME type.
	 *
	 * @return array{ocrProcessed: bool, ocrConfidence: float|int|null, ocrAvailable: bool} The status.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	private function ocrStatus(int $fileId, string $mimeType): array {
		$status = ['ocrProcessed' => false, 'ocrConfidence' => null, 'ocrAvailable' => false];
		if ($this->ocrRuns->isCandidate(mimeType: $mimeType) === false) {
			return $status;
		}

		$status['ocrAvailable'] = true;
		try {
			$result = $this->ocrResults->findForFile(fileId: $fileId);
		} catch (Exception $e) {
			$this->logger->warning('OCR status could not be read', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			return $status;
		}

		if ($result !== null) {
			$status['ocrProcessed'] = true;
			$status['ocrConfidence'] = $result['confidence'] ?? null;
		}

		return $status;

	}//end ocrStatus()

	/**
	 * List all processed files in the user's Filinq folder
	 *
	 * @return array<int, array<string, mixed>> Array of file info
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function listProcessedFiles(): array {
		try {
			// Ensures the user is authenticated (throws otherwise).
			$this->fileUploadService->getCurrentUserId();
			$filinqFolder = $this->fileUploadService->getFilinqFolder();

			$files = $filinqFolder->getDirectoryListing();
			$entityRelationMapper = $this->entityStatsService->tryGetEntityRelationMapper();
			$riskLevelService = $this->entityStatsService->tryGetRiskLevelService();

			$result = [];
			foreach ($files as $file) {
				if ($file instanceof \OCP\Files\File === false) {
					continue;
				}

				$result[] = $this->buildFileInfo(
					file: $file,
					entityRelationMapper: $entityRelationMapper,
					riskLevelService: $riskLevelService
				);
			}

			usort(
				$result,
				function ($left, $right) {
					return $right['modified'] - $left['modified'];
				}
			);

			return $result;
		} catch (Exception $e) {
			$this->logger->error(
				'Failed to list processed files: ' . $e->getMessage(),
				['exception' => $e]
			);
			throw new Exception(
				'Failed to list processed files: ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}//end try

	}//end listProcessedFiles()

	/**
	 * Upload a file to the user's Filinq folder
	 *
	 * @param string $fileName The name of the file to upload
	 * @param string $fileContent The raw file content
	 *
	 * @return array<string, mixed> Upload result with fileId, filePath, fileName, fileSize
	 *
	 * @throws Exception If the upload fails
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function uploadFile(string $fileName, string $fileContent): array {
		return $this->fileUploadService->uploadFile($fileName, $fileContent);
	}//end uploadFile()
}//end class
