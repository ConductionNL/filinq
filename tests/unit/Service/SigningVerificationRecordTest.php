<?php

/**
 * Unit tests for SigningVerificationService::verifyByRecord().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-4.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\SigningVerificationService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The public verdict of a stored verification record: fail-closed, tri-state, honest about identity.
 *
 * @covers \OCA\Filinq\Service\SigningVerificationService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SigningVerificationRecordTest extends TestCase {

	/**
	 * The service under test; the record path touches neither files nor config.
	 *
	 * @var SigningVerificationService
	 */
	private SigningVerificationService $service;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->expects($this->never())->method('getUserFolder');
		$config = $this->createMock(IAppConfig::class);
		$config->expects($this->never())->method('getValueString');

		$this->service = new SigningVerificationService(rootFolder: $rootFolder, config: $config);

	}//end setUp()

	/**
	 * A record with one signature, merged with the given fields.
	 *
	 * @param array<string, mixed> $signature Fields of the one signature.
	 * @param array<string, mixed> $record    Fields of the record.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function record(array $signature = [], array $record = []): array {
		return array_merge(
			[
				'token' => str_repeat('ab', 16),
				'fileRef' => '11:signed:0011223344556677',
				'fileName' => 'besluit.pdf',
				'contentHash' => str_repeat('c', 64),
				'signatures' => [array_merge(
					[
						'status' => 'verified',
						'level' => 'AES',
						'method' => 'native',
						'signerAsserted' => 'Alice',
						'signedAt' => '2026-07-01T09:00:00+00:00',
						'integrityVerified' => true,
						'identityBound' => true,
					],
					$signature
				)],
				'revoked' => false,
				'createdAt' => '2026-07-01T09:00:01+00:00',
			],
			$record
		);
	}//end record()

	/**
	 * A verified record answers verified with both guarantees, the file name and no file reference.
	 *
	 * @return void
	 */
	public function testAVerifiedRecord(): void {
		$verdict = $this->service->verifyByRecord(record: $this->record());

		$this->assertSame(
			[
				'status' => 'verified',
				'fileName' => 'besluit.pdf',
				'contentHash' => str_repeat('c', 64),
				'recordedAt' => '2026-07-01T09:00:01+00:00',
				'signatures' => [[
					'status' => 'verified',
					'level' => 'AES',
					'method' => 'native',
					'signerAsserted' => 'Alice',
					'signedAt' => '2026-07-01T09:00:00+00:00',
					'integrityVerified' => true,
					'identityBound' => true,
				]],
				'audit' => ['signatureCount' => 1, 'verifiedCount' => 1],
				'seal' => null,
			],
			$verdict
		);
		$this->assertStringNotContainsString('11:signed', (string)json_encode($verdict), 'the file reference is never shown');

	}//end testAVerifiedRecord()

	/**
	 * The unknown verdict has exactly the keys of a real one, so the two cannot be told apart by shape.
	 *
	 * @return void
	 */
	public function testTheUnknownVerdictHasTheSameShape(): void {
		$unknown = SigningVerificationService::unknownVerdict();

		$this->assertSame(array_keys($this->service->verifyByRecord(record: $this->record())), array_keys($unknown));
		$this->assertSame('unknown', $unknown['status']);
		$this->assertSame([], $unknown['signatures']);

	}//end testTheUnknownVerdictHasTheSameShape()

	/**
	 * A revoked record, or one without a signature list, answers unknown.
	 *
	 * @return void
	 */
	public function testARevokedOrBrokenRecordIsUnknown(): void {
		$unknown = SigningVerificationService::unknownVerdict();

		$this->assertSame($unknown, $this->service->verifyByRecord(record: $this->record(record: ['revoked' => true])));
		$this->assertSame($unknown, $this->service->verifyByRecord(record: $this->record(record: ['revoked' => 'yes'])));
		$this->assertSame($unknown, $this->service->verifyByRecord(record: $this->record(record: ['signatures' => 'verified'])));
		$this->assertSame($unknown, $this->service->verifyByRecord(record: ['token' => str_repeat('ab', 16)]));

	}//end testARevokedOrBrokenRecordIsUnknown()

	/**
	 * A record that claims a bound signer on a signature that did not verify is not believed.
	 *
	 * @return void
	 */
	public function testAnUnverifiedSignatureIsNeverABoundIdentity(): void {
		foreach (['invalid', 'unverifiable'] as $status) {
			$verdict = $this->service->verifyByRecord(
				record: $this->record(signature: ['status' => $status, 'integrityVerified' => true, 'identityBound' => true])
			);

			$this->assertFalse($verdict['signatures'][0]['integrityVerified'], $status);
			$this->assertFalse($verdict['signatures'][0]['identityBound'], $status);
			$this->assertSame('Alice', $verdict['signatures'][0]['signerAsserted'], 'the signer stays, as asserted');
		}

	}//end testAnUnverifiedSignatureIsNeverABoundIdentity()

	/**
	 * Integrity can be verified while the signer is not bound; the flag is read, not assumed.
	 *
	 * @return void
	 */
	public function testIntegrityWithoutABoundSigner(): void {
		$verdict = $this->service->verifyByRecord(record: $this->record(signature: ['identityBound' => false]));

		$this->assertSame('verified', $verdict['status']);
		$this->assertTrue($verdict['signatures'][0]['integrityVerified']);
		$this->assertFalse($verdict['signatures'][0]['identityBound']);

		$truthy = $this->service->verifyByRecord(record: $this->record(signature: ['identityBound' => 1, 'integrityVerified' => 'true']));
		$this->assertFalse($truthy['signatures'][0]['integrityVerified'], 'only a real true counts');
		$this->assertFalse($truthy['signatures'][0]['identityBound']);

	}//end testIntegrityWithoutABoundSigner()

	/**
	 * The status is tri-state: an unknown stored status reads unverifiable, and the verdict follows the signatures.
	 *
	 * @return void
	 */
	public function testTriStateStatusesAndVerdicts(): void {
		$odd = $this->service->verifyByRecord(record: $this->record(signature: ['status' => 'valid']));
		$this->assertSame('unverifiable', $odd['signatures'][0]['status']);
		$this->assertSame('unverifiable', $odd['status']);

		$this->assertSame('tampered', $this->service->verifyByRecord(record: $this->record(signature: ['status' => 'invalid']))['status']);

		$mixed = $this->record();
		$mixed['signatures'][] = ['status' => 'unverifiable', 'method' => 'external'];
		$mixed['signatures'][] = 'not a signature';
		$verdict = $this->service->verifyByRecord(record: $mixed);
		$this->assertSame('mixed', $verdict['status']);
		$this->assertSame(['signatureCount' => 2, 'verifiedCount' => 1], $verdict['audit']);

		$this->assertSame('unverifiable', $this->service->verifyByRecord(record: $this->record(record: ['signatures' => []]))['status']);

	}//end testTriStateStatusesAndVerdicts()

	/**
	 * A named seal is reported as not checkable here, never as sealed.
	 *
	 * @return void
	 */
	public function testANamedSealIsNotClaimed(): void {
		$verdict = $this->service->verifyByRecord(record: $this->record(record: ['waarmerkRef' => 'wm-1']));
		$this->assertSame(['status' => 'unverifiable', 'reason' => 'seal-check-unavailable'], $verdict['seal']);

		$this->assertNull($this->service->verifyByRecord(record: $this->record(record: ['waarmerkRef' => '']))['seal']);

	}//end testANamedSealIsNotClaimed()
}//end class
