<?php

/**
 * Line Chart Renderer
 *
 * Draws the line chart type as self-contained SVG: one polyline per series
 * with a break wherever a point was skipped, plus point markers.
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
 * Renders line charts as deterministic SVG.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
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
	 * @param array $normalized Normalized `{labels, series}` data.
	 * @param array $palette Ordered hex colors.
	 * @param int $width Canvas width.
	 * @param int $height Canvas height.
	 * @param array $options Rendering options.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(array $normalized, array $palette, int $width, int $height, array $options): string {
		$labels = $normalized['labels'];
		$series = $normalized['series'];
		$title = (string)($options['title'] ?? '');
		$showLegend = (bool)($options['showLegend'] ?? (count($series) > 1));

		$layout = $this->frame->layout(hasTitle: ($title !== ''), showLegend: $showLegend, width: $width, height: $height);
		$niceMax = $this->scale->axisCeiling(value: $this->scale->seriesMax(series: $series));
		$stepX = $layout['plotWidth'] / max(1, ($this->lineSteps(numLabels: count($labels))));

		$parts = [];
		$parts[] = $this->frame->render(
			layout: $layout,
			title: $title,
			valueFmt: (string)($options['valueFormat'] ?? 'integer'),
			niceMax: $niceMax,
			width: $width,
			height: $height
		);
		$parts[] = $this->drawSeries(
			series: $series,
			palette: $palette,
			layout: $layout,
			niceMax: $niceMax,
			stepX: $stepX,
			numLabels: count($labels)
		);
		$parts[] = $this->drawCategoryLabels(labels: $labels, layout: $layout, stepX: $stepX);

		if ($showLegend === true) {
			$parts[] = $this->legend->render(
				series: $series,
				palette: $palette,
				x: $layout['marginLeft'],
				y: $height - 12,
				maxEntries: ChartLabelFormatter::MAX_LABELLED_ENTRIES
			);
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
	 * @param array $series Normalized series list.
	 * @param array $palette Ordered hex colors.
	 * @param array $layout Geometry from {@see CartesianFrame::layout()}.
	 * @param float $niceMax Axis ceiling value.
	 * @param float $stepX Horizontal distance between category positions.
	 * @param int $numLabels Number of category labels.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawSeries(array $series, array $palette, array $layout, float $niceMax, float $stepX, int $numLabels): string {
		$parts = [];
		$numSeries = count($series);

		for ($s = 0; $s < $numSeries; $s++) {
			$color = $palette[$s % count($palette)];
			$points = [];
			$segment = [];

			for ($i = 0; $i < $numLabels; $i++) {
				$value = $series[$s]['values'][$i] ?? null;
				if ($value === null) {
					if ($segment !== []) {
						$parts[] = $this->svg->polylineEl(points: $segment, color: $color);
						$segment = [];
					}

					continue;
				}

				$x = $layout['marginLeft'] + ($i * $stepX);
				$y = $layout['baselineY'] - (($value / $niceMax) * $layout['plotHeight']);
				$segment[] = [$x, $y];
				$points[] = [$x, $y];
			}

			if ($segment !== []) {
				$parts[] = $this->svg->polylineEl(points: $segment, color: $color);
			}

			foreach ($points as [$px, $py]) {
				$parts[] = $this->svg->circleEl(cx: $px, cy: $py, radius: 2.5, color: $color);
			}
		}//end for

		return implode('', $parts);
	}//end drawSeries()

	/**
	 * Draw the x-axis category labels, bounded by the label-volume cap.
	 *
	 * @param array $labels Category labels.
	 * @param array $layout Geometry from {@see CartesianFrame::layout()}.
	 * @param float $stepX Horizontal distance between category positions.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawCategoryLabels(array $labels, array $layout, float $stepX): string {
		$numLabels = count($labels);
		if ($numLabels > ChartLabelFormatter::MAX_LABELLED_ENTRIES) {
			return '';
		}

		$parts = [];
		for ($i = 0; $i < $numLabels; $i++) {
			$parts[] = $this->svg->textEl(
				x: $layout['marginLeft'] + ($i * $stepX),
				y: $layout['baselineY'] + 16,
				text: $this->labels->truncate(text: $labels[$i], max: 14),
				anchor: 'middle',
				size: 9,
				color: '#333333'
			);
		}

		return implode('', $parts);
	}//end drawCategoryLabels()
}//end class
