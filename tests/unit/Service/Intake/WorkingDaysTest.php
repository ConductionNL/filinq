<?php

/**
 * Unit tests for WorkingDays.
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

use DateTimeImmutable;
use OCA\Filinq\Service\Intake\WorkingDays;
use PHPUnit\Framework\TestCase;

/**
 * Weekends are skipped; nothing else is.
 *
 * @covers \OCA\Filinq\Service\Intake\WorkingDays
 */
class WorkingDaysTest extends TestCase {

	/**
	 * Ten working days after Monday 5 October 2026 is Monday 19 October.
	 *
	 * @return void
	 */
	public function testTenWorkingDaysFromAMondayIsTheMondayTwoWeeksLater(): void {
		$deadline = WorkingDays::after(from: new DateTimeImmutable('2026-10-05T09:30:00+00:00'), days: 10);

		self::assertSame('2026-10-19T09:30:00+00:00', $deadline->format(DATE_ATOM));
	}//end testTenWorkingDaysFromAMondayIsTheMondayTwoWeeksLater()

	/**
	 * One working day after a Friday, a Saturday or a Sunday is the Monday.
	 *
	 * @return void
	 */
	public function testTheWeekendIsSkipped(): void {
		foreach (['2026-10-09', '2026-10-10', '2026-10-11'] as $day) {
			self::assertSame('2026-10-12', WorkingDays::after(from: new DateTimeImmutable($day), days: 1)->format('Y-m-d'), $day);
		}

		self::assertSame('2026-10-13', WorkingDays::after(from: new DateTimeImmutable('2026-10-12'), days: 1)->format('Y-m-d'));
	}//end testTheWeekendIsSkipped()

	/**
	 * Zero or a negative number of days is the day itself.
	 *
	 * @return void
	 */
	public function testNoDaysIsTheDayItself(): void {
		self::assertSame('2026-10-10', WorkingDays::after(from: new DateTimeImmutable('2026-10-10'), days: 0)->format('Y-m-d'));
		self::assertSame('2026-10-10', WorkingDays::after(from: new DateTimeImmutable('2026-10-10'), days: -3)->format('Y-m-d'));
	}//end testNoDaysIsTheDayItself()
}//end class
