<?php
/**
 * PDF Service
 *
 * Shared service for generating PDF documents from Twig templates and data.
 * Uses mPDF for HTML-to-PDF conversion and delegates template rendering
 * to TemplateRenderer.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
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
     *
     * @return string PDF binary content
     *
     * @throws Exception If Twig rendering or PDF generation fails
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
     * Ensure the mPDF temp directory exists and is writable
     *
     * @param string $tempDir The temp directory path
     *
     * @return void
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
     * @param string $tempDir The temp directory path
     * @param array  $options PDF configuration options
     *
     * @return array<string, mixed> mPDF configuration
     */
    private function buildMpdfConfig(string $tempDir, array $options): array
    {
        $margins = $options['margin'] ?? [];

        return [
            'tempDir'       => $tempDir,
            'format'        => $options['format'] ?? 'A4',
            'orientation'   => $options['orientation'] ?? 'P',
            'margin_top'    => $margins['top'] ?? 15,
            'margin_right'  => $margins['right'] ?? 15,
            'margin_bottom' => $margins['bottom'] ?? 15,
            'margin_left'   => $margins['left'] ?? 15,
        ];

    }//end buildMpdfConfig()


    /**
     * Generate a PDF from rendered HTML content
     *
     * Creates the mPDF temp directory if it does not exist,
     * initializes mPDF with the given options, and returns the PDF binary.
     *
     * @param string $html    Rendered HTML content
     * @param array  $options PDF configuration options
     *
     * @return string PDF binary content
     *
     * @throws Exception If mPDF fails to generate the PDF
     */
    private function generatePdf(string $html, array $options): string
    {
        $tempDir = '/tmp/mpdf';
        $this->ensureTempDirectory($tempDir);

        $config = $this->buildMpdfConfig($tempDir, $options);
        $title  = $options['title'] ?? '';

        try {
            $mpdf = new Mpdf(config: $config);

            if ($title !== '') {
                $mpdf->SetTitle($title);
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
