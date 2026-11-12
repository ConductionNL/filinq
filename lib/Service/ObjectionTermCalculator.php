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
	 * How far a deadline on a weekday (ISO number) moves: Saturday and Sunday go to the Monday.
	 */
	private const WEEKEND_SHIFT = [
		6 => '+2 days',
		7 => '+1 day',
	];

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
		$decided = $this->parseDecisionDate(decisionDate: $decisionDate);
		if ($decided === null) {
			return null;
		}

		$weeks = $this->configuredWeeks();
		$deadline = $this->nextWorkingDay(deadline: $decided->modify('+' . $weeks . ' weeks'));

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
		$topLevel = $this->dateIn(values: $data);
		if ($topLevel !== null) {
			return $topLevel;
		}

		return $this->firstNestedDate(data: $data);

	}//end findDecisionDate()

	/**
	 * Add the legal basis and the objection deadline to a template context.
	 *
	 * A resolved `base` object is also offered as `grondslag`, the name the
	 * merge field dialog uses. The `bezwaar` block is added when the data
	 * carries a decision date; when it does not and the template uses
	 * `bezwaar`, a warning says so instead of printing a wrong date.
	 *
	 * @param array<string, mixed> $data            The resolved template data
	 * @param string               $templateContent The template source, to see whether it uses `bezwaar`
	 *
	 * @return array{data: array<string, mixed>, warnings: array<int, string>}
	 *
	 * @spec openspec/changes/archive/2026-09-28-decision-letter-legal-basis-and-deadline/tasks.md#task-1.2
	 */
	public function addToContext(array $data, string $templateContent): array {
		$data = $this->withGrondslag(data: $data);
		if (isset($data['bezwaar']) === true) {
			return ['data' => $data, 'warnings' => []];
		}

		$term = $this->termFor(data: $data);
		if ($term !== null) {
			$data['bezwaar'] = $term;
			return ['data' => $data, 'warnings' => []];
		}

		return ['data' => $data, 'warnings' => $this->missingTermWarnings(templateContent: $templateContent)];

	}//end addToContext()

	/**
	 * Read a decision date as the start of that day.
	 *
	 * @param string $decisionDate The decision date, any format DateTimeImmutable reads
	 *
	 * @return DateTimeImmutable|null The date at midnight, or null when it is empty or unreadable.
	 */
	private function parseDecisionDate(string $decisionDate): ?DateTimeImmutable {
		if (trim($decisionDate) === '') {
			return null;
		}

		try {
			return (new DateTimeImmutable($decisionDate))->setTime(0, 0);
		} catch (Exception) {
			return null;
		}

	}//end parseDecisionDate()

	/**
	 * Move a deadline that ends in the weekend to the Monday.
	 *
	 * Algemene termijnenwet art. 1: a term ending on a Saturday or Sunday
	 * ends on the next working day, which for a weekend is the Monday.
	 *
	 * @param DateTimeImmutable $deadline The deadline as counted
	 *
	 * @return DateTimeImmutable The deadline on a working day.
	 */
	private function nextWorkingDay(DateTimeImmutable $deadline): DateTimeImmutable {
		$shift = self::WEEKEND_SHIFT[(int) $deadline->format('N')] ?? null;
		if ($shift === null) {
			return $deadline;
		}

		return $deadline->modify($shift);

	}//end nextWorkingDay()

	/**
	 * The first non-empty string under one of the date keys.
	 *
	 * @param array<mixed> $values The data or one resolved object
	 *
	 * @return string|null The decision date, or null when none of the keys holds one.
	 */
	private function dateIn(array $values): ?string {
		foreach (self::DATE_KEYS as $key) {
			if (is_string($values[$key] ?? null) === true && $values[$key] !== '') {
				return $values[$key];
			}
		}

		return null;

	}//end dateIn()

	/**
	 * The first decision date held by one of the resolved objects in the data.
	 *
	 * @param array<string, mixed> $data The resolved template data
	 *
	 * @return string|null The decision date, or null when no object carries one.
	 */
	private function firstNestedDate(array $data): ?string {
		foreach ($data as $value) {
			if (is_array($value) === false) {
				continue;
			}

			$nested = $this->dateIn(values: $value);
			if ($nested !== null) {
				return $nested;
			}
		}

		return null;

	}//end firstNestedDate()

	/**
	 * The warning for a context without a term, given only when the template uses `bezwaar`.
	 *
	 * @param string $templateContent The template source
	 *
	 * @return array<int, string> The warning, or nothing when the template does not need the term.
	 */
	private function missingTermWarnings(string $templateContent): array {
		if (str_contains($templateContent, 'bezwaar') === false) {
			return [];
		}

		return ['No decision date found (besluitDatum or decisionDate), so the objection deadline is left out.'];

	}//end missingTermWarnings()

	/**
	 * Offer a resolved `base` object as `grondslag` when the data has no `grondslag` yet.
	 *
	 * @param array<string, mixed> $data The resolved template data
	 *
	 * @return array<string, mixed> The data, with `grondslag` when it applies.
	 */
	private function withGrondslag(array $data): array {
		if (isset($data['grondslag']) === false && is_array($data['base'] ?? null) === true) {
			$data['grondslag'] = $data['base'];
		}

		return $data;

	}//end withGrondslag()

	/**
	 * The objection term for the decision date in the data.
	 *
	 * @param array<string, mixed> $data The resolved template data
	 *
	 * @return array{termijnWeken: int, vanaf: string, uiterlijk: string, uiterlijkIso: string}|null
	 *         The term, or null when the data has no readable decision date.
	 */
	private function termFor(array $data): ?array {
		$decisionDate = $this->findDecisionDate(data: $data);
		if ($decisionDate === null) {
			return null;
		}

		return $this->calculate(decisionDate: $decisionDate);

	}//end termFor()

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
