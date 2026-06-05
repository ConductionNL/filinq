<?php

/**
 * Unit tests for PrintController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
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

use Exception;
use OCA\DocuDesk\Controller\PrintController;
use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Service\TemplateService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
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
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PrintControllerTest extends TestCase
{

    /**
     * The controller under test.
     *
     * @var PrintController
     */
    private PrintController $controller;

    /**
     * Mock request.
     *
     * @var IRequest&MockObject
     */
    private IRequest $mockRequest;

    /**
     * Mock logger.
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $mockLogger;

    /**
     * Mock PDF service.
     *
     * @var PdfService&MockObject
     */
    private PdfService $mockPdfService;

    /**
     * Mock template service.
     *
     * @var TemplateService&MockObject
     */
    private TemplateService $mockTemplateSvc;

    /**
     * Mock user session.
     *
     * @var IUserSession&MockObject
     */
    private IUserSession $mockUserSession;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(originalClassName: IRequest::class);
        $this->mockLogger      = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockPdfService  = $this->createMock(originalClassName: PdfService::class);
        $this->mockTemplateSvc = $this->createMock(originalClassName: TemplateService::class);
        $this->mockUserSession = $this->createMock(originalClassName: IUserSession::class);

        $mockUser = $this->createMock(originalClassName: IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->controller = new PrintController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->mockLogger,
            pdfService: $this->mockPdfService,
            templateService: $this->mockTemplateSvc,
            userSession: $this->mockUserSession
        );

    }//end setUp()


    /**
     * Test preview returns 400 when neither templateId nor template provided
     *
     * @return void
     */
    public function testPreviewReturns400WhenNoTemplateProvided(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, null],
                    ['template', null, null],
                    ['data', [], []],
                    ['options', [], []],
                ]
            );

        $result = $this->controller->preview();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testPreviewReturns400WhenNoTemplateProvided()


    /**
     * Test preview returns HTML and printConfig on success with inline template
     *
     * @return void
     */
    public function testPreviewReturnsHtmlAndPrintConfigOnSuccess(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, null],
                    ['template', null, '<h1>Test</h1>'],
                    ['data', [], []],
                    ['options', [], ['duplex' => true, 'color' => false]],
                ]
            );

        $this->mockPdfService->method('renderHtmlPreview')
            ->willReturn('<style>/* print css */</style><h1>Test</h1>');

        $result = $this->controller->preview();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

        $data = $result->getData();
        $this->assertArrayHasKey('html', $data);
        $this->assertArrayHasKey('printConfig', $data);
        $this->assertTrue($data['printConfig']['duplex']);
        $this->assertFalse($data['printConfig']['color']);

    }//end testPreviewReturnsHtmlAndPrintConfigOnSuccess()


    /**
     * Test preview returns 500 on PDF service exception
     *
     * @return void
     */
    public function testPreviewReturnsErrorOnException(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, null],
                    ['template', null, '<h1>Test</h1>'],
                    ['data', [], []],
                    ['options', [], []],
                ]
            );

        $this->mockPdfService->method('renderHtmlPreview')
            ->willThrowException(new Exception('Render failed', 500));

        $result = $this->controller->preview();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(500, $result->getStatus());

    }//end testPreviewReturnsErrorOnException()


    /**
     * Test downloadPdfA returns PDF binary on success
     *
     * @return void
     */
    public function testDownloadPdfAReturnsPdfOnSuccess(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, null],
                    ['template', null, '<h1>Archival</h1>'],
                    ['data', [], []],
                    ['filename', 'document.pdf', 'archived.pdf'],
                    ['options', [], []],
                ]
            );

        $this->mockPdfService->method('renderPdf')
            ->willReturn('%PDF-1.4 archival content');

        $result = $this->controller->downloadPdfA();

        $this->assertInstanceOf(DataDownloadResponse::class, $result);

    }//end testDownloadPdfAReturnsPdfOnSuccess()


    /**
     * Test preview returns printConfig with template defaults from TemplateService
     *
     * @return void
     */
    public function testPreviewReturnsPrintConfigFromTemplate(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'template-uuid'],
                    ['template', null, null],
                    ['data', [], []],
                    ['options', [], []],
                ]
            );

        $this->mockTemplateSvc->method('getTemplate')
            ->willReturn(
                [
                    'content'     => '<h1>Template</h1>',
                    'name'        => 'Test Template',
                    'format'      => 'A4',
                    'orientation' => 'P',
                    'duplex'      => true,
                    'color'       => true,
                    'paperTray'   => 'tray-2',
                    'stapling'    => false,
                ]
            );

        $this->mockPdfService->method('renderHtmlPreview')
            ->willReturn('<h1>Template</h1>');

        $result = $this->controller->preview();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('printConfig', $data);
        $this->assertTrue($data['printConfig']['duplex']);
        $this->assertEquals('tray-2', $data['printConfig']['paperTray']);

    }//end testPreviewReturnsPrintConfigFromTemplate()
}//end class
