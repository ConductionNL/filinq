<?php

/**
 * Unit tests for ConsentBasisBuilder.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\ConsentBasisBuilder;
use OCA\Filinq\Service\Signing\GuardianAgePolicy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The guardian consent basis recorded for under-age signers.
 *
 * @covers \OCA\Filinq\Service\Signing\ConsentBasisBuilder
 * @uses   \OCA\Filinq\Service\Signing\GuardianAgePolicy
 */
class ConsentBasisBuilderTest extends TestCase {

	/**
	 * Build the builder with the default age (16).
	 *
	 * @return ConsentBasisBuilder
	 */
	private function builder(): ConsentBasisBuilder {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getFeatureToggles')->willReturn([]);
		return new ConsentBasisBuilder(agePolicy: new GuardianAgePolicy(settingsService: $settings));
	}//end builder()

	/**
	 * Adults, guardians and signers without a birth date get no entry.
	 *
	 * @return void
	 */
	public function testOnlyUnderAgeSignersGetAnEntry(): void {
		$signers = [
			'adult' => ['birthDate' => '1990-01-01', 'signedAt' => '2026-01-01T00:00:00+00:00'],
			'nodob' => ['signedAt' => '2026-01-01T00:00:00+00:00'],
			'g1'    => ['role' => 'guardian', 'birthDate' => '2015-01-01'],
		];

		$this->assertSame([], $this->builder()->build(request: [], signers: $signers));
	}//end testOnlyUnderAgeSignersGetAnEntry()

	/**
	 * A co-signing guardian gives a co-signature basis; only acting guardians for this signer count.
	 *
	 * @return void
	 */
	public function testCoSignatureBasis(): void {
		$signers = [
			'kid' => ['birthDate' => '2015-05-05', 'signedAt' => '2026-03-01T10:00:00+00:00', 'displayName' => 'Kim'],
			'mum' => [
				'role' => 'guardian', 'guardianForSignerId' => 'kid', 'status' => 'SIGNED',
				'displayName' => 'Mum', 'signedAt' => '2026-03-01T09:00:00+00:00',
				'actingIdentity' => ['method' => 'digid'], 'guardianRef' => 'ref-1',
			],
			'dad' => ['role' => 'guardian', 'guardianForSignerId' => 'kid', 'status' => 'PENDING'],
			'aunt' => ['role' => 'guardian', 'guardianForSignerId' => 'other', 'status' => 'SIGNED'],
		];

		$this->assertSame(
			[
				[
					'signerId' => 'kid',
					'displayName' => 'Kim',
					'guardianConsentAge' => 16,
					'evaluatedAt' => '2026-03-01T10:00:00+00:00',
					'basis' => 'guardian-co-signature',
					'guardians' => [
						[
							'signerId' => 'mum',
							'displayName' => 'Mum',
							'guardianAct' => 'co-sign',
							'actedAt' => '2026-03-01T09:00:00+00:00',
							'identity' => ['method' => 'digid'],
							'guardianRef' => 'ref-1',
						],
					],
				],
			],
			$this->builder()->build(request: [], signers: $signers)
		);
	}//end testCoSignatureBasis()

	/**
	 * Consent-only guardians give a consent basis with the statement; the request can raise the age.
	 *
	 * @return void
	 */
	public function testConsentBasisAndRaisedAge(): void {
		$signers = [
			7 => ['birthDate' => '2007-01-01', 'signedAt' => '2026-01-01T00:00:00+00:00'],
			8 => ['role' => 'guardian', 'guardianForSignerId' => '7', 'status' => 'SIGNED', 'guardianAct' => 'consent', 'consentStatement' => 'I agree'],
		];

		$basis = $this->builder()->build(request: ['guardianConsentAge' => 21], signers: $signers);

		$this->assertCount(1, $basis);
		$this->assertSame('7', $basis[0]['signerId']);
		$this->assertSame(21, $basis[0]['guardianConsentAge']);
		$this->assertSame('guardian-consent', $basis[0]['basis']);
		$this->assertSame(
			[['signerId' => '8', 'displayName' => '', 'guardianAct' => 'consent', 'actedAt' => '', 'identity' => [], 'consentStatement' => 'I agree']],
			$basis[0]['guardians']
		);
	}//end testConsentBasisAndRaisedAge()

	/**
	 * An under-age signer without an acting guardian blocks completion with 409.
	 *
	 * @return void
	 */
	public function testUnderAgeWithoutGuardianIsRefused(): void {
		$signers = ['kid' => ['birthDate' => '2015-05-05', 'signedAt' => '2026-03-01T10:00:00+00:00']];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(409);
		$this->expectExceptionMessage('Signer kid signed under 16 and no guardian has acted for them, so the request cannot complete');

		$this->builder()->build(request: [], signers: $signers);
	}//end testUnderAgeWithoutGuardianIsRefused()
}//end class
