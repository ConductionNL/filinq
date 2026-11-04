<?php

/**
 * Signing services
 *
 * Signing, its audit trail and the verification of a signed document.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningVerificationServiceInterface;

/**
 * Signing, its audit trail and the verification of a signed document.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
final class SigningServices {

	/**
	 * Constructor.
	 *
	 * @param SigningServiceInterface             $signing      Runs signing requests
	 * @param SigningAuditServiceInterface        $audit        The signing audit trail
	 * @param SigningVerificationServiceInterface $verification Verifies signed documents
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function __construct(
		public readonly SigningServiceInterface $signing,
		public readonly SigningAuditServiceInterface $audit,
		public readonly SigningVerificationServiceInterface $verification,
	) {

	}//end __construct()
}//end class
