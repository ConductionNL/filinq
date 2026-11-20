<?php

/**
 * Intake Deadlines
 *
 * The handling deadline a document gets on arrival: the matching intake
 * default rule's handling period in working days, counted from the moment it
 * arrived, or the admin default when no rule matches or the rule sets none.
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
use DateTimeZone;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCP\IAppConfig;
use Throwable;

/**
 * Sets the handling deadline from the rules.
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
 */
class IntakeDeadlines {

	/**
	 * The admin setting holding the handling period when no rule sets one.
	 *
	 * @var string
	 */
	public const DEFAULT_DAYS_KEY = 'intake.default_handling_days';

	/**
	 * The handling period when the admin set none: ten working days.
	 *
	 * @var int
	 */
	public const DEFAULT_DAYS = 10;

	/**
	 * Constructor.
	 *
	 * @param IntakeDefaultRuleService $rules  The intake default rules.
	 * @param IAppConfig               $config The admin default.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeDefaultRuleService $rules,
		private readonly IAppConfig $config,
	) {

	}//end __construct()

	/**
	 * The deadline of a document arriving on a channel from a sender.
	 *
	 * @param string $channel    The channel it arrived on.
	 * @param string $sender     Who sent it.
	 * @param string $receivedAt When it arrived (ISO 8601); unreadable counts as now.
	 *
	 * @return string The deadline as a date (Y-m-d).
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
	 */
	public function deadlineFor(string $channel, string $sender, string $receivedAt): string {
		$days = $this->handlingDays(rule: $this->rules->match(channel: $channel, sender: $sender));

		return WorkingDays::after(from: $this->moment(value: $receivedAt), days: $days)->format('Y-m-d');

	}//end deadlineFor()

	/**
	 * The rule's handling period, else the admin default, else ten.
	 *
	 * @param array<string, mixed>|null $rule The matching rule, or null.
	 *
	 * @return int Working days.
	 */
	private function handlingDays(?array $rule): int {
		$ruleDays = ($rule['handlingDays'] ?? null);
		if (is_int($ruleDays) === true || (is_string($ruleDays) === true && ctype_digit($ruleDays) === true)) {
			return (int)$ruleDays;
		}

		try {
			return max(0, $this->config->getValueInt(AppIdentity::APP_ID, self::DEFAULT_DAYS_KEY, self::DEFAULT_DAYS));
		} catch (Throwable $e) {
			return self::DEFAULT_DAYS;
		}

	}//end handlingDays()

	/**
	 * The arrival moment, in UTC; now when it cannot be read.
	 *
	 * @param string $value The ISO 8601 moment.
	 *
	 * @return DateTimeImmutable The moment.
	 */
	private function moment(string $value): DateTimeImmutable {
		$utc = new DateTimeZone('UTC');
		try {
			if (trim($value) !== '') {
				return (new DateTimeImmutable($value))->setTimezone($utc);
			}
		} catch (Throwable $e) {
			// An unreadable arrival moment counts from now, below.
		}

		return new DateTimeImmutable('now', $utc);

	}//end moment()
}//end class
