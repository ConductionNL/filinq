<?php

/**
 * Assurance gate test harness
 *
 * Builds the real `SigningAssuranceGate` over in-memory doubles, for the
 * gate's own test and for every SigningService test that constructs the
 * service by hand.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

require_once __DIR__ . '/OidcBrokerHarness.php';

use OCA\Filinq\Service\SignerAuth\IdentityEvidenceStore;
use OCA\Filinq\Service\SignerAuth\NextcloudSessionProvider;
use OCA\Filinq\Service\SignerAuth\SignerAuthProviderFactory;
use OCA\Filinq\Service\SignerAuth\SigningAssuranceGate;
use OCP\IUserSession;

/**
 * The gate, its store and its factory over the OIDC harness doubles.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait AssuranceGateHarness {
	use OidcBrokerHarness;

	/**
	 * The evidence store over the harness session.
	 *
	 * @return IdentityEvidenceStore
	 */
	protected function evidenceStore(): IdentityEvidenceStore {
		return new IdentityEvidenceStore(session: $this->session());

	}//end evidenceStore()

	/**
	 * The provider factory, with the session provider over a given user session.
	 *
	 * @param IUserSession $userSession The user session.
	 *
	 * @return SignerAuthProviderFactory
	 */
	protected function providerFactory(IUserSession $userSession): SignerAuthProviderFactory {
		return new SignerAuthProviderFactory(
			settings: $this->settings(),
			sessionProvider: new NextcloudSessionProvider(userSession: $userSession, pseudonymiser: $this->pseudonymiser()),
			brokerProvider: $this->broker()
		);

	}//end providerFactory()

	/**
	 * The real gate.
	 *
	 * @param IUserSession $userSession The user session the session provider reads.
	 *
	 * @return SigningAssuranceGate
	 */
	protected function assuranceGate(IUserSession $userSession): SigningAssuranceGate {
		return new SigningAssuranceGate(
			providers: $this->providerFactory(userSession: $userSession),
			settings: $this->settings(),
			store: $this->evidenceStore(),
			pseudonymiser: $this->pseudonymiser()
		);

	}//end assuranceGate()
}//end trait
