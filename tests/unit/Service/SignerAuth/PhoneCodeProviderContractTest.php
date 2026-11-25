<?php

/**
 * The phone code provider passes the signer-authentication contract, and a
 * code is right, wrong, expired or used once
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

require_once __DIR__ . '/SignerAuthProviderContractTestCase.php';
require_once __DIR__ . '/OidcBrokerHarness.php';

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignerAuth\AuthChallenge;
use OCA\Filinq\Service\SignerAuth\NextcloudSessionProvider;
use OCA\Filinq\Service\SignerAuth\OidcBrokerProvider;
use OCA\Filinq\Service\SignerAuth\PhoneCodeProvider;
use OCA\Filinq\Service\SignerAuth\PhoneCodeSenderInterface;
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use OCA\Filinq\Service\SignerAuth\SignerAuthenticationProviderInterface;
use OCA\Filinq\Service\SignerAuth\SignerAuthProviderFactory;
use OCA\Filinq\Service\SignerAuth\SignerAuthSettings;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use RuntimeException;

/**
 * Phone code provider.
 *
 * @covers \OCA\Filinq\Service\SignerAuth\PhoneCodeProvider
 * @uses   \OCA\Filinq\Service\SignerAuth\AuthChallenge
 * @uses   \OCA\Filinq\Service\SignerAuth\IdentityEvidence
 * @uses   \OCA\Filinq\Service\SignerAuth\SignerAuthContext
 * @uses   \OCA\Filinq\Service\SignerAuth\SubjectPseudonymiser
 * @uses   \OCA\Filinq\Service\SignerAuth\SignerAuthProviderFactory
 */
class PhoneCodeProviderContractTest extends SignerAuthProviderContractTestCase {
	use OidcBrokerHarness;

	/**
	 * The texts sent: [number, message].
	 *
	 * @var list<array{0: string, 1: string}>
	 */
	private array $texts = [];

	/**
	 * The cache contents.
	 *
	 * @var array<string, mixed>
	 */
	private array $cached = [];

	/**
	 * Now, as a timestamp.
	 *
	 * @var int
	 */
	private int $now = 1_780_000_000;

	/**
	 * The provider over an in-memory cache, a recording gateway and one signer record.
	 *
	 * @param bool $withGateway Whether a gateway is bound.
	 *
	 * @return PhoneCodeProvider
	 */
	private function provider(bool $withGateway=true): PhoneCodeProvider {
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturnCallback(fn (string $key): mixed => $this->cached[$key] ?? null);
		$cache->method('set')->willReturnCallback(
			function (string $key, mixed $value): bool {
				$this->cached[$key] = $value;
				return true;
			}
		);
		$cache->method('remove')->willReturnCallback(
			function (string $key): bool {
				unset($this->cached[$key]);
				return true;
			}
		);
		$caches = $this->createMock(ICacheFactory::class);
		$caches->method('createDistributed')->with('filinq-phone-code')->willReturn($cache);

		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getTime')->willReturnCallback(fn (): int => $this->now);

		$objects = $this->getMockBuilder(ObjectService::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		$objects->method('find')->willReturnCallback(
			static fn (string $id): array => ['id' => $id, 'signingRequestId' => 'req-contract-1', 'phoneNumber' => '+31 6 12345678']
		);
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objects);
		$settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);

		$sender = null;
		if ($withGateway === true) {
			$sender = $this->createMock(PhoneCodeSenderInterface::class);
			$sender->method('send')->willReturnCallback(
				function (string $phoneNumber, string $message): void {
					$this->texts[] = [$phoneNumber, $message];
				}
			);
		}

