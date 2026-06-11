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
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-13
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-14
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-36
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-13
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-36
     */
    public function getConsentConfig(): ?array
    {
        $settings = $this->settingsService->getAllSettings();
        $register = $settings['configuration']['publicationConsent_register'] ?? '';
        $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

        if (empty($register) === true || empty($schema) === true) {
            // Surface the configuration gap to the admin: callers treat
            // null as "consent feature disabled" and silently skip, which
            // hid the misconfiguration in earlier iterations.
            $this->logger->debug(
                'ConsentCrudService: publicationConsent register/schema not configured; '
                .'consent CRUD is disabled until both settings are populated.',
                ['register' => $register, 'schema' => $schema]
            );
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end getConsentConfig()

    /**
     * List consent records, optionally scoped to a single owner
     *
     * When $ownerUid is provided (non-admin callers) the search is filtered
     * server-side so only records whose @self.owner matches the caller are
     * returned. Passing null returns all records (admin callers only).
     *
     * @param string      $register The register ID
     * @param string      $schema   The schema ID
     * @param string|null $ownerUid UID to scope results to, or null for all
     *
     * @return array<int, array<string, mixed>> List of consent records
     *
     * @throws Exception If listing fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
     */
    public function listConsents(string $register, string $schema, ?string $ownerUid=null): array
    {
        $objectService = $this->settingsService->getObjectService();

        $query = ['@self' => ['register' => $register, 'schema' => $schema]];

        // Security (H1): scope listing to the caller's own records so that
        // non-admin users cannot enumerate consent records belonging to
        // other users through the listing endpoint.
        if ($ownerUid !== null) {
            $query['@self']['owner'] = $ownerUid;
        }

        $results = $objectService->searchObjects($query);

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
     */
    public function getConsent(string $consentId, string $register, string $schema): ?array
    {
        // Let OpenRegister enforce per-object RBAC and multitenancy access.
        // Bypassing these (security finding #283) allowed any authenticated
        // user to read consent records owned by other users.
        $objectService = $this->settingsService->getObjectService();
        $object        = $objectService->find(
            id: $consentId,
            register: $register,
            schema: $schema
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
     * Fields accepted at consent creation time.
     *
     * Any request parameter NOT in this list is silently dropped before the
     * record is written to OpenRegister (finding #290b).  Callers must not be
     * able to force internal state fields such as `consentStatus`,
     * `publicationDecision`, or `userId` at creation time.
     *
     * @var array<string>
     */
    private const ALLOWED_CREATE_FIELDS = [
        'documentId',
        'entityType',
        'entityText',
        'entityKey',
        'publicationBases',
        'legalBasis',
        'contactEmail',
        'contactAddress',
    ];

    /**
     * Create a consent request from controller data
     *
     * Only the fields listed in {@see ALLOWED_CREATE_FIELDS} are accepted.
     * All other request parameters are silently dropped so that callers cannot
     * set internal status fields (e.g. `consentStatus`, `publicationDecision`,
     * `userId`) at creation time, bypassing status-machine logic (finding #290b).
     *
     * @param array<string, mixed> $data     The request data
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     *
     * @return array<string, mixed> The created consent record
     *
     * @throws Exception If creation fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-13
     */
    public function createFromRequest(array $data, string $register, string $schema): array
    {
        // Only forward the explicitly allowed fields; drop everything else.
        $filtered = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));

        // Build the $extra array with idempotency and notes fields.
        $extra = [];
        foreach (['entityKey', 'publicationBases', 'legalBasis', 'contactEmail', 'contactAddress'] as $key) {
            if (isset($filtered[$key]) === true) {
                $extra[$key] = $filtered[$key];
            }
        }

        return $this->consentService->createConsentRequest(
            documentId: (string) ($filtered['documentId'] ?? ''),
            entityType: (string) ($filtered['entityType'] ?? ''),
            entityText: (string) ($filtered['entityText'] ?? ''),
            register: $register,
            schema: $schema,
            extra: $extra
        );

    }//end createFromRequest()

    /**
     * Get consent records for a specific document, optionally scoped to one owner
     *
     * @param string      $documentId The document UUID
     * @param string      $register   The register ID
     * @param string      $schema     The schema ID
     * @param string|null $ownerUid   UID to scope results to, or null for all
     *
     * @return array<int, array<string, mixed>> List of consent records
     *
     * @throws Exception If query fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
     */
    public function getConsentsByDocument(
        string $documentId,
        string $register,
        string $schema,
        ?string $ownerUid=null
    ): array {
        return $this->consentService->getConsentsByDocument($documentId, $register, $schema, $ownerUid);

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-14
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
