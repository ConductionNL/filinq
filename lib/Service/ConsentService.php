<?php
/**
 * Consent Service
 *
 * Service for managing GDPR publication consent tracking.
 * Handles creating consent records for entities detected in documents.
 * Delegates deadline checking to ObjectionDeadlineChecker and
 * update/query operations to ConsentUpdateHandler.
 *
 * createConsentRequest() is idempotent on (documentId, entityKey):
 * a second call for the same pair updates the existing record rather
 * than creating a duplicate, preserving workflow state fields.
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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCA\DocuDesk\Exception\PolicyRejectedException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for GDPR publication consent management
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */
class ConsentService
{

    /**
     * Workflow-state fields that MUST NOT be overwritten on update.
     *
     * @var string[]
     */
    private const PRESERVED_FIELDS = [
        'notificationStatus',
        'notificationSentAt',
        'objectionDeadline',
        'objectionReceivedAt',
        'objectionReason',
        'consentStatus',
        'publicationDecision',
    ];

    /**
     * Constructor for ConsentService
     *
     * @param LoggerInterface          $logger          Logger for error reporting
     * @param ContainerInterface       $container       Container for DI
     * @param IAppManager              $appManager      App manager interface
     * @param ObjectionDeadlineChecker $deadlineChecker Deadline checker
     * @param ConsentUpdateHandler     $updateHandler   Update and query handler
     * @param PolicyMatchService       $policyMatcher   Policy rule matcher
     * @param ConsentNotesHelper       $notesHelper     Sentinel-tagged notes helper
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly ObjectionDeadlineChecker $deadlineChecker,
        private readonly ConsentUpdateHandler $updateHandler,
        private readonly PolicyMatchService $policyMatcher,
        private readonly ConsentNotesHelper $notesHelper,
        private readonly ConsentScopeValidator $scopeValidator
    ) {

    }//end __construct()

    /**
     * Get the ObjectService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps(), strict: true) === true) {
            return $this->container->get(id: 'OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException(message: 'OpenRegister service is not available.');

    }//end getObjectService()

    /**
     * Create (or idempotently update) a consent request for a detected entity.
     *
     * Idempotency key: `(documentId, entityKey, scope: "document")`.
     * When entityKey is null the fallback key is `(documentId, entityText, scope: "document")`.
     * scope=entity standing-consent records are never matched as duplicates.
     *
     * Return shape includes `wasUpdated: bool` — true when an existing record
     * was matched and updated, false when a new record was created.
     *
     * CONS-049 reaffirmed: new records receive `notificationStatus: "pending"` and
     * a computed `objectionDeadline`; NO email or postal notification is dispatched.
     *
     * @param string               $documentId The document UUID
     * @param string               $entityType The entity type
     * @param string               $entityText The detected entity text
     * @param string               $register   The register ID
     * @param string               $schema     The schema ID
     * @param array<string, mixed> $extra      Additional consent data.
     *                                         Recognised keys:
     *                                         - entityKey (string|null): OR entity UUID.
     *                                         - publicationBases (string[]): bases array;
     *                                         [0] → legalBasis, [1..] → notes sentinel.
     *                                         - contactEmail (string): contact e-mail.
     *                                         - contactAddress (string): contact address.
     *
     * @return array<string, mixed> The created or updated consent record (includes `wasUpdated`).
     *
     * @throws PolicyRejectedException If a prohibition rule matches the entity.
     * @throws Exception               If consent creation/update fails.
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
     */
    public function createConsentRequest(
        string $documentId,
        string $entityType,
        string $entityText,
        string $register,
        string $schema,
        array $extra=[]
    ): array {
        try {
            $objectService = $this->getObjectService();

            if (isset($extra['entityKey']) === true) {
                $entityKey = (string) $extra['entityKey'];
            } else {
                $entityKey = null;
            }

            if (is_array($extra['publicationBases'] ?? null) === true) {
                $publicationBases = array_values(
                    array_filter(array_map('strval', $extra['publicationBases']))
                );
            } else {
                $publicationBases = [];
            }

            if (isset($extra['contactEmail']) === true) {
                $contactEmail = (string) $extra['contactEmail'];
            } else {
                $contactEmail = null;
            }

            if (isset($extra['contactAddress']) === true) {
                $contactAddress = (string) $extra['contactAddress'];
            } else {
                $contactAddress = null;
            }

            // Policy check — prohibition match blocks the entire request.
            $policyResult = $this->policyMatcher->match(
                entityText: $entityText,
                entityType: $entityType
            );
            if ($policyResult !== null && $policyResult['kind'] === PolicyMatchService::KIND_PROHIBITION) {
                throw new PolicyRejectedException(
                    ruleUuid: (string) $policyResult['uuid'],
                    ruleName: (string) $policyResult['primaryName']
                );
            }

            // Idempotency lookup — find an existing scope=document record.
            $existing = $this->findExistingConsent(
                objectService: $objectService,
                documentId: $documentId,
                entityKey: $entityKey,
                entityText: $entityText,
                register: $register,
                schema: $schema
            );

            if ($existing !== null) {
                return $this->updateExistingConsent(
                    objectService: $objectService,
                    existing: $existing,
                    entityType: $entityType,
                    publicationBases: $publicationBases,
                    contactEmail: $contactEmail,
                    contactAddress: $contactAddress,
                    policyResult: $policyResult,
                    register: $register,
                    schema: $schema
                );
            }

            return $this->createNewConsent(
                objectService: $objectService,
                documentId: $documentId,
                entityType: $entityType,
                entityText: $entityText,
                entityKey: $entityKey,
                publicationBases: $publicationBases,
                contactEmail: $contactEmail,
                contactAddress: $contactAddress,
                policyResult: $policyResult,
                register: $register,
                schema: $schema
            );
        } catch (PolicyRejectedException $e) {
            // Re-throw without wrapping so callers can catch the typed exception.
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to create consent request: '.$e->getMessage(),
                context: ['documentId' => $documentId, 'exception' => $e]
            );
            throw new Exception(message: 'Failed to create consent request: '.$e->getMessage(), code: 0, previous: $e);
        }//end try

    }//end createConsentRequest()

    /**
     * Look up an existing scope=document consent record by idempotency key.
     *
     * Primary key: (documentId, entityKey, scope=document).
     * Fallback key when entityKey is null: (documentId, entityText, scope=document).
     * scope=entity records are excluded from matching.
     *
     * @param \OCA\OpenRegister\Service\ObjectService $objectService The OR object service.
     * @param string                                  $documentId    The document UUID.
     * @param string|null                             $entityKey     OR entity UUID, or null.
     * @param string                                  $entityText    Detected entity text.
     * @param string                                  $register      Register ID.
     * @param string                                  $schema        Schema ID.
     *
     * @return array<string, mixed>|null Existing record data or null if not found.
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
     */
    private function findExistingConsent(
        \OCA\OpenRegister\Service\ObjectService $objectService,
        string $documentId,
        ?string $entityKey,
        string $entityText,
        string $register,
        string $schema
    ): ?array {
        $filter = [
            '@self'      => ['register' => $register, 'schema' => $schema],
            'documentId' => $documentId,
            'scope'      => 'document',
        ];

        if ($entityKey !== null && $entityKey !== '') {
            $filter['entityKey'] = $entityKey;
        } else {
            $filter['entityText'] = $entityText;
        }

        try {
            $results = $objectService->searchObjects(query: $filter);
        } catch (Exception $e) {
            // A failed lookup is non-fatal — fall through to create path.
            $this->logger->warning(
                message: 'ConsentService: idempotency lookup failed, falling through to create',
                context: ['documentId' => $documentId, 'error' => $e->getMessage()]
            );
            return null;
        }

        if (is_array($results) === true) {
            $candidates = $results;
        } else {
            $candidates = [];
        }

        foreach ($candidates as $candidate) {
            $data = $this->extractObjectData(candidate: $candidate);
            // Belt-and-braces: confirm scope=document (the filter should have
            // already excluded scope=entity, but defence-in-depth matters here).
            if (($data['scope'] ?? 'document') !== 'entity') {
                return $data;
            }
        }

        return null;

    }//end findExistingConsent()

    /**
     * Update an existing consent record (idempotent re-submit path).
     *
     * Preserves workflow state fields; updates operator-set fields.
     * Re-evaluates policyMatch: sets it if newly applicable, never clears it.
     *
     * @param \OCA\OpenRegister\Service\ObjectService $objectService    The OR object service.
     * @param array<string, mixed>                    $existing         Current record data.
     * @param string                                  $entityType       Entity type.
     * @param string[]                                $publicationBases Bases array.
     * @param string|null                             $contactEmail     Contact e-mail.
     * @param string|null                             $contactAddress   Contact address.
     * @param array<string, mixed>|null               $policyResult     Policy match result.
     * @param string                                  $register         Register ID.
     * @param string                                  $schema           Schema ID.
     *
     * @return array<string, mixed> Updated record with `wasUpdated: true`.
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
     */
    private function updateExistingConsent(
        \OCA\OpenRegister\Service\ObjectService $objectService,
        array $existing,
        string $entityType,
        array $publicationBases,
        ?string $contactEmail,
        ?string $contactAddress,
        ?array $policyResult,
        string $register,
        string $schema
    ): array {
        // Start from the preserved record so all stored values survive.
        $updated = $existing;

        // Update operator-set fields.
        $updated['entityType'] = $entityType;

        // Resolve legalBasis + notes from publicationBases.
        if (count($publicationBases) > 0) {
            $updated['legalBasis'] = $this->notesHelper->truncateAtWordBoundary(
                value: $publicationBases[0]
            );
            $additionalBases       = array_slice(array: $publicationBases, offset: 1);
        } else {
            $additionalBases = [];
        }

        $currentNotes     = (string) ($existing['notes'] ?? '');
        $updated['notes'] = $this->notesHelper->updateSentinelRegion(
            currentNotes: $currentNotes,
            additionalBases: $additionalBases
        );

        if ($contactEmail !== null) {
            $updated['contactEmail'] = $contactEmail;
        }

        if ($contactAddress !== null) {
            $updated['contactAddress'] = $contactAddress;
        }

        // Re-evaluate pre-emption discriminator: set policyMatch when newly
        // applicable; never clear it when previously set (D2).
        if ($policyResult !== null && ($existing['policyMatch'] ?? null) === null) {
            $updated['policyMatch'] = $policyResult['uuid'];
        }

        // Ensure all preserved workflow fields are kept (not overwritten).
        foreach (self::PRESERVED_FIELDS as $field) {
            if (isset($existing[$field]) === true) {
                $updated[$field] = $existing[$field];
            }
        }

        $savedObject = $objectService->saveObject(
            object: $updated,
            register: $register,
            schema: $schema
        );

        $this->logger->info(
            message: 'Consent request updated (idempotent re-submit)',
            context: ['documentId' => $updated['documentId'] ?? '']
        );

        $result = $savedObject->getObject();
        $result['wasUpdated'] = true;
        return $result;

    }//end updateExistingConsent()

    /**
     * Create a brand-new consent record.
     *
     * Sets notificationStatus=pending and computes objectionDeadline.
     * No email or postal notification is dispatched (CONS-049).
     *
     * @param \OCA\OpenRegister\Service\ObjectService $objectService    The OR object service.
     * @param string                                  $documentId       The document UUID.
     * @param string                                  $entityType       Entity type.
     * @param string                                  $entityText       Detected entity text.
     * @param string|null                             $entityKey        OR entity UUID.
     * @param string[]                                $publicationBases Bases array.
     * @param string|null                             $contactEmail     Contact e-mail.
     * @param string|null                             $contactAddress   Contact address.
     * @param array<string, mixed>|null               $policyResult     Policy match result.
     * @param string                                  $register         Register ID.
     * @param string                                  $schema           Schema ID.
     *
     * @return array<string, mixed> Created record with `wasUpdated: false`.
     *
     * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
     */
    private function createNewConsent(
        \OCA\OpenRegister\Service\ObjectService $objectService,
        string $documentId,
        string $entityType,
        string $entityText,
        ?string $entityKey,
        array $publicationBases,
        ?string $contactEmail,
        ?string $contactAddress,
        ?array $policyResult,
        string $register,
        string $schema
    ): array {
        $deadline = $this->deadlineChecker->calculateDeadline();

        // Resolve legalBasis and notes from publicationBases.
        if (count($publicationBases) > 0) {
            $legalBasis      = $this->notesHelper->truncateAtWordBoundary(value: $publicationBases[0]);
            $additionalBases = array_slice(array: $publicationBases, offset: 1);
        } else {
            $legalBasis      = null;
            $additionalBases = [];
        }

        $notes = $this->notesHelper->updateSentinelRegion(
            currentNotes: '',
            additionalBases: $additionalBases
        );

        $consentData = [
            'documentId'          => $documentId,
            'entityType'          => $entityType,
            'entityText'          => $entityText,
            'scope'               => 'document',
            'notificationStatus'  => 'pending',
            'consentStatus'       => 'pending',
            'publicationDecision' => 'pending',
            'objectionDeadline'   => $deadline->format(format: 'c'),
        ];

        if ($entityKey !== null && $entityKey !== '') {
            $consentData['entityKey'] = $entityKey;
        }

        if ($legalBasis !== null) {
            $consentData['legalBasis'] = $legalBasis;
        }

        if ($notes !== '') {
            $consentData['notes'] = $notes;
        }

        if ($contactEmail !== null) {
            $consentData['contactEmail'] = $contactEmail;
        }

        if ($contactAddress !== null) {
            $consentData['contactAddress'] = $contactAddress;
        }

        if ($policyResult !== null && $policyResult['kind'] === PolicyMatchService::KIND_STANDING_CONSENT) {
            $consentData['policyMatch'] = $policyResult['uuid'];
        }

        // Service-level scope contract (publication-consent-policy-fields
        // task 5): the schema cannot express "matchRules required when
        // scope=entity but forbidden when scope=document". The validator
        // throws InvalidArgumentException on violations, which the
        // controller surfaces as HTTP 400.
        $this->scopeValidator->assertValid($consentData);

        // Let OpenRegister enforce RBAC and multitenancy so the consent
        // record is owned by the creating user (security finding #283).
        $savedObject = $objectService->saveObject(
            object: $consentData,
            register: $register,
            schema: $schema
        );

        $this->logger->info(
            message: 'Consent request created',
            context: ['documentId' => $documentId]
        );

        $result = $savedObject->getObject();
        $result['wasUpdated'] = false;
        return $result;

    }//end createNewConsent()

    /**
     * Extract plain-array data from an ObjectService result item.
     *
     * @param mixed $candidate One element from searchObjects() result.
     *
     * @return array<string, mixed>
     */
    private function extractObjectData(mixed $candidate): array
    {
        if (is_array($candidate) === true) {
            return $candidate;
        }

        if (is_object($candidate) === true) {
            if (method_exists(object_or_class: $candidate, method: 'getObject') === true) {
                $payload = $candidate->getObject();
                if (is_array($payload) === true) {
                    // Ensure @self.id is present for the saveObject update path.
                    if (method_exists(object_or_class: $candidate, method: 'getUuid') === true) {
                        $uuid = $candidate->getUuid();
                        if ($uuid !== null && isset($payload['@self']) === false) {
                            $payload['@self'] = ['id' => $uuid];
                        }
                    }

                    return $payload;
                }
            }

            if (method_exists(object_or_class: $candidate, method: 'jsonSerialize') === true) {
                $payload = $candidate->jsonSerialize();
                if (is_array($payload) === true) {
                    return $payload;
                }
            }
        }//end if

        return [];

    }//end extractObjectData()

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
        return $this->updateHandler->updateConsentStatus(
            consentId: $consentId,
            register: $register,
            schema: $schema,
            data: $data
        );

    }//end updateConsentStatus()

    /**
     * Check if an objection deadline has expired
     *
     * @param string $consentId The consent object UUID
     * @param string $register  The register ID
     * @param string $schema    The schema ID
     *
     * @return bool True if the deadline has passed
     *
     * @throws Exception If check fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
     */
    public function checkObjectionDeadline(
        string $consentId,
        string $register,
        string $schema
    ): bool {
        return $this->deadlineChecker->checkObjectionDeadline(
            consentId: $consentId,
            register: $register,
            schema: $schema
        );

    }//end checkObjectionDeadline()

    /**
     * Get all consent records for a specific document
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
        return $this->updateHandler->getConsentsByDocument(
            documentId: $documentId,
            register: $register,
            schema: $schema,
            ownerUid: $ownerUid
        );

    }//end getConsentsByDocument()

    /**
     * Validate publication consent data against scope rules.
     *
     * @param array<string, mixed> $data Consent data to validate
     *
     * @return void
     *
     * @throws \InvalidArgumentException When data violates scope constraints
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     */
    public function validatePublicationConsentData(array $data): void
    {
        $scope = ($data['scope'] ?? null);

        if ($scope === 'document') {
            if (empty($data['documentId']) === true) {
                throw new \InvalidArgumentException(message: 'scope=document requires a non-empty documentId');
            }

            return;
        }

        if ($scope === 'entity') {
            if (isset($data['documentId']) === true) {
                throw new \InvalidArgumentException(message: 'scope=entity must not include documentId');
            }

            if (empty($data['matchRules']) === true) {
                throw new \InvalidArgumentException(message: 'scope=entity requires a non-empty matchRules array');
            }

            if (empty($data['consentMethod']) === true) {
                throw new \InvalidArgumentException(message: 'scope=entity requires a non-empty consentMethod');
            }

            if (isset($data['policyMatch']) === true) {
                throw new \InvalidArgumentException(message: 'scope=entity must not include policyMatch');
            }
        }//end if

    }//end validatePublicationConsentData()
}//end class
