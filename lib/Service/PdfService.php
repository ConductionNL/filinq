<?php
/**
 * PDF Service
 *
 * Shared service for generating PDF documents from Twig templates and data.
 * Uses mPDF for HTML-to-PDF conversion and Twig for template rendering.
 * Designed to be consumed by any Nextcloud app via DI container.
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
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

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
     * Allowed Twig filters in the sandbox
     *
     * @var string[]
     */
    private const ALLOWED_FILTERS = [
        'escape',
        'e',
        'upper',
        'lower',
        'trim',
        'nl2br',
        'date',
        'number_format',
        'join',
        'split',
        'first',
        'last',
        'length',
        'default',
        'raw',
        'sort',
        'reverse',
        'keys',
        'values',
        'merge',
        'slice',
        'batch',
        'column',
        'round',
        'abs',
    ];

    /**
     * Allowed Twig functions in the sandbox
     *
     * @var string[]
     */
    private const ALLOWED_FUNCTIONS = [
        'range',
        'cycle',
        'date',
        'max',
        'min',
    ];

    /**
     * Allowed Twig tags in the sandbox
     *
     * @var string[]
     */
    private const ALLOWED_TAGS = [
        'if',
        'for',
        'set',
        'block',
        'extends',
        'include',
        'macro',
        'spaceless',
        'apply',
        'autoescape',
    ];


    /**
     * Constructor for PdfService
     *
     * @param LoggerInterface $logger Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger
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
        $html = $this->renderTemplate(templateContent: $templateContent, data: $data);

        return $this->generatePdf(html: $html, options: $options);

    }//end renderPdf()


    /**
     * Render a Twig template string with the given data context
     *
     * Uses a sandboxed Twig environment that only allows safe filters,
     * functions, and tags. Objects cannot have methods or properties called.
     *
     * @param string $templateContent Twig template content
     * @param array  $data            Data context for rendering
     *
     * @return string Rendered HTML
     *
     * @throws Exception If Twig rendering fails (syntax error, security violation)
     */
    private function renderTemplate(string $templateContent, array $data): string
    {
        $loader = new ArrayLoader(templates: ['document' => $templateContent]);
        $twig   = new Environment(loader: $loader, options: ['strict_variables' => false]);

        $policy  = new SecurityPolicy(
            allowedTags: self::ALLOWED_TAGS,
            allowedFilters: self::ALLOWED_FILTERS,
            allowedMethods: [],
            allowedProperties: [],
            allowedFunctions: self::ALLOWED_FUNCTIONS
        );
        $sandbox = new SandboxExtension(policy: $policy, sandboxed: true);
        $twig->addExtension(extension: $sandbox);

        try {
            return $twig->render(name: 'document', context: $data);
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Twig template rendering failed: '.$e->getMessage(),
                context: ['exception' => $e]
            );
            throw new Exception(
                message: 'Template rendering failed: '.$e->getMessage(),
                code: 400,
                previous: $e
            );
        }

    }//end renderTemplate()


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
        if (file_exists(filename: $tempDir) === false) {
            mkdir(directory: $tempDir, permissions: 0777, recursive: true);
        }

        chmod(filename: $tempDir, permissions: 0777);

        $format       = $options['format'] ?? 'A4';
        $orientation  = $options['orientation'] ?? 'P';
        $title        = $options['title'] ?? '';
        $marginTop    = $options['margin']['top'] ?? 15;
        $marginRight  = $options['margin']['right'] ?? 15;
        $marginBottom = $options['margin']['bottom'] ?? 15;
        $marginLeft   = $options['margin']['left'] ?? 15;

        try {
            $mpdf = new Mpdf(
                    config: [
                        'tempDir'       => $tempDir,
                        'format'        => $format,
                        'orientation'   => $orientation,
                        'margin_top'    => $marginTop,
                        'margin_right'  => $marginRight,
                        'margin_bottom' => $marginBottom,
                        'margin_left'   => $marginLeft,
                    ]
                    );

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
