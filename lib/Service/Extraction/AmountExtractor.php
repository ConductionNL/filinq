<?php

/**
 * Amount Extractor
 *
 * Pure, side-effect-free heuristic extractor that locates and parses
 * monetary amounts in both Dutch (`1.234,56`) and Anglo (`1,234.56`)
 * groupings, with an optional `€`/`EUR` marker (REQ-FIN-02).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Extraction;

/**
 * Extracts and parses monetary amounts (Dutch/Anglo grouping) from free text.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-4
 */
class AmountExtractor {

	/**
	 * Confidence assigned to an amount found immediately after a matched label.
	 *
	 * @var float
	 */
	private const LABELLED_CONFIDENCE = 0.75;

	/**
	 * The raw amount-token regex (without delimiters): an optional currency
	 * marker followed by digits, optional thousands groups, and a decimal
	 * part.
	 *
	 * @var string
	 */
	private const AMOUNT_TOKEN = '(?:€|EUR)?\s?[0-9]{1,3}(?:[.,][0-9]{3})*[.,][0-9]{2}';

	/**
	 * Parse a single raw amount token (Dutch or Anglo grouping) to a float.
	 *
	 * @param string $raw The raw amount text (e.g. "€ 1.234,56", "1,234.56").
	 *
	 * @return float|null The parsed numeric value, or null when unparseable.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function parseAmount(string $raw): ?float {
		$cleaned = trim(str_replace(['€', 'EUR', ' '], '', $raw));
		if ($cleaned === '') {
			return null;
		}

		$normalised = $this->normaliseGrouping(cleaned: $cleaned);
		if (is_numeric($normalised) === false) {
			return null;
		}

		return round((float)$normalised, 2);
	}//end parseAmount()

	/**
	 * Normalise Dutch/Anglo thousands+decimal grouping to a plain
	 * dot-decimal numeric string.
	 *
	 * @param string $cleaned Amount text with currency markers/spaces stripped.
	 *
	 * @return string The normalised (dot-decimal) numeric string.
	 */
	private function normaliseGrouping(string $cleaned): string {
		$hasComma = strpos($cleaned, ',') !== false;
		$hasDot = strpos($cleaned, '.') !== false;

		if ($hasComma === true && $hasDot === true) {
			return $this->normaliseMixedGrouping(cleaned: $cleaned);
		}

		if ($hasComma === true) {
			// Only commas: decimal comma if exactly two digits follow the
			// last one (Dutch "100,00"), otherwise a thousands separator.
			$afterLastComma = substr($cleaned, strrpos($cleaned, ',') + 1);
			if (strlen($afterLastComma) === 2) {
				return str_replace(',', '.', $cleaned);
			}

			return str_replace(',', '', $cleaned);
		}

		if ($hasDot === true) {
			// Only dots: decimal dot if exactly two digits follow the last
			// one, otherwise a thousands separator.
			$afterLastDot = substr($cleaned, strrpos($cleaned, '.') + 1);
			if (strlen($afterLastDot) === 2) {
				return $cleaned;
			}

			return str_replace('.', '', $cleaned);
		}

		return $cleaned;
	}//end normaliseGrouping()

	/**
	 * Normalise grouping when both `,` and `.` are present: whichever
	 * separator appears last is the decimal separator.
	 *
	 * @param string $cleaned Amount text containing both `,` and `.`.
	 *
	 * @return string The normalised (dot-decimal) numeric string.
	 */
	private function normaliseMixedGrouping(string $cleaned): string {
		$lastComma = strrpos($cleaned, ',');
		$lastDot = strrpos($cleaned, '.');

		if ($lastComma > $lastDot) {
			return str_replace(',', '.', str_replace('.', '', $cleaned));
		}

		return str_replace(',', '', $cleaned);
	}//end normaliseMixedGrouping()

	/**
	 * Extract every amount token in the text, in document order.
	 *
	 * @param string $text The text to search.
	 *
	 * @return array<int, array{raw: string, value: float}> Amounts found.
	 *
	 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-4
	 */
	public function extractAll(string $text): array {
		$matchCount = preg_match_all('/' . self::AMOUNT_TOKEN . '/', $text, $matches);
		if ($matchCount === false || $matchCount === 0) {
			return [];
		}

		$results = [];
		foreach ($matches[0] as $raw) {
			$value = $this->parseAmount(raw: $raw);
			if ($value === null) {
				continue;
			}

			$results[] = ['raw' => $raw, 'value' => $value];
		}

		return $results;
	}//end extractAll()

	/**
	 * Extract the first amount immediately following one of the given labels.
	 *
	 * @param string $text The text to search.
	 * @param array<string> $labels Case-insensitive labels (e.g. "totaal").
	 *
	 * @return array{value: float|null, confidence: float} The amount and its
	 *                                                     confidence, or a null value with confidence 0.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function extractLabelled(string $text, array $labels): array {
		if ($labels === []) {
			return ['value' => null, 'confidence' => 0.0];
		}

		$labelPattern = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $labels));

		// \b on both sides of the label so a longer word (e.g. "Subtotaal")
		// never matches a shorter label (e.g. "totaal") as a substring.
		$matched = preg_match(
			'/\b(?:' . $labelPattern . ')\b\s*[:\-]?\s*(' . self::AMOUNT_TOKEN . ')/i',
			$text,
			$matches
		);

		if ($matched === 1) {
			$value = $this->parseAmount(raw: $matches[1]);
			if ($value !== null) {
				return [
					'value' => $value,
					'confidence' => self::LABELLED_CONFIDENCE,
				];
			}
		}

		return ['value' => null, 'confidence' => 0.0];
	}//end extractLabelled()
}//end class
