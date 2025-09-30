<?php

/**
 * Unit tests for TemplateService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\TemplateService;
use OCA\DocuDesk\Db\Template;
use OCA\DocuDesk\Db\TemplateMapper;
use Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TemplateService
 *
 * This test class provides comprehensive testing for the TemplateService functionality
 * including template creation, rendering, and format conversion capabilities.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class TemplateServiceTest extends TestCase
{

    /**
     * The TemplateService instance being tested
     *
     * @var TemplateService
     */
    private TemplateService $templateService;

    /**
     * Mocked TemplateMapper for database operations
     *
     * @var TemplateMapper|MockObject
     */
    private TemplateMapper|MockObject $mockMapper;


    /**
     * Set up test environment before each test
     *
     * This method initializes the test environment by creating mock objects
     * and setting up the TemplateService instance for testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock for TemplateMapper
        $this->mockMapper = $this->createMock(TemplateMapper::class);

        // Initialize TemplateService with mocked dependencies
        $this->templateService = new TemplateService($this->mockMapper);

    }//end setUp()


    /**
     * Clean up after each test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

    }//end tearDown()


    /**
     * Test template creation with valid data
     *
     * This test verifies that the createTemplate method correctly creates
     * a new template with the provided parameters and returns the inserted template.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testCreateTemplateWithValidData(): void
    {
        // Arrange: Set up test data
        $name = 'Test Template';
        $content = '<h1>Hello {{ name }}</h1>';
        $category = 'Test Category';
        $outputFormat = 'pdf';

        // Create expected template
        $expectedTemplate = new Template();
        $expectedTemplate->setName($name);
        $expectedTemplate->setContent($content);
        $expectedTemplate->setCategory($category);
        $expectedTemplate->setOutputFormat($outputFormat);
        $expectedTemplate->setId(1);

        // Configure mock to return the expected template
        $this->mockMapper->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Template $template): bool {
                return $template->getName() === 'Test Template'
                    && $template->getContent() === '<h1>Hello {{ name }}</h1>'
                    && $template->getCategory() === 'Test Category'
                    && $template->getOutputFormat() === 'pdf';
            }))
            ->willReturn($expectedTemplate);

        // Act: Call the method under test
        $result = $this->templateService->createTemplate($name, $content, $category, $outputFormat);

        // Assert: Verify the results
        $this->assertInstanceOf(Template::class, $result);
        $this->assertEquals($name, $result->getName());
        $this->assertEquals($content, $result->getContent());
        $this->assertEquals($category, $result->getCategory());
        $this->assertEquals($outputFormat, $result->getOutputFormat());
        $this->assertEquals(1, $result->getId());

    }//end testCreateTemplateWithValidData()


    /**
     * Test template rendering with simple data
     *
     * This test verifies that the renderTemplate method correctly renders
     * a template with provided data and returns HTML content.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testRenderTemplateWithSimpleData(): void
    {
        // Arrange: Set up test data
        $templateId = 1;
        $templateContent = '<h1>Hello {{ name }}</h1>';
        $renderData = ['name' => 'World'];
        $format = 'html';

        // Create mock template
        $mockTemplate = new Template();
        $mockTemplate->setContent($templateContent);

        // Configure mock to return the template
        $this->mockMapper->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($mockTemplate);

        // Act: Call the method under test
        $result = $this->templateService->renderTemplate($templateId, $renderData, $format);

        // Assert: Verify the rendered content
        $this->assertIsString($result);
        $this->assertStringContainsString('Hello World', $result);
        $this->assertStringContainsString('<h1>', $result);

    }//end testRenderTemplateWithSimpleData()


    /**
     * Test template rendering with complex data structure
     *
     * This test verifies that the renderTemplate method can handle
     * complex data structures including arrays and nested objects.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testRenderTemplateWithComplexData(): void
    {
        // Arrange: Set up complex test data
        $templateId = 2;
        $templateContent = '<h1>{{ title }}</h1><ul>{% for item in items %}<li>{{ item.name }}: {{ item.value }}</li>{% endfor %}</ul>';
        $renderData = [
            'title' => 'Test Report',
            'items' => [
                ['name' => 'Item 1', 'value' => 'Value 1'],
                ['name' => 'Item 2', 'value' => 'Value 2'],
            ]
        ];
        $format = 'html';

        // Create mock template
        $mockTemplate = new Template();
        $mockTemplate->setContent($templateContent);

        // Configure mock to return the template
        $this->mockMapper->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($mockTemplate);

        // Act: Call the method under test
        $result = $this->templateService->renderTemplate($templateId, $renderData, $format);

        // Assert: Verify the rendered content contains expected elements
        $this->assertIsString($result);
        $this->assertStringContainsString('Test Report', $result);
        $this->assertStringContainsString('Item 1: Value 1', $result);
        $this->assertStringContainsString('Item 2: Value 2', $result);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);

    }//end testRenderTemplateWithComplexData()


    /**
     * Test PDF format request returns HTML (placeholder implementation)
     *
     * This test verifies that requesting PDF format currently returns HTML
     * as the PDF conversion is not yet implemented.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testRenderTemplatePdfFormat(): void
    {
        // Arrange: Set up test data for PDF format
        $templateId = 3;
        $templateContent = '<h1>PDF Test</h1>';
        $renderData = [];
        $format = 'pdf';

        // Create mock template
        $mockTemplate = new Template();
        $mockTemplate->setContent($templateContent);

        // Configure mock to return the template
        $this->mockMapper->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($mockTemplate);

        // Act: Call the method under test
        $result = $this->templateService->renderTemplate($templateId, $renderData, $format);

        // Assert: Verify HTML is returned (placeholder implementation)
        $this->assertIsString($result);
        $this->assertStringContainsString('PDF Test', $result);

    }//end testRenderTemplatePdfFormat()


    /**
     * Test DOCX format request returns HTML (placeholder implementation)
     *
     * This test verifies that requesting DOCX format currently returns HTML
     * as the DOCX conversion is not yet implemented.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testRenderTemplateDocxFormat(): void
    {
        // Arrange: Set up test data for DOCX format
        $templateId = 4;
        $templateContent = '<h1>DOCX Test</h1>';
        $renderData = [];
        $format = 'docx';

        // Create mock template
        $mockTemplate = new Template();
        $mockTemplate->setContent($templateContent);

        // Configure mock to return the template
        $this->mockMapper->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($mockTemplate);

        // Act: Call the method under test
        $result = $this->templateService->renderTemplate($templateId, $renderData, $format);

        // Assert: Verify HTML is returned (placeholder implementation)
        $this->assertIsString($result);
        $this->assertStringContainsString('DOCX Test', $result);

    }//end testRenderTemplateDocxFormat()


}//end class 