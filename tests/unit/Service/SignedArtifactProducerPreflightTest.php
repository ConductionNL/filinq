<?php

/**
 * Unit tests for SignedArtifactProducer::assertCanProduce().
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The preflight a signature passes before anything is written: a native
 * request needs a signing secret and the SES level; other providers are
 * checked when they produce.
 */
class SignedArtifactProducerPreflightTest extends TestCase {

	/**
	 * A producer whose factory answers $provider for every name.
	 *
	 * @param SigningProviderInterface $provider The provider.
	 *
	 * @return SignedArtifactProducer
	 */
	private function producer(SigningProviderInterface $provider): SignedArtifactProducer {
		$factory = $this->createMock(originalClassName: SigningProviderFactory::class);
		$factory->method('getProvider')->willReturn($provider);

		return new SignedArtifactProducer(
			providerFactory: $factory,
			userSession: $this->createMock(originalClassName: IUserSession::class),
			request: $this->createMock(originalClassName: IRequest::class),
			rootFolder: $this->createMock(originalClassName: IRootFolder::class),
			finalDocuments: $this->createMock(originalClassName: FinalDocumentService::class)
		);
	}//end producer()

	/**
	 * The native provider with the given signing secret.
	 *
	 * @param string $secret The configured secret.
	 *
	 * @return NativeSigningProvider
	 */
	private function native(string $secret): NativeSigningProvider {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturn($secret);

		return new NativeSigningProvider(
			logger: $this->createMock(originalClassName: LoggerInterface::class),
			settingsService: $this->createMock(originalClassName: SettingsService::class),
			config: $config
		);
	}//end native()

	/**
	 * No secret: the native request is refused, naming the setting.
	 *
	 * @return void
	 */
	public function testANativeRequestWithoutASecretIsRefused(): void {
		$this->expectException(exception: RuntimeException::class);
		$this->expectExceptionMessage(message: 'signing_verification_secret is unset');

		$this->producer(provider: $this->native(secret: ''))->assertCanProduce(request: ['provider' => 'native', 'signatureLevel' => 'SES']);
	}//end testANativeRequestWithoutASecretIsRefused()

	/**
	 * A level the native provider cannot sign at is refused too.
	 *
	 * @return void
	 */
	public function testANativeRequestAtALevelItCannotSignIsRefused(): void {
		$this->expectException(exception: RuntimeException::class);
		$this->expectExceptionMessage(message: 'unsupported level "QES"');

		$this->producer(provider: $this->native(secret: 'configured'))->assertCanProduce(request: ['provider' => 'native', 'signatureLevel' => 'QES']);
	}//end testANativeRequestAtALevelItCannotSignIsRefused()

	/**
	 * With a secret, an SES request (also when it names no level) passes.
	 *
	 * @return void
	 */
	public function testAConfiguredNativeRequestPasses(): void {
		$producer = $this->producer(provider: $this->native(secret: 'configured'));
		$producer->assertCanProduce(request: ['provider' => 'native', 'signatureLevel' => 'SES']);
		$producer->assertCanProduce(request: []);

		$this->addToAssertionCount(count: 2);
	}//end testAConfiguredNativeRequestPasses()

	/**
	 * Another provider is not asked anything here: it is checked when it produces.
	 *
	 * @return void
	 */
	public function testAnotherProviderIsNotAsked(): void {
		$provider = $this->createMock(originalClassName: SigningProviderInterface::class);
		$provider->expects($this->never())->method('produceSignedArtifact');

		$this->producer(provider: $provider)->assertCanProduce(request: ['provider' => 'libresign', 'signatureLevel' => 'QES']);
	}//end testAnotherProviderIsNotAsked()
}//end class
