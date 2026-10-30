<?php

/**
 * Bar Chart Renderer
 *
 * Draws the bar chart type as self-contained SVG in both orientations:
 * vertical (grouped by series) and horizontal (the ranked-list orientation,
 * first series only).
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
 * Renders bar charts as deterministic SVG.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
 */
class BarChartRenderer {

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
	 * Render a bar chart (vertical, grouped by series; or horizontal, first
	 * series only — the ranked-list orientation).
	 *
	 * @param ChartRequest $chart The normalized data, palette, canvas size and options.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(ChartRequest $chart): string {
		if (($chart->options['orientation'] ?? 'vertical') === 'horizontal') {
			return $this->renderHorizontal(chart: $chart);
		}

		$series = $chart->normalized['series'];
		$showLegend = (bool)($chart->options['showLegend'] ?? (count($series) > 1));

		$plot = $this->frame->layout(chart: $chart, showLegend: $showLegend);
		$plot['niceMax'] = $this->scale->axisCeiling(value: $this->scale->seriesMax(series: $series));

		$parts = [];
		$parts[] = $this->frame->render(chart: $chart, plot: $plot);
		$parts[] = $this->drawBars(chart: $chart, plot: $plot);

		if ($showLegend === true) {
			$parts[] = $this->legend->render(chart: $chart, x: $plot['marginLeft']);
		}

		$parts[] = '</svg>';

		return implode('', $parts);
	}//end render()

	/**
	 * Draw the grouped bars plus their value and category labels.
	 *
	 * @param ChartRequest $chart The normalized data, palette and value format.
	 * @param array $plot Geometry from {@see CartesianFrame::layout()} plus the axis ceiling `niceMax`.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawBars(ChartRequest $chart, array $plot): string {
		$niceMax = $plot['niceMax'];
		$valueFmt = $chart->valueFormat();
		$labels = $chart->normalized['labels'];
		$series = $chart->normalized['series'];
		$numLabels = count($labels);
		$numSeries = count($series);
		$baselineY = $plot['baselineY'];
		$labelled = ($numLabels <= ChartLabelFormatter::MAX_LABELLED_ENTRIES);

		$groupWidth = $plot['plotWidth'] / max(1, $numLabels);
		$barGap = $groupWidth * 0.18;
		$barWidth = ($groupWidth - (2 * $barGap)) / max(1, $numSeries);

		$parts = [];
		for ($i = 0; $i < $numLabels; $i++) {
			$groupX = $plot['marginLeft'] + ($i * $groupWidth);

			for ($s = 0; $s < $numSeries; $s++) {
				$value = $series[$s]['values'][$i] ?? null;
				if ($value === null) {
					continue;
				}

				$barHeight = max(0.0, (($value / $niceMax) * $plot['plotHeight']));
				$x = $groupX + $barGap + ($s * $barWidth);
				$y = $baselineY - $barHeight;

				$parts[] = $this->svg->rectEl(
					box: new SvgBox(origin: new SvgPoint(x: $x, y: $y), size: new SvgSize(width: $barWidth * 0.86, height: $barHeight)),
					color: $chart->color(index: $s)
				);

				if ($labelled === true && $barHeight > 12) {
					$parts[] = $this->svg->textEl(
						position: new SvgPoint(x: $x + (($barWidth * 0.86) / 2), y: $y - 4),
						text: new SvgText(content: $this->labels->formatValue(value: $value, format: $valueFmt), style: SvgTextStyle::LabelMiddle)
					);
				}
			}//end for

			if ($labelled === true) {
				$parts[] = $this->svg->textEl(
					position: new SvgPoint(x: $groupX + ($groupWidth / 2), y: $baselineY + 16),
					text: new SvgText(content: $this->labels->truncate(text: $labels[$i], max: 14), style: SvgTextStyle::LabelMiddle)
				);
			}
		}//end for

		return implode('', $parts);
	}//end drawBars()

	/**
	 * Render a horizontal bar chart (ranked-list orientation, first series only).
	 *
	 * @param ChartRequest $chart The normalized data, palette, canvas size and options.
	 *
	 * @return string SVG markup.
	 */
	private function renderHorizontal(ChartRequest $chart): string {
		$width = $chart->width;
		$values = $chart->normalized['series'][0]['values'];
		$title = $chart->title();

		$marginTop = 14;
		if ($title !== '') {
			$marginTop = 34;
		}

		$marginLeft = 130;
		$plotWidth = (float)max(1, $width - $marginLeft - 50);
		$plotHeight = (float)max(1, $chart->height - $marginTop - 12);

		$numericValues = array_filter($values, static fn ($v) => $v !== null);
		if ($numericValues === []) {
			$numericValues = [0];
		}

		$niceMax = $this->scale->axisCeiling(value: (float)max($numericValues));

		$parts = [];
		$parts[] = $this->svg->svgOpenTag(width: $width, height: $chart->height);

		if ($title !== '') {
			$parts[] = $this->svg->textEl(position: new SvgPoint(x: $width / 2, y: 18), text: new SvgText(content: $title, style: SvgTextStyle::Title));
		}

		$parts[] = $this->drawRows(
			chart: $chart,
			geometry: [
				'marginTop' => $marginTop,
				'marginLeft' => $marginLeft,
				'plotWidth' => $plotWidth,
				'rowHeight' => ($plotHeight / max(1, count($chart->normalized['labels']))),
				'niceMax' => $niceMax,
			]
		);

		$parts[] = '</svg>';

		return implode('', $parts);
	}//end renderHorizontal()

	/**
	 * Draw one labelled horizontal bar row per category.
	 *
	 * Draws the first series (null marks a gap) in the first palette color.
	 *
	 * @param ChartRequest $chart The labels, first-series values, palette and value format.
	 * @param array $geometry `{marginTop, marginLeft, plotWidth, rowHeight, niceMax}`.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawRows(ChartRequest $chart, array $geometry): string {
		$labels = $chart->normalized['labels'];
		$values = $chart->normalized['series'][0]['values'];
		$color = $chart->palette[0];
		$valueFmt = $chart->valueFormat();
		$niceMax = $geometry['niceMax'];
		$marginLeft = $geometry['marginLeft'];
		$rowHeight = $geometry['rowHeight'];
		$barHeight = $rowHeight * 0.6;
		$numLabels = count($labels);

		$parts = [];
		for ($i = 0; $i < $numLabels; $i++) {
			$value = $values[$i] ?? null;
			$rowY = $geometry['marginTop'] + ($i * $rowHeight);

			$parts[] = $this->svg->textEl(
				position: new SvgPoint(x: $marginLeft - 8, y: $rowY + ($rowHeight / 2) + 3),
				text: new SvgText(content: $this->labels->truncate(text: $labels[$i], max: 22), style: SvgTextStyle::LabelEnd)
			);

			if ($value === null) {
				continue;
			}

			$barWidth = max(0.0, (($value / $niceMax) * $geometry['plotWidth']));
			$barY = $rowY + (($rowHeight - $barHeight) / 2);

			$bar = new SvgBox(origin: new SvgPoint(x: $marginLeft, y: $barY), size: new SvgSize(width: $barWidth, height: $barHeight));
			$parts[] = $this->svg->rectEl(box: $bar, color: $color);
			$parts[] = $this->svg->textEl(
				position: new SvgPoint(x: $marginLeft + $barWidth + 6, y: $barY + ($barHeight / 2) + 3),
				text: new SvgText(content: $this->labels->formatValue(value: $value, format: $valueFmt), style: SvgTextStyle::LabelStart)
			);
		}//end for

		return implode('', $parts);
	}//end drawRows()
}//end class