		return new PhoneCodeProvider(settings: $settings, caches: $caches, clock: $clock, pseudonymiser: $this->pseudonymiser(), sender: $sender);

	}//end provider()

	/**
	 * {@inheritDoc}
	 *
	 * @return SignerAuthenticationProviderInterface
	 */
	protected function createProvider(): SignerAuthenticationProviderInterface {
		return $this->provider();

	}//end createProvider()

	/**
	 * The code in the last text.
	 *
	 * @return string
	 */
	private function sentCode(): string {
		preg_match('/\b(\d{6})\b/', (string) end($this->texts)[1], $match);

		return $match[1];

	}//end sentCode()

	/**
	 * {@inheritDoc}
	 *
	 * @param SignerAuthenticationProviderInterface $provider  The provider
	 * @param SignerAuthContext                     $context   The act
	 * @param AuthChallenge                         $challenge The challenge
	 *
	 * @return array<string, mixed>
	 */
	protected function validCallback(SignerAuthenticationProviderInterface $provider, SignerAuthContext $context, AuthChallenge $challenge): array {
		return ['requestId' => $context->requestId, 'signerId' => $context->signerId, 'code' => $this->sentCode()];

	}//end validCallback()

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string, mixed> $valid A valid callback
	 *
	 * @return array<string, mixed>
	 */
	protected function foreignCallback(array $valid): array {
		$valid['signerId'] = 'signer-of-someone-else';

		return $valid;

	}//end foreignCallback()

	/**
	 * The right code is accepted once; the evidence names the phone code and keeps neither code nor number.
	 *
	 * @return void
	 */
	public function testTheRightCodeIsAcceptedOnce(): void {
		$provider = $this->provider();
		$challenge = $provider->initiateAuthentication($this->context());

		$this->assertSame(AuthChallenge::TYPE_CODE, $challenge->type);
		$this->assertSame('+31 6 12345678', $this->texts[0][0]);
		$code = $this->sentCode();

		$evidence = $provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $code]);

		$this->assertSame('phone-code', $evidence->means);
		$this->assertSame('low', $evidence->assurance);
		$stored = json_encode($evidence->toArray());
		$this->assertStringNotContainsString($code, $stored);
		$this->assertStringNotContainsString('12345678', $stored);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No phone code is pending for this signer, or it was used');
		$provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $code]);

	}//end testTheRightCodeIsAcceptedOnce()

	/**
	 * A wrong code is refused and the right one still works, until too many wrong ones void it.
	 *
	 * @return void
	 */
	public function testAWrongCodeIsRefused(): void {
		$provider = $this->provider();
		$provider->initiateAuthentication($this->context());
		$code = $this->sentCode();
		$wrong = str_pad((string) (((int) $code + 1) % 1000000), 6, '0', STR_PAD_LEFT);

		try {
			$provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $wrong]);
			$this->fail('A wrong code was accepted');
		} catch (RuntimeException $e) {
			$this->assertSame('The phone code is wrong', $e->getMessage());
		}

		$this->assertSame('phone-code', $provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $code])->means);

		$provider->initiateAuthentication($this->context());
		$code = $this->sentCode();
		$wrong = str_pad((string) (((int) $code + 1) % 1000000), 6, '0', STR_PAD_LEFT);
		for ($i = 0; $i < PhoneCodeProvider::MAX_ATTEMPTS; $i++) {
			try {
				$provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $wrong]);
			} catch (RuntimeException) {
			}
		}

		$this->expectExceptionMessage('No phone code is pending for this signer, or it was used');
		$provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $code]);

	}//end testAWrongCodeIsRefused()

	/**
	 * An expired code is refused.
	 *
	 * @return void
	 */
	public function testAnExpiredCodeIsRefused(): void {
		$provider = $this->provider();
		$provider->initiateAuthentication($this->context());
		$this->now += (PhoneCodeProvider::TTL_SECONDS + 1);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The phone code has expired');
		$provider->completeAuthentication(['requestId' => 'req-contract-1', 'signerId' => 'signer-contract-1', 'code' => $this->sentCode()]);

	}//end testAnExpiredCodeIsRefused()

	/**
	 * Without a gateway nothing is sent and the provider refuses to start.
	 *
	 * @return void
	 */
	public function testWithoutAGatewayItRefusesToStart(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No text message gateway is configured for phone codes');

		try {
			$this->provider(withGateway: false)->initiateAuthentication($this->context());
		} finally {
			$this->assertSame([], $this->cached);
		}

	}//end testWithoutAGatewayItRefusesToStart()

	/**
	 * The factory registers the phone code provider when it is given.
	 *
	 * @return void
	 */
	public function testTheFactoryRegistersIt(): void {
		$factory = new SignerAuthProviderFactory(
			settings: $this->createMock(SignerAuthSettings::class),
			sessionProvider: $this->createMock(NextcloudSessionProvider::class),
			brokerProvider: $this->createMock(OidcBrokerProvider::class),
			phoneCodeProvider: $this->provider()
		);

		$this->assertTrue($factory->has('phone-code'));
		$this->assertInstanceOf(PhoneCodeProvider::class, $factory->get('phone-code'));

	}//end testTheFactoryRegistersIt()
}//end class
