<?php

/**
 * Unit tests for DashboardController
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

use OCA\DocuDesk\Controller\DashboardController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DashboardController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DashboardControllerTest extends TestCase
{

    /**
     * @var DashboardController
     */
    private DashboardController $controller;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest = $this->createMock(IRequest::class);
        $this->controller  = new DashboardController('docudesk', $this->mockRequest);

    }//end setUp()


    /**
     * Test page returns TemplateResponse
     *
     * @return void
     */
    public function testPageReturnsTemplateResponse(): void
    {
        $result = $this->controller->page(null);
        $this->assertInstanceOf(TemplateResponse::class, $result);

    }//end testPageReturnsTemplateResponse()


    /**
     * Test page with parameter returns TemplateResponse
     *
     * @return void
     */
    public function testPageWithParameterReturnsTemplateResponse(): void
    {
        $result = $this->controller->page('some-param');
        $this->assertInstanceOf(TemplateResponse::class, $result);

    }//end testPageWithParameterReturnsTemplateResponse()


    /**
     * Test page renders index template
     *
     * @return void
     */
    public function testPageRendersIndexTemplate(): void
    {
        $result = $this->controller->page(null);
        $this->assertEquals('index', $result->getTemplateName());

    }//end testPageRendersIndexTemplate()


}//end class
