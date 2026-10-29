<?php

/**
 * Contract of ConsentService
 *
 * The public surface of the ConsentService service, so callers in other
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

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use OCP\IUser;
use OCA\Filinq\Exception\PolicyRejectedException;

/**
 * Contract of ConsentService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ConsentServiceInterface {
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
	 * Checks for a matching standing-consent (scope:entity) record first.
	 * If one is found the record is auto-resolved with consentStatus=consent_given
	 * and policyMatch set to the matching entity-scope record UUID.
	 * If no match is found, the standard WOO objection-period workflow is used.
	 *
	 * @param string $documentId The document UUID
	 * @param string $entityType The entity type
	 * @param string $entityText The detected entity text
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $extra Additional consent data.
	 *                                    Recognised keys:
	 *                                    - entityKey (string|null): OR entity UUID.
	 *                                    - publicationBases (string[]): bases array;
	 *                                    [0] → legalBasis, [1..] → notes sentinel.
	 *                                    - contactEmail (string): contact e-mail.
	 *                                    - contactAddress (string): contact address.
	 *
	 * @return array<string, mixed> The created or updated consent record (includes `wasUpdated`).
	 *
	 * @throws PolicyRejectedException If a prohibition rule matches the entity.
	 * @throws Exception If consent creation/update fails.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
	 * @spec openspec/specs/consent-management/spec.md
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-6
	 */
	public function createConsentRequest(
		string $documentId,
		string $entityType,
		string $entityText,
		string $register,
		string $schema,
		array $extra = [],
	): array;

	/**
	 * Validate and update a consent record, enforcing policy-transition rules
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $data The update payload
	 * @param IUser|null $user The acting user, or null for system context
	 *
	 * @return array<string, mixed> The updated consent record
	 *
	 * @throws \InvalidArgumentException When the transition is blocked by policy-match
	 * @throws Exception When the update fails
	 *
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
	 */
	public function validateAndUpdateConsent(string $consentId, string $register, string $schema, array $data, ?IUser $user = null): array;

	/**
	 * Update consent status for a consent record
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $data The data to update
	 * @param string $actor The user making the change, recorded on each status event
	 *
	 * @return array<string, mixed> The updated consent record
	 *
	 * @throws Exception If update fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function updateConsentStatus(string $consentId, string $register, string $schema, array $data, string $actor = ''): array;

	/**
	 * Check if an objection deadline has expired
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 *
	 * @return bool True if the deadline has passed
	 *
	 * @throws Exception If check fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function checkObjectionDeadline(string $consentId, string $register, string $schema): bool;

	/**
	 * Get all consent records for a specific document
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
	public function validatePublicationConsentData(array $data): void;

	/**
	 * Whether a document's consent requests allow publication. Reads only.
	 *
	 * Reads every record for the document, not only the caller's: the verdict
	 * names record ids and never personal data, and a clearance that skipped
	 * someone else's objection would clear too much.
	 *
	 * @param string            $documentId The document
	 * @param string            $register   The consent register
	 * @param string            $schema     The consent schema
	 * @param DateTimeImmutable $now        The moment deadlines are judged at
	 *
	 * @return array{clear: bool, reasons: list<string>} The verdict.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.1
	 */
	public function isDocumentConsentClear(string $documentId, string $register, string $schema, DateTimeImmutable $now): array;
}//end interface
