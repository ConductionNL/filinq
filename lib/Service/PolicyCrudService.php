<?php
/**
 * Policy CRUD Service
 *
 * Service for CRUD operations on the two policy surfaces introduced by the
 * `entity-publication-policies` change:
 *
 *   - `publicationProhibition` records (the entity-level deny-list).
 *   - `publicationConsent` records with `scope: "entity"` (standing consents).
 *
 * The CRUD layer dispatches to OpenRegister's ObjectService and enforces the
 * spec-level scope-validation rules via `ConsentService::validatePublicationConsentData`.
 * Schema-level RBAC stays unchanged (task 7); this layer is the place to
 * enforce DocuDesk-specific service-level gates (e.g. the standing-consent
 * scope check called out in spec §RBAC).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use InvalidArgumentException;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * CRUD wrapper around the two policy surfaces.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class PolicyCrudService
{

    /**
     * Register slug shared by both policy surfaces.
     */
    public const REGISTER = 'consent';

    /**
     * Schema slug for the deny-list surface.
     */
    public const SCHEMA_PROHIBITION = 'publicationProhibition';

    /**
     * Schema slug shared between standing consents and per-document records.
     */
    public const SCHEMA_CONSENT = 'publicationConsent';

    /**
     * Policy surface selector for the entity-level deny-list.
     *
     * Used as the `$surface` argument to {@see self::requirePolicyPermission()}.
     */
    public const SURFACE_PROHIBITION = 'prohibition';

    /**
     * Policy surface selector for standing consents (scope=entity records).
     *
     * Used as the `$surface` argument to {@see self::requirePolicyPermission()}.
     */
    public const SURFACE_STANDING_CONSENT = 'standingConsent';

    /**
     * Group whose members may create/update/delete standing-consent records.
     *
     * Enforced at service level per spec §RBAC scenario "Standing-consent
     * write requires standing-consent permission" — the underlying
     * publicationConsent schema's RBAC cannot discriminate by `scope`, so the
     * scope-aware gate lives here.
     */
    public const STANDING_CONSENT_GROUP = 'docudesk-standing-consent-admins';

    /**
     * Group whose members may create/update/delete publication-prohibition
     * records. Prohibitions are operator-level blocking rules that override
     * the standard anonymise flow, so write authorisation requires either
     * admin role or membership in this group — never any authenticated user.
     */
    public const PROHIBITION_GROUP = 'docudesk-prohibition-admins';

    /**
     * Constructor.
     *
     * @param SettingsService $settingsService Settings service providing ObjectService.
     * @param ConsentService  $consentService  Consent service for scope-validation.
     * @param IGroupManager   $groupManager    Group membership check.
     * @param IUserSession    $userSession     Current-user lookup.
     * @param LoggerInterface $logger          Structured logger.
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ConsentService $consentService,
        private readonly IGroupManager $groupManager,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * List all publicationProhibition records.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception On query failure.
     */
    public function listProhibitions(): array
    {
        return $this->listByRegisterSchema(
            register: self::REGISTER,
            schema: self::SCHEMA_PROHIBITION
        );

    }//end listProhibitions()

    /**
     * List publicationConsent records with `scope: "entity"` (standing consents).
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception On query failure.
     */
    public function listStandingConsents(): array
    {
        $rows = $this->listByRegisterSchema(
            register: self::REGISTER,
            schema: self::SCHEMA_CONSENT
        );

        return array_values(
                array_filter(
            $rows,
            static fn (array $r): bool => ($r['scope'] ?? 'document') === 'entity'
        )
                );

    }//end listStandingConsents()

    /**
     * Get a single prohibition by UUID.
     *
     * @param string $uuid The record UUID.
     *
     * @return array<string, mixed>|null
     *
     * @throws Exception On lookup failure.
     */
    public function getProhibition(string $uuid): ?array
    {
        return $this->findOne(
            register: self::REGISTER,
            schema: self::SCHEMA_PROHIBITION,
            uuid: $uuid
        );

    }//end getProhibition()

    /**
     * Get a single standing consent by UUID, asserting its scope.
     *
     * @param string $uuid The record UUID.
     *
     * @return array<string, mixed>|null
     *
     * @throws Exception On lookup failure.
     */
    public function getStandingConsent(string $uuid): ?array
    {
        $record = $this->findOne(
            register: self::REGISTER,
            schema: self::SCHEMA_CONSENT,
            uuid: $uuid
        );

        if ($record === null) {
            return null;
        }

        if (($record['scope'] ?? 'document') !== 'entity') {
            return null;
        }

        return $record;

    }//end getStandingConsent()

    /**
     * Create a publicationProhibition record.
     *
     * @param array<string, mixed> $data Caller-supplied prohibition data.
     *
     * @return array<string, mixed> The created record.
     *
     * @throws Exception On write failure.
     */
    public function createProhibition(array $data): array
    {
        $this->assertProhibitionPermission(action: 'create');

        $payload = $this->stripFrameworkParams(data: $data);

        // Defaults the spec requires the record to carry.
        $payload['active'] = ($payload['active'] ?? true);

        return $this->saveObject(
            register: self::REGISTER,
            schema: self::SCHEMA_PROHIBITION,
            data: $payload
        );

    }//end createProhibition()

    /**
     * Update an existing prohibition record.
     *
     * @param string               $uuid The record UUID.
     * @param array<string, mixed> $data The updated data.
     *
     * @return array<string, mixed> The updated record.
     *
     * @throws Exception On write failure.
     */
    public function updateProhibition(string $uuid, array $data): array
    {
        $this->assertProhibitionPermission(action: 'update');

        $payload = $this->stripFrameworkParams(data: $data);
        return $this->saveObject(
            register: self::REGISTER,
            schema: self::SCHEMA_PROHIBITION,
            data: $payload,
            uuid: $uuid
        );

    }//end updateProhibition()

    /**
     * Delete a prohibition record.
     *
     * NOTE: this can never succeed while `publicationProhibition` declares
     * `x-openregister-archival` (see `lib/Settings/docudesk_register.json`).
     * OpenRegister's `ObjectService::deleteObject()` refuses every user-driven
     * delete on an archival schema and throws
     * `OCA\OpenRegister\Exception\ArchivalImmutableException`; rows are removed
     * only by `OCA\OpenRegister\Cron\ArchivalRetentionTask` once their
     * retention lapses. `PolicyController::deleteProhibition()` translates that
     * refusal into HTTP 409 Conflict instead of letting it surface as a 500.
     *
     * In practice this method therefore always throws: an
     * `ArchivalImmutableException` while the annotation is present, or another
     * `Exception` on an unrelated failure. It is kept (rather than removed)
     * because the annotation is a policy declaration that may be lifted, and
     * because the endpoint must keep answering a specific, documented status.
     *
     * @param string $uuid The record UUID.
     *
     * @return void
     *
     * @throws Exception On deletion failure, including OpenRegister's
     *                   ArchivalImmutableException (not type-hinted here: the class is
     *                   absent during static analysis, OpenRegister is a runtime sibling).
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function deleteProhibition(string $uuid): void
    {
        $this->assertProhibitionPermission(action: 'delete');

        $objectService = $this->settingsService->getObjectService();
        $objectService->deleteObject(
            uuid: $uuid,
            register: self::REGISTER,
            schema: self::SCHEMA_PROHIBITION,
            _rbac: false,
            _multitenancy: false
        );

    }//end deleteProhibition()

    /**
     * Create a standing consent (scope=entity publicationConsent) record.
     *
     * Service-level scope validation rejects records without `matchRules` or
     * `consentMethod`, or that include a `documentId`.
     *
     * @param array<string, mixed> $data Caller-supplied data.
     *
     * @return array<string, mixed> The created record.
     *
     * @throws Exception On write failure.
     */
    public function createStandingConsent(array $data): array
    {
        $this->assertStandingConsentPermission(action: 'create');

        $payload           = $this->stripFrameworkParams(data: $data);
        $payload['scope']  = 'entity';
        $payload['active'] = ($payload['active'] ?? true);

        $this->consentService->validatePublicationConsentData(data: $payload);

        return $this->saveObject(
            register: self::REGISTER,
            schema: self::SCHEMA_CONSENT,
            data: $payload
        );

    }//end createStandingConsent()

    /**
     * Update an existing standing consent record.
     *
     * @param string               $uuid The record UUID.
     * @param array<string, mixed> $data The updated data.
     *
     * @return array<string, mixed> The updated record.
     *
     * @throws Exception On write failure.
     */
    public function updateStandingConsent(string $uuid, array $data): array
    {
        $this->assertStandingConsentPermission(action: 'update');

        $existing = $this->getStandingConsent(uuid: $uuid);
        if ($existing === null) {
            throw new Exception('Standing consent not found: '.$uuid);
        }

        $payload          = array_merge($existing, $this->stripFrameworkParams(data: $data));
        $payload['scope'] = 'entity';

        $this->consentService->validatePublicationConsentData(data: $payload);

        return $this->saveObject(
            register: self::REGISTER,
            schema: self::SCHEMA_CONSENT,
            data: $payload,
            uuid: $uuid
        );

    }//end updateStandingConsent()

    /**
     * Delete a standing consent.
     *
     * Unlike {@see deleteProhibition()} this really can succeed: the
     * `publicationConsent` schema declares NO `x-openregister-archival` in
     * `lib/Settings/docudesk_register.json`, so OpenRegister's archival gate
     * does not apply to it.
     *
     * @param string $uuid The record UUID.
     *
     * @return void
     *
     * @throws Exception On deletion failure.
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function deleteStandingConsent(string $uuid): void
    {
        $this->assertStandingConsentPermission(action: 'delete');

        $existing = $this->getStandingConsent(uuid: $uuid);
        if ($existing === null) {
            throw new Exception('Standing consent not found: '.$uuid);
        }

        $objectService = $this->settingsService->getObjectService();
        $objectService->deleteObject(
            uuid: $uuid,
            register: self::REGISTER,
            schema: self::SCHEMA_CONSENT,
            _rbac: false,
            _multitenancy: false
        );

    }//end deleteStandingConsent()

    /**
     * Public entry point to the per-surface permission gates — called by the
     * PolicyController and the StandingConsentController.
     *
     * Delegates to the private gate for the requested surface so the controllers
     * do not need to duplicate the permission logic. Throws RuntimeException
     * (mapped to 403) when the current user is not authorised, and
     * InvalidArgumentException — never a silent pass — for an unknown surface.
     *
     * @param string $surface The policy surface: self::SURFACE_PROHIBITION or self::SURFACE_STANDING_CONSENT.
     * @param string $action  The operation being authorised ('read', 'create', 'update', 'delete').
     *
     * @return void
     *
     * @throws RuntimeException         When the current user is not authorised.
     * @throws InvalidArgumentException When $surface names no known policy surface.
     *
     * @spec openspec/changes/archive/2026-06-14-publication-prohibition-schema/tasks.md
     * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
     */
    public function requirePolicyPermission(string $surface, string $action): void
    {
        if ($surface === self::SURFACE_PROHIBITION) {
            $this->assertProhibitionPermission(action: $action);
            return;
        }

        if ($surface === self::SURFACE_STANDING_CONSENT) {
            $this->assertStandingConsentPermission(action: $action);
            return;
        }

        throw new InvalidArgumentException(
            message: 'Unknown policy surface: '.$surface
        );

    }//end requirePolicyPermission()

    /**
     * Enforce service-level standing-consent group membership.
     *
     * Spec §RBAC, scenario "Standing-consent write requires standing-consent
     * permission". A consent-officer with normal `publicationConsent` write
     * permission may write `scope: "document"` records, but writes to
     * `scope: "entity"` require explicit membership in
     * `docudesk-standing-consent-admins`.
     *
     * Admin users bypass this gate (NC convention — they implicitly belong to
     * every privileged group).
     *
     * @param string $action 'create', 'update', or 'delete' (used in error msg only).
     *
     * @return void
     *
     * @throws RuntimeException When the current user is not authorised. Mapped to 403 by the controller.
     */
    private function assertStandingConsentPermission(string $action): void
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException(
                'Standing-consent '.$action.' requires an authenticated user.'
            );
        }

        if ($this->groupManager->isAdmin($user->getUID()) === true) {
            return;
        }

        if ($this->groupManager->isInGroup($user->getUID(), self::STANDING_CONSENT_GROUP) === true) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Standing-consent %s requires membership in the "%s" group.',
                $action,
                self::STANDING_CONSENT_GROUP
            )
        );

    }//end assertStandingConsentPermission()

    /**
     * Assert the current user can create/update/delete a prohibition record.
     *
     * Prohibitions are tenant-wide blocking rules; write authorisation requires
     * admin role or membership in `PROHIBITION_GROUP`. Throws otherwise.
     *
     * @param string $action The operator action being authorised (`create`, `update`, `delete`).
     *
     * @return void
     *
     * @throws RuntimeException When the current user is not authorised. Mapped to 403 by the controller.
     */
    private function assertProhibitionPermission(string $action): void
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException(
                message: 'Prohibition '.$action.' requires an authenticated user.'
            );
        }

        if ($this->groupManager->isAdmin($user->getUID()) === true) {
            return;
        }

        if ($this->groupManager->isInGroup($user->getUID(), self::PROHIBITION_GROUP) === true) {
            return;
        }

        throw new RuntimeException(
            message: sprintf(
                'Prohibition %s requires membership in the "%s" group.',
                $action,
                self::PROHIBITION_GROUP
            )
        );

    }//end assertProhibitionPermission()

    /**
     * Strip framework-injected request params before persistence.
     *
     * @param array<string, mixed> $data Raw incoming data.
     *
     * @return array<string, mixed>
     */
    private function stripFrameworkParams(array $data): array
    {
        unset($data['_route'], $data['_method'], $data['id'], $data['uuid']);
        return $data;

    }//end stripFrameworkParams()

    /**
     * List records by register+schema slugs and serialise them to plain arrays.
     *
     * @param string $register Register slug.
     * @param string $schema   Schema slug.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception On query failure.
     */
    private function listByRegisterSchema(string $register, string $schema): array
    {
        $objectService = $this->settingsService->getObjectService();
        // Use the slug-aware variant — OR's standard searchObjects requires
        // numeric register/schema IDs and silently returns nothing otherwise.
        $results = $objectService->searchObjectsBySlug(
            registerSlug: $register,
            schemaSlug: $schema,
            _rbac: false,
            _multitenancy: false
        );

        if (is_int($results) === true) {
            return [];
        }

        $rows = [];
        foreach ($results as $result) {
            if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
                $rows[] = $result->jsonSerialize();
                continue;
            }

            if (is_array($result) === true) {
                $rows[] = $result;
            }
        }

        return $rows;

    }//end listByRegisterSchema()

    /**
     * Look up one record by UUID.
     *
     * @param string $register Register slug.
     * @param string $schema   Schema slug.
     * @param string $uuid     Record UUID.
     *
     * @return array<string, mixed>|null
     *
     * @throws Exception On lookup failure.
     */
    private function findOne(string $register, string $schema, string $uuid): ?array
    {
        $objectService = $this->settingsService->getObjectService();
        $object        = $objectService->find(
            id: $uuid,
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

    }//end findOne()

    /**
     * Persist a record via ObjectService::saveObject.
     *
     * @param string               $register Register slug.
     * @param string               $schema   Schema slug.
     * @param array<string, mixed> $data     The record payload.
     * @param string|null          $uuid     Optional UUID for updates.
     *
     * @return array<string, mixed>
     *
     * @throws Exception On write failure.
     */
    private function saveObject(
        string $register,
        string $schema,
        array $data,
        ?string $uuid=null
    ): array {
        try {
            $objectService = $this->settingsService->getObjectService();
            $saved         = $objectService->saveObject(
                object: $data,
                register: $register,
                schema: $schema,
                uuid: $uuid,
                _rbac: false,
                _multitenancy: false
            );
            return $saved->getObject();
        } catch (Exception $e) {
            $this->logger->error(
                'PolicyCrudService: save failed',
                ['schema' => $schema, 'uuid' => $uuid, 'error' => $e->getMessage()]
            );
            throw $e;
        }//end try

    }//end saveObject()
}//end class
