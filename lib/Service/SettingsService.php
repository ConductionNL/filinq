<?php
/**
 * Settings Service
 *
 * Service for handling settings-related operations in DocuDesk.
 * Provides functionality for retrieving, saving, and loading settings,
 * as well as managing configuration for OpenRegister integration.
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
use TypeError;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use OCA\OpenRegister\Service\RegisterService;

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
     * The minimum version of the OpenRegister application required
     *
     * @var string The minimum required version of OpenRegister
     */
    private const MIN_OPENREGISTER_VERSION = '0.2.10';


    /**
     * SettingsService constructor
     *
     * @param IAppConfig         $config          App configuration interface
     * @param IRequest           $request         Request interface
     * @param ContainerInterface $container       Container for dependency injection
     * @param IAppManager        $appManager      App manager interface
     * @param LoggerInterface    $logger          Logger interface
     * @param RegisterService    $registerService Register service for getting registers
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly IRequest $request,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly LoggerInterface $logger,
        private readonly RegisterService $registerService
    ) {
        $this->appName = 'docudesk';

    }//end __construct()


    /**
     * Checks if OpenRegister is installed and meets version requirements
     *
     * @param string|null $minVersion Minimum required version
     *
     * @return bool True if OpenRegister is installed and meets version requirements
     */
    public function isOpenRegisterInstalled(?string $minVersion=self::MIN_OPENREGISTER_VERSION): bool
    {
        if ($this->appManager->isInstalled(self::OPENREGISTER_APP_ID) === false) {
            return false;
        }

        if ($minVersion === null) {
            return true;
        }

        $currentVersion = $this->appManager->getAppVersion(self::OPENREGISTER_APP_ID);
        return version_compare($currentVersion, $minVersion, '>=') === true;

    }//end isOpenRegisterInstalled()


    /**
     * Checks if OpenRegister is enabled
     *
     * @return bool True if OpenRegister is enabled
     */
    public function isOpenRegisterEnabled(): bool
    {
        return $this->appManager->isEnabledForUser(self::OPENREGISTER_APP_ID);

    }//end isOpenRegisterEnabled()


    /**
     * Attempts to retrieve the OpenRegister service from the container
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service if available
     *
     * @throws \RuntimeException If the service is not available
     */
    public function getObjectService(): ?\OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(self::OPENREGISTER_APP_ID, $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException('OpenRegister service is not available.');

    }//end getObjectService()


    /**
     * Attempts to retrieve the Configuration service from the container
     *
     * @return \OCA\OpenRegister\Service\ConfigurationService|null The Configuration service if available
     *
     * @throws \RuntimeException If the service is not available
     */
    public function getConfigurationService(): ?\OCA\OpenRegister\Service\ConfigurationService
    {
        if (in_array(self::OPENREGISTER_APP_ID, $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ConfigurationService');
        }

        throw new RuntimeException('Configuration service is not available.');

    }//end getConfigurationService()


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
            // Check if OpenRegister is installed and enabled.
            if ($this->isOpenRegisterInstalled() === false) {
                throw new RuntimeException('OpenRegister is not installed or version is too low');
            }

            if ($this->isOpenRegisterEnabled() === false) {
                throw new RuntimeException('OpenRegister is not enabled');
            }

            // Try to get the OpenRegister configuration service.
            try {
                $configurationService = $this->getConfigurationService();
            } catch (Exception $e) {
                throw new RuntimeException('OpenRegister configuration service is not available: '.$e->getMessage());
            }

            // Get current configuration version from app config.
            $currentVersion = $this->config->getValueString($this->appName, 'configuration_version', '0.0.0');

            // Load settings from file.
            $settings = $this->loadSettings();

            // Check if new configuration version is higher than current.
            if (version_compare($settings['info']['version'], $currentVersion, '<=') === true) {
                $results['info'][] = "Configuration version {$currentVersion} is up to date or newer than {$settings['info']['version']}";
                return $results;
            }

            // Import the new configuration using the app-aware method.
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
                [
                    'app' => $this->appName,
                ]
            );
        }//end try

        return $results;

    }//end initialize()


    /**
     * Load settings from the docudesk_register.json file
     *
     * @return array<string, mixed> The loaded settings configuration
     *
     * @throws \RuntimeException If settings loading fails
     */
    public function loadSettings(): array
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
                throw new RuntimeException('Settings file does not contain version information');
            }

            return $settings;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to load settings: '.$e->getMessage());
        }//end try

    }//end loadSettings()


    /**
     * Retrieve all settings
     *
     * @return array<string, mixed> The current settings configuration
     *
     * @throws RuntimeException If settings retrieval fails
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAllSettings(): array
    {
        // Initialize the data array.
        $data = [];
        $data['objectTypes']        = [
            'publicationConsent',
            'template',
        ];
        $data['openRegisters']      = false;
        $data['availableRegisters'] = [];

        // Check if the OpenRegister service is available.
        try {
            if ($this->isOpenRegisterInstalled() === true) {
                $data['openRegisters'] = true;

                // Add additional error handling for OpenRegister internal errors.
                try {
                    // Get all registers with schemas extended.
                    $rawRegisters = $this->registerService->findAll(
                        limit: null,
                        offset: null,
                        filters: [],
                        searchConditions: [],
                        searchParams: [],
                        _extend: ['schemas']
                    );

                    // Convert Register entities to arrays and filter schemas.
                    $data['availableRegisters'] = array_map(
                        function ($register) {
                            $registerArray = $register->jsonSerialize();

                            // Filter schemas to remove properties field for cleaner response.
                            if (isset($registerArray['schemas']) === true && is_array($registerArray['schemas']) === true) {
                                $registerArray['schemas'] = array_map(
                                    function ($schema) {
                                        if (is_array($schema) === true) {
                                            return array_filter(
                                                $schema,
                                                function ($key) {
                                                    return in_array($key, ['properties'], true) === false;
                                                },
                                                ARRAY_FILTER_USE_KEY
                                            );
                                        }

                                        return $schema;
                                    },
                                    $registerArray['schemas']
                                );
                            }

                            return $registerArray;
                        },
                        $rawRegisters
                    );
                } catch (TypeError $e) {
                    $this->logger->warning(
                        'OpenRegister internal error - using empty registers list',
                        [
                            'exception' => $e->getMessage(),
                            'file'      => $e->getFile(),
                            'line'      => $e->getLine(),
                        ]
                    );
                    $data['availableRegisters'] = [];
                } catch (Exception $e) {
                    $this->logger->warning(
                        'OpenRegister findAll() failed - using empty registers list',
                        [
                            'exception' => $e->getMessage(),
                            'file'      => $e->getFile(),
                            'line'      => $e->getLine(),
                        ]
                    );
                    $data['availableRegisters'] = [];
                }//end try
            }//end if
        } catch (\RuntimeException $e) {
            $this->logger->info(
                'OpenRegister service not available',
                [
                    'exception' => $e->getMessage(),
                ]
            );
        }//end try

        // Build defaults array dynamically based on object types.
        $defaults = [];
        foreach ($data['objectTypes'] as $type) {
            $defaults["{$type}_source"]   = 'openregister';
            $defaults["{$type}_schema"]   = '';
            $defaults["{$type}_register"] = '';
        }

        // Get the current values for the object types from the configuration.
        try {
            foreach ($defaults as $key => $defaultValue) {
                $data['configuration'][$key] = $this->config->getValueString($this->appName, $key, $defaultValue);
            }

            // Get DocuDesk-specific consent and metadata settings.
            $data['publication_objection_period_days'] = (int) $this->config->getValueString(
                $this->appName,
                'publication_objection_period_days',
                '28'
            );
            $data['enable_language_detection']         = $this->config->getValueString(
                $this->appName,
                'enable_language_detection',
                '1'
            ) === '1';
            $data['enable_keyword_extraction']         = $this->config->getValueString(
                $this->appName,
                'enable_keyword_extraction',
                '1'
            ) === '1';
            $data['enable_topic_classification']       = $this->config->getValueString(
                $this->appName,
                'enable_topic_classification',
                '1'
            ) === '1';

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to retrieve settings: '.$e->getMessage());
        }//end try

    }//end getAllSettings()


    /**
     * Update the settings configuration
     *
     * @param array<string, mixed> $data The settings data to update
     *
     * @return array<string, mixed> The updated settings configuration
     *
     * @throws \RuntimeException If settings update fails
     */
    public function updateSettings(array $data): array
    {
        try {
            // Update each setting in the configuration.
            foreach ($data as $key => $value) {
                // Skip empty keys.
                if (empty($key) === true) {
                    $this->logger->warning(
                        'Skipping empty key in updateSettings',
                        [
                            'value' => $value,
                        ]
                    );
                    continue;
                }

                // Handle arrays and objects by converting to JSON.
                $stringValue = (is_string($value) === true) ? $value : (string) $value;
                if (is_array($value) === true || is_object($value) === true) {
                    $stringValue = json_encode($value);
                }

                $this->config->setValueString($this->appName, $key, $stringValue);
                $data[$key] = $this->config->getValueString($this->appName, $key);
            }//end foreach

            $this->logger->info(
                'Settings updated successfully',
                [
                    'updatedKeys' => array_keys($data),
                ]
            );

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to update settings: '.$e->getMessage());
        }//end try

    }//end updateSettings()


}//end class
