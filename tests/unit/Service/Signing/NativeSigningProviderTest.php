<?php

/**
 * Unit tests for NativeSigningProvider — wave-9 C1 mitigation coverage.
 *
 * Verifies that `initiateSigning` and `downloadSignedDocument` throw
 * a descriptive RuntimeException referencing issue #304 until the full
 * request↔provider wiring ships.  Also guards the `checkStatus` not-found
 * path retained from finding #287 regression coverage.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for NativeSigningProvider C1 mitigation (issue #304)
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NativeSigningProviderTest extends TestCase {
	/**
	 * Build a minimal NativeSigningProvider for testing.
	 *
	 * @param string $secret The signing_verification_secret to return ('' = unset).
	 *
	 * @return NativeSigningProvider
	 */
	private function buildProvider(string $secret = ''): NativeSigningProvider {
		$logger = $this->createMock(LoggerInterface::class);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				if ($key === 'signing_verification_secret') {
					return $secret;
				}

				return $default;
			}
		);

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['findAll', 'saveObject'])
			->getMock();

		$objectService->method('findAll')->willReturn([]);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($objectService);

		return new NativeSigningProvider(
			logger: $logger,
			settingsService: $settingsService,
			config: $config
		);

	}//end buildProvider()

	/**
	 * Reset test state before each test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

	}//end setUp()

	/**
	 * initiateSigning now creates a session (issue #304 writer wired) rather
	 * than throwing — it returns a success envelope with an externalId.
	 *
	 * @return void
	 */
	public function testInitiateCreatesSession(): void {
		$provider = $this->buildProvider();

		$result = $provider->initiateSigning(
			documentPath: '/foo.pdf',
			documentName: 'foo.pdf',
			signers: [['userId' => 'alice']],
			level: 'SES'
		);

		$this->assertTrue($result['success']);
		$this->assertStringStartsWith('native-', $result['externalId']);

	}//end testInitiateCreatesSession()

	/**
	 * produceSignedArtifact embeds a verifiable /DocuDesk-Signature marker whose
	 * v2 HMAC the SigningVerificationService recomputes and accepts (issue
	 * #304 / signing-trust-rebuild REQ-DDSTR-001).
	 *
	 * @return void
	 */
	public function testProduceSignedArtifactPassesVerifier(): void {
		$secret = 'unit-test-signing-secret';
		$provider = $this->buildProvider(secret: $secret);

		$original = "%PDF-1.4\noriginal besluit content\n%%EOF\n";
		$signed = $provider->produceSignedArtifact(
			documentContent: $original,
			context: ['signer' => 'Alice', 'ip' => '127.0.0.1', 'level' => 'SES']
		);

		$this->assertStringContainsString('/Type /Sig', $signed);
		$this->assertStringContainsString('/DocuDesk-Signature(', $signed);

		// Acceptance oracle: the existing verifier's extractSignatures() must
		// report the produced artifact as status=verified for the same secret.
		$verifierConfig = $this->createMock(IAppConfig::class);
		$verifierConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		$verifier = new \OCA\Filinq\Service\SigningVerificationService(
			rootFolder: $this->createMock(\OCP\Files\IRootFolder::class),
			config: $verifierConfig
		);

		$ref = new \ReflectionClass($verifier);
		$method = $ref->getMethod('extractSignatures');
		$method->setAccessible(true);
		$signatures = $method->invoke($verifier, $signed);

		$this->assertCount(1, $signatures);
		$this->assertSame('verified', $signatures[0]['status'], 'Produced artifact must verify against the existing verifier (v2).');
		$this->assertTrue($signatures[0]['valid'], 'Produced artifact must verify against the existing verifier.');
		$this->assertSame('Alice', $signatures[0]['signer']);

	}//end testProduceSignedArtifactPassesVerifier()

	/**
	 * The produced artifact folds the verified-actor-resolved portal identity
	 * claims into the SAME MAC (portal-signing-surface REQ-DDPSS-004): a
	 * rewritten portal identity field invalidates verification.
	 *
	 * @return void
	 */
	public function testProduceSignedArtifactBindsPortalIdentityIntoMac(): void {
		$secret = 'unit-test-signing-secret';
		$provider = $this->buildProvider(secret: $secret);

		$original = "%PDF-1.4\noriginal besluit content\n%%EOF\n";
		$signed = $provider->produceSignedArtifact(
			documentContent: $original,
			context: [
				'signer' => 'signer@example.invalid',
				'ip' => '127.0.0.1',
				'level' => 'SES',
				'portalSubjectRef' => '00000000-0000-0000-0000-000000000003',
				'portalTrust' => 'substantial',
				'portalJti' => '00000000-0000-0000-0000-0000000000aa',
			]
		);

		$verifierConfig = $this->createMock(IAppConfig::class);
		$verifierConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);
		$verifier = new \OCA\Filinq\Service\SigningVerificationService(
			rootFolder: $this->createMock(\OCP\Files\IRootFolder::class),
			config: $verifierConfig
		);
		$ref = new \ReflectionClass($verifier);
		$method = $ref->getMethod('extractSignatures');
		$method->setAccessible(true);

		// Genuine artifact verifies.
		$this->assertSame('verified', $method->invoke($verifier, $signed)[0]['status']);

		// Rewrite the bound portal subject reference, keeping the original mac.
		$dataPattern = '/\/DocuDesk-Signature\s*\(([^)]+)\)/';
		preg_match($dataPattern, $signed, $matches);
		$genuineAssertion = json_decode(base64_decode($matches[1]), true);
		$forgedAssertion = $genuineAssertion;
		$forgedAssertion['portalSubjectRef'] = '00000000-0000-0000-0000-000000000099';
		$forgedPayload = base64_encode((string)json_encode($forgedAssertion));
		$forgedSigned = str_replace($matches[1], $forgedPayload, $signed);

		$result = $method->invoke($verifier, $forgedSigned);
		$this->assertSame(
			'invalid',
			$result[0]['status'],
			'Rewriting the bound portal subjectRef (MAC kept) MUST invalidate verification.'
		);

	}//end testProduceSignedArtifactBindsPortalIdentityIntoMac()

	/**
	 * Provider/level honesty (signing-trust-rebuild REQ-DDSTR-002 point 3):
	 * NativeSigningProvider::produceSignedArtifact() refuses a level it does
	 * not support, rather than producing an SES-mechanism artifact labelled
	 * with the unsupported level.
	 *
	 * @return void
	 */
	public function testProduceSignedArtifactRefusesUnsupportedLevel(): void {
		$provider = $this->buildProvider(secret: 'unit-test-signing-secret');

		$this->expectException(exception: RuntimeException::class);
		$this->expectExceptionMessage(message: 'unsupported level "AdES"');

		$provider->produceSignedArtifact(documentContent: "%PDF-1.4\n", context: ['level' => 'AdES']);

	}//end testProduceSignedArtifactRefusesUnsupportedLevel()

	/**
	 * Honest-completion gate: produceSignedArtifact throws when the signing
	 * secret is unset rather than emitting an unverifiable artifact.
	 *
	 * @return void
	 */
	public function testProduceSignedArtifactThrowsWhenSecretUnset(): void {
		$provider = $this->buildProvider(secret: '');

		$this->expectException(exception: RuntimeException::class);
		$this->expectExceptionMessage(message: 'signing_verification_secret is unset');

		$provider->produceSignedArtifact(documentContent: "%PDF-1.4\n", context: ['level' => 'SES']);

	}//end testProduceSignedArtifactThrowsWhenSecretUnset()

	/**
	 * downloadSignedDocument throws for an unknown session (no silent fallback).
	 *
	 * @return void
	 */
	public function testDownloadThrowsForMissingSession(): void {
		$provider = $this->buildProvider();

		$this->expectException(exception: RuntimeException::class);

		$provider->downloadSignedDocument(externalId: 'native-does-not-exist');

	}//end testDownloadThrowsForMissingSession()

	/**
	 * Fail-closed session download (signing-trust-rebuild REQ-DDSTR-004,
	 * closing the #287 residual): a completed session without an embedded,
	 * marker-verified artifact throws — it MUST NEVER return the unsigned
	 * original `documentPath`.
	 *
	 * @return void
	 */
	public function testDownloadRefusesCompletedSessionWithoutMarker(): void {
		$logger = $this->createMock(LoggerInterface::class);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('');

		$session = [
			'externalId' => 'native-no-marker',
			'documentPath' => '/unsigned/original.pdf',
			'status' => 'completed',
			'signedDocumentPath' => '',
			'markerEmbedded' => false,
		];

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['findAll', 'saveObject'])
			->getMock();
		$objectService->method('findAll')->willReturn([$session]);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($objectService);

		$provider = new NativeSigningProvider(logger: $logger, settingsService: $settingsService, config: $config);

		$this->expectException(exception: RuntimeException::class);
		$this->expectExceptionMessage(message: 'no verifiable signed artifact');

		$result = $provider->downloadSignedDocument(externalId: 'native-no-marker');

		// Defence in depth: if the assertion above were ever weakened, this
		// still guarantees the unsigned original is never the return value.
		$this->assertNotSame('/unsigned/original.pdf', $result ?? null);

	}//end testDownloadRefusesCompletedSessionWithoutMarker()

	/**
	 * A completed session WITH an embedded, verified marker DOES return the
	 * signed path (the fail-closed gate does not block the honest case).
	 *
	 * @return void
	 */
	public function testDownloadReturnsSignedPathWhenMarkerEmbedded(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('');

		$session = [
			'externalId' => 'native-with-marker',
			'documentPath' => '/unsigned/original.pdf',
			'status' => 'completed',
			'signedDocumentPath' => '/signed/artifact.pdf',
			'markerEmbedded' => true,
		];

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['findAll', 'saveObject'])
			->getMock();
		$objectService->method('findAll')->willReturn([$session]);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($objectService);

		$provider = new NativeSigningProvider(logger: $logger, settingsService: $settingsService, config: $config);

		$result = $provider->downloadSignedDocument(externalId: 'native-with-marker');

		$this->assertSame('/signed/artifact.pdf', $result);

	}//end testDownloadReturnsSignedPathWhenMarkerEmbedded()

	/**
	 * CheckStatus on an unknown externalId throws (not silently returns).
	 *
	 * @return void
	 */
	public function testCheckStatusOnMissingSessionThrows(): void {
		$provider = $this->buildProvider();

		$this->expectException(exception: RuntimeException::class);
		$provider->checkStatus(externalId: 'native-does-not-exist');

	}//end testCheckStatusOnMissingSessionThrows()
}//end class
