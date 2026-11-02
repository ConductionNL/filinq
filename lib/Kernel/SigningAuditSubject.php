<?php

/**
 * Signing audit subject
 *
 * The signing request an audit entry is about, with its signature level and provider.
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
 * The request id, signature level and provider of one audit entry.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 */
final class SigningAuditSubject {

	/**
	 * Constructor.
	 *
	 * @param string $id             The signing request UUID
	 * @param string $signatureLevel The signature level
	 * @param string $provider       The signing provider
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $signatureLevel = '',
		public readonly string $provider = '',
	) {

	}//end __construct()
}//end class
