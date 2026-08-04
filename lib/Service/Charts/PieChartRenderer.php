<?php
/**
 * Pie Chart Renderer
 *
 * Draws the pie (and donut) chart type as self-contained SVG from the first
 * series of a normalized chart payload.
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
 * Renders pie and donut charts as deterministic SVG.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class PieChartRenderer
{

    /**
     * SVG element builders.
     *
     * @var SvgPrimitives
     */
    private readonly SvgPrimitives $svg;

    /**
     * Label text formatter.
     *
     * @var ChartLabelFormatter
     */
    private readonly ChartLabelFormatter $labels;

    /**
     * Constructor.
     *
     * Collaborators are pure, stateless helpers with no I/O, so they are
     * composed here rather than injected — this keeps the public constructor
     * argument-free for both the DI container and direct instantiation.
     *
     * @return void
     */
    public function __construct()
    {
        $this->svg    = new SvgPrimitives();
        $this->labels = new ChartLabelFormatter();

    }//end __construct()

    /**
     * Render a pie (or donut) chart from the first series.
     *
     * @param array $normalized Normalized `{labels, series}` data.
     * @param array $palette    Ordered hex colors.
     * @param int   $width      Canvas width.
     * @param int   $height     Canvas height.
     * @param array $options    Rendering options.
     *
     * @return string|ChartRenderError SVG markup, or the reason no chart could
     *                                 be drawn (the caller renders a placeholder).
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function render(array $normalized, array $palette, int $width, int $height, array $options): string|ChartRenderError
    {
        $sliced = $this->collectSlices(labels: $normalized['labels'], values: $normalized['series'][0]['values']);
        if ($sliced['total'] <= 0.0 || $sliced['slices'] === []) {
            return new ChartRenderError(message: 'chart error: no data');
        }

        $title      = (string) ($options['title'] ?? '');
        $showLegend = (bool) ($options['showLegend'] ?? true);

        $geometry = $this->geometry(
            hasTitle: ($title !== ''),
            showLegend: $showLegend,
            donut: (bool) ($options['donut'] ?? false),
            width: $width,
            height: $height
        );

        $parts   = [];
        $parts[] = $this->svg->svgOpenTag(width: $width, height: $height);

        if ($title !== '') {
            $parts[] = $this->svg->textEl(x: $width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
        }

        $parts[] = $this->drawSlices(
            slices: $sliced['slices'],
            total: $sliced['total'],
            palette: $palette,
            geometry: $geometry
        );

        if ($geometry['innerRadius'] > 0.0) {
            $parts[] = $this->svg->circleEl(
                cx: $geometry['cx'],
                cy: $geometry['cy'],
                radius: $geometry['innerRadius'],
                color: '#FFFFFF'
            );
        }

        if ($showLegend === true) {
            $parts[] = $this->drawLegend(
                slices: $sliced['slices'],
                total: $sliced['total'],
                palette: $palette,
                geometry: $geometry
            );
        }

        $parts[] = '</svg>';

        return implode('', $parts);

    }//end render()

    /**
     * Collect the drawable (positive) slices and their total.
     *
     * @param array $labels Category labels.
     * @param array $values First-series values.
     *
     * @return array{slices: array<int, array{label: string, value: float}>, total: float}
     */
    private function collectSlices(array $labels, array $values): array
    {
        $slices = [];
        $total  = 0.0;
        foreach ($values as $i => $value) {
            if ($value === null || $value <= 0.0) {
                continue;
            }

            $slices[] = [
                'label' => $labels[$i],
                'value' => $value,
            ];
            $total   += $value;
        }

        return [
            'slices' => $slices,
            'total'  => $total,
        ];

    }//end collectSlices()

    /**
     * Compute the pie geometry: plot area, center, radius, and donut hole.
     *
     * @param bool $hasTitle   Whether a chart title is drawn.
     * @param bool $showLegend Whether a legend column is drawn.
     * @param bool $donut      Whether a donut hole is punched out.
     * @param int  $width      Canvas width.
     * @param int  $height     Canvas height.
     *
     * @return array{marginTop: int, plotWidth: float, cx: float, cy: float, radius: float, innerRadius: float}
     */
    private function geometry(bool $hasTitle, bool $showLegend, bool $donut, int $width, int $height): array
    {
        $marginTop = 14;
        if ($hasTitle === true) {
            $marginTop = 34;
        }

        $legendWidth = 0;
        if ($showLegend === true) {
            $legendWidth = min(220, (int) ($width * 0.4));
        }

        $plotWidth  = (float) ($width - $legendWidth);
        $plotHeight = (float) ($height - $marginTop - 14);
        $radius     = max(10.0, ((min($plotWidth, $plotHeight) / 2) - 8));

        $innerRadius = 0.0;
        if ($donut === true) {
            $innerRadius = $radius * 0.55;
        }

        return [
            'marginTop'   => $marginTop,
            'plotWidth'   => $plotWidth,
            'cx'          => ($plotWidth / 2),
            'cy'          => ($marginTop + ($plotHeight / 2)),
            'radius'      => $radius,
            'innerRadius' => $innerRadius,
        ];

    }//end geometry()

    /**
     * Render the pie/donut slice shapes: a single non-zero slice as a full
     * `<circle>` (the 360° sweep arc-math edge case), otherwise one `<path>`
     * arc sector per slice.
     *
     * @param array $slices   `[{label, value}, ...]` — already filtered to
     *                        non-zero, positive values.
     * @param float $total    Sum of all slice values.
     * @param array $palette  Ordered hex colors.
     * @param array $geometry Geometry from {@see geometry()}.
     *
     * @return string SVG markup fragment.
     */
    private function drawSlices(array $slices, float $total, array $palette, array $geometry): string
    {
        $cx     = $geometry['cx'];
        $cy     = $geometry['cy'];
        $radius = $geometry['radius'];

        if (count($slices) === 1) {
            return $this->svg->circleEl(cx: $cx, cy: $cy, radius: $radius, color: $palette[0]);
        }

        $parts = [];
        $angle = -90.0;
        foreach ($slices as $index => $slice) {
            $endAngle = $angle + ((($slice['value'] / $total) * 360.0));
            $parts[]  = $this->svg->pieSliceEl(
                cx: $cx,
                cy: $cy,
                radius: $radius,
                startAngle: $angle,
                endAngle: $endAngle,
                color: $palette[(int) $index % count($palette)]
            );
            $angle    = $endAngle;
        }

        return implode('', $parts);

    }//end drawSlices()

    /**
     * Render the slice legend column with per-slice percentage shares.
     *
     * @param array $slices   `[{label, value}, ...]`.
     * @param float $total    Sum of all slice values.
     * @param array $palette  Ordered hex colors.
     * @param array $geometry Geometry from {@see geometry()}.
     *
     * @return string SVG markup fragment.
     */
    private function drawLegend(array $slices, float $total, array $palette, array $geometry): string
    {
        $legendX = $geometry['plotWidth'] + 12;
        $legendY = $geometry['marginTop'] + 6;

        $parts = [];
        $shown = 0;
        foreach ($slices as $index => $slice) {
            if ($shown >= ChartLabelFormatter::MAX_LABELLED_ENTRIES) {
                break;
            }

            $percent = round(($slice['value'] / $total) * 100);
            $rowY    = $legendY + ($shown * 16);

            $parts[] = $this->svg->rectEl(
                x: $legendX,
                y: $rowY - 8,
                width: 10,
                height: 10,
                color: $palette[(int) $index % count($palette)]
            );
            $parts[] = $this->svg->textEl(
                x: $legendX + 14,
                y: $rowY + 1,
                text: $this->labels->truncate(text: $slice['label'], max: 20).' ('.$percent.'%)',
                anchor: 'start',
                size: 9,
                color: '#333333'
            );
            $shown++;
        }//end foreach

        return implode('', $parts);

    }//end drawLegend()
}//end class
