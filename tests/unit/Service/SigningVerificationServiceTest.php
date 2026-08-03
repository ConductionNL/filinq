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
     * A legacy (pre-rebuild, v1) marker is reported unverifiable, never valid.
     *
     * v1 MAC = HMAC(secret, contentHash) — no `v` field, no identity binding.
     * Retained as a REGRESSION test for the v1 shape while the v2 rebuild
     * (signing-trust-rebuild REQ-DDSTR-001) supersedes it as the writer.
     *
     * @return void
     */
    public function testLegacyV1ArtifactIsUnverifiableNeverValid(): void
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
        // v1 formula: MAC over the content-hash ONLY — no `v` field.
        $mac       = hash_hmac('sha256', hash('sha256', $canonical), $secret);
        $payload   = base64_encode((string) json_encode(['signer' => 'Alice', 'level' => 'SES', 'method' => 'native', 'mac' => $mac]));
        $signed    = $assemble($original, $payload);

        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $signed);

        $this->assertSame('unverifiable', $result[0]['status'], 'A v1 assertion must never report verified/invalid.');
        $this->assertSame('legacy-assertion-v1', $result[0]['reason']);
        $this->assertFalse($result[0]['valid'], 'A v1 assertion must never report valid=true.');

    }//end testLegacyV1ArtifactIsUnverifiableNeverValid()

    /**
     * Build a v2 signed marker with a caller-chosen assertion, mirroring
     * NativeSigningProvider::produceSignedArtifact()'s exact canonical-JSON +
     * MAC formula (signing-trust-rebuild REQ-DDSTR-001). Returns both the
     * signed bytes and the decoded assertion so a test can mutate it.
     *
     * @param string               $secret         The signing secret.
     * @param array<string, mixed> $assertionExtra Fields to merge onto the base assertion.
     *
     * @return array{0: string, 1: array<string, mixed>} [signedBytes, assertion]
     */
    private function buildV2SignedArtifact(string $secret, array $assertionExtra=[]): array
    {
        $original = "%PDF-1.4\ngovernment besluit content\n%%EOF\n";
        $assemble = static function (string $doc, string $payload): string {
            return $doc."\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(".$payload.")\nendobj\n";
        };

        $assertion = array_merge(
            [
                'v'         => 2,
                'signer'    => 'Alice',
                'signers'   => [],
                'timestamp' => '2026-07-23T12:00:00+00:00',
                'level'     => 'SES',
                'method'    => 'native',
                'ip'        => '127.0.0.1',
            ],
            $assertionExtra
        );

        $canonical   = $assemble($original, '');
        $contentHash = hash('sha256', $canonical);
        $payloadCore = (new \OCA\DocuDesk\Service\Signing\AssertionCanonicalizer())->canonicalJson(data: $assertion);

        $mac              = hash_hmac('sha256', $contentHash."\n".$payloadCore, $secret);
        $assertion['mac'] = $mac;

        $payload = base64_encode((string) json_encode($assertion));
        $signed  = $assemble($original, $payload);

        return [$signed, $assertion];

    }//end buildV2SignedArtifact()

    /**
     * A genuine v2 artifact, unmodified, verifies (writer/verifier symmetry).
     *
     * @return void
     */
    public function testV2ArtifactVerifiesWhenGenuine(): void
    {
        $secret = 'e2e-verification-secret';
        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') use ($secret): string {
                return $key === 'signing_verification_secret' ? $secret : $default;
            }
        );

        [$signed] = $this->buildV2SignedArtifact(secret: $secret);

        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $signed);

        $this->assertSame('verified', $result[0]['status']);
        $this->assertTrue($result[0]['valid']);
        $this->assertSame('Alice', $result[0]['signer']);

    }//end testV2ArtifactVerifiesWhenGenuine()

    /**
     * SECURITY TEST (portaliq#3 class): a v2 artifact whose assertion PAYLOAD
     * is rewritten to claim a DIFFERENT signer name — keeping the original
     * `mac` — reports `invalid`, never `verified` (signing-trust-rebuild
     * REQ-DDSTR-001, the core forgeable-signer mutation test).
     *
     * @return void
     */
    public function testPayloadRewrittenSignerNameFailsVerification(): void
    {
        $secret = 'e2e-verification-secret';
        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') use ($secret): string {
                return $key === 'signing_verification_secret' ? $secret : $default;
            }
        );

        [$signed, $genuineAssertion] = $this->buildV2SignedArtifact(secret: $secret);

        // Attacker rewrites the assertion payload — swaps the signer name —
        // but KEEPS the original mac (the exact portaliq#3 attack shape).
        $forgedAssertion           = $genuineAssertion;
        $forgedAssertion['signer'] = 'Mallory (attacker)';
        // mac is intentionally left as the ORIGINAL genuine value.
        $forgedPayload = base64_encode((string) json_encode($forgedAssertion));

        // Splice the forged payload into the signed bytes in place of the
        // genuine marker (mirrors an attacker editing the PDF byte stream).
        $forgedSigned = preg_replace(
            '/\/DocuDesk-Signature\([^)]*\)/',
            '/DocuDesk-Signature('.$forgedPayload.')',
            $signed
        );

        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $forgedSigned);

        $this->assertSame(
            'invalid',
            $result[0]['status'],
            'A payload-rewritten assertion (signer name swapped, MAC kept) MUST verify invalid.'
        );
        $this->assertFalse($result[0]['valid']);
        $this->assertSame('Mallory (attacker)', $result[0]['signer'], 'The rewritten (forged) signer name is what was reported — but as INVALID.');

    }//end testPayloadRewrittenSignerNameFailsVerification()

    /**
     * A v2 artifact whose document BYTES were modified after signing (not the
     * assertion) also reports `invalid` — this IS tamper evidence
     * (signing-trust-rebuild REQ-DDSTR-005: document-level verdict `tampered`).
     *
     * @return void
     */
    public function testByteFlippedContentFailsVerification(): void
    {
        $secret = 'e2e-verification-secret';
        $this->mockConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') use ($secret): string {
                return $key === 'signing_verification_secret' ? $secret : $default;
            }
        );

        [$signed] = $this->buildV2SignedArtifact(secret: $secret);
        $tampered = str_replace('besluit', 'FORGED', $signed);

        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $tampered);

        $this->assertSame('invalid', $result[0]['status']);
        $this->assertFalse($result[0]['valid']);

    }//end testByteFlippedContentFailsVerification()

    /**
     * The document-level `verdict` distinguishes tampering from mere
     * inability to verify (signing-trust-rebuild REQ-DDSTR-005).
     *
     * @return void
     */
    public function testComputeVerdictDistinguishesStates(): void
    {
        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('computeVerdict');
        $method->setAccessible(true);

        $this->assertSame('verified', $method->invoke($this->service, [['status' => 'verified']]));
        $this->assertSame('tampered', $method->invoke($this->service, [['status' => 'invalid']]));
        $this->assertSame('unverifiable', $method->invoke($this->service, [['status' => 'unverifiable']]));
        $this->assertSame('unverifiable', $method->invoke($this->service, []));
        $this->assertSame(
            'mixed',
            $method->invoke($this->service, [['status' => 'verified'], ['status' => 'invalid']])
        );

    }//end testComputeVerdictDistinguishesStates()

    /**
     * An embedded external `/Type /Sig` signature (no DocuDesk marker) is
     * honestly `unverifiable`, never `invalid` — DocuDesk cannot yet validate
     * it, and that is not the same as tampering (signing-trust-rebuild
     * REQ-DDSTR-005).
     *
     * @return void
     */
    public function testExternalSignatureIsUnverifiableNotInvalid(): void
    {
        $externalPdf = "%PDF-1.4\n1 0 obj\n<< /Type /Sig /Filter /Adobe.PPKLite >>\nendobj\n%%EOF\n";

        $ref    = new ReflectionClass($this->service);
        $method = $ref->getMethod('extractSignatures');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $externalPdf);

        $this->assertCount(1, $result);
        $this->assertSame('unverifiable', $result[0]['status']);
        $this->assertSame('external-signature-unsupported', $result[0]['reason']);
        $this->assertFalse($result[0]['valid']);

    }//end testExternalSignatureIsUnverifiableNotInvalid()

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
