<?php

/**
 * Line Chart Renderer
 *
 * Draws the line chart type as self-contained SVG: one polyline per series
 * with a break wherever a point was skipped, plus point markers.
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
 * Renders line charts as deterministic SVG.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
 */
class LineChartRenderer {

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
	 * Axis scaling arithmetic.
	 *
	 * @var ChartScale
	 */
	private readonly ChartScale $scale;

	/**
	 * Plot geometry and chrome.
	 *
	 * @var CartesianFrame
	 */
	private readonly CartesianFrame $frame;

	/**
	 * Series legend renderer.
	 *
	 * @var ChartLegendRenderer
	 */
	private readonly ChartLegendRenderer $legend;

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
	 * Render a line chart (one polyline per series, gaps for skipped values).
	 *
	 * @param ChartRequest $chart The normalized data, palette, canvas size and options.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(ChartRequest $chart): string {
		$labels = $chart->normalized['labels'];
		$series = $chart->normalized['series'];
		$showLegend = (bool)($chart->options['showLegend'] ?? (count($series) > 1));

		$plot = $this->frame->layout(chart: $chart, showLegend: $showLegend);
		$plot['niceMax'] = $this->scale->axisCeiling(value: $this->scale->seriesMax(series: $series));
		$plot['stepX'] = $plot['plotWidth'] / max(1, ($this->lineSteps(numLabels: count($labels))));

		$parts = [];
		$parts[] = $this->frame->render(chart: $chart, plot: $plot);
		$parts[] = $this->drawSeries(chart: $chart, plot: $plot);
		$parts[] = $this->drawCategoryLabels(labels: $labels, plot: $plot);

		if ($showLegend === true) {
			$parts[] = $this->legend->render(chart: $chart, x: $plot['marginLeft']);
		}

		$parts[] = '</svg>';

		return implode('', $parts);
	}//end render()

	/**
	 * The number of x-axis intervals between the category positions.
	 *
	 * @param int $numLabels Number of category labels.
	 *
	 * @return int The interval count (at least 1).
	 */
	private function lineSteps(int $numLabels): int {
		if (($numLabels - 1) > 0) {
			return ($numLabels - 1);
		}

		return 1;
	}//end lineSteps()

	/**
	 * Draw one polyline (broken at skipped points) plus point markers per series.
	 *
	 * @param ChartRequest $chart The normalized series, labels and palette.
	 * @param array $plot Geometry from {@see CartesianFrame::layout()} plus the axis
	 *                    ceiling `niceMax` and the category distance `stepX`.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawSeries(ChartRequest $chart, array $plot): string {
		$parts = [];
		$numSeries = count($chart->normalized['series']);
		for ($s = 0; $s < $numSeries; $s++) {
			$parts[] = $this->drawOneSeries(chart: $chart, plot: ($plot + ['series' => $s]));
		}

		return implode('', $parts);
	}//end drawSeries()

	/**
	 * Draw the polyline segments and point markers of one series.
	 *
	 * @param ChartRequest $chart The normalized series, labels and palette.
	 * @param array $plot The plot from {@see drawSeries()} plus the index `series` to draw.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawOneSeries(ChartRequest $chart, array $plot): string {
		$values = ($chart->normalized['series'][$plot['series']]['values'] ?? []);
		$numLabels = count($chart->normalized['labels']);
		$color = $chart->color(index: $plot['series']);
		$parts = [];
		$points = [];
		$segment = [];

		for ($i = 0; $i < $numLabels; $i++) {
			$value = $values[$i] ?? null;
			if ($value === null) {
				if ($segment !== []) {
					$parts[] = $this->svg->polylineEl(points: $segment, color: $color);
					$segment = [];
				}

				continue;
			}

			$x = $plot['marginLeft'] + ($i * $plot['stepX']);
			$y = $plot['baselineY'] - (($value / $plot['niceMax']) * $plot['plotHeight']);
			$segment[] = [$x, $y];
			$points[] = [$x, $y];
		}

		if ($segment !== []) {
			$parts[] = $this->svg->polylineEl(points: $segment, color: $color);
		}

		foreach ($points as [$px, $py]) {
			$parts[] = $this->svg->circleEl(cx: $px, cy: $py, radius: 2.5, color: $color);
		}

		return implode('', $parts);
	}//end drawOneSeries()

	/**
	 * Draw the x-axis category labels, bounded by the label-volume cap.
	 *
	 * @param array $labels Category labels.
	 * @param array $plot Geometry from {@see CartesianFrame::layout()} plus the category distance `stepX`.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawCategoryLabels(array $labels, array $plot): string {
		$stepX = $plot['stepX'];
		$numLabels = count($labels);
		if ($numLabels > ChartLabelFormatter::MAX_LABELLED_ENTRIES) {
			return '';
		}

		$parts = [];
		for ($i = 0; $i < $numLabels; $i++) {
			$parts[] = $this->svg->textEl(
				x: $plot['marginLeft'] + ($i * $stepX),
				y: $plot['baselineY'] + 16,
				text: $this->labels->truncate(text: $labels[$i], max: 14),
				anchor: 'middle',
				size: 9,
				color: '#333333'
			);
		}

		return implode('', $parts);
	}//end drawCategoryLabels()
}//end class
