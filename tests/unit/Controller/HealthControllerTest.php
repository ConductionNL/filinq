<?php

/**
 * Unit tests for HealthController
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
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-5.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\HealthController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for HealthController
 *
 * HealthController::index() calls OCP\Server::get() which requires \OC.
 * We configure \OC::$server to throw, so the openregister check falls into
 * the catch(\Exception) branch and sets 'openregister' = 'unknown'.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HealthControllerTest extends TestCase
{

    /**
     * @var HealthController
     */
    private HealthController $controller;

    /**
     * @var IDBConnection|MockObject
     */
    private IDBConnection|MockObject $mockDatabase;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $mockRequest        = $this->createMock(IRequest::class);
        $this->mockDatabase = $this->createMock(IDBConnection::class);
        $this->mockLogger   = $this->createMock(LoggerInterface::class);

        // Make OC::$server throw so Server::get() lands in catch(\Exception).
        \OC::$server = new class {
            public function get(string $class): never
            {
                throw new Exception('Server not available in unit tests');
            }//end get()
        };

        $this->controller = new HealthController(
            appName: 'docudesk',
            request: $mockRequest,
            database: $this->mockDatabase,
            logger: $this->mockLogger,
        );

    }//end setUp()

    protected function tearDown(): void
    {
        \OC::$server = null;
        parent::tearDown();

    }//end tearDown()

    /**
     * Test index returns JSONResponse with status and checks keys.
     *
     * @return void
     */
    public function testIndexReturnsJsonResponseWithStatusAndChecks(): void
    {
        $mockQb = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select', 'createFunction', 'executeQuery'])
            ->getMock();

        $mockResult = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['closeCursor'])
            ->getMock();

        $mockQb->method('select')->willReturnSelf();
        $mockQb->method('createFunction')->willReturn('1');
        $mockQb->method('executeQuery')->willReturn($mockResult);

        $this->mockDatabase->method('getQueryBuilder')->willReturn($mockQb);

        $response = $this->controller->index();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('checks', $data);

    }//end testIndexReturnsJsonResponseWithStatusAndChecks()

    /**
     * Test index reports database error when query builder throws.
     *
     * @return void
     */
    public function testIndexReportsDatabaseErrorOnException(): void
    {
        $this->mockDatabase->method('getQueryBuilder')
            ->willThrowException(new Exception('DB connection failed'));

        $response = $this->controller->index();

        $data = $response->getData();
        $this->assertSame('error', $data['checks']['database']);
        $this->assertSame('error', $data['status']);

    }//end testIndexReportsDatabaseErrorOnException()

    /**
     * Test index always includes openregister check.
     *
     * @return void
     */
    public function testIndexAlwaysIncludesOpenregisterCheck(): void
    {
        $this->mockDatabase->method('getQueryBuilder')
            ->willThrowException(new Exception('DB unavailable'));

        $response = $this->controller->index();

        $data = $response->getData();
        $this->assertArrayHasKey('openregister', $data['checks']);

    }//end testIndexAlwaysIncludesOpenregisterCheck()
}//end class
