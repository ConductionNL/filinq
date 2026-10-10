<?php

/**
 * A future EUDI wallet provider through the provider contract
 *
 * No wallet provider ships (a stub would be dead code). This fixture plays
 * the part a real `eudi-wallet` plugin will: it declares means `eudi-wallet`
 * at assurance `high`, and it passes the same contract as the shipped
 * providers, registered through the factory without a core change.
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

use DateTimeImmutable;
use OCA\Filinq\Service\SignerAuth\AuthChallenge;
use OCA\Filinq\Service\SignerAuth\IdentityEvidence;
use OCA\Filinq\Service\SignerAuth\NextcloudSessionProvider;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use OCA\Filinq\Service\SignerAuth\SignerAuthenticationProviderInterface;
use OCA\Filinq\Service\SignerAuth\SignerAuthProviderFactory;
use OCP\IUserSession;
use RuntimeException;

/**
 * A wallet fixture: a presentation request with a nonce, answered once.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class EudiWalletFixtureProvider implements SignerAuthenticationProviderInterface {

	/**
	 * Open presentation requests: nonce => [requestId, signerId].
	 *
	 * @var array<string, array{0: string, 1: string}>
	 */
	private array $open = [];

	/**
	 * The identifier.
	 *
	 * @return string
	 */
	public function getIdentifier(): string {
		return 'eudi-wallet';

	}//end getIdentifier()

	/**
	 * The means.
	 *
	 * @return list<string>
	 */
	public function getSupportedMeans(): array {
		return ['eudi-wallet'];

	}//end getSupportedMeans()

	/**
	 * The assurance.
	 *
	 * @return list<string>
	 */
	public function getSupportedAssurance(): array {
		return ['high'];

	}//end getSupportedAssurance()

	/**
	 * Open a presentation request.
	 *
	 * @param SignerAuthContext $context The signing act.
	 *
	 * @return AuthChallenge
	 */
	public function initiateAuthentication(SignerAuthContext $context): AuthChallenge {
		$nonce = 'n' . (count($this->open) + 1);
		$this->open[$nonce] = [$context->requestId, $context->signerId];

		return new AuthChallenge(
			provider: 'eudi-wallet',
			type: AuthChallenge::TYPE_REDIRECT,
			url: 'https://wallet.example.eu/present?nonce=' . $nonce
		);

	}//end initiateAuthentication()

	/**
	 * Accept a presentation once, for the act it was opened for.
	 *
	 * @param array<string, mixed> $callbackData `nonce`, `presentation`, `requestId`, `signerId`.
	 *
	 * @return IdentityEvidence
	 */
	public function completeAuthentication(array $callbackData): IdentityEvidence {
		$nonce = (string)($callbackData['nonce'] ?? '');
		$bound = $this->open[$nonce] ?? null;
		$act = [(string)($callbackData['requestId'] ?? ''), (string)($callbackData['signerId'] ?? '')];
		if ($bound === null || $bound !== $act || (string)($callbackData['presentation'] ?? '') === '') {
			throw new RuntimeException('Presentation refused');
		}

		unset($this->open[$nonce]);

		return new IdentityEvidence(
			provider: 'eudi-wallet',
			means: 'eudi-wallet',
			assurance: 'high',
			subjectPseudonym: 'wallet-pairwise-subject',
			authenticatedAt: new DateTimeImmutable(),
			evidenceHash: hash('sha256', (string)$callbackData['presentation'])
		);

	}//end completeAuthentication()
}//end class

/**
 * Runs the provider contract against the wallet fixture (REQ-DDSIR-006).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EudiWalletFixtureProviderContractTest extends SignerAuthProviderContractTestCase {
	use OidcBrokerHarness;

	/**
	 * The fixture, one per test.
	 *
	 * @var EudiWalletFixtureProvider|null
	 */
	private ?EudiWalletFixtureProvider $wallet = null;

	/**
	 * The provider under test.
	 *
	 * @return SignerAuthenticationProviderInterface
	 */
	protected function createProvider(): SignerAuthenticationProviderInterface {
		$this->wallet ??= new EudiWalletFixtureProvider();

		return $this->wallet;

	}//end createProvider()

	/**
	 * Valid callback data: the wallet answers the nonce it was shown.
	 *
	 * @param SignerAuthenticationProviderInterface $provider The provider.
	 * @param SignerAuthContext $context The signing act.
	 * @param AuthChallenge $challenge The presentation request.
	 *
	 * @return array<string, mixed>
	 */
	protected function validCallback(
		SignerAuthenticationProviderInterface $provider,
		SignerAuthContext $context,
		AuthChallenge $challenge,
	): array {
		return [
			'nonce' => $this->queryParam(url: $challenge->url, name: 'nonce'),
			'presentation' => 'vp-token',
			'requestId' => $context->requestId,
			'signerId' => $context->signerId,
		];

	}//end validCallback()

	/**
	 * The same presentation, claimed for another request.
	 *
	 * @param array<string, mixed> $valid The valid callback data.
	 *
	 * @return array<string, mixed>
	 */
	protected function foreignCallback(array $valid): array {
		$valid['requestId'] = 'req-someone-else';

		return $valid;

	}//end foreignCallback()

	/**
	 * The wallet registers through the factory with no core change.
	 *
	 * @return void
	 */
	public function testTheWalletRegistersThroughTheFactoryWithoutACoreChange(): void {
		$this->appConfig = ['signer_auth_provider' => 'eudi-wallet'];
		$factory = new SignerAuthProviderFactory(
			settings: $this->settings(),
			sessionProvider: new NextcloudSessionProvider(
				userSession: $this->createMock(IUserSession::class),
				pseudonymiser: $this->pseudonymiser()
			),
			brokerProvider: $this->broker(),
			additional: [$this->createProvider()]
		);

		$this->assertSame('eudi-wallet', $factory->configured()->getIdentifier());
		$this->assertSame(['nextcloud-session', 'oidc-broker', 'eudi-wallet'], $factory->identifiers());

	}//end testTheWalletRegistersThroughTheFactoryWithoutACoreChange()
}//end class
