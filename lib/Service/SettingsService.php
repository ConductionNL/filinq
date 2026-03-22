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
     * The minimum version of the OpenRegister application required
     *
     * @var string The minimum required version of OpenRegister
     */
    private const MIN_OPENREGISTER_VERSION = '0.2.10';


    /**
     * SettingsService constructor
     *
     * @param IAppConfig               $config           App configuration interface
     * @param ContainerInterface       $container        Container for DI
     * @param IAppManager              $appManager       App manager interface
     * @param LoggerInterface          $logger           Logger interface
     * @param RegisterDiscoveryService $discoveryService Register discovery service
     * @param SettingsInitializer      $initializer      Settings initializer
     * @param OcrService               $ocrService       OCR service for status checks
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly LoggerInterface $logger,
        private readonly RegisterDiscoveryService $discoveryService,
        private readonly SettingsInitializer $initializer,
        private readonly OcrService $ocrService
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
     * Attempts to retrieve the OpenRegister service from the container
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service
     *
     * @throws \RuntimeException If the service is not available
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
     */
    public function initialize(): array
    {
        return $this->initializer->initialize();

    }//end initialize()


    /**
     * Load feature toggle settings from app config
     *
     * @return array<string, mixed> Feature toggle settings
     */
    private function loadFeatureToggles(): array
    {
        return [
            'publication_objection_period_days' => (int) $this->config->getValueString(
                $this->appName,
                'publication_objection_period_days',
                '28'
            ),
            'enable_language_detection'         => $this->config->getValueString(
                $this->appName,
                'enable_language_detection',
                '1'
            ) === '1',
            'enable_keyword_extraction'         => $this->config->getValueString(
                $this->appName,
                'enable_keyword_extraction',
                '1'
            ) === '1',
            'enable_topic_classification'       => $this->config->getValueString(
                $this->appName,
                'enable_topic_classification',
                '1'
            ) === '1',
            'signing_enabled'                   => $this->config->getValueString(            'ocr_enabled'                       => $this->config->getValueString(
                $this->appName,
                'ocr_enabled',
                '1'
            ) === '1',
            'ocr_languages'                     => $this->config->getValueString(
                $this->appName,
                'ocr_languages',
                'nld+eng'
            ),
            'ocr_dpi'                           => (int) $this->config->getValueString(
                $this->appName,
                'ocr_dpi',
                '300'            'signing_enabled'                   => $this->config->getValueString(                $this->appName,
                'signing_enabled',
                '0'
            ) === '1',
            'signing_provider'                  => $this->config->getValueString(
                $this->appName,
                'signing_provider',
                'native'
            ),
            'signing_default_level'             => $this->config->getValueString(
                $this->appName,
                'signing_default_level',
                'SES'
            ),
            'signing_request_expiry_days'       => (int) $this->config->getValueString(
                $this->appName,
                'signing_request_expiry_days',
                '30'
            ),                '30'            ),        ];

    }//end loadFeatureToggles()


    /**
     * Get the OCR system status including Tesseract availability
     *
     * @return array<string, mixed> OCR status information
     */
    public function getOcrStatus(): array
    {
        return [
            'tesseractAvailable' => $this->ocrService->isTesseractAvailable(),
            'tesseractVersion'   => $this->ocrService->getTesseractVersion(),
        ];

    }//end getOcrStatus()


    /**
     * Retrieve all settings
     *
     * @return array<string, mixed> The current settings configuration
     *
     * @throws RuntimeException If settings retrieval fails
     */
    public function getAllSettings(): array
    {
        $data = [
            'objectTypes'        => ['publicationConsent', 'template', 'templateVersion'],
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
            $data['ocrStatus'] = $this->getOcrStatus();

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
            foreach ($data as $key => $value) {
                if (empty($key) === true) {
                    $this->logger->warning(
                        'Skipping empty key in updateSettings',
                        ['value' => $value]
                    );
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
