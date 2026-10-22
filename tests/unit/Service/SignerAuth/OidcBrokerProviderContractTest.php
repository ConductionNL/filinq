<?php

/**
 * The `oidc-broker` provider through the provider contract
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

require_once __DIR__ . '/SignerAuthProviderContractTestCase.php';
require_once __DIR__ . '/OidcBrokerHarness.php';

use OCA\Filinq\Service\SignerAuth\AuthChallenge;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use OCA\Filinq\Service\SignerAuth\SignerAuthenticationProviderInterface;

/**
 * Runs the provider contract against `oidc-broker` (REQ-DDSIR-001).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OidcBrokerProviderContractTest extends SignerAuthProviderContractTestCase {
	use OidcBrokerHarness;

	/**
	 * The provider, built once per test so the session survives initiate and complete.
	 *
	 * @var SignerAuthenticationProviderInterface|null
	 */
	private ?SignerAuthenticationProviderInterface $provider = null;

	/**
	 * A configured broker, fresh per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->configureBroker();
		$this->provider = null;

	}//end setUp()

	/**
	 * The provider under test.
	 *
	 * @return SignerAuthenticationProviderInterface
	 */
	protected function createProvider(): SignerAuthenticationProviderInterface {
		$this->provider ??= $this->broker();

		return $this->provider;

	}//end createProvider()

	/**
	 * Valid callback data: the broker answers with the nonce the URL carried.
	 *
	 * @param SignerAuthenticationProviderInterface $provider The provider.
	 * @param SignerAuthContext $context The signing act.
	 * @param AuthChallenge $challenge The redirect challenge.
	 *
	 * @return array<string, mixed>
	 */
	protected function validCallback(
		SignerAuthenticationProviderInterface $provider,
		SignerAuthContext $context,
		AuthChallenge $challenge,
	): array {
		$this->nextClaims = $this->claimsFor(
			nonce: $this->queryParam(url: $challenge->url, name: 'nonce'),
			acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard'
		);

		return [
			'code' => 'auth-code-1',
			'state' => $this->queryParam(url: $challenge->url, name: 'state'),
			'requestId' => $context->requestId,
			'signerId' => $context->signerId,
			'userId' => $context->userId,
		];

	}//end validCallback()

	/**
	 * The same callback, claimed for another signer.
	 *
	 * @param array<string, mixed> $valid The valid callback data.
	 *
	 * @return array<string, mixed>
	 */
	protected function foreignCallback(array $valid): array {
		$valid['signerId'] = 'signer-someone-else';

		return $valid;

	}//end foreignCallback()
}//end class
