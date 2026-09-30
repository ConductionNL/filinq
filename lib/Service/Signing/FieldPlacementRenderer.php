<?php

/**
 * Field Placement Renderer
 *
 * Draws a signing request's placed fields into the PDF before the native
 * provider hashes it, so the visible blocks sit inside the content the v2 MAC
 * covers: a block moved or altered after signing makes verification fail.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Throwable;

/**
 * Renders placed fields onto the pages of a PDF.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.2
 */
class FieldPlacementRenderer {

	/**
	 * How many pages the PDF has.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return int The page count.
	 *
	 * @throws RuntimeException When the bytes are not a PDF this renderer can read.
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function pageCount(string $pdf): int {
		try {
			return (new Fpdi())->setSourceFile(StreamReader::createByString($pdf));
		} catch (Throwable $e) {
			throw new RuntimeException('Fields cannot be placed on this document: ' . $e->getMessage(), 0, $e);
		}

	}//end pageCount()

	/**
	 * Draw the placements onto the PDF.
	 *
	 * Without placements the bytes come back untouched, so a request without
	 * fields produces exactly the artifact it produced before. With them every
	 * page is carried over at its own size and each field becomes a bordered
	 * box: the signer's name for a signature, their initials, the signing date,
	 * the signer's name for a text field, a cross for a checkbox.
	 *
	 * @param string                               $pdf        The PDF bytes.
	 * @param list<array<string, mixed>>           $placements The normalised placements.
	 * @param list<string>                         $signers    The signer labels, by signer index.
	 * @param string                               $timestamp  The signing moment (ATOM).
	 *
	 * @return string The PDF with the fields drawn.
	 *
	 * @throws RuntimeException When the PDF cannot be read or a placement names a page it does not have.
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.2
	 */
	public function render(string $pdf, array $placements, array $signers, string $timestamp): string {
		if ($placements === []) {
			return $pdf;
		}

		$document = new Fpdi('P', 'pt');
		$document->SetAutoPageBreak(false);
		$pages = $this->pageCount(pdf: $pdf);
		$document->setSourceFile(StreamReader::createByString($pdf));

		foreach ($placements as $placement) {
			if ((int) $placement['page'] > $pages) {
				throw new RuntimeException(
					'A field is placed on page ' . $placement['page'] . ' of a document with ' . $pages . ' pages'
				);
			}
		}

		for ($page = 1; $page <= $pages; $page++) {
			$template = $document->importPage($page);
			$size     = $document->getTemplateSize($template);
			$document->AddPage($size['orientation'], [$size['width'], $size['height']]);
			$document->useTemplate($template);

			foreach ($placements as $placement) {
				if ((int) $placement['page'] === $page) {
					$this->drawField(
						document: $document,
						placement: $placement,
						size: $size,
						text: $this->fieldText(placement: $placement, signers: $signers, timestamp: $timestamp)
					);
				}
			}
		}

		return $document->Output('S');

	}//end render()

	/**
	 * The text a field shows.
	 *
	 * @param array<string, mixed> $placement The placement.
	 * @param list<string>         $signers   The signer labels.
	 * @param string               $timestamp The signing moment.
	 *
	 * @return string The text.
	 */
	private function fieldText(array $placement, array $signers, string $timestamp): string {
		$signer = (string) ($signers[(int) $placement['signerIndex']] ?? '');

		return match ($placement['type']) {
			'initials' => $this->initials(name: $signer),
			'date'     => substr($timestamp, 0, 10),
			'checkbox' => 'X',
			default    => $signer,
		};

	}//end fieldText()

	/**
	 * The initials of a name: the first letter of each word, upper case.
	 *
	 * @param string $name The name.
	 *
	 * @return string The initials.
	 */
	private function initials(string $name): string {
		$letters = '';
		foreach (preg_split('/[\s.\-]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) as $word) {
			$letters .= mb_strtoupper(mb_substr($word, 0, 1));
		}

		return $letters;

	}//end initials()

	/**
	 * Draw one bordered field with its text.
	 *
	 * @param Fpdi                 $document  The document, on the field's page.
	 * @param array<string, mixed> $placement The placement (0-1, origin top left).
	 * @param array<string, mixed> $size      The page size in points.
	 * @param string               $text      The text to show.
	 *
	 * @return void
	 */
	private function drawField(Fpdi $document, array $placement, array $size, string $text): void {
		$left   = (float) $placement['x'] * (float) $size['width'];
		$top    = (float) $placement['y'] * (float) $size['height'];
		$width  = (float) $placement['width'] * (float) $size['width'];
		$height = (float) $placement['height'] * (float) $size['height'];

		$document->SetDrawColor(0, 0, 0);
		$document->SetLineWidth(0.8);
		$document->Rect($left, $top, $width, $height);

		// Core fonts speak Windows-1252; a name outside it keeps its readable letters.
		$encoded = (string) iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
		$document->SetFont('Helvetica', '', max(6.0, min(14.0, $height * 0.5)));
		$document->SetXY($left, $top);
		$document->Cell($width, $height, $encoded, 0, 0, 'C');

	}//end drawField()
}//end class
