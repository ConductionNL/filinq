<?php

/**
 * Whether anybody is actually going to be told, said on the screen an operator opens.
 *
 * 🔴 THE ANNOTATION ALONE WOULD HAVE BEEN A FIX THAT REACHES NOBODY. The rule
 * addresses `docudesk-woo-officers`, and every declared group in this fleet
 * ships EMPTY on purpose — an empty group denies everyone except admins and
 * object owners, which is the right default. OpenRegister now records a rule
 * that resolved to nobody, once per run, at warning level.
 *
 * 🔴 BUT A LOG LINE IS NOT A PERSON. That record is findable by somebody who
 * already suspects the problem, which is the wrong audience: the person who
 * needs to know is the registrar who will never be told a scan failed, and they
 * are looking at the inbox, not at the server log.
 *
 * So the inbox asks the question directly, at read time, from the group the
 * rule names: is anybody in it? If not, the inbox says so beside the failure
 * count, in words, where the person who would have been notified is already
 * standing. That is the difference between a gap that is recorded and a gap
 * that is seen.
 *
 * This duplicates nothing. OpenRegister answers "did this rule reach anybody
 * when it ran"; this answers "will it reach anybody at all", before it runs and
 * on a screen. The first is for whoever reads logs, the second for whoever
 * works the post.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-failure-reaches-someone/specs/filinq-notifications/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

/**
 * Says, on the inbox, whether the failure notification can reach anybody.
 */
class IntakeNotificationReach {

	/**
	 * The rule declared on `intakeDocument`.
	 *
	 * @var string
	 */
	public const RULE = 'readingFailed';

	/**
	 * The group the rule addresses.
	 *
	 * @var string
	 */
	public const GROUP = 'docudesk-woo-officers';

	/**
	 * What the inbox shows about who will be told.
	 *
	 * @param int  $failureCount How many documents could not be read.
	 * @param int  $groupMembers How many people are in the notified group.
	 *
	 * @return array<string, mixed> What to show.
	 */
	public function describe(int $failureCount, int $groupMembers): array {
		$staffed = ($groupMembers > 0);

		if ($failureCount === 0) {
			$warning = '';
			if ($staffed === false) {
				$warning = $this->unstaffedWarning(failureCount: 0);
			}

			return [
				'notificationReaches' => $groupMembers,
				'staffed' => $staffed,
				// 🔴 SAID EVEN WITH NOTHING FAILING. An unstaffed rule is worth
				// knowing about BEFORE the night it is needed, and an inbox
				// that only mentions it once something has already gone
				// unnoticed is telling somebody too late.
				'warning' => $warning,
				'needsAPerson' => ($staffed === false),
			];
		}

		if ($staffed === true) {
			return [
				'notificationReaches' => $groupMembers,
				'staffed' => true,
				'warning' => '',
				'needsAPerson' => true,
			];
		}

		return [
			'notificationReaches' => 0,
			'staffed' => false,
			'warning' => $this->unstaffedWarning(failureCount: $failureCount),
			'needsAPerson' => true,
		];
	}//end describe()

	/**
	 * What the inbox says when nobody is in the group.
	 *
	 * Names the group, because "nobody is configured" sends an administrator
	 * hunting through settings, and the group name is the one thing that turns
	 * this into a two-minute fix.
	 *
	 * @param int $failureCount How many documents could not be read.
	 *
	 * @return string The warning.
	 */
	private function unstaffedWarning(int $failureCount): string {
		if ($failureCount === 0) {
			return sprintf(
				'Nobody is in "%s", so if a document cannot be read tonight, no one will be told. Add at '
				.'least one person to that group.',
				self::GROUP
			);
		}

		return sprintf(
			'%d document(s) could not be read, and nobody is in "%s", so no one was told. You are seeing '
			.'this because you opened the inbox, not because anybody was notified. Add at least one '
			.'person to that group.',
			$failureCount,
			self::GROUP
		);
	}//end unstaffedWarning()
}//end class
