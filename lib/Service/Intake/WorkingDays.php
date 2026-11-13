<?php

/**
 * Working Days
 *
 * Counts working days forward from a moment: Saturdays and Sundays are
 * skipped, public holidays are not known (a rule that needs them adds days).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use DateTimeImmutable;

/**
 * Working-day arithmetic over the calendar week.
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
 */
final class WorkingDays {

	/**
	 * The date that lies the given number of working days after a moment.
	 *
	 * Zero days is the day itself. A moment on a weekend starts counting from
	 * that weekend, so one working day after Saturday is Monday.
	 *
	 * @param DateTimeImmutable $from The moment counting starts from.
	 * @param int               $days How many working days to add; negative counts as zero.
	 *
	 * @return DateTimeImmutable The resulting day, at the time of day of $from.
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
	 */
	public static function after(DateTimeImmutable $from, int $days): DateTimeImmutable {
		$date = $from;
		$left = max(0, $days);
		while ($left > 0) {
			$date = $date->modify('+1 day');
			if ((int)$date->format('N') <= 5) {
				$left--;
			}
		}

		return $date;

	}//end after()
}//end class
