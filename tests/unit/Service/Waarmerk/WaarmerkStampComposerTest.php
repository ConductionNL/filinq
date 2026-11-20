<?php

/**
 * Unit tests for WaarmerkStampComposer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\Waarmerk\WaarmerkStampComposer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\Fpdi;

/**
 * The stamp page is appended as the final page, with a local QR.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkStampComposer
 * @uses   \OCA\Filinq\Service\PdfService
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkStampComposerTest extends TestCase {

	/**
	 * The page count of a PDF.
	 *
	 * @param string $pdf The bytes.
	 *
	 * @return int
	 */
	private function pages(string $pdf): int {
		$path = (string) tempnam(sys_get_temp_dir(), 'pages-');
		file_put_contents($path, $pdf);
		$count = (new Fpdi())->setSourceFile($path);
		unlink($path);
		return $count;
	}//end pages()

	/**
	 * A PDF service that renders whatever HTML the renderer returns.
	 *
	 * @param array<string, mixed>|null $seen Receives the template data.
	 *
	 * @return PdfService
	 */
	private function pdfService(?array &$seen = null): PdfService {
		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturnCallback(
			function (string $templateContent, array $data) use (&$seen): string {
				$seen = $data;
				return '<p>Waarmerk ' . htmlspecialchars((string) ($data['stamp']['verificationCode'] ?? 'source')) . '</p>';
			}
		);
		return new PdfService($this->createMock(LoggerInterface::class), $renderer);
	}//end pdfService()

	/**
	 * Two source pages plus one stamp page; the stamp carries the facts and a QR data URI.
	 *
	 * @return void
	 */
	public function testAppendsTheStampAsTheFinalPage(): void {
		$pdfService = $this->pdfService(seen: $seen);
		$source = $pdfService->generatePdfFromHtml(html: '<p>one</p><pagebreak /><p>two</p>');
		$this->assertSame(2, $this->pages($source));

		$sealed = (new WaarmerkStampComposer(pdfService: $pdfService))->compose(
			sourcePdf: $source,
			stamp: ['organisation' => 'Gemeente Demostad', 'documentName' => 'besluit.pdf', 'sealedAt' => '2026-11-14T10:00:00+00:00', 'verificationCode' => 'abc', 'verificationUrl' => 'https://example.test/verify/abc']
		);

		$this->assertStringStartsWith('%PDF-', $sealed);
		$this->assertSame(3, $this->pages($sealed));
		$this->assertSame('abc', $seen['stamp']['verificationCode']);
		$this->assertSame('Gemeente Demostad', $seen['stamp']['organisation']);
		$this->assertStringStartsWith('data:image/svg+xml;base64,', $seen['stamp']['qr']);
		$this->assertStringContainsString('<svg', (string) base64_decode(substr($seen['stamp']['qr'], 26)));
	}//end testAppendsTheStampAsTheFinalPage()

	/**
	 * Something that is not a PDF is refused with 422.
	 *
	 * @return void
	 */
	public function testOnlyAPdfIsSealed(): void {
		try {
			(new WaarmerkStampComposer(pdfService: $this->pdfService()))->compose(sourcePdf: 'PK zip bytes', stamp: []);
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(422, $e->getStatus());
		}
	}//end testOnlyAPdfIsSealed()

	/**
	 * A corrupt PDF that FPDI cannot read is a 422 too.
	 *
	 * @return void
	 */
	public function testACorruptPdfIsRefused(): void {
		$this->expectException(WaarmerkRefusedException::class);
		$this->expectExceptionMessage('could not be read');
		(new WaarmerkStampComposer(pdfService: $this->pdfService()))->compose(sourcePdf: "%PDF-1.4\ngarbage", stamp: ['verificationUrl' => 'x']);
	}//end testACorruptPdfIsRefused()
}//end class
