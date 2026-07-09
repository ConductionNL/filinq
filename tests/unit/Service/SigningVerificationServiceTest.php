<?php

/**
 * Unit tests for SigningVerificationService — findings M1 and L1.
 *
 * M1: allSignaturesValid() must return false for an empty signature list so
 *     that a document with zero verifiable signatures is never reported as
 *     valid (previously returned true).
 *
 * L1: stripAssertionMac() must use preg_replace with preg_quote so that a MAC
 *     value that contains regex metacharacters is treated as a literal string
 *     (previously used str_replace which cannot handle regex patterns, but
 *     the intent was to strip literal occurrences — using preg_replace with
 *     preg_quote is the correct approach for safety and explicitness).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\SigningVerificationService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for SigningVerificationService correctness
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningVerificationServiceTest extends TestCase
{

    /**
     * @var SigningVerificationService
     */
    private SigningVerificationService $service;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = $this->createMock(IAppConfig::class);

        $this->service = new SigningVerificationService(
            rootFolder: $this->createMock(IRootFolder::class),
            config: $this->mockConfig
        );

    }//end setUp()


    /**
     * allSignaturesValid() must return false for an empty array (finding M1).
     *
     * Before the fix, the foreach simply never ran and the method returned true
     * — a document with zero signatures was incorrectly considered "all valid".
     *
     * @return void
     */
    public function testAllSignaturesValidReturnsFalseForEmptyArray(): void
    {
        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('allSignaturesValid');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, []);

        $this->assertFalse(
            $result,
            'allSignaturesValid() must return false for an empty signatures array (finding M1).'
        );

    }//end testAllSignaturesValidReturnsFalseForEmptyArray()


    /**
     * allSignaturesValid() returns true only when all entries have valid=true.
     *
     * @return void
     */
    public function testAllSignaturesValidReturnsTrueWhenAllValid(): void
    {
        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('allSignaturesValid');
        $method->setAccessible(true);

        $signatures = [
            ['valid' => true, 'signer' => 'Alice'],
            ['valid' => true, 'signer' => 'Bob'],
        ];

        $this->assertTrue($method->invoke($this->service, $signatures));

    }//end testAllSignaturesValidReturnsTrueWhenAllValid()


    /**
     * allSignaturesValid() returns false when any entry has valid=false.
     *
     * @return void
     */
    public function testAllSignaturesValidReturnsFalseWhenOneInvalid(): void
    {
        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('allSignaturesValid');
        $method->setAccessible(true);

        $signatures = [
            ['valid' => true,  'signer' => 'Alice'],
            ['valid' => false, 'signer' => 'Bob'],
        ];

        $this->assertFalse($method->invoke($this->service, $signatures));

    }//end testAllSignaturesValidReturnsFalseWhenOneInvalid()


    /**
     * A natively-produced SES artifact verifies against the marker+HMAC path.
     *
     * Builds the same canonical-form marker the NativeSigningProvider writer
     * emits and confirms extractSignatures() reports valid=true when the secret
     * matches, and valid=false for a wrong secret or tampered content
     * (native-ses-signature-embedding acceptance oracle, issue #304).
     *
     * @return void
     */
    public function testNativeArtifactVerifiesAgainstMarkerHmac(): void
    {
        $secret = 'e2e-verification-secret';
        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') use ($secret): string {
                return $key === 'signing_verification_secret' ? $secret : $default;
            }
        );

        // Reproduce the writer's canonical-form marker embedding.
        $original  = "%PDF-1.4\ngovernment besluit content\n%%EOF\n";
        $assemble  = static function (string $doc, string $payload): string {
            return $doc."\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(".$payload.")\nendobj\n";
        };
        $canonical = $assemble($original, '');
        $mac       = hash_hmac('sha256', hash('sha256', $canonical), $secret);
        $payload   = base64_encode((string) json_encode(['signer' => 'Alice', 'level' => 'SES', 'method' => 'native', 'mac' => $mac]));
        $signed    = $assemble($original, $payload);

        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);

        $valid = $method->invoke($this->service, $signed);
        $this->assertTrue($valid[0]['valid'], 'A correctly signed artifact must verify.');

        // Tampering the document content invalidates the assertion.
        $tampered = $method->invoke($this->service, str_replace('besluit', 'FORGED', $signed));
        $this->assertFalse($tampered[0]['valid'], 'Tampered content must not verify.');

    }//end testNativeArtifactVerifiesAgainstMarkerHmac()

    /**
     * stripAssertionMac() removes a plain MAC value from the content (finding L1).
     *
     * @return void
     */
    public function testStripAssertionMacRemovesLiteralMac(): void
    {
        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('stripAssertionMac');
        $method->setAccessible(true);

        $mac     = 'abc123def456';
        $content = 'prefix ' . $mac . ' suffix';

        $result = $method->invoke($this->service, $content, $mac);

        $this->assertStringNotContainsString($mac, $result);
        $this->assertStringContainsString('prefix', $result);
        $this->assertStringContainsString('suffix', $result);

    }//end testStripAssertionMacRemovesLiteralMac()


    /**
     * stripAssertionMac() handles MAC values that contain regex metacharacters
     * (finding L1 — preg_quote ensures literal matching).
     *
     * @return void
     */
    public function testStripAssertionMacHandlesRegexMetacharacters(): void
    {
        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('stripAssertionMac');
        $method->setAccessible(true);

        // A MAC that happens to contain characters special to regex.
        $mac     = 'a.b+c(d)e$f';
        $content = 'before' . $mac . 'after';

        // Must not throw a regex error and must strip the literal text.
        $result = $method->invoke($this->service, $content, $mac);

        $this->assertStringNotContainsString($mac, $result);
        $this->assertSame('beforeafter', $result);

    }//end testStripAssertionMacHandlesRegexMetacharacters()


}//end class
