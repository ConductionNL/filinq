<?php

/**
 * OCR Page
 *
 * One page as Tesseract read it: the size of the image it read, in pixels,
 * and each recognised word with its box in that image.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

/**
 * The words of one page and the pixel size they are measured in.
 */
final class OcrPage {

	/**
	 * Constructor.
	 *
	 * @param int $width The image width in pixels.
	 * @param int $height The image height in pixels.
	 * @param list<array{text: string, left: int, top: int, width: int, height: int}> $words The words, in reading order.
	 */
	public function __construct(
		public readonly int $width,
		public readonly int $height,
		public readonly array $words,
	) {

	}//end __construct()

	/**
	 * The page read from Tesseract's TSV output.
	 *
	 * The level-1 row gives the image size; each level-5 row with text is a word.
	 *
	 * @param string $tsv The TSV text, header line first.
	 *
	 * @return self The page; without a level-1 row its size is 0 x 0.
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity) Each branch skips one kind of malformed TSV row;
	 * they are independent checks of one line.
	 *
	 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#task-1.1
	 */
	public static function fromTsv(string $tsv): self {
		$width  = 0;
		$height = 0;
		$words  = [];
		$lines = preg_split('/\r?\n/', $tsv);
		if ($lines === false) {
			$lines = [];
		}

		foreach ($lines as $line) {
			$cells = explode("\t", $line);
			if (count($cells) < 12 || ctype_digit($cells[0]) === false) {
				continue;
			}

			if ($cells[0] === '1' && $width === 0) {
				$width  = (int) $cells[8];
				$height = (int) $cells[9];
				continue;
			}

			$text = trim(implode("\t", array_slice($cells, 11)));
			if ($cells[0] !== '5' || $text === '' || (int) $cells[8] <= 0 || (int) $cells[9] <= 0) {
				continue;
			}

			$words[] = [
				'text' => $text,
				'left' => (int) $cells[6],
				'top' => (int) $cells[7],
				'width' => (int) $cells[8],
				'height' => (int) $cells[9],
			];
		}//end foreach

		return new self(width: $width, height: $height, words: $words);

	}//end fromTsv()
}//end class
