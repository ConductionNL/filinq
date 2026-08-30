<?php

/**
 * Unit tests for PrintController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-5.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\PrintController;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PrintController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PrintControllerTest extends TestCase {

	/**
	 * @var PrintController
	 */
	private PrintController $controller;

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $mockRequest;

	/**
	 * @var PdfService|MockObject
	 */
	private PdfService|MockObject $mockPdfService;

	/**
	 * @var TemplateService|MockObject
	 */
	private TemplateService|MockObject $mockTemplateService;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	protected function setUp(): void {
		parent::setUp();

		$this->mockRequest = $this->createMock(IRequest::class);
		$this->mockPdfService = $this->createMock(PdfService::class);
		$this->mockTemplateService = $this->createMock(TemplateService::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);

		$mockUser = $this->createMock(IUser::class);
		$mockUser->method('getUID')->willReturn('testuser');
		$this->mockUserSession->method('getUser')->willReturn($mockUser);

		$this->controller = new PrintController(
			appName: 'filinq',
			request: $this->mockRequest,
			logger: $this->mockLogger,
			pdfService: $this->mockPdfService,
			templateService: $this->mockTemplateService,
			userSession: $this->mockUserSession,
		);

	}//end setUp()

	/**
	 * Test preview returns 401 when no authenticated user.
	 *
	 * @return void
	 */
	public function testPreviewReturns401WhenNoUser(): void {
		$mockRequest = $this->createMock(IRequest::class);
		$mockSession = $this->createMock(IUserSession::class);
		$mockSession->method('getUser')->willReturn(null);

		$controller = new PrintController(
			appName: 'filinq',
			request: $mockRequest,
			logger: $this->mockLogger,
			pdfService: $this->mockPdfService,
			templateService: $this->mockTemplateService,
			userSession: $mockSession,
		);

		$result = $controller->preview();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertSame(401, $result->getStatus());

	}//end testPreviewReturns401WhenNoUser()

	/**
	 * Test preview returns 400 when neither templateId nor template provided.
	 *
	 * @return void
	 */
	public function testPreviewReturns400WhenNoTemplateProvided(): void {
		$this->mockRequest->method('getParam')->willReturn(null);

		$result = $this->controller->preview();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertSame(400, $result->getStatus());

	}//end testPreviewReturns400WhenNoTemplateProvided()

	/**
	 * Test preview returns rendered HTML on success with inline template.
	 *
	 * @return void
	 */
	public function testPreviewReturnsHtmlOnSuccess(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['templateId', null, null],
					['template', null, '<h1>Hello</h1>'],
					['data', [], []],
					['options', [], []],
					['filename', 'document.pdf', 'document.pdf'],
				]
			);

		$this->mockPdfService->method('renderHtmlPreview')->willReturn('<html><h1>Hello</h1></html>');

		$result = $this->controller->preview();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertSame(200, $result->getStatus());
		$data = $result->getData();
		$this->assertArrayHasKey('html', $data);

	}//end testPreviewReturnsHtmlOnSuccess()

	/**
	 * Test downloadPdfA returns 401 when no authenticated user.
	 *
	 * @return void
	 */
	public function testDownloadPdfAReturns401WhenNoUser(): void {
		$mockRequest = $this->createMock(IRequest::class);
		$mockSession = $this->createMock(IUserSession::class);
		$mockSession->method('getUser')->willReturn(null);

		$controller = new PrintController(
			appName: 'filinq',
			request: $mockRequest,
			logger: $this->mockLogger,
			pdfService: $this->mockPdfService,
			templateService: $this->mockTemplateService,
			userSession: $mockSession,
		);

		$result = $controller->downloadPdfA();

		$this->assertSame(401, $result->getStatus());

	}//end testDownloadPdfAReturns401WhenNoUser()

	/**
	 * Test preview returns 500 on exception from PdfService.
	 *
	 * @return void
	 */
	public function testPreviewReturns500OnException(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['templateId', null, null],
					['template', null, '<h1>Bad</h1>'],
					['data', [], []],
					['options', [], []],
				]
			);

		$this->mockPdfService->method('renderHtmlPreview')
			->willThrowException(new Exception('Render error'));

		$result = $this->controller->preview();

		$this->assertSame(500, $result->getStatus());

	}//end testPreviewReturns500OnException()
}//end class
