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
use OCA\OpenRegister\Db\SchemaMapper;
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
     * @param SchemaMapper    $schemaMapper    Schema mapper for expanding schema IDs
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger,
        private readonly RegisterService $registerService,
        private readonly SchemaMapper $schemaMapper
    ) {
        $this->appName = 'docudesk';

    }//end __construct()

    /**
     * Fetch available registers from OpenRegister with schemas
     *
     * Calls `RegisterService::findAll`, serializes each register via
     * `jsonSerialize`, then expands schema IDs to full schema objects
     * (with `properties` stripped). On orphan schema IDs, the bare ID is
     * retained in place (heterogeneous array of objects + ints) —
     * `filterSchemaProperties()` passes those through unchanged.
     *
     * Mirrors the expansion logic in OpenRegister's
     * `RegistersController::index` for `_extend: ['schemas']`, but performed
     * inline so docudesk doesn't depend on a non-existent serialized helper.
     *
     * @return array<int, array<string, mixed>> List of register arrays with filtered schemas
     */
    public function fetchAvailableRegisters(): array
    {
        try {
            $registers = $this->registerService->findAll(
                limit: null,
                offset: null,
                filters: [],
                searchConditions: [],
                searchParams: [],
                _multitenancy: false
            );

            $rawRegisters = array_map(
                fn($register) => $register->jsonSerialize(),
                $registers
            );

            // Expand schema IDs to full schema objects.
            foreach ($rawRegisters as &$registerArr) {
                if (isset($registerArr['schemas']) === true && is_array($registerArr['schemas']) === true) {
                    $expanded = [];
                    foreach ($registerArr['schemas'] as $schemaId) {
                        try {
                            $schema     = $this->schemaMapper->find(id: $schemaId, _multitenancy: false);
                            $expanded[] = $schema->jsonSerialize();
                        } catch (Exception $schemaError) {
                            // Orphan schema — retain bare ID for transparency.
                            $expanded[] = $schemaId;
                        }
                    }

                    $registerArr['schemas'] = $expanded;
                }
            }

            unset($registerArr);

            return array_map([$this, 'filterSchemas'], $rawRegisters);
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
     * Strip the `properties` field from each schema in a serialized register
     *
     * Input is the already-serialized register array — each entry in `schemas`
     * is either a full schema array (when expansion succeeded) or a bare ID
     * (orphan schema). `filterSchemaProperties()` handles both transparently.
     *
     * @param array<string, mixed> $registerArray Serialized register
     *
     * @return array<string, mixed> Serialized register with filtered schemas
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function filterSchemas(array $registerArray): array
    {
        if (isset($registerArray['schemas']) === true && is_array($registerArray['schemas']) === true) {
            $registerArray['schemas'] = array_map(
                [$this, 'filterSchemaProperties'],
                $registerArray['schemas']
            );
        }

        return $registerArray;

    }//end filterSchemas()

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
