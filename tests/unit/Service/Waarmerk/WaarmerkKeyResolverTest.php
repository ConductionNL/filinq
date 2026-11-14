<?php

/**
 * Unit tests for WaarmerkKeyResolver.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\SignerAuth\BrokerCredentialResolver;
use OCA\Filinq\Service\Waarmerk\WaarmerkCertificate;
use OCA\Filinq\Service\Waarmerk\WaarmerkKeyResolver;
use OCA\Filinq\Service\Waarmerk\WaarmerkSettings;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Key custody: broker or local, fail-closed, never logged.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkKeyResolver
 * @uses   \OCA\Filinq\Service\Waarmerk\WaarmerkCertificate
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkKeyResolverTest extends TestCase {

	/**
	 * Build a resolver.
	 *
	 * @param string $custody The custody mode.
	 * @param string $localKey The local key.
	 * @param BrokerCredentialResolver $broker The broker double.
	 * @param LoggerInterface|null $logger The logger double.
	 *
	 * @return WaarmerkKeyResolver
	 */
	private function resolver(string $custody, string $localKey, BrokerCredentialResolver $broker, ?LoggerInterface $logger = null): WaarmerkKeyResolver {
		$settings = $this->createMock(WaarmerkSettings::class);
		$settings->method('custody')->willReturn($custody);
		$settings->method('localKey')->willReturn($localKey);
		$settings->method('credentialRef')->willReturn('ref-1');

		return new WaarmerkKeyResolver(settings: $settings, broker: $broker, logger: $logger ?? $this->createMock(LoggerInterface::class));
	}//end resolver()

	/**
	 * Broker custody resolves the key through the credential reference.
	 *
	 * @return void
	 */
	public function testBrokerCustody(): void {
		$pair = WaarmerkKeyPair::make();
		$broker = $this->createMock(BrokerCredentialResolver::class);
		$broker->expects($this->once())->method('resolve')->with('ref-1')->willReturn($pair['key']);

		$key = $this->resolver(custody: 'broker', localKey: '', broker: $broker)->resolve(certificate: WaarmerkCertificate::fromPem(pem: $pair['cert']));
		$this->assertSame($pair['key'], $key);
	}//end testBrokerCustody()

	/**
	 * Local custody reads the decrypted local key and never calls the broker.
	 *
	 * @return void
	 */
	public function testLocalCustody(): void {
		$pair = WaarmerkKeyPair::make();
		$broker = $this->createMock(BrokerCredentialResolver::class);
		$broker->expects($this->never())->method('resolve');

		$key = $this->resolver(custody: 'local', localKey: $pair['key'], broker: $broker)->resolve(certificate: WaarmerkCertificate::fromPem(pem: $pair['cert']));
		$this->assertSame($pair['key'], $key);
	}//end testLocalCustody()

	/**
	 * A broker failure is a 503 naming key resolution, and the log carries no key.
	 *
	 * @return void
	 */
	public function testUnresolvableKeyFailsClosedWithoutLoggingIt(): void {
		$pair = WaarmerkKeyPair::make();
		$broker = $this->createMock(BrokerCredentialResolver::class);
		$broker->method('resolve')->willThrowException(new RuntimeException('broker down'));
		$logged = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(function (string $message, array $context) use (&$logged): void {
			$logged[] = $message . json_encode($context);
		});

		try {
			$this->resolver(custody: 'broker', localKey: '', broker: $broker, logger: $logger)->resolve(certificate: WaarmerkCertificate::fromPem(pem: $pair['cert']));
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(503, $e->getStatus());
			$this->assertStringContainsString('Key resolution failed', $e->getMessage());
		}

		$this->assertCount(1, $logged);
		$this->assertStringNotContainsString('PRIVATE KEY', $logged[0]);
		$this->assertStringContainsString('broker', $logged[0]);
	}//end testUnresolvableKeyFailsClosedWithoutLoggingIt()

	/**
	 * A key that does not belong to the certificate, or none at all, is refused too.
	 *
	 * @return void
	 */
	public function testAMismatchedOrEmptyKeyIsRefused(): void {
		$pair = WaarmerkKeyPair::make();
		$other = WaarmerkKeyPair::make(organisation: 'Gemeente Elders');
		$broker = $this->createMock(BrokerCredentialResolver::class);
		foreach (['local' => [$other['key'], 'other'], 'empty' => ['', 'none']] as $name => [$key]) {
			try {
				$this->resolver(custody: 'local', localKey: $key, broker: $broker)->resolve(certificate: WaarmerkCertificate::fromPem(pem: $pair['cert']));
				$this->fail($name . ': expected a refusal');
			} catch (WaarmerkRefusedException $e) {
				$this->assertSame(503, $e->getStatus(), $name);
			}
		}
	}//end testAMismatchedOrEmptyKeyIsRefused()
}//end class
