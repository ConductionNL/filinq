<?php

/**
 * TcLibPdfBackend unit tests
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\TcLibPdfBackend;
use OCA\Filinq\Service\PdfService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for {@see TcLibPdfBackend}.
 */
class TcLibPdfBackendTest extends TestCase {

	/**
	 * Build the backend with the given value for its app config key.
	 *
	 * @param string $flag Value returned for the enabled key.
	 *
	 * @return TcLibPdfBackend
	 */
	private function backend(string $flag): TcLibPdfBackend {
		$appConfig = $this->createMock(originalClassName: IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => ($key === TcLibPdfBackend::ENABLED_KEY) ? $flag : $default
		);
		$pdfService = $this->createMock(originalClassName: PdfService::class);
		$pdfService->expects($this->never())->method('generatePdfFromHtml');

		return new TcLibPdfBackend(
			pdfService: $pdfService,
			appConfig: $appConfig,
			logger: $this->createMock(originalClassName: LoggerInterface::class),
		);
	}//end backend()

	/**
	 * Off unless the tenant enables it.
	 *
	 * @return void
	 */
	public function testIsOffByDefaultAndOnWhenEnabled(): void {
		$this->assertSame('tc-lib-pdf', $this->backend(flag: 'false')->name());
		$this->assertFalse($this->backend(flag: 'false')->isAvailable());
		$this->assertTrue($this->backend(flag: 'true')->isAvailable());
	}//end testIsOffByDefaultAndOnWhenEnabled()

	/**
	 * HTML converts to a PDF/A-3b file beside the source, without mPDF.
	 *
	 * @return void
	 */
	public function testConvertWritesPdfa3bBesideSource(): void {
		$written = null;
		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('nodeExists')->willReturn(false);
		$folder->expects($this->once())->method('newFile')->willReturnCallback(
			function (string $name, string $content) use (&$written): File {
				$this->assertSame('besluit.pdf', $name);
				$written = $content;
				return $this->createMock(originalClassName: File::class);
			}
		);

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('besluit.html');
		$source->method('getContent')->willReturn('<h1>Besluit</h1><p>Zoë, € 1.250, <b>vet</b></p>');
		$source->method('getParent')->willReturn($folder);

		$this->backend(flag: 'true')->convert(source: $source);

		$this->assertIsString($written);
		$this->assertStringStartsWith('%PDF-', $written);
		$this->assertMatchesRegularExpression('#<pdfaid:part>3</pdfaid:part>#', $written);
		$this->assertMatchesRegularExpression('#<pdfaid:conformance>B</pdfaid:conformance>#', $written);
		$this->assertStringContainsString('/OutputIntents', $written);
		$this->assertStringContainsString('/FontFile2', $written);
	}//end testConvertWritesPdfa3bBesideSource()
}//end class
