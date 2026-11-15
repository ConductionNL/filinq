<?php

/**
 * PDF Merger
 *
 * Joins PDF byte streams into one PDF with continuous pages and a named
 * outline entry at the first page of each bookmarked stream, through mPDF's
 * own FPDI import (no other library). Other apps call mergePdfs() to bind
 * documents together (a meeting pack, a dossier); the document comes from
 * PdfService, so it has the app's mPDF configuration.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Exception\PdfMergeException;
use Throwable;

/**
 * Merges PDFs page by page into one mPDF document.
 *
 * Every input is parsed before anything is written, so a stream that cannot
 * be read fails the merge with its index and no half-built document.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */
class PdfMerger {

	/**
	 * Constructor.
	 *
	 * @param PdfService             $pdfService Makes the configured mPDF document to merge into.
	 * @param PdfStreamReaderFactory $readers    Builds the in-memory readers FPDI parses.
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly PdfStreamReaderFactory $readers = new PdfStreamReaderFactory(),
	) {

	}//end __construct()

	/**
	 * Merge PDFs into one, with continuous pages and a named outline entry
	 * at the first page of each bookmarked stream.
	 *
	 * @param array<int, string> $pdfs      The PDF byte streams, in order.
	 * @param array<int, string> $bookmarks An outline title per stream index; a stream without one gets none.
	 *
	 * @return string The merged PDF.
	 *
	 * @throws PdfMergeException When a stream cannot be read as a PDF; getStreamIndex() names it, so the caller can substitute a placeholder.
	 * @throws \Exception        When mPDF cannot be instantiated.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	public function mergePdfs(array $pdfs, array $bookmarks = []): string {
		return $this->merge(mpdf: $this->pdfService->createMpdfInstance(), pdfs: $pdfs, bookmarks: $bookmarks);

	}//end mergePdfs()

	/**
	 * Merge the streams into the given mPDF document and return the PDF.
	 *
	 * @param Mpdf                $mpdf      An empty mPDF document.
	 * @param array<int, string>  $pdfs      The PDF byte streams, in order.
	 * @param array<int, string>  $bookmarks An outline title per stream index; a stream without one gets none.
	 *
	 * @return string The merged PDF.
	 *
	 * @throws PdfMergeException When a stream cannot be read as a PDF, naming its index.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	public function merge(Mpdf $mpdf, array $pdfs, array $bookmarks = []): string {
		$streams = array_values($pdfs);
		foreach ($streams as $index => $bytes) {
			$this->assertReadable(mpdf: $mpdf, index: $index, bytes: $bytes);
		}

		foreach ($streams as $index => $bytes) {
			$this->appendStream(mpdf: $mpdf, index: $index, bytes: $bytes, bookmark: (string)($bookmarks[$index] ?? ''));
		}

		return (string)$mpdf->Output('', 'S');

	}//end merge()

	/**
	 * Fail with the stream's index when it is not a PDF with at least one page.
	 *
	 * @param Mpdf   $mpdf  The document.
	 * @param int    $index The stream's index.
	 * @param mixed  $bytes The stream.
	 *
	 * @return void
	 *
	 * @throws PdfMergeException When the stream cannot be read.
	 */
	private function assertReadable(Mpdf $mpdf, int $index, mixed $bytes): void {
		if (is_string($bytes) === false || $bytes === '') {
			throw new PdfMergeException(streamIndex: $index);
		}

		try {
			$pages = $mpdf->setSourceFile($this->readers->fromString(content: $bytes));
		} catch (Throwable $e) {
			throw new PdfMergeException(streamIndex: $index, previous: $e);
		}

		if ($pages < 1) {
			throw new PdfMergeException(streamIndex: $index);
		}

	}//end assertReadable()

	/**
	 * Append every page of one stream, at its own size, bookmarking the first.
	 *
	 * @param Mpdf   $mpdf     The document.
	 * @param int    $index    The stream's index.
	 * @param string $bytes    The stream.
	 * @param string $bookmark The outline title, or '' for none.
	 *
	 * @return void
	 *
	 * @throws PdfMergeException When the stream cannot be imported after all.
	 */
	private function appendStream(Mpdf $mpdf, int $index, string $bytes, string $bookmark): void {
		try {
			$pages = $mpdf->setSourceFile($this->readers->fromString(content: $bytes));
			for ($page = 1; $page <= $pages; $page++) {
				$template = $mpdf->importPage($page);
				$size     = $mpdf->getTemplateSize($template);
				$sheet    = [$size['width'], $size['height']];
				$turned   = $size['width'] > $size['height'];
				if ($turned === true) {
					$sheet = [$size['height'], $size['width']];
				}

				$orientation = 'P';
				if ($turned === true) {
					$orientation = 'L';
				}

				// MPDF takes the sheet in portrait and turns it for `L`.
				$mpdf->AddPageByArray(['orientation' => $orientation, 'sheet-size' => $sheet]);
				$mpdf->useTemplate($template);
				if ($page === 1 && trim($bookmark) !== '') {
					$mpdf->Bookmark(trim($bookmark), 0);
				}
			}
		} catch (Throwable $e) {
			throw new PdfMergeException(streamIndex: $index, previous: $e);
		}

	}//end appendStream()
}//end class
