<?php

/**
 * KvK Extractor
 *
 * Pure, side-effect-free heuristic extractor that locates a Dutch Chamber of
 * Commerce (KvK) number — 8 digits, conventionally preceded by a `KvK`
 * label — in free text (REQ-FIN-02).
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
 * Extracts an 8-digit KvK number from free text.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-2
 */
class KvkExtractor {

	/**
	 * Confidence assigned to a labelled KvK-number match.
	 *
	 * @var float
	 */
	private const LABELLED_CONFIDENCE = 0.85;

	/**
	 * Extract a KvK number labelled by "KvK" (or "Kamer van Koophandel") from text.
	 *
	 * @param string $text The text to search.
	 *
	 * @return array{value: string|null, confidence: float} The extracted
	 *                                                      KvK number and its confidence, or a null value with
	 *                                                      confidence 0 when no labelled candidate is found.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function extract(string $text): array {
		$matched = preg_match(
			'/(?:KvK|Kamer\s*van\s*Koophandel)[.:\s-]{0,10}([0-9]{8})\b/i',
			$text,
			$matches
		);

		if ($matched === 1) {
			return [
				'value' => $matches[1],
				'confidence' => self::LABELLED_CONFIDENCE,
			];
		}

		return ['value' => null, 'confidence' => 0.0];
	}//end extract()
}//end class
