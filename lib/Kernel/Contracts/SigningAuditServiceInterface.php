<?php

/**
 * Contract of SigningAuditService
 *
 * The public surface of the SigningAuditService service, so callers in other
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

use OCA\Filinq\Kernel\SigningAuditEvent;
use RuntimeException;

/**
 * Contract of SigningAuditService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningAuditServiceInterface {
	/**
	 * Log a signing audit event via OR's native audit trail.
	 *
	 * Binds the entry to the REAL signing-request object (register `signing`,
	 * schema `signingRequest`) so its register/schema/object-id linkage is
	 * real and the hash chain anchors to an actual row — closing the #289
	 * residual where every entry was created from a uuid-only `ObjectEntity`
	 * stub (signing-trust-rebuild REQ-DDSTR-006). Fail-soft: when the request
	 * no longer resolves (deleted mid-flight) the entry is still written with
	 * the uuid-only fallback and a warning is logged — an unlinked audit entry
	 * is acceptable, a dropped one is not.
	 *
	 * @param SigningAuditEvent $event The request, action, actor and metadata.
	 *
	 * @return array<string, mixed> The created audit entry serialised.
	 *
	 * @throws RuntimeException If the action is not valid.
	 *
	 * @spec openspec/specs/signing-audit-via-or/spec.md
	 */
	public function logEvent(SigningAuditEvent $event): array;

	/**
	 * Get all audit entries for a signing request from OR's audit trail.
	 *
	 * Queries OR's audit trail scoped to the request's object identity
	 * (`object_uuid` filter, pushed into `AuditTrailMapper::findAll()`)
	 * instead of fetching every `docudesk.signing.*` entry fleet-wide and
	 * filtering in PHP — closing the #289 residual (signing-trust-rebuild
	 * REQ-DDSTR-007). The action-type filter is retained alongside it so a
	 * non-signing entry that happened to share an objectUuid (should not
	 * occur, but costs nothing to exclude) can never leak in.
	 *
	 * @param string $signingRequestId The signing request UUID.
	 *
	 * @return array<int, array<string, mixed>> Audit entries in chronological order.
	 *
	 * @spec openspec/specs/signing-audit-via-or/spec.md
	 */
	public function getAuditTrail(string $signingRequestId): array;
}//end interface
