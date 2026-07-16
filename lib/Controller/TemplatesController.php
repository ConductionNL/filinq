<?php
/**
 * Templates Controller
 *
 * Controller for managing reusable document templates.
 * Provides CRUD endpoints plus version history, preview, duplication, and locking.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Exception\RegisterNotConfiguredException;
use OCA\DocuDesk\Service\TemplatePreviewService;
use OCA\DocuDesk\Service\TemplateService;
use OCA\DocuDesk\Service\TemplateVersionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for template CRUD, versioning, preview, duplication and locking
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TemplatesController extends Controller
{
    /**
     * Constructor for TemplatesController
     *
     * @param string                 $appName         The application name
     * @param IRequest               $request         The request object
     * @param TemplateService        $templateService Service for template operations
     * @param TemplateRequestHandler $requestHandler  Request param parser and error handler
     * @param TemplateVersionService $versionService  Service for version operations
     * @param TemplatePreviewService $previewService  Service for preview rendering
     * @param IUserSession           $userSession     User session for current user
     * @param LoggerInterface        $logger          Logger for not-configured info messages
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly TemplateService $templateService,
        private readonly TemplateRequestHandler $requestHandler,
        private readonly TemplateVersionService $versionService,
        private readonly TemplatePreviewService $previewService,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Get the current user ID
     *
     * @return string The current user ID or 'anonymous'
     */
    private function getCurrentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user !== null) {
            return $user->getUID();
        }

        return 'anonymous';

    }//end getCurrentUserId()

    /**
     * List templates with optional namespace filter and pagination
     *
     * @return JSONResponse JSON response with results array and total count
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function index(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $params = $this->requestHandler->parseListParams(request: $this->request);
            $result = $this->templateService->getTemplates(
                filters: $params['filters'],
                limit: $params['limit'],
                offset: $params['offset']
            );
            return new JSONResponse(data: $result);
        } catch (RegisterNotConfiguredException $e) {
            // Configuration missing is a setup state, not a failure — emit
            // an empty list with a notConfigured flag so the UI can render
            // a calm "register not configured yet" empty state instead of
            // surfacing a 500.
            $this->logger->info(
                'Templates list requested but register/schema is not configured: '.$e->getMessage()
            );
            return new JSONResponse(
                data: [
                    'results'       => [],
                    'total'         => 0,
                    'notConfigured' => true,
                ]
            );
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to list templates: ');
        }//end try

    }//end index()

    /**
     * Get a single template by ID
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with the template object
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function show(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $result = $this->templateService->getTemplate(id: $id);
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to get template: ');
        }

    }//end show()

    /**
     * Create a new template
     *
     * @return JSONResponse JSON response with the created template object
     *
     * @NoAdminRequired
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function create(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $data   = $this->requestHandler->parseBodyParams(request: $this->request);
            $result = $this->templateService->createTemplate(data: $data);
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to create template: ');
        }//end try

    }//end create()

    /**
     * Update an existing template
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with the updated template object
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function update(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $data   = $this->requestHandler->parseBodyParams(request: $this->request, stripKeys: ['id']);
            $result = $this->templateService->updateTemplate(id: $id, data: $data);
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to update template: ');
        }//end try

    }//end update()

    /**
     * Delete a template
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with success status
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function destroy(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $this->templateService->deleteTemplate(id: $id);
            return new JSONResponse(data: ['success' => true]);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to delete template: ');
        }

    }//end destroy()

    /**
     * List version history for a template
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with version list
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function versions(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $limit  = (int) $this->request->getParam('_limit', '20');
            $offset = (int) $this->request->getParam('_offset', '0');
            $result = $this->versionService->getVersions(
                templateId: $id,
                limit: $limit,
                offset: $offset
            );
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to list versions: ');
        }

    }//end versions()

    /**
     * Restore a template to a previous version
     *
     * @param string $id        The template UUID
     * @param string $versionId The version UUID to restore
     *
     * @return JSONResponse JSON response with the restored template object
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function restoreVersion(string $id, string $versionId): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $editor = $this->getCurrentUserId();
            $result = $this->versionService->restoreVersion(
                templateId: $id,
                versionId: $versionId,
                editor: $editor,
                service: $this->templateService
            );
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to restore version: ');
        }

    }//end restoreVersion()

    /**
     * Get two versions for diff comparison
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with both version objects
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function diffVersions(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $from = $this->request->getParam('from', '');
            $to   = $this->request->getParam('to', '');

            if (empty($from) === true || empty($to) === true) {
                throw new Exception(
                    message: 'Both "from" and "to" version UUIDs are required',
                    code: 400
                );
            }

            $result = $this->versionService->getDiff(
                versionIdFrom: $from,
                versionIdTo: $to
            );
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to get version diff: ');
        }//end try

    }//end diffVersions()

    /**
     * Preview raw template content with sample data
     *
     * @return JSONResponse JSON response with rendered HTML
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/advanced-template-management/tasks.md#task-5
     */
    public function preview(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $data    = $this->requestHandler->parseBodyParams(request: $this->request);
            $content = $data['content'] ?? '';
            $context = $data['data'] ?? [];

            if (empty($content) === true) {
                throw new Exception(message: 'Content is required for preview', code: 400);
            }

            $html = $this->previewService->preview(content: $content, data: $context);
            return new JSONResponse(data: ['html' => $html]);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to preview template: ');
        }//end try

    }//end preview()

    /**
     * Preview an existing template with sample data
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with rendered HTML
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/advanced-template-management/tasks.md#task-5
     */
    public function previewTemplate(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $data    = $this->requestHandler->parseBodyParams(request: $this->request);
            $context = $data['data'] ?? [];
            $html    = $this->previewService->previewTemplate(templateId: $id, data: $context);
            return new JSONResponse(data: ['html' => $html]);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to preview template: ');
        }

    }//end previewTemplate()

    /**
     * Duplicate a template
     *
     * @param string $id The template UUID to duplicate
     *
     * @return JSONResponse JSON response with the duplicated template object
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function duplicate(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $result = $this->templateService->duplicateTemplate(id: $id);
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to duplicate template: ');
        }

    }//end duplicate()

    /**
     * Acquire an edit lock on a template
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with the locked template
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function lock(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $userId = $this->getCurrentUserId();
            $result = $this->templateService->acquireLock(id: $id, userId: $userId);
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 409) {
                $decoded = json_decode($e->getMessage(), true);
                return new JSONResponse(
                    data: $decoded ?? ['error' => $e->getMessage()],
                    statusCode: 409
                );
            }

            return $this->requestHandler->buildErrorResponse($e, 'Failed to lock template: ');
        }//end try

    }//end lock()

    /**
     * Release an edit lock on a template
     *
     * @param string $id The template UUID
     *
     * @return JSONResponse JSON response with the unlocked template
     *
     * @NoAdminRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/specs/template-management/spec.md
     */
    public function unlock(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $userId = $this->getCurrentUserId();
            $result = $this->templateService->releaseLock(id: $id, userId: $userId);
            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to unlock template: ');
        }

    }//end unlock()
}//end class
