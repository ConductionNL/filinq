<?php

/**
 * OCR Service
 *
 * Service for extracting text from image-based documents using Tesseract OCR.
 * Provides detection of OCR-needing files, text extraction from images and
 * scanned PDFs, and graceful degradation when Tesseract is not installed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 * @spec openspec/specs/ocr-document-scanning/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use Imagick;
use OCA\Filinq\Kernel\Contracts\OcrServiceInterface;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * Service for OCR text extraction from scanned documents
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class OcrService implements OcrServiceInterface {

	/**
	 * MIME types that always require OCR processing
	 *
	 * @var array<string>
	 */
	private const IMAGE_MIME_TYPES = [
		'image/png',
		'image/jpeg',
		'image/tiff',
		'image/bmp',
		'image/gif',
	];

	/**
	 * MIME types that may require OCR if no text is embedded
	 *
	 * @var array<string>
	 */
	private const PDF_MIME_TYPES = [
		'application/pdf',
	];

	/**
	 * Default OCR languages for Tesseract
	 *
	 * @var string
	 */
	private const DEFAULT_LANGUAGES = 'nld+eng';

	/**
	 * Default DPI for PDF-to-image conversion
	 *
	 * @var int
	 */
	private const DEFAULT_DPI = 300;

	/**
	 * Application name for config lookups
	 *
	 * @var string
	 */
	private const APP_NAME = 'filinq';

	/**
	 * The answer when no OCR ran.
	 *
	 * @var array{text: string, confidence: float, ocrProcessed: bool}
	 */
	private const NO_OCR_RESULT = [
		'text' => '',
		'confidence' => 0.0,
		'ocrProcessed' => false,
	];

	/**
	 * The tesseract command line.
	 *
	 * @var TesseractCommand
	 */
	private readonly TesseractCommand $tesseract;

	/**
	 * Constructor for OcrService
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param IAppConfig $config App configuration interface
	 * @param IRootFolder $rootFolder Root folder for file access
	 * @param IUserSession $userSession User session for current user
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IAppConfig $config,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
	) {
		$this->tesseract = new TesseractCommand(logger: $logger);

	}//end __construct()

	/**
	 * Check if Tesseract OCR binary is available on the system
	 *
	 * @return bool True if Tesseract is installed and executable
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function isTesseractAvailable(): bool {
		return $this->tesseract->isAvailable();

	}//end isTesseractAvailable()

	/**
	 * Get the installed Tesseract version string
	 *
	 * @return string|null The version string or null if not available
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function getTesseractVersion(): ?string {
		return $this->tesseract->version();

	}//end getTesseractVersion()

	/**
	 * Determine if a file needs OCR processing based on MIME type and existing text
	 *
	 * @param string $mimeType The file MIME type
	 * @param string|null $existingText Existing extracted text content
	 *
	 * @return bool True if the file needs OCR processing
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function needsOcr(string $mimeType, ?string $existingText = null): bool {
		// Image files always need OCR.
		if (in_array($mimeType, self::IMAGE_MIME_TYPES, true) === true) {
			return true;
		}

		// PDFs need OCR only if no text was extracted.
		if (in_array($mimeType, self::PDF_MIME_TYPES, true) === true) {
			return empty(trim((string)$existingText)) === true;
		}

		// All other file types do not need OCR.
		return false;
	}//end needsOcr()

	/**
	 * Check if OCR is enabled in admin settings
	 *
	 * @return bool True if OCR is enabled
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function isOcrEnabled(): bool {
		return $this->config->getValueString(
			self::APP_NAME,
			'ocr_enabled',
			'1'
		) === '1';

	}//end isOcrEnabled()

	/**
	 * Get configured OCR languages
	 *
	 * @return string Tesseract language string (e.g., "nld+eng")
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function getOcrLanguages(): string {
		// Canonical manifest-declared key (docudesk-adopt-or-abstractions task 10);
		// legacy 'ocr_languages' read kept as a one-release migration fallback so
		// existing admin overrides do not regress.
		$value = $this->config->getValueString(
			self::APP_NAME,
			'ocr.default_languages',
			''
		);
		if ($value !== '') {
			return $value;
		}

		return $this->config->getValueString(
			self::APP_NAME,
			'ocr_languages',
			self::DEFAULT_LANGUAGES
		);

	}//end getOcrLanguages()

	/**
	 * Get configured OCR DPI for PDF conversion
	 *
	 * @return int DPI value
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function getOcrDpi(): int {
		// Canonical manifest-declared key (docudesk-adopt-or-abstractions task 10);
		// legacy 'ocr_dpi' read kept as a one-release migration fallback so existing
		// admin overrides do not regress.
		$value = $this->config->getValueString(
			self::APP_NAME,
			'ocr.default_dpi',
			''
		);
		if ($value !== '') {
			return (int)$value;
		}

		return (int)$this->config->getValueString(
			self::APP_NAME,
			'ocr_dpi',
			(string)self::DEFAULT_DPI
		);

	}//end getOcrDpi()

	/**
	 * Extract text from an image file using Tesseract OCR
	 *
	 * @param string $filePath Path to the image file
	 * @param string $languages Tesseract language string
	 * @param int $dpi DPI setting (unused for direct images)
	 *
	 * @return array{text: string, confidence: float} Extracted text and confidence
	 *
	 * @throws Exception If OCR processing fails
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function extractTextFromImage(
		string $filePath,
		string $languages = self::DEFAULT_LANGUAGES,
		int $dpi = self::DEFAULT_DPI,
	): array {
		if ($this->isTesseractAvailable() === false) {
			return $this->skippedOcr(reason: 'Tesseract is not available, skipping OCR');
		}

		try {
			$ocr = new TesseractOCR($filePath);
			$ocr->lang(...explode('+', $languages));
			$ocr->dpi($dpi);

			$text = $ocr->run();

			// Get confidence by running with configfile to get detailed output.
			$confidence = $this->tesseract->confidence(filePath: $filePath, languages: $languages, dpi: $dpi);

			$this->logger->debug(
				'OCR text extracted from image',
				[
					'filePath' => $filePath,
					'textLength' => strlen($text),
					'confidence' => $confidence,
				]
			);

			return [
				'text' => $text,
				'confidence' => $confidence,
			];
		} catch (Exception $e) {
			$this->logger->error(
				'OCR extraction failed for image',
				[
					'filePath' => $filePath,
					'exception' => $e->getMessage(),
				]
			);
			throw new Exception('OCR extraction failed: ' . $e->getMessage(), 0, $e);
		}//end try

	}//end extractTextFromImage()

	/**
	 * Extract text from a PDF by converting pages to images and running OCR
	 *
	 * @param string $filePath Path to the PDF file
	 * @param string $languages Tesseract language string
	 * @param int $dpi DPI for PDF-to-image conversion
	 *
	 * @return array{text: string, confidence: float} Extracted text and average confidence
	 *
	 * @throws Exception If PDF conversion or OCR processing fails
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function extractTextFromPdf(
		string $filePath,
		string $languages = self::DEFAULT_LANGUAGES,
		int $dpi = self::DEFAULT_DPI,
	): array {
		if ($this->isTesseractAvailable() === false) {
			return $this->skippedOcr(reason: 'Tesseract is not available, skipping OCR');
		}

		try {
			return $this->ocrPdf(filePath: $filePath, options: new OcrOptions(languages: $languages, dpi: $dpi));
		} catch (Exception $e) {
			$this->failPdfOcr(exception: $e, filePath: $filePath);
		}

	}//end extractTextFromPdf()

	/**
	 * Log a failed PDF OCR pass and rethrow it wrapped.
	 *
	 * @param Exception $exception What went wrong.
	 * @param string    $filePath  Path to the PDF file.
	 *
	 * @return never
	 *
	 * @throws Exception Always, wrapping the failure.
	 */
	private function failPdfOcr(Exception $exception, string $filePath): never {
		$this->logger->error(
			'OCR extraction failed for PDF',
			[
				'filePath' => $filePath,
				'exception' => $exception->getMessage(),
			]
		);
		throw new Exception('PDF OCR extraction failed: ' . $exception->getMessage(), 0, $exception);

	}//end failPdfOcr()

	/**
	 * Log why OCR did not run and answer the empty result.
	 *
	 * @param string $reason The warning to log.
	 *
	 * @return array{text: string, confidence: float} No text, no confidence.
	 */
	private function skippedOcr(string $reason): array {
		$this->logger->warning($reason);
		return [
			'text' => '',
			'confidence' => 0.0,
		];

	}//end skippedOcr()

	/**
	 * Render every page of a PDF to an image, OCR it, and clean up after;
	 * without the Imagick extension nothing is rendered.
	 *
	 * @param string     $filePath Path to the PDF file.
	 * @param OcrOptions $options  The languages and DPI.
	 *
	 * @return array{text: string, confidence: float} The joined text and average confidence.
	 *
	 * @throws Exception When a page cannot be rendered or read.
	 */
	private function ocrPdf(string $filePath, OcrOptions $options): array {
		if (extension_loaded('imagick') === false) {
			return $this->skippedOcr(reason: 'Imagick extension not available, cannot convert PDF to images');
		}

		$imagick = new Imagick();
		$imagick->setResolution($options->dpi, $options->dpi);
		$imagick->readImage($filePath);

		$run = new PdfOcrRun(imagick: $imagick, options: $options);
		mkdir($run->tempDir, 0700, true);

		for ($page = 0; $page < $run->pageCount; $page++) {
			$this->ocrPdfPage(run: $run, page: $page);
		}

		$this->releasePdf(run: $run);

		return $this->pdfOcrResult(filePath: $filePath, run: $run);

	}//end ocrPdf()

	/**
	 * Free the loaded document and remove the emptied temp directory.
	 *
	 * @param PdfOcrRun $run The finished pass.
	 *
	 * @return void
	 */
	private function releasePdf(PdfOcrRun $run): void {
		$run->imagick->clear();
		$run->imagick->destroy();

		// Clean up temp directory.
		if (is_dir($run->tempDir) === true) {
			rmdir($run->tempDir);
		}

	}//end releasePdf()

	/**
	 * Render one page to a temp image, OCR it, add its text and confidence to the run, and remove the image.
	 *
	 * @param PdfOcrRun $run  The pass.
	 * @param int       $page The zero-based page.
	 *
	 * @return void
	 *
	 * @throws Exception When the page cannot be rendered or read.
	 */
	private function ocrPdfPage(PdfOcrRun $run, int $page): void {
		$run->imagick->setIteratorIndex($page);
		$run->imagick->setImageFormat('png');

		$tempImage = $run->tempDir . '/page_' . $page . '.png';
		$run->imagick->writeImage($tempImage);

		$pageResult = $this->extractTextFromImage(filePath: $tempImage, languages: $run->options->languages, dpi: $run->options->dpi);
		$run->text .= $pageResult['text'] . "\n";
		$run->totalConfidence += $pageResult['confidence'];

		// Clean up temp image.
		if (file_exists($tempImage) === true) {
			unlink($tempImage);
		}

	}//end ocrPdfPage()

	/**
	 * The pass's joined text and average confidence, logged.
	 *
	 * @param string    $filePath Path to the PDF file, for the log line.
	 * @param PdfOcrRun $run      The finished pass.
	 *
	 * @return array{text: string, confidence: float} The trimmed text and the confidence rounded to one decimal.
	 */
	private function pdfOcrResult(string $filePath, PdfOcrRun $run): array {
		$avgConfidence = $run->averageConfidence();

		$this->logger->debug(
			'OCR text extracted from PDF',
			[
				'filePath' => $filePath,
				'pages' => $run->pageCount,
				'textLength' => strlen($run->text),
				'confidence' => $avgConfidence,
			]
		);

		return [
			'text' => trim($run->text),
			'confidence' => round($avgConfidence, 1),
		];

	}//end pdfOcrResult()

	/**
	 * Process a file for OCR text extraction
	 *
	 * Main entry point that reads OCR settings, gets the file from
	 * Nextcloud filesystem, determines the processing path, and returns results.
	 *
	 * @param int $fileId The Nextcloud file ID
	 *
	 * @return array{text: string, confidence: float, ocrProcessed: bool} OCR results
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function processFile(int $fileId): array {
		if ($this->canRunOcr(fileId: $fileId) === false) {
			return self::NO_OCR_RESULT;
		}

		try {
			return $this->ocrOfFile(fileId: $fileId);
		} catch (Exception $e) {
			$this->logger->error(
				'OCR processing failed',
				[
					'fileId' => $fileId,
					'exception' => $e->getMessage(),
				]
			);
			return self::NO_OCR_RESULT;
		}//end try

	}//end processFile()

	/**
	 * Whether OCR is switched on and Tesseract is installed; logs why not.
	 *
	 * @param int $fileId The file the check is for, for the log.
	 *
	 * @return bool True when OCR can run.
	 */
	private function canRunOcr(int $fileId): bool {
		if ($this->isOcrEnabled() === false) {
			$this->logger->debug('OCR is disabled, skipping');
			return false;
		}

		if ($this->isTesseractAvailable() === false) {
			$this->logger->warning(
				'Tesseract is not installed, OCR unavailable',
				['fileId' => $fileId]
			);
			return false;
		}

		return true;
	}//end canRunOcr()

	/**
	 * Run OCR on one of the acting user's files, when its type needs it.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array{text: string, confidence: float, ocrProcessed: bool} The OCR result.
	 *
	 * @throws Exception When OCR fails.
	 */
	private function ocrOfFile(int $fileId): array {
		$file = $this->getFileById(fileId: $fileId);
		if ($file === null) {
			$this->logger->warning(
				'File not found for OCR processing',
				['fileId' => $fileId]
			);
			return self::NO_OCR_RESULT;
		}

		if ($this->needsOcr(mimeType: $file->getMimeType()) === false) {
			return self::NO_OCR_RESULT;
		}

		$result = $this->processNode(file: $file);

		return [
			'text' => $result['text'],
			'confidence' => $result['confidence'],
			'ocrProcessed' => empty($result['text']) === false,
		];
	}//end ocrOfFile()

	/**
	 * Run Tesseract on one file node with the admin's languages and DPI.
	 *
	 * The node is resolved by the caller, so a background job with no user
	 * session can run it too. An image goes straight to Tesseract; a PDF is
	 * rasterised page by page first. The caller checks that OCR is enabled,
	 * that Tesseract is installed and that the MIME type is a candidate.
	 *
	 * @param File $file The file to recognise.
	 *
	 * @return array{text: string, confidence: float, languages: string, dpi: int} What was recovered,
	 *                                                                            and with which settings.
	 *
	 * @throws Exception When the file cannot be copied to a temporary location or recognition fails.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.1
	 */
	public function processNode(File $file): array {
		$languages = $this->getOcrLanguages();
		$dpi = $this->getOcrDpi();
		$tempFile = $this->writeToTemp(file: $file);

		$isImage = in_array($file->getMimeType(), self::IMAGE_MIME_TYPES, true);

		try {
			$result = match ($isImage) {
				true => $this->extractTextFromImage(filePath: $tempFile, languages: $languages, dpi: $dpi),
				false => $this->extractTextFromPdf(filePath: $tempFile, languages: $languages, dpi: $dpi),
			};
		} finally {
			if (file_exists($tempFile) === true) {
				unlink($tempFile);
			}
		}

		return [
			'text' => (string) $result['text'],
			'confidence' => (float) $result['confidence'],
			'languages' => $languages,
			'dpi' => $dpi,
		];

	}//end processNode()

	/**
	 * Get a file by its Nextcloud file ID
	 *
	 * @param int $fileId The Nextcloud file ID
	 *
	 * @return File|null The file or null if not found
	 */
	private function getFileById(int $fileId): ?File {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($fileId);

		if (empty($nodes) === true) {
			return null;
		}

		$node = $nodes[0];
		if ($node instanceof File === false) {
			return null;
		}

		return $node;
	}//end getFileById()

	/**
	 * Write a Nextcloud file to a temporary location for processing
	 *
	 * @param File $file The Nextcloud file
	 *
	 * @return string Path to the temporary file
	 *
	 * @throws Exception If writing fails
	 */
	private function writeToTemp(File $file): string {
		$extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
		$tempFile = sys_get_temp_dir() . '/filinq_ocr_' . uniqid() . '.' . $extension;

		$content = $file->getContent();
		if (file_put_contents($tempFile, $content) === false) {
			throw new Exception('Failed to write file to temporary location');
		}

		return $tempFile;
	}//end writeToTemp()
}//end class
