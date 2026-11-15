<?php

/**
 * Unit tests for GuardianAgePolicy.
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
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\GuardianAgePolicy;
use PHPUnit\Framework\TestCase;

/**
 * The guardian consent age and the under-age test.
 *
 * @covers \OCA\Filinq\Service\Signing\GuardianAgePolicy
 */
class GuardianAgePolicyTest extends TestCase {

	/**
	 * Build the policy over the given feature toggles.
	 *
	 * @param array<string, mixed> $toggles Feature toggles.
	 *
	 * @return GuardianAgePolicy
	 */
	private function policy(array $toggles): GuardianAgePolicy {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getFeatureToggles')->willReturn($toggles);
		return new GuardianAgePolicy(settingsService: $settings);
	}//end policy()

	/**
	 * Configured and requested ages: the higher one applies; bad values are ignored.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: int}>
	 */
	public static function ages(): array {
		return [
			'default'                   => [[], [], 16],
			'configured int'            => [[GuardianAgePolicy::SETTING => 18], [], 18],
			'configured digit string'   => [[GuardianAgePolicy::SETTING => ' 14 '], [], 14],
			'configured zero ignored'   => [[GuardianAgePolicy::SETTING => 0], [], 16],
			'configured junk ignored'   => [[GuardianAgePolicy::SETTING => '12a'], [], 16],
			'request raises'            => [[], ['guardianConsentAge' => 21], 21],
			'request cannot lower'      => [[GuardianAgePolicy::SETTING => 18], ['guardianConsentAge' => '12'], 18],
			'request negative ignored'  => [[], ['guardianConsentAge' => -30], 16],
			'request float ignored'     => [[], ['guardianConsentAge' => 20.5], 16],
		];
	}//end ages()

	/**
	 * The applied age.
	 *
	 * @param array<string, mixed> $toggles  Feature toggles.
	 * @param array<string, mixed> $data     Request data.
	 * @param int                  $expected Expected age.
	 *
	 * @dataProvider ages
	 *
	 * @return void
	 */
	public function testAppliedAge(array $toggles, array $data, int $expected): void {
		$this->assertSame($expected, $this->policy(toggles: $toggles)->appliedAge(data: $data));
	}//end testAppliedAge()

	/**
	 * Under-age turns over on the birthday; unparseable or future birth dates count as under age.
	 *
	 * @return void
	 */
	public function testIsUnderAge(): void {
		$policy = $this->policy(toggles: []);
		$moment = new DateTimeImmutable('2026-06-15T12:00:00+00:00');

		$this->assertFalse($policy->isUnderAge(birthDate: '2010-06-15', age: 16, moment: $moment));
		$this->assertTrue($policy->isUnderAge(birthDate: '2010-06-16', age: 16, moment: $moment));
		$this->assertFalse($policy->isUnderAge(birthDate: '1980-01-01', age: 16, moment: $moment));
		$this->assertTrue($policy->isUnderAge(birthDate: '2027-01-01', age: 16, moment: $moment));
		$this->assertTrue($policy->isUnderAge(birthDate: '15-06-1980', age: 16, moment: $moment));
	}//end testIsUnderAge()

	/**
	 * Only real Y-m-d dates parse, at midnight UTC.
	 *
	 * @return void
	 */
	public function testParseBirthDate(): void {
		$policy = $this->policy(toggles: []);

		$this->assertSame('2008-02-29T00:00:00+00:00', $policy->parseBirthDate(value: '2008-02-29')?->format(DATE_ATOM));
		$this->assertNull($policy->parseBirthDate(value: '2007-02-29'));
		$this->assertNull($policy->parseBirthDate(value: '2008-2-9'));
		$this->assertNull($policy->parseBirthDate(value: '2008-02-29 '));
	}//end testParseBirthDate()

	/**
	 * A valid moment is parsed; empty or invalid text falls back to now.
	 *
	 * @return void
	 */
	public function testMoment(): void {
		$policy = $this->policy(toggles: []);

		$this->assertSame('2026-01-02T03:04:05+00:00', $policy->moment(value: '2026-01-02T03:04:05+00:00')->format(DATE_ATOM));

		$before = time();
		$this->assertGreaterThanOrEqual($before, $policy->moment(value: '')->getTimestamp());
		$this->assertGreaterThanOrEqual($before, $policy->moment(value: 'not a date')->getTimestamp());
	}//end testMoment()
}//end class
