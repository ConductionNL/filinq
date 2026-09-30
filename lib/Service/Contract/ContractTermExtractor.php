<?php

/**
 * Contract term extractor
 *
 * Reads candidate key terms out of contract text with local patterns, in
 * Dutch and English: start and end date, notice period, value and currency,
 * and a named counterparty. It only proposes; nothing here writes a contract.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

/**
 * Pattern-based key-term extraction over plain text.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-key-term-extraction-is-suggestion-only-req-ddclm-005
 */
class ContractTermExtractor {

	/**
	 * A date as people write it in a contract.
	 */
	private const DATE = '(\d{4}-\d{2}-\d{2}|\d{1,2}[-\/.]\d{1,2}[-\/.]\d{4}|\d{1,2}\s+[a-zA-Z]+\s+\d{4})';

	/**
	 * The words that introduce each date, per field.
	 */
	private const DATE_CUES = [
		'startDate' => 'ingangsdatum|met ingang van|gaat in op|start date|commences on|effective from',
		'endDate'   => 'einddatum|eindigt op|loopt tot en met|end date|expires on|ends on',
	];

	/**
	 * Month names, Dutch and English, to their number.
	 */
	private const MONTHS = [
		'januari' => 1, 'january' => 1, 'februari' => 2, 'february' => 2, 'maart' => 3, 'march' => 3,
		'april' => 4, 'mei' => 5, 'may' => 5, 'juni' => 6, 'june' => 6, 'juli' => 7, 'july' => 7,
		'augustus' => 8, 'august' => 8, 'september' => 9, 'oktober' => 10, 'october' => 10,
		'november' => 11, 'december' => 12,
	];

	/**
	 * Days per unit of a notice period, and how sure the conversion is.
	 */
	private const UNITS = [
		'dag' => [1, 0.85], 'dagen' => [1, 0.85], 'day' => [1, 0.85], 'days' => [1, 0.85],
		'week' => [7, 0.8], 'weken' => [7, 0.8], 'weeks' => [7, 0.8],
		'maand' => [30, 0.6], 'maanden' => [30, 0.6], 'month' => [30, 0.6], 'months' => [30, 0.6],
	];

	/**
	 * Propose key terms found in the text.
	 *
	 * @param string $text The contract text.
	 *
	 * @return list<array{field: string, value: string, confidence: float}> The proposals, best first per field.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-2
	 */
	public function extract(string $text): array {
		$text = (string) preg_replace('/\s+/u', ' ', $text);
		$found = [];
		foreach (self::DATE_CUES as $field => $cues) {
			if (preg_match('/(?:' . $cues . ')\s*:?\s*' . self::DATE . '/iu', $text, $match) === 1) {
				$date = $this->normaliseDate(raw: $match[1]);
				if ($date !== null) {
					$found[] = ['field' => $field, 'value' => $date, 'confidence' => 0.8];
				}
			}
		}

		$notice = $this->noticePeriod(text: $text);
		if ($notice !== null) {
			$found[] = $notice;
		}

		return array_merge($found, $this->value(text: $text), $this->party(text: $text));

	}//end extract()

	/**
	 * A date written as Y-m-d, d-m-Y (any of - / .) or "31 december 2028", as Y-m-d.
	 *
	 * @param string $raw The date as written.
	 *
	 * @return string|null The date, or null when it is not a real date.
	 */
	private function normaliseDate(string $raw): ?string {
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) === 1) {
			[$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
		} else if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $raw, $m) === 1) {
			[$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
		} else if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/', $raw, $m) === 1) {
			$month = (self::MONTHS[strtolower($m[2])] ?? 0);
			[$day, $year] = [(int) $m[1], (int) $m[3]];
		} else {
			return null;
		}

		if (checkdate($month, $day, $year) === false) {
			return null;
		}

		return sprintf('%04d-%02d-%02d', $year, $month, $day);

	}//end normaliseDate()

	/**
	 * The notice period, in days.
	 *
	 * @param string $text The contract text.
	 *
	 * @return array{field: string, value: string, confidence: float}|null The proposal.
	 */
	private function noticePeriod(string $text): ?array {
		$pattern = '/(?:opzegtermijn|notice period)\s*(?:van|of|:)?\s*(\d{1,4})\s*(dagen|dag|days|day|weken|week|weeks|maanden|maand|months|month)\b/iu';
		if (preg_match($pattern, $text, $match) !== 1) {
			return null;
		}

		[$perUnit, $confidence] = self::UNITS[strtolower($match[2])];

		return ['field' => 'noticePeriodDays', 'value' => (string) ((int) $match[1] * $perUnit), 'confidence' => $confidence];

	}//end noticePeriod()

	/**
	 * The contract value and its currency.
	 *
	 * @param string $text The contract text.
	 *
	 * @return list<array{field: string, value: string, confidence: float}> The proposals.
	 */
	private function value(string $text): array {
		$pattern = '/(contractwaarde|waarde|bedrag|totaal|value|amount)?[^€\d]{0,30}(?:€|EUR)\s?(\d{1,3}(?:[.,\s]\d{3})*(?:[.,]\d{2})?|\d+(?:[.,]\d{2})?)/iu';
		if (preg_match($pattern, $text, $match) !== 1) {
			return [];
		}

		$amount = $this->normaliseAmount(raw: $match[2]);
		if ($amount === null) {
			return [];
		}

		$confidence = 0.5;
		if ($match[1] !== '') {
			$confidence = 0.75;
		}

		return [
			['field' => 'value', 'value' => $amount, 'confidence' => $confidence],
			['field' => 'currency', 'value' => 'EUR', 'confidence' => $confidence],
		];

	}//end value()

	/**
	 * An amount written the Dutch (240.000,00) or English (240,000.00) way, as a plain number.
	 *
	 * @param string $raw The amount as written.
	 *
	 * @return string|null The amount, or null when it is not one.
	 */
	private function normaliseAmount(string $raw): ?string {
		$raw = str_replace(' ', '', $raw);
		// The last separator followed by exactly two digits is the decimal one.
		if (preg_match('/^(.*)[.,](\d{2})$/', $raw, $m) === 1) {
			$whole = (string) preg_replace('/[.,]/', '', $m[1]);
			$number = $whole . '.' . $m[2];
		} else {
			$number = (string) preg_replace('/[.,]/', '', $raw);
		}

		if (is_numeric($number) === false) {
			return null;
		}

		if (str_contains($number, '.') === true) {
			$number = rtrim(rtrim($number, '0'), '.');
		}

		return $number;

	}//end normaliseAmount()

	/**
	 * A counterparty named after its role.
	 *
	 * @param string $text The contract text.
	 *
	 * @return list<array{field: string, value: string, confidence: float}> The proposals.
	 */
	private function party(string $text): array {
		$pattern = '/(?:opdrachtnemer|leverancier|supplier|contractor)\s*:\s*([^,;:()]{2,80}?)(?:,|;|\(|\s+(?:gevestigd|located|hierna|hereinafter)\b|$)/iu';
		if (preg_match($pattern, $text, $match) !== 1) {
			return [];
		}

		return [['field' => 'party', 'value' => trim($match[1]), 'confidence' => 0.6]];

	}//end party()
}//end class
