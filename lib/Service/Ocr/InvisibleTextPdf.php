<?php

/**
 * Invisible Text PDF
 *
 * An FPDI document that can put a page's recognised words on it as invisible
 * text (rendering mode 3), each scaled into the box Tesseract found it in, so
 * the page looks the same and its words can be selected and searched.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

use setasign\Fpdi\Fpdi;

/**
 * FPDI with an invisible text layer, in points.
 */
class InvisibleTextPdf extends Fpdi {

	/**
	 * Constructor: points, so a PDF page's size is used as it is.
	 */
	public function __construct() {
		parent::__construct(orientation: 'P', unit: 'pt');
		$this->SetAutoPageBreak(auto: false);
		$this->SetMargins(left: 0, top: 0, right: 0);

	}//end __construct()

	/**
	 * Put the page's words on the current page as invisible text.
	 *
	 * The page's pixel boxes are scaled onto the current page's size.
	 *
	 * @param OcrPage $page The words and the image size they are measured in.
	 *
	 * @return int How many words were written.
	 *
	 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-1.1
	 */
	public function addInvisibleWords(OcrPage $page): int {
		if ($page->width <= 0 || $page->height <= 0 || $page->words === []) {
			return 0;
		}

		$scaleX = ($this->w / $page->width);
		$scaleY = ($this->h / $page->height);
		$this->SetFont(family: 'Helvetica', style: '', size: 10);
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- FPDF's own property.
		$fontIndex = $this->CurrentFont['i'];

		$this->_out(s: 'q 3 Tr');
		$written = 0;
		foreach ($page->words as $word) {
			$text = $this->latin1(text: $word['text']);
			$size = max(1.0, ($word['height'] * $scaleY));
			$this->SetFontSize(size: $size);
			$natural = $this->GetStringWidth(s: $text);
			if ($text === '' || $natural <= 0.0) {
				continue;
			}

			$this->_out(
				s: sprintf(
					'BT /F%d %.2F Tf %.2F Tz %.2F %.2F Td (%s) Tj ET',
					$fontIndex,
					$size,
					(100 * ($word['width'] * $scaleX) / $natural),
					($word['left'] * $scaleX),
					($this->h - (($word['top'] + $word['height']) * $scaleY)),
					$this->_escape(s: $text)
				)
			);
			$written++;
		}

		$this->_out(s: 'Q');

		return $written;

	}//end addInvisibleWords()

	/**
	 * A word in the Windows-1252 encoding of the core fonts; what has no equivalent is dropped.
	 *
	 * @param string $text The UTF-8 word.
	 *
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator) The call reports failure through its return value,
	 * which is checked right after; the PHP warning it would also raise adds nothing.
	 */
	private function latin1(string $text): string {
		$converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
		if ($converted === false) {
			return '';
		}

		return $converted;

	}//end latin1()
}//end class
