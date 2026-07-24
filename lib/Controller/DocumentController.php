<?php

/**
 * Document Controller
 *
 * REST API controller for document generation endpoints.
 * Provides single document generation, bulk generation, HTML preview,
 * and async job status queries.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\DocumentService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for document generation endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */
class DocumentController extends Controller
{
    /**
     * Constructor for DocumentController.
     *
     * @param string          $appName     The application name
     * @param IRequest        $request     The request object
     * @param DocumentService $documentSvc Document generation service
     * @param IUserSession    $userSession User session for authentication
     * @param LoggerInterface $logger      Logger for error reporting
     * @param IL10N           $l10n        The localization service
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly DocumentService $documentSvc,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
        private readonly IL10N $l10n
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Generate a single document from a template.
     *
     * POST /api/documents/generate
     *
     * Request body:
     * - templateId (string, required): UUID of the template
     * - dataRefs (array, required): [{register, schema, id}, ...]
     * - options (object, optional): format, huisstijlId, zaakId, adHocData,
     *   listRefs, pdfOptions, output
     *   - listRefs (array, optional): [{register, schema, filter?, limit?,
     *     order?, as?}, ...] — each resolves to an array of objects in the
     *     Twig context under key 'as' (default: schema + '_list')
     *   - output (object, optional): {mode?: 'return'|'files'|'both', targetPath?}
     *     — defaults to mode 'return' (byte-identical to omitting it). 'files'
     *     stores the document in the requesting user's Files and returns JSON
     *     refs instead of a binary; 'both' stores AND returns the binary, with
     *     the stored file identified via X-Docudesk-File-Id/X-Docudesk-File-Path
     *     response headers.
     * - filename (string, optional): Download filename (also used as the
     *   stored filename's basename when output.mode is 'files'/'both')
     *
     * @return DataDownloadResponse|JSONResponse Generated document binary,
     *         stored-file JSON refs, or error
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
     * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md
     */
    public function generate(): DataDownloadResponse | JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $params = $this->parseGenerateParams();
            if ($params instanceof JSONResponse) {
                return $params;
            }

            $params['options']['userId']   = $user->getUID();
            $params['options']['filename'] = $params['filename'];

            $result = $this->documentSvc->generateDocument(
                templateId: $params['templateId'],
                dataRefs: $params['dataRefs'],
                options: $params['options']
            );

