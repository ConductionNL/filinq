<?php

/**
 * Unit tests for ConsentController
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
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\ConsentController;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ConsentController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentControllerTest extends TestCase
{

    /**
     * @var ConsentController
     */
    private ConsentController $controller;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var ConsentCrudService|MockObject
     */
    private ConsentCrudService|MockObject $mockCrudService;

    /**
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;


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
        $this->mockCrudService = $this->createMock(ConsentCrudService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')->willReturnCallback(function ($text, $params = []) {
            return vsprintf($text, $params);
        });

        $this->controller = new ConsentController(
            'docudesk',
            $this->mockRequest,
            $this->mockLogger,
            $this->mockCrudService,
            $this->mockL10n
        );

    }//end setUp()


    /**
     * Test index returns 400 when not configured
     *
     * @return void
     */
    public function testIndexReturns400WhenNotConfigured(): void
    {
        $this->mockCrudService->method('getConsentConfig')
            ->willReturn(null);

        $result = $this->controller->index();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testIndexReturns400WhenNotConfigured()


    /**
     * Test index returns consent list when configured
     *
     * @return void
     */
    public function testIndexReturnsConsentList(): void
    {
        $this->mockCrudService->method('getConsentConfig')
            ->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
        $this->mockCrudService->method('listConsents')
            ->willReturn([['id' => 'uuid-1']]);

        $result = $this->controller->index();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testIndexReturnsConsentList()


    /**
     * Test create returns 400 when missing required fields
     *
     * @return void
     */
    public function testCreateReturns400WhenMissingFields(): void
    {
        $this->mockRequest->method('getParams')
            ->willReturn([]);

        $result = $this->controller->create();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testCreateReturns400WhenMissingFields()


    /**
     * Test show returns 404 when not found
     *
     * @return void
     */
    public function testShowReturns404WhenNotFound(): void
    {
        $this->mockCrudService->method('getConsentConfig')
            ->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
        $this->mockCrudService->method('getConsent')
            ->willReturn(null);

        $result = $this->controller->show('uuid-1');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(404, $result->getStatus());

    }//end testShowReturns404WhenNotFound()


    /**
     * Test show returns consent record when found
     *
     * @return void
     */
    public function testShowReturnsConsentWhenFound(): void
    {
        $this->mockCrudService->method('getConsentConfig')
            ->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
        $this->mockCrudService->method('getConsent')
            ->willReturn(['id' => 'uuid-1', 'consentStatus' => 'pending']);

        $result = $this->controller->show('uuid-1');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testShowReturnsConsentWhenFound()


}//end class
