<?php

/**
 * Cartesian Chart Renderer
 *
 * What the bar and line chart renderers share: the SVG, label, scale, frame
 * and legend collaborators, whether a chart shows its legend, and the plot
 * area scaled to the series' maximum.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

/**
 * Shared base of the chart types drawn on x/y axes.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md
 */
abstract class CartesianChartRenderer {

	/**
	 * SVG element builders.
	 *
	 * @var SvgPrimitives
	 */
	protected readonly SvgPrimitives $svg;

	/**
	 * Label text formatter.
	 *
	 * @var ChartLabelFormatter
	 */
	protected readonly ChartLabelFormatter $labels;

	/**
	 * Axis scaling arithmetic.
	 *
	 * @var ChartScale
	 */
	protected readonly ChartScale $scale;

	/**
	 * Plot geometry and chrome.
	 *
	 * @var CartesianFrame
	 */
	protected readonly CartesianFrame $frame;

	/**
	 * Series legend renderer.
	 *
	 * @var ChartLegendRenderer
	 */
	protected readonly ChartLegendRenderer $legend;

	/**
	 * Constructor.
	 *
	 * Collaborators are pure, stateless helpers with no I/O, so they are
	 * composed here rather than injected — this keeps the public constructor
	 * argument-free for both the DI container and direct instantiation.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->svg = new SvgPrimitives();
		$this->labels = new ChartLabelFormatter();
		$this->scale = new ChartScale();
		$this->frame = new CartesianFrame();
		$this->legend = new ChartLegendRenderer();

	}//end __construct()

	/**
	 * Whether the chart shows its legend: as asked, else when it has more than one series.
	 *
	 * @param ChartRequest $chart The chart.
	 *
	 * @return bool True to draw the legend.
	 */
	protected function showsLegend(ChartRequest $chart): bool {
		$series = $chart->normalized['series'];
		return (bool)($chart->options['showLegend'] ?? (count($series) > 1));

	}//end showsLegend()

	/**
	 * The plot area, with the axis ceiling of the series' maximum as `niceMax`.
	 *
	 * @param ChartRequest $chart      The chart.
	 * @param bool         $showLegend Whether room is kept for the legend.
	 *
	 * @return array<string, mixed> The plot area.
	 */
	protected function scaledPlot(ChartRequest $chart, bool $showLegend): array {
		$plot = $this->frame->layout(chart: $chart, showLegend: $showLegend);
		$plot['niceMax'] = $this->scale->axisCeiling(value: $this->scale->seriesMax(series: $chart->normalized['series']));
		return $plot;

	}//end scaledPlot()
}//end class
