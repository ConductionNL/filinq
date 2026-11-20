<?php

/**
 * A scanned PDF or a photographed page gets an invisible, searchable text layer.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use FPDF;
use InvalidArgumentException;
use OCA\Filinq\Service\Ocr\InvisibleTextPdf;
use OCA\Filinq\Service\Ocr\OcrPage;
use OCA\Filinq\Service\Ocr\PdfPageRasterizer;
use OCA\Filinq\Service\Ocr\SearchablePdfWriter;
use OCA\Filinq\Service\TesseractCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * OcrPage, InvisibleTextPdf and SearchablePdfWriter.
 */
class SearchablePdfWriterTest extends TestCase {

	/**
	 * Files to remove after the test.
	 *
	 * @var list<string>
	 */
	private array $temporary = [];

	/**
	 * Remove the files the test made.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->temporary as $path) {
			if (is_file($path) === true) {
				unlink($path);
			}
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * Tesseract's TSV for a page of 2480 x 3508 pixels reading "Bezwaar tegen besluit".
	 *
	 * @return string
	 */
	private static function tsv(): string {
		return implode(
			"\n",
			[
				"level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext",
				"1\t1\t0\t0\t0\t0\t0\t0\t2480\t3508\t-1\t",
				"2\t1\t1\t0\t0\t0\t300\t400\t1200\t80\t-1\t",
				"4\t1\t1\t1\t1\t0\t300\t400\t1200\t80\t-1\t",
				"5\t1\t1\t1\t1\t1\t300\t400\t420\t80\t96.1\tBezwaar",
				"5\t1\t1\t1\t1\t2\t760\t400\t300\t80\t95.0\ttegen",
				"5\t1\t1\t1\t1\t3\t1100\t400\t400\t80\t93.2\tbesluit",
				"5\t1\t1\t1\t1\t4\t1600\t400\t10\t80\t-1\t ",
			]
		);

	}//end tsv()

	/**
	 * A scanned-looking PDF without text: pages with only a drawn rectangle.
	 *
	 * @param int $pages How many A4 pages.
	 *
	 * @return string Its path.
	 */
	private function scannedPdf(int $pages): string {
		$pdf = new FPDF('P', 'pt', 'A4');
		for ($page = 0; $page < $pages; $page++) {
			$pdf->AddPage();
			$pdf->Rect(50, 50, 200, 100, 'F');
		}

		$path = tempnam(sys_get_temp_dir(), 'filinq-scan');
		file_put_contents($path, $pdf->Output('S'));
		$this->temporary[] = $path;

		return $path;

	}//end scannedPdf()

	/**
	 * Every content stream of a PDF, inflated.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return string The streams joined.
	 */
	private static function contentOf(string $pdf): string {
		preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);
		$streams = array_map(static fn (string $raw): string => (string) (@gzuncompress($raw) ?: $raw), $matches[1]);

