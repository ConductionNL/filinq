<?php

/**
 * IBAN Extractor
 *
 * Pure, side-effect-free heuristic extractor that locates IBAN candidates in
 * free text and validates them with the ISO 13616 mod-97 checksum. A
 * checksum failure is never returned as a value — the extractor prefers no
 * value over an invalid one (REQ-FIN-02).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

/**
 * Extracts and mod-97-validates IBAN candidates from free text.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-1
 */
class IbanExtractor {

	/**
	 * Confidence assigned to a checksum-valid IBAN.
	 *
	 * @var float
	 */
	private const VALID_CONFIDENCE = 0.99;

	/**
	 * Extract the first checksum-valid IBAN from text.
	 *
	 * @param string $text The text to search.
	 *
	 * @return array{value: string|null, confidence: float} The extracted
	 *                                                      IBAN and its confidence, or a null value with confidence 0
	 *                                                      when no checksum-valid candidate is found.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function extract(string $text): array {
		$normalised = strtoupper($text);

		$matchCount = preg_match_all('/\b[A-Z]{2}[0-9]{2}[A-Z0-9]{9,30}\b/', $normalised, $matches);
		if ($matchCount === false || $matchCount === 0) {
			return ['value' => null, 'confidence' => 0.0];
		}

		foreach ($matches[0] as $candidate) {
			$candidate = str_replace(' ', '', $candidate);
			if ($this->isValidMod97(iban: $candidate) === true) {
				return [
					'value' => $candidate,
					'confidence' => self::VALID_CONFIDENCE,
				];
			}
		}

		// No checksum-valid candidate — prefer no value over an invalid one.
		return ['value' => null, 'confidence' => 0.0];
	}//end extract()

	/**
	 * Validate an IBAN candidate using the ISO 13616 mod-97 checksum.
	 *
	 * @param string $iban The candidate IBAN (uppercase, no spaces).
	 *
	 * @return bool True when the checksum is valid.
	 */
	private function isValidMod97(string $iban): bool {
		if (strlen($iban) < 15 || strlen($iban) > 34) {
			return false;
		}

		// Move the first four characters to the end.
		$rearranged = substr($iban, 4) . substr($iban, 0, 4);

		// Convert letters to numbers (A=10 .. Z=35).
		$numeric = '';
		foreach (str_split($rearranged) as $char) {
			if (ctype_alpha($char) === true) {
				$numeric .= (string)(ord($char) - 55);
				continue;
			}

			if (ctype_digit($char) === false) {
				return false;
			}

			$numeric .= $char;
		}

		// Mod-97 on a large numeric string via chunked remainder arithmetic.
		$remainder = 0;
		foreach (str_split($numeric) as $digit) {
			$remainder = (int)(($remainder * 10 + (int)$digit) % 97);
		}

		return $remainder === 1;
	}//end isValidMod97()
}//end class
