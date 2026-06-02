<?php
/**
 * OCR Service
 *
 * Service for extracting text from image-based documents using Tesseract OCR.
 * Provides detection of OCR-needing files, text extraction from images and
 * scanned PDFs, and graceful degradation when Tesseract is not installed.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-48
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-49
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-50
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-51
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-52
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-53
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-54
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-55
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-56
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Imagick;
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
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class OcrService
{

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
    private const APP_NAME = 'docudesk';

    /**
     * Constructor for OcrService
     *
     * @param LoggerInterface $logger      Logger for error reporting
     * @param IAppConfig      $config      App configuration interface
     * @param IRootFolder     $rootFolder  Root folder for file access
     * @param IUserSession    $userSession User session for current user
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IAppConfig $config,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession
    ) {

    }//end __construct()

    /**
     * Check if Tesseract OCR binary is available on the system
     *
     * @return bool True if Tesseract is installed and executable
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-48
     */
    public function isTesseractAvailable(): bool
    {
        try {
            $output     = [];
            $returnCode = 0;
            exec('tesseract --version 2>&1', $output, $returnCode);
            return $returnCode === 0;
        } catch (Exception $e) {
            $this->logger->debug(
                'Tesseract availability check failed',
                ['exception' => $e->getMessage()]
            );
            return false;
        }//end try

    }//end isTesseractAvailable()

    /**
     * Get the installed Tesseract version string
     *
     * @return string|null The version string or null if not available
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-49
     */
    public function getTesseractVersion(): ?string
    {
        try {
            $output     = [];
            $returnCode = 0;
            exec('tesseract --version 2>&1', $output, $returnCode);
            if ($returnCode === 0 && empty($output) === false) {
                // The first line contains the version string.
                return trim($output[0]);
            }

            return null;
        } catch (Exception $e) {
            $this->logger->debug(
                'Tesseract version check failed',
                ['exception' => $e->getMessage()]
            );
            return null;
        }//end try

    }//end getTesseractVersion()

    /**
     * Determine if a file needs OCR processing based on MIME type and existing text
     *
     * @param string      $mimeType     The file MIME type
     * @param string|null $existingText Existing extracted text content
     *
     * @return bool True if the file needs OCR processing
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-50
     */
    public function needsOcr(string $mimeType, ?string $existingText=null): bool
    {
        // Image files always need OCR.
        if (in_array($mimeType, self::IMAGE_MIME_TYPES, true) === true) {
            return true;
        }

        // PDFs need OCR only if no text was extracted.
        if (in_array($mimeType, self::PDF_MIME_TYPES, true) === true) {
            return empty(trim((string) $existingText)) === true;
        }

        // All other file types do not need OCR.
        return false;

    }//end needsOcr()

    /**
     * Check if OCR is enabled in admin settings
     *
     * @return bool True if OCR is enabled
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-51
     */
    public function isOcrEnabled(): bool
    {
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-51
     */
    public function getOcrLanguages(): string
    {
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-52
     */
    public function getOcrDpi(): int
    {
        return (int) $this->config->getValueString(
            self::APP_NAME,
            'ocr_dpi',
            (string) self::DEFAULT_DPI
        );

    }//end getOcrDpi()

    /**
     * Extract text from an image file using Tesseract OCR
     *
     * @param string $filePath  Path to the image file
     * @param string $languages Tesseract language string
     * @param int    $dpi       DPI setting (unused for direct images)
     *
     * @return array{text: string, confidence: float} Extracted text and confidence
     *
     * @throws Exception If OCR processing fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-53
     */
    public function extractTextFromImage(
        string $filePath,
        string $languages=self::DEFAULT_LANGUAGES,
        int $dpi=self::DEFAULT_DPI
    ): array {
        if ($this->isTesseractAvailable() === false) {
            $this->logger->warning('Tesseract is not available, skipping OCR');
            return [
                'text'       => '',
                'confidence' => 0.0,
            ];
        }

        try {
            $ocr = new TesseractOCR($filePath);
            $ocr->lang(...explode('+', $languages));
            $ocr->dpi($dpi);

            $text = $ocr->run();

            // Get confidence by running with configfile to get detailed output.
            $confidence = $this->getConfidenceScore(filePath: $filePath, languages: $languages, dpi: $dpi);

            $this->logger->debug(
                'OCR text extracted from image',
                [
                    'filePath'   => $filePath,
                    'textLength' => strlen($text),
                    'confidence' => $confidence,
                ]
            );

            return [
                'text'       => $text,
                'confidence' => $confidence,
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'OCR extraction failed for image',
                [
                    'filePath'  => $filePath,
                    'exception' => $e->getMessage(),
                ]
            );
            throw new Exception('OCR extraction failed: '.$e->getMessage(), 0, $e);
        }//end try

    }//end extractTextFromImage()

    /**
     * Extract text from a PDF by converting pages to images and running OCR
     *
     * @param string $filePath  Path to the PDF file
     * @param string $languages Tesseract language string
     * @param int    $dpi       DPI for PDF-to-image conversion
     *
     * @return array{text: string, confidence: float} Extracted text and average confidence
     *
     * @throws Exception If PDF conversion or OCR processing fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-54
     */
    public function extractTextFromPdf(
        string $filePath,
        string $languages=self::DEFAULT_LANGUAGES,
        int $dpi=self::DEFAULT_DPI
    ): array {
        if ($this->isTesseractAvailable() === false) {
            $this->logger->warning('Tesseract is not available, skipping OCR');
            return [
                'text'       => '',
                'confidence' => 0.0,
            ];
        }

        if (extension_loaded('imagick') === false) {
            $this->logger->warning('Imagick extension not available, cannot convert PDF to images');
            return [
                'text'       => '',
                'confidence' => 0.0,
            ];
        }

        try {
            $imagick = new Imagick();
            $imagick->setResolution($dpi, $dpi);
            $imagick->readImage($filePath);

            $pageCount       = $imagick->getNumberImages();
            $allText         = '';
            $totalConfidence = 0.0;

            $tempDir = sys_get_temp_dir().'/docudesk_ocr_'.uniqid();
            mkdir($tempDir, 0700, true);

            for ($page = 0; $page < $pageCount; $page++) {
                $imagick->setIteratorIndex($page);
                $imagick->setImageFormat('png');

                $tempImage = $tempDir.'/page_'.$page.'.png';
                $imagick->writeImage($tempImage);

                $pageResult       = $this->extractTextFromImage(filePath: $tempImage, languages: $languages, dpi: $dpi);
                $allText         .= $pageResult['text']."\n";
                $totalConfidence += $pageResult['confidence'];

                // Clean up temp image.
                if (file_exists($tempImage) === true) {
                    unlink($tempImage);
                }
            }//end for

            $imagick->clear();
            $imagick->destroy();

            // Clean up temp directory.
            if (is_dir($tempDir) === true) {
                rmdir($tempDir);
            }

            $avgConfidence = 0.0;
            if ($pageCount > 0) {
                $avgConfidence = ($totalConfidence / $pageCount);
            }

            $this->logger->debug(
                'OCR text extracted from PDF',
                [
                    'filePath'   => $filePath,
                    'pages'      => $pageCount,
                    'textLength' => strlen($allText),
                    'confidence' => $avgConfidence,
                ]
            );

            return [
                'text'       => trim($allText),
                'confidence' => round($avgConfidence, 1),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'OCR extraction failed for PDF',
                [
                    'filePath'  => $filePath,
                    'exception' => $e->getMessage(),
                ]
            );
            throw new Exception('PDF OCR extraction failed: '.$e->getMessage(), 0, $e);
        }//end try

    }//end extractTextFromPdf()

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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-55
     */
    public function processFile(int $fileId): array
    {
        $noOcrResult = [
            'text'         => '',
            'confidence'   => 0.0,
            'ocrProcessed' => false,
        ];

        if ($this->isOcrEnabled() === false) {
            $this->logger->debug('OCR is disabled, skipping');
            return $noOcrResult;
        }

        if ($this->isTesseractAvailable() === false) {
            $this->logger->warning(
                'Tesseract is not installed, OCR unavailable',
                ['fileId' => $fileId]
            );
            return $noOcrResult;
        }

        try {
            $file = $this->getFileById(fileId: $fileId);
            if ($file === null) {
                $this->logger->warning(
                    'File not found for OCR processing',
                    ['fileId' => $fileId]
                );
                return $noOcrResult;
            }

            $mimeType = $file->getMimeType();
            if ($this->needsOcr(mimeType: $mimeType) === false) {
                return $noOcrResult;
            }

            $languages = $this->getOcrLanguages();
            $dpi       = $this->getOcrDpi();

            // Write file to temp location for Tesseract processing.
            $tempFile = $this->writeToTemp(file: $file);

            try {
                $result = $this->extractTextFromPdf(filePath: $tempFile, languages: $languages, dpi: $dpi);
                if (in_array($mimeType, self::IMAGE_MIME_TYPES, true) === true) {
                    $result = $this->extractTextFromImage(filePath: $tempFile, languages: $languages, dpi: $dpi);
                }

                return [
                    'text'         => $result['text'],
                    'confidence'   => $result['confidence'],
                    'ocrProcessed' => empty($result['text']) === false,
                ];
            } finally {
                // Clean up temp file.
                if (file_exists($tempFile) === true) {
                    unlink($tempFile);
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                'OCR processing failed',
                [
                    'fileId'    => $fileId,
                    'exception' => $e->getMessage(),
                ]
            );
            return $noOcrResult;
        }//end try

    }//end processFile()

    /**
     * Get a file by its Nextcloud file ID
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return File|null The file or null if not found
     */
    private function getFileById(int $fileId): ?File
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return null;
        }

        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        $nodes      = $userFolder->getById($fileId);

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
    private function writeToTemp(File $file): string
    {
        $extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
        $tempFile  = sys_get_temp_dir().'/docudesk_ocr_'.uniqid().'.'.$extension;

        $content = $file->getContent();
        if (file_put_contents($tempFile, $content) === false) {
            throw new Exception('Failed to write file to temporary location');
        }

        return $tempFile;

    }//end writeToTemp()

    /**
     * Get OCR confidence score for a file
     *
     * Uses Tesseract's hocr output to extract mean confidence.
     *
     * @param string $filePath  Path to the image file
     * @param string $languages Tesseract language string
     * @param int    $dpi       DPI setting
     *
     * @return float Confidence score (0-100)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-56
     */
    private function getConfidenceScore(string $filePath, string $languages, int $dpi): float
    {
        try {
            $output     = [];
            $returnCode = 0;
            $langArgs   = str_replace('+', '+', $languages);
            $command    = sprintf(
                'tesseract %s stdout -l %s --dpi %d -c hocr_font_info=0 hocr 2>/dev/null',
                escapeshellarg($filePath),
                escapeshellarg($langArgs),
                $dpi
            );
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return 0.0;
            }

            $hocrContent = implode("\n", $output);
            $confidences = [];
            $matchCount  = preg_match_all(
                '/x_wconf\s+(\d+)/',
                $hocrContent,
                $matches
            );

            if ($matchCount > 0 && empty($matches[1]) === false) {
                foreach ($matches[1] as $conf) {
                    $confidences[] = (float) $conf;
                }
            }

            if (empty($confidences) === true) {
                return 0.0;
            }

            return round(array_sum($confidences) / count($confidences), 1);
        } catch (Exception $e) {
            $this->logger->debug(
                'Could not determine OCR confidence',
                ['exception' => $e->getMessage()]
            );
            return 0.0;
        }//end try

    }//end getConfidenceScore()
}//end class
