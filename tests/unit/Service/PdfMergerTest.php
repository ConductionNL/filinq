<?php

/**
 * Unit tests for PdfMerger
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Exception\PdfMergeException;
use OCA\Filinq\Service\PdfMerger;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PdfStreamReaderFactory;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\Fpdi;

/**
 * Merging PDFs: continuous pages, one outline entry per bookmarked stream,
 * each page at its own size, and an unreadable stream named by its index
 * before anything is written.
 *
 * @covers \OCA\Filinq\Service\PdfMerger
 * @covers \OCA\Filinq\Exception\PdfMergeException
 * @uses   \OCA\Filinq\Service\PdfStreamReaderFactory
 * @uses   \OCA\Filinq\Service\PdfService
 */
class PdfMergerTest extends TestCase {

	/**
	 * A PDF with the given number of pages, each saying its label.
	 *
	 * @param int    $pages  How many pages.
	 * @param string $label  The text on each page.
	 * @param string $format The page format, e.g. A4 or A4-L.
	 *
	 * @return string
	 */
	private function pdf(int $pages, string $label, string $format = 'A4'): string {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-test', 'format' => $format]);
		for ($page = 1; $page <= $pages; $page++) {
			if ($page > 1) {
				$mpdf->AddPage();
			}

			$mpdf->WriteHTML('<p>' . $label . ' ' . $page . '</p>');
		}

		return (string)$mpdf->Output('', 'S');
	}//end pdf()

	/**
	 * The page count of a PDF, read back with FPDI.
	 *
	 * @param string $pdf The PDF.
	 *
	 * @return int
	 */
	private function pageCount(string $pdf): int {
		return (new Fpdi())->setSourceFile((new PdfStreamReaderFactory())->fromString(content: $pdf));
	}//end pageCount()

	/**
	 * A merger over a real PdfService.
	 *
	 * @return PdfMerger
	 */
	private function merger(): PdfMerger {
		return new PdfMerger(new PdfService($this->createMock(LoggerInterface::class), $this->createMock(TemplateRenderer::class)));
	}//end merger()

	/**
	 * An empty mPDF document to merge into.
	 *
	 * @return Mpdf
	 */
	private function document(): Mpdf {
		return new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-test']);
	}//end document()

	/**
	 * The pages of every stream follow each other in order.
	 *
	 * @return void
	 */
	public function testPagesAreContinuousInStreamOrder(): void {
		$merged = $this->merger()->merge(mpdf: $this->document(), pdfs: [$this->pdf(2, 'Agenda'), $this->pdf(3, 'Bijlage')]);

		$this->assertStringStartsWith('%PDF-', $merged);
		$this->assertSame(5, $this->pageCount($merged));
	}//end testPagesAreContinuousInStreamOrder()

	/**
	 * Each bookmarked stream gets one named outline entry; one without a title gets none.
	 *
	 * @return void
	 */
	public function testEachBookmarkIsANamedOutlineEntry(): void {
		$mpdf   = $this->document();
		$merged = $this->merger()->merge(
			mpdf: $mpdf,
			pdfs: [$this->pdf(1, 'Agenda'), $this->pdf(1, 'Notulen'), $this->pdf(2, 'Raadsvoorstel')],
			bookmarks: [0 => 'Agenda', 2 => 'Raadsvoorstel']
		);

		$outline = array_map(static fn (array $entry): array => [$entry['t'], $entry['p']], $mpdf->BMoutlines);
		$this->assertSame([['Agenda', 1], ['Raadsvoorstel', 3]], $outline);
		$this->assertStringContainsString('/Outlines', $merged);
		$this->assertSame(4, $this->pageCount($merged));
	}//end testEachBookmarkIsANamedOutlineEntry()

	/**
	 * A landscape attachment keeps its landscape page.
	 *
	 * @return void
	 */
	public function testEachPageKeepsItsOwnSize(): void {
		$merged = $this->merger()->merge(mpdf: $this->document(), pdfs: [$this->pdf(1, 'Staand'), $this->pdf(1, 'Liggend', 'A4-L')]);

		$reader = new Fpdi();
		$reader->setSourceFile((new PdfStreamReaderFactory())->fromString(content: $merged));
		$portrait  = $reader->getTemplateSize($reader->importPage(1));
		$landscape = $reader->getTemplateSize($reader->importPage(2));

		$this->assertSame('P', $portrait['orientation']);
		$this->assertSame('L', $landscape['orientation']);
	}//end testEachPageKeepsItsOwnSize()

	/**
	 * An unreadable stream fails the merge with its index, before any page is written.
	 *
	 * @return void
	 */
	public function testAnUnreadableStreamIsNamedByItsIndex(): void {
		$mpdf = $this->document();
		try {
			$this->merger()->merge(mpdf: $mpdf, pdfs: [$this->pdf(1, 'Agenda'), 'this is not a pdf', $this->pdf(1, 'Bijlage')]);
			$this->fail('An unreadable stream must fail the merge.');
		} catch (PdfMergeException $e) {
			$this->assertSame(1, $e->getStreamIndex());
			$this->assertSame(422, $e->getCode());
			$this->assertNotNull($e->getPrevious());
		}

		$this->assertSame(0, $mpdf->page);
	}//end testAnUnreadableStreamIsNamedByItsIndex()

	/**
	 * An empty stream is unreadable too.
	 *
	 * @return void
	 */
	public function testAnEmptyStreamIsUnreadable(): void {
		$this->expectException(PdfMergeException::class);
		$this->expectExceptionMessage('PDF stream 0 could not be read as a PDF.');

		$this->merger()->merge(mpdf: $this->document(), pdfs: ['']);
	}//end testAnEmptyStreamIsUnreadable()

	/**
	 * mergePdfs() merges into PdfService's own configured mPDF document.
	 *
	 * @return void
	 */
	public function testMergePdfsUsesTheAppsMpdfConfiguration(): void {
		$merged = $this->merger()->mergePdfs([$this->pdf(1, 'Agenda'), $this->pdf(2, 'Bijlage')], [0 => 'Agenda', 1 => 'Bijlage']);

		$this->assertSame(3, $this->pageCount($merged));
		$this->assertStringContainsString('/Outlines', $merged);
	}//end testMergePdfsUsesTheAppsMpdfConfiguration()
}//end class
