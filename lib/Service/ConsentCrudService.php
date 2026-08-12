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
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
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
 * @spec openspec/specs/consent-management/spec.md
 */
class ConsentCrudService
{
    /**
     * Constructor for ConsentCrudService
     *
     * @param SettingsService $settingsService Settings service for register/schema IDs
     * @param ConsentService  $consentService  Consent service for consent operations
     * @param LoggerInterface $logger          Logger for security/audit events
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
     * @spec openspec/specs/consent-management/spec.md
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
     * @spec openspec/specs/consent-management/spec.md
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
     * @spec openspec/specs/consent-management/spec.md
     */
    public function getConsent(string $consentId, string $register, string $schema): ?array
    {
        // Do NOT pass `_rbac: false` / `_multitenancy: false` here — bypassing
        // them (security finding #283) is a regression and the multitenancy
        // half is genuinely load-bearing.
        //
        // ⚠️ But this is NOT what stops one user reading another's consent
        // record, and this comment used to imply that it was. The
        // `publicationConsent` schema declares `"authorization": null` in
        // `lib/Settings/docudesk_register.json`, and OpenRegister treats an
        // unconfigured authorization cascade as OPEN — so the per-object RBAC
        // half permits the read for any authenticated caller in the org.
        //
        // The control that actually closes #283 is
        // `ConsentController::canAccessConsent()`, which compares
        // `@self.owner` against the session uid with an admin bypass, and the
        // server-side owner filter on the list paths. Do not delete either as
        // "redundant with OpenRegister RBAC" — measured, it is not redundant.
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
     * Positional consent-creation fields consumed directly by createFromRequest.
     *
     * These three are passed as the named positional arguments to
     * {@see ConsentService::createConsentRequest()} and are therefore never
     * part of the forwarded `$extra` payload.
     *
     * @var array<string>
     */
    private const POSITIONAL_CREATE_FIELDS = [
        'documentId',
        'entityType',
        'entityText',
    ];

    /**
     * Server-controlled fields that callers must NOT set at creation time.
     *
     * These are the consent workflow/state fields whose values are owned by
     * the server's status machine and the policy-match engine. A request that
     * carries any of them is a probing/injection attempt: the field is
     * stripped and a structured security warning is logged naming the key
     * (NOT the value — ADR-005). DENYLIST, not allowlist — any field NOT
     * listed here (and not a framework key) is a legitimate extra that is
     * forwarded unchanged (finding #290b, PR #147 fifth-pass).
     *
     * @var array<string>
     */
    private const SERVER_CONTROLLED_CREATE_FIELDS = [
        'policyMatch',
        'matchKind',
        'consentStatus',
        'publicationDecision',
        'notificationStatus',
        'notificationSentAt',
        'objectionDeadline',
        'objectionReceivedAt',
        'objectionReason',
        'userId',
        'owner',
    ];

    /**
     * Framework / routing keys that leak into the request bag and must never
     * be forwarded to the domain layer. Stripped silently (not a security
     * event — they are an artefact of the request plumbing).
     *
     * @var array<string>
     */
    private const FRAMEWORK_REQUEST_KEYS = [
        '_route',
        '_method',
        '_format',
    ];

    /**
     * Create a consent request from controller data
     *
     * Uses a DENYLIST: the three positional fields (documentId, entityType,
     * entityText) are consumed directly; server-controlled status/policy
     * fields ({@see SERVER_CONTROLLED_CREATE_FIELDS}) are STRIPPED and a
     * security warning is logged naming the stripped keys; framework routing
     * keys are stripped silently; everything else is forwarded to
     * ConsentService as the `$extra` payload unchanged. This prevents callers
     * from forcing internal status fields at creation time (finding #290b)
     * while still allowing legitimate extra fields such as `consentScope`
     * (PR #147 fifth-pass).
     *
     * @param array<string, mixed> $data     The request data
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     *
     * @return array<string, mixed> The created or idempotently-updated consent
     *                              record, including the `wasUpdated` discriminator
     *
     * @throws \OCA\DocuDesk\Exception\PolicyRejectedException Propagated unwrapped when a
     *                                                         publication-prohibition rule matches; the
     *                                                         controller maps it to HTTP 403.
     * @throws Exception If creation fails
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    public function createFromRequest(array $data, string $register, string $schema): array
    {
        $documentId = (string) ($data['documentId'] ?? '');
        $entityType = (string) ($data['entityType'] ?? '');
        $entityText = (string) ($data['entityText'] ?? '');

        // Everything that is not a positional field is a candidate $extra.
        $extra = $data;
        foreach (self::POSITIONAL_CREATE_FIELDS as $field) {
            unset($extra[$field]);
        }

        // Strip framework routing keys silently.
        foreach (self::FRAMEWORK_REQUEST_KEYS as $key) {
            unset($extra[$key]);
        }

        // Strip server-controlled fields and record which keys were stripped
        // so the injection attempt is visible in the audit stream. ADR-005:
        // log the KEYS only, never the attacker-supplied values.
        $strippedKeys = [];
        foreach (self::SERVER_CONTROLLED_CREATE_FIELDS as $field) {
            if (array_key_exists($field, $extra) === true) {
                $strippedKeys[] = $field;
                unset($extra[$field]);
            }
        }

        if ($strippedKeys !== []) {
            $this->logger->warning(
                'ConsentCrudService: server-controlled fields stripped from consent creation request',
                ['strippedKeys' => $strippedKeys]
            );
        }

        return $this->consentService->createConsentRequest(
            documentId: $documentId,
            entityType: $entityType,
            entityText: $entityText,
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
     * @spec openspec/specs/consent-management/spec.md
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
     * Update consent status for a consent record, enforcing policy-transition rules
     *
     * Delegates to ConsentService::validateAndUpdateConsent() which checks for
     * policy-matched transition blocks and the override-up flow before saving.
     *
     * @param string               $consentId The consent object UUID
     * @param string               $register  The register ID
     * @param string               $schema    The schema ID
     * @param array<string, mixed> $data      The data to update
     * @param \OCP\IUser|null      $user      The acting user, or null for system context
     *
     * @return array<string, mixed> The updated consent record
     *
     * @throws Exception If update fails
     *
     * @spec openspec/specs/consent-management/spec.md
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
     */
    public function updateConsentStatus(
        string $consentId,
        string $register,
        string $schema,
        array $data,
        ?\OCP\IUser $user=null
    ): array {
        return $this->consentService->validateAndUpdateConsent($consentId, $register, $schema, $data, $user);

    }//end updateConsentStatus()
}//end class
