<?php
/**
 * Consent CRUD Service
 *
 * Service for handling CRUD operations on consent records via the controller.
 * Provides config resolution, object listing, and single-object retrieval.
 * Extracted from ConsentController to reduce class complexity.
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
use Psr\Log\LoggerInterface;

/**
 * Service for consent CRUD operations used by the controller
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConsentCrudService
{
    /**
     * Constructor for ConsentCrudService
     *
     * @param SettingsService $settingsService Settings service for register/schema IDs
     * @param ConsentService  $consentService  Consent service for consent operations
     * @param LoggerInterface $logger          Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ConsentService $consentService,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Get the consent register and schema IDs from settings
     *
     * @return array{register: string, schema: string}|null Config or null if not configured
     */
    public function getConsentConfig(): ?array
    {
        $settings = $this->settingsService->getAllSettings();
        $register = $settings['configuration']['publicationConsent_register'] ?? '';
        $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

        if (empty($register) === true || empty($schema) === true) {
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end getConsentConfig()

    /**
     * List all consent records
     *
     * @param string $register The register ID
     * @param string $schema   The schema ID
     *
     * @return array<int, array<string, mixed>> List of consent records
     *
     * @throws Exception If listing fails
     */
    public function listConsents(string $register, string $schema): array
    {
        $objectService = $this->settingsService->getObjectService();
        $results       = $objectService->searchObjects(
            [
                '@self' => ['register' => $register, 'schema' => $schema],
            ]
        );

        $consents = [];
        foreach ($results as $result) {
            if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
                $consents[] = $result->jsonSerialize();
                continue;
            }

            $consents[] = (array) $result;
        }

        return $consents;

    }//end listConsents()

    /**
     * Get a single consent record by ID
     *
     * @param string $consentId The consent record UUID
     * @param string $register  The register ID
     * @param string $schema    The schema ID
     *
     * @return array<string, mixed>|null The consent record or null if not found
     *
     * @throws Exception If retrieval fails
     */
    public function getConsent(string $consentId, string $register, string $schema): ?array
    {
        $objectService = $this->settingsService->getObjectService();
        $object        = $objectService->find(
            id: $consentId,
            register: $register,
            schema: $schema,
            _rbac: false,
            _multitenancy: false
        );

        if ($object === null) {
            return null;
        }

        if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
            return $object->jsonSerialize();
        }

        return (array) $object;

    }//end getConsent()

    /**
     * Server-controlled fields on a `publicationConsent` record. These are
     * populated by `ConsentService::buildConsentData` from the
     * `PolicyMatchService::match` outcome — an attacker who could inject
     * them via the HTTP request body would defeat the WOO objection
     * deadline or DOS publication via a fake prohibition. PR #147
     * fifth-pass review locked this surface. Listed here once so the
     * symmetric guard in `ConsentService::createConsentRequest` and the
     * controller-side strip stay in sync.
     *
     * @var string[]
     */
    public const SERVER_CONTROLLED_FIELDS = [
        'policyMatch',
        'matchKind',
        'consentStatus',
        'publicationDecision',
        'notificationStatus',
        'objectionDeadline',
    ];


    /**
     * Create a consent request from controller data
     *
     * @param array<string, mixed> $data     The request data
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     *
     * @return array<string, mixed> The created consent record
     *
     * @throws Exception If creation fails
     */
    public function createFromRequest(array $data, string $register, string $schema): array
    {
        // Extract known fields and pass any remaining as extra.
        $knownFields = ['documentId', 'entityType', 'entityText'];
        $extra       = array_diff_key($data, array_flip($knownFields));

        // Remove framework-injected params that are not consent data.
        unset($extra['_route'], $extra['_method']);

        // Strip server-controlled fields the caller has no business setting.
        // The HTTP-input boundary defense for the CREATE-side bypass class
        // (PR #147 fifth-pass — symmetric to the UPDATE-side immutability
        // guard added in the fourth pass). Without this strip,
        // `array_merge($serverComputed, $extra)` in
        // `ConsentService::createConsentRequest` would let the caller
        // overwrite the policy-match outcome with attacker-supplied values.
        $rejected = array_intersect_key($extra, array_flip(self::SERVER_CONTROLLED_FIELDS));
        if (empty($rejected) === false) {
            $this->logger->warning(
                'Server-controlled consent fields stripped from create-request body',
                [
                    'register'     => $register,
                    'schema'       => $schema,
                    // ADR-005: log keys only; do not echo attacker-supplied values back into the log stream.
                    'strippedKeys' => array_keys($rejected),
                ]
            );
            $extra = array_diff_key($extra, $rejected);
        }

        return $this->consentService->createConsentRequest(
            $data['documentId'],
            $data['entityType'],
            $data['entityText'],
            $register,
            $schema,
            $extra
        );

    }//end createFromRequest()

    /**
     * Get all consent records for a specific document
     *
     * @param string $documentId The document UUID
     * @param string $register   The register ID
     * @param string $schema     The schema ID
     *
     * @return array<int, array<string, mixed>> List of consent records
     *
     * @throws Exception If query fails
     */
    public function getConsentsByDocument(
        string $documentId,
        string $register,
        string $schema
    ): array {
        return $this->consentService->getConsentsByDocument($documentId, $register, $schema);

    }//end getConsentsByDocument()

    /**
     * Update consent status for a consent record
     *
     * @param string               $consentId The consent object UUID
     * @param string               $register  The register ID
     * @param string               $schema    The schema ID
     * @param array<string, mixed> $data      The data to update
     *
     * @return array<string, mixed> The updated consent record
     *
     * @throws Exception If update fails
     */
    public function updateConsentStatus(
        string $consentId,
        string $register,
        string $schema,
        array $data
    ): array {
        return $this->consentService->updateConsentStatus($consentId, $register, $schema, $data);

    }//end updateConsentStatus()
}//end class
