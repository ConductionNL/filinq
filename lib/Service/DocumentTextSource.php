<?php

/**
 * Document text source
 *
 * The text of a Nextcloud file the session user can read: embedded PDF text
 * when there is some, otherwise OCR through Tesseract. Shared by the financial
 * extraction and the schema field suggestions.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads the text of a file the session user can read.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class DocumentTextSource {

	/**
	 * Constructor.
	 *
	 * @param IRootFolder     $rootFolder  Root folder, to find the file.
	 * @param IUserSession    $userSession The session user the file is looked up for.
	 * @param OcrService      $ocrService  Tesseract OCR.
	 * @param LoggerInterface $logger      Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly OcrService $ocrService,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether the session user can read the file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return bool True when the file is found in the session user's files.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function readable(int $fileId): bool {
		if ($fileId <= 0) {
			return false;
		}

		return $this->resolveFile(fileId: $fileId) !== null;

	}//end readable()

	/**
	 * Resolve document text for a Nextcloud file id: reuse embedded PDF text
	 * when present, otherwise fall back to OCR via OcrService (REQ-FIN-01).
	 *
	 * @param int|null $fileId The Nextcloud file id, or null when unresolvable.
	 *
	 * @return array{text: string, ocrConfidence: float}
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function resolveText(?int $fileId): array {
		if ($fileId === null || $fileId <= 0) {
			return ['text' => '', 'ocrConfidence' => 0.0];
		}

		$file = $this->resolveFile(fileId: $fileId);
		if ($file === null) {
			return ['text' => '', 'ocrConfidence' => 0.0];
		}

		$mimeType = $file->getMimeType();
		$embedded = '';
		if ($mimeType === 'application/pdf') {
			$embedded = $this->extractEmbeddedPdfText(file: $file);
		}

		if ($this->ocrService->needsOcr(mimeType: $mimeType, existingText: $embedded) === false) {
			return ['text' => $embedded, 'ocrConfidence' => 1.0];
		}

		if ($this->ocrService->isTesseractAvailable() === false) {
			// Graceful degradation: keep whatever embedded text we have.
			return ['text' => $embedded, 'ocrConfidence' => 0.0];
		}

		return $this->runOcr(file: $file, embedded: $embedded);
	}//end resolveText()

	/**
	 * Stage the file to a temp path and run Tesseract OCR on it. Absent-safe:
	 * any failure at any stage falls back to the embedded text (possibly
	 * empty) with zero OCR confidence.
	 *
	 * @param File $file The Nextcloud file to OCR.
	 * @param string $embedded Embedded text already extracted (fallback value).
	 *
	 * @return array{text: string, ocrConfidence: float}
	 */
	private function runOcr(File $file, string $embedded): array {
		try {
			$tempFile = $this->writeToTemp(file: $file);
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: document text could not stage file for OCR: ' . $e->getMessage());
			return ['text' => $embedded, 'ocrConfidence' => 0.0];
		}

		try {
			return $this->ocrStagedFile(tempFile: $tempFile, isImage: str_starts_with($file->getMimeType(), 'image/'));
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: document text OCR failed: ' . $e->getMessage());
			return ['text' => $embedded, 'ocrConfidence' => 0.0];
		} finally {
			if (file_exists($tempFile) === true) {
				unlink($tempFile);
			}
		}//end try

	}//end runOcr()

	/**
	 * OCR a staged copy of the file with the configured languages and DPI.
	 *
	 * @param string $tempFile Path of the staged copy.
	 * @param bool $isImage Whether the file is an image rather than a PDF.
	 *
	 * @return array{text: string, ocrConfidence: float}
	 */
	private function ocrStagedFile(string $tempFile, bool $isImage): array {
		$languages = $this->ocrService->getOcrLanguages();
		$dpi = $this->ocrService->getOcrDpi();

		$result = $this->ocrService->extractTextFromPdf(filePath: $tempFile, languages: $languages, dpi: $dpi);
		if ($isImage === true) {
			$result = $this->ocrService->extractTextFromImage(filePath: $tempFile, languages: $languages, dpi: $dpi);
		}

		return [
			'text' => $result['text'],
			'ocrConfidence' => ($result['confidence'] / 100),
		];
	}//end ocrStagedFile()

	/**
	 * Extract embedded PDF text via the `pdftotext` CLI (poppler-utils), when
	 * available. Absent-safe: any failure yields an empty string so the
	 * caller falls through to OCR.
	 *
	 * @param File $file The PDF file.
	 *
	 * @return string The embedded text, or '' when unavailable/unextractable.
	 */
	private function extractEmbeddedPdfText(File $file): string {
		try {
			$tempFile = $this->writeToTemp(file: $file);
		} catch (Throwable $e) {
			return '';
		}

		try {
			$output = [];
			$returnCode = 0;
			exec('pdftotext ' . escapeshellarg($tempFile) . ' - 2>/dev/null', $output, $returnCode);
			if ($returnCode !== 0) {
				return '';
			}

			return trim(implode("\n", $output));
		} finally {
			if (file_exists($tempFile) === true) {
				unlink($tempFile);
			}
		}

	}//end extractEmbeddedPdfText()

	/**
	 * Resolve a Nextcloud file by id, scoped to the current user's folder.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File|null The file, or null when not found/not accessible.
	 */
	private function resolveFile(int $fileId): ?File {
		return (new SessionUserFile(rootFolder: $this->rootFolder, userSession: $this->userSession))->find(fileId: $fileId);

	}//end resolveFile()

	/**
	 * Write a Nextcloud file to a temporary location for processing.
	 *
	 * @param File $file The Nextcloud file.
	 *
	 * @return string Path to the temporary file.
	 *
	 * @throws RuntimeException If writing fails.
	 */
	private function writeToTemp(File $file): string {
		$extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
		$tempFile = sys_get_temp_dir() . '/filinq_extraction_' . uniqid() . '.' . $extension;

		$content = $file->getContent();
		if (file_put_contents($tempFile, $content) === false) {
			throw new RuntimeException('Failed to write file to temporary location');
		}

		return $tempFile;
	}//end writeToTemp()
}//end class
