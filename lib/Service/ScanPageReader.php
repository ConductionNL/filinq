<?php

/**
 * Scan Page Reader
 *
 * Splits one batch into its pages and says what is on each of them: the text,
 * and how much of the page carries anything at all.
 *
 * The text comes from the OCR path this app already has. The "how much" is the
 * size of that one page as its own PDF, which is a PROXY for ink and is treated
 * as one: it separates a sheet that carries a scan of something from a sheet
 * that carries almost nothing, and it does not pretend to measure coverage.
 *
 * 🔴 `isAvailable()` is not decoration. An instance without the OCR toolchain
 * reads every page as empty text, and a splitter that believed it would deliver
 * a forty-page batch as one document while the separators sat in it unread.
 * That is silent and it is worse than a failure, so the splitter asks first.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads a scanned batch page by page.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
class ScanPageReader {

	/**
	 * Constructor.
	 *
	 * @param PdfDocumentFactory $documents Builds the single-page documents.
	 * @param OcrService $ocr The text this instance can read off a scan.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfDocumentFactory $documents,
		private readonly OcrService $ocr,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether this instance can read text off a scanned page at all.
	 *
	 * @return bool True when the OCR toolchain is present.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function isAvailable(): bool {
		try {
			return ($this->ocr->isTesseractAvailable() === true && extension_loaded('imagick') === true);
		} catch (Throwable $e) {
			return false;
		}

	}//end isAvailable()

	/**
	 * Cut one batch into single-page PDFs.
	 *
	 * @param string $batch The batch as PDF bytes.
	 *
	 * @return array<int, string> One PDF per page, in order.
	 *
	 * @throws RuntimeException When the batch cannot be read as a PDF.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function pages(string $batch): array {
		$temp = tempnam(sys_get_temp_dir(), 'filinq-batch-');
		if ($temp === false) {
			throw new RuntimeException(message: 'Could not allocate a temp file for the batch.');
		}

		try {
			file_put_contents($temp, $batch);
			$probe = $this->documents->create();
			$count = $probe->setSourceFile($temp);

			$pages = [];
			for ($page = 1; $page <= $count; $page++) {
				$single = $this->documents->create();
				$single->setSourceFile($temp);
				$template = $single->importPage($page);
				$size = $single->getTemplateSize($template);
				$single->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$single->useTemplate($template);
				$pages[] = $this->documents->output(document: $single);
			}

			return $pages;
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not read the batch as a PDF: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		} finally {
			if (file_exists($temp) === true) {
				unlink($temp);
			}
		}//end try

	}//end pages()

	/**
	 * The text on one page.
	 *
	 * @param string $page The page as PDF bytes.
	 *
	 * @return string The text, or an empty string when there is none to read.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function text(string $page): string {
		$temp = tempnam(sys_get_temp_dir(), 'filinq-page-');
		if ($temp === false) {
			return '';
		}

		try {
			file_put_contents($temp, $page);
			$result = $this->ocr->extractTextFromPdf($temp);

			return trim((string)($result['text'] ?? ''));
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[ScanPageReader] could not read the text of one page',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return '';
		} finally {
			if (file_exists($temp) === true) {
				unlink($temp);
			}
		}//end try

	}//end text()

	/**
	 * How many bytes one page takes as its own PDF.
	 *
	 * A proxy for how much is on the page, and named as one. It tells a page
	 * carrying a scanned image apart from a page carrying nearly nothing, which
	 * is all the blank-page mode needs; it is not a measure of ink coverage and
	 * nothing here treats it as one.
	 *
	 * @param string $page The page as PDF bytes.
	 *
	 * @return int The byte count.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function weight(string $page): int {
		return strlen($page);

	}//end weight()
}//end class
