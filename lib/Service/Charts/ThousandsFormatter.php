<?php

/**
 * Thousands Formatter
 *
 * Locale-independent NL-style number formatting shared by the chart and table
 * renderers. Extracted so both renderers format numbers through one
 * implementation instead of carrying byte-identical copies.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Charts;

/**
 * Formats numbers with NL-style thousands separators, deterministically.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class ThousandsFormatter {
	/**
	 * Format a number with NL-style thousands separators, independent of
	 * environment locale (deterministic across containers).
	 *
	 * @param float $value Value to format.
	 * @param int $decimals Number of decimal places.
	 *
	 * @return string Formatted number, e.g. '1.234,56'.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
	 */
	public function format(float $value, int $decimals): string {
		$fixed = sprintf('%.' . $decimals . 'f', $value);
		$negative = str_starts_with($fixed, '-');
		if ($negative === true) {
			$fixed = substr($fixed, 1);
		}

		$parts = explode('.', $fixed);
		$decimalPart = $parts[1] ?? '';

		$result = $this->group(wholePart: $parts[0]);
		if ($decimals > 0) {
			$result .= ',' . $decimalPart;
		}

		$sign = '';
		if ($negative === true) {
			$sign = '-';
		}

		return $sign . $result;
	}//end format()

	/**
	 * Insert '.' group separators into the whole-number part of a number.
	 *
	 * @param string $wholePart The digits before the decimal separator.
	 *
	 * @return string The grouped digits.
	 */
	private function group(string $wholePart): string {
		$grouped = '';
		$len = strlen($wholePart);
		for ($i = 0; $i < $len; $i++) {
			if ($i > 0 && ($len - $i) % 3 === 0) {
				$grouped .= '.';
			}

			$grouped .= $wholePart[$i];
		}

		return $grouped;
	}//end group()
}//end class
