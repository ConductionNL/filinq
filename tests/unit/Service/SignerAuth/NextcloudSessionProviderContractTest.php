<?php

/**
 * The `nextcloud-session` provider through the provider contract
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
use OCA\Filinq\Service\SignerAuth\NextcloudSessionProvider;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use OCA\Filinq\Service\SignerAuth\SignerAuthenticationProviderInterface;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Runs the provider contract against `nextcloud-session` (REQ-DDSIR-001).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NextcloudSessionProviderContractTest extends SignerAuthProviderContractTestCase {
	use OidcBrokerHarness;

	/**
	 * The provider under test, for a session belonging to alice.
	 *
	 * @return SignerAuthenticationProviderInterface
	 */
	protected function createProvider(): SignerAuthenticationProviderInterface {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new NextcloudSessionProvider(userSession: $session, pseudonymiser: $this->pseudonymiser());

	}//end createProvider()

	/**
	 * Valid callback data: the act, claimed by the session user.
	 *
	 * @param SignerAuthenticationProviderInterface $provider The provider.
	 * @param SignerAuthContext $context The signing act.
	 * @param AuthChallenge $challenge The challenge (nothing to do).
	 *
	 * @return array<string, mixed>
	 */
	protected function validCallback(
		SignerAuthenticationProviderInterface $provider,
		SignerAuthContext $context,
		AuthChallenge $challenge,
	): array {
		return ['requestId' => $context->requestId, 'signerId' => $context->signerId, 'userId' => 'alice'];

	}//end validCallback()

	/**
	 * The same act, claimed for a user who is not in this session.
	 *
	 * @param array<string, mixed> $valid The valid callback data.
	 *
	 * @return array<string, mixed>
	 */
	protected function foreignCallback(array $valid): array {
		$valid['userId'] = 'mallory';

		return $valid;

	}//end foreignCallback()

	/**
	 * A Nextcloud session never asserts more than `low`.
	 *
	 * @return void
	 */
	public function testASessionIsLowAssuranceOnly(): void {
		$provider = $this->createProvider();

		$this->assertSame(['low'], $provider->getSupportedAssurance());
		$this->assertSame('nextcloud-session', $provider->getIdentifier());
		$evidence = $provider->completeAuthentication(['requestId' => 'r', 'signerId' => 's', 'userId' => 'alice']);
		$this->assertSame('low', $evidence->assurance);
		$this->assertStringNotContainsString('alice', $evidence->subjectPseudonym);

	}//end testASessionIsLowAssuranceOnly()
}//end class
