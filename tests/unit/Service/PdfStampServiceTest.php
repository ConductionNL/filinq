<?php

/**
 * PdfStampService tests: a text on every page, and the three refusals.
 *
 * The fixture is a real three-page PDF built with mPDF, its second page
 * landscape, and the result is read back with FPDI's own reader: page count,
 * page size and the text in each page's content stream. Nothing in the
 * stamping path is doubled.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Exception\PdfStampRefusedException;
use OCA\Filinq\Service\ChartSvgRenderer;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PdfStampService;
use OCA\Filinq\Service\TableHtmlRenderer;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

/**
 * Tests for PdfStampService.
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
 */
class PdfStampServiceTest extends TestCase {

	private const STAMP = "J. de Vries, 28-09-2026 14:05\nVertrouwelijk";

	/**
	 * App config overrides by key.
	 *
	 * @var array<string, string>
	 */
	private array $config = [];

	private PdfStampService $service;

	/**
	 * Build the service on the real PdfService.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$logger = $this->createMock(LoggerInterface::class);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => $this->config[$key] ?? $default
		);

		$this->service = new PdfStampService(
			new PdfService($logger, new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer())),
			$appConfig,
			$logger
		);

	}//end setUp()

	/**
	 * Three pages in, three pages out, the landscape page kept, the text on each.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
	 */
	public function testAThreePagePaperIsStampedOnEveryPage(): void {
		$source = $this->threePageFixture();

		$stamped = $this->service->stamp($source, self::STAMP, ['placement' => 'both']);

		$reader = new PdfReader(new PdfParser(StreamReader::createByString($stamped)));
		$this->assertSame(3, $reader->getPageCount());

		for ($page = 1; $page <= 3; $page++) {
			[$width, $height] = $this->pageSize($reader, $page);
			if ($page === 2) {
				$this->assertGreaterThan($height, $width, 'page 2 must stay landscape');
			} else {
				$this->assertGreaterThan($width, $height, "page $page must stay portrait");
			}

			$text = $this->pageText($reader, $page);
			$this->assertStringContainsString('Vertrouwelijk', $text, "page $page carries the stamp");
			$this->assertStringContainsString('J. de Vries', $text, "page $page carries the name");
		}

		$this->assertNotSame($source, $stamped);

	}//end testAThreePagePaperIsStampedOnEveryPage()

	/**
	 * The foot placement alone writes each line at the foot of every page.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
	 */
	public function testTheFooterPlacementStampsEveryPage(): void {
		$stamped = $this->service->stamp($this->threePageFixture(), self::STAMP, ['placement' => 'footer']);

		$reader = new PdfReader(new PdfParser(StreamReader::createByString($stamped)));
		for ($page = 1; $page <= 3; $page++) {
			$this->assertStringContainsString('Vertrouwelijk', $this->pageText($reader, $page));
		}

	}//end testTheFooterPlacementStampsEveryPage()

	/**
	 * Something that is not a PDF is refused with `not-a-pdf`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-2
	 */
	public function testANonPdfIsRefused(): void {
		$this->assertRefusal('not-a-pdf', 'PK' . str_repeat("\0", 64));

	}//end testANonPdfIsRefused()

	/**
	 * An encrypted PDF is refused with `encrypted`, never served unstamped.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-2
	 */
	public function testAnEncryptedPdfIsRefused(): void {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test']);
		$mpdf->SetProtection(['print'], '', 'owner-secret');
		$mpdf->WriteHTML('<p>Besloten stuk</p>');

		$this->assertRefusal('encrypted', $mpdf->Output('', 'S'));

	}//end testAnEncryptedPdfIsRefused()

	/**
	 * A file above the configured limit is refused with `too-large`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-2
	 */
	public function testAFileAboveTheLimitIsRefused(): void {
		$this->config[PdfStampService::MAX_BYTES_KEY] = '100';

		$this->assertRefusal('too-large', $this->threePageFixture());

	}//end testAFileAboveTheLimitIsRefused()

	/**
	 * Text over 200 characters, or an unknown placement, is refused before any work.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
	 */
	public function testAnOverlongTextOrUnknownPlacementIsRefused(): void {
		try {
			$this->service->stamp($this->threePageFixture(), str_repeat('x', 201));
			$this->fail('a 201-character text must be refused');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('200', $e->getMessage());
		}

		$this->expectException(\InvalidArgumentException::class);
		$this->service->stamp($this->threePageFixture(), 'Vertrouwelijk', ['placement' => 'header']);

	}//end testAnOverlongTextOrUnknownPlacementIsRefused()

	/**
	 * Assert that stamping the given bytes is refused with the given code.
	 *
	 * @param string $code The expected refusal code.
	 * @param string $pdf  The input bytes.
	 *
	 * @return void
	 */
	private function assertRefusal(string $code, string $pdf): void {
		try {
			$this->service->stamp($pdf, 'Vertrouwelijk');
			$this->fail("expected refusal $code");
		} catch (PdfStampRefusedException $e) {
			$this->assertSame($code, $e->getRefusalCode());
			$this->assertNotSame('', $e->getMessage());
		}

	}//end assertRefusal()

	/**
	 * A three-page PDF whose second page is landscape.
	 *
	 * @return string The PDF bytes.
	 */
	private function threePageFixture(): string {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test']);
		$mpdf->WriteHTML('<p>Pagina een</p>');
		$mpdf->AddPage('L');
		$mpdf->WriteHTML('<p>Pagina twee</p>');
		$mpdf->AddPage('P');
		$mpdf->WriteHTML('<p>Pagina drie</p>');

		return $mpdf->Output('', 'S');

	}//end threePageFixture()

	/**
	 * The width and height of one page.
	 *
	 * @param PdfReader $reader The reader.
	 * @param int       $page   The page number.
	 *
	 * @return array{0: float, 1: float} Width and height.
	 */
	private function pageSize(PdfReader $reader, int $page): array {
		$box = $reader->getPage($page)->getBoundary();

		return [$box->getWidth(), $box->getHeight()];

	}//end pageSize()

	/**
	 * The text shown on one page, decoded from its content stream.
	 *
	 * mPDF writes text in Unicode fonts as UTF-16BE strings; every literal and
	 * hex string on the page is decoded and joined, so text split by kerning
	 * still reads as one.
	 *
	 * @param PdfReader $reader The reader.
	 * @param int       $page   The page number.
	 *
	 * @return string The decoded text.
	 */
	private function pageText(PdfReader $reader, int $page): string {
		$content = $reader->getPage($page)->getContentStream();
		$parts = [];

		preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $content, $literals);
		foreach ($literals[0] as $literal) {
			$parts[] = $this->decodeString(
				stripcslashes(substr($literal, 1, -1))
			);
		}

		preg_match_all('/<([0-9A-Fa-f\s]+)>/', $content, $hexes);
		foreach ($hexes[1] as $hex) {
			$parts[] = $this->decodeString((string)hex2bin(preg_replace('/\s+/', '', $hex)));
		}

		return implode('', $parts);

	}//end pageText()

	/**
	 * Decode one PDF string, as UTF-16BE when it looks like it.
	 *
	 * @param string $bytes The raw string bytes.
	 *
	 * @return string UTF-8 text.
	 */
	private function decodeString(string $bytes): string {
		if (strlen($bytes) >= 2 && strlen($bytes) % 2 === 0 && $bytes[0] === "\0") {
			return (string)mb_convert_encoding($bytes, 'UTF-8', 'UTF-16BE');
		}

		return $bytes;

	}//end decodeString()
}//end class
