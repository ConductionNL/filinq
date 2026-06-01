<?php

/**
 * Unit tests for MetricsController
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

use OCA\DocuDesk\Controller\MetricsCollector;
use OCA\DocuDesk\Controller\MetricsController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MetricsController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MetricsControllerTest extends TestCase
{

    /**
     * @var MetricsController
     */
    private MetricsController $controller;

    /**
     * @var IConfig|MockObject
     */
    private IConfig|MockObject $mockConfig;

    /**
     * @var MetricsCollector|MockObject
     */
    private MetricsCollector|MockObject $mockCollector;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mockRequest         = $this->createMock(IRequest::class);
        $this->mockConfig    = $this->createMock(IConfig::class);
        $mockAppConfig       = $this->createMock(IAppConfig::class);
        $this->mockCollector = $this->createMock(MetricsCollector::class);

        $this->controller = new MetricsController(
            'docudesk',
            $mockRequest,
            $this->mockConfig,
            $mockAppConfig,
            $this->mockCollector
        );

    }//end setUp()


    /**
     * Test metrics endpoint returns Prometheus format
     *
     * @return void
     */
    public function testIndexReturnsPrometheusFormat(): void
    {
        $this->mockConfig->method('getAppValue')
            ->willReturnMap([
                ['docudesk', 'installed_version', '0.0.0', '0.0.32'],
                ['docudesk', 'pdf_generations_total', '0', '5'],
                ['docudesk', 'anonymizations_total', '0', '3'],
            ]);

        $this->mockConfig->method('getSystemValueString')
            ->willReturn('29.0.0');

        $this->mockCollector->method('countDocuments')->willReturn(10);
        $this->mockCollector->method('countTemplates')->willReturn(2);

        $response = $this->controller->index();

        $this->assertInstanceOf(\OCP\AppFramework\Http\TextPlainResponse::class, $response);

    }//end testIndexReturnsPrometheusFormat()


}//end class
