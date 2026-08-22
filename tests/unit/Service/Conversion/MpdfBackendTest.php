<?php

/**
 * Unit tests for MpdfBackend
 *
 * Covers MIME/extension routing, tenant-flag gating, and the HTML/TXT
 * rendering paths (PdfService is mocked so mPDF itself is not invoked).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-11
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\MpdfBackend;
use OCA\Filinq\Service\PdfService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for MpdfBackend
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MpdfBackendTest extends TestCase {

	/**
	 * App config mock.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $appConfig;

	/**
	 * PdfService mock.
	 *
	 * @var PdfService|MockObject
	 */
	private PdfService|MockObject $pdfService;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * Backend under test.
	 *
	 * @var MpdfBackend
	 */
	private MpdfBackend $backend;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(originalClassName: IAppConfig::class);
		$this->pdfService = $this->createMock(originalClassName: PdfService::class);
		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);

		$this->backend = new MpdfBackend(
			pdfService: $this->pdfService,
			appConfig: $this->appConfig,
			logger: $this->logger,
		);

	}//end setUp()

	/**
	 * Test that name() returns the stable identifier 'mpdf'
	 *
	 * @return void
	 */
	public function testNameReturnsMpdf(): void {
		$this->assertSame(expected: 'mpdf', actual: $this->backend->name());

	}//end testNameReturnsMpdf()

	/**
	 * Test that isAvailable() returns true when the tenant flag is not 'false'
	 *
	 * @return void
	 */
	public function testIsAvailableWhenFlagNotFalse(): void {
		$this->appConfig->method('getValueString')->willReturn('true');
		$this->assertTrue(condition: $this->backend->isAvailable());

	}//end testIsAvailableWhenFlagNotFalse()

	/**
	 * Test that isAvailable() returns false when tenant flag is 'false'
	 *
	 * @return void
	 */
	public function testIsUnavailableWhenFlagFalse(): void {
		$this->appConfig->method('getValueString')->willReturn('false');
		$this->assertFalse(condition: $this->backend->isAvailable());

	}//end testIsUnavailableWhenFlagFalse()

	/**
	 * Test that canHandle() returns true for text/html MIME
	 *
	 * @return void
	 */
	public function testCanHandleHtmlMime(): void {
		$this->assertTrue(condition: $this->backend->canHandle(mimeType: 'text/html', extension: 'html'));

	}//end testCanHandleHtmlMime()

	/**
	 * Test that canHandle() returns true for text/plain MIME
	 *
	 * @return void
	 */
	public function testCanHandlePlainTextMime(): void {
		$this->assertTrue(condition: $this->backend->canHandle(mimeType: 'text/plain', extension: 'txt'));

	}//end testCanHandlePlainTextMime()

	/**
	 * Test that canHandle() returns true for .md extension
	 *
	 * @return void
	 */
	public function testCanHandleMarkdownExtension(): void {
		$this->assertTrue(condition: $this->backend->canHandle(mimeType: 'text/markdown', extension: 'md'));

	}//end testCanHandleMarkdownExtension()

	/**
	 * Test that canHandle() returns false for DOCX MIME
	 *
	 * @return void
	 */
	public function testCannotHandleDocxMime(): void {
		$this->assertFalse(
			condition: $this->backend->canHandle(
				mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				extension: 'docx'
			)
		);

	}//end testCannotHandleDocxMime()

	/**
	 * Test that canHandle() returns true for xhtml+xml MIME
	 *
	 * @return void
	 */
	public function testCanHandleXhtmlMime(): void {
		$this->assertTrue(
			condition: $this->backend->canHandle(mimeType: 'application/xhtml+xml', extension: 'xhtml')
		);

	}//end testCanHandleXhtmlMime()

	/**
	 * Test that convert() for an HTML file calls PdfService and returns the new PDF node
	 *
	 * @return void
	 */
	public function testConvertHtmlReturnsPdfFile(): void {
		$htmlContent = '<html><body>Hello</body></html>';
		$pdfBytes = '%PDF-1.4 fake';

		$folder = $this->createMock(originalClassName: Folder::class);
		$outputFile = $this->createMock(originalClassName: File::class);
		$folder->method('nodeExists')->willReturn(false);
		$folder->method('newFile')->willReturn($outputFile);

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('page.html');
		$source->method('getContent')->willReturn($htmlContent);
		$source->method('getParent')->willReturn($folder);

		$this->pdfService->method('generatePdfFromHtml')->willReturn($pdfBytes);

		$result = $this->backend->convert(source: $source);
		$this->assertSame(expected: $outputFile, actual: $result);

	}//end testConvertHtmlReturnsPdfFile()

	/**
	 * Test that convert() for a TXT file wraps the content in pre tag before calling PdfService
	 *
	 * @return void
	 */
	public function testConvertTxtWrapsInPreTag(): void {
		$textContent = 'Hello world';
		$pdfBytes = '%PDF-1.4 fake';

		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('nodeExists')->willReturn(false);
		$folder->method('newFile')->willReturn($this->createMock(originalClassName: File::class));

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('note.txt');
		$source->method('getContent')->willReturn($textContent);
		$source->method('getParent')->willReturn($folder);

		$capturedHtml = null;
		$this->pdfService
			->method('generatePdfFromHtml')
			->willReturnCallback(
				function (string $html) use ($pdfBytes, &$capturedHtml): string {
					$capturedHtml = $html;
					return $pdfBytes;
				}
			);

		$this->backend->convert(source: $source);

		$this->assertStringContainsString(needle: '<pre', haystack: (string)$capturedHtml);
		$this->assertStringContainsString(needle: 'Hello world', haystack: (string)$capturedHtml);

	}//end testConvertTxtWrapsInPreTag()

	/**
	 * Test that convert() deletes an existing output file before writing the new one
	 *
	 * @return void
	 */
	public function testConvertDeletesExistingOutputFile(): void {
		$pdfBytes = '%PDF-1.4 fake';
		$existingPdf = $this->createMock(originalClassName: File::class);
		$existingPdf->expects($this->once())->method('delete');

		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('nodeExists')->willReturn(true);
		$folder->method('get')->willReturn($existingPdf);
		$folder->method('newFile')->willReturn($this->createMock(originalClassName: File::class));

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('page.html');
		$source->method('getContent')->willReturn('<p>hi</p>');
		$source->method('getParent')->willReturn($folder);

		$this->pdfService->method('generatePdfFromHtml')->willReturn($pdfBytes);

		$this->backend->convert(source: $source);

	}//end testConvertDeletesExistingOutputFile()

	/**
	 * Test that convert() throws ConversionFailedException when PdfService throws
	 *
	 * @return void
	 */
	public function testConvertThrowsWhenPdfServiceFails(): void {
		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('page.html');
		$source->method('getContent')->willReturn('<p>hi</p>');
		$source->method('getPath')->willReturn('/u/admin/page.html');

		$this->pdfService
			->method('generatePdfFromHtml')
			->willThrowException(new RuntimeException('mPDF OOM'));

		$this->expectException(exception: ConversionFailedException::class);
		$this->expectExceptionMessageMatches(regularExpression: '/mPDF/');

		$this->backend->convert(source: $source);

	}//end testConvertThrowsWhenPdfServiceFails()
}//end class
