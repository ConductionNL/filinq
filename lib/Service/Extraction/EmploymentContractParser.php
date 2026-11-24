<?php

/**
 * Employment Contract Parser
 *
 * Pure, heuristic reader of the terms of an employment contract in free text
 * (Dutch and English): the employee's name, the start and end date, the hours
 * per week, the monthly or hourly wage and whether the contract is for a
 * fixed or an indefinite term. Every field carries the heuristic's confidence,
 * so the caller can show what it should check (REQ-EDE-002).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

/**
 * Reads the terms of an employment contract.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-002
 */
class EmploymentContractParser {

	/**
	 * Confidence of a value read after its label.
	 */
	public const LABELLED = 0.8;

	/**
	 * Confidence of a value read from a phrase without a label.
	 */
	public const PHRASED = 0.6;

	/**
	 * A date: 1-2-2026, 01-02-2026, 2026-02-01, or 1 februari 2026.
	 */
	private const DATE = '(\d{4}-\d{2}-\d{2}|\d{1,2}[-\/.]\d{1,2}[-\/.]\d{4}|\d{1,2}\s+[a-z]+\s+\d{4})';

	/**
	 * A euro amount: 3.250,00 or 3250.00 or 18,50.
	 */
	private const AMOUNT = '(?:€|eur|euro)\s*(\d{1,3}(?:[.\s]\d{3})*(?:,\d{1,2})?|\d+(?:[.,]\d{1,2})?)';

	/**
	 * Month names to numbers.
	 *
	 * @var array<string, int>
	 */
	private const MONTHS = [
		'januari' => 1, 'january' => 1, 'februari' => 2, 'february' => 2, 'maart' => 3, 'march' => 3,
		'april' => 4, 'mei' => 5, 'may' => 5, 'juni' => 6, 'june' => 6, 'juli' => 7, 'july' => 7,
		'augustus' => 8, 'august' => 8, 'september' => 9, 'oktober' => 10, 'october' => 10,
		'november' => 11, 'december' => 12,
	];

	/**
	 * Read the terms.
	 *
	 * @param string $text The contract text.
	 *
	 * @return array<string, array{value: string|float, confidence: float}>
	 *
	 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-002
	 */
	public function parse(string $text): array {
		$text = str_replace("\u{a0}", ' ', $text);
		$fields = array_merge(
			$this->name(text: $text),
			$this->dates(text: $text),
			$this->hours(text: $text),
			$this->wage(text: $text),
			$this->term(text: $text)
		);

		return array_filter($fields, static fn (array $field): bool => $field['value'] !== '');
	}//end parse()

	/**
	 * The employee's name after a label, split on its last space.
	 *
	 * @param string $text The text.
	 *
	 * @return array<string, array{value: string, confidence: float}>
	 */
	private function name(string $text): array {
		$label = '/(?:naam\s+werknemer|werknemer|employee(?:\s+name)?)\s*[:]\s*(?:de heer|mevrouw|dhr\.?|mevr\.?|mr\.?|mrs\.?|ms\.?)?\s*([^\n,]+)/iu';
		if (preg_match($label, $text, $match) !== 1) {
			return [];
		}

		$full = trim(preg_replace('/\s+/u', ' ', $match[1]));
		$parts = preg_split('/\s+/u', $full);
		if (count($parts) < 2) {
			return ['lastName' => ['value' => $full, 'confidence' => self::PHRASED]];
		}

		// Dutch surname prefixes belong to the last name (de Boer, van der Berg).
		$first = array_shift($parts);
		return [
			'firstName' => ['value' => $first, 'confidence' => self::PHRASED],
			'lastName' => ['value' => implode(' ', $parts), 'confidence' => self::PHRASED],
		];
	}//end name()

