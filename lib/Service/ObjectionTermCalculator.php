<?php

/**
 * Objection Term Calculator
 *
 * Computes the objection deadline of a decision (Awb 6:7): the configured
 * number of weeks (app setting `bezwaar_termijn_weken`, default 6), counted
 * from the day after the decision date. A deadline that ends on a Saturday or
 * Sunday moves to the Monday (Algemene termijnenwet). Public holidays are not
 * moved; that limit is documented in docs/features/document-creatie-sjablonen.md.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use Exception;
use OCP\IAppConfig;

/**
 * Turns a decision date into the `bezwaar` block of a template context.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
class ObjectionTermCalculator {

	/**
	 * App config key holding the objection term in weeks.
	 */
	public const CONFIG_KEY = 'bezwaar_termijn_weken';

	/**
	 * The statutory objection term (Awb 6:7) when nothing is configured.
	 */
	public const DEFAULT_WEEKS = 6;

	/**
	 * The data keys that carry a decision date, in the order they are tried.
	 */
	private const DATE_KEYS = ['besluitDatum', 'decisionDate'];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig The app configuration holding the term
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Compute the objection term for a decision date.
	 *
	 * @param string $decisionDate The decision date, any format DateTimeImmutable reads
	 *
	 * @return array{termijnWeken: int, vanaf: string, uiterlijk: string, uiterlijkIso: string}|null
	 *         The term, or null when the date cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-28-decision-letter-legal-basis-and-deadline/tasks.md#task-1.1
	 */
	public function calculate(string $decisionDate): ?array {
		if (trim($decisionDate) === '') {
			return null;
		}

		try {
			$decided = new DateTimeImmutable($decisionDate);
		} catch (Exception) {
			return null;
		}

		$decided = $decided->setTime(0, 0);
		$weeks = $this->configuredWeeks();
		$deadline = $decided->modify('+' . $weeks . ' weeks');

		// Algemene termijnenwet art. 1: a term ending on a Saturday or Sunday
		// ends on the next working day, which for a weekend is the Monday.
		$weekday = (int) $deadline->format('N');
		if ($weekday === 6) {
			$deadline = $deadline->modify('+2 days');
		} else if ($weekday === 7) {
			$deadline = $deadline->modify('+1 day');
		}

		return [
			'termijnWeken' => $weeks,
			'vanaf' => $decided->modify('+1 day')->format('d-m-Y'),
			'uiterlijk' => $deadline->format('d-m-Y'),
			'uiterlijkIso' => $deadline->format('Y-m-d'),
		];

	}//end calculate()

	/**
	 * Find the decision date in resolved template data.
	 *
	 * Top-level keys (ad-hoc data) win; otherwise the first resolved object
	 * that carries one of the date keys is used.
	 *
	 * @param array<string, mixed> $data The resolved template data
	 *
	 * @return string|null The decision date, or null when the data has none.
	 *
	 * @spec openspec/changes/archive/2026-09-28-decision-letter-legal-basis-and-deadline/tasks.md#task-1.2
	 */
	public function findDecisionDate(array $data): ?string {
		foreach (self::DATE_KEYS as $key) {
			if (is_string($data[$key] ?? null) === true && $data[$key] !== '') {
				return $data[$key];
			}
		}

		foreach ($data as $value) {
			if (is_array($value) === false) {
				continue;
			}

			foreach (self::DATE_KEYS as $key) {
				if (is_string($value[$key] ?? null) === true && $value[$key] !== '') {
					return $value[$key];
				}
			}
		}

		return null;

	}//end findDecisionDate()

	/**
	 * Read the configured term, falling back to the statutory six weeks.
	 *
	 * @return int The term in weeks, at least one.
	 */
	private function configuredWeeks(): int {
		$weeks = $this->appConfig->getValueInt('filinq', self::CONFIG_KEY, self::DEFAULT_WEEKS);
		if ($weeks < 1) {
			return self::DEFAULT_WEEKS;
		}

		return $weeks;

	}//end configuredWeeks()
}//end class
