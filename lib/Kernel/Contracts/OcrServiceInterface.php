<?php

/**
 * Contract of OcrService
 *
 * The public surface of the OcrService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use Exception;
use OCP\Files\File;

/**
 * Contract of OcrService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OcrServiceInterface {
	/**
	 * Check if Tesseract OCR binary is available on the system
	 *
	 * @return bool True if Tesseract is installed and executable
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function isTesseractAvailable(): bool;

	/**
	 * Get the installed Tesseract version string
	 *
	 * @return string|null The version string or null if not available
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function getTesseractVersion(): ?string;

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
	public function needsOcr(string $mimeType, ?string $existingText = null): bool;

	/**
	 * Check if OCR is enabled in admin settings
	 *
	 * @return bool True if OCR is enabled
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function isOcrEnabled(): bool;

	/**
	 * Get configured OCR languages
	 *
	 * @return string Tesseract language string (e.g., "nld+eng")
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function getOcrLanguages(): string;

	/**
	 * Get configured OCR DPI for PDF conversion
	 *
	 * @return int DPI value
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function getOcrDpi(): int;

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
	public function extractTextFromImage(string $filePath, string $languages = 'nld+eng', int $dpi = 300): array;

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
	public function extractTextFromPdf(string $filePath, string $languages = 'nld+eng', int $dpi = 300): array;

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
	public function processFile(int $fileId): array;

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
	public function processNode(File $file): array;
}//end interface