            return $this->buildDocumentResponse(
                result: $result,
                filename: $params['filename']
            );
        } catch (Exception $e) {
            return $this->handleException(exception: $e);
        }//end try

    }//end generate()

    /**
     * Generate an HTML preview of a template without final output.
     *
     * POST /api/documents/generate/preview
     *
     * Request body:
     * - templateId (string, required): UUID of the template
     * - dataRefs (array, optional): [{register, schema, id}, ...]
     * - options (object, optional): huisstijlId, adHocData, listRefs
     *   - listRefs (array, optional): [{register, schema, filter?, limit?,
     *     order?, as?}, ...] — each resolves to an array of objects in the
     *     Twig context under key 'as' (default: schema + '_list')
     *
     * @return JSONResponse Rendered HTML or error
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
     */
    public function preview(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $templateId = $this->request->getParam('templateId');
            if (empty($templateId) === true) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('templateId is required')],
                    statusCode: Http::STATUS_BAD_REQUEST
                );
            }

            $dataRefs = $this->request->getParam('dataRefs', []);
            if (is_array($dataRefs) === false) {
                $dataRefs = [];
            }

            $options = $this->request->getParam('options', []);
            if (is_array($options) === false) {
                $options = [];
            }

            $result = $this->documentSvc->generatePreview(
                templateId: $templateId,
                dataRefs: $dataRefs,
                options: $options
            );

            return new JSONResponse(
                data: [
                    'html'     => $result['html'],
                    'warnings' => $result['warnings'],
                ],
                statusCode: Http::STATUS_OK
            );
        } catch (Exception $e) {
            return $this->handleException(exception: $e);
        }//end try

    }//end preview()

    /**
     * Generate documents for multiple objects in bulk.
     *
     * POST /api/documents/generate/bulk
     *
     * Request body:
     * - templateId (string, required): UUID of the template
     * - objectIds (array, required): Array of object UUIDs
     * - options (object, optional): register, schema, format, huisstijlId
     *   - Note: options.listRefs is NOT supported here — see
     *     DocumentService::generateBulk() docblock for why.
     *
     * @return JSONResponse Synchronous results or async job info (202 Accepted)
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function generateBulk(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $templateId = $this->request->getParam('templateId');
            $objectIds  = $this->request->getParam('objectIds', []);
            $options    = $this->request->getParam('options', []);

            if (empty($templateId) === true) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('templateId is required')],
                    statusCode: Http::STATUS_BAD_REQUEST
                );
            }

            if (empty($objectIds) === true || is_array($objectIds) === false) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('objectIds is required and must be an array')],
                    statusCode: Http::STATUS_BAD_REQUEST
                );
            }

            if (is_array($options) === false) {
                $options = [];
            }

            $options['userId'] = $user->getUID();

            $result = $this->documentSvc->generateBulk(
                templateId: $templateId,
                objectIds: $objectIds,
                options: $options
            );

            if (isset($result['jobId']) === true) {
                return new JSONResponse(data: $result, statusCode: Http::STATUS_ACCEPTED);
            }

            return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
        } catch (Exception $e) {
            return $this->handleException(exception: $e);
        }//end try

    }//end generateBulk()

    /**
     * Get the status of an async bulk document generation job.
     *
     * GET /api/documents/jobs/{jobId}
     *
     * @param string $jobId The job UUID
     *
     * @return JSONResponse Job status or 404 if not found
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function jobStatus(string $jobId): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $status = $this->documentSvc->getJobStatus(jobId: $jobId);

            if ($status === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Job not found')],
                    statusCode: Http::STATUS_NOT_FOUND
                );
            }

            $jobUserId = (string) ($status['options']['userId'] ?? '');
            if ($jobUserId !== '' && $jobUserId !== $user->getUID()) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Access denied')],
                    statusCode: Http::STATUS_FORBIDDEN
                );
            }

            $status['jobId'] = $jobId;

            return new JSONResponse(data: $status, statusCode: Http::STATUS_OK);
        } catch (Exception $e) {
            return $this->handleException(exception: $e);
        }//end try

    }//end jobStatus()

    /**
     * Parse and validate the generate request parameters.
     *
     * @return array|JSONResponse Parsed params array or an error JSONResponse
     */
    private function parseGenerateParams(): array | JSONResponse
    {
        $templateId = $this->request->getParam('templateId');
        $dataRefs   = $this->request->getParam('dataRefs', []);
        $options    = $this->request->getParam('options', []);
        $filename   = $this->request->getParam('filename', 'document');

        if (empty($templateId) === true) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('templateId is required')],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        if (is_array($dataRefs) === false) {
            $dataRefs = [];
        }

        if (is_array($options) === false) {
            $options = [];
        }

        return [
            'templateId' => $templateId,
            'dataRefs'   => $dataRefs,
            'options'    => $options,
            'filename'   => $filename,
        ];

    }//end parseGenerateParams()

    /**
     * Build a download response for the generated document.
     *
     * Returns a binary download for pdf/odf, or JSON with HTML content, when
     * the resolved output mode is 'return' (the default — byte-identical to
     * this method's behaviour before options.output existed) or 'both'.
     * Returns JSON stored-file refs instead, with no binary, when the mode
     * is 'files'. When the mode is 'both', the stored file is additionally
     * identified via X-Docudesk-File-Id/X-Docudesk-File-Path response
     * headers.
     *
     * @param array  $result   The generation result
     * @param string $filename The requested filename (without extension)
     *
     * @return DataDownloadResponse|JSONResponse The formatted response
     *
     * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-001
     */
    private function buildDocumentResponse(
        array $result,
        string $filename
    ): DataDownloadResponse | JSONResponse {
        $format = $result['format'];
        $output = $result['output'] ?? ['mode' => 'return'];
        $mode   = $output['mode'] ?? 'return';

        if ($mode === 'files') {
            return new JSONResponse(
                data: [
                    'fileId' => $output['fileId'] ?? null,
                    'path'   => $output['path'] ?? null,
                    'name'   => $output['name'] ?? null,
                    'size'   => $output['size'] ?? null,
                    'format' => $format,
                ],
                statusCode: Http::STATUS_OK
            );
        }

        if ($format === 'html') {
            $response = new JSONResponse(
                data: [
                    'content'  => $result['content'],
                    'format'   => $format,
                    'metadata' => $result['metadata'],
                    'warnings' => $result['warnings'],
                ],
                statusCode: Http::STATUS_OK
            );
            $this->addStoredFileHeaders(response: $response, mode: $mode, output: $output);
            return $response;
        }

        $extension   = '.pdf';
        $contentType = 'application/pdf';
        if ($format === 'odf') {
            $extension   = '.odt';
            $contentType = 'application/vnd.oasis.opendocument.text';
        }

        $basename = pathinfo($filename, PATHINFO_FILENAME);
        if (empty($basename) === true) {
            $basename = 'document';
        }

        $response = new DataDownloadResponse(
            data: $result['content'],
            filename: $basename.$extension,
            contentType: $contentType
        );
        $this->addStoredFileHeaders(response: $response, mode: $mode, output: $output);

        return $response;

    }//end buildDocumentResponse()

    /**
     * Attach X-Docudesk-File-Id/X-Docudesk-File-Path headers when the
     * output mode is 'both' and the document was actually stored (a
     * fail-open storage failure leaves no fileId, so no headers are added).
     *
     * @param Response $response The response to annotate
     * @param string   $mode     The resolved output mode
     * @param array    $output   The generation result's output sub-array
     *
     * @return void
     *
     * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-001
     */
    private function addStoredFileHeaders(Response $response, string $mode, array $output): void
    {
        if ($mode !== 'both') {
            return;
        }

        if (isset($output['fileId']) === false) {
            return;
        }

        $response->addHeader('X-Docudesk-File-Id', (string) $output['fileId']);
        $response->addHeader('X-Docudesk-File-Path', (string) $output['path']);

    }//end addStoredFileHeaders()

    /**
     * Handle exceptions and return appropriate JSON error responses.
     *
     * @param Exception $exception The exception to handle
     *
     * @return JSONResponse The error response
     *
     * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>
     */
    private function handleException(Exception $exception): JSONResponse
    {
        $statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
        $code       = $exception->getCode();
        if ($code >= 400 && $code < 600) {
            $statusCode = $code;
        }

        $this->logger->error(
            message: 'Document generation failed: '.$exception->getMessage(),
            context: ['exception' => $exception]
        );

        return new JSONResponse(
            data: ['error' => $exception->getMessage()],
            statusCode: $statusCode
        );

    }//end handleException()
}//end class
