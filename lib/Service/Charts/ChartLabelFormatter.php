<?php
/**
 * Chart Label Formatter
 *
 * Turns raw values and category names into the short, deterministic label text
 * the chart renderers draw on axes, bars, slices, and legends.
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
 * Formats on-chart value and category label text.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class ChartLabelFormatter
{

    /**
     * Maximum number of legend/slice/category label entries rendered before
     * the renderers stop emitting individual text labels (chart shapes are
     * still drawn; this only bounds label text volume for very wide series).
     *
     * @var int
     */
    public const MAX_LABELLED_ENTRIES = 20;

    /**
     * Number formatter used for the integer and currency formats.
     *
     * @var ThousandsFormatter
     */
    private readonly ThousandsFormatter $thousands;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->thousands = new ThousandsFormatter();

    }//end __construct()

    /**
     * Format a numeric value for on-chart labels.
     *
     * @param float  $value  The value to format.
     * @param string $format 'integer' (default), 'decimal:N', 'currency', or 'percent'.
     *
     * @return string Formatted value.
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function formatValue(float $value, string $format): string
    {
        if ($format === 'percent') {
            return sprintf('%.0f%%', $value * 100);
        }

        if (str_starts_with($format, 'decimal:') === true) {
            $precision = (int) substr($format, 8);
            $precision = max(0, min($precision, 6));
            return sprintf('%.'.$precision.'f', $value);
        }

        if ($format === 'currency') {
            return '€ '.$this->thousands->format(value: $value, decimals: 2);
        }

        // Default: integer.
        return $this->thousands->format(value: round($value), decimals: 0);

    }//end formatValue()

    /**
     * Truncate text to a maximum character length with an ellipsis marker.
     *
     * @param string $text Source text.
     * @param int    $max  Maximum character length.
     *
     * @return string Truncated text.
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function truncate(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, max(1, $max - 1)).'…';

    }//end truncate()
}//end class
