<?php

/**
 * Signer-authentication provider contract
 *
 * The acceptance bar every signer-authentication provider passes, shipped or
 * plugged in later: the two built-in providers run it, and so does the
 * `eudi-wallet` fixture that stands in for a future wallet verifier.
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

use OCA\Filinq\Service\SignerAuth\AssuranceLevel;
use OCA\Filinq\Service\SignerAuth\AuthChallenge;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use OCA\Filinq\Service\SignerAuth\SignerAuthenticationProviderInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The contract (REQ-DDSIR-001, REQ-DDSIR-006).
 *
 * A concrete test supplies the provider, a way to turn a started
 * authentication into valid callback data, and a way to spoil that data so
 * it belongs to another signing act.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class SignerAuthProviderContractTestCase extends TestCase {

	/**
	 * The provider under test.
	 *
	 * @return SignerAuthenticationProviderInterface
	 */
	abstract protected function createProvider(): SignerAuthenticationProviderInterface;

	/**
	 * Valid callback data for an authentication the provider just started.
	 *
	 * @param SignerAuthenticationProviderInterface $provider The provider.
	 * @param SignerAuthContext $context The signing act.
	 * @param AuthChallenge $challenge What initiateAuthentication() returned.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function validCallback(
		SignerAuthenticationProviderInterface $provider,
		SignerAuthContext $context,
		AuthChallenge $challenge,
	): array;

	/**
	 * The same callback, spoiled so it belongs to another signer or session.
	 *
	 * @param array<string, mixed> $valid The valid callback data.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function foreignCallback(array $valid): array;

	/**
	 * The signing act every case authenticates for.
	 *
	 * @return SignerAuthContext
	 */
	protected function context(): SignerAuthContext {
		return new SignerAuthContext(
			requestId: 'req-contract-1',
			signerId: 'signer-contract-1',
			requiredAssurance: 'low',
			userId: 'alice'
		);

	}//end context()

	/**
	 * The identifier is a lowercase slug.
	 *
	 * @return void
	 */
	public function testTheIdentifierIsALowercaseSlug(): void {
		$this->assertMatchesRegularExpression('/^[a-z][a-z0-9-]*$/', $this->createProvider()->getIdentifier());

	}//end testTheIdentifierIsALowercaseSlug()

	/**
	 * The provider names at least one means, as strings.
	 *
	 * @return void
	 */
	public function testTheProviderNamesItsMeans(): void {
		$means = $this->createProvider()->getSupportedMeans();

		$this->assertNotEmpty($means);
		foreach ($means as $item) {
			$this->assertIsString($item);
			$this->assertNotSame('', $item);
		}

	}//end testTheProviderNamesItsMeans()

	/**
	 * The assurance levels are a non-empty subset of the eIDAS scale.
	 *
	 * @return void
	 */
	public function testTheAssuranceLevelsStayOnTheEidasScale(): void {
		$levels = $this->createProvider()->getSupportedAssurance();

		$this->assertNotEmpty($levels);
		$this->assertSame([], array_values(array_diff($levels, AssuranceLevel::LEVELS)));

	}//end testTheAssuranceLevelsStayOnTheEidasScale()

	/**
	 * Initiation answers a redirect over https, or nothing to do.
	 *
	 * @return void
	 */
	public function testInitiationAnswersAKnownChallenge(): void {
		$provider = $this->createProvider();
		$challenge = $provider->initiateAuthentication($this->context());

		$this->assertSame($provider->getIdentifier(), $challenge->provider);
		$this->assertContains($challenge->type, [AuthChallenge::TYPE_REDIRECT, AuthChallenge::TYPE_NONE]);
		if ($challenge->type === AuthChallenge::TYPE_REDIRECT) {
			$this->assertStringStartsWith('https://', $challenge->url);
		}

	}//end testInitiationAnswersAKnownChallenge()

	/**
	 * A completed authentication yields minimised evidence within the declared bounds.
	 *
	 * @return void
	 */
	public function testCompletionYieldsMinimisedEvidenceWithinTheDeclaredBounds(): void {
		$provider = $this->createProvider();
		$context = $this->context();
		$challenge = $provider->initiateAuthentication($context);

		$evidence = $provider->completeAuthentication($this->validCallback($provider, $context, $challenge));

		$this->assertSame($provider->getIdentifier(), $evidence->provider);
		$this->assertContains($evidence->assurance, $provider->getSupportedAssurance());
		$this->assertContains($evidence->means, $provider->getSupportedMeans());
		$this->assertNotSame('', $evidence->subjectPseudonym);
		$this->assertDoesNotMatchRegularExpression('/(?<!\d)\d{9}(?!\d)/', $evidence->subjectPseudonym, 'A pseudonym never looks like a BSN');
		$this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $evidence->evidenceHash);

	}//end testCompletionYieldsMinimisedEvidenceWithinTheDeclaredBounds()

	/**
	 * Empty callback data is refused, never turned into weak evidence.
	 *
	 * @return void
	 */
	public function testEmptyCallbackDataFailsClosed(): void {
		$this->expectException(RuntimeException::class);

		$this->createProvider()->completeAuthentication([]);

	}//end testEmptyCallbackDataFailsClosed()

	/**
	 * Garbage callback data is refused.
	 *
	 * @return void
	 */
	public function testGarbageCallbackDataFailsClosed(): void {
		$this->expectException(RuntimeException::class);

		$this->createProvider()->completeAuthentication(
			['code' => 'x', 'state' => 'nope', 'requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'userId' => '']
		);

	}//end testGarbageCallbackDataFailsClosed()

	/**
	 * A callback that belongs to another signing act or person is refused.
	 *
	 * @return void
	 */
	public function testACallbackForAnotherActFailsClosed(): void {
		$provider = $this->createProvider();
		$context = $this->context();
		$challenge = $provider->initiateAuthentication($context);
		$foreign = $this->foreignCallback($this->validCallback($provider, $context, $challenge));

		$this->expectException(RuntimeException::class);

		$provider->completeAuthentication($foreign);

	}//end testACallbackForAnotherActFailsClosed()
}//end class