	/**
	 * The start and end date after their labels.
	 *
	 * @param string $text The text.
	 *
	 * @return array<string, array{value: string, confidence: float}>
	 */
	private function dates(string $text): array {
		$fields = [];
		$labels = [
			'startDate' => '(?:met ingang van|ingangsdatum|datum (?:van )?indiensttreding|in dienst (?:per|op|vanaf)|startdatum|'
				.'start date|commencing on|aanvang(?:sdatum)?)',
			'endDate' => '(?:tot en met|t\/m|einddatum|eindigt (?:van rechtswege )?(?:op|per)|end date|ending on)',
		];
		foreach ($labels as $field => $label) {
			preg_match_all('/'.$label.'\s*:?\s*'.self::DATE.'/iu', $text, $matches);
			foreach ($matches[1] as $raw) {
				$date = $this->isoDate(raw: $raw);
				if ($date !== '') {
					$fields[$field] = ['value' => $date, 'confidence' => self::LABELLED];
					break;
				}
			}
		}

		return $fields;
	}//end dates()

	/**
	 * The hours per week.
	 *
	 * @param string $text The text.
	 *
	 * @return array<string, array{value: float, confidence: float}>
	 */
	private function hours(string $text): array {
		if (preg_match('/(\d{1,2}(?:[.,]\d{1,2})?)\s*(?:uur|uren|hours)\s*(?:per|a|\/)\s*(?:week|wk)/iu', $text, $match) !== 1) {
			return [];
		}

		$hours = (float)str_replace(',', '.', $match[1]);
		if ($hours <= 0.0 || $hours > 60.0) {
			return [];
		}

		return ['hoursPerWeek' => ['value' => $hours, 'confidence' => self::PHRASED]];
	}//end hours()

	/**
	 * The gross wage per month or per hour.
	 *
	 * @param string $text The text.
	 *
	 * @return array<string, array{value: float, confidence: float}>
	 */
	private function wage(string $text): array {
		$fields = [];
		$periods = ['monthlyWage' => '(?:per maand|bruto per maand|a month|per month)', 'hourlyWage' => '(?:per uur|bruto per uur|an hour|per hour)'];
		foreach ($periods as $field => $period) {
			if (preg_match('/'.self::AMOUNT.'\s*(?:bruto|gross)?\s*'.$period.'/iu', $text, $match) === 1) {
				$fields[$field] = ['value' => $this->amount(raw: $match[1]), 'confidence' => self::PHRASED];
			}
		}

		return $fields;
	}//end wage()

	/**
	 * Fixed term (`temporary`) or indefinite (`permanent`).
	 *
	 * @param string $text The text.
	 *
	 * @return array<string, array{value: string, confidence: float}>
	 */
	private function term(string $text): array {
		if (preg_match('/onbepaalde\s+tijd|indefinite\s+(?:term|period)|permanent\s+contract/iu', $text) === 1) {
			return ['contractType' => ['value' => 'permanent', 'confidence' => self::PHRASED]];
		}

		if (preg_match('/bepaalde\s+tijd|fixed[- ]term/iu', $text) === 1) {
			return ['contractType' => ['value' => 'temporary', 'confidence' => self::PHRASED]];
		}

		return [];
	}//end term()

	/**
	 * A date as Y-m-d, or '' when it is not a date.
	 *
	 * @param string $raw The date as written.
	 *
	 * @return string
	 */
	private function isoDate(string $raw): string {
		$raw = strtolower(trim($raw));
		$parts = null;
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $match) === 1) {
			$parts = [(int)$match[1], (int)$match[2], (int)$match[3]];
		}

		if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $raw, $match) === 1) {
			$parts = [(int)$match[3], (int)$match[2], (int)$match[1]];
		}

		if (preg_match('/^(\d{1,2})\s+([a-z]+)\s+(\d{4})$/', $raw, $match) === 1 && isset(self::MONTHS[$match[2]]) === true) {
			$parts = [(int)$match[3], self::MONTHS[$match[2]], (int)$match[1]];
		}

		if ($parts === null || checkdate($parts[1], $parts[2], $parts[0]) === false) {
			return '';
		}

		return sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
	}//end isoDate()

	/**
	 * A written euro amount as a number: 3.250,00 and 3250.00 are 3250.0.
	 *
	 * @param string $raw The amount as written.
	 *
	 * @return float
	 */
	private function amount(string $raw): float {
		$raw = str_replace(' ', '', $raw);
		if (str_contains($raw, ',') === true) {
			return (float)str_replace(['.', ','], ['', '.'], $raw);
		}

		if (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw) === 1) {
			return (float)str_replace('.', '', $raw);
		}

		return (float)$raw;
	}//end amount()
}//end class
