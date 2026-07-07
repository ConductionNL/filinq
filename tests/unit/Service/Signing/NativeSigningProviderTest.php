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
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Signing;

use OCA\DocuDesk\Service\Signing\NativeSigningProvider;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for NativeSigningProvider C1 mitigation (issue #304)
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NativeSigningProviderTest extends TestCase
{
    /**
     * Build a minimal NativeSigningProvider for testing.
     *
     * @param string $secret The signing_verification_secret to return ('' = unset).
     *
     * @return NativeSigningProvider
     */
    private function buildProvider(string $secret=''): NativeSigningProvider
    {
        $logger = $this->createMock(LoggerInterface::class);

        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') use ($secret): string {
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
    protected function setUp(): void
    {
        parent::setUp();

    }//end setUp()

    /**
     * initiateSigning now creates a session (issue #304 writer wired) rather
     * than throwing — it returns a success envelope with an externalId.
     *
     * @return void
     */
    public function testInitiateCreatesSession(): void
    {
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
     * HMAC the SigningVerificationService recomputes and accepts (issue #304).
     *
     * @return void
     */
    public function testProduceSignedArtifactPassesVerifier(): void
    {
        $secret   = 'unit-test-signing-secret';
        $provider = $this->buildProvider(secret: $secret);

        $original = "%PDF-1.4\noriginal besluit content\n%%EOF\n";
        $signed   = $provider->produceSignedArtifact(
            documentContent: $original,
            context: ['signer' => 'Alice', 'ip' => '127.0.0.1', 'level' => 'SES']
        );

        $this->assertStringContainsString('/Type /Sig', $signed);
        $this->assertStringContainsString('/DocuDesk-Signature(', $signed);

        // Acceptance oracle: the existing verifier's extractSignatures() must
        // report the produced artifact as valid=true for the same secret.
        $verifierConfig = $this->createMock(IAppConfig::class);
        $verifierConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') use ($secret): string {
                return $key === 'signing_verification_secret' ? $secret : $default;
            }
        );

        $verifier = new \OCA\DocuDesk\Service\SigningVerificationService(
            rootFolder: $this->createMock(\OCP\Files\IRootFolder::class),
            config: $verifierConfig
        );

        $ref    = new \ReflectionClass($verifier);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);
        $signatures = $method->invoke($verifier, $signed);

        $this->assertCount(1, $signatures);
        $this->assertTrue($signatures[0]['valid'], 'Produced artifact must verify against the existing verifier.');
        $this->assertSame('Alice', $signatures[0]['signer']);

    }//end testProduceSignedArtifactPassesVerifier()

    /**
     * Honest-completion gate: produceSignedArtifact throws when the signing
     * secret is unset rather than emitting an unverifiable artifact.
     *
     * @return void
     */
    public function testProduceSignedArtifactThrowsWhenSecretUnset(): void
    {
        $provider = $this->buildProvider(secret: '');

        $this->expectException(exception: RuntimeException::class);
        $this->expectExceptionMessage(message: 'signing_verification_secret is unset');

        $provider->produceSignedArtifact(documentContent: "%PDF-1.4\n", context: []);

    }//end testProduceSignedArtifactThrowsWhenSecretUnset()

    /**
     * downloadSignedDocument throws for an unknown session (no silent fallback).
     *
     * @return void
     */
    public function testDownloadThrowsForMissingSession(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(exception: RuntimeException::class);

        $provider->downloadSignedDocument(externalId: 'native-does-not-exist');

    }//end testDownloadThrowsForMissingSession()

    /**
     * CheckStatus on an unknown externalId throws (not silently returns).
     *
     * @return void
     */
    public function testCheckStatusOnMissingSessionThrows(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(exception: RuntimeException::class);
        $provider->checkStatus(externalId: 'native-does-not-exist');

    }//end testCheckStatusOnMissingSessionThrows()
}//end class
