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
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/specs/print-preview/spec.md
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Exception\Pdfa3ConversionException;
use OCA\DocuDesk\Service\Pdfa3ConversionService;
use OCA\DocuDesk\Service\PdfService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for PDF generation endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class PdfController extends Controller
{
    /**
     * Constructor for PdfController
     *
     * @param string                 $appName      The application name
     * @param IRequest               $request      The request object
     * @param LoggerInterface        $logger       Logger for error reporting
     * @param PdfService             $pdfService   Service for PDF generation
     * @param Pdfa3ConversionService $pdfa3Service Service for archival PDF/A-3
     *                                             generation (attachments +
     *                                             MDTO metadata)
     * @param IL10N                  $l10n         The localization service
     * @param IUserSession           $userSession  User session for authentication
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly PdfService $pdfService,
        private readonly Pdfa3ConversionService $pdfa3Service,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession
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
     *
     * @spec openspec/specs/pdf-generation/spec.md
     */
    public function render(): DataDownloadResponse | JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

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
     * - metadata (object, optional): MDTO/archival metadata. When present (or when
     *   `attachments` is present), generation is delegated to Pdfa3ConversionService
     *   so the document is emitted as a full archival PDF/A-3 with the metadata folded
     *   into its XMP packet — see openspec/changes/pdfa3-conversion.
     * - attachments (array, optional): [{name, mime, content, description?,
     *   AFRelationship?}, ...] to embed alongside the generated document (PDF/A-3's
     *   defining feature over PDF/A-1/A-2).
     *
     * @return DataDownloadResponse|JSONResponse PDF/A download or error response
     *
     * @NoAdminRequired
     *
     * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
     *
     * @spec openspec/specs/print-preview/spec.md
     * @spec openspec/specs/pdfa3-conversion/spec.md
     */
    public function renderPdfA(): DataDownloadResponse | JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $template = $this->request->getParam('template');
            if (empty($template) === true) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Template content is required')],
                    statusCode: 400
                );
            }

            $params = $this->collectRenderPdfAParams();

            // Archival path: MDTO metadata and/or embedded attachments were
            // requested, so hand off to the dedicated PDF/A-3 service rather
            // than the plain PDF/A-1B-safe-but-attachment-less PdfService path.
            if (empty($params['metadata']) === false || empty($params['attachments']) === false) {
                return $this->renderArchivalPdfA(
                    template: $template,
                    data: $params['data'],
                    metadata: $params['metadata'],
                    attachments: $params['attachments'],
                    options: $params['options'],
                    filename: $params['filename']
                );
            }

            $options         = $params['options'];
            $options['pdfa'] = true;

            $pdfContent = $this->pdfService->renderPdf(
                templateContent: $template,
                data: $params['data'],
                options: $options
            );

            return new DataDownloadResponse(
                data: $pdfContent,
                filename: $params['filename'],
                contentType: 'application/pdf'
            );
        } catch (Pdfa3ConversionException $e) {
            return $this->mapPdfa3ExceptionToResponse(exception: $e);
        } catch (Exception $e) {
            return $this->mapGenericExceptionToResponse(exception: $e);
        }//end try

    }//end renderPdfA()

    /**
     * Collect and normalise renderPdfA()'s request parameters. Split out
     * purely to keep renderPdfA() within the fleet's cyclomatic-complexity
     * threshold.
     *
     * @return array{data:array<string,mixed>,options:array<string,mixed>,metadata:array<string,mixed>,attachments:array<int,mixed>,filename:string}
     */
    private function collectRenderPdfAParams(): array
    {
        $data     = $this->request->getParam('data', []);
        $options  = $this->request->getParam('options', []);
        $filename = $this->request->getParam('filename', 'document.pdf');

        $metadata    = $this->request->getParam('metadata', []);
        $attachments = $this->request->getParam('attachments', []);

        if (is_array($data) === false) {
            $data = [];
        }

        if (is_array($options) === false) {
            $options = [];
        }

        if (is_array($metadata) === false) {
            $metadata = [];
        }

        if (is_array($attachments) === false) {
            $attachments = [];
        }

        return [
            'data'        => $data,
            'options'     => $options,
            'metadata'    => $metadata,
            'attachments' => $attachments,
            'filename'    => (string) $filename,
        ];

    }//end collectRenderPdfAParams()

    /**
     * Map a Pdfa3ConversionException to its typed JSON error response.
     *
     * @param Pdfa3ConversionException $exception The caught exception.
     *
     * @return JSONResponse
     */
    private function mapPdfa3ExceptionToResponse(Pdfa3ConversionException $exception): JSONResponse
    {
        $this->logger->warning(
            message: 'PDF/A-3 generation failed: '.$exception->getMessage(),
            context: ['reason' => $exception->getReason()]
        );

        return new JSONResponse(
            data: [
                'error'     => $exception->getMessage(),
                'reason'    => $exception->getReason(),
                'adminHint' => $exception->getAdminHint(),
            ],
            statusCode: $this->clampStatusCode(code: $exception->getCode())
        );

    }//end mapPdfa3ExceptionToResponse()

    /**
     * Map a generic Exception to a JSON error response.
     *
     * @param Exception $exception The caught exception.
     *
     * @return JSONResponse
     */
    private function mapGenericExceptionToResponse(Exception $exception): JSONResponse
    {
        $this->logger->error(
            message: 'PDF/A generation failed: '.$exception->getMessage(),
            context: ['exception' => $exception]
        );

        return new JSONResponse(
            data: ['error' => $exception->getMessage()],
            statusCode: $this->clampStatusCode(code: $exception->getCode())
        );

    }//end mapGenericExceptionToResponse()

    /**
     * Clamp an exception code to a valid HTTP status range, defaulting
     * to 500 for anything outside [400, 599].
     *
     * @param int $code Candidate status code.
     *
     * @return int Valid HTTP status code.
     *
     * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
     */
    private function clampStatusCode(int $code): int
    {
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        return Http::STATUS_INTERNAL_SERVER_ERROR;

    }//end clampStatusCode()

    /**
     * Render the archival PDF/A-3 path: render the Twig template to
     * HTML, delegate to Pdfa3ConversionService::convertHtml() for the
     * full PDF/A-3 assembly (metadata XMP + attachments), and surface
     * the composition metadata as response headers.
     *
     * Extracted from renderPdfA() to keep that method's cyclomatic
     * complexity within the fleet's phpmd threshold.
     *
     * @param string              $template    Twig template content.
     * @param array<string,mixed> $data        Data context for template rendering.
     * @param array<string,mixed> $metadata    MDTO/archival metadata.
     * @param array<int,mixed>    $attachments Files to embed.
     * @param array<string,mixed> $options     PDF configuration options.
     * @param string              $filename    Suggested download filename.
     *
     * @return DataDownloadResponse
     *
     * @throws \OCA\DocuDesk\Exception\Pdfa3ConversionException Propagated to renderPdfA()'s catch block.
     *
     * @spec openspec/specs/pdfa3-conversion/spec.md
     */
    private function renderArchivalPdfA(
        string $template,
        array $data,
        array $metadata,
        array $attachments,
        array $options,
        string $filename
    ): DataDownloadResponse {
        $html   = $this->pdfService->renderTemplateToHtml(templateContent: $template, data: $data);
        $result = $this->pdfa3Service->convertHtml(
            html: $html,
            metadata: $metadata,
            attachments: $attachments,
            options: $options
        );

        $response = new DataDownloadResponse(
            data: $result['content'],
            filename: $filename,
            contentType: 'application/pdf'
        );
        $response->addHeader('X-Docudesk-Pdfa3-Checksum-Sha256', $result['checksumSha256']);
        $response->addHeader('X-Docudesk-Pdfa3-Pages', (string) $result['pages']);
        $response->addHeader('X-Docudesk-Pdfa3-Conformance', $result['conformance']);

        return $response;

    }//end renderArchivalPdfA()
}//end class
