<?php

/**
 * Unit tests for ScanPageReader
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\MergedPdfDocument;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\PdfDocumentFactory;
use OCA\Filinq\Service\ScanPageReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The reader cuts a real PDF into single pages with the real FPDI factory, asks
 * the OCR toolchain (a double) for the text, and never leaves temp files behind.
 *
 * @covers \OCA\Filinq\Service\ScanPageReader
 * @uses   \OCA\Filinq\Service\PdfDocumentFactory
 * @uses   \OCA\Filinq\Service\MergedPdfDocument
 */
class ScanPageReaderTest extends TestCase {

	/**
	 * The OCR toolchain.
	 *
	 * @var OcrService&MockObject
	 */
	private OcrService&MockObject $ocr;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The reader under test.
	 *
	 * @var ScanPageReader
	 */
	private ScanPageReader $reader;

	/**
	 * Build the reader.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ocr = $this->createMock(OcrService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->reader = new ScanPageReader(
			documents: new PdfDocumentFactory(),
			ocr: $this->ocr,
			logger: $this->logger
		);

	}//end setUp()

	/**
	 * A PDF with one A4 portrait and one A4 landscape page.
	 *
	 * @return string The PDF bytes.
	 */
	private function twoPageBatch(): string {
		$document = new MergedPdfDocument();
		$document->AddPage('P', 'A4');
		$document->AddPage('L', 'A4');

		return (string)$document->Output('S');

	}//end twoPageBatch()

	/**
	 * How many pages a PDF has, and the size of its first page.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return array{0: int, 1: array<string, mixed>} The count and the first page's size.
	 */
	private function inspect(string $pdf): array {
		$temp = (string)tempnam(sys_get_temp_dir(), 'filinq-test-');
		file_put_contents($temp, $pdf);
		try {
			$document = new MergedPdfDocument();
			$count = $document->setSourceFile($temp);
			$size = $document->getTemplateSize($document->importPage(1));
		} finally {
			unlink($temp);
		}

		return [$count, $size];

	}//end inspect()

	/**
	 * Without tesseract nothing can be read.
	 *
	 * @return void
	 */
	public function testIsNotAvailableWithoutTesseract(): void {
		$this->ocr->expects($this->once())->method('isTesseractAvailable')->willReturn(false);

		$this->assertFalse($this->reader->isAvailable());

	}//end testIsNotAvailableWithoutTesseract()

	/**
	 * With tesseract, availability follows the imagick extension.
	 *
	 * @return void
	 */
	public function testIsAvailableFollowsImagickWithTesseract(): void {
		$this->ocr->expects($this->once())->method('isTesseractAvailable')->willReturn(true);

		$this->assertSame(extension_loaded('imagick'), $this->reader->isAvailable());

	}//end testIsAvailableFollowsImagickWithTesseract()

	/**
	 * A toolchain probe that throws means unavailable, not an error.
	 *
	 * @return void
	 */
	public function testIsNotAvailableWhenTheProbeThrows(): void {
		$this->ocr->expects($this->once())->method('isTesseractAvailable')->willThrowException(new RuntimeException('probe'));

		$this->assertFalse($this->reader->isAvailable());

	}//end testIsNotAvailableWhenTheProbeThrows()

	/**
	 * A two-page batch becomes two one-page PDFs that keep their own size.
	 *
	 * @return void
	 */
	public function testPagesSplitsTheBatchKeepingEachPageSize(): void {
		$pages = $this->reader->pages($this->twoPageBatch());

		$this->assertCount(2, $pages);

		[$firstCount, $firstSize] = $this->inspect($pages[0]);
		[$secondCount, $secondSize] = $this->inspect($pages[1]);

		$this->assertSame(1, $firstCount);
		$this->assertSame(1, $secondCount);
		$this->assertSame('P', $firstSize['orientation']);
		$this->assertSame('L', $secondSize['orientation']);
		$this->assertEqualsWithDelta(210.0, (float)$firstSize['width'], 0.5);
		$this->assertEqualsWithDelta(297.0, (float)$secondSize['width'], 0.5);

	}//end testPagesSplitsTheBatchKeepingEachPageSize()

	/**
	 * Bytes that are no PDF fail with the reason.
	 *
	 * @return void
	 */
	public function testPagesRejectsBytesThatAreNoPdf(): void {
		try {
			$this->reader->pages('this is not a pdf');
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertStringStartsWith('Could not read the batch as a PDF: ', $e->getMessage());
			$this->assertNotNull($e->getPrevious());
		}

	}//end testPagesRejectsBytesThatAreNoPdf()

	/**
	 * The text is read off a temp copy of the page, trimmed, and the copy removed.
	 *
	 * @return void
	 */
	public function testTextReadsTheTrimmedTextAndRemovesTheTempFile(): void {
		$seen = [];
		$this->ocr->expects($this->once())
			->method('extractTextFromPdf')
			->willReturnCallback(
				static function (string $path) use (&$seen): array {
					$seen = ['path' => $path, 'bytes' => file_get_contents($path)];
					return ['text' => "  SCHEIDINGSBLAD\n "];
				}
			);

		$this->assertSame('SCHEIDINGSBLAD', $this->reader->text('%PDF-page'));
		$this->assertSame('%PDF-page', $seen['bytes']);
		$this->assertFileDoesNotExist($seen['path']);

	}//end testTextReadsTheTrimmedTextAndRemovesTheTempFile()

	/**
	 * A result without text is an empty string.
	 *
	 * @return void
	 */
	public function testTextWithoutTextKeyIsEmpty(): void {
		$this->ocr->expects($this->once())->method('extractTextFromPdf')->willReturn(['skipped' => true]);

		$this->assertSame('', $this->reader->text('%PDF-page'));

	}//end testTextWithoutTextKeyIsEmpty()

	/**
	 * A failing OCR run is logged and read as no text.
	 *
	 * @return void
	 */
	public function testTextFailureIsLoggedAndEmpty(): void {
		$this->ocr->expects($this->once())->method('extractTextFromPdf')->willThrowException(new RuntimeException('ocr broke'));
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'[ScanPageReader] could not read the text of one page',
				$this->callback(static fn (array $context): bool => $context['error'] === 'ocr broke')
			);

		$this->assertSame('', $this->reader->text('%PDF-page'));

	}//end testTextFailureIsLoggedAndEmpty()

	/**
	 * The weight of a page is its byte count.
	 *
	 * @return void
	 */
	public function testWeightIsTheByteCount(): void {
		$this->assertSame(0, $this->reader->weight(''));
		$this->assertSame(9, $this->reader->weight('%PDF-page'));

	}//end testWeightIsTheByteCount()
}//end class
