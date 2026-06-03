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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-63
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-64
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-63
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-64
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
                return $results;
            }

            $configurationService->importFromApp(
                appId: $this->appName,
                data: $settings,
                version: $settings['info']['version']
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
}//end class
