<?php
/**
 * Template Service
 *
 * Service for managing reusable Twig/HTML templates stored as OpenRegister objects.
 * Templates are scoped per-app via a namespace field, enabling multiple apps
 * to maintain their own template collections.
 * Delegates register/schema resolution and namespace validation to OpenRegisterResolver.
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
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;

/**
 * Service for CRUD operations on document templates via OpenRegister
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TemplateService
{


    /**
     * Constructor for TemplateService
     *
     * @param ContainerInterface   $container        Container for dependency injection
     * @param IAppManager          $appManager       App manager interface
     * @param OpenRegisterResolver $registerResolver Resolver for register/schema config
     *
     * @return void
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly OpenRegisterResolver $registerResolver
    ) {

    }//end __construct()


    /**
     * Get the ObjectService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(
            needle: 'openregister',
            haystack: $this->appManager->getInstalledApps(),
            strict: true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException(message: 'OpenRegister service is not available.');

    }//end getObjectService()


    /**
     * List templates with optional filters
     *
     * @param array $filters Optional filters (e.g. namespace, _search)
     * @param int   $limit   Maximum number of results (default: 20)
     * @param int   $offset  Result offset for pagination (default: 0)
     *
     * @return array{results: array, total: int} Paginated template results
     *
     * @throws Exception If listing fails
     */
    public function getTemplates(array $filters=[], int $limit=20, int $offset=0): array
    {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getRegisterAndSchema();

        $requestParams            = $filters;
        $requestParams['_limit']  = $limit;
        $requestParams['_offset'] = $offset;

        $query = $objectService->buildSearchQuery(
            requestParams: $requestParams,
            register: $config['register'],
            schema: $config['schema']
        );

        return $objectService->searchObjectsPaginated(query: $query);

    }//end getTemplates()


    /**
     * Get a single template by UUID
     *
     * @param string $id The template UUID
     *
     * @return array The template object
     *
     * @throws Exception If the template is not found
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function getTemplate(string $id): array
    {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getRegisterAndSchema();

        $result = $objectService->find(
            id: $id,
            register: $config['register'],
            schema: $config['schema']
        );

        if (empty($result) === true) {
            throw new Exception(message: 'Template not found', code: 404);
        }

        if (is_object($result) === true
            && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
        ) {
            return $result->jsonSerialize();
        }

        return $result;

    }//end getTemplate()


    /**
     * Create a new template
     *
     * @param array $data Template data (name, content, namespace required)
     *
     * @return array The created template object
     *
     * @throws Exception If creation fails or validation errors occur
     */
    public function createTemplate(array $data): array
    {
        if (empty($data['namespace']) === true) {
            throw new Exception(message: 'Namespace is required', code: 400);
        }

        $this->registerResolver->validateNamespace(namespace: $data['namespace']);

        if (empty($data['name']) === true) {
            throw new Exception(message: 'Name is required', code: 400);
        }

        if (empty($data['content']) === true) {
            throw new Exception(message: 'Content is required', code: 400);
        }

        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getRegisterAndSchema();

        $result = $objectService->saveObject(
            object: $data,
            register: $config['register'],
            schema: $config['schema']
        );

        if (is_object($result) === true
            && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
        ) {
            return $result->jsonSerialize();
        }

        return $result;

    }//end createTemplate()


    /**
     * Update an existing template
     *
     * The namespace field cannot be changed after creation.
     *
     * @param string $id   The template UUID
     * @param array  $data Updated template data
     *
     * @return array The updated template object
     *
     * @throws Exception If the template is not found or update fails
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function updateTemplate(string $id, array $data): array
    {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getRegisterAndSchema();

        $existing = $this->getTemplate(id: $id);

        // Namespace is immutable after creation.
        unset($data['namespace']);

        $data['id'] = $id;
        $merged     = array_merge($existing, $data);

        $result = $objectService->saveObject(
            object: $merged,
            register: $config['register'],
            schema: $config['schema']
        );

        if (is_object($result) === true
            && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
        ) {
            return $result->jsonSerialize();
        }

        return $result;

    }//end updateTemplate()


    /**
     * Delete a template
     *
     * @param string $id The template UUID
     *
     * @return bool True if deletion succeeded
     *
     * @throws Exception If the template is not found or deletion fails
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function deleteTemplate(string $id): bool
    {
        $objectService = $this->getObjectService();

        $objectService->deleteObject(
            uuid: $id
        );

        return true;

    }//end deleteTemplate()


    /**
     * Get all templates for a specific app namespace
     *
     * @param string $namespace The app namespace (e.g. 'larpingapp')
     *
     * @return array Array of template objects
     *
     * @throws Exception If listing fails
     */
    public function getTemplatesByNamespace(string $namespace): array
    {
        $result = $this->getTemplates(
            filters: ['namespace' => $namespace],
            limit: 100,
            offset: 0
        );

        return $result['results'];

    }//end getTemplatesByNamespace()


}//end class
