<?php
/**
 * Service for handling settings-related operations
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Service;

use OCP\IAppConfig;
use OCP\IRequest;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use OCP\AppFramework\Http\JSONResponse;
use OC_App;
use OCP\ILogger;

/**
 * Service for handling settings-related operations.
 *
 * Provides functionality for retrieving, saving, and loading settings,
 * as well as managing configuration for different object types.
 */
class SettingsService
{

    /**
     * This property holds the name of the application, which is used for identification and configuration purposes.
     *
     * @var string $appName The name of the app.
     */
    private string $appName;

    /**
     * This constant represents the unique identifier for the OpenCatalogi application.
     *
     * @var string $openCatalogiAppId The ID of the OpenCatalogi app.
     */
    private const OPENCATALOGI_APP_ID = 'opencatalogi';


    /**
     * SettingsService constructor.
     *
     * @param IAppConfig         $config     App configuration interface.
     * @param IRequest           $request    Request interface.
     * @param ContainerInterface $container  Container for dependency injection.
     * @param IAppManager        $appManager App manager interface.
     * @param ILogger            $logger     Logger interface.
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly IRequest $request,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly ILogger $logger
    ) {
        $this->appName = 'docudesk';

    }//end __construct()


    /**
     * Initializes the app with all required components.
     *
     * @return array The initialization results.
     * @throws \RuntimeException If initialization fails.
     */
    public function initialize(): array
    {
        $results = [
            'configuration' => false,
            'errors'        => [],
            'info'          => [],
        ];

        try {
            // Try to get the OpenCatalogi configuration service.
            try {
                $configurationService = $this->getConfigurationService();
            } catch (\Exception $e) {
                throw new \RuntimeException('OpenCatalogi configuration service is not available: '.$e->getMessage());
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

            // Import the new configuration.
            $configurationService->importFromJson($settings, false);

            // Update the configuration version in app config.
            $this->config->setValueString($this->appName, 'configuration_version', $settings['info']['version']);

            $results['configuration'] = true;
            $results['info'][]        = 'Configuration updated to version '.$settings['info']['version'];
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->logger->error('Failed to initialize DocuDesk: '.$e->getMessage(), ['app' => $this->appName]);
        }//end try

        return $results;

    }//end initialize()


    /**
     * Load settings from the document_register.json file.
     *
     * @return array The loaded settings configuration.
     * @throws \RuntimeException If settings loading fails.
     */
    public function loadSettings(): array
    {
        $settingsFilePath = __DIR__.'/../Settings/document_register.json';

        try {
            if (file_exists($settingsFilePath) === false) {
                throw new \RuntimeException('Settings file not found at: '.$settingsFilePath);
            }

            $jsonContent = file_get_contents($settingsFilePath);
            if ($jsonContent === false) {
                throw new \RuntimeException('Failed to read settings file');
            }

            $settings = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Error decoding JSON: '.json_last_error_msg());
            }

            if (isset($settings['info']['version']) === false) {
                throw new \RuntimeException('Settings file does not contain version information');
            }

            return $settings;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to load settings: '.$e->getMessage());
        }//end try

    }//end loadSettings()


    /**
     * Attempts to auto-configure registers and schemas.
     *
     * @return array The updated configuration.
     * @throws \RuntimeException If auto-configuration fails.
     */
    public function autoConfigure(): array
    {
        try {
            $objectService = $this->getObjectService();
            $registers     = $objectService->getRegisters();

            if (empty($registers) === true) {
                return [];
            }

            $configuration = [];
            foreach ($this->getSettings()['objectTypes'] as $type) {
                // Try to find a register with a matching name.
                $matchingRegister = null;
                foreach ($registers as $register) {
                    if (stripos($register['title'], $type) !== false) {
                        $matchingRegister = $register;
                        break;
                    }
                }

                if ($matchingRegister !== null) {
                    $configuration["{$type}_register"] = $matchingRegister['id'];

                    // Try to find a matching schema.
                    if (empty($matchingRegister['schemas']) === false) {
                        foreach ($matchingRegister['schemas'] as $schema) {
                            if (stripos($schema['title'], $type) !== false) {
                                $configuration["{$type}_schema"] = $schema['id'];
                                break;
                            }
                        }
                    }
                }
            }//end foreach

            return $configuration;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to auto-configure: '.$e->getMessage());
        }//end try

    }//end autoConfigure()


    /**
     * Attempts to retrieve the OpenRegister service from the container.
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service if available, null otherwise.
     * @throws \RuntimeException If the service is not available.
     */
    public function getObjectService(): ?\OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps()) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new \RuntimeException('OpenRegister service is not available.');

    }//end getObjectService()


    /**
     * Attempts to retrieve the Configuration service from the container.
     *
     * @return \OCA\OpenRegister\Service\ConfigurationService|null The Configuration service if available, null otherwise.
     * @throws \RuntimeException If the service is not available.
     */
    public function getConfigurationService(): ?\OCA\OpenRegister\Service\ConfigurationService
    {
        if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps()) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ConfigurationService');
        }

        throw new \RuntimeException('Configuration service is not available.');

    }//end getConfigurationService()


    /**
     * Retrieve the current settings.
     *
     * @return array The current settings configuration.
     * @throws \RuntimeException If settings retrieval fails.
     */
    public function getSettings(): array
    {
        // Initialize the data array.
        $data = [];
        $data['objectTypes']        = [
            "report",
            "template",
            "entity",
        ];
        $data['openRegisters']      = false;
        $data['availableRegisters'] = [];

        // Check if the OpenRegister service is available.
        try {
            $openRegisters = $this->getObjectService();
            if ($openRegisters !== null) {
                $data['openRegisters']      = true;
                $data['availableRegisters'] = $openRegisters->getRegisters();
            }
        } catch (\RuntimeException $e) {
            // Service not available, continue with default values.
        }

        // Build defaults array dynamically based on object types.
        $defaults = [];
        foreach ($data['objectTypes'] as $type) {
            // Always use openregister as source.
            $defaults["{$type}_source"]   = 'openregister';
            $defaults["{$type}_schema"]   = '';
            $defaults["{$type}_register"] = '';
        }

        // Get the current values for the object types from the configuration.
        try {
            foreach ($defaults as $key => $defaultValue) {
                $data['configuration'][$key] = $this->config->getValueString($this->appName, $key, $defaultValue);
            }

            return $data;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to retrieve settings: '.$e->getMessage());
        }

    }//end getSettings()


    /**
     * Update the settings configuration.
     *
     * @param array $data The settings data to update.
     *
     * @return array The updated settings configuration.
     * @throws \RuntimeException If settings update fails.
     */
    public function updateSettings(array $data): array
    {
        try {
            // Update each setting in the configuration.
            foreach ($data as $key => $value) {
                $this->config->setValueString($this->appName, $key, $value);
                // Retrieve the updated value to confirm the change.
                $data[$key] = $this->config->getValueString($this->appName, $key);
            }

            return $data;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update settings: '.$e->getMessage());
        }//end try

    }//end updateSettings()


}//end class
