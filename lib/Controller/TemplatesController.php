<?php
/**
 * Templates Controller
 *
 * Controller for managing reusable document templates.
 * Provides CRUD endpoints for Twig/HTML templates stored in OpenRegister.
 * Delegates request parsing and error handling to TemplateRequestHandler.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\TemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Controller for template CRUD endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
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
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly TemplateService $templateService,
        private readonly TemplateRequestHandler $requestHandler,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()


    /**
     * List templates with optional namespace filter and pagination
     *
     * Supports query parameters: namespace, _search, _limit, _offset
     *
     * @return JSONResponse JSON response with results array and total count
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): JSONResponse
    {
        try {
            $params = $this->requestHandler->parseListParams($this->request);
            $result = $this->templateService->getTemplates(
                filters: $params['filters'],
                limit: $params['limit'],
                offset: $params['offset']
            );

            return new JSONResponse(data: $result);
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
     */
    public function show(string $id): JSONResponse
    {
        try {
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
     * @NoCSRFRequired
     */
    public function create(): JSONResponse
    {
        try {
            $data   = $this->requestHandler->parseBodyParams($this->request);
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
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function update(string $id): JSONResponse
    {
        try {
            $data   = $this->requestHandler->parseBodyParams($this->request, ['id']);
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
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function destroy(string $id): JSONResponse
    {
        try {
            $this->templateService->deleteTemplate(id: $id);

            return new JSONResponse(data: ['success' => true]);
        } catch (Exception $e) {
            return $this->requestHandler->buildErrorResponse($e, 'Failed to delete template: ');
        }

    }//end destroy()


}//end class
