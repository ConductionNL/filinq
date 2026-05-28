<?php

/**
 * Unit tests for MetadataController
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

use OCA\DocuDesk\Controller\MetadataController;
use OCA\DocuDesk\Service\MetadataService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for MetadataController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MetadataControllerTest extends TestCase
{

    /**
     * @var MetadataController
     */
    private MetadataController $controller;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var MetadataService|MockObject
     */
    private MetadataService|MockObject $mockMetadataService;

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

        $this->mockRequest         = $this->createMock(IRequest::class);
        $this->mockLogger          = $this->createMock(LoggerInterface::class);
        $this->mockMetadataService = $this->createMock(MetadataService::class);
        $this->mockL10n            = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')->willReturnCallback(function ($text, $params = []) {
            return vsprintf($text, $params);
        });

        $mockUser                = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->mockUserSession   = $this->createMock(IUserSession::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->controller = new MetadataController(
            'docudesk',
            $this->mockRequest,
            $this->mockLogger,
            $this->mockMetadataService,
            $this->mockL10n,
            $this->mockUserSession
        );

    }//end setUp()


    /**
     * Test enrich returns 400 when objectId missing
     *
     * @return void
     */
    public function testEnrichReturns400WhenObjectIdMissing(): void
    {
        $this->mockRequest->method('getParams')
            ->willReturn([]);

        $result = $this->controller->enrich();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testEnrichReturns400WhenObjectIdMissing()


    /**
     * Test enrich returns 400 when register missing
     *
     * @return void
     */
    public function testEnrichReturns400WhenRegisterMissing(): void
    {
        $this->mockRequest->method('getParams')
            ->willReturn(['objectId' => 'obj-1']);

        $result = $this->controller->enrich();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testEnrichReturns400WhenRegisterMissing()


    /**
     * Test enrich returns 400 when schema missing
     *
     * @return void
     */
    public function testEnrichReturns400WhenSchemaMissing(): void
    {
        $this->mockRequest->method('getParams')
            ->willReturn(['objectId' => 'obj-1', 'register' => 'reg-1']);

        $result = $this->controller->enrich();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testEnrichReturns400WhenSchemaMissing()


    /**
     * Test enrich returns success when no enrichment needed
     *
     * @return void
     */
    public function testEnrichReturnsSuccessNoEnrichmentNeeded(): void
    {
        $this->mockRequest->method('getParams')
            ->willReturn([
                'objectId' => 'obj-1',
                'register' => 'reg-1',
                'schema'   => 'sch-1',
            ]);

        $this->mockMetadataService->method('enhanceMetadata')
            ->willReturn([]);

        $result = $this->controller->enrich();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testEnrichReturnsSuccessNoEnrichmentNeeded()


}//end class
