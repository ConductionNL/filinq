<?php
/**
 * Templates Controller
 *
 * Controller for managing reusable document templates.
 * Provides CRUD endpoints for Twig/HTML templates stored in OpenRegister.
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
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

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
     * @param string          $appName         The application name
     * @param IRequest        $request         The request object
     * @param LoggerInterface $logger          Logger for error reporting
     * @param TemplateService $templateService Service for template operations
     * @param IL10N           $l10n            The localization service
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly TemplateService $templateService,
        private readonly IL10N $l10n
    ) {
        parent::__construct($appName, $request);

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
            $filters   = [];
            $namespace = $this->request->getParam('namespace');
            $search    = $this->request->getParam('_search');
            $limit     = (int) $this->request->getParam('_limit', '20');
            $offset    = (int) $this->request->getParam('_offset', '0');

            if (empty($namespace) === false) {
                $filters['namespace'] = $namespace;
            }

            if (empty($search) === false) {
                $filters['_search'] = $search;
            }

            $result = $this->templateService->getTemplates(
                filters: $filters,
                limit: $limit,
                offset: $offset
            );

            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'Failed to list templates: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
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
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'Failed to get template: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
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
            $data = $this->request->getParams();
            unset($data['_route']);

            $result = $this->templateService->createTemplate(data: $data);

            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'Failed to create template: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
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
            $data = $this->request->getParams();
            unset($data['_route'], $data['id']);

            $result = $this->templateService->updateTemplate(id: $id, data: $data);

            return new JSONResponse(data: $result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'Failed to update template: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
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
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                message: 'Failed to delete template: '.$e->getMessage(),
                context: ['exception' => $e]
            );

            return new JSONResponse(
                data: ['error' => $e->getMessage()],
                statusCode: $statusCode
            );
        }

    }//end destroy()


}//end class
