<?php
/**
 * PDF Service
 *
 * Shared service for generating PDF documents from Twig templates and data.
 * Uses mPDF for HTML-to-PDF conversion and delegates template rendering
 * to TemplateRenderer. Supports PDF/A-3b archival compliance mode.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-57
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-58
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-59
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-60
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-61
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Psr\Log\LoggerInterface;

/**
 * Service for generating PDF documents from Twig templates
 *
 * Supports standard PDF 1.4 output and PDF/A-3b archival compliance.
 * When PDF/A mode is enabled, fonts are embedded and print-optimized
 * CSS is injected automatically.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class PdfService
{
    /**
     * Constructor for PdfService
     *
     * @param LoggerInterface  $logger           Logger for error reporting
     * @param TemplateRenderer $templateRenderer Template renderer for Twig
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TemplateRenderer $templateRenderer
    ) {

    }//end __construct()

    /**
     * Render a PDF from a Twig template string and data context
     *
     * @param string $templateContent Twig template content (HTML with Twig syntax)
     * @param array  $data            Data context for template rendering
     * @param array  $options         PDF configuration options:
     *                                - format: Page size (A4, A3, Letter, Legal). Default: A4
     *                                - orientation: P (portrait) or L (landscape). Default: P
     *                                - margin: Array with top, right, bottom, left in mm. Default: 15
     *                                - title: PDF document title metadata. Default: empty
     *                                - pdfa: Enable PDF/A-3b compliance. Default: false
     *
     * @return string PDF binary content
     *
     * @throws Exception If Twig rendering or PDF generation fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-57
     */
    public function renderPdf(string $templateContent, array $data=[], array $options=[]): string
    {
        $html = $this->templateRenderer->renderTemplate(
            templateContent: $templateContent,
            data: $data
        );

        return $this->generatePdf(html: $html, options: $options);

    }//end renderPdf()

    /**
     * Render HTML from a Twig template string and data context (for print preview)
     *
     * @param string $templateContent Twig template content (HTML with Twig syntax)
     * @param array  $data            Data context for template rendering
     * @param array  $options         Options for CSS injection:
     *                                - format: Page size (A4, A3, Letter, Legal). Default: A4
     *                                - orientation: P (portrait) or L (landscape). Default: P
     *
     * @return string Rendered HTML with print-optimized CSS injected
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-58
     */
    public function renderHtmlPreview(string $templateContent, array $data=[], array $options=[]): string
    {
        $html = $this->templateRenderer->renderTemplate(
            templateContent: $templateContent,
            data: $data
        );

        $format      = $options['format'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'P';
        $printCss    = $this->buildPrintCss(format: $format, orientation: $orientation);

        return $printCss.$html;

    }//end renderHtmlPreview()

    /**
     * Ensure the mPDF temp directory exists and is writable
     *
     * @param string $tempDir The temp directory path
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-59
     */
    private function ensureTempDirectory(string $tempDir): void
    {
        if (file_exists(filename: $tempDir) === false) {
            mkdir(directory: $tempDir, permissions: 0777, recursive: true);
        }

        chmod(filename: $tempDir, permissions: 0777);

    }//end ensureTempDirectory()

    /**
     * Build mPDF configuration array from options
     *
     * When PDF/A mode is enabled, configures mPDF for PDF/A-3b compliance
     * with embedded DejaVu Sans fonts and automatic PDF/A metadata.
     *
     * @param string $tempDir The temp directory path
     * @param array  $options PDF configuration options
     *
     * @return array<string, mixed> mPDF configuration
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-60
     */
    private function buildMpdfConfig(string $tempDir, array $options): array
    {
        $margins = $options['margin'] ?? [];
        $isPdfA  = ($options['pdfa'] ?? false) === true;

        $config = [
            'tempDir'       => $tempDir,
            'format'        => $options['format'] ?? 'A4',
            'orientation'   => $options['orientation'] ?? 'P',
            'margin_top'    => $margins['top'] ?? 15,
            'margin_right'  => $margins['right'] ?? 15,
            'margin_bottom' => $margins['bottom'] ?? 15,
            'margin_left'   => $margins['left'] ?? 15,
        ];

        if ($isPdfA === true) {
            $config['PDFA']     = true;
            $config['PDFAauto'] = true;

            $fontDir = $this->getFontDirectory();
            if ($fontDir !== null) {
                $config['fontDir']      = [
                    $fontDir,
                ];
                $config['fontdata']     = [
                    'dejavusans' => [
                        'R'  => 'DejaVuSans.ttf',
                        'B'  => 'DejaVuSans-Bold.ttf',
                        'I'  => 'DejaVuSans-Oblique.ttf',
                        'BI' => 'DejaVuSans-BoldOblique.ttf',
                    ],
                ];
                $config['default_font'] = 'dejavusans';
            }
        }//end if

        return $config;

    }//end buildMpdfConfig()

    /**
     * Get the path to the bundled font directory
     *
     * @return string|null The font directory path, or null if not found
     */
    private function getFontDirectory(): ?string
    {
        $fontDir = dirname(path: __DIR__).'/Fonts';
        if (is_dir(filename: $fontDir) === true) {
            return $fontDir;
        }

        return null;

    }//end getFontDirectory()

    /**
     * Build print-optimized CSS for PDF/A and print preview output
     *
     * Generates a style block with @media print rules including page size,
     * page-break-inside avoidance, and margin normalization.
     *
     * @param string $format      Page format (A4, A3, Letter, Legal)
     * @param string $orientation Page orientation (P for portrait, L for landscape)
     *
     * @return string HTML style block with print-optimized CSS
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-58
     */
    public function buildPrintCss(string $format, string $orientation): string
    {
        // Note on `@page`: this stylesheet intentionally omits the
        // `@page { size: ...; margin: ...; }` rule. mPDF's CSS parser
        // produced a degenerate page size (one character per page) when
        // `@page` was nested inside `@media print`, regardless of
        // whether `size: A4`, `size: A4 portrait`, or `size: A4
        // landscape` was emitted. Page dimensions are driven by the
        // mPDF config (`format` / `orientation` / `margin_*` in
        // `buildMpdfConfig`) instead, which mPDF interprets reliably.
        // The remaining rules are layout hints that don't touch page
        // dimensions.
        //
        // `$format` / `$orientation` remain part of the public contract
        // for callers that may key off them later; not consumed locally
        // since page sizing is delegated to the config path.
        unset($format, $orientation);

        return '<style>
@media print {
    body {
        margin: 0;
        padding: 0;
        font-family: "DejaVu Sans", sans-serif;
    }
    table, figure, img, pre, blockquote {
        page-break-inside: avoid;
    }
    h1, h2, h3, h4, h5, h6 {
        page-break-after: avoid;
    }
    nav, .no-print {
        display: none;
    }
}
</style>
';

    }//end buildPrintCss()

    /**
     * Generate a PDF from rendered HTML content
     *
     * Creates the mPDF temp directory if it does not exist,
     * initializes mPDF with the given options, and returns the PDF binary.
     * When PDF/A mode is enabled, injects print CSS and sets XMP metadata.
     *
     * @param string $html    Rendered HTML content
     * @param array  $options PDF configuration options
     *
     * @return string PDF binary content
     *
     * @throws Exception If mPDF fails to generate the PDF
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-61
     */
    private function generatePdf(string $html, array $options): string
    {
        $tempDir = '/tmp/mpdf';
        $this->ensureTempDirectory(tempDir: $tempDir);

        $config = $this->buildMpdfConfig(tempDir: $tempDir, options: $options);
        $title  = $options['title'] ?? '';
        $isPdfA = ($options['pdfa'] ?? false) === true;

        if ($isPdfA === true) {
            $format      = $options['format'] ?? 'A4';
            $orientation = $options['orientation'] ?? 'P';
            $printCss    = $this->buildPrintCss(format: $format, orientation: $orientation);
            $html        = $printCss.$html;
        }

        try {
            $mpdf = new Mpdf(config: $config);

            if ($title !== '') {
                $mpdf->SetTitle($title);
            }

            if ($isPdfA === true) {
                $mpdf->SetAuthor('DocuDesk');
                $mpdf->SetCreator('DocuDesk PDF/A Generator');
            }

            $mpdf->WriteHTML(html: $html);

            return $mpdf->Output(name: '', dest: \Mpdf\Output\Destination::STRING_RETURN);
        } catch (MpdfException $e) {
            $this->logger->error(
                message: 'mPDF generation failed: '.$e->getMessage(),
                context: ['exception' => $e]
            );
            throw new Exception(
                message: 'PDF generation failed: '.$e->getMessage(),
                code: 500,
                previous: $e
            );
        }//end try

    }//end generatePdf()
}//end class
