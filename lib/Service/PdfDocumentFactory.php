<?php

/**
 * PDF Document Factory
 *
 * The seam between the merge and FPDI. It builds the document, appends the
 * pages of one PDF at a time and hands back the bytes.
 *
 * FPDI reads its sources from a PATH, not from a string, so every append writes
 * a temp file and removes it again. Keeping that in one place means the merge
 * itself never touches the filesystem for anything but its result.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use RuntimeException;
use Throwable;

/**
 * Builds and fills the FPDI document a merge produces.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class PdfDocumentFactory {

	/**
	 * A new, empty merged document.
	 *
	 * @return MergedPdfDocument The document.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function create(): MergedPdfDocument {
		return new MergedPdfDocument();

	}//end create()

	/**
	 * Append every page of one PDF to the document.
	 *
	 * Each page keeps its own size and orientation. A merge that forced every
	 * page onto A4 portrait would silently crop the landscape plattegrond that
	 * is exactly why somebody merged the bundle in the first place.
	 *
	 * @param MergedPdfDocument $document The document under construction.
	 * @param string $pdf The raw bytes of the PDF to append.
	 *
	 * @return int How many pages were appended.
	 *
	 * @throws RuntimeException When the bytes are not a PDF FPDI can read.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function appendPages(MergedPdfDocument $document, string $pdf): int {
		if ($pdf === '') {
			throw new RuntimeException(message: 'There are no bytes to append.');
		}

		$temp = tempnam(sys_get_temp_dir(), 'filinq-merge-');
		if ($temp === false) {
			throw new RuntimeException(message: 'Could not allocate a temp file for the merge.');
		}

		try {
			file_put_contents($temp, $pdf);
			$pages = $document->setSourceFile($temp);

			for ($page = 1; $page <= $pages; $page++) {
				$template = $document->importPage($page);
				$size = $document->getTemplateSize($template);
				$document->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$document->useTemplate($template);
			}

			return $pages;
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not read this document as a PDF: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		} finally {
			if (file_exists($temp) === true) {
				unlink($temp);
			}
		}//end try

	}//end appendPages()

	/**
	 * The finished document, as bytes.
	 *
	 * @param MergedPdfDocument $document The document.
	 *
	 * @return string The PDF bytes.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function output(MergedPdfDocument $document): string {
		// FPDI inherits Output() from FPDF, which has no stubs for static
		// analysis; 'S' returns the bytes rather than sending them.
		// @phpstan-ignore-next-line method.notFound
		return (string)$document->Output('S');

	}//end output()
}//end class
