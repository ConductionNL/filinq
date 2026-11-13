<?php

/**
 * Unit tests for IntakeDeadlines.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\IntakeDeadlines;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The matching rule's period wins, then the admin default, then ten days.
 *
 * @covers \OCA\Filinq\Service\Intake\IntakeDeadlines
 * @uses   \OCA\Filinq\Service\Intake\WorkingDays
 */
class IntakeDeadlinesTest extends TestCase {

	/**
	 * The deadlines over a matching rule and an admin setting.
	 *
	 * @param array<string, mixed>|null $rule      The rule match() answers.
	 * @param int|null                  $adminDays The admin default, or null to make the setting unreadable.
	 *
	 * @return IntakeDeadlines
	 */
	private function deadlines(?array $rule, ?int $adminDays): IntakeDeadlines {
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->expects($this->once())->method('match')->with('mail', 'a@b.nl')->willReturn($rule);

		$config = $this->createMock(IAppConfig::class);
		if ($adminDays === null) {
			$config->method('getValueInt')->willThrowException(new RuntimeException('config down'));
		} else {
			$config->method('getValueInt')->with('filinq', IntakeDeadlines::DEFAULT_DAYS_KEY, IntakeDeadlines::DEFAULT_DAYS)->willReturn($adminDays);
		}

		return new IntakeDeadlines($rules, $config);
	}//end deadlines()

	/**
	 * A rule with ten handling days gives a letter of Monday 5 October the deadline Monday 19 October.
	 *
	 * @return void
	 */
	public function testTheRulesHandlingDaysCountFromArrival(): void {
		$deadline = $this->deadlines(rule: ['uuid' => 'r', 'handlingDays' => 10], adminDays: 3)
			->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-05T08:00:00+00:00');

		self::assertSame('2026-10-19', $deadline);
	}//end testTheRulesHandlingDaysCountFromArrival()

	/**
	 * A rule's period written as a numeric string counts too.
	 *
	 * @return void
	 */
	public function testANumericStringPeriodCounts(): void {
		self::assertSame(
			'2026-10-07',
			$this->deadlines(rule: ['handlingDays' => '2'], adminDays: 9)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-05T08:00:00+00:00')
		);
	}//end testANumericStringPeriodCounts()

	/**
	 * Without a rule, or with a rule that sets no period, the admin default applies.
	 *
	 * @return void
	 */
	public function testWithoutARulePeriodTheAdminDefaultApplies(): void {
		self::assertSame(
			'2026-10-08',
			$this->deadlines(rule: null, adminDays: 3)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-05T08:00:00+00:00')
		);
		self::assertSame(
			'2026-10-08',
			$this->deadlines(rule: ['handlingDays' => null], adminDays: 3)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-05T08:00:00+00:00')
		);
		self::assertSame(
			'2026-10-05',
			$this->deadlines(rule: ['handlingDays' => 'soon'], adminDays: -4)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-05T08:00:00+00:00')
		);
	}//end testWithoutARulePeriodTheAdminDefaultApplies()

	/**
	 * An unreadable admin setting falls back to ten working days.
	 *
	 * @return void
	 */
	public function testAnUnreadableSettingIsTenDays(): void {
		self::assertSame(
			'2026-10-19',
			$this->deadlines(rule: null, adminDays: null)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-05T08:00:00+00:00')
		);
	}//end testAnUnreadableSettingIsTenDays()

	/**
	 * The arrival is read in UTC, and an unreadable arrival counts from today.
	 *
	 * @return void
	 */
	public function testTheArrivalIsReadInUtc(): void {
		self::assertSame(
			'2026-10-05',
			$this->deadlines(rule: ['handlingDays' => 0], adminDays: 9)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: '2026-10-06T00:30:00+02:00')
		);

		$today = $this->deadlines(rule: ['handlingDays' => 0], adminDays: 9)->deadlineFor(channel: 'mail', sender: 'a@b.nl', receivedAt: 'not a date');
		self::assertSame(gmdate('Y-m-d'), $today);
	}//end testTheArrivalIsReadInUtc()
}//end class
