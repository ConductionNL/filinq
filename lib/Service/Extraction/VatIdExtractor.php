<?php

/**
 * VAT Id (BTW-nummer) Extractor
 *
 * Pure, side-effect-free heuristic extractor that locates a Dutch VAT
 * identification number (`NL` + 9 digits + `B` + 2 digits) in free text
 * (REQ-FIN-02).
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
 * Extracts a Dutch BTW-nummer (VAT id) from free text.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-2
 */
class VatIdExtractor {

	/**
	 * Confidence assigned to a format-valid BTW-nummer match.
	 *
	 * @var float
	 */
	private const FORMAT_CONFIDENCE = 0.9;

	/**
	 * Extract a Dutch BTW-nummer (`NL` + 9 digits + `B` + 2 digits) from text.
	 *
	 * @param string $text The text to search.
	 *
	 * @return array{value: string|null, confidence: float} The extracted
	 *                                                      VAT id and its confidence, or a null value with confidence 0
	 *                                                      when no format-valid candidate is found.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function extract(string $text): array {
		$matched = preg_match('/\bNL[0-9]{9}B[0-9]{2}\b/i', $text, $matches);

		if ($matched === 1) {
			return [
				'value' => strtoupper($matches[0]),
				'confidence' => self::FORMAT_CONFIDENCE,
			];
		}

		return ['value' => null, 'confidence' => 0.0];
	}//end extract()
}//end class
