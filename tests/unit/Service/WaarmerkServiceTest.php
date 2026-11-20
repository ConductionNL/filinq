<?php

/**
 * Unit tests for WaarmerkService and the verification round trip.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTime;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\SealedArtifact;
use OCA\Filinq\Service\Waarmerk\WaarmerkCertificate;
use OCA\Filinq\Service\Waarmerk\WaarmerkCms;
use OCA\Filinq\Service\Waarmerk\WaarmerkKeyResolver;
use OCA\Filinq\Service\Waarmerk\WaarmerkSettings;
use OCA\Filinq\Service\Waarmerk\WaarmerkStampComposer;
use OCA\Filinq\Service\Waarmerk\WaarmerkStore;
use OCA\Filinq\Service\Waarmerk\WaarmerkVerifier;
use OCA\Filinq\Service\WaarmerkService;
use OCA\Filinq\Tests\Unit\Service\Waarmerk\WaarmerkKeyPair;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Sealing, the write-once record, revocation and fail-closed verification.
 *
 * @covers \OCA\Filinq\Service\WaarmerkService
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkVerifier
 * @uses   \OCA\Filinq\Kernel\SealedArtifact
 * @uses   \OCA\Filinq\Service\Waarmerk\WaarmerkCertificate
 * @uses   \OCA\Filinq\Service\Waarmerk\WaarmerkCms
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkServiceTest extends TestCase {

	/**
	 * The organisation pair.
	 *
	 * @var array{cert: string, key: string}
	 */
	private array $pair;

	/**
	 * The certificate settings double.
	 *
	 * @var WaarmerkSettings&MockObject
	 */
	private WaarmerkSettings&MockObject $settings;

	/**
	 * The key resolver double.
	 *
	 * @var WaarmerkKeyResolver&MockObject
	 */
	private WaarmerkKeyResolver&MockObject $keys;

	/**
	 * The composer double.
	 *
	 * @var WaarmerkStampComposer&MockObject
	 */
	private WaarmerkStampComposer&MockObject $composer;

	/**
	 * The store double.
	 *
	 * @var WaarmerkStore&MockObject
	 */
	private WaarmerkStore&MockObject $store;

	/**
	 * The current moment.
	 *
	 * @var DateTime
	 */
	private DateTime $now;

	/**
	 * Lines logged, as text.
	 *
	 * @var array<int, string>
	 */
	private array $logged = [];

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pair = WaarmerkKeyPair::make();
		$this->settings = $this->createMock(WaarmerkSettings::class);
		$this->settings->method('certificate')->willReturnCallback(fn (): WaarmerkCertificate => WaarmerkCertificate::fromPem(pem: $this->pair['cert']));
		$this->keys = $this->createMock(WaarmerkKeyResolver::class);
		$this->composer = $this->createMock(WaarmerkStampComposer::class);
		$this->composer->method('compose')->willReturnCallback(fn (string $sourcePdf, array $stamp): string => $sourcePdf . "\n%STAMP " . $stamp['verificationCode']);
		$this->store = $this->createMock(WaarmerkStore::class);
		$this->now = new DateTime();
	}//end setUp()

	/**
	 * The clock double.
	 *
	 * @return ITimeFactory
	 */
	private function time(): ITimeFactory {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturnCallback(fn (): DateTime => clone $this->now);
		return $time;
	}//end time()

	/**
	 * The service.
	 *
	 * @return WaarmerkService
	 */
	private function service(): WaarmerkService {
		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('linkToRouteAbsolute')->willReturnCallback(fn (string $route, array $args): string => 'https://cloud.example/apps/filinq/verify/' . $args['code']);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('info')->willReturnCallback(function (string $message, array $context = []): void {
			$this->logged[] = $message . json_encode($context);
		});

		return new WaarmerkService(
			settings: $this->settings,
			keys: $this->keys,
			composer: $this->composer,
			cms: new WaarmerkCms(),
			store: $this->store,
			urls: $urls,
			time: $this->time(),
			logger: $logger
		);
	}//end service()

	/**
	 * The verifier over the same store double.
	 *
	 * @return WaarmerkVerifier
	 */
	private function verifier(): WaarmerkVerifier {
		return new WaarmerkVerifier(store: $this->store, cms: new WaarmerkCms(), time: $this->time());
	}//end verifier()

	/**
	 * A prepared seal of a small PDF.
	 *
	 * @return SealedArtifact
	 */
	private function sealed(): SealedArtifact {
		$this->keys->method('resolve')->willReturn($this->pair['key']);
		return $this->service()->prepare(pdf: "%PDF-1.4 besluit", documentName: 'besluit-gewaarmerkt.pdf', userId: 'alice');
	}//end sealed()

	/**
	 * The sealed artifact: hash over the sealed bytes, a CMS that verifies, an active write-once record.
	 *
	 * @return void
	 */
	public function testPrepareSealsTheArtifact(): void {
		$artifact = $this->sealed();
		$record = $artifact->record;

		$this->assertStringStartsWith('%PDF-1.4 besluit', $artifact->pdf);
		$this->assertStringEndsWith('%STAMP ' . $record['verificationCode'], $artifact->pdf);
		$this->assertSame(hash('sha256', $artifact->pdf), $record['documentHash']);
		$this->assertSame('waarmerk', $record['sealType']);
		$this->assertSame('active', $record['status']);
		$this->assertSame('alice', $record['sealedBy']);
		$this->assertSame($this->now->format(DATE_ATOM), $record['sealedAt']);
		$this->assertStringContainsString('O=Gemeente Demostad', $record['certificateSubject']);
		$this->assertTrue(WaarmerkService::isWellFormedCode(code: $record['verificationCode']));
		$this->assertNotNull((new WaarmerkCms())->verify(hash: $record['documentHash'], cmsBase64: $record['cmsSignature'], fingerprint: $record['certificateFingerprint']));
		$this->assertArrayNotHasKey('anonymizationLinkId', $record);
		foreach ($record as $value) {
			$this->assertStringNotContainsString('PRIVATE KEY', (string) $value);
		}
	}//end testPrepareSealsTheArtifact()

	/**
	 * Without a resolvable key: 503 and nothing composed, nothing stored.
	 *
	 * @return void
	 */
	public function testUnresolvableKeyProducesNoArtifact(): void {
		$this->keys->method('resolve')->willThrowException(new WaarmerkRefusedException(message: 'Key resolution failed', status: 503));
		$this->composer->expects($this->never())->method('compose');
		$this->store->expects($this->never())->method('create');

		try {
			$this->service()->prepare(pdf: '%PDF-1.4', documentName: 'x.pdf', userId: 'alice');
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(503, $e->getStatus());
		}
	}//end testUnresolvableKeyProducesNoArtifact()

	/**
	 * An expired certificate, no certificate, or an unknown seal type is refused before the key is touched.
	 *
	 * @return void
	 */
	public function testRefusedBeforeTheKey(): void {
		$this->keys->expects($this->never())->method('resolve');
		$this->now = new DateTime('@' . (WaarmerkCertificate::fromPem(pem: $this->pair['cert'])->validTo + 60));
		try {
			$this->service()->prepare(pdf: '%PDF-1.4', documentName: 'x.pdf', userId: 'alice');
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(422, $e->getStatus());
			$this->assertStringContainsString('expired', $e->getMessage());
		}

		try {
			$this->service()->prepare(pdf: '%PDF-1.4', documentName: 'x.pdf', userId: 'alice', sealType: 'qualified');
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(400, $e->getStatus());
		}

		$unconfigured = $this->createMock(WaarmerkSettings::class);
		$unconfigured->method('certificate')->willReturn(null);
		$this->settings = $unconfigured;
		try {
			$this->service()->prepare(pdf: '%PDF-1.4', documentName: 'x.pdf', userId: 'alice');
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(503, $e->getStatus());
		}
	}//end testRefusedBeforeTheKey()

	/**
	 * record() stores the prepared fields plus the file id, and logs the fingerprint, never key material.
	 *
	 * @return void
	 */
	public function testRecordStoresWriteOnce(): void {
		$artifact = $this->sealed();
		$this->store->expects($this->once())->method('create')
			->with(array_merge($artifact->record, ['documentFileId' => 42]))
			->willReturnCallback(fn (array $row): array => ['uuid' => 'w-1'] + $row);

		$stored = $this->service()->record(artifact: $artifact, documentFileId: 42);
		$this->assertSame('w-1', $stored['uuid']);
		$this->assertCount(1, $this->logged);
		$this->assertStringContainsString($artifact->record['certificateFingerprint'], $this->logged[0]);
		$this->assertStringNotContainsString('PRIVATE', $this->logged[0]);
	}//end testRecordStoresWriteOnce()

	/**
	 * A processing certificate carries its seal type and link id.
	 *
	 * @return void
	 */
	public function testProcessingCertificateSealType(): void {
		$this->keys->method('resolve')->willReturn($this->pair['key']);
		$record = $this->service()->prepare(pdf: '%PDF-1.4', documentName: 'c.pdf', userId: 'bob', sealType: 'processing-certificate', anonymizationLinkId: 'link-1')->record;
		$this->assertSame('processing-certificate', $record['sealType']);
		$this->assertSame('link-1', $record['anonymizationLinkId']);
	}//end testProcessingCertificateSealType()

	/**
	 * The verifier: valid for the sealed bytes, hash-mismatch after one flipped byte, never valid when revoked.
	 *
	 * @return void
	 */
	public function testVerificationRoundTripTamperAndRevocation(): void {
		$artifact = $this->sealed();
		$seal = ['uuid' => 'w-1', 'documentFileId' => 42] + $artifact->record;
		$code = $seal['verificationCode'];
		$this->store->method('findByCode')->willReturnCallback(function (string $asked) use (&$seal, $code): ?array {
			return ($asked === $code) ? $seal : null;
		});

		$valid = $this->verifier()->verify(code: strtoupper($code), sha256: hash('sha256', $artifact->pdf));
		$this->assertSame('valid', $valid['status']);
		$this->assertSame('Gemeente Demostad', $valid['organisation']);
		$this->assertSame('besluit-gewaarmerkt.pdf', $valid['documentName']);
		$this->assertFalse($valid['certificateExpired']);

		$tampered = $artifact->pdf;
		$tampered[3] = ($tampered[3] === 'X') ? 'Y' : 'X';
		$this->assertSame('hash-mismatch', $this->verifier()->verify(code: $code, sha256: hash('sha256', $tampered))['status']);
		$this->assertSame('hash-mismatch', $this->verifier()->verify(code: $code, sha256: 'not-a-hash')['status']);

		// Revocation dominates a matching hash.
		$seal = array_merge($seal, ['status' => 'revoked', 'revokedAt' => '2026-11-15T09:00:00+00:00', 'revocationReason' => 'document withdrawn after objection']);
		$revoked = $this->verifier()->verify(code: $code, sha256: hash('sha256', $artifact->pdf));
		$this->assertSame('revoked', $revoked['status']);
		$this->assertSame('document withdrawn after objection', $revoked['revocationReason']);
		$this->assertNull($revoked['organisation']);
		$this->assertSame('revoked', $this->verifier()->page(code: $code)['status']);
	}//end testVerificationRoundTripTamperAndRevocation()

	/**
	 * A stripped or foreign CMS answers unknown, never valid; expiry after sealing is an advisory.
	 *
	 * @return void
	 */
	public function testBrokenCmsIsUnknownAndExpiryIsAnAdvisory(): void {
		$artifact = $this->sealed();
		$hash = hash('sha256', $artifact->pdf);
		$seal = ['uuid' => 'w-1'] + $artifact->record;
		$code = $seal['verificationCode'];
		$this->store->method('findByCode')->willReturnCallback(function () use (&$seal): array {
			return $seal;
		});

		$seal['cmsSignature'] = '';
		$this->assertSame('unknown', $this->verifier()->verify(code: $code, sha256: $hash)['status']);

		$other = WaarmerkKeyPair::make(organisation: 'Gemeente Elders');
		$seal['cmsSignature'] = (new WaarmerkCms())->sign(hash: $hash, certificatePem: $other['cert'], keyPem: $other['key']);
		$this->assertSame('unknown', $this->verifier()->verify(code: $code, sha256: $hash)['status']);

		$seal['cmsSignature'] = $artifact->record['cmsSignature'];
		$this->now = new DateTime('@' . (WaarmerkCertificate::fromPem(pem: $this->pair['cert'])->validTo + 60));
		$late = $this->verifier()->verify(code: $code, sha256: $hash);
		$this->assertSame('valid', $late['status']);
		$this->assertTrue($late['certificateExpired']);
	}//end testBrokenCmsIsUnknownAndExpiryIsAnAdvisory()

	/**
	 * Unknown and malformed codes read the same as other non-valid answers: same keys, no data.
	 *
	 * @return void
	 */
	public function testNoExistenceOracle(): void {
		$this->store->method('findByCode')->willReturn(null);
		$this->keys->method('resolve')->willReturn($this->pair['key']);
		$plausible = '3f2b1c4d-5e6f-4a7b-8c9d-0e1f2a3b4c5d-' . substr(hash('sha256', '3f2b1c4d-5e6f-4a7b-8c9d-0e1f2a3b4c5d'), 0, 4);
		$this->assertTrue(WaarmerkService::isWellFormedCode(code: $plausible));

		$unknown = $this->verifier()->verify(code: $plausible, sha256: str_repeat('0', 64));
		$malformed = $this->verifier()->verify(code: 'abc', sha256: str_repeat('0', 64));
		$this->assertSame('unknown', $unknown['status']);
		$this->assertSame('unknown', $malformed['status']);
		$this->assertSame(array_keys($unknown), array_keys($malformed));
		unset($unknown['checkedAt'], $malformed['checkedAt']);
		$this->assertSame($unknown, $malformed);
		$this->assertSame([null], array_values(array_unique(array_slice($unknown, 1))));

		// A mismatch on an existing code has the very same shape.
		$artifact = $this->service()->prepare(pdf: '%PDF-1.4', documentName: 'x.pdf', userId: 'alice');
		$store = $this->createMock(WaarmerkStore::class);
		$store->method('findByCode')->willReturn(['uuid' => 'w'] + $artifact->record);
		$mismatch = (new WaarmerkVerifier(store: $store, cms: new WaarmerkCms(), time: $this->time()))->verify(code: $artifact->record['verificationCode'], sha256: str_repeat('0', 64));
		unset($mismatch['checkedAt']);
		$this->assertSame(array_keys($unknown), array_keys($mismatch));
		$this->assertSame(array_slice($unknown, 1), array_slice($mismatch, 1));

		// The check part is checked.
		$this->assertFalse(WaarmerkService::isWellFormedCode(code: substr($plausible, 0, -4) . 'zzzz'));
		$this->assertFalse(WaarmerkService::isWellFormedCode(code: substr($plausible, 0, -1) . (($plausible[-1] === '0') ? '1' : '0')));
	}//end testNoExistenceOracle()

	/**
	 * Revocation: 404 unknown, 403 for neither admin nor sealer (and nothing written), 400 without reason.
	 *
	 * @return void
	 */
	public function testRevokeGuard(): void {
		$seal = ['uuid' => 'w-1', 'sealedBy' => 'alice', 'status' => 'active'];
		$this->store->method('find')->willReturnCallback(fn (string $uuid): ?array => ($uuid === 'w-1') ? $seal : null);
		$this->store->expects($this->once())->method('revoke')
			->with($seal, $this->callback(fn (array $r): bool => $r['status'] === 'revoked' && $r['revokedBy'] === 'root' && $r['revocationReason'] === 'withdrawn' && $r['revokedAt'] === $this->now->format(DATE_ATOM)))
			->willReturn(['status' => 'revoked'] + $seal);

		foreach ([['nope', 'alice', false, 'r', 404], ['w-1', 'mallory', false, 'r', 403], ['w-1', 'alice', false, '  ', 400]] as [$id, $user, $admin, $reason, $status]) {
			try {
				$this->service()->revoke(uuid: $id, userId: $user, isAdmin: $admin, reason: $reason);
				$this->fail($status . ': expected a refusal');
			} catch (WaarmerkRefusedException $e) {
				$this->assertSame($status, $e->getStatus());
			}
		}

		$this->assertSame('revoked', $this->service()->revoke(uuid: 'w-1', userId: 'root', isAdmin: true, reason: ' withdrawn ')['status']);
	}//end testRevokeGuard()

	/**
	 * Listing is scoped to the sealer unless admin; show() answers null for another user's seal.
	 *
	 * @return void
	 */
	public function testListAndShowScope(): void {
		$this->store->expects($this->exactly(2))->method('search')
			->willReturnCallback(fn (array $filters): array => [$filters]);
		$this->assertSame([['sealedBy' => 'alice', 'documentFileId' => 7]], $this->service()->list(userId: 'alice', isAdmin: false, documentFileId: 7));
		$this->assertSame([[]], $this->service()->list(userId: 'root', isAdmin: true));

		$this->store->method('find')->willReturn(['uuid' => 'w-1', 'sealedBy' => 'alice']);
		$this->assertNull($this->service()->show(uuid: 'w-1', userId: 'bob', isAdmin: false));
		$this->assertSame('w-1', $this->service()->show(uuid: 'w-1', userId: 'alice', isAdmin: false)['uuid']);
		$this->assertSame('w-1', $this->service()->show(uuid: 'w-1', userId: 'root', isAdmin: true)['uuid']);
	}//end testListAndShowScope()
}//end class
