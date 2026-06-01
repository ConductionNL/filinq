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
class PdfControllerTest extends TestCase
{

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
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(IRequest::class);
        $this->mockLogger      = $this->createMock(LoggerInterface::class);
        $this->mockPdfService  = $this->createMock(PdfService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);
        $this->mockUserSession = $this->createMock(IUserSession::class);
        $this->mockL10n->method('t')->willReturnCallback(function ($text, $params = []) {
            return vsprintf($text, $params);
        });

        $mockUser = $this->createMock(\OCP\IUser::class);
        $mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->controller = new PdfController(
            'docudesk',
            $this->mockRequest,
            $this->mockLogger,
            $this->mockPdfService,
            $this->mockL10n,
            $this->mockUserSession
        );

    }//end setUp()


    /**
     * Test render returns 400 when template empty
     *
     * @return void
     */
    public function testRenderReturns400WhenTemplateEmpty(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap([
                ['template', null, ''],
                ['data', [], []],
                ['options', [], []],
                ['filename', 'document.pdf', 'document.pdf'],
            ]);

        $result = $this->controller->render();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testRenderReturns400WhenTemplateEmpty()


    /**
     * Test render returns PDF on success
     *
     * @return void
     */
    public function testRenderReturnsPdfOnSuccess(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap([
                ['template', null, '<h1>Test</h1>'],
                ['data', [], []],
                ['options', [], []],
                ['filename', 'document.pdf', 'output.pdf'],
            ]);

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
    public function testRenderReturnsErrorOnException(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap([
                ['template', null, '<h1>Test</h1>'],
                ['data', [], []],
                ['options', [], []],
                ['filename', 'document.pdf', 'output.pdf'],
            ]);

        $this->mockPdfService->method('renderPdf')
            ->willThrowException(new \Exception('PDF error', 500));

        $result = $this->controller->render();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(500, $result->getStatus());

    }//end testRenderReturnsErrorOnException()


}//end class
