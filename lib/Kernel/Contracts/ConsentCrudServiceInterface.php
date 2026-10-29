<?php

/**
 * Contract of ConsentCrudService
 *
 * The public surface of the ConsentCrudService service, so callers in other
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
use OCP\IUser;
use OCA\Filinq\Exception\PolicyRejectedException;

/**
 * Contract of ConsentCrudService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ConsentCrudServiceInterface {
	/**
	 * Get the consent register and schema IDs from settings
	 *
	 * @return array{register: string, schema: string}|null Config or null if not configured
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function getConsentConfig(): ?array;

	/**
	 * List consent records, optionally scoped to a single owner
	 *
	 * When $ownerUid is provided (non-admin callers) the search is filtered
	 * server-side so only records whose @self.owner matches the caller are
	 * returned. Passing null returns all records (admin callers only).
	 *
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param string|null $ownerUid UID to scope results to, or null for all
	 *
	 * @return array<int, array<string, mixed>> List of consent records
	 *
	 * @throws Exception If listing fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function listConsents(string $register, string $schema, ?string $ownerUid = null): array;

	/**
	 * Get a single consent record by ID
	 *
	 * @param string $consentId The consent record UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 *
	 * @return array<string, mixed>|null The consent record or null if not found
	 *
	 * @throws Exception If retrieval fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function getConsent(string $consentId, string $register, string $schema): ?array;

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
	 * @param array<string, mixed> $data The request data
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 *
	 * @return array<string, mixed> The created or idempotently-updated consent
	 *                              record, including the `wasUpdated` discriminator
	 *
	 * @throws \OCA\Filinq\Exception\PolicyRejectedException Propagated unwrapped when a
	 *                                                       publication-prohibition rule matches; the
	 *                                                       controller maps it to HTTP 403.
	 * @throws Exception If creation fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function createFromRequest(array $data, string $register, string $schema): array;

	/**
	 * Get consent records for a specific document, optionally scoped to one owner
	 *
	 * @param string $documentId The document UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param string|null $ownerUid UID to scope results to, or null for all
	 *
	 * @return array<int, array<string, mixed>> List of consent records
	 *
	 * @throws Exception If query fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function getConsentsByDocument(string $documentId, string $register, string $schema, ?string $ownerUid = null): array;

	/**
	 * Update consent status for a consent record, enforcing policy-transition rules
	 *
	 * Delegates to ConsentService::validateAndUpdateConsent() which checks for
	 * policy-matched transition blocks and the override-up flow before saving.
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $data The data to update
	 * @param \OCP\IUser|null $user The acting user, or null for system context
	 *
	 * @return array<string, mixed> The updated consent record
	 *
	 * @throws Exception If update fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
	 */
	public function updateConsentStatus(string $consentId, string $register, string $schema, array $data, ?IUser $user = null): array;
}//end interface
