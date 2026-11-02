<?php

/**
 * Contract of SigningService
 *
 * The public surface of the SigningService service, so callers in other
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

use InvalidArgumentException;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use RuntimeException;
use OCA\Filinq\Exception\StepUpRequiredException;

/**
 * Contract of SigningService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningServiceInterface {
	/**
	 * Create a new signing request
	 *
	 * @param array<string, mixed> $data The signing request data
	 *
	 * @return array<string, mixed> The created signing request
	 *
	 * @throws RuntimeException If creation fails
	 * @throws \InvalidArgumentException With code 400 when a guardian entry is invalid,
	 *                                   or a signer under the age of consent has no guardian.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function createRequest(array $data): array;

	/**
	 * Get a signing request by ID
	 *
	 * Access control: a SCOPED caller (non-empty $callerUserId) must be the
	 * initiator or a listed signer. Pass callerUserId='' to read UNSCOPED —
	 * that is the single, explicit bypass, used by an NC admin caller and by
	 * internal methods that have already verified access.
	 *
	 * There is deliberately no separate `isAdmin` flag: the previous guard was
	 * `$callerUserId !== '' && $isAdmin === false`, so an admin caller and an
	 * unscoped caller already took the identical path. Collapsing the two
	 * spellings into one means there is exactly ONE way to bypass scoping, and
	 * it is visible at the call site.
	 *
	 * @param string $requestId The signing request ID
	 * @param string $callerUserId UID to scope the read to ('' = unscoped)
	 *
	 * @return array<string, mixed>|null The signing request, or null when a
	 *                                   scoped caller (callerUserId set,
	 *                                   non-admin) is neither initiator nor
	 *                                   signer, or the request does not exist.
	 *
	 * @throws RegisterNotConfiguredException When the signing register is not configured.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
	 */
	public function getRequest(string $requestId, string $callerUserId = ''): ?array;

	/**
	 * List signing requests scoped to the calling user
	 *
	 * A SCOPED caller (non-empty $callerUserId) sees only requests where they
	 * are the initiator or a listed signer (WF2 fix: previously returned all
	 * requests regardless of ownership — full cross-tenant data disclosure).
	 * Pass callerUserId='' to list UNSCOPED — that is the single, explicit
	 * bypass, used by an NC admin caller.
	 *
	 * As in getRequest(), there is deliberately no separate `isAdmin` flag:
	 * the previous guard was `$isAdmin === false && $callerUserId !== ''`, so
	 * an admin caller and an unscoped caller already took the identical path.
	 *
	 * @param string $callerUserId UID to scope the listing to ('' = unscoped)
	 *
	 * @return array<int, array<string, mixed>> List of signing requests
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
	 */
	public function listRequests(string $callerUserId = ''): array;

	/**
	 * Sign a document within a signing request
	 *
	 * @param string $requestId The signing request ID
	 * @param string $signerId The signer record ID
	 * @param array<string, mixed>|null $verifiedActor The already-resolved, verified
	 *                                                 external actor (portal-signing-actions
	 *                                                 REQ-DDPSA-005): `email` (invited signer
	 *                                                 identity), and optionally `subjectRef`,
	 *                                                 `identityRef`, `trust`, `jti` from the
	 *                                                 verified portal assertion. When null
	 *                                                 (default) the actor is the Nextcloud
	 *                                                 session user, exactly as before — this
	 *                                                 parameter is an ADDITIVE seam, not a
	 *                                                 behaviour change for existing callers.
	 * @param array<string, mixed>|null $signatureData Optional evidence to record on the
	 *                                                 signer record's `signatureData` field
	 *                                                 (portal-signing-surface REQ-DDPSS-002:
	 *                                                 consent confirmation + optional drawn
	 *                                                 signature). Never trusted for identity.
	 *
	 * @return array<string, mixed> The updated signer record
	 *
	 * @throws RuntimeException If signing fails
	 * @throws \OCA\Filinq\Exception\StepUpRequiredException With code 403, before any write, when the
	 *                                                         identity evidence is missing, stale, from an
	 *                                                         unregistered provider or below the assurance
	 *                                                         this signer needs (signer-identity-rails
	 *                                                         REQ-DDSIR-003). The evidence goes onto the
	 *                                                         record and the SIGNED audit entry.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function sign(string $requestId, string $signerId, ?array $verifiedActor = null, ?array $signatureData = null): array;

	/**
	 * Decline a signing request
	 *
	 * @param string $requestId The signing request ID
	 * @param string $signerId The signer record ID
	 * @param string $reason The decline reason
	 * @param array<string, mixed>|null $verifiedActor The already-resolved, verified
	 *                                                 external actor (see `sign()`); null
	 *                                                 (default) behaves exactly as before.
	 *
	 * @return array<string, mixed> The updated signer record
	 *
	 * @throws RuntimeException If the request is not in a state that can be
	 *                          declined, or the actor is not authorised.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 * @spec openspec/specs/document-signing/spec.md
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 */
	public function decline(string $requestId, string $signerId, string $reason, ?array $verifiedActor = null): array;

	/**
	 * Cancel a signing request
	 *
	 * @param string $requestId The signing request ID
	 *
	 * @return array<string, mixed>|null The cancelled request, or null
	 *                                   when the request is not found (or
	 *                                   not accessible to a non-admin
	 *                                   caller). Wilco #6 fix (filinq#100,
	 *                                   2026-06-06): callers must collapse
	 *                                   null to a single 404 — never split
	 *                                   into 404-vs-403, which would be an
	 *                                   existence-probing oracle.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 */
	public function cancelRequest(string $requestId): ?array;

	/**
	 * Bulk sign multiple signing requests
	 *
	 * @param array<string> $requestIds Array of request IDs to sign
	 *
	 * @return array<string, array<string, mixed>> Results keyed by request ID
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-4
	 */
	public function bulkSign(array $requestIds): array;

	/**
	 * Validate a status transition
	 *
	 * @param string $currentStatus The current status
	 * @param string $newStatus The proposed new status
	 *
	 * @return bool True if transition is valid
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 */
	public function isValidTransition(string $currentStatus, string $newStatus): bool;

	/**
	 * Emit a terminal SigningConcludedEvent for an expired request.
	 *
	 * Public entry point for the SigningExpirationJob, which marks requests
	 * EXPIRED outside this service. Delegates to the shared fail-soft helper so
	 * the cross-app contract has a single emission source. Only fires for a
	 * delegated (provenance-carrying) request; internal requests emit nothing.
	 *
	 * @param array<string, mixed> $request The persisted (EXPIRED) signing-request array
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 *
	 * @return void
	 */
	public function emitExpiredConclusion(array $request): void;
}//end interface
