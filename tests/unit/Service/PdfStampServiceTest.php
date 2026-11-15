<?php

/**
 * Tests for PdfStampService.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Exception\PdfStampRefusal;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PdfStampService;
use OCA\Filinq\Service\PdfStreamReaderFactory;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Real mPDF and FPDI on a generated three-page fixture whose second page is
 * landscape. The stamping document uses core fonts without compression so
 * the stamped text can be read back from the bytes.
 *
 * @covers \OCA\Filinq\Service\PdfStampService
 * @uses   \OCA\Filinq\Exception\PdfStampRefusal
 * @uses   \OCA\Filinq\Service\PdfStreamReaderFactory
 */
class PdfStampServiceTest extends TestCase {

	/**
	 * The multibyte encoding before the test: mPDF in core-font mode changes it.
	 *
	 * @var string
	 */
	private string $encoding = 'UTF-8';

	protected function setUp(): void {
		parent::setUp();
		$this->encoding = (string) mb_internal_encoding();
	}//end setUp()

	protected function tearDown(): void {
		mb_internal_encoding($this->encoding);
		parent::tearDown();
	}//end tearDown()

	private const TEXT = 'J. de Vries, 28-09-2026 14:05';

	/**
	 * The service, with a size limit.
	 *
	 * @param int $limit The stamp_max_bytes setting.
	 *
	 * @return PdfStampService
	 */
	private function service(int $limit = PdfStampService::DEFAULT_MAX_BYTES): PdfStampService {
		$pdfService = $this->createMock(PdfService::class);
		$pdfService->method('createMpdfInstance')->willReturnCallback(static function (): Mpdf {
			$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test', 'mode' => 'c']);
			$mpdf->SetCompression(false);
			return $mpdf;
		});
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueInt')->with('filinq', 'stamp_max_bytes', PdfStampService::DEFAULT_MAX_BYTES)->willReturn($limit);

		return new PdfStampService(pdfService: $pdfService, streamReaders: new PdfStreamReaderFactory(), appConfig: $config);
	}//end service()

	/**
	 * A three-page PDF whose second page is landscape.
	 *
	 * @return string The bytes.
	 */
	private static function fixture(): string {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test', 'mode' => 'c']);
		$mpdf->WriteHTML('<p>Agenda item one</p>');
		$mpdf->AddPage('L');
		$mpdf->WriteHTML('<p>Landscape table</p>');
		$mpdf->AddPage('P');
		$mpdf->WriteHTML('<p>Annex</p>');

		return $mpdf->Output('', 'S');
	}//end fixture()

	/**
	 * Width and height of every page, in mm.
	 *
	 * @param string $pdf The bytes.
	 *
	 * @return array<int, array{0: int, 1: int}> Rounded sizes, per page.
	 */
	private static function pageSizes(string $pdf): array {
		$reader = new Fpdi();
		$count  = $reader->setSourceFile(StreamReader::createByString($pdf));
		$sizes  = [];
		for ($page = 1; $page <= $count; $page++) {
			$size    = $reader->getTemplateSize($reader->importPage($page));
			$sizes[] = [(int) round($size['width']), (int) round($size['height'])];
		}

		return $sizes;
	}//end pageSizes()

	/**
	 * How often a text is written as a footer line: every time it is written, less the rotated ones.
	 *
	 * @param string $pdf  The uncompressed bytes.
	 * @param string $text The text.
	 *
	 * @return int The count.
	 */
	private static function footerLines(string $pdf, string $text): int {
		return (preg_match_all('/\(' . preg_quote($text, '/') . '\) Tj/', $pdf) - self::acrossPage(pdf: $pdf, text: $text));
	}//end footerLines()

	/**
	 * How often a text is written across a page: under a rotation.
	 *
	 * @param string $pdf  The uncompressed bytes.
	 * @param string $text The text.
	 *
	 * @return int The count.
	 */
	private static function acrossPage(string $pdf, string $text): int {
		return preg_match_all('/ cm\nq [^\n]*\(' . preg_quote($text, '/') . '\) Tj/', $pdf);
	}//end acrossPage()

	public function testEveryPageIsStampedAndKeepsItsSizeAndOrientation(): void {
		$source = self::fixture();

		$stamped = $this->service()->stamp(pdf: $source, text: self::TEXT . "\nVertrouwelijk", options: ['placement' => 'both']);

		$this->assertSame([[210, 297], [297, 210], [210, 297]], self::pageSizes($stamped));
		$this->assertSame(3, self::footerLines($stamped, self::TEXT), 'the first footer line is on every page');
		$this->assertSame(3, self::footerLines($stamped, 'Vertrouwelijk'), 'the second footer line is on every page');
		$this->assertSame(3, self::acrossPage($stamped, self::TEXT . " \xB7 Vertrouwelijk"), 'the lines run across every page as one');
		$this->assertStringStartsWith('%PDF-', $source);
		$this->assertSame([[210, 297], [297, 210], [210, 297]], self::pageSizes($source), 'the input is not changed');
	}//end testEveryPageIsStampedAndKeepsItsSizeAndOrientation()