		return implode("\n", $streams);

	}//end contentOf()

	/**
	 * A writer whose rasterizer renders the given number of pages and whose Tesseract reads the TSV.
	 *
	 * @param int        $pages How many page images the rasterizer renders.
	 * @param array|null $asked Filled with the paths, languages and DPI Tesseract was asked for.
	 *
	 * @return SearchablePdfWriter
	 */
	private function writer(int $pages, ?array &$asked = null): SearchablePdfWriter {
		$asked = [];
		$rasterizer = $this->createMock(PdfPageRasterizer::class);
		$rasterizer->method('pages')->willReturnCallback(
			static function (string $pdfPath, int $dpi, string $directory) use ($pages): array {
				$paths = [];
				for ($page = 0; $page < $pages; $page++) {
					$paths[] = $directory . '/page_' . $page . '.png';
					file_put_contents(end($paths), 'png');
				}

				return $paths;
			}
		);

		$tesseract = $this->createMock(TesseractCommand::class);
		$tesseract->method('words')->willReturnCallback(
			static function (string $filePath, string $languages, int $dpi) use (&$asked): string {
				$asked[] = [basename($filePath), $languages, $dpi];
				return self::tsv();
			}
		);

		return new SearchablePdfWriter(tesseract: $tesseract, rasterizer: $rasterizer);

	}//end writer()

	/**
	 * The TSV gives the image size and the words with text, and skips blank and structural rows.
	 *
	 * @return void
	 */
	public function testThePageIsReadFromTesseractsTsv(): void {
		$page = OcrPage::fromTsv(tsv: self::tsv());

		$this->assertSame(2480, $page->width);
		$this->assertSame(3508, $page->height);
		$this->assertSame(['Bezwaar', 'tegen', 'besluit'], array_column($page->words, 'text'));
		$this->assertSame(['text' => 'Bezwaar', 'left' => 300, 'top' => 400, 'width' => 420, 'height' => 80], $page->words[0]);

		$empty = OcrPage::fromTsv(tsv: '');
		$this->assertSame([0, 0, []], [$empty->width, $empty->height, $empty->words]);

	}//end testThePageIsReadFromTesseractsTsv()

	/**
	 * A scanned PDF has extractable, invisible text on every page after the run; its pages keep their size.
	 *
	 * @return void
	 */
	public function testAScannedPdfHasExtractableTextAfterTheRun(): void {
		$source = $this->scannedPdf(pages: 2);
		$this->assertStringNotContainsString('Bezwaar', self::contentOf((string) file_get_contents($source)));

		$built = $this->writer(pages: 2, asked: $asked)->fromPdf(pdfPath: $source, languages: 'nld+eng', dpi: 300);

		$this->assertSame(6, $built['words']);
		$this->assertSame([['page_0.png', 'nld+eng', 300], ['page_1.png', 'nld+eng', 300]], $asked);

		$content = self::contentOf($built['bytes']);
		$this->assertSame(2, substr_count($content, '(Bezwaar) Tj'));
		$this->assertStringContainsString('(tegen) Tj', $content);
		$this->assertStringContainsString('(besluit) Tj', $content);
		$this->assertSame(2, substr_count($content, 'q 3 Tr'));

		// The original page is imported as it is: same page count and size.
		$check = new InvisibleTextPdf();
		$output = tempnam(sys_get_temp_dir(), 'filinq-out');
		$this->temporary[] = $output;
		file_put_contents($output, $built['bytes']);
		$this->assertSame(2, $check->setSourceFile($output));
		$size = $check->getTemplateSize($check->importPage(1));
		$this->assertEqualsWithDelta(595.28, $size['width'], 0.01);
		$this->assertEqualsWithDelta(841.89, $size['height'], 0.01);

	}//end testAScannedPdfHasExtractableTextAfterTheRun()

	/**
	 * A word sits in its box: scaled from pixels to points, with its baseline at the box's bottom.
	 *
	 * @return void
	 */
	public function testAWordIsPlacedInItsBox(): void {
		$built = $this->writer(pages: 1)->fromPdf(pdfPath: $this->scannedPdf(pages: 1), languages: 'nld', dpi: 300);

		// 300 px of a 2480 px page on 595.28 pt is 72.01 pt; the box bottom at 480 px of 3508 is 115.20 pt from the top.
		$this->assertMatchesRegularExpression(
			'#BT /F1 19\.20 Tf [0-9.]+ Tz 72\.01 726\.69 Td \(Bezwaar\) Tj ET#',
			self::contentOf($built['bytes'])
		);

	}//end testAWordIsPlacedInItsBox()

	/**
	 * Fewer page images than pages is refused, and the temporary images are removed.
	 *
	 * @return void
	 */
	public function testAPageCountMismatchIsRefused(): void {
		$before = glob(sys_get_temp_dir() . '/filinq_textlayer_*');

		try {
			$this->writer(pages: 1)->fromPdf(pdfPath: $this->scannedPdf(pages: 2), languages: 'nld', dpi: 300);
			$this->fail('A page count mismatch was not refused.');
		} catch (RuntimeException $e) {
			$this->assertSame('1 page images were rendered for 2 pages.', $e->getMessage());
		}

		$this->assertSame($before, glob(sys_get_temp_dir() . '/filinq_textlayer_*'));

	}//end testAPageCountMismatchIsRefused()

	/**
	 * A photographed page becomes a PDF of the image's size at its resolution, with its words.
	 *
	 * @return void
	 */
	public function testAPhotographedPageGetsASearchablePdf(): void {
		$image = imagecreatetruecolor(2480, 3508);
		$path = tempnam(sys_get_temp_dir(), 'filinq-photo');
		$this->temporary[] = $path;
		imagejpeg($image, $path);

		$built = $this->writer(pages: 0, asked: $asked)->fromImage(imagePath: $path, mimeType: 'image/jpeg', languages: 'nld', dpi: 300);

		$this->assertSame(3, $built['words']);
		$this->assertSame([[basename($path), 'nld', 300]], $asked);
		$this->assertStringStartsWith('%PDF-', $built['bytes']);
		$this->assertStringContainsString('/Subtype /Image', $built['bytes']);
		$this->assertStringContainsString('/MediaBox [0 0 595.20 841.92]', $built['bytes']);
		$this->assertStringContainsString('(Bezwaar) Tj', self::contentOf($built['bytes']));

	}//end testAPhotographedPageGetsASearchablePdf()

	/**
	 * An image type a page is not built from is refused.
	 *
	 * @return void
	 */
	public function testAnUnsupportedImageTypeIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A searchable PDF is not built from image/tiff.');

		$this->writer(pages: 0)->fromImage(imagePath: '/nonexistent.tif', mimeType: 'image/tiff', languages: 'nld', dpi: 300);

	}//end testAnUnsupportedImageTypeIsRefused()

	/**
	 * A page without words or without a size gets no text.
	 *
	 * @return void
	 */
	public function testAPageWithoutWordsWritesNothing(): void {
		$pdf = new InvisibleTextPdf();
		$pdf->AddPage();

		$this->assertSame(0, $pdf->addInvisibleWords(page: new OcrPage(width: 100, height: 100, words: [])));
		$this->assertSame(0, $pdf->addInvisibleWords(page: new OcrPage(width: 0, height: 0, words: [['text' => 'x', 'left' => 0, 'top' => 0, 'width' => 5, 'height' => 5]])));
		$this->assertStringNotContainsString('Tj', self::contentOf($pdf->Output('S')));

	}//end testAPageWithoutWordsWritesNothing()
}//end class
