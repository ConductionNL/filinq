<?php

/**
 * tc-lib-pdf PDF/A-3b renderer
 *
 * Renders HTML to PDF/A-3b with tecnickcom/tc-lib-pdf (LGPL-3.0-or-later),
 * using the DejaVu Sans fonts shipped in lib/Fonts/tc-lib-pdf. Output was
 * checked with veraPDF against the PDF/A-3b profile; see
 * tests/unit/Service/Conversion/TcLibPdfaConformanceTest.php.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
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

namespace OCA\Filinq\Service\Conversion;

use Com\Tecnick\Pdf\Tcpdf;
use ReflectionClass;

/**
 * HTML in, PDF/A-3b bytes out.
 *
 * Fonts are loaded by explicit path, so no global K_PATH_FONTS constant is
 * defined that another app's copy of the library could pick up.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */
class TcLibPdfaRenderer {

	/**
	 * Font file base names per style, in lib/Fonts/tc-lib-pdf.
	 */
	private const FONT_FILES = [
		'B' => 'dejavusansb',
		'I' => 'dejavusansi',
		'BI' => 'dejavusansbi',
		'' => 'dejavusans',
	];

	/**
	 * Render an HTML document as one PDF/A-3b file on A4 portrait, 15 mm margins.
	 *
	 * @param string $html  HTML document.
	 * @param string $title Document title, written to the document info and XMP.
	 *
	 * @return string PDF bytes.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function render(string $html, string $title): string {
		$fontDir = dirname(__DIR__, 2) . '/Fonts/tc-lib-pdf';
		$libraryRoot = dirname((string) (new ReflectionClass(Tcpdf::class))->getFileName(), 3);
		$pdf = new Tcpdf(
			unit: 'mm',
			isunicode: true,
			subsetfont: true,
			compress: true,
			mode: 'pdfa3b',
			fileOptions: ['allowedPaths' => [sys_get_temp_dir(), $libraryRoot, $fontDir]]
		);
		$pdf->setTitle($title);
		$pdf->setCreator('Filinq');

		// The last font inserted becomes the current one, so regular goes last.
		$font = [];
		foreach (self::FONT_FILES as $style => $file) {
			$font = $pdf->font->insert($pdf->pon, 'dejavusans', $style, 10, null, null, $fontDir . '/' . $file . '.json');
		}

		$pdf->addPage();
		$pdf->page->addContent($font['out']);
		$pdf->page->addContent($pdf->getHTMLCell($html, 15, 15, 180));

		return $pdf->getOutPDFString();
	}//end render()
}//end class
