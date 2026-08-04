<?php
/**
 * Chart Scale
 *
 * Axis scaling arithmetic for the cartesian (bar/line) chart renderers:
 * the maximum value across a normalized series list and the "nice" axis
 * ceiling that maximum is rounded up to.
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
 * Computes chart axis maxima and nice ceilings.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class ChartScale
{
    /**
     * Compute the maximum value across all series (ignoring skipped points).
     *
     * @param array $series Normalized series list.
     *
     * @return float Maximum value, or 0.0 when no numeric value is present.
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function seriesMax(array $series): float
    {
        $max = 0.0;
        foreach ($series as $oneSeries) {
            foreach ($oneSeries['values'] as $value) {
                if ($value !== null && $value > $max) {
                    $max = $value;
                }
            }
        }

        return $max;

    }//end seriesMax()

    /**
     * Round a value up to a "nice" axis maximum (1/2/5/10 × 10^n), never
     * returning a non-positive ceiling for a drawable chart.
     *
     * @param float $value Raw maximum value.
     *
     * @return float Nice ceiling value (always >= 1.0).
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function axisCeiling(float $value): float
    {
        $ceiling = $this->niceCeiling(value: $value);
        if ($ceiling <= 0.0) {
            return 1.0;
        }

        return $ceiling;

    }//end axisCeiling()

    /**
     * Round a value up to a "nice" axis maximum (1/2/5/10 × 10^n).
     *
     * @param float $value Raw maximum value.
     *
     * @return float Nice ceiling value.
     */
    private function niceCeiling(float $value): float
    {
        if ($value <= 0.0) {
            return 0.0;
        }

        $magnitude  = 10 ** floor(log10($value));
        $normalized = $value / $magnitude;

        if ($normalized <= 1.0) {
            return 1.0 * $magnitude;
        }

        if ($normalized <= 2.0) {
            return 2.0 * $magnitude;
        }

        if ($normalized <= 5.0) {
            return 5.0 * $magnitude;
        }

        return 10.0 * $magnitude;

    }//end niceCeiling()
}//end class
