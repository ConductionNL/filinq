<?php

/**
 * Unit tests for VerificationQrStamp.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-signature-verification-portal/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PdfStreamReaderFactory;
use OCA\Filinq\Service\Qr\QrCodeEncoder;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\VerificationQrStamp;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The stamp keeps every page and draws the QR code on the last one.
 *
 * @covers \OCA\Filinq\Service\VerificationQrStamp
 * @uses \OCA\Filinq\Service\PdfService
 * @uses \OCA\Filinq\Service\PdfStreamReaderFactory
 * @uses \OCA\Filinq\Service\Qr\QrCodeEncoder
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class VerificationQrStampTest extends TestCase {

	/**
	 * The link stamped throughout.
	 *
	 * @var string
	 */
	private const URL = 'https://cloud.example.nl/apps/filinq/verify/0123456789abcdef0123456789abcdef';

	/**
	 * The stamp over the real PDF service and encoder.
	 *
	 * @return VerificationQrStamp
	 */
	private function stamp(): VerificationQrStamp {
		return new VerificationQrStamp(
			pdfService: new PdfService(logger: new NullLogger(), templateRenderer: $this->createMock(TemplateRenderer::class)),
			streamReaders: new PdfStreamReaderFactory(),
			encoder: new QrCodeEncoder()
		);
	}//end stamp()

	/**
	 * A PDF of a number of pages.
	 *
	 * @param int $pages The page count.
	 *
	 * @return string The PDF bytes.
	 */
	private static function pdf(int $pages): string {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf']);
		$mpdf->WriteHTML(implode('<pagebreak />', array_map(static fn (int $page): string => '<p>Page ' . $page . '</p>', range(1, $pages))));

		return $mpdf->Output('', 'S');
	}//end pdf()

	/**
	 * How many filled rectangles each content stream of a PDF draws.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return array<int, int> The count of filled rectangles ("re" then "f") per stream.
	 */
	private static function rectanglesPerPage(string $pdf): array {
		$counts = [];
		preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
		foreach ($streams[1] as $stream) {
			$content = @gzuncompress($stream);
			if ($content === false) {
				$content = $stream;
			}

			$counts[] = preg_match_all('/ re\s+f/', $content);
		}

		return $counts;
	}//end rectanglesPerPage()

	/**
	 * Every page is kept and the last one carries one rectangle per dark
	 * module plus the light background.
	 *
	 * @return void
	 */
	public function testThePagesAreKeptAndTheLastCarriesTheCode(): void {
		$source = self::pdf(pages: 2);
		$dark = array_sum(array_map(static fn (array $row): int => count(array_filter($row)), (new QrCodeEncoder())->encode(text: self::URL)));

		$stamped = $this->stamp()->stamp(pdf: $source, url: self::URL, caption: 'Verify this document');

		$reader = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf']);
		$this->assertSame(2, $reader->setSourceFile((new PdfStreamReaderFactory())->fromString(content: $stamped)));
		$this->assertContains(($dark + 1), self::rectanglesPerPage(pdf: $stamped), 'the QR code is drawn as its dark modules on a light square');
		$this->assertNotContains(($dark + 1), self::rectanglesPerPage(pdf: $source));

	}//end testThePagesAreKeptAndTheLastCarriesTheCode()

	/**
	 * Only an unencrypted PDF can be stamped.
	 *
	 * @return void
	 */
	public function testOnlyAnUnencryptedPdfCanBeStamped(): void {
		$stamp = $this->stamp();

		$this->assertTrue($stamp->canStamp(content: self::pdf(pages: 1)));
		$this->assertFalse($stamp->canStamp(content: 'PK zip bytes'));
		$this->assertFalse($stamp->canStamp(content: "%PDF-1.7\n1 0 obj << /Encrypt 5 0 R >> endobj"));

	}//end testOnlyAnUnencryptedPdfCanBeStamped()

	/**
	 * Bytes that are not a PDF are refused, not passed to mPDF.
	 *
	 * @return void
	 */
	public function testANonPdfIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Only an unencrypted PDF can carry the verification QR code');

		$this->stamp()->stamp(pdf: 'Hello', url: self::URL, caption: 'Verify this document');

	}//end testANonPdfIsRefused()

	/**
	 * A PDF mPDF cannot read is refused with the reason.
	 *
	 * @return void
	 */
	public function testAnUnreadablePdfIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The verification QR code could not be stamped: ');

		$this->stamp()->stamp(pdf: "%PDF-1.7\nnot really a pdf", url: self::URL, caption: 'Verify this document');

	}//end testAnUnreadablePdfIsRefused()
}//end class
