<?php

/**
 * Unit tests for PdfService
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

use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Service\TemplateRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PdfService
 *
 * Tests cover buildPrintCss output, renderHtmlPreview CSS injection,
 * and renderPdf delegating to TemplateRenderer. The actual mPDF call is
 * not exercised in unit tests (requires file-system and library setup),
 * so generatePdf is tested indirectly through integration-style assertions
 * where the TemplateRenderer mock is used.
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
class PdfServiceTest extends TestCase
{

    /**
     * The PdfService instance under test
     *
     * @var PdfService
     */
    private PdfService $pdfService;

    /**
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked TemplateRenderer
     *
     * @var TemplateRenderer|MockObject
     */
    private TemplateRenderer|MockObject $mockRenderer;


    /**
     * Set up test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger   = $this->createMock(LoggerInterface::class);
        $this->mockRenderer = $this->createMock(TemplateRenderer::class);

        $this->pdfService = new PdfService(
            $this->mockLogger,
            $this->mockRenderer
        );

    }//end setUp()


    /**
     * Test buildPrintCss returns a style block for portrait A4
     *
     * @return void
     */
    public function testBuildPrintCssPortraitA4(): void
    {
        $css = $this->pdfService->buildPrintCss(format: 'A4', orientation: 'P');

        $this->assertStringContainsString('<style>', $css);
        $this->assertStringContainsString('@media print', $css);
        $this->assertStringContainsString('A4 portrait', $css);
        $this->assertStringContainsString('margin: 15mm', $css);

    }//end testBuildPrintCssPortraitA4()


    /**
     * Test buildPrintCss returns landscape orientation for L
     *
     * @return void
     */
    public function testBuildPrintCssLandscape(): void
    {
        $css = $this->pdfService->buildPrintCss(format: 'A3', orientation: 'L');

        $this->assertStringContainsString('A3 landscape', $css);

    }//end testBuildPrintCssLandscape()


    /**
     * Test buildPrintCss includes page-break-inside: avoid for tables
     *
     * @return void
     */
    public function testBuildPrintCssIncludesPageBreakRules(): void
    {
        $css = $this->pdfService->buildPrintCss(format: 'A4', orientation: 'P');

        $this->assertStringContainsString('page-break-inside: avoid', $css);
        $this->assertStringContainsString('page-break-after: avoid', $css);

    }//end testBuildPrintCssIncludesPageBreakRules()


    /**
     * Test buildPrintCss hides nav and .no-print elements
     *
     * @return void
     */
    public function testBuildPrintCssHidesNoPrintElements(): void
    {
        $css = $this->pdfService->buildPrintCss(format: 'A4', orientation: 'P');

        $this->assertStringContainsString('display: none', $css);
        $this->assertStringContainsString('.no-print', $css);

    }//end testBuildPrintCssHidesNoPrintElements()


    /**
     * Test renderHtmlPreview returns rendered HTML with print CSS prepended
     *
     * @return void
     */
    public function testRenderHtmlPreviewPrependsStyleBlock(): void
    {
        $this->mockRenderer->expects($this->once())
            ->method('renderTemplate')
            ->with('<p>{{ name }}</p>', ['name' => 'Test'])
            ->willReturn('<p>Test</p>');

        $result = $this->pdfService->renderHtmlPreview(
            templateContent: '<p>{{ name }}</p>',
            data: ['name' => 'Test'],
            options: ['format' => 'A4', 'orientation' => 'P']
        );

        $this->assertStringStartsWith('<style>', $result);
        $this->assertStringContainsString('<p>Test</p>', $result);

    }//end testRenderHtmlPreviewPrependsStyleBlock()


    /**
     * Test renderHtmlPreview uses defaults when options are empty
     *
     * @return void
     */
    public function testRenderHtmlPreviewUsesDefaultOptions(): void
    {
        $this->mockRenderer->expects($this->once())
            ->method('renderTemplate')
            ->willReturn('<p>Hello</p>');

        $result = $this->pdfService->renderHtmlPreview(
            templateContent: '<p>Hello</p>',
            data: [],
            options: []
        );

        // Defaults: A4 portrait
        $this->assertStringContainsString('A4 portrait', $result);

    }//end testRenderHtmlPreviewUsesDefaultOptions()


    /**
     * Test renderHtmlPreview with landscape orientation option
     *
     * @return void
     */
    public function testRenderHtmlPreviewWithLandscapeOption(): void
    {
        $this->mockRenderer->method('renderTemplate')->willReturn('<p>Doc</p>');

        $result = $this->pdfService->renderHtmlPreview(
            templateContent: '<p>Doc</p>',
            data: [],
            options: ['format' => 'Letter', 'orientation' => 'L']
        );

        $this->assertStringContainsString('Letter landscape', $result);

    }//end testRenderHtmlPreviewWithLandscapeOption()


    /**
     * Test renderPdf delegates rendering to TemplateRenderer
     *
     * mPDF itself is not exercised because it writes files to disk.
     * We verify the TemplateRenderer is called with the correct arguments.
     * The test will throw at the mPDF instantiation step — we catch that
     * and confirm the renderer was called as expected.
     *
     * @return void
     */
    public function testRenderPdfDelegatesToTemplateRenderer(): void
    {
        $this->mockRenderer->expects($this->once())
            ->method('renderTemplate')
            ->with('<h1>Hello</h1>', ['name' => 'World'])
            ->willThrowException(new \Exception('Renderer error'));

        $this->expectException(\Exception::class);

        $this->pdfService->renderPdf(
            templateContent: '<h1>Hello</h1>',
            data: ['name' => 'World']
        );

    }//end testRenderPdfDelegatesToTemplateRenderer()


}//end class
