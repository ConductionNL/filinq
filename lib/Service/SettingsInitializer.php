<?php
/**
 * Settings Initializer
 *
 * Service for initializing the DocuDesk app configuration from
 * the settings JSON file. Handles version checking and importing
 * configuration into OpenRegister. Extracted from SettingsService
 * to reduce class complexity.
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
 * @spec openspec/specs/admin-settings/spec.md
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
 * Service for initializing DocuDesk configuration from JSON settings
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SettingsInitializer
{

    /**
     * The application name
     *
     * @var string
     */
    private readonly string $appName;

    /**
     * The unique identifier for the OpenRegister application
     *
     * @var string
     */
    private const OPENREGISTER_APP_ID = 'openregister';

    /**
     * The minimum version of the OpenRegister application required
     *
     * @var string
     */
    private const MIN_OPENREGISTER_VERSION = '0.2.10';

    /**
     * Constructor for SettingsInitializer
     *
     * @param IAppConfig         $config     App configuration interface
     * @param ContainerInterface $container  Container for dependency injection
     * @param IAppManager        $appManager App manager interface
     * @param LoggerInterface    $logger     Logger interface
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly LoggerInterface $logger
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
        return version_compare($currentVersion, self::MIN_OPENREGISTER_VERSION, '>=') === true;

    }//end isOpenRegisterInstalled()

    /**
     * Checks if OpenRegister is enabled
     *
     * @return bool True if OpenRegister is enabled
     */
    private function isOpenRegisterEnabled(): bool
    {
        return $this->appManager->isEnabledForUser(self::OPENREGISTER_APP_ID);

    }//end isOpenRegisterEnabled()

    /**
     * Attempts to retrieve the Configuration service from the container
     *
     * @return \OCA\OpenRegister\Service\ConfigurationService The Configuration service
     *
     * @throws \RuntimeException If the service is not available
     */
    private function getConfigurationService(): \OCA\OpenRegister\Service\ConfigurationService
    {
        if (in_array(
            self::OPENREGISTER_APP_ID,
            $this->appManager->getInstalledApps(),
            true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Service\ConfigurationService');
        }

        throw new RuntimeException('Configuration service is not available.');

    }//end getConfigurationService()

    /**
     * Load settings from the docudesk_register.json file
     *
     * @return array<string, mixed> The loaded settings configuration
     *
     * @throws \RuntimeException If settings loading fails
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    private function loadSettings(): array
    {
        $settingsFilePath = __DIR__.'/../Settings/docudesk_register.json';

        try {
            if (file_exists($settingsFilePath) === false) {
                throw new RuntimeException('Settings file not found at: '.$settingsFilePath);
            }

            $jsonContent = file_get_contents($settingsFilePath);
            if ($jsonContent === false) {
                throw new RuntimeException('Failed to read settings file');
            }

            $settings = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Error decoding JSON: '.json_last_error_msg());
            }

            if (isset($settings['info']['version']) === false) {
                throw new RuntimeException(
                    'Settings file does not contain version information'
                );
            }

            return $settings;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to load settings: '.$e->getMessage());
        }//end try

    }//end loadSettings()

    /**
     * Initializes the app with all required components
     *
     * @return array<string, mixed> The initialization results
     *
     * @throws \RuntimeException If initialization fails
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function initialize(): array
    {
        $results = [
            'configuration' => false,
            'errors'        => [],
            'info'          => [],
        ];

        try {
            if ($this->isOpenRegisterInstalled() === false) {
                throw new RuntimeException(
                    'OpenRegister is not installed or version is too low'
                );
            }

            if ($this->isOpenRegisterEnabled() === false) {
                throw new RuntimeException('OpenRegister is not enabled');
            }

            try {
                $configurationService = $this->getConfigurationService();
            } catch (Exception $e) {
                throw new RuntimeException(
                    'OpenRegister configuration service is not available: '.$e->getMessage()
                );
            }

            $currentVersion = $this->config->getValueString(
                $this->appName,
                'configuration_version',
                '0.0.0'
            );
            $settings       = $this->loadSettings();

            // Idempotently ensure the templateVersion register/schema config
            // keys exist. Run before the version gate so existing installs
            // (already at the current config version) get back-filled without
            // requiring a config-version bump.
            $this->provisionTemplateVersionConfig();

            if (version_compare(
                $settings['info']['version'],
                $currentVersion,
                '<='
            ) === true
            ) {
                $settingsVersion   = $settings['info']['version'];
                $message           = 'Configuration version '.$currentVersion;
                $message          .= ' is up to date or newer than '.$settingsVersion;
                $results['info'][] = $message;
                return $results;
            }

            $configurationService->importFromApp(
                appId: $this->appName,
                data: $settings,
                version: $settings['info']['version']
            );

            // Persist the version we just imported. Nothing else writes this
            // key — not this app, not OpenRegister — so without it
            // `$currentVersion` above stays at its '0.0.0' default forever, the
            // version gate can never close, and this import runs on EVERY
            // request. It is reached from Application::boot(), so that is every
            // request to the whole instance, not just DocuDesk's own.
            //
            // Measured 2026-07-29 on the dev instance, an OpenRegister object
            // create: 354ms median with the key absent, 255ms with it set —
            // ~100ms per request, ~28%, plus 14 schema lookups.
            $this->config->setValueString(
                $this->appName,
                'configuration_version',
                (string) $settings['info']['version']
            );

            $results['configuration'] = true;
            $results['info'][]        = 'Configuration updated to version '.$settings['info']['version'];
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->logger->error(
                'Failed to initialize DocuDesk: '.$e->getMessage(),
                ['app' => $this->appName]
            );
        }//end try

        return $results;

    }//end initialize()

    /**
     * Provision the templateVersion register/schema app-config keys
     *
     * Template editing writes a version-history snapshot before saving, which
     * needs the `templateVersion_register` and `templateVersion_schema` app
     * config keys. Unlike `template_*`, these were never provisioned, so every
     * template update threw RegisterNotConfiguredException and surfaced a 500.
     *
     * The `templateVersion` schema lives in the same register(s) as
     * `template`. This method resolves the schema ID from OpenRegister by slug
     * and prefers to co-locate version objects in the same register the
     * templates themselves use (`template_register`), falling back to whichever
     * register holds the `templateVersion` schema. It is idempotent: keys that
     * are already populated are left untouched, and any resolution failure is
     * logged without aborting initialization.
     *
     * @return void
     *
     * @spec openspec/specs/template-management/spec.md
     */
    private function provisionTemplateVersionConfig(): void
    {
        $existingRegister = $this->config->getValueString($this->appName, 'templateVersion_register', '');
        $existingSchema   = $this->config->getValueString($this->appName, 'templateVersion_schema', '');

        if (empty($existingRegister) === false && empty($existingSchema) === false) {
            // Already provisioned — nothing to do.
            return;
        }

        try {
            $registerService = $this->container->get('OCA\OpenRegister\Service\RegisterService');
            $schemaMapper    = $this->container->get('OCA\OpenRegister\Db\SchemaMapper');

            $registers = $registerService->findAll(
                limit: null,
                offset: null,
                filters: [],
                searchConditions: [],
                searchParams: []
            );

            // Prefer the register the templates themselves live in so version
            // snapshots are co-located; fall back to any register that holds
            // the templateVersion schema.
            $templateRegister   = $this->config->getValueString($this->appName, 'template_register', '');
            $fallbackRegisterId = '';
            $schemaId           = '';

            foreach ($registers as $register) {
                $registerArr = $register->jsonSerialize();
                $registerId  = (string) ($registerArr['id'] ?? '');

                foreach (($registerArr['schemas'] ?? []) as $candidateSchemaId) {
                    try {
                        $schema    = $schemaMapper->find(id: $candidateSchemaId);
                        $schemaArr = $schema->jsonSerialize();
                    } catch (Exception $schemaError) {
                        continue;
                    }

                    if (($schemaArr['slug'] ?? '') !== 'templateVersion') {
                        continue;
                    }

                    $schemaId = (string) ($schemaArr['id'] ?? '');

                    if ($registerId === $templateRegister) {
                        // Exact co-location with the templates register.
                        $this->writeTemplateVersionConfig(registerId: $registerId, schemaId: $schemaId);
                        return;
                    }

                    if ($fallbackRegisterId === '') {
                        $fallbackRegisterId = $registerId;
                    }
                }//end foreach
            }//end foreach

            if ($fallbackRegisterId !== '' && $schemaId !== '') {
                $this->writeTemplateVersionConfig(registerId: $fallbackRegisterId, schemaId: $schemaId);
                return;
            }

            $this->logger->warning(
                'templateVersion schema not found in any register; '
                .'template version history will be unavailable'
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to provision templateVersion config: '.$e->getMessage(),
                ['app' => $this->appName]
            );
        }//end try

    }//end provisionTemplateVersionConfig()

    /**
     * Write the resolved templateVersion register/schema config keys
     *
     * @param string $registerId The OpenRegister register ID to store.
     * @param string $schemaId   The templateVersion schema ID to store.
     *
     * @return void
     */
    private function writeTemplateVersionConfig(string $registerId, string $schemaId): void
    {
        $this->config->setValueString($this->appName, 'templateVersion_register', $registerId);
        $this->config->setValueString($this->appName, 'templateVersion_schema', $schemaId);
        $this->config->setValueString($this->appName, 'templateVersion_source', 'openregister');

        $this->logger->info(
            'Provisioned templateVersion register/schema config',
            [
                'register' => $registerId,
                'schema'   => $schemaId,
            ]
        );

    }//end writeTemplateVersionConfig()
}//end class
