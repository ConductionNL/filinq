<?php

/**
 * Signing audit actor
 *
 * Who made a step of a signing request, as the audit trail records it.
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
 * The user id, display name and IP address of the actor of one audit entry.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 */
final class SigningAuditActor {

	/**
	 * Constructor.
	 *
	 * @param string $userId      The actor user ID
	 * @param string $displayName The actor display name
	 * @param string $ipAddress   The actor IP address
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $userId,
		public readonly string $displayName,
		public readonly string $ipAddress,
	) {

	}//end __construct()
}//end class
