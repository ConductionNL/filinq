<?php
/**
 * Correspondence Controller
 *
 * Controller for correspondence generation endpoints.
 * Provides single generation, batch generation, and job status queries.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-15
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-16
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-17
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for correspondence generation endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-1
 */
class CorrespondenceController extends Controller
{
    /**
     * Constructor for CorrespondenceController
     *
     * @param string                $appName     The application name
     * @param IRequest              $request     The request object
     * @param CorrespondenceService $corrSvc     Correspondence generation service
     * @param IUserSession          $userSession User session for auth info
     * @param LoggerInterface       $logger      Logger for error reporting
     * @param IL10N                 $l10n        The localization service
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly CorrespondenceService $corrSvc,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
        private readonly IL10N $l10n
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Generate a single correspondence document
     *
     * Accepts JSON body with:
     * - templateId (string, required): UUID of the template to use
     * - dataRefs (array, required): Data references with register/schema/id
     * - options (object, optional): format, huisstijlId, caseReference
     * - filename (string, optional): Download filename
     *
     * @return DataDownloadResponse|JSONResponse Generated document or error
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-15
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

            $result = $this->corrSvc->generate(
                templateId: $params['templateId'],
                dataRefs: $params['dataRefs'],
                options: $params['options']
            );

            return $this->formatGenerateResponse(
                result: $result,
                filename: $params['filename']
            );
        } catch (Exception $e) {
            return $this->handleException(exception: $e);
        }//end try

    }//end generate()

    /**
     * Parse and validate generation request parameters
     *
     * @return array|JSONResponse Parsed params or error response
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-15
     */
    private function parseGenerateParams(): array | JSONResponse
    {
        $templateId = $this->request->getParam('templateId');
        $dataRefs   = $this->request->getParam('dataRefs', []);
        $options    = $this->request->getParam('options', []);
        $filename   = $this->request->getParam('filename', 'correspondence.pdf');

        if (empty($templateId) === true) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('templateId is required')],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        if (empty($dataRefs) === true || is_array($dataRefs) === false) {
            return new JSONResponse(
                data: ['error' => $this->l10n->t('dataRefs is required and must be an array')],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        if (is_array($options) === false) {
            $options = [];
        }

        $options['userId'] = $this->getCurrentUserId();

        return [
            'templateId' => $templateId,
            'dataRefs'   => $dataRefs,
            'options'    => $options,
            'filename'   => $filename,
        ];

    }//end parseGenerateParams()

    /**
     * Format the generate response based on output format
     *
     * @param array  $result   The generation result
     * @param string $filename The requested filename
     *
     * @return DataDownloadResponse|JSONResponse The formatted response
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-17
     */
    private function formatGenerateResponse(
        array $result,
        string $filename
    ): DataDownloadResponse | JSONResponse {
        $format = $result['format'];

        // For binary formats, return as download.
        if ($format === 'pdf' || $format === 'docx') {
            return $this->buildDownloadResponse(
                result: $result,
                format: $format,
                filename: $filename
            );
        }

        // For HTML/email formats, return as JSON with content.
        return new JSONResponse(
            data: [
                'content'  => $result['content'],
                'format'   => $format,
                'warnings' => $result['warnings'],
            ],
            statusCode: Http::STATUS_OK
        );

    }//end formatGenerateResponse()

    /**
     * Build a download response for binary document formats
     *
     * @param array  $result   The generation result
     * @param string $format   The output format (pdf or docx)
     * @param string $filename The requested filename
     *
     * @return DataDownloadResponse The download response
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-15
     */
    private function buildDownloadResponse(
        array $result,
        string $format,
        string $filename
    ): DataDownloadResponse {
        $extension   = '.pdf';
        $contentType = 'application/pdf';
        if ($format === 'docx') {
            $extension   = '.docx';
            $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        if (str_ends_with($filename, $extension) === false) {
            $filename = pathinfo($filename, PATHINFO_FILENAME).$extension;
        }

        return new DataDownloadResponse(
            data: $result['content'],
            filename: $filename,
            contentType: $contentType
        );

    }//end buildDownloadResponse()

    /**
     * Generate correspondence for a batch of recipients
     *
     * Accepts JSON body with:
     * - templateId (string, required): UUID of the template
     * - recipientIds (array, required): Array of recipient object UUIDs
     * - options (object, optional): format, register, schema, huisstijlId
     *
     * @return JSONResponse Batch results or job info
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-16
     */
    public function generateBatch(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $templateId   = $this->request->getParam('templateId');
            $recipientIds = $this->request->getParam('recipientIds', []);
            $options      = $this->request->getParam('options', []);

            if (empty($templateId) === true) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('templateId is required')],
                    statusCode: Http::STATUS_BAD_REQUEST
                );
            }

            if (empty($recipientIds) === true || is_array($recipientIds) === false) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('recipientIds is required and must be an array')],
                    statusCode: Http::STATUS_BAD_REQUEST
                );
            }

            if (is_array($options) === false) {
                $options = [];
            }

            $options['userId'] = $this->getCurrentUserId();

            $result = $this->corrSvc->generateBatch(
                templateId: $templateId,
                recipientIds: $recipientIds,
                options: $options
            );

            // If async (has jobId), return 202 Accepted.
            if (isset($result['jobId']) === true) {
                return new JSONResponse(
                    data: $result,
                    statusCode: Http::STATUS_ACCEPTED
                );
            }

            return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
        } catch (Exception $e) {
            return $this->handleException(exception: $e);
        }//end try

    }//end generateBatch()

    /**
     * Get the status of a batch correspondence job
     *
     * @param string $jobId The job UUID
     *
     * @return JSONResponse The job status
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-16
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

            $status = $this->corrSvc->getJobStatus(jobId: $jobId);

            if ($status === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Job not found')],
                    statusCode: Http::STATUS_NOT_FOUND
                );
            }

            // C3 / SB1 security fix: enforce job ownership so an authenticated user
            // cannot poll another user's job by guessing or brute-forcing the jobId.
            // ownerUserId is stored at the top level of the status payload (persisted
            // by dispatchBatchJob and every storeJobStatus call in the background job).
            // The old check read options.userId which was NEVER persisted — SB1 fix.
            $jobUserId = (string) ($status['ownerUserId'] ?? '');
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
     * Get the current user ID from the session
     *
     * @return string The user ID or empty string if not logged in
     */
    private function getCurrentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user !== null) {
            return $user->getUID();
        }

        return '';

    }//end getCurrentUserId()

    /**
     * Handle exceptions and return appropriate JSON error responses
     *
     * @param Exception $exception The exception to handle
     *
     * @return JSONResponse The error response
     *
     * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
     */
    private function handleException(Exception $exception): JSONResponse
    {
        $statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
        $code       = $exception->getCode();
        if ($code >= 400 && $code < 600) {
            $statusCode = $code;
        }

        $this->logger->error(
            message: 'Correspondence generation failed: '.$exception->getMessage(),
            context: ['exception' => $exception]
        );

        return new JSONResponse(
            data: ['error' => $exception->getMessage()],
            statusCode: $statusCode
        );

    }//end handleException()
}//end class
