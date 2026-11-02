<?php

/**
 * Signing audit event
 *
 * One step of a signing request for the audit trail: which request, what happened, who did it and what else to keep.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel;

/**
 * The subject, action, actor and metadata of one signing audit entry.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 */
final class SigningAuditEvent {

	/**
	 * Constructor.
	 *
	 * @param SigningAuditSubject  $request  The signing request
	 * @param string               $action   The action type (must be in VALID_ACTIONS)
	 * @param SigningAuditActor    $actor    Who did it
	 * @param array<string, mixed> $metadata Additional metadata (pass-through)
	 *
	 * @return void
	 */
	public function __construct(
		public readonly SigningAuditSubject $request,
		public readonly string $action,
		public readonly SigningAuditActor $actor,
		public readonly array $metadata = [],
	) {

	}//end __construct()
}//end class
