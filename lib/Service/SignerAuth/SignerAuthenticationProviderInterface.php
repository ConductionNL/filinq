<?php

/**
 * Signer Authentication Provider Interface
 *
 * The seam any identity source plugs into: a Nextcloud session, an OIDC
 * broker fronting DigiD, eHerkenning and iDIN, or a future EUDI wallet
 * verifier.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
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

namespace OCA\Filinq\Service\SignerAuth;

use RuntimeException;

/**
 * Asserts who a signer is, and at what assurance.
 *
 * Deliberately separate from `SigningProviderInterface`: a signing provider
 * produces the artifact, a signer-authentication provider establishes the
 * actor. A native SES artifact signed by someone who authenticated with
 * DigiD at substantial is a legitimate pair, so the two seams compose rather
 * than bundle (design D1).
 *
 * A new provider (for example `eudi-wallet`) implements this interface,
 * passes `tests/unit/Service/SignerAuth/SignerAuthProviderContractTestCase.php`
 * and is registered in `SignerAuthProviderFactory`; nothing else changes.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
interface SignerAuthenticationProviderInterface {

	/**
	 * The provider identifier, for example `nextcloud-session` or `oidc-broker`.
	 *
	 * @return string The identifier.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getIdentifier(): string;

	/**
	 * The identity means this provider can establish.
	 *
	 * @return list<string> For example `['digid', 'eherkenning', 'idin']`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getSupportedMeans(): array;

	/**
	 * The assurance levels this provider can assert.
	 *
	 * @return list<string> A non-empty subset of `low`, `substantial`, `high`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getSupportedAssurance(): array;

	/**
	 * Start an authentication for one signing act.
	 *
	 * @param SignerAuthContext $context The request, signer and required assurance.
	 *
	 * @return AuthChallenge What the signer must do next.
	 *
	 * @throws RuntimeException When the provider cannot start (for example, not configured).
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function initiateAuthentication(SignerAuthContext $context): AuthChallenge;

	/**
	 * Finish an authentication and derive the identity evidence.
	 *
	 * Fail-closed: anything invalid throws, it never yields weaker evidence
	 * silently.
	 *
	 * @param array<string, mixed> $callbackData The callback input (for OIDC: `code`, `state`,
	 *                                           and the `requestId` and `signerId` it must be bound to).
	 *
	 * @return IdentityEvidence The evidence.
	 *
	 * @throws RuntimeException When the input is invalid, expired, unbound or unverifiable.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function completeAuthentication(array $callbackData): IdentityEvidence;
}//end interface