	public function testFooterOnlyWritesNoTextAcrossThePage(): void {
		$stamped = $this->service()->stamp(pdf: self::fixture(), text: self::TEXT, options: ['placement' => 'footer']);

		$this->assertSame(3, self::footerLines($stamped, self::TEXT));
		$this->assertSame(0, self::acrossPage($stamped, self::TEXT));
		$this->assertSame(3, count(self::pageSizes($stamped)));
	}//end testFooterOnlyWritesNoTextAcrossThePage()

	public function testDiagonalOnlyWritesNoFooter(): void {
		$stamped = $this->service()->stamp(pdf: self::fixture(), text: self::TEXT, options: ['placement' => 'diagonal']);

		$this->assertSame(0, self::footerLines($stamped, self::TEXT), 'no footer line');
		$this->assertSame(3, self::acrossPage($stamped, self::TEXT), 'the text across each page');
	}//end testDiagonalOnlyWritesNoFooter()

	public function testControlCharactersAreDroppedAndTheTextIsCutAt200Characters(): void {
		$stamped = $this->service()->stamp(pdf: self::fixture(), text: "Naam\x07" . str_repeat('x', 300), options: ['placement' => 'footer']);

		$this->assertSame(3, self::footerLines($stamped, 'Naam' . str_repeat('x', 196)));
	}//end testControlCharactersAreDroppedAndTheTextIsCutAt200Characters()

	/**
	 * Inputs refused before anything is imported, with their codes.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: int}>
	 */
	public static function refusals(): array {
		return [
			'not a pdf' => ['PK' . "\x03\x04" . 'docx bytes', self::TEXT, PdfStampRefusal::NOT_A_PDF, PdfStampService::DEFAULT_MAX_BYTES],
			'empty file' => ['', self::TEXT, PdfStampRefusal::NOT_A_PDF, PdfStampService::DEFAULT_MAX_BYTES],
			'too large' => ['%PDF-1.4' . str_repeat(' ', 100), self::TEXT, PdfStampRefusal::TOO_LARGE, 50],
			'no text' => ['%PDF-1.4 x', " \n\x07 ", PdfStampRefusal::FAILED, PdfStampService::DEFAULT_MAX_BYTES],
			'unreadable pdf' => ['%PDF-1.4 not really', self::TEXT, PdfStampRefusal::FAILED, PdfStampService::DEFAULT_MAX_BYTES],
		];
	}//end refusals()

	/**
	 * @dataProvider refusals
	 */
	public function testRefusals(string $pdf, string $text, string $code, int $limit): void {
		try {
			$this->service(limit: $limit)->stamp(pdf: $pdf, text: $text);
			$this->fail('The stamp was not refused.');
		} catch (PdfStampRefusal $refusal) {
			$this->assertSame($code, $refusal->getRefusalCode());
			$this->assertSame(422, $refusal->getCode());
		}
	}//end testRefusals()

	public function testAnEncryptedPdfIsRefused(): void {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test', 'mode' => 'c']);
		$mpdf->SetProtection(['print'], 'user', 'owner');
		$mpdf->WriteHTML('<p>Secret</p>');

		$this->expectException(PdfStampRefusal::class);
		$this->expectExceptionMessage('The PDF is encrypted.');
		$this->service()->stamp(pdf: $mpdf->Output('', 'S'), text: self::TEXT);
	}//end testAnEncryptedPdfIsRefused()

	public function testAnUnknownPlacementIsRefused(): void {
		$this->expectException(PdfStampRefusal::class);
		$this->expectExceptionMessage('Unknown placement: header');
		$this->service()->stamp(pdf: self::fixture(), text: self::TEXT, options: ['placement' => 'header']);
	}//end testAnUnknownPlacementIsRefused()

	public function testAZeroLimitTurnsTheSizeCheckOff(): void {
		$stamped = $this->service(limit: 0)->stamp(pdf: self::fixture(), text: self::TEXT, options: ['placement' => 'footer']);

		$this->assertSame(3, count(self::pageSizes($stamped)));
	}//end testAZeroLimitTurnsTheSizeCheckOff()
}//end class
