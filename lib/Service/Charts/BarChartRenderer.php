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
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
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
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
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
		if (($options['orientation'] ?? 'vertical') === 'horizontal') {
			return $this->renderHorizontal(
				normalized: $normalized,
				palette: $palette,
				width: $width,
				height: $height,
				options: $options
			);
		}

		$series = $normalized['series'];
		$title = (string)($options['title'] ?? '');
		$showLegend = (bool)($options['showLegend'] ?? (count($series) > 1));

		$layout = $this->frame->layout(hasTitle: ($title !== ''), showLegend: $showLegend, width: $width, height: $height);
		$niceMax = $this->scale->axisCeiling(value: $this->scale->seriesMax(series: $series));

		$parts = [];
		$parts[] = $this->frame->render(
			layout: $layout,
			title: $title,
			valueFmt: (string)($options['valueFormat'] ?? 'integer'),
			niceMax: $niceMax,
			width: $width,
			height: $height
		);
		$parts[] = $this->drawBars(
			normalized: $normalized,
			palette: $palette,
			layout: $layout,
			niceMax: $niceMax,
			valueFmt: (string)($options['valueFormat'] ?? 'integer')
		);

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
	 * Draw the grouped bars plus their value and category labels.
	 *
	 * @param array $normalized Normalized `{labels, series}` data.
	 * @param array $palette Ordered hex colors.
	 * @param array $layout Geometry from {@see CartesianFrame::layout()}.
	 * @param float $niceMax Axis ceiling value.
	 * @param string $valueFmt Value label format.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawBars(array $normalized, array $palette, array $layout, float $niceMax, string $valueFmt): string {
		$labels = $normalized['labels'];
		$series = $normalized['series'];
		$numLabels = count($labels);
		$numSeries = count($series);
		$baselineY = $layout['baselineY'];
		$labelled = ($numLabels <= ChartLabelFormatter::MAX_LABELLED_ENTRIES);

		$groupWidth = $layout['plotWidth'] / max(1, $numLabels);
		$barGap = $groupWidth * 0.18;
		$barWidth = ($groupWidth - (2 * $barGap)) / max(1, $numSeries);

		$parts = [];
		for ($i = 0; $i < $numLabels; $i++) {
			$groupX = $layout['marginLeft'] + ($i * $groupWidth);

			for ($s = 0; $s < $numSeries; $s++) {
				$value = $series[$s]['values'][$i] ?? null;
				if ($value === null) {
					continue;
				}

				$barHeight = max(0.0, (($value / $niceMax) * $layout['plotHeight']));
				$x = $groupX + $barGap + ($s * $barWidth);
				$y = $baselineY - $barHeight;

				$parts[] = $this->svg->rectEl(
					x: $x,
					y: $y,
					width: $barWidth * 0.86,
					height: $barHeight,
					color: $palette[$s % count($palette)]
				);

				if ($labelled === true && $barHeight > 12) {
					$parts[] = $this->svg->textEl(
						x: $x + (($barWidth * 0.86) / 2),
						y: $y - 4,
						text: $this->labels->formatValue(value: $value, format: $valueFmt),
						anchor: 'middle',
						size: 9,
						color: '#333333'
					);
				}
			}//end for

			if ($labelled === true) {
				$parts[] = $this->svg->textEl(
					x: $groupX + ($groupWidth / 2),
					y: $baselineY + 16,
					text: $this->labels->truncate(text: $labels[$i], max: 14),
					anchor: 'middle',
					size: 9,
					color: '#333333'
				);
			}
		}//end for

		return implode('', $parts);
	}//end drawBars()

	/**
	 * Render a horizontal bar chart (ranked-list orientation, first series only).
	 *
	 * @param array $normalized Normalized `{labels, series}` data.
	 * @param array $palette Ordered hex colors.
	 * @param int $width Canvas width.
	 * @param int $height Canvas height.
	 * @param array $options Rendering options.
	 *
	 * @return string SVG markup.
	 */
	private function renderHorizontal(array $normalized, array $palette, int $width, int $height, array $options): string {
		$values = $normalized['series'][0]['values'];
		$title = (string)($options['title'] ?? '');

		$marginTop = 14;
		if ($title !== '') {
			$marginTop = 34;
		}

		$marginLeft = 130;
		$plotWidth = (float)max(1, $width - $marginLeft - 50);
		$plotHeight = (float)max(1, $height - $marginTop - 12);

		$numericValues = array_filter($values, static fn ($v) => $v !== null);
		if ($numericValues === []) {
			$numericValues = [0];
		}

		$niceMax = $this->scale->axisCeiling(value: (float)max($numericValues));

		$parts = [];
		$parts[] = $this->svg->svgOpenTag(width: $width, height: $height);

		if ($title !== '') {
			$parts[] = $this->svg->textEl(x: $width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
		}

		$parts[] = $this->drawRows(
			labels: $normalized['labels'],
			values: $values,
			color: $palette[0],
			valueFmt: (string)($options['valueFormat'] ?? 'integer'),
			geometry: [
				'marginTop' => $marginTop,
				'marginLeft' => $marginLeft,
				'plotWidth' => $plotWidth,
				'rowHeight' => ($plotHeight / max(1, count($normalized['labels']))),
			],
			niceMax: $niceMax
		);

		$parts[] = '</svg>';

		return implode('', $parts);
	}//end renderHorizontal()

	/**
	 * Draw one labelled horizontal bar row per category.
	 *
	 * @param array $labels Category labels.
	 * @param array $values First-series values (null marks a gap).
	 * @param string $color Bar fill color.
	 * @param string $valueFmt Value label format.
	 * @param array $geometry `{marginTop, marginLeft, plotWidth, rowHeight}`.
	 * @param float $niceMax Axis ceiling value.
	 *
	 * @return string SVG markup fragment.
	 */
	private function drawRows(array $labels, array $values, string $color, string $valueFmt, array $geometry, float $niceMax): string {
		$marginLeft = $geometry['marginLeft'];
		$rowHeight = $geometry['rowHeight'];
		$barHeight = $rowHeight * 0.6;
		$numLabels = count($labels);

		$parts = [];
		for ($i = 0; $i < $numLabels; $i++) {
			$value = $values[$i] ?? null;
			$rowY = $geometry['marginTop'] + ($i * $rowHeight);

			$parts[] = $this->svg->textEl(
				x: $marginLeft - 8,
				y: $rowY + ($rowHeight / 2) + 3,
				text: $this->labels->truncate(text: $labels[$i], max: 22),
				anchor: 'end',
				size: 9,
				color: '#333333'
			);

			if ($value === null) {
				continue;
			}

			$barWidth = max(0.0, (($value / $niceMax) * $geometry['plotWidth']));
			$barY = $rowY + (($rowHeight - $barHeight) / 2);

			$parts[] = $this->svg->rectEl(x: $marginLeft, y: $barY, width: $barWidth, height: $barHeight, color: $color);
			$parts[] = $this->svg->textEl(
				x: $marginLeft + $barWidth + 6,
				y: $barY + ($barHeight / 2) + 3,
				text: $this->labels->formatValue(value: $value, format: $valueFmt),
				anchor: 'start',
				size: 9,
				color: '#333333'
			);
		}//end for

		return implode('', $parts);
	}//end drawRows()
}//end class
