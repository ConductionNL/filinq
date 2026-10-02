<?php

/**
 * Credential custody for the identity broker (ADR-064)
 *
 * The broker's client secret lives in OpenRegister's credential broker.
 * Filinq holds a `credentialRef` and resolves it at token-exchange time.
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

use InvalidArgumentException;
use OCA\Filinq\Service\SignerAuth\BrokerCredentialResolver;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * REQ-DDSIR-005: secret is only a reference at rest.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CredentialCustodyTest extends TestCase {
	use OidcBrokerHarness;

	/**
	 * Property names that would mean a secret sits on a register object.
	 *
	 * @var list<string>
	 */
	private const SECRET_NAMES = ['secret', 'clientsecret', 'client_secret', 'password', 'apikey', 'token', 'jwt', 'idtoken', 'id_token'];

	/**
	 * A resolver whose container has no OpenRegister broker.
	 *
	 * @return BrokerCredentialResolver
	 */
	private function resolverWithoutBroker(): BrokerCredentialResolver {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('has')->willReturn(false);

		return new BrokerCredentialResolver(container: $container, logger: $this->logger());

	}//end resolverWithoutBroker()

	/**
	 * The resolver asks OpenRegister's broker for the reference, as filinq.
	 *
	 * @return void
	 */
	public function testTheResolverAsksTheBrokerForTheReferenceAsFilinq(): void {
		$secret = $this->resolver()->resolve(credentialRef: $this->credentialRef);

		$this->assertSame($this->brokerSecret, $secret);
		$this->assertSame([$this->credentialRef . '@filinq'], $this->resolvedRefs);

	}//end testTheResolverAsksTheBrokerForTheReferenceAsFilinq()

	/**
	 * No reference, no broker, or a broker that refuses: the exchange stops, and nothing is logged in clear.
	 *
	 * @return void
	 */
	public function testAMissingReferenceOrBrokerStopsTheExchange(): void {
		foreach (
			[
				fn () => $this->resolver()->resolve(credentialRef: ''),
				fn () => $this->resolverWithoutBroker()->resolve(credentialRef: $this->credentialRef),
				fn () => $this->resolver(
					broker: new class {
						/**
						 * A broker that refuses.
						 *
						 * @param string $credentialId The id.
						 * @param string $appId The app.
						 *
						 * @return string|null
						 */
						public function resolveInjectable(string $credentialId, string $appId): ?string {
							throw new \RuntimeException('Guard 2: app filinq not allowed');
						}
					}
				)->resolve(credentialRef: $this->credentialRef),
			] as $attempt
		) {
			try {
				$attempt();
				$this->fail('The exchange should have stopped');
			} catch (RuntimeException $e) {
				$this->assertStringNotContainsString($this->brokerSecret, $e->getMessage());
			}
		}//end foreach

		$this->assertStringNotContainsString($this->brokerSecret, implode("\n", $this->logLines));

	}//end testAMissingReferenceOrBrokerStopsTheExchange()

	/**
	 * The settings accept a credential reference and refuse anything that is not one.
	 *
	 * @return void
	 */
	public function testTheSettingsHoldOnlyAReference(): void {
		$settings = $this->settings();
		$settings->update(data: ['credentialRef' => $this->credentialRef]);
		$this->assertSame($this->credentialRef, $this->appConfig['signer_auth_oidc_credential_ref']);

		$this->expectException(InvalidArgumentException::class);

		try {
			$settings->update(data: ['credentialRef' => 'sk_live_this-is-a-pasted-client-secret']);
		} finally {
			$this->assertSame($this->credentialRef, $this->appConfig['signer_auth_oidc_credential_ref']);
		}

	}//end testTheSettingsHoldOnlyAReference()

	/**
	 * After a full exchange neither the app config nor the settings response holds the secret.
	 *
	 * @return void
	 */
	public function testNoSecretAtRestAfterAnExchange(): void {
		$this->configureBroker();
		$provider = $this->broker();
		$challenge = $provider->initiateAuthentication(new SignerAuthContext(requestId: 'r', signerId: 's', requiredAssurance: 'low', userId: 'alice'));
		$this->nextClaims = $this->claimsFor(nonce: $this->queryParam(url: $challenge->url, name: 'nonce'), acr: 'urn:etoegang:core:assurance-class:loa3');
		$provider->completeAuthentication(['code' => 'c', 'state' => $this->queryParam(url: $challenge->url, name: 'state'), 'requestId' => 'r', 'signerId' => 's', 'userId' => 'alice']);

		$atRest = (string)json_encode($this->appConfig) . (string)json_encode($this->settings()->toArray()) . (string)json_encode($this->sessionData);
		$this->assertStringNotContainsString($this->brokerSecret, $atRest);
		$this->assertStringContainsString($this->credentialRef, (string)json_encode($this->settings()->toArray()));

	}//end testNoSecretAtRestAfterAnExchange()

	/**
	 * No signing schema declares a property that would hold a secret or a raw token.
	 *
	 * @return void
	 */
	public function testNoSigningSchemaDeclaresASecretProperty(): void {
		$register = json_decode((string)file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);

		foreach (['signingRequest', 'signerRecord'] as $schema) {
			$names = $this->propertyNames(properties: $register['components']['schemas'][$schema]['properties']);
			foreach ($names as $name) {
				$this->assertNotContains(strtolower($name), self::SECRET_NAMES, $schema . '.' . $name . ' would hold a secret');
			}
		}

	}//end testNoSigningSchemaDeclaresASecretProperty()

	/**
	 * Every property name, nested ones included.
	 *
	 * @param array<string, mixed> $properties The schema properties.
	 *
	 * @return list<string>
	 */
	private function propertyNames(array $properties): array {
		$names = [];
		foreach ($properties as $name => $definition) {
			$names[] = (string)$name;
			if (is_array($definition) === true && isset($definition['properties']) === true) {
				$names = array_merge($names, $this->propertyNames(properties: $definition['properties']));
			}
		}

		return $names;

	}//end propertyNames()
}//end class
