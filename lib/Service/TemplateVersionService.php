<?php
/**
 * Template Version Service
 *
 * Service for managing template version history stored as OpenRegister objects.
 * Each version captures the state of a template before an update, enabling
 * rollback, comparison, and audit trail of template changes.
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
 * Service for CRUD operations on template versions via OpenRegister
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TemplateVersionService
{
    /**
     * Constructor for TemplateVersionService
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
     * Create a version snapshot of a template's current state
     *
     * @param string      $templateId    The UUID of the parent template
     * @param array       $templateState The current template data to capture
     * @param string      $editor        The Nextcloud user ID who made the edit
     * @param string|null $changelog     Optional note describing the change
     *
     * @return array The created version object
     *
     * @throws Exception If version creation fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-1
     */
    public function createVersion(
        string $templateId,
        array $templateState,
        string $editor,
        ?string $changelog=null
    ): array {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getVersionRegisterAndSchema();
        $versionNumber = $this->getNextVersionNumber(templateId: $templateId);

        $versionData = [
            'templateId'  => $templateId,
            'version'     => $versionNumber,
            'content'     => $templateState['content'] ?? '',
            'name'        => $templateState['name'] ?? '',
            'description' => $templateState['description'] ?? '',
            'format'      => $templateState['format'] ?? 'A4',
            'orientation' => $templateState['orientation'] ?? 'P',
            'editor'      => $editor,
            'changelog'   => $changelog ?? '',
        ];

        $result = $objectService->saveObject(
            object: $versionData,
            register: $config['register'],
            schema: $config['schema']
        );

        if (is_object($result) === true
            && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
        ) {
            return $result->jsonSerialize();
        }

        return $result;

    }//end createVersion()

    /**
     * List versions for a template, ordered by version number descending
     *
     * @param string $templateId The UUID of the parent template
     * @param int    $limit      Maximum number of results (default: 20)
     * @param int    $offset     Result offset for pagination (default: 0)
     *
     * @return array{results: array, total: int} Paginated version results
     *
     * @throws Exception If listing fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-1
     */
    public function getVersions(string $templateId, int $limit=20, int $offset=0): array
    {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getVersionRegisterAndSchema();

        $requestParams = [
            'templateId' => $templateId,
            '_limit'     => $limit,
            '_offset'    => $offset,
            '_order'     => ['version' => 'desc'],
        ];

        $query = $objectService->buildSearchQuery(
            requestParams: $requestParams,
            register: $config['register'],
            schema: $config['schema']
        );

        return $objectService->searchObjectsPaginated(query: $query);

    }//end getVersions()

    /**
     * Get a single version by UUID
     *
     * @param string $versionId The version UUID
     *
     * @return array The version object
     *
     * @throws Exception If the version is not found
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-1
     */
    public function getVersion(string $versionId): array
    {
        $objectService = $this->getObjectService();
        $config        = $this->registerResolver->getVersionRegisterAndSchema();

        $result = $objectService->find(
            id: $versionId,
            register: $config['register'],
            schema: $config['schema']
        );

        if (empty($result) === true) {
            throw new Exception(message: 'Version not found', code: 404);
        }

        if (is_object($result) === true
            && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
        ) {
            return $result->jsonSerialize();
        }

        return $result;

    }//end getVersion()

    /**
     * Get the next version number for a template
     *
     * @param string $templateId The UUID of the parent template
     *
     * @return int The next version number (existing count + 1)
     *
     * @throws Exception If counting fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-1
     */
    public function getNextVersionNumber(string $templateId): int
    {
        $result = $this->getVersions(
            templateId: $templateId,
            limit: 1,
            offset: 0
        );

        return $result['total'] + 1;

    }//end getNextVersionNumber()

    /**
     * Restore a template to a previous version
     *
     * Saves the current template state as a new version, then updates the
     * template with the content from the target version.
     *
     * @param string          $templateId The UUID of the template to restore
     * @param string          $versionId  The UUID of the version to restore to
     * @param string          $editor     The Nextcloud user ID performing the restore
     * @param TemplateService $service    The template service for updating the template
     *
     * @return array The restored template object
     *
     * @throws Exception If restore fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-1
     */
    public function restoreVersion(
        string $templateId,
        string $versionId,
        string $editor,
        TemplateService $service
    ): array {
        $targetVersion = $this->getVersion(versionId: $versionId);
        $currentState  = $service->getTemplate(id: $templateId);

        // Save current state as a new version before restoring.
        $this->createVersion(
            templateId: $templateId,
            templateState: $currentState,
            editor: $editor,
            changelog: 'Auto-saved before restore to version '.$targetVersion['version']
        );

        // Restore the target version's data to the template.
        $restoreData = [
            'content'     => $targetVersion['content'],
            'name'        => $targetVersion['name'],
            'description' => $targetVersion['description'] ?? '',
            'format'      => $targetVersion['format'] ?? 'A4',
            'orientation' => $targetVersion['orientation'] ?? 'P',
        ];

        return $service->updateTemplateWithoutVersion(
            id: $templateId,
            data: $restoreData
        );

    }//end restoreVersion()

    /**
     * Get two versions for client-side diff comparison
     *
     * @param string $versionIdFrom The UUID of the source version
     * @param string $versionIdTo   The UUID of the target version
     *
     * @return array{from: array, to: array} Both version objects
     *
     * @throws Exception If either version is not found
     *
     * @spec openspec/changes/retrofit-2026-05-24-template-management/tasks.md#task-2
     */
    public function getDiff(string $versionIdFrom, string $versionIdTo): array
    {
        $from = $this->getVersion(versionId: $versionIdFrom);
        $to   = $this->getVersion(versionId: $versionIdTo);

        return [
            'from' => $from,
            'to'   => $to,
        ];

    }//end getDiff()
}//end class
