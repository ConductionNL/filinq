<?php

/**
 * Unit tests for TemplatesController
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

use OCA\DocuDesk\Controller\TemplateRequestHandler;
use OCA\DocuDesk\Controller\TemplatesController;
use OCA\DocuDesk\Service\TemplatePreviewService;
use OCA\DocuDesk\Service\TemplateService;
use OCA\DocuDesk\Service\TemplateVersionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TemplatesController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplatesControllerTest extends TestCase
{

    /**
     * @var TemplatesController
     */
    private TemplatesController $controller;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var TemplateService|MockObject
     */
    private TemplateService|MockObject $mockTemplateService;

    /**
     * @var TemplateRequestHandler|MockObject
     */
    private TemplateRequestHandler|MockObject $mockRequestHandler;

    /**
     * @var TemplateVersionService|MockObject
     */
    private TemplateVersionService|MockObject $mockVersionService;

    /**
     * @var TemplatePreviewService|MockObject
     */
    private TemplatePreviewService|MockObject $mockPreviewService;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest         = $this->createMock(IRequest::class);
        $this->mockTemplateService = $this->createMock(TemplateService::class);
        $this->mockRequestHandler  = $this->createMock(TemplateRequestHandler::class);
        $this->mockVersionService  = $this->createMock(TemplateVersionService::class);
        $this->mockPreviewService  = $this->createMock(TemplatePreviewService::class);
        $this->mockUserSession     = $this->createMock(IUserSession::class);
        $this->mockLogger          = $this->createMock(LoggerInterface::class);

        $mockUser = $this->createMock(\OCP\IUser::class);
        $mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->controller = new TemplatesController(
            'docudesk',
            $this->mockRequest,
            $this->mockTemplateService,
            $this->mockRequestHandler,
            $this->mockVersionService,
            $this->mockPreviewService,
            $this->mockUserSession,
            $this->mockLogger
        );

    }//end setUp()


    /**
     * Test index returns template list
     *
     * @return void
     */
    public function testIndexReturnsTemplateList(): void
    {
        $this->mockRequestHandler->method('parseListParams')
            ->willReturn(['filters' => [], 'limit' => 20, 'offset' => 0]);
        $this->mockTemplateService->method('getTemplates')
            ->willReturn(['results' => [], 'total' => 0]);

        $result = $this->controller->index();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testIndexReturnsTemplateList()


    /**
     * Test show returns template
     *
     * @return void
     */
    public function testShowReturnsTemplate(): void
    {
        $this->mockTemplateService->method('getTemplate')
            ->with('uuid-1')
            ->willReturn(['id' => 'uuid-1', 'name' => 'Test']);

        $result = $this->controller->show('uuid-1');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testShowReturnsTemplate()


    /**
     * Test create returns created template
     *
     * @return void
     */
    public function testCreateReturnsCreatedTemplate(): void
    {
        $this->mockRequestHandler->method('parseBodyParams')
            ->willReturn(['name' => 'New Template']);
        $this->mockTemplateService->method('createTemplate')
            ->willReturn(['id' => 'uuid-new', 'name' => 'New Template']);

        $result = $this->controller->create();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testCreateReturnsCreatedTemplate()


    /**
     * Test destroy returns success
     *
     * @return void
     */
    public function testDestroyReturnsSuccess(): void
    {
        $this->mockTemplateService->expects($this->once())
            ->method('deleteTemplate')
            ->with('uuid-1');

        $result = $this->controller->destroy('uuid-1');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testDestroyReturnsSuccess()


    /**
     * Test index returns error on exception
     *
     * @return void
     */
    public function testIndexReturnsErrorOnException(): void
    {
        $this->mockRequestHandler->method('parseListParams')
            ->willReturn(['filters' => [], 'limit' => 20, 'offset' => 0]);
        $this->mockTemplateService->method('getTemplates')
            ->willThrowException(new \Exception('Error'));
        $this->mockRequestHandler->method('buildErrorResponse')
            ->willReturn(new JSONResponse(['error' => 'Error'], 500));

        $result = $this->controller->index();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(500, $result->getStatus());

    }//end testIndexReturnsErrorOnException()


}//end class
