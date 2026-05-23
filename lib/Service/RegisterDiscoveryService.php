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

use Throwable;
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
     * Calls `RegisterService::findAllSerialized` with `_extend: ['schemas']` so
     * each register's `schemas` field is returned as full schema objects (not
     * bare IDs). On orphan schema IDs, OpenRegister retains the ID in place
     * (heterogeneous array of objects + ints) — `filterSchemaProperties()`
     * passes those through unchanged.
     *
     * Requires `nextcloud/openregister` with `findAllSerialized` available
     * (see openregister#1428). When the deployed OpenRegister is older than
     * #1428 the call raises an `Error` ("Call to undefined method ..."); we
     * catch `Throwable` (the only common ancestor of `Exception` and `Error`
     * in PHP 7+) so the caller falls back to an empty registers list instead
     * of bubbling a 500 to the controller. Without this widened catch, the
     * `/api/settings` and `/api/consents` endpoints surface as HTTP 500s for
     * every user on an environment where the OR sidecar lags this app.
     *
     * @return array<int, array<string, mixed>> List of register arrays with filtered schemas
     */
    public function fetchAvailableRegisters(): array
    {
        try {
            $rawRegisters = $this->registerService->findAllSerialized(
                limit: null,
                offset: null,
                filters: [],
                searchConditions: [],
                searchParams: [],
                _extend: ['schemas']
            );

            return array_map([$this, 'filterSchemas'], $rawRegisters);
        } catch (Throwable $e) {
            // Catches both Exception (runtime failures from OpenRegister) and
            // Error (e.g. "Call to undefined method" when the deployed OR is
            // older than #1428 — see openregister#1428). Either way the
            // graceful fallback is an empty list; the controller still
            // returns a 200 with whatever non-register settings it can
            // assemble.
            $this->logger->warning(
                'OpenRegister findAllSerialized() unavailable - using empty registers list',
                [
                    'exception' => $e->getMessage(),
                    'class'     => get_class($e),
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
     * Input is the already-serialized register array from
     * `RegisterService::findAllSerialized` — each entry in `schemas` is either
     * a full schema array (when expansion succeeded) or a bare ID (orphan
     * schema). `filterSchemaProperties()` handles both transparently.
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
