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
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PdfServiceTest extends TestCase
{

    /**
     * @var PdfService
     */
    private PdfService $service;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var TemplateRenderer|MockObject
     */
    private TemplateRenderer|MockObject $mockRenderer;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger   = $this->createMock(LoggerInterface::class);
        $this->mockRenderer = $this->createMock(TemplateRenderer::class);

        $this->service = new PdfService(
            $this->mockLogger,
            $this->mockRenderer
        );

    }//end setUp()


    /**
     * Test renderPdf generates valid PDF content
     *
     * @return void
     */
    public function testRenderPdfGeneratesValidPdf(): void
    {
        $this->mockRenderer->method('renderTemplate')
            ->willReturn('<html><body><h1>Test</h1></body></html>');

        $result = $this->service->renderPdf('<h1>{{ title }}</h1>', ['title' => 'Test']);

        $this->assertNotEmpty($result);
        // PDF files start with %PDF.
        $this->assertStringStartsWith('%PDF', $result);

    }//end testRenderPdfGeneratesValidPdf()


    /**
     * Test renderPdf with custom options
     *
     * @return void
     */
    public function testRenderPdfWithCustomOptions(): void
    {
        $this->mockRenderer->method('renderTemplate')
            ->willReturn('<html><body>Landscape</body></html>');

        $result = $this->service->renderPdf(
            '<p>Test</p>',
            [],
            [
                'format'      => 'A3',
                'orientation' => 'L',
                'title'       => 'Test Document',
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('%PDF', $result);

    }//end testRenderPdfWithCustomOptions()


    /**
     * Test renderPdf with empty template
     *
     * @return void
     */
    public function testRenderPdfWithEmptyTemplate(): void
    {
        $this->mockRenderer->method('renderTemplate')
            ->willReturn('');

        $result = $this->service->renderPdf('', []);

        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('%PDF', $result);

    }//end testRenderPdfWithEmptyTemplate()


    /**
     * Test renderPdf throws on renderer failure
     *
     * @return void
     */
    public function testRenderPdfThrowsOnRendererFailure(): void
    {
        $this->expectException(\Exception::class);

        $this->mockRenderer->method('renderTemplate')
            ->willThrowException(new \Exception('Template rendering failed'));

        $this->service->renderPdf('{{ invalid', []);

    }//end testRenderPdfThrowsOnRendererFailure()

    /**
     * Test that pdfa=true produces genuine PDF/A-3b output — regression
     * guard for a pre-existing bug where buildMpdfConfig() enabled
     * PDFA/PDFAauto but never set PDFAversion, so mPDF silently defaulted
     * to PDF/A-1B even though this class's docblock has always promised
     * PDF/A-3b (see also Pdfa3ConversionService, which shares this
     * requirement for real archival compliance).
     *
     * @return void
     */
    public function testRenderPdfWithPdfaOptionProducesPdfA3b(): void
    {
        $this->mockRenderer->method('renderTemplate')
            ->willReturn('<html><body><h1>Archival</h1></body></html>');

        $result = $this->service->renderPdf('<h1>{{ title }}</h1>', ['title' => 'Archival'], ['pdfa' => true]);

        $this->assertStringStartsWith('%PDF', $result);
        $this->assertStringContainsString('<pdfaid:part>3</pdfaid:part>', $result);
        $this->assertStringContainsString('<pdfaid:conformance>B</pdfaid:conformance>', $result);

    }//end testRenderPdfWithPdfaOptionProducesPdfA3b()

    /**
     * The print CSS must NOT force whole tables onto a single page.
     *
     * `page-break-inside: avoid` on the `table` element made mPDF cram a
     * large data table (e.g. the 696-row grondslagen summary) onto one
     * page, rendering it unreadable. The avoid rule must apply to atomic
     * blocks (figure/img/pre/blockquote) but not to `table`.
     *
     * @return void
     */
    public function testBuildPrintCssDoesNotKeepWholeTablesOnOnePage(): void
    {
        $css = $this->service->buildPrintCss('A4', 'P');

        // The old, broken selector grouped `table` with the atomic blocks.
        $this->assertStringNotContainsString(
            'table, figure',
            $css,
            'The print CSS must not apply page-break-inside: avoid to whole tables.'
        );

        // The atomic-block avoid rule must still be present.
        $this->assertStringContainsString('figure, img, pre, blockquote', $css);

    }//end testBuildPrintCssDoesNotKeepWholeTablesOnOnePage()


    /**
     * Large tables must paginate cleanly: rows stay intact and the column
     * header repeats on every page the table spans.
     *
     * @return void
     */
    public function testBuildPrintCssPaginatesLargeTables(): void
    {
        $css = $this->service->buildPrintCss('A4', 'P');

        // Rows are kept whole so cells never split across a page boundary.
        $this->assertMatchesRegularExpression(
            '/tr\s*\{[^}]*page-break-inside:\s*avoid/s',
            $css,
            'Table rows must carry page-break-inside: avoid.'
        );

        // mPDF repeats <thead> on each page when display is table-header-group.
        $this->assertMatchesRegularExpression(
            '/thead\s*\{[^}]*display:\s*table-header-group/s',
            $css,
            'Table headers must repeat across pages via table-header-group.'
        );

    }//end testBuildPrintCssPaginatesLargeTables()


}//end class
