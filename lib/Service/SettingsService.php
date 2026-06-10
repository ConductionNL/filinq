<?php
/**
 * Settings Service
 *
 * Service for handling settings-related operations in DocuDesk.
 * Provides functionality for retrieving and saving settings.
 * Delegates initialization to SettingsInitializer and register
 * discovery to RegisterDiscoveryService.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-27
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-64
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-65
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-66
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\IAppConfig;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling settings-related operations in DocuDesk
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SettingsService
{

    /**
     * The application name for identification and configuration purposes
     *
     * @var string The name of the app
     */
    private readonly string $appName;

    /**
     * The unique identifier for the OpenRegister application
     *
     * @var string The ID of the OpenRegister app
     */
    private const OPENREGISTER_APP_ID = 'openregister';

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
    private const FALLBACK_MIN_OPENREGISTER_VERSION = '0.2.10';

    /**
     * Cached minimum OpenRegister version resolved from the manifest.
     *
     * @var string|null
     */
    private ?string $minOpenRegisterVersion = null;

    /**
     * SettingsService constructor
     *
     * @param IAppConfig               $config           App configuration interface
     * @param ContainerInterface       $container        Container for DI
     * @param IAppManager              $appManager       App manager interface
     * @param LoggerInterface          $logger           Logger interface
     * @param RegisterDiscoveryService $discoveryService Register discovery service
     * @param SettingsInitializer      $initializer      Settings initializer
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly LoggerInterface $logger,
        private readonly RegisterDiscoveryService $discoveryService,
        private readonly SettingsInitializer $initializer
    ) {
        $this->appName = 'docudesk';

    }//end __construct()

    /**
     * Checks if OpenRegister is installed and meets version requirements
     *
     * @return bool True if OpenRegister is installed and meets version requirements
     */
    private function isOpenRegisterInstalled(): bool
    {
        if ($this->appManager->isInstalled(self::OPENREGISTER_APP_ID) === false) {
            return false;
        }

        $currentVersion = $this->appManager->getAppVersion(self::OPENREGISTER_APP_ID);
        return version_compare($currentVersion, $this->getMinOpenRegisterVersion(), '>=') === true;

    }//end isOpenRegisterInstalled()

    /**
     * Resolve the minimum supported OpenRegister version.
     *
     * Reads `dependencies.openregister.minVersion` from the project's
     * `openspec/manifest.yaml`. Falls back to FALLBACK_MIN_OPENREGISTER_VERSION
     * when the manifest is missing, unreadable, or shaped unexpectedly so the
     * boot path stays defensive. The result is memoised per-instance.
     *
     * @return string Semantic version of the minimum supported OpenRegister.
     */
    private function getMinOpenRegisterVersion(): string
    {
        if ($this->minOpenRegisterVersion !== null) {
            return $this->minOpenRegisterVersion;
        }

        $manifestPath = dirname(__DIR__, 2).'/openspec/manifest.yaml';
        $minVersion   = self::FALLBACK_MIN_OPENREGISTER_VERSION;
        if (is_file($manifestPath) === true && is_readable($manifestPath) === true) {
            $contents = file_get_contents($manifestPath);
            if (is_string($contents) === true && preg_match(
                '/dependencies:\s*\n(?:\s+#[^\n]*\n)*\s+openregister:\s*\n(?:\s+#[^\n]*\n)*\s+minVersion:\s*["\']?([0-9][0-9A-Za-z\.\-+]*)["\']?/m',
                $contents,
                $matches
            ) === 1) {
                $minVersion = $matches[1];
            }
        }

        $this->minOpenRegisterVersion = $minVersion;
        return $minVersion;

    }//end getMinOpenRegisterVersion()

    /**
     * Attempts to retrieve the OpenRegister service from the container
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service
     *
     * @throws \RuntimeException If the service is not available
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-65
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

    /**
     * Initializes the app with all required components
     *
     * @return array<string, mixed> The initialization results
     *
     * @throws \RuntimeException If initialization fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-64
     */
    public function initialize(): array
    {
        return $this->initializer->initialize();

    }//end initialize()

    /**
     * Load feature toggle settings from app config
     *
     * @return array<string, mixed> Feature toggle settings
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-66
     */
    private function loadFeatureToggles(): array
    {
        return [
            'publication_objection_period_days'            => (int) $this->config->getValueString(
                $this->appName,
                'publication_objection_period_days',
                '28'
            ),
            'enable_language_detection'                    => $this->config->getValueString(
                $this->appName,
                'enable_language_detection',
                '1'
            ) === '1',
            'enable_keyword_extraction'                    => $this->config->getValueString(
                $this->appName,
                'enable_keyword_extraction',
                '1'
            ) === '1',
            'enable_topic_classification'                  => $this->config->getValueString(
                $this->appName,
                'enable_topic_classification',
                '1'
            ) === '1',
            'signing_enabled'                              => $this->config->getValueString(
                $this->appName,
                'signing_enabled',
                '0'
            ) === '1',
            'signing_provider'                             => $this->config->getValueString(
                $this->appName,
                'signing_provider',
                'native'
            ),
            'signing_default_level'                        => $this->config->getValueString(
                $this->appName,
                'signing_default_level',
                'SES'
            ),
            'signing_request_expiry_days'                  => (int) $this->config->getValueString(
                $this->appName,
                'signing_request_expiry_days',
                '30'
            ),
            // Anonymise-output-as-pdf-by-default — tenant-wide default
            // for the anonymise endpoint's `outputFormat` request param.
            // 'pdf' converts the anonymised output via the cascade;
            // 'preserve' returns it in the native input format.
            'docudesk.anonymisation.default_output_format' => $this->config->getValueString(
                $this->appName,
                'docudesk.anonymisation.default_output_format',
                'pdf'
            ),
        ];

    }//end loadFeatureToggles()

    /**
     * Retrieve all settings
     *
     * @return array<string, mixed> The current settings configuration
     *
     * @throws RuntimeException If settings retrieval fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-65
     */
    public function getAllSettings(): array
    {
        $data = [
            'objectTypes'        => ['publicationConsent', 'template'],
            'openRegisters'      => false,
            'availableRegisters' => [],
        ];

        try {
            if ($this->isOpenRegisterInstalled() === true) {
                $data['openRegisters']      = true;
                $data['availableRegisters'] = $this->discoveryService->fetchAvailableRegisters();
            }
        } catch (\RuntimeException $e) {
            $this->logger->info(
                'OpenRegister service not available',
                ['exception' => $e->getMessage()]
            );
        }//end try

        try {
            $data['configuration'] = $this->discoveryService->loadObjectTypeConfiguration(
                $data['objectTypes']
            );
            $data = array_merge($data, $this->loadFeatureToggles());

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to retrieve settings: '.$e->getMessage());
        }//end try

    }//end getAllSettings()

    /**
     * Convert a setting value to string for storage
     *
     * @param mixed $value The value to convert
     *
     * @return string The string representation
     */
    private function convertValueToString(mixed $value): string
    {
        if (is_array($value) === true || is_object($value) === true) {
            return json_encode($value);
        }

        return (string) $value;

    }//end convertValueToString()

    /**
     * Keys that are permitted to be written via the settings endpoint.
     *
     * This allowlist prevents any authenticated user (wave-3 C1) from
     * overwriting security-sensitive keys such as signing_verification_secret
     * through the open settings POST endpoint.  Secret keys (signing_* tokens
     * etc.) must be managed through dedicated, separately-secured endpoints.
     *
     * @var array<int, string>
     */
    private const WRITABLE_KEYS = [
        'publicationConsent_register',
        'publicationConsent_schema',
        'publicationConsent_source',
        'template_register',
        'template_schema',
        'template_source',
        'publication_objection_period_days',
        'enable_language_detection',
        'enable_keyword_extraction',
        'enable_topic_classification',
        'signing_enabled',
        'signing_provider',
        'signing_default_level',
        'signing_request_expiry_days',
    ];

    /**
     * Update the settings configuration
     *
     * Only keys present in WRITABLE_KEYS may be written; all other keys are
     * silently skipped.  This prevents escalation via security-sensitive keys
     * such as signing_verification_secret (wave-3 C1).
     *
     * @param array<string, mixed> $data The settings data to update
     *
     * @return array<string, mixed> The updated settings configuration
     *
     * @throws \RuntimeException If settings update fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-27
     */
    public function updateSettings(array $data): array
    {
        try {
            foreach ($data as $key => $value) {
                if (empty($key) === true) {
                    $this->logger->warning(
                        'Skipping empty key in updateSettings',
                        ['value' => $value]
                    );
                    continue;
                }

                if (in_array($key, self::WRITABLE_KEYS, true) === false) {
                    $this->logger->warning(
                        'Skipping non-allowlisted key in updateSettings',
                        ['key' => $key]
                    );
                    unset($data[$key]);
                    continue;
                }

                $stringValue = $this->convertValueToString(value: $value);
                $this->config->setValueString($this->appName, $key, $stringValue);
                $data[$key] = $this->config->getValueString($this->appName, $key);
            }//end foreach

            $this->logger->info(
                'Settings updated successfully',
                ['updatedKeys' => array_keys($data)]
            );

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to update settings: '.$e->getMessage());
        }//end try

    }//end updateSettings()
}//end class
