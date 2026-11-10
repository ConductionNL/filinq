<?php

/**
 * PDF OCR Run
 *
 * The state of one PDF OCR pass while OcrService runs it: the loaded
 * document, the options, the page count, the temp directory the page images
 * go to, and the text and confidence gathered so far. Built and used inside
 * OcrService only.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
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

use Imagick;

/**
 * One PDF OCR pass and its progress.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */
final class PdfOcrRun {

	/**
	 * How many pages the document has.
	 *
	 * @var int
	 */
	public readonly int $pageCount;

	/**
	 * Where the page images are written.
	 *
	 * @var string
	 */
	public readonly string $tempDir;

	/**
	 * The text of the pages read so far, one line break after each.
	 *
	 * @var string
	 */
	public string $text = '';

	/**
	 * The summed confidence of the pages read so far.
	 *
	 * @var float
	 */
	public float $totalConfidence = 0.0;

	/**
	 * Constructor: counts the pages of the loaded document and names the temp directory.
	 *
	 * @param Imagick    $imagick The document, already read.
	 * @param OcrOptions $options The languages and DPI.
	 */
	public function __construct(
		public readonly Imagick $imagick,
		public readonly OcrOptions $options,
	) {
		$this->pageCount = $imagick->getNumberImages();
		$this->tempDir = sys_get_temp_dir() . '/filinq_ocr_' . uniqid();

	}//end __construct()

	/**
	 * The average confidence over all pages, 0.0 for a document without pages.
	 *
	 * @return float The unrounded average.
	 *
	 * @spec openspec/specs/ocr-document-scanning/spec.md
	 */
	public function averageConfidence(): float {
		$avgConfidence = 0.0;
		if ($this->pageCount > 0) {
			$avgConfidence = ($this->totalConfidence / $this->pageCount);
		}

		return $avgConfidence;

	}//end averageConfidence()
}//end class
