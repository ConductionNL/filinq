<?php
/**
 * PDF Controller
 *
 * Controller for the PDF generation endpoint.
 * Accepts a Twig template and data context, returns a generated PDF.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-23
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-24
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\PdfService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for PDF generation endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class PdfController extends Controller
{
    /**
     * Constructor for PdfController
     *
     * @param string          $appName    The application name
     * @param IRequest        $request    The request object
     * @param LoggerInterface $logger     Logger for error reporting
     * @param PdfService      $pdfService Service for PDF generation
     * @param IL10N           $l10n       The localization service
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly PdfService $pdfService,
        private readonly IL10N $l10n
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Generate a PDF from a Twig template and data context
     *
     * Accepts JSON body with:
     * - template (string, required): Twig/HTML template content
     * - data (object, optional): Data context for template rendering
     * - options (object, optional): PDF configuration (format, orientation, margin, title)
     * - filename (string, optional): Suggested download filename (default: document.pdf)
     *
     * @return DataDownloadResponse|JSONResponse PDF download or error response
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-23
     */
    public function render(): DataDownloadResponse | JSONResponse
    {
        try {
            $template = $this->request->getParam('template');
            $data     = $this->request->getParam('data', []);
            $options  = $this->request->getParam('options', []);
            $filename = $this->request->getParam('filename', 'document.pdf');

            if (empty($template) === true) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Template content is required')],
                    statusCode: 400
                );
            }

            if (is_array($data) === false) {
                $data = [];
            }

            if (is_array($options) === false) {
                $options = [];
            }

            $pdfContent = $this->pdfService->renderPdf(
                templateContent: $template,
                data: $data,
                options: $options
            );

            return new DataDownloadResponse(
                data: $pdfContent,
                filename: $filename,
                contentType: 'application/pdf'
            );
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'PDF generation failed: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
        }//end try

    }//end render()

    /**
     * Generate a PDF/A-3b compliant document from a Twig template
     *
     * Behaves identically to render() but forces PDF/A-3b compliance.
     * The pdfa option in the request body is ignored; PDF/A is always enabled.
     *
     * Accepts JSON body with:
     * - template (string, required): Twig/HTML template content
     * - data (object, optional): Data context for template rendering
     * - options (object, optional): PDF configuration (format, orientation, margin, title)
     * - filename (string, optional): Suggested download filename (default: document.pdf)
     *
     * @return DataDownloadResponse|JSONResponse PDF/A download or error response
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-24
     */
    public function renderPdfA(): DataDownloadResponse | JSONResponse
    {
        try {
            $template = $this->request->getParam('template');
            $data     = $this->request->getParam('data', []);
            $options  = $this->request->getParam('options', []);
            $filename = $this->request->getParam('filename', 'document.pdf');

            if (empty($template) === true) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Template content is required')],
                    statusCode: 400
                );
            }

            if (is_array($data) === false) {
                $data = [];
            }

            if (is_array($options) === false) {
                $options = [];
            }

            $options['pdfa'] = true;

            $pdfContent = $this->pdfService->renderPdf(
                templateContent: $template,
                data: $data,
                options: $options
            );

            return new DataDownloadResponse(
                data: $pdfContent,
                filename: $filename,
                contentType: 'application/pdf'
            );
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'PDF/A generation failed: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
        }//end try

    }//end renderPdfA()
}//end class
