<?php

/**
 * Unit tests for GuardianEntryPreparer.
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

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\GuardianAgePolicy;
use OCA\Filinq\Service\Signing\GuardianEntryPreparer;
use PHPUnit\Framework\TestCase;

/**
 * Roles, guardian fields, links and the minor check of a signer list.
 *
 * @covers \OCA\Filinq\Service\Signing\GuardianEntryPreparer
 * @uses   \OCA\Filinq\Service\Signing\GuardianAgePolicy
 */
class GuardianEntryPreparerTest extends TestCase {

	/**
	 * The moment every check is made at.
	 *
	 * @var DateTimeImmutable
	 */
	private DateTimeImmutable $now;

	/**
	 * Fix the moment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->now = new DateTimeImmutable('2026-06-01T12:00:00+00:00');

	}//end setUp()

	/**
	 * The preparer over the real age policy.
	 *
	 * @return GuardianEntryPreparer
	 */
	private function preparer(): GuardianEntryPreparer {
		return new GuardianEntryPreparer(agePolicy: new GuardianAgePolicy(settingsService: $this->createMock(SettingsService::class)));

	}//end preparer()

	/**
	 * Prepare a signer list at age 16.
	 *
	 * @param array<int|string, array<string, mixed>> $signers The signers.
	 *
	 * @return array{fields: array<int|string, array<string, string>>, links: array<int|string, int|string>}
	 */
	private function prepare(array $signers): array {
		return $this->preparer()->prepare(signers: $signers, age: 16, now: $this->now);

	}//end prepare()

	/**
	 * A guardian named by guardianFor gets the guardian role and the default
	 * co-sign act, and is linked to the minor it names by user id.
	 *
	 * @return void
	 */
	public function testANamedGuardianIsLinkedToTheMinor(): void {
		$prepared = $this->prepare(
			[
				['userId' => 'kid', 'birthDate' => '2015-03-04'],
				['userId' => 'mum', 'guardianFor' => 'kid', 'guardianRef' => ' BSN-ref '],
			]
		);

		$this->assertSame(
			[
				0 => ['role' => 'signer', 'birthDate' => '2015-03-04'],
				1 => ['role' => 'guardian', 'guardianAct' => 'co-sign', 'guardianRef' => 'BSN-ref'],
			],
			$prepared['fields']
		);
		$this->assertSame([1 => 0], $prepared['links']);

	}//end testANamedGuardianIsLinkedToTheMinor()

	/**
	 * A guardian may name the minor by e-mail, case-insensitively; a consent
	 * act keeps its statement.
	 *
	 * @return void
	 */
	public function testAGuardianMayNameTheMinorByEmail(): void {
		$prepared = $this->prepare(
			[
				'g' => ['email' => 'mum@example.org', 'role' => 'guardian', 'guardianFor' => 'KID@example.org', 'guardianAct' => 'consent', 'consentStatement' => ' I agree '],
				'k' => ['email' => 'kid@example.org', 'birthDate' => '2014-01-01'],
			]
		);

		$this->assertSame(['g' => 'k'], $prepared['links']);
		$this->assertSame(['role' => 'guardian', 'guardianAct' => 'consent', 'consentStatement' => 'I agree'], $prepared['fields']['g']);

	}//end testAGuardianMayNameTheMinorByEmail()

	/**
	 * An entry without role, guardianFor or birth date carries no fields.
	 *
	 * @return void
	 */
	public function testAPlainSignerCarriesNoFields(): void {
		$this->assertSame(['fields' => [0 => []], 'links' => []], $this->prepare([['userId' => 'adult']]));

	}//end testAPlainSignerCarriesNoFields()

	/**
	 * Each refusal, with its message and code 400.
	 *
	 * @return array<string, array{0: array<int, array<string, mixed>>, 1: string}>
	 */
	public static function refusals(): array {
		return [
			'unknown role'                => [[['role' => 'witness']], 'A signer role is signer or guardian, not "witness"'],
			'unknown act'                 => [[['userId' => 'k', 'birthDate' => '2015-01-01'], ['guardianFor' => 'k', 'guardianAct' => 'approve']], 'A guardian act is co-sign or consent, not "approve"'],
			'consent without statement'   => [[['userId' => 'k'], ['guardianFor' => 'k', 'guardianAct' => 'consent']], 'A consent act needs the statement the guardian consents to'],
			'malformed birth date'        => [[['birthDate' => '01-02-2015']], 'A birth date must be an ISO 8601 date (YYYY-MM-DD)'],
			'guardian with empty name'    => [[['userId' => 'k'], ['role' => 'guardian', 'guardianFor' => '  ']], 'A guardian entry must name another signer on this request in guardianFor'],
			'guardian naming nobody'      => [[['userId' => 'k'], ['guardianFor' => 'someone-else']], 'A guardian entry must name another signer on this request in guardianFor'],
			'guardian naming a guardian'  => [[['userId' => 'a', 'guardianFor' => 'b'], ['userId' => 'b', 'guardianFor' => 'a']], 'A guardian entry must name another signer on this request in guardianFor'],
			'guardian naming themselves'  => [[['userId' => 'mum', 'guardianFor' => 'mum']], 'A guardian entry must name another signer on this request in guardianFor'],
			'under-age guardian'          => [[['userId' => 'k', 'birthDate' => '2015-01-01'], ['userId' => 'teen', 'guardianFor' => 'k', 'birthDate' => '2012-01-01']], 'A guardian must have reached the age of 16'],
			'unguarded minor'             => [[['userId' => 'k', 'birthDate' => '2015-01-01']], 'A signer under 16 needs a guardian on this request, and this request names none for them'],
		];

	}//end refusals()

	/**
	 * Every malformed or unsafe signer list is refused with a 400.
	 *
	 * @param array<int, array<string, mixed>> $signers The signers.
	 * @param string                           $message The refusal.
	 *
	 * @return void
	 *
	 * @dataProvider refusals
	 */
	public function testRefusals(array $signers, string $message): void {
		try {
			$this->prepare($signers);
			$this->fail('Accepted: ' . $message);
		} catch (InvalidArgumentException $exception) {
			$this->assertSame($message, $exception->getMessage());
			$this->assertSame(400, $exception->getCode());
		}

	}//end testRefusals()

	/**
	 * A signer who turned 16 on the day itself needs no guardian.
	 *
	 * @return void
	 */
	public function testASignerOfExactlyTheAgeNeedsNoGuardian(): void {
		$prepared = $this->prepare([['userId' => 'k', 'birthDate' => '2010-06-01']]);

		$this->assertSame([], $prepared['links']);

	}//end testASignerOfExactlyTheAgeNeedsNoGuardian()
}//end class
