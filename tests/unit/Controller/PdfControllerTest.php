<?php

/**
 * Unit tests for PdfController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\PdfController;
use OCA\DocuDesk\Service\Pdfa3ConversionService;
use OCA\DocuDesk\Service\PdfService;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PdfController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PdfControllerTest extends TestCase {

	/**
	 * @var PdfController
	 */
	private PdfController $controller;

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $mockRequest;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var PdfService|MockObject
	 */
	private PdfService|MockObject $mockPdfService;

	/**
	 * @var Pdfa3ConversionService|MockObject
	 */
	private Pdfa3ConversionService|MockObject $mockPdfa3Service;

	/**
	 * @var IL10N|MockObject
	 */
	private IL10N|MockObject $mockL10n;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRequest = $this->createMock(IRequest::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockPdfService = $this->createMock(PdfService::class);
		$this->mockPdfa3Service = $this->createMock(Pdfa3ConversionService::class);
		$this->mockL10n = $this->createMock(IL10N::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockL10n->method('t')->willReturnCallback(
			function ($text, $params = []) {
				return vsprintf($text, $params);
			}
		);

		$mockUser = $this->createMock(\OCP\IUser::class);
		$mockUser->method('getUID')->willReturn('testuser');
		$this->mockUserSession->method('getUser')->willReturn($mockUser);

		$this->controller = new PdfController(
			'docudesk',
			$this->mockRequest,
			$this->mockLogger,
			$this->mockPdfService,
			$this->mockPdfa3Service,
			$this->mockL10n,
			$this->mockUserSession
		);

	}//end setUp()

	/**
	 * Test render returns 400 when template empty
	 *
	 * @return void
	 */
	public function testRenderReturns400WhenTemplateEmpty(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['template', null, ''],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'document.pdf'],
				]
			);

		$result = $this->controller->render();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(400, $result->getStatus());

	}//end testRenderReturns400WhenTemplateEmpty()

	/**
	 * Test render returns PDF on success
	 *
	 * @return void
	 */
	public function testRenderReturnsPdfOnSuccess(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['template', null, '<h1>Test</h1>'],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'output.pdf'],
				]
			);

		$this->mockPdfService->method('renderPdf')
			->willReturn('%PDF-1.4 fake content');

		$result = $this->controller->render();

		$this->assertInstanceOf(DataDownloadResponse::class, $result);

	}//end testRenderReturnsPdfOnSuccess()

	/**
	 * Test render returns error on exception
	 *
	 * @return void
	 */
	public function testRenderReturnsErrorOnException(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['template', null, '<h1>Test</h1>'],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'output.pdf'],
				]
			);

		$this->mockPdfService->method('renderPdf')
			->willThrowException(new \Exception('PDF error', 500));

		$result = $this->controller->render();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(500, $result->getStatus());

	}//end testRenderReturnsErrorOnException()

	/**
	 * Test renderPdfA takes the plain PdfService path when no metadata or
	 * attachments are supplied (backward compatible with existing callers).
	 *
	 * @return void
	 */
	public function testRenderPdfAUsesPlainPathWithoutArchivalParams(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['template', null, '<h1>Test</h1>'],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'output.pdf'],
					['metadata', [], []],
					['attachments', [], []],
				]
			);

		$this->mockPdfService->method('renderPdf')
			->willReturn('%PDF-1.4 fake content');
		$this->mockPdfa3Service->expects($this->never())->method('convertHtml');

		$result = $this->controller->renderPdfA();

		$this->assertInstanceOf(DataDownloadResponse::class, $result);

	}//end testRenderPdfAUsesPlainPathWithoutArchivalParams()

	/**
	 * Test renderPdfA delegates to Pdfa3ConversionService when metadata is
	 * present, and surfaces checksum/pages/conformance as response headers.
	 *
	 * @return void
	 */
	public function testRenderPdfADelegatesToPdfa3ServiceWithMetadata(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['template', null, '<h1>Test</h1>'],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'output.pdf'],
					['metadata', [], ['identifier' => 'ZAAK-123']],
					['attachments', [], []],
				]
			);

		$this->mockPdfService->method('renderTemplateToHtml')->willReturn('<h1>Test</h1>');
		$this->mockPdfa3Service->method('convertHtml')->willReturn(
			[
				'content' => '%PDF-1.7 fake pdfa3 content',
				'checksumSha256' => str_repeat('a', 64),
				'pages' => 1,
				'conformance' => '3-B',
			]
		);

		$result = $this->controller->renderPdfA();

		$this->assertInstanceOf(DataDownloadResponse::class, $result);
		$this->assertEquals('3-B', $result->getHeaders()['X-Docudesk-Pdfa3-Conformance']);

	}//end testRenderPdfADelegatesToPdfa3ServiceWithMetadata()

	/**
	 * Test renderPdfA maps a Pdfa3ConversionException to its declared HTTP
	 * status and surfaces the reason + adminHint fields.
	 *
	 * @return void
	 */
	public function testRenderPdfAMapsPdfa3ExceptionToTypedErrorResponse(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['template', null, '<h1>Test</h1>'],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'output.pdf'],
					['metadata', [], ['identifier' => 'ZAAK-123']],
					['attachments', [], []],
				]
			);

		$this->mockPdfService->method('renderTemplateToHtml')->willReturn('<h1>Test</h1>');
		$this->mockPdfa3Service->method('convertHtml')->willThrowException(
			new \OCA\DocuDesk\Exception\Pdfa3ConversionException(
				reason: \OCA\DocuDesk\Exception\Pdfa3ConversionException::REASON_CONVERTER_UNAVAILABLE,
				message: 'PDF/A-3 conversion is disabled on this instance.',
				adminHint: 'Enable it in app config.',
				code: 503
			)
		);

		$result = $this->controller->renderPdfA();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(503, $result->getStatus());
		$this->assertEquals('converter_unavailable', $result->getData()['reason']);

	}//end testRenderPdfAMapsPdfa3ExceptionToTypedErrorResponse()
}//end class
