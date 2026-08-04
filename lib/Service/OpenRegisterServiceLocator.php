<?php
/**
 * Open Register Service Locator
 *
 * Resolves OpenRegister services and mappers by class name, guarded by the
 * app-enabled check. Extracted from AnonymizationService so that every
 * collaborator of the anonymisation pipeline can reach OpenRegister without
 * each of them depending on both IAppManager and the DI container.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Resolves OpenRegister services/mappers, or fails loudly when OR is absent.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class OpenRegisterServiceLocator
{
    /**
     * Constructor for OpenRegisterServiceLocator
     *
     * @param IAppManager        $appManager App manager used to check whether OpenRegister is installed.
     * @param ContainerInterface $container  Container the OpenRegister service is resolved from.
     *
     * @return void
     */
    public function __construct(
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container
    ) {

    }//end __construct()

    /**
     * Get an OpenRegister service or mapper by class name.
     *
     * @param string $className The fully qualified class name.
     *
     * @return mixed The service instance.
     *
     * @throws RuntimeException If OpenRegister is not available.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function get(string $className): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get($className);
        }

        throw new RuntimeException($className.' is not available.');

    }//end get()

    /**
     * Read OpenRegister's best-effort residual-entity list, when it exposes one.
     *
     * OpenRegister produces the anonymised file even when some entity text could
     * not be removed (e.g. the ExApp NER over-captured a span across table cells,
     * so the value is not contiguous in the document). Pulling the residual list
     * lets the operator be warned and iterate. Defensive method_exists() guard
     * for older OpenRegister versions without the best-effort API.
     *
     * @param mixed $fileService OpenRegister FileService (resolved reflectively).
     *
     * @return array<int, mixed> The residual entities, or an empty array.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function lastResidualEntities(mixed $fileService): array
    {
        if (method_exists($fileService, 'getLastResidualEntities') === true) {
            return $fileService->getLastResidualEntities();
        }

        return [];

    }//end lastResidualEntities()

    /**
     * Read OpenRegister's per-entity placeholder map, when it exposes one.
     *
     * The EXACT placeholder OpenRegister emitted per global entity id (e.g.
     * `"7" => "[PERSOON: 1]"`), so the grondslagen summary renders the same
     * scope-local number + localized label the document carries instead of
     * re-deriving `[<TYPE>: <entity_id>]`. Defensive method_exists() for older
     * OpenRegister versions (the summary then falls back to its own scope-local
     * map, or omits the entity).
     *
     * @param mixed $fileService OpenRegister FileService (resolved reflectively).
     *
     * @return array<string, string> The placeholder map, or an empty array.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function lastPlaceholderMap(mixed $fileService): array
    {
        if (method_exists($fileService, 'getLastPlaceholderMap') === true) {
            return $fileService->getLastPlaceholderMap();
        }

        return [];

    }//end lastPlaceholderMap()
}//end class
