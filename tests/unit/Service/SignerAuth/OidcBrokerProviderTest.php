<?php

/**
 * Unit tests for OidcBrokerProvider
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

use OCA\Filinq\Service\SignerAuth\OidcBrokerProvider;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The OIDC broker: acr mapping (REQ-DDSIR-002), OIDC hygiene (REQ-DDSIR-001),
 * minimisation (REQ-DDSIR-004) and custody at exchange time (REQ-DDSIR-005).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OidcBrokerProviderTest extends TestCase {
	use OidcBrokerHarness;

	/**
	 * A configured broker per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->configureBroker();

	}//end setUp()

	/**
	 * The act every test authenticates for.
	 *
	 * @param string $required The required assurance.
	 *
	 * @return SignerAuthContext
	 */
	private function act(string $required = 'substantial'): SignerAuthContext {
		return new SignerAuthContext(requestId: 'req-1', signerId: 'signer-1', requiredAssurance: $required, userId: 'alice');

	}//end act()

	/**
	 * Start, answer with the given claims, and complete.
	 *
	 * @param OidcBrokerProvider $provider The provider.
	 * @param callable(array<string, mixed>): array<string, mixed> $claims Rewrites the valid claims.
	 * @param string $acr The acr the broker reports.
	 *
	 * @return \OCA\Filinq\Service\SignerAuth\IdentityEvidence
	 */
	private function roundTrip(OidcBrokerProvider $provider, callable $claims, string $acr): \OCA\Filinq\Service\SignerAuth\IdentityEvidence {
		$challenge = $provider->initiateAuthentication($this->act());
		$this->nextClaims = $claims($this->claimsFor(nonce: $this->queryParam(url: $challenge->url, name: 'nonce'), acr: $acr));

		return $provider->completeAuthentication(
			[
				'code' => 'auth-code',
				'state' => $this->queryParam(url: $challenge->url, name: 'state'),
				'requestId' => 'req-1',
				'signerId' => 'signer-1',
				'userId' => 'alice',
			]
		);

	}//end roundTrip()

	/**
	 * The documented default mapping (design D2).
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function defaultMapping(): array {
		return [
			'DigiD Midden' => ['urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorContract', 'digid', 'substantial'],
			'DigiD Substantieel' => ['urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard', 'digid', 'substantial'],
			'DigiD Hoog' => ['urn:oasis:names:tc:SAML:2.0:ac:classes:SmartcardPKI', 'digid', 'high'],
			'eHerkenning EH3' => ['urn:etoegang:core:assurance-class:loa3', 'eherkenning', 'substantial'],
			'eHerkenning EH4' => ['urn:etoegang:core:assurance-class:loa4', 'eherkenning', 'high'],
			'iDIN' => ['urn:nl:idin:bank-verified', 'idin', 'substantial'],
		];

	}//end defaultMapping()

	/**
	 * Each documented acr maps to its means and assurance.
	 *
	 * @param string $acr The acr.
	 * @param string $means The expected means.
	 * @param string $assurance The expected assurance.
	 *
	 * @return void
	 */
	#[DataProvider('defaultMapping')]
	public function testTheDefaultMappingCoversDigidEherkenningAndIdin(string $acr, string $means, string $assurance): void {
		$evidence = $this->roundTrip(provider: $this->broker(), claims: fn (array $c): array => $c, acr: $acr);

		$this->assertSame($means, $evidence->means);
		$this->assertSame($assurance, $evidence->assurance);
		$this->assertSame('oidc-broker', $evidence->provider);

	}//end testTheDefaultMappingCoversDigidEherkenningAndIdin()

	/**
	 * An acr nobody mapped degrades to low, never up.
	 *
	 * @return void
	 */
	public function testAnUnknownAcrDegradesToLow(): void {
		$evidence = $this->roundTrip(provider: $this->broker(), claims: fn (array $c): array => $c, acr: 'urn:vendor:super-strong');

		$this->assertSame('low', $evidence->assurance);

	}//end testAnUnknownAcrDegradesToLow()

	/**
	 * A token with no acr at all is low too.
	 *
	 * @return void
	 */
	public function testAMissingAcrDegradesToLow(): void {
		$evidence = $this->roundTrip(
			provider: $this->broker(),
			claims: function (array $c): array {
				unset($c['acr']);
				return $c;
			},
			acr: ''
		);

		$this->assertSame('low', $evidence->assurance);

	}//end testAMissingAcrDegradesToLow()

	/**
	 * The admin mapping adds a broker's own acr and can lower a default, never invent a level.
	 *
	 * @return void
	 */
	public function testTheAdminMappingExtendsTheDefaults(): void {
		$this->appConfig['signer_auth_oidc_acr_mapping'] = (string)json_encode(
			[
				'urn:signicat:idin' => ['means' => 'idin', 'assurance' => 'substantial'],
				'urn:etoegang:core:assurance-class:loa4' => ['means' => 'eherkenning', 'assurance' => 'substantial'],
				'urn:broken' => ['means' => 'digid', 'assurance' => 'ultra'],
			]
		);

		$this->assertSame('substantial', $this->roundTrip(provider: $this->broker(), claims: fn (array $c): array => $c, acr: 'urn:signicat:idin')->assurance);
		$this->assertSame('substantial', $this->roundTrip(provider: $this->broker(), claims: fn (array $c): array => $c, acr: 'urn:etoegang:core:assurance-class:loa4')->assurance);
		$this->assertSame('low', $this->roundTrip(provider: $this->broker(), claims: fn (array $c): array => $c, acr: 'urn:broken')->assurance);

	}//end testTheAdminMappingExtendsTheDefaults()

	/**
	 * The authorize URL is a code flow with state, nonce and only acr values that meet the requirement.
	 *
	 * @return void
	 */
	public function testTheAuthorizeUrlIsACodeFlowAskingOnlyForSufficientAcrValues(): void {
		$challenge = $this->broker()->initiateAuthentication($this->act(required: 'high'));

		$this->assertSame('redirect', $challenge->type);
		$this->assertStringStartsWith('https://broker.example.nl/authorize?', $challenge->url);
		$this->assertSame('code', $this->queryParam(url: $challenge->url, name: 'response_type'));
		$this->assertSame('filinq-client', $this->queryParam(url: $challenge->url, name: 'client_id'));
		$this->assertStringContainsString('openid', $this->queryParam(url: $challenge->url, name: 'scope'));
		$this->assertNotSame('', $this->queryParam(url: $challenge->url, name: 'state'));
		$this->assertNotSame('', $this->queryParam(url: $challenge->url, name: 'nonce'));
		$acrValues = explode(' ', $this->queryParam(url: $challenge->url, name: 'acr_values'));
		$this->assertContains('urn:oasis:names:tc:SAML:2.0:ac:classes:SmartcardPKI', $acrValues);
		$this->assertContains('urn:etoegang:core:assurance-class:loa4', $acrValues);
		$this->assertNotContains('urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard', $acrValues);
		$this->assertStringNotContainsString($this->brokerSecret, $challenge->url);

	}//end testTheAuthorizeUrlIsACodeFlowAskingOnlyForSufficientAcrValues()

	/**
	 * A state answers once: replaying the callback fails.
	 *
	 * @return void
	 */
	public function testAStateIsSingleUse(): void {
		$provider = $this->broker();
		$challenge = $provider->initiateAuthentication($this->act());
		$this->nextClaims = $this->claimsFor(nonce: $this->queryParam(url: $challenge->url, name: 'nonce'), acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard');
		$callback = ['code' => 'c', 'state' => $this->queryParam(url: $challenge->url, name: 'state'), 'requestId' => 'req-1', 'signerId' => 'signer-1', 'userId' => 'alice'];
		$provider->completeAuthentication($callback);

		$this->expectException(RuntimeException::class);

		$provider->completeAuthentication($callback);

	}//end testAStateIsSingleUse()

	/**
	 * A callback in another user's session fails, even with a valid state.
	 *
	 * @return void
	 */
	public function testACallbackForAnotherUserFails(): void {
		$provider = $this->broker();
		$challenge = $provider->initiateAuthentication($this->act());

		$this->expectException(RuntimeException::class);

		$provider->completeAuthentication(
			['code' => 'c', 'state' => $this->queryParam(url: $challenge->url, name: 'state'), 'requestId' => 'req-1', 'signerId' => 'signer-1', 'userId' => 'mallory']
		);

	}//end testACallbackForAnotherUserFails()

	/**
	 * Each spoiled claim, and what it must do.
	 *
	 * @return array<string, array{0: callable(array<string, mixed>): array<string, mixed>}>
	 */
	public static function spoiledClaims(): array {
		return [
			'wrong nonce' => [static fn (array $c): array => ['nonce' => 'replayed'] + $c],
			'wrong issuer' => [static fn (array $c): array => ['iss' => 'https://evil.example'] + $c],
			'wrong audience' => [static fn (array $c): array => ['aud' => 'another-client'] + $c],
			'audience list without us' => [static fn (array $c): array => ['aud' => ['a', 'b']] + $c],
			'expired' => [static fn (array $c): array => ['exp' => time() - 120] + $c],
			'issued in the future' => [static fn (array $c): array => ['iat' => time() + 3600] + $c],
			'no subject' => [static fn (array $c): array => ['sub' => ''] + $c],
		];

	}//end spoiledClaims()

	/**
	 * A token that fails any OIDC check is refused.
	 *
	 * @param callable(array<string, mixed>): array<string, mixed> $spoil Rewrites the claims.
	 *
	 * @return void
	 */
	#[DataProvider('spoiledClaims')]
	public function testATokenFailingAnOidcCheckIsRefused(callable $spoil): void {
		$this->expectException(RuntimeException::class);

		$this->roundTrip(provider: $this->broker(), claims: $spoil, acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard');

	}//end testATokenFailingAnOidcCheckIsRefused()

	/**
	 * The secret is resolved from the reference at exchange time, and goes only to the token endpoint.
	 *
	 * @return void
	 */
	public function testTheSecretIsResolvedFromTheReferenceAtExchangeTime(): void {
		$provider = $this->broker();
		$challenge = $provider->initiateAuthentication($this->act());
		$this->assertSame([], $this->resolvedRefs, 'Starting an authentication needs no secret');

		$this->nextClaims = $this->claimsFor(nonce: $this->queryParam(url: $challenge->url, name: 'nonce'), acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard');
		$provider->completeAuthentication(
			['code' => 'the-code', 'state' => $this->queryParam(url: $challenge->url, name: 'state'), 'requestId' => 'req-1', 'signerId' => 'signer-1', 'userId' => 'alice']
		);

		$this->assertSame([$this->credentialRef . '@filinq'], $this->resolvedRefs);
		$this->assertCount(1, $this->tokenRequests);
		$this->assertSame('https://broker.example.nl/token', $this->tokenRequests[0]['url']);
		$form = $this->tokenRequests[0]['options']['form_params'] ?? [];
		$this->assertSame('authorization_code', $form['grant_type'] ?? null);
		$this->assertSame('the-code', $form['code'] ?? null);
		$this->assertSame($this->brokerSecret, $form['client_secret'] ?? null);

	}//end testTheSecretIsResolvedFromTheReferenceAtExchangeTime()

	/**
	 * A reference the broker will not inject stops the exchange.
	 *
	 * @return void
	 */
	public function testAProxyOnlyCredentialStopsTheExchange(): void {
		$broker = new class {
			/**
			 * A proxy credential: nothing to inject.
			 *
			 * @param string $credentialId The id.
			 * @param string $appId The app.
			 *
			 * @return string|null
			 */
			public function resolveInjectable(string $credentialId, string $appId): ?string {
				return null;
			}
		};

		$this->expectException(RuntimeException::class);

		$this->roundTrip(provider: $this->broker(broker: $broker), claims: fn (array $c): array => $c, acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard');

	}//end testAProxyOnlyCredentialStopsTheExchange()

	/**
	 * The evidence keeps a hash of the token and a salted pseudonym, never the token or a BSN.
	 *
	 * @return void
	 */
	public function testTheEvidenceKeepsNoTokenAndNoBsn(): void {
		$evidence = $this->roundTrip(
			provider: $this->broker(),
			claims: fn (array $c): array => ['sub' => 's00000000:123456782'] + $c,
			acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard'
		);

		$stored = (string)json_encode($evidence->toArray()) . (string)json_encode($this->sessionData) . implode("\n", $this->logLines);
		$this->assertSame(hash('sha256', $this->lastIdToken), $evidence->evidenceHash);
		$this->assertStringNotContainsString('123456782', $stored);
		$this->assertStringNotContainsString($this->lastIdToken, $stored);
		$this->assertStringNotContainsString('at-not-kept', $stored);
		$this->assertStringNotContainsString($this->brokerSecret, $stored);

	}//end testTheEvidenceKeepsNoTokenAndNoBsn()

	/**
	 * An unconfigured broker cannot start, and an http endpoint counts as unconfigured.
	 *
	 * @return void
	 */
	public function testAnUnconfiguredOrPlainHttpBrokerCannotStart(): void {
		$this->appConfig['signer_auth_oidc_token_endpoint'] = 'http://broker.example.nl/token';

		$this->expectException(RuntimeException::class);

		$this->broker()->initiateAuthentication($this->act());

	}//end testAnUnconfiguredOrPlainHttpBrokerCannotStart()

	/**
	 * The broker declares its means and never more than it can map.
	 *
	 * @return void
	 */
	public function testTheBrokerDeclaresItsMeansAndAssurance(): void {
		$provider = $this->broker();

		$this->assertSame('oidc-broker', $provider->getIdentifier());
		$this->assertSame(['low', 'substantial', 'high'], $provider->getSupportedAssurance());
		foreach (['digid', 'eherkenning', 'idin'] as $means) {
			$this->assertContains($means, $provider->getSupportedMeans());
		}

	}//end testTheBrokerDeclaresItsMeansAndAssurance()
}//end class
