<?php

/**
 * Totals Reconciler
 *
 * Pure, side-effect-free helper that checks whether
 * `totalExcl + totalVat ≈ totalIncl` within a rounding tolerance, so the
 * orchestrating service can boost the confidence of amount fields that
 * reconcile (REQ-FIN-03).
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
 * Reconciles totalExcl + totalVat against totalIncl within a tolerance.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-4
 */
class TotalsReconciler {

	/**
	 * Default rounding tolerance (in currency units) for reconciliation.
	 *
	 * @var float
	 */
	private const DEFAULT_TOLERANCE = 0.01;

	/**
	 * Check whether totalExcl + totalVat reconciles with totalIncl.
	 *
	 * All three values must be present (non-null) for reconciliation to be
	 * possible; a missing value always yields false.
	 *
	 * @param float|null $totalExcl The excl.-VAT total, or null.
	 * @param float|null $totalVat The VAT amount, or null.
	 * @param float|null $totalIncl The incl.-VAT total, or null.
	 * @param float $tolerance Rounding tolerance in currency units.
	 *
	 * @return bool True when all three values are present and reconcile
	 *              within the tolerance.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function reconciles(
		?float $totalExcl,
		?float $totalVat,
		?float $totalIncl,
		float $tolerance = self::DEFAULT_TOLERANCE,
	): bool {
		if ($totalExcl === null || $totalVat === null || $totalIncl === null) {
			return false;
		}

		return abs(($totalExcl + $totalVat) - $totalIncl) <= $tolerance;
	}//end reconciles()
}//end class
