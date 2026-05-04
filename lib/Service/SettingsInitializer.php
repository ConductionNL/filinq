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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\AppFramework\Db\DoesNotExistException;
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
     * Retrieve the OpenRegister RegisterMapper from the container
     *
     * @return \OCA\OpenRegister\Db\RegisterMapper The RegisterMapper
     *
     * @throws \RuntimeException If the mapper is not available
     */
    private function getRegisterMapper(): \OCA\OpenRegister\Db\RegisterMapper
    {
        if (in_array(
            self::OPENREGISTER_APP_ID,
            $this->appManager->getInstalledApps(),
            true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Db\RegisterMapper');
        }

        throw new RuntimeException('RegisterMapper is not available.');

    }//end getRegisterMapper()


    /**
     * Retrieve the OpenRegister SchemaMapper from the container
     *
     * @return \OCA\OpenRegister\Db\SchemaMapper The SchemaMapper
     *
     * @throws \RuntimeException If the mapper is not available
     */
    private function getSchemaMapper(): \OCA\OpenRegister\Db\SchemaMapper
    {
        if (in_array(
            self::OPENREGISTER_APP_ID,
            $this->appManager->getInstalledApps(),
            true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Db\SchemaMapper');
        }

        throw new RuntimeException('SchemaMapper is not available.');

    }//end getSchemaMapper()


    /**
     * Load settings from the docudesk_register.json file
     *
     * @return array<string, mixed> The loaded settings configuration
     *
     * @throws \RuntimeException If settings loading fails
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

                // Even when the JSON import is skipped, IAppConfig keys may
                // have been cleared (or never written). Re-apply per-object-type
                // defaults from the existing OpenRegister registers/schemas.
                $this->applyObjectTypeConfigurationDefaults($settings);

                return $results;
            }

            $configurationService->importFromApp(
                appId: $this->appName,
                data: $settings,
                version: $settings['info']['version']
            );

            // Seed IAppConfig keys for every object type declared in the JSON
            // so consent and other endpoints work without the admin having to
            // pick a register/schema in the settings UI on a fresh install.
            $this->applyObjectTypeConfigurationDefaults($settings);

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
     * Seed per-object-type IAppConfig keys from the existing OpenRegister state.
     *
     * Derives a `schemaSlug → registerSlug` map at runtime by walking
     * `$jsonDef['components']['registers'][*]['schemas'][]` so the mapping stays
     * in sync with `lib/Settings/docudesk_register.json` without a hardcoded PHP
     * table. For every (schemaSlug, registerSlug) pair, looks up the actual
     * Register and Schema by slug via `RegisterMapper::find()` /
     * `SchemaMapper::find()` (both accept ID, UUID, or slug) and writes:
     *
     * - `{schemaSlug}_source`   = `'openregister'`
     * - `{schemaSlug}_register` = the integer register ID
     * - `{schemaSlug}_schema`   = the integer schema ID
     *
     * Each `setValueString` call is gated by an empty-check so administrator
     * overrides are preserved across reboots and version bumps. Missing slugs
     * (e.g., a partial import) are skipped with a warning log; failures inside
     * the helper never propagate to `initialize()`.
     *
     * @param array<string, mixed> $jsonDef The parsed `docudesk_register.json` payload.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) Length is dominated by structured logging, not logic.
     */
    private function applyObjectTypeConfigurationDefaults(array $jsonDef): void
    {
        try {
            $registers = $jsonDef['components']['registers'] ?? [];
            if (is_array($registers) === false || $registers === []) {
                $this->logger->info(
                    'Auto-default skipped: no registers declared in JSON',
                    ['app' => $this->appName]
                );
                return;
            }

            // Build the schema-slug → register-slug map by inverting each
            // register's schemas[] list. The map is derived, not hardcoded.
            $schemaToRegister = [];
            foreach ($registers as $registerEntry) {
                if (is_array($registerEntry) === false) {
                    continue;
                }

                $registerSlug = $registerEntry['slug'] ?? null;
                $schemaSlugs  = $registerEntry['schemas'] ?? [];
                if (is_string($registerSlug) === false || is_array($schemaSlugs) === false) {
                    continue;
                }

                foreach ($schemaSlugs as $schemaSlug) {
                    if (is_string($schemaSlug) === true) {
                        $schemaToRegister[$schemaSlug] = $registerSlug;
                    }
                }
            }

            if ($schemaToRegister === []) {
                $this->logger->info(
                    'Auto-default skipped: no schema → register pairs derived from JSON',
                    ['app' => $this->appName]
                );
                return;
            }

            $registerMapper = $this->getRegisterMapper();
            $schemaMapper   = $this->getSchemaMapper();

            foreach ($schemaToRegister as $schemaSlug => $registerSlug) {
                try {
                    $register = $registerMapper->find(id: $registerSlug);
                } catch (DoesNotExistException $e) {
                    $this->logger->warning(
                        'Auto-default skipped schema: register not found',
                        [
                            'app'          => $this->appName,
                            'schemaSlug'   => $schemaSlug,
                            'registerSlug' => $registerSlug,
                        ]
                    );
                    continue;
                }

                try {
                    $schema = $schemaMapper->find(id: $schemaSlug);
                } catch (DoesNotExistException $e) {
                    $this->logger->warning(
                        'Auto-default skipped schema: schema not found',
                        [
                            'app'          => $this->appName,
                            'schemaSlug'   => $schemaSlug,
                            'registerSlug' => $registerSlug,
                        ]
                    );
                    continue;
                }

                $writes = [
                    "{$schemaSlug}_source"   => 'openregister',
                    "{$schemaSlug}_register" => (string) $register->getId(),
                    "{$schemaSlug}_schema"   => (string) $schema->getId(),
                ];

                foreach ($writes as $key => $value) {
                    $current = $this->config->getValueString($this->appName, $key, '');
                    if ($current !== '') {
                        $this->logger->info(
                            'Preserving existing override for '.$key,
                            ['app' => $this->appName, 'key' => $key]
                        );
                        continue;
                    }

                    $this->config->setValueString($this->appName, $key, $value);
                    $this->logger->info(
                        'Auto-default wrote IAppConfig key',
                        [
                            'app'   => $this->appName,
                            'key'   => $key,
                            'value' => $value,
                        ]
                    );
                }
            }//end foreach
        } catch (Exception $e) {
            // Helper failure must never break app boot.
            $this->logger->error(
                'Auto-default helper failed: '.$e->getMessage(),
                ['app' => $this->appName, 'exception' => $e]
            );
        }//end try

    }//end applyObjectTypeConfigurationDefaults()


}//end class
