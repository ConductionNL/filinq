<?php

/**
 * Unit tests for ReceivedStampService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-intake-acknowledge-and-stamp
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use FPDF;
use OCA\Filinq\Service\PdfStreamReaderFactory;
use OCA\Filinq\Service\ReceivedStampService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * The stamp sits on page 1 of a copy; the pages and the arrived file stay as they were.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
 */
#[CoversClass(ReceivedStampService::class)]
#[UsesClass(PdfStreamReaderFactory::class)]
class ReceivedStampServiceTest extends TestCase {

	/**
	 * A two-page PDF: A4 portrait, then A4 landscape.
	 *
	 * @return string
	 */
	private static function arrived(): string {
		$pdf = new FPDF('P', 'pt', 'A4');
		$pdf->SetFont('Helvetica', '', 12);
		$pdf->AddPage();
		$pdf->Cell(0, 20, 'Bezwaarschrift');
		$pdf->AddPage('L');
		$pdf->Cell(0, 20, 'Bijlage');

		return $pdf->Output('S');
	}//end arrived()

	/**
	 * The page texts of a PDF, page by page, from its content streams.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return string All decoded content, joined.
	 */
	private static function content(string $pdf): string {
		preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
		$text = '';
		foreach ($streams[1] as $stream) {
			$decoded = @gzuncompress($stream);
			$text   .= ($decoded === false ? $stream : $decoded) . "\n";
		}

		return $text;
	}//end content()

	/**
	 * The page sizes of a PDF.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return list<array{0: float, 1: float}>
	 */
	private static function sizes(string $pdf): array {
		$reader = new Fpdi('P', 'pt');
		$pages  = $reader->setSourceFile((new PdfStreamReaderFactory())->fromString(content: $pdf));
		$sizes  = [];
		for ($page = 1; $page <= $pages; $page++) {
			$size    = $reader->getTemplateSize($reader->importPage($page));
			$sizes[] = [round((float)$size['width']), round((float)$size['height'])];
		}

		return $sizes;
	}//end sizes()

	/**
	 * @return ReceivedStampService
	 */
	private function service(?IRootFolder $root = null): ReceivedStampService {
		return new ReceivedStampService(rootFolder: ($root ?? $this->createMock(IRootFolder::class)), logger: new NullLogger());
	}//end service()

	/**
	 * Every page is kept at its size, and the stamp with the word, the date
	 * and the number is drawn once.
	 *
	 * @return void
	 */
	public function testThePagesStayAndTheStampCarriesDateAndNumber(): void {
		$arrived = self::arrived();
		$stamped = $this->service()->stamp(pdf: $arrived, date: '02-10-2026', number: 'IN-2026-0042');

		$this->assertSame(self::sizes(pdf: $arrived), self::sizes(pdf: $stamped));
		$content = self::content(pdf: $stamped);
		$this->assertSame(1, substr_count($content, '(Ontvangen)'));
		$this->assertStringContainsString('(02-10-2026)', $content);
		$this->assertStringContainsString('(IN-2026-0042)', $content);
		$this->assertStringNotContainsString('Ontvangen', self::content(pdf: $arrived));
	}//end testThePagesStayAndTheStampCarriesDateAndNumber()

	/**
	 * Without a registration the stamp carries the date only.
	 *
	 * @return void
	 */
	public function testWithoutANumberTheStampHasTheDateOnly(): void {
		$content = self::content(pdf: $this->service()->stamp(pdf: self::arrived(), date: '02-10-2026'));

		$this->assertStringContainsString('(02-10-2026)', $content);
		$this->assertStringNotContainsString('IN-2026', $content);
	}//end testWithoutANumberTheStampHasTheDateOnly()

	/**
	 * Bytes that are no PDF are refused, by name.
	 *
	 * @return void
	 */
	public function testANonPdfIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('A received stamp cannot be placed on this document');

		$this->service()->stamp(pdf: 'not a pdf', date: '02-10-2026');
	}//end testANonPdfIsRefused()

	/**
	 * The copy is written beside the arrived file as "<name> (ontvangen).pdf";
	 * the arrived file is read and never written, so its checksum stays.
	 *
	 * @return void
	 */
	public function testTheCopyIsWrittenBesideAndTheArrivedFileIsUntouched(): void {
		$bytes    = self::arrived();
		$checksum = hash('sha256', $bytes);
		$written  = [];

		$copy = $this->createMock(File::class);
		$copy->method('getId')->willReturn(77);
		$folder = $this->createMock(Folder::class);
		$folder->method('getNonExistingName')->willReturnArgument(0);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, $content) use (&$written, $copy): File {
				$written[] = [$name, $content];
				return $copy;
			}
		);

		$file = $this->createMock(File::class);
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getContent')->willReturn($bytes);
		$file->method('getName')->willReturn('Bezwaar.PDF');
		$file->method('getParent')->willReturn($folder);
		$file->expects($this->never())->method('putContent');

		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->with(41)->willReturn($file);

		$this->assertSame(77, $this->service(root: $root)->stampFile(fileId: 41, date: '02-10-2026'));
		$this->assertCount(1, $written);
		$this->assertSame('Bezwaar (ontvangen).pdf', $written[0][0]);
		$this->assertStringStartsWith('%PDF', (string)$written[0][1]);
		$this->assertNotSame($checksum, hash('sha256', (string)$written[0][1]));
		$this->assertSame($checksum, hash('sha256', $bytes));
	}//end testTheCopyIsWrittenBesideAndTheArrivedFileIsUntouched()

	/**
	 * A file that is gone, is not a PDF or cannot be read gets no copy.
	 *
	 * @return void
	 */
	public function testNoCopyForAMissingOrNonPdfFile(): void {
		$text = $this->createMock(File::class);
		$text->method('getMimetype')->willReturn('text/plain');
		$broken = $this->createMock(File::class);
		$broken->method('getMimetype')->willReturn('application/pdf');
		$broken->method('getContent')->willReturn('%PDF-broken');
		$broken->method('getParent')->willReturn($this->createMock(Folder::class));

		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnMap([[1, null], [2, $text], [3, $broken]]);

		foreach ([1, 2, 3] as $id) {
			$this->assertNull($this->service(root: $root)->stampFile(fileId: $id, date: '02-10-2026'), (string)$id);
		}
	}//end testNoCopyForAMissingOrNonPdfFile()
}//end class
