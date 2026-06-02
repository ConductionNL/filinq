<?php
/**
 * Template Service
 *
 * Service for managing reusable Twig/HTML templates stored as OpenRegister objects.
 * Templates are scoped per-app via a namespace field, enabling multiple apps
 * to maintain their own template collections.
 * Supports versioning, categories, tags, duplication, and optimistic locking.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-29
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-67
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-68
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-69
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTime;
use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;

/**
 * Service for CRUD operations on document templates via OpenRegister
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TemplateService
{

    /**
     * Lock timeout in minutes
     *
     * @var int
     */
    private const LOCK_TIMEOUT_MINUTES = 15;

    /**
     * Constructor for TemplateService
     *
     * @param ContainerInterface     $container        Container for dependency injection
     * @param IAppManager            $appManager       App manager interface
     * @param OpenRegisterResolver   $registerResolver Resolver for register/schema config
     * @param TemplateVersionService $versionService   Service for template version management
     * @param IUserSession           $userSession      User session for getting current user
     *
     * @return void
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly OpenRegisterResolver $registerResolver,
        private readonly TemplateVersionService $versionService,
        private readonly IUserSession $userSession
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
     * Get the current user ID from the session
     *
     * @return string The current user ID or 'system'
     */
    private function getCurrentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user !== null) {
            return $user->getUID();
        }

        return 'system';

    }//end getCurrentUserId()

    /**
     * List templates with optional filters
     *
     * Supports namespace, category, and tags filter parameters.
     *
     * @param array $filters Optional filters (e.g. namespace, category, tags, _search)
     * @param int   $limit   Maximum number of results (default: 20)
     * @param int   $offset  Result offset for pagination (default: 0)
     *
     * @return array{results: array, total: int} Paginated template results
     *
     * @throws Exception If listing fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-68
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-67
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-29
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
     * Update an existing template with version history
     *
     * The namespace field cannot be changed after creation.
     * A version snapshot of the current state is created before updating.
     *
     * @param string $id   The template UUID
     * @param array  $data Updated template data
     *
     * @return array The updated template object
     *
     * @throws Exception If the template is not found or update fails
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-29
     */
    public function updateTemplate(string $id, array $data): array
    {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getRegisterAndSchema();

        $existing = $this->getTemplate(id: $id);

        // Create a version snapshot of the current state before updating.
        $editor    = $this->getCurrentUserId();
        $changelog = $data['_changelog'] ?? null;
        unset($data['_changelog']);

        $this->versionService->createVersion(
            templateId: $id,
            templateState: $existing,
            editor: $editor,
            changelog: $changelog
        );

        // Namespace is immutable after creation.
        unset($data['namespace']);

        $data['id'] = $id;
        $merged     = array_merge($existing, $data);

        // Release lock after successful save.
        $merged['lockedBy'] = null;
        $merged['lockedAt'] = null;

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
     * Update a template without creating a version (used for restore operations)
     *
     * @param string $id   The template UUID
     * @param array  $data Updated template data
     *
     * @return array The updated template object
     *
     * @throws Exception If the template is not found or update fails
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-29
     */
    public function updateTemplateWithoutVersion(string $id, array $data): array
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

    }//end updateTemplateWithoutVersion()

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-29
     */
    public function deleteTemplate(string $id): bool
    {
        $objectService = $this->getObjectService();

        $objectService->deleteObject(uuid: $id);

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-69
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

    /**
     * Duplicate a template
     *
     * Creates a copy with name suffixed " (kopie)", a new UUID,
     * and no version history. Preserves namespace, category, and tags.
     *
     * @param string $id The UUID of the template to duplicate
     *
     * @return array The duplicated template object
     *
     * @throws Exception If the template is not found or duplication fails
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-3
     */
    public function duplicateTemplate(string $id): array
    {
        $original = $this->getTemplate(id: $id);

        $duplicateData = [
            'name'        => $original['name'].' (kopie)',
            'description' => $original['description'] ?? '',
            'content'     => $original['content'] ?? '',
            'namespace'   => $original['namespace'],
            'format'      => $original['format'] ?? 'A4',
            'orientation' => $original['orientation'] ?? 'P',
            'category'    => $original['category'] ?? '',
            'tags'        => $original['tags'] ?? [],
        ];

        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getRegisterAndSchema();

        $result = $objectService->saveObject(
            object: $duplicateData,
            register: $config['register'],
            schema: $config['schema']
        );

        if (is_object($result) === true
            && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
        ) {
            return $result->jsonSerialize();
        }

        return $result;

    }//end duplicateTemplate()

    /**
     * Acquire an edit lock on a template
     *
     * Sets lockedBy and lockedAt if the template is not currently locked
     * or if the existing lock has expired (older than 15 minutes).
     *
     * @param string $id     The template UUID
     * @param string $userId The Nextcloud user ID requesting the lock
     *
     * @return array The updated template object with lock information
     *
     * @throws Exception If the template is locked by another user (code 409)
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-4
     */
    public function acquireLock(string $id, string $userId): array
    {
        $template = $this->getTemplate(id: $id);

        // Check if already locked by another user.
        if (empty($template['lockedBy']) === false && $template['lockedBy'] !== $userId) {
            // Check if lock has expired.
            if ($this->isLockExpired(template: $template) === false) {
                throw new Exception(
                    message: json_encode(
                        [
                            'error'    => 'Template is locked by another user',
                            'lockedBy' => $template['lockedBy'],
                            'lockedAt' => $template['lockedAt'],
                        ]
                    ),
                    code: 409
                );
            }
        }

        // Acquire or refresh the lock.
        $now = (new DateTime())->format('c');

        return $this->updateTemplateWithoutVersion(
            id: $id,
            data: [
                'lockedBy' => $userId,
                'lockedAt' => $now,
            ]
        );

    }//end acquireLock()

    /**
     * Release an edit lock on a template
     *
     * Clears the lockedBy and lockedAt fields if the lock is held by the given user.
     *
     * @param string $id     The template UUID
     * @param string $userId The Nextcloud user ID releasing the lock
     *
     * @return array The updated template object with lock cleared
     *
     * @throws Exception If the template is not locked by this user
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-4
     */
    public function releaseLock(string $id, string $userId): array
    {
        $template = $this->getTemplate(id: $id);

        // Only the lock holder can release (or if lock is expired).
        if (empty($template['lockedBy']) === false
            && $template['lockedBy'] !== $userId
            && $this->isLockExpired(template: $template) === false
        ) {
            throw new Exception(
                message: 'Cannot release lock held by another user',
                code: 403
            );
        }

        return $this->updateTemplateWithoutVersion(
            id: $id,
            data: [
                'lockedBy' => null,
                'lockedAt' => null,
            ]
        );

    }//end releaseLock()

    /**
     * Check if a template's lock has expired.
     *
     * @param array $template The template data with lockedAt field
     *
     * @return bool True if the lock has expired or no lock exists
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-4
     */
    private function isLockExpired(array $template): bool
    {
        if (empty($template['lockedAt']) === true) {
            return true;
        }

        try {
            $lockedAt = new DateTime($template['lockedAt']);
            $now      = new DateTime();
            $diffMins = ($now->getTimestamp() - $lockedAt->getTimestamp()) / 60;

            return $diffMins > self::LOCK_TIMEOUT_MINUTES;
        } catch (Exception $exception) {
            // If we cannot parse the lock time, consider it expired.
            return true;
        }

    }//end isLockExpired()
}//end class
