<?php
/**
 * Register Discovery Service
 *
 * Service for discovering available registers and their schemas from
 * OpenRegister. Also handles loading object type configuration defaults.
 * Extracted from SettingsService to reduce class complexity.
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
use TypeError;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use OCA\OpenRegister\Service\RegisterService;

/**
 * Service for discovering available registers and loading object type config
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RegisterDiscoveryService
{

    /**
     * The application name
     *
     * @var string
     */
    private readonly string $appName;

    /**
     * Constructor for RegisterDiscoveryService
     *
     * @param IAppConfig      $config          App configuration interface
     * @param LoggerInterface $logger          Logger interface
     * @param RegisterService $registerService Register service for getting registers
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger,
        private readonly RegisterService $registerService
    ) {
        $this->appName = 'docudesk';

    }//end __construct()

    /**
     * Fetch available registers from OpenRegister with schemas
     *
     * @return array<int, array<string, mixed>> List of register arrays with filtered schemas
     */
    public function fetchAvailableRegisters(): array
    {
        try {
            $rawRegisters = $this->registerService->findAll(
                limit: null,
                offset: null,
                filters: [],
                searchConditions: [],
                searchParams: [],
                _extend: ['schemas']
            );

            return array_map([$this, 'serializeRegister'], $rawRegisters);
        } catch (TypeError $e) {
            $this->logger->warning(
                'OpenRegister internal error - using empty registers list',
                [
                    'exception' => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]
            );
            return [];
        } catch (Exception $e) {
            $this->logger->warning(
                'OpenRegister findAll() failed - using empty registers list',
                [
                    'exception' => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]
            );
            return [];
        }//end try

    }//end fetchAvailableRegisters()

    /**
     * Serialize a register entity and filter its schemas
     *
     * @param mixed $register The register entity
     *
     * @return array<string, mixed> Serialized register with filtered schemas
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function serializeRegister(mixed $register): array
    {
        $registerArray = $register->jsonSerialize();

        if (isset($registerArray['schemas']) === true && is_array($registerArray['schemas']) === true) {
            $registerArray['schemas'] = array_map(
                [$this, 'filterSchemaProperties'],
                $registerArray['schemas']
            );
        }

        return $registerArray;

    }//end serializeRegister()

    /**
     * Filter out the properties field from a schema array
     *
     * @param mixed $schema The schema data
     *
     * @return mixed The filtered schema
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function filterSchemaProperties(mixed $schema): mixed
    {
        if (is_array($schema) === false) {
            return $schema;
        }

        unset($schema['properties']);

        return $schema;

    }//end filterSchemaProperties()

    /**
     * Load object type configuration defaults from app config
     *
     * @param array<string> $objectTypes The object types to load config for
     *
     * @return array<string, string> Configuration key-value pairs
     */
    public function loadObjectTypeConfiguration(array $objectTypes): array
    {
        $configuration = [];
        foreach ($objectTypes as $type) {
            $sourceKey   = "{$type}_source";
            $schemaKey   = "{$type}_schema";
            $registerKey = "{$type}_register";

            $configuration[$sourceKey]   = $this->config->getValueString(
                $this->appName,
                $sourceKey,
                'openregister'
            );
            $configuration[$schemaKey]   = $this->config->getValueString(
                $this->appName,
                $schemaKey,
                ''
            );
            $configuration[$registerKey] = $this->config->getValueString(
                $this->appName,
                $registerKey,
                ''
            );
        }//end foreach

        return $configuration;

    }//end loadObjectTypeConfiguration()
}//end class
