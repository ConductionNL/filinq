<?php
/**
 * Open Register Availability Service
 *
 * Owns the "is OpenRegister present and new enough, and how do I get its
 * ObjectService" concern. Extracted from SettingsService so that settings
 * handling is not also responsible for app-manager probing, manifest parsing
 * and DI-container resolution.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;

/**
 * Resolves OpenRegister availability, minimum version and ObjectService
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
class OpenRegisterAvailabilityService
{

    /**
     * The unique identifier for the OpenRegister application
     *
     * @var string The ID of the OpenRegister app
     */
    public const OPENREGISTER_APP_ID = 'openregister';

    /**
     * Fallback minimum OpenRegister version if the manifest cannot be read.
     *
     * The canonical source of truth is `openspec/manifest.yaml`
     * (`dependencies.openregister.minVersion`) per
     * docudesk-adopt-or-abstractions task 1. This constant is only used when
     * the manifest is missing/unreadable so the runtime still has a defensive
     * floor; the manifest validator enforces parity.
     *
     * @var string Fallback minimum required version of OpenRegister.
     */
    public const FALLBACK_MIN_OPENREGISTER_VERSION = '0.2.10';

    /**
     * Cached minimum OpenRegister version resolved from the manifest.
     *
     * @var string|null
     */
    private ?string $cachedMinVersion = null;

    /**
     * OpenRegisterAvailabilityService constructor
     *
     * @param IAppManager        $appManager App manager interface
     * @param ContainerInterface $container  Container for DI
     *
     * @return void
     */
    public function __construct(
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container
    ) {

    }//end __construct()

    /**
     * Checks if OpenRegister is installed and meets version requirements
     *
     * @return bool True if OpenRegister is installed and meets version requirements
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function isInstalled(): bool
    {
        if ($this->appManager->isInstalled(self::OPENREGISTER_APP_ID) === false) {
            return false;
        }

        $currentVersion = $this->appManager->getAppVersion(self::OPENREGISTER_APP_ID);
        return version_compare($currentVersion, $this->getMinVersion(), '>=') === true;

    }//end isInstalled()

    /**
     * Resolve the minimum supported OpenRegister version.
     *
     * Reads `dependencies.openregister.minVersion` from the project's
     * `openspec/manifest.yaml`. Falls back to FALLBACK_MIN_OPENREGISTER_VERSION
     * when the manifest is missing, unreadable, or shaped unexpectedly so the
     * boot path stays defensive. The result is memoised per-instance.
     *
     * @return string Semantic version of the minimum supported OpenRegister.
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function getMinVersion(): string
    {
        if ($this->cachedMinVersion !== null) {
            return $this->cachedMinVersion;
        }

        $manifestPath = dirname(__DIR__, 2).'/openspec/manifest.yaml';
        $minVersion   = self::FALLBACK_MIN_OPENREGISTER_VERSION;
        if (is_file($manifestPath) === true && is_readable($manifestPath) === true) {
            $contents = file_get_contents($manifestPath);
            if (is_string($contents) === true && preg_match(
                '/dependencies:\s*\n(?:\s+#[^\n]*\n)*\s+openregister:\s*\n(?:\s+#[^\n]*\n)*\s+minVersion:\s*["\']?([0-9][0-9A-Za-z\.\-+]*)["\']?/m',
                $contents,
                $matches
            ) === 1
            ) {
                $minVersion = $matches[1];
            }
        }

        $this->cachedMinVersion = $minVersion;
        return $minVersion;

    }//end getMinVersion()

    /**
     * Attempts to retrieve the OpenRegister service from the container
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service
     *
     * @throws \RuntimeException If the service is not available
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function getObjectService(): ?\OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(
            self::OPENREGISTER_APP_ID,
            $this->appManager->getInstalledApps(),
            true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException('OpenRegister service is not available.');

    }//end getObjectService()
}//end class
