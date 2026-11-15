<?php

/**
 * Tesseract Command
 *
 * Runs the tesseract binary for OcrService: whether it is installed, its
 * version, and the mean word confidence of a recognition.
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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * The tesseract command line, as OcrService uses it.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */
class TesseractCommand {

	/**
	 * Constructor for TesseractCommand
	 *
	 * @param LoggerInterface $logger Logger for the debug notes of a failed call.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether the tesseract binary answers `--version`.
	 *
	 * @return bool True if Tesseract is installed and executable
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function isAvailable(): bool {
		try {
			$output = [];
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

	}//end isAvailable()

	/**
	 * The first line of `tesseract --version`.
	 *
	 * @return string|null Version string or null if not available
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function version(): ?string {
		try {
			$output = [];
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

	}//end version()

	/**
	 * The mean word confidence tesseract reports for an image, from its hOCR output.
	 *
	 * @param string $filePath  Path to the image file
	 * @param string $languages Tesseract language codes
	 * @param int    $dpi       DPI setting
	 *
	 * @return float Average confidence score (0-100), 0.0 when tesseract fails
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function confidence(string $filePath, string $languages, int $dpi): float {
		try {
			$output = [];
			$returnCode = 0;
			$longArgs = str_replace('+', '+', $languages);
			$command = sprintf(
				'tesseract %s stdout -l %s --dpi %d -c hocr_font_info=0 hocr 2>/dev/null',
				escapeshellarg($filePath),
				escapeshellarg($longArgs),
				$dpi
			);
			exec($command, $output, $returnCode);

			if ($returnCode !== 0) {
				return 0.0;
			}

			return $this->averageConfidence(hocrContent: implode("\n", $output));
		} catch (Exception $e) {
			$this->logger->debug(
				'Could not determine OCR confidence',
				['exception' => $e->getMessage()]
			);
			return 0.0;
		}//end try

	}//end confidence()

	/**
	 * The mean of every `x_wconf` word confidence in hOCR markup.
	 *
	 * @param string $hocrContent The hOCR output.
	 *
	 * @return float The mean, rounded to one decimal, or 0.0 when no word carries one.
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function averageConfidence(string $hocrContent): float {
		$confidences = [];
		$matchCount = preg_match_all(
			'/x_wconf\s+(\d+)/',
			$hocrContent,
			$matches
		);

		if ($matchCount > 0 && empty($matches[1]) === false) {
			foreach ($matches[1] as $conf) {
				$confidences[] = (float)$conf;
			}
		}

		if (empty($confidences) === true) {
			return 0.0;
		}

		return round(array_sum($confidences) / count($confidences), 1);

	}//end averageConfidence()
}//end class
