<?php

/**
 * Unit tests for GuardianConsentGuard
 *
 * A signer under the guardian consent age signs only with a guardian beside
 * them; the guardian acts through the same identity rails; the completed
 * request records both signers and the consent basis.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the guardian consent rules on a signing request.
 *
 * The signer records live in an in-memory store the ObjectService double
 * reads from, so each test states the request it is about and nothing else.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GuardianConsentGuardTest extends TestCase {

	/**
	 * The moment every test acts at.
	 *
	 * @var string
	 */
	private const NOW = '2026-09-27T12:00:00+00:00';

	/**
	 * A birth date 14 years before NOW.
	 *
	 * @var string
	 */
	private const AGED_14 = '2012-06-01';

	/**
	 * A birth date 15 years before NOW.
	 *
	 * @var string
	 */
	private const AGED_15 = '2011-06-01';

	/**
	 * A birth date 17 years before NOW.
	 *
	 * @var string
	 */
	private const AGED_17 = '2009-06-01';

	/**
	 * Signer records by id, as the ObjectService double serves them.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $records = [];

	/**
	 * The feature toggles SettingsService reports.
	 *
	 * @var array<string, mixed>
	 */
	private array $toggles = [];

	/**
	 * @var GuardianConsentGuard
	 */
	private GuardianConsentGuard $guard;

	/**
	 * Build the guard over an in-memory signer store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['find'])
			->getMock();
		$objectService->method('find')->willReturnCallback(
			function (string $id = ''): ?array {
				return ($this->records[$id] ?? null);
			}
		);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objectService);
		$settings->method('resolveSignerRecordBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);
		$settings->method('getFeatureToggles')->willReturnCallback(
			function (): array {
				return $this->toggles;
			}
		);

		$this->guard = new GuardianConsentGuard(settingsService: $settings);

	}//end setUp()

	/**
	 * The moment every test acts at.
	 *
	 * @return DateTimeImmutable
	 */
	private function now(): DateTimeImmutable {
		return new DateTimeImmutable(self::NOW);

	}//end now()

	/**
	 * A request carrying the given signer ids.
	 *
	 * @param list<string> $signerIds The signer record ids on the request.
	 *
	 * @return array<string, mixed>
	 */
	private function request(array $signerIds): array {
		return [
			'id' => 'req-1',
			'status' => 'PENDING',
			'signatureLevel' => 'SES',
			'guardianConsentAge' => 16,
			'signerIds' => $signerIds,
		];

	}//end request()

	/**
	 * Store a learner signer record.
	 *
	 * @param string $birthDate The learner's birth date.
	 * @param array<string, mixed> $extra Fields to add or override.
	 *
	 * @return array<string, mixed> The stored record.
	 */
	private function learner(string $birthDate, array $extra = []): array {
		$record = array_merge(
			[
				'id' => 'learner-1',
				'signingRequestId' => 'req-1',
				'userId' => 'sanne',
				'displayName' => 'Sanne de Vries',
				'email' => 'sanne@school.example',
				'status' => 'PENDING',
				'role' => 'signer',
				'birthDate' => $birthDate,
			],
			$extra
		);
		$this->records[(string)$record['id']] = $record;

		return $record;

	}//end learner()

	/**
	 * Store a guardian signer record pointing at the learner.
	 *
	 * @param array<string, mixed> $extra Fields to add or override.
	 *
	 * @return array<string, mixed> The stored record.
	 */
	private function guardian(array $extra = []): array {
		$record = array_merge(
			[
				'id' => 'guardian-1',
				'signingRequestId' => 'req-1',
				'userId' => 'mark',
				'displayName' => 'Mark de Vries',
				'email' => 'mark@home.example',
				'status' => 'PENDING',
				'role' => 'guardian',
				'guardianForSignerId' => 'learner-1',
				'guardianAct' => 'co-sign',
			],
			$extra
		);
		$this->records[(string)$record['id']] = $record;

		return $record;

	}//end guardian()

	/**
	 * With no setting, the age of consent is 16.
	 *
	 * @return void
	 */
	public function testTheAgeDefaultsToSixteen(): void {
		$this->assertSame(16, $this->guard->appliedAge(data: []));

	}//end testTheAgeDefaultsToSixteen()

	/**
	 * A setting nobody can use falls back to 16 rather than switching the rule off.
	 *
	 * @return void
	 */
	public function testAnUnusableSettingFallsBackToSixteen(): void {
		foreach (['', 'abc', '0', '-3'] as $value) {
			$this->toggles = ['signing_guardian_consent_age' => $value];
			$this->assertSame(16, $this->guard->appliedAge(data: []), 'Setting "' . $value . '" must fall back to 16.');
		}

	}//end testAnUnusableSettingFallsBackToSixteen()

	/**
	 * An administrator can set a different age.
	 *
	 * @return void
	 */
	public function testTheSettingSetsTheAge(): void {
		$this->toggles = ['signing_guardian_consent_age' => '18'];

		$this->assertSame(18, $this->guard->appliedAge(data: []));

	}//end testTheSettingSetsTheAge()

	/**
	 * A request may raise the age for its own signers and never lower it.
	 *
	 * @return void
	 */
	public function testARequestCanRaiseTheAgeButNeverLowerIt(): void {
		$this->assertSame(16, $this->guard->appliedAge(data: ['guardianConsentAge' => 12]));
		$this->assertSame(18, $this->guard->appliedAge(data: ['guardianConsentAge' => 18]));
		$this->assertSame(18, $this->guard->appliedAge(data: ['guardianConsentAge' => '18']));

	}//end testARequestCanRaiseTheAgeButNeverLowerIt()

	/**
	 * Assert that preparing these signer entries is refused with a 400.
	 *
	 * @param array<int, array<string, mixed>> $signers The signer entries.
	 * @param string $because What the refusal must mention.
	 *
	 * @return void
	 */
	private function assertCreationRefused(array $signers, string $because): void {
		try {
			$this->guard->prepareSigners(signers: $signers, age: 16, now: $this->now());
		} catch (InvalidArgumentException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertStringContainsStringIgnoringCase($because, $e->getMessage());
			return;
		}

		$this->fail('Creation should have been refused: ' . $because);

	}//end assertCreationRefused()

	/**
	 * A guardian entry must point at another signer on the same request.
	 *
	 * @return void
	 */
	public function testCreationRefusesAGuardianForNobody(): void {
		$this->assertCreationRefused(
			signers: [
				['userId' => 'sanne', 'birthDate' => self::AGED_14],
				['userId' => 'mark', 'role' => 'guardian', 'guardianFor' => 'ghost'],
			],
			because: 'guardian'
		);

	}//end testCreationRefusesAGuardianForNobody()

	/**
	 * A guardian cannot be their own guardian.
	 *
	 * @return void
	 */
	public function testCreationRefusesAGuardianForThemselves(): void {
		$this->assertCreationRefused(
			signers: [
				['userId' => 'mark', 'role' => 'guardian', 'guardianFor' => 'mark'],
			],
			because: 'guardian'
		);

	}//end testCreationRefusesAGuardianForThemselves()

	/**
	 * A guardian stands beside a signer, not beside another guardian.
	 *
	 * @return void
	 */
	public function testCreationRefusesAGuardianForAnotherGuardian(): void {
		$this->assertCreationRefused(
			signers: [
				['userId' => 'sanne', 'birthDate' => self::AGED_14],
				['userId' => 'mark', 'role' => 'guardian', 'guardianFor' => 'sanne'],
				['userId' => 'ilse', 'role' => 'guardian', 'guardianFor' => 'mark'],
			],
			because: 'guardian'
		);

	}//end testCreationRefusesAGuardianForAnotherGuardian()

	/**
	 * A consent act without the statement the guardian agrees to is refused.
	 *
	 * @return void
	 */
	public function testCreationRefusesAConsentActWithoutAStatement(): void {
		$this->assertCreationRefused(
			signers: [
				['userId' => 'sanne', 'birthDate' => self::AGED_14],
				['userId' => 'mark', 'role' => 'guardian', 'guardianFor' => 'sanne', 'guardianAct' => 'consent'],
			],
			because: 'statement'
		);

	}//end testCreationRefusesAConsentActWithoutAStatement()

	/**
	 * A birth date that is not a real ISO date is refused.
	 *
	 * @return void
	 */
	public function testCreationRefusesAMalformedBirthDate(): void {
		foreach (['2012-02-30', '14 years', '01-06-2012'] as $birthDate) {
			$this->assertCreationRefused(
				signers: [['userId' => 'sanne', 'birthDate' => $birthDate]],
				because: 'birth date'
			);
		}

	}//end testCreationRefusesAMalformedBirthDate()

	/**
	 * A request that names a minor without a guardian is refused at creation.
	 *
	 * @return void
	 */
	public function testCreationRefusesAMinorWithoutAGuardian(): void {
		$this->assertCreationRefused(
			signers: [['userId' => 'sanne', 'birthDate' => self::AGED_14]],
			because: 'guardian'
		);

	}//end testCreationRefusesAMinorWithoutAGuardian()

	/**
	 * A valid request links each guardian to their minor and keeps the fields.
	 *
	 * @return void
	 */
	public function testCreationLinksTheGuardianToTheMinor(): void {
		$prepared = $this->guard->prepareSigners(
			signers: [
				['userId' => 'sanne', 'email' => 'sanne@school.example', 'birthDate' => self::AGED_14],
				[
					'userId' => '',
					'email' => 'mark@home.example',
					'role' => 'guardian',
					'guardianFor' => 'SANNE@school.example',
					'guardianRef' => 'learniq/guardian/0001',
				],
				[
					'userId' => 'ilse',
					'role' => 'guardian',
					'guardianFor' => 'sanne',
					'guardianAct' => 'consent',
					'consentStatement' => 'Ik geef toestemming dat Sanne deze overeenkomst ondertekent.',
				],
			],
			age: 16,
			now: $this->now()
		);

		$this->assertSame([1 => 0, 2 => 0], $prepared['links']);
		$this->assertSame(self::AGED_14, $prepared['fields'][0]['birthDate']);
		$this->assertSame('signer', $prepared['fields'][0]['role']);
		$this->assertSame('guardian', $prepared['fields'][1]['role']);
		$this->assertSame('co-sign', $prepared['fields'][1]['guardianAct']);
		$this->assertSame('learniq/guardian/0001', $prepared['fields'][1]['guardianRef']);
		$this->assertSame('consent', $prepared['fields'][2]['guardianAct']);
		$this->assertSame(
			'Ik geef toestemming dat Sanne deze overeenkomst ondertekent.',
			$prepared['fields'][2]['consentStatement']
		);

	}//end testCreationLinksTheGuardianToTheMinor()

	/**
	 * Entries without any guardian field come back untouched and unlinked.
	 *
	 * @return void
	 */
	public function testCreationLeavesAnOrdinaryRequestAlone(): void {
		$prepared = $this->guard->prepareSigners(
			signers: [['userId' => 'alice'], ['userId' => 'bob']],
			age: 16,
			now: $this->now()
		);

		$this->assertSame([], $prepared['links']);
		$this->assertSame([], $prepared['fields'][0]);
		$this->assertSame([], $prepared['fields'][1]);

	}//end testCreationLeavesAnOrdinaryRequestAlone()

	/**
	 * A 14-year-old with no guardian on the request cannot sign.
	 *
	 * @return void
	 */
	public function testAMinorWithNoGuardianCannotSign(): void {
		$learner = $this->learner(birthDate: self::AGED_14);

		try {
			$this->guard->guardSigningAct(
				requestId: 'req-1',
				request: $this->request(signerIds: ['learner-1']),
				signer: $learner,
				verifiedActor: null,
				now: $this->now()
			);
		} catch (RuntimeException $e) {
			$this->assertSame(403, $e->getCode());
			$this->assertStringContainsString('guardian', $e->getMessage());
			return;
		}

		$this->fail('A minor without a guardian must not be able to sign.');

	}//end testAMinorWithNoGuardianCannotSign()

	/**
	 * With a guardian on the request, the minor signs and the act is recorded.
	 *
	 * @return void
	 */
	public function testAMinorWithAGuardianMaySignAndTheActIsRecorded(): void {
		$learner = $this->learner(birthDate: self::AGED_14);
		$this->guardian();

		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signer: $learner,
			verifiedActor: null,
			now: $this->now()
		);

		$this->assertSame(
			['provider' => 'nextcloud-session', 'assurance' => 'low', 'authenticatedAt' => self::NOW],
			$result['record']['actingIdentity']
		);
		$this->assertTrue($result['audit']['guardianConsent']['required']);
		$this->assertSame(16, $result['audit']['guardianConsent']['guardianConsentAge']);
		$this->assertSame(['guardian-1'], $result['audit']['guardianConsent']['guardianSignerIds']);

	}//end testAMinorWithAGuardianMaySignAndTheActIsRecorded()

	/**
	 * A signer who has reached the age signs alone and nothing is added.
	 *
	 * @return void
	 */
	public function testASignerWhoReachedTheAgeSignsAlone(): void {
		$learner = $this->learner(birthDate: self::AGED_17);

		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['learner-1']),
			signer: $learner,
			verifiedActor: null,
			now: $this->now()
		);

		$this->assertSame(['record' => [], 'audit' => []], $result);

	}//end testASignerWhoReachedTheAgeSignsAlone()

	/**
	 * An ordinary signer never costs a lookup.
	 *
	 * @return void
	 */
	public function testAnOrdinarySignerIsOutsideTheRule(): void {
		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['alice-1']),
			signer: ['id' => 'alice-1', 'signingRequestId' => 'req-1', 'userId' => 'alice', 'status' => 'PENDING'],
			verifiedActor: null,
			now: $this->now()
		);

		$this->assertSame(['record' => [], 'audit' => []], $result);

	}//end testAnOrdinarySignerIsOutsideTheRule()

	/**
	 * Assert that this guardian act is refused with a 403.
	 *
	 * @param array<string, mixed> $guardian The guardian record.
	 *
	 * @return void
	 */
	private function assertGuardianActRefused(array $guardian): void {
		try {
			$this->guard->guardSigningAct(
				requestId: 'req-1',
				request: $this->request(signerIds: ['learner-1', 'guardian-1']),
				signer: $guardian,
				verifiedActor: null,
				now: $this->now()
			);
		} catch (RuntimeException $e) {
			$this->assertSame(403, $e->getCode());
			return;
		}

		$this->fail('The guardian act should have been refused.');

	}//end assertGuardianActRefused()

	/**
	 * A guardian who is under the age cannot act as a guardian.
	 *
	 * @return void
	 */
	public function testAGuardianUnderTheAgeIsRefused(): void {
		$this->learner(birthDate: self::AGED_14);

		$this->assertGuardianActRefused(guardian: $this->guardian(extra: ['birthDate' => self::AGED_15]));

	}//end testAGuardianUnderTheAgeIsRefused()

	/**
	 * A guardian who is the minor, by user id or by email, is refused.
	 *
	 * @return void
	 */
	public function testAGuardianWhoIsTheMinorIsRefused(): void {
		$this->learner(birthDate: self::AGED_14);
		$this->assertGuardianActRefused(guardian: $this->guardian(extra: ['userId' => 'sanne']));

		$this->assertGuardianActRefused(
			guardian: $this->guardian(extra: ['userId' => '', 'email' => 'SANNE@school.example'])
		);

	}//end testAGuardianWhoIsTheMinorIsRefused()

	/**
	 * A guardian pointing at a signer of another request is refused.
	 *
	 * @return void
	 */
	public function testAGuardianForASignerOutsideTheRequestIsRefused(): void {
		$this->learner(birthDate: self::AGED_14, extra: ['signingRequestId' => 'req-other']);

		$this->assertGuardianActRefused(guardian: $this->guardian());

	}//end testAGuardianForASignerOutsideTheRequestIsRefused()

	/**
	 * A guardian who acts is recorded with the identity the rails resolved.
	 *
	 * @return void
	 */
	public function testAGuardianActIsRecorded(): void {
		$this->learner(birthDate: self::AGED_14);

		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signer: $this->guardian(),
			verifiedActor: null,
			now: $this->now()
		);

		$this->assertSame('nextcloud-session', $result['record']['actingIdentity']['provider']);
		$this->assertSame(
			['role' => 'guardian', 'guardianAct' => 'co-sign', 'guardianForSignerId' => 'learner-1'],
			$result['audit']['guardianConsent']
		);

	}//end testAGuardianActIsRecorded()

	/**
	 * A portal guardian's verified trust becomes the assurance, and no subject reference is kept.
	 *
	 * @return void
	 */
	public function testAPortalGuardiansTrustLandsAsAssurance(): void {
		$this->learner(birthDate: self::AGED_14);

		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signer: $this->guardian(),
			verifiedActor: [
				'email' => 'mark@home.example',
				'trust' => 'substantial',
				'subjectRef' => 'portal-subject-4711',
				'jti' => 'jti-1',
			],
			now: $this->now()
		);

		$this->assertSame(
			['provider' => 'portaliq', 'assurance' => 'substantial', 'authenticatedAt' => self::NOW],
			$result['record']['actingIdentity']
		);
		$this->assertStringNotContainsString('portal-subject-4711', (string)json_encode($result));

	}//end testAPortalGuardiansTrustLandsAsAssurance()

	/**
	 * A trust value nobody declared degrades to the weakest assurance.
	 *
	 * @return void
	 */
	public function testAnUnknownPortalTrustDegradesToLow(): void {
		$this->learner(birthDate: self::AGED_14);

		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signer: $this->guardian(),
			verifiedActor: ['email' => 'mark@home.example', 'trust' => 'totally-trusted'],
			now: $this->now()
		);

		$this->assertSame('low', $result['record']['actingIdentity']['assurance']);

	}//end testAnUnknownPortalTrustDegradesToLow()

	/**
	 * Identity evidence from the rails wins over the session, and its pseudonym stays behind.
	 *
	 * @return void
	 */
	public function testIdentityEvidenceWinsWhenPresent(): void {
		$this->learner(birthDate: self::AGED_14);
		$guardian = $this->guardian(
			extra: [
				'identityEvidence' => [
					'provider' => 'oidc-broker',
					'means' => 'digid',
					'assurance' => 'substantial',
					'subjectPseudonym' => 'pairwise-0001',
					'authenticatedAt' => '2026-09-27T11:58:00+00:00',
					'evidenceHash' => 'abc123',
				],
			]
		);

		$result = $this->guard->guardSigningAct(
			requestId: 'req-1',
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signer: $guardian,
			verifiedActor: null,
			now: $this->now()
		);

		$this->assertSame(
			['provider' => 'oidc-broker', 'assurance' => 'substantial', 'authenticatedAt' => '2026-09-27T11:58:00+00:00'],
			$result['record']['actingIdentity']
		);

	}//end testIdentityEvidenceWinsWhenPresent()

	/**
	 * Signer records of a completing request, keyed by id.
	 *
	 * @param array<string, mixed> $learnerExtra Fields for the learner.
	 * @param array<string, mixed>|null $guardianExtra Fields for the guardian, or null for no guardian.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function completingSigners(array $learnerExtra = [], ?array $guardianExtra = []): array {
		$signers = [
			'learner-1' => $this->learner(
				birthDate: self::AGED_14,
				extra: array_merge(
					[
						'status' => 'SIGNED',
						'signedAt' => '2026-09-20T09:00:00+00:00',
						'actingIdentity' => [
							'provider' => 'nextcloud-session',
							'assurance' => 'low',
							'authenticatedAt' => '2026-09-20T09:00:00+00:00',
						],
					],
					$learnerExtra
				)
			),
		];

		if ($guardianExtra !== null) {
			$signers['guardian-1'] = $this->guardian(
				extra: array_merge(
					[
						'status' => 'SIGNED',
						'signedAt' => '2026-09-21T19:30:00+00:00',
						'guardianRef' => 'learniq/guardian/0001',
						'actingIdentity' => [
							'provider' => 'nextcloud-session',
							'assurance' => 'low',
							'authenticatedAt' => '2026-09-21T19:30:00+00:00',
						],
					],
					$guardianExtra
				)
			);
		}

		return $signers;

	}//end completingSigners()

	/**
	 * The consent basis names the learner, the parent and the basis, and never the birth date.
	 *
	 * @return void
	 */
	public function testTheConsentBasisNamesBothSignersAndNoBirthDate(): void {
		$basis = $this->guard->consentBasis(
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signers: $this->completingSigners()
		);

		$this->assertSame(
			[
				[
					'signerId' => 'learner-1',
					'displayName' => 'Sanne de Vries',
					'guardianConsentAge' => 16,
					'evaluatedAt' => '2026-09-20T09:00:00+00:00',
					'basis' => 'guardian-co-signature',
					'guardians' => [
						[
							'signerId' => 'guardian-1',
							'displayName' => 'Mark de Vries',
							'guardianAct' => 'co-sign',
							'actedAt' => '2026-09-21T19:30:00+00:00',
							'identity' => [
								'provider' => 'nextcloud-session',
								'assurance' => 'low',
								'authenticatedAt' => '2026-09-21T19:30:00+00:00',
							],
							'guardianRef' => 'learniq/guardian/0001',
						],
					],
				],
			],
			$basis
		);
		$this->assertStringNotContainsString(self::AGED_14, (string)json_encode($basis));

	}//end testTheConsentBasisNamesBothSignersAndNoBirthDate()

	/**
	 * A consent act is recorded as consent, with the statement the guardian agreed to.
	 *
	 * @return void
	 */
	public function testAConsentActIsRecordedWithItsStatement(): void {
		$basis = $this->guard->consentBasis(
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signers: $this->completingSigners(
				guardianExtra: [
					'guardianAct' => 'consent',
					'consentStatement' => 'Ik geef toestemming dat Sanne deze overeenkomst ondertekent.',
				]
			)
		);

		$this->assertSame('guardian-consent', $basis[0]['basis']);
		$this->assertSame('consent', $basis[0]['guardians'][0]['guardianAct']);
		$this->assertSame(
			'Ik geef toestemming dat Sanne deze overeenkomst ondertekent.',
			$basis[0]['guardians'][0]['consentStatement']
		);

	}//end testAConsentActIsRecordedWithItsStatement()

	/**
	 * A request cannot complete while the minor's guardian has not acted.
	 *
	 * @return void
	 */
	public function testCompletionIsRefusedWhenNoGuardianActed(): void {
		foreach ([['status' => 'PENDING'], null] as $guardianExtra) {
			try {
				$this->guard->consentBasis(
					request: $this->request(signerIds: ['learner-1', 'guardian-1']),
					signers: $this->completingSigners(guardianExtra: $guardianExtra)
				);
			} catch (RuntimeException $e) {
				$this->assertStringContainsString('guardian', $e->getMessage());
				continue;
			}

			$this->fail('Completion must be refused when no guardian acted.');
		}

	}//end testCompletionIsRefusedWhenNoGuardianActed()

	/**
	 * The age is evaluated when the minor signed, not when the request completes.
	 *
	 * The learner was 15 on 10 January and is 16 by the time the request
	 * completes: the signature still needed a guardian.
	 *
	 * @return void
	 */
	public function testTheAgeIsEvaluatedWhenTheMinorSigned(): void {
		$learnerExtra = ['birthDate' => '2010-03-01', 'signedAt' => '2026-01-10T10:00:00+00:00'];

		$basis = $this->guard->consentBasis(
			request: $this->request(signerIds: ['learner-1', 'guardian-1']),
			signers: $this->completingSigners(learnerExtra: $learnerExtra)
		);
		$this->assertSame('2026-01-10T10:00:00+00:00', $basis[0]['evaluatedAt']);

		$this->expectException(RuntimeException::class);
		$this->guard->consentBasis(
			request: $this->request(signerIds: ['learner-1']),
			signers: $this->completingSigners(learnerExtra: $learnerExtra, guardianExtra: null)
		);

	}//end testTheAgeIsEvaluatedWhenTheMinorSigned()

	/**
	 * The completing request gains the basis only when it has one.
	 *
	 * @return void
	 */
	public function testWithConsentBasisTouchesOnlyARequestThatNeedsIt(): void {
		$request = $this->request(signerIds: ['learner-1', 'guardian-1']);

		$withBasis = $this->guard->withConsentBasis(request: $request, signers: $this->completingSigners());
		$this->assertSame('learner-1', $withBasis['consentBasis'][0]['signerId']);

		$adults = ['alice-1' => ['id' => 'alice-1', 'userId' => 'alice', 'status' => 'SIGNED', 'signedAt' => self::NOW]];
		$this->assertSame($request, $this->guard->withConsentBasis(request: $request, signers: $adults));

	}//end testWithConsentBasisTouchesOnlyARequestThatNeedsIt()

	/**
	 * A request between adults carries no consent basis.
	 *
	 * @return void
	 */
	public function testARequestBetweenAdultsHasNoConsentBasis(): void {
		$basis = $this->guard->consentBasis(
			request: $this->request(signerIds: ['alice-1', 'learner-1']),
			signers: [
				'alice-1' => ['id' => 'alice-1', 'userId' => 'alice', 'status' => 'SIGNED', 'signedAt' => self::NOW],
				'learner-1' => $this->learner(
					birthDate: self::AGED_17,
					extra: ['status' => 'SIGNED', 'signedAt' => self::NOW]
				),
			]
		);

		$this->assertSame([], $basis);

	}//end testARequestBetweenAdultsHasNoConsentBasis()
}//end class
