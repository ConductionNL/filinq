<?php

/**
 * Unit tests for SigningVerificationService — findings M1 and L1.
 *
 * M1: allSignaturesValid() must return false for an empty signature list so
 *     that a document with zero verifiable signatures is never reported as
 *     valid (previously returned true).
 *
 * L1: the MAC is recomputed over the canonical document bytes as they are, so
 *     bytes added to a signed document fail verification, whatever they hold.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\SigningVerificationService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for SigningVerificationService correctness
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningVerificationServiceTest extends TestCase {

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
	protected function setUp(): void {
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
	public function testAllSignaturesValidReturnsFalseForEmptyArray(): void {
		$ref = new ReflectionClass($this->service);
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
	public function testAllSignaturesValidReturnsTrueWhenAllValid(): void {
		$ref = new ReflectionClass($this->service);
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
	public function testAllSignaturesValidReturnsFalseWhenOneInvalid(): void {
		$ref = new ReflectionClass($this->service);
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
	public function testLegacyV1ArtifactIsUnverifiableNeverValid(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		// Reproduce the writer's canonical-form marker embedding.
		$original = "%PDF-1.4\ngovernment besluit content\n%%EOF\n";
		$assemble = static function (string $doc, string $payload): string {
			return $doc . "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(" . $payload . ")\nendobj\n";
		};
		$canonical = $assemble($original, '');
		// v1 formula: MAC over the content-hash ONLY — no `v` field.
		$mac = hash_hmac('sha256', hash('sha256', $canonical), $secret);
		$payload = base64_encode((string)json_encode(['signer' => 'Alice', 'level' => 'SES', 'method' => 'native', 'mac' => $mac]));
		$signed = $assemble($original, $payload);

		$ref = new ReflectionClass($this->service);
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
	 * @param string $secret The signing secret.
	 * @param array<string, mixed> $assertionExtra Fields to merge onto the base assertion.
	 *
	 * @return array{0: string, 1: array<string, mixed>} [signedBytes, assertion]
	 */
	private function buildV2SignedArtifact(string $secret, array $assertionExtra = []): array {
		$original = "%PDF-1.4\ngovernment besluit content\n%%EOF\n";
		$assemble = static function (string $doc, string $payload): string {
			return $doc . "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(" . $payload . ")\nendobj\n";
		};

		$assertion = array_merge(
			[
				'v' => 2,
				'signer' => 'Alice',
				'signers' => [],
				'timestamp' => '2026-07-23T12:00:00+00:00',
				'level' => 'SES',
				'method' => 'native',
				'ip' => '127.0.0.1',
			],
			$assertionExtra
		);

		$canonical = $assemble($original, '');
		$contentHash = hash('sha256', $canonical);
		$payloadCore = (new \OCA\Filinq\Service\Signing\AssertionCanonicalizer())->canonicalJson(data: $assertion);

		$mac = hash_hmac('sha256', $contentHash . "\n" . $payloadCore, $secret);
		$assertion['mac'] = $mac;

		$payload = base64_encode((string)json_encode($assertion));
		$signed = $assemble($original, $payload);

		return [$signed, $assertion];
	}//end buildV2SignedArtifact()

	/**
	 * A genuine v2 artifact, unmodified, verifies (writer/verifier symmetry).
	 *
	 * @return void
	 */
	public function testV2ArtifactVerifiesWhenGenuine(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed] = $this->buildV2SignedArtifact(secret: $secret);

		$ref = new ReflectionClass($this->service);
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
	public function testPayloadRewrittenSignerNameFailsVerification(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed, $genuineAssertion] = $this->buildV2SignedArtifact(secret: $secret);

		// Attacker rewrites the assertion payload — swaps the signer name —
		// but KEEPS the original mac (the exact portaliq#3 attack shape).
		$forgedAssertion = $genuineAssertion;
		$forgedAssertion['signer'] = 'Mallory (attacker)';
		// mac is intentionally left as the ORIGINAL genuine value.
		$forgedPayload = base64_encode((string)json_encode($forgedAssertion));

		// Splice the forged payload into the signed bytes in place of the
		// genuine marker (mirrors an attacker editing the PDF byte stream).
		$forgedSigned = preg_replace(
			'/\/DocuDesk-Signature\([^)]*\)/',
			'/DocuDesk-Signature(' . $forgedPayload . ')',
			$signed
		);

		$ref = new ReflectionClass($this->service);
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
	public function testByteFlippedContentFailsVerification(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed] = $this->buildV2SignedArtifact(secret: $secret);
		$tampered = str_replace('besluit', 'FORGED', $signed);

		$ref = new ReflectionClass($this->service);
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
	public function testComputeVerdictDistinguishesStates(): void {
		$ref = new ReflectionClass($this->service);
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
	 * An embedded external `/Type /Sig` signature (no Filinq marker) is
	 * honestly `unverifiable`, never `invalid` — Filinq cannot yet validate
	 * it, and that is not the same as tampering (signing-trust-rebuild
	 * REQ-DDSTR-005).
	 *
	 * @return void
	 */
	public function testExternalSignatureIsUnverifiableNotInvalid(): void {
		$externalPdf = "%PDF-1.4\n1 0 obj\n<< /Type /Sig /Filter /Adobe.PPKLite >>\nendobj\n%%EOF\n";

		$ref = new ReflectionClass($this->service);
		$method = $ref->getMethod('extractSignatures');
		$method->setAccessible(true);

		$result = $method->invoke($this->service, $externalPdf);

		$this->assertCount(1, $result);
		$this->assertSame('unverifiable', $result[0]['status']);
		$this->assertSame('external-signature-unsupported', $result[0]['reason']);
		$this->assertFalse($result[0]['valid']);

	}//end testExternalSignatureIsUnverifiableNotInvalid()

	/**
	 * Bytes added to a signed document fail verification, also when they are
	 * the asserted MAC itself (finding L1).
	 *
	 * @return void
	 */
	public function testAddedMacBytesFailVerification(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed, $assertion] = $this->buildV2SignedArtifact(secret: $secret);
		$tampered = str_replace('government besluit content', 'government besluit content' . $assertion['mac'], $signed);

		$this->assertNotSame($signed, $tampered);
		$result = $this->extract($tampered);

		$this->assertSame('invalid', $result[0]['status']);
		$this->assertFalse($result[0]['valid']);

	}//end testAddedMacBytesFailVerification()


	/**
	 * A verified result says what was checked, against which key, and what
	 * that PROVES.
	 *
	 * 🔴 The sentence has to include what a green tick does NOT mean. A MAC
	 * this server can recompute proves the server produced the artifact and
	 * that the bytes have not changed; it does not prove who the signer is,
	 * and it is not a qualified electronic signature. A verification screen
	 * that leaves that out is one somebody will rely on in a dispute.
	 *
	 * @return void
	 */
	public function testAVerifiedResultSaysWhatItProvesAndWhatItDoesNot(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed] = $this->buildV2SignedArtifact(secret: $secret);
		$result = $this->extract($signed);

		$this->assertSame('verified', $result[0]['status']);
		$this->assertSame('the document bytes and the assertion fields', $result[0]['checked']);
		$this->assertStringContainsString('have not changed', $result[0]['means']);
		$this->assertStringContainsString('does not prove who the signer is', $result[0]['means']);
		$this->assertStringContainsString('not a qualified electronic signature', $result[0]['means']);
	}//end testAVerifiedResultSaysWhatItProvesAndWhatItDoesNot()

	/**
	 * The key a check ran against is NAMED, and it is not the secret.
	 *
	 * "Verified" is meaningless without saying against what: an instance whose
	 * secret was rotated reports `invalid` for every older artifact, and with
	 * no key id that reads as mass tampering.
	 *
	 * @return void
	 */
	public function testTheKeyIsNamedAndTheNameIsNotTheSecret(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed] = $this->buildV2SignedArtifact(secret: $secret);
		$keyId = $this->extract($signed)[0]['keyId'];

		$this->assertIsString($keyId);
		$this->assertNotSame('', $keyId);
		$this->assertStringNotContainsString($secret, $keyId, 'the key id must not carry the secret');
		$this->assertStringNotContainsString($keyId, $secret);
	}//end testTheKeyIsNamedAndTheNameIsNotTheSecret()

	/**
	 * A rotated secret gets a different key id, which is the whole reason the
	 * id is there: it turns "everything is suddenly invalid" into "these were
	 * signed with the previous key".
	 *
	 * @return void
	 */
	public function testARotatedSecretGetsADifferentKeyId(): void {
		$first = $this->keyIdFor(secret: 'secret-one');
		$second = $this->keyIdFor(secret: 'secret-two');

		$this->assertNotSame($first, $second);
		$this->assertSame($first, $this->keyIdFor(secret: 'secret-one'), 'and the same key always gets the same id');
	}//end testARotatedSecretGetsADifferentKeyId()

	/**
	 * An instance with no key says so, and says that it is not evidence of
	 * anything being wrong.
	 *
	 * @return void
	 */
	public function testNoConfiguredKeyIsNotEvidenceOfTampering(): void {
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = ''): string {
				return $default;
			}
		);

		[$signed] = $this->buildV2SignedArtifact(secret: 'whatever-signed-it');
		$result = $this->extract($signed);

		$this->assertSame('unverifiable', $result[0]['status']);
		$this->assertNull($result[0]['keyId'], 'there was no key, so none is named');
		$this->assertSame('nothing', $result[0]['checked']);
		$this->assertStringContainsString('not evidence that anything is wrong', $result[0]['means']);
	}//end testNoConfiguredKeyIsNotEvidenceOfTampering()

	/**
	 * A tampered document says what changed and what else could explain it,
	 * rather than a slug.
	 *
	 * @return void
	 */
	public function testATamperedDocumentSaysWhatThatMeans(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed, $genuine] = $this->buildV2SignedArtifact(secret: $secret);
		$forged = $genuine;
		$forged['signer'] = 'Mallory (attacker)';
		$signed = preg_replace(
			'/\/DocuDesk-Signature\s*\([^)]*\)/',
			'/DocuDesk-Signature(' . base64_encode((string)json_encode($forged)) . ')',
			$signed
		);

		$result = $this->extract((string)$signed);

		$this->assertSame('invalid', $result[0]['status']);
		$this->assertStringContainsString('changed after this server signed it', $result[0]['means']);
		$this->assertStringContainsString('different key', $result[0]['means'], 'the other explanation is named too');
	}//end testATamperedDocumentSaysWhatThatMeans()

	/**
	 * A rewritten signer field is never kept as a verified identity (signature-verification-portal REQ-DDSVP-002).
	 *
	 * The summary a verification record stores comes from summarise(): the
	 * forged signer is kept only as asserted, with neither guarantee set, and
	 * the public verdict built from that record says `tampered`.
	 *
	 * @return void
	 */
	public function testRewrittenSignerNotVerifiedIdentity(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed, $assertion] = $this->buildV2SignedArtifact(secret: $secret);
		$assertion['signer'] = 'Mallory (attacker)';
		$forged = (string)preg_replace(
			'/\/DocuDesk-Signature\([^)]*\)/',
			'/DocuDesk-Signature(' . base64_encode((string)json_encode($assertion)) . ')',
			$signed
		);

		$summary = $this->service->summarise(pdfContent: $forged);

		$this->assertSame(
			[[
				'status' => 'invalid',
				'level' => 'SES',
				'method' => 'native',
				'signerAsserted' => 'Mallory (attacker)',
				'signedAt' => '2026-07-23T12:00:00+00:00',
				'integrityVerified' => false,
				'identityBound' => false,
			]],
			$summary
		);

		$verdict = $this->service->verifyByRecord(record: ['signatures' => $summary, 'revoked' => false]);
		$this->assertSame('tampered', $verdict['status']);
		$this->assertFalse($verdict['signatures'][0]['identityBound']);

	}//end testRewrittenSignerNotVerifiedIdentity()

	/**
	 * A genuine v2 signature is summarised with both guarantees, because its MAC covers the signer fields.
	 *
	 * @return void
	 */
	public function testAGenuineSignatureIsSummarisedAsBound(): void {
		$secret = 'e2e-verification-secret';
		$this->mockConfig->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($secret): string {
				return $key === 'signing_verification_secret' ? $secret : $default;
			}
		);

		[$signed] = $this->buildV2SignedArtifact(secret: $secret);

		$summary = $this->service->summarise(pdfContent: $signed);

		$this->assertSame('verified', $summary[0]['status']);
		$this->assertSame('Alice', $summary[0]['signerAsserted']);
		$this->assertTrue($summary[0]['integrityVerified']);
		$this->assertTrue($summary[0]['identityBound']);
		$this->assertArrayNotHasKey('ip', $summary[0], 'the public summary carries no client address');
		$this->assertArrayNotHasKey('keyId', $summary[0], 'the public summary names no key');

	}//end testAGenuineSignatureIsSummarisedAsBound()

	/**
	 * An external `/Type /Sig` signature is summarised as unverifiable, with neither guarantee.
	 *
	 * @return void
	 */
	public function testAnExternalSignatureIsSummarisedUnverifiable(): void {
		$summary = $this->service->summarise(pdfContent: "%PDF-1.7\n1 0 obj\n<< /Type /Sig /Filter /Adobe.PPKLite >>\nendobj\n");

		$this->assertCount(1, $summary);
		$this->assertSame('unverifiable', $summary[0]['status']);
		$this->assertSame('external', $summary[0]['method']);
		$this->assertFalse($summary[0]['integrityVerified']);
		$this->assertFalse($summary[0]['identityBound']);

	}//end testAnExternalSignatureIsSummarisedUnverifiable()

	/**
	 * Run extractSignatures() over a document.
	 *
	 * @param string $signed The signed bytes.
	 *
	 * @return array<int, array<string, mixed>> The signatures.
	 */
	private function extract(string $signed): array {
		$method = (new ReflectionClass($this->service))->getMethod('extractSignatures');
		$method->setAccessible(true);
		return $method->invoke($this->service, $signed);
	}//end extract()

	/**
	 * The key id a given secret produces.
	 *
	 * @param string $secret The secret.
	 *
	 * @return string|null The key id.
	 */
	private function keyIdFor(string $secret): ?string {
		$method = (new ReflectionClass($this->service))->getMethod('keyId');
		$method->setAccessible(true);
		return $method->invoke($this->service, $secret);
	}//end keyIdFor()

}//end class
