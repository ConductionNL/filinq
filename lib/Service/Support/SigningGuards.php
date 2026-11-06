<?php

/**
 * Signing guards
 *
 * The checks a signing request and every signing act pass: the request
 * validator, the guardian consent rule, the signer identity gate and the
 * signing mandate.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
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

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\SignerAuth\SigningAssuranceGate;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\SigningMandateService;
use OCA\Filinq\Service\SigningRequestValidator;

/**
 * The checks a signing request and every signing act pass.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
final class SigningGuards {

	/**
	 * Constructor.
	 *
	 * The consent guard and the identity gate are required, not nullable: an
	 * unwired safety guard must fail construction, not pass silently. The
	 * mandate service is additive: null signs as without a mandate, and the
	 * DI container resolves the real one.
	 *
	 * @param SigningRequestValidator    $validator      Validates request data, signers and placements
	 * @param GuardianConsentGuard       $consentGuard   The guardian rule for signers under the age of consent (REQ-DDSIR-008 to 010)
	 * @param SigningAssuranceGate       $assuranceGate  The identity gate of every signing act (REQ-DDSIR-003)
	 * @param SigningMandateService|null $mandateService The consuming app's per-type mandate for a direct signing attempt (REQ-SFC-04)
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function __construct(
		public readonly SigningRequestValidator $validator,
		public readonly GuardianConsentGuard $consentGuard,
		public readonly SigningAssuranceGate $assuranceGate,
		public readonly ?SigningMandateService $mandateService = null,
	) {

	}//end __construct()
}//end class
