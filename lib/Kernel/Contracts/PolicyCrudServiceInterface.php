<?php

/**
 * Contract of PolicyCrudService
 *
 * The public surface of the PolicyCrudService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Contract of PolicyCrudService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PolicyCrudServiceInterface {
	/**
	 * Register slug shared by both policy surfaces.
	 *
	 * `filinq`, not `consent`: this app declares ONE register holding all 23
	 * schemas. The five it used to declare are retired.
	 */
	public const REGISTER = 'filinq';

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
	 *
	 * Renamed from `docudesk-*` with a membership migration; see
	 * PROHIBITION_GROUP below.
	 */
	public const STANDING_CONSENT_GROUP = 'filinq-standing-consent-admins';

	/**
	 * Group whose members may create/update/delete publication-prohibition
	 * records. Prohibitions are operator-level blocking rules that override
	 * the standard anonymise flow, so write authorisation requires either
	 * admin role or membership in this group — never any authenticated user.
	 *
	 * This is a Nextcloud GROUP ID, declared in `lib/Settings/filinq_register.json`
	 * and provisioned by OpenRegister CREATE-ONLY, then populated by admins.
	 * Renaming an id creates a NEW, EMPTY group, so the move from `docudesk-*`
	 * to `filinq-*` is carried by the RenameRoleGroups repair step, which copies
	 * every member of the old group into the new one and deletes nothing.
	 */
	public const PROHIBITION_GROUP = 'filinq-prohibition-admins';

	/**
	 * List all publicationProhibition records.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws Exception On query failure.
	 */
	public function listProhibitions(): array;

	/**
	 * List publicationConsent records with `scope: "entity"` (standing consents).
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws Exception On query failure.
	 */
	public function listStandingConsents(): array;

	/**
	 * Get a single prohibition by UUID.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @throws Exception On lookup failure.
	 */
	public function getProhibition(string $uuid): ?array;

	/**
	 * Get a single standing consent by UUID, asserting its scope.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @throws Exception On lookup failure.
	 */
	public function getStandingConsent(string $uuid): ?array;

	/**
	 * Create a publicationProhibition record.
	 *
	 * @param array<string, mixed> $data Caller-supplied prohibition data.
	 *
	 * @return array<string, mixed> The created record.
	 *
	 * @throws Exception On write failure.
	 */
	public function createProhibition(array $data): array;

	/**
	 * Update an existing prohibition record.
	 *
	 * @param string $uuid The record UUID.
	 * @param array<string, mixed> $data The updated data.
	 *
	 * @return array<string, mixed> The updated record.
	 *
	 * @throws Exception On write failure.
	 */
	public function updateProhibition(string $uuid, array $data): array;

	/**
	 * Delete a prohibition record.
	 *
	 * NOTE: this can never succeed while `publicationProhibition` declares
	 * `x-openregister-archival` (see `lib/Settings/filinq_register.json`).
	 * OpenRegister's `ObjectService::deleteObject()` refuses every user-driven
	 * delete on an archival schema and throws
	 * `OCA\OpenRegister\Exception\ArchivalImmutableException`; rows are removed
	 * only by `OCA\OpenRegister\BackgroundJob\ArchivalRetentionTask` once their
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
	public function deleteProhibition(string $uuid): void;

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
	public function createStandingConsent(array $data): array;

	/**
	 * Update an existing standing consent record.
	 *
	 * @param string $uuid The record UUID.
	 * @param array<string, mixed> $data The updated data.
	 *
	 * @return array<string, mixed> The updated record.
	 *
	 * @throws Exception On write failure.
	 */
	public function updateStandingConsent(string $uuid, array $data): array;

	/**
	 * Delete a standing consent.
	 *
	 * Unlike {@see deleteProhibition()} this really can succeed: the
	 * `publicationConsent` schema declares NO `x-openregister-archival` in
	 * `lib/Settings/filinq_register.json`, so OpenRegister's archival gate
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
	public function deleteStandingConsent(string $uuid): void;

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
	 * @param string $action The operation being authorised ('read', 'create', 'update', 'delete').
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the current user is not authorised.
	 * @throws InvalidArgumentException When $surface names no known policy surface.
	 *
	 * @spec openspec/changes/archive/2026-06-14-publication-prohibition-schema/tasks.md
	 * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
	 */
	public function requirePolicyPermission(string $surface, string $action): void;
}//end interface
