<?php

/**
 * Cartesian Frame
 *
 * The plot-area geometry and chrome (background, title, y-axis gridlines and
 * tick labels, axis baseline) shared byte-for-byte by the bar and line chart
 * renderers.
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
 * Computes and draws the plot frame of a cartesian (bar/line) chart.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
 */
class CartesianFrame {

	/**
	 * Number of y-axis gridline intervals (so 5 lines, 0..max).
	 *
	 * @var int
	 */
	private const TICK_COUNT = 4;

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
	public function __construct() {
		$this->svg = new SvgPrimitives();
		$this->labels = new ChartLabelFormatter();

	}//end __construct()

	/**
	 * Compute the plot-area geometry for a cartesian chart.
	 *
	 * @param ChartRequest $chart The chart: its title and canvas size.
	 * @param bool $showLegend Whether a legend row is drawn.
	 *
	 * @return array{marginTop: int, marginLeft: int, plotWidth: float, plotHeight: float, baselineY: float}
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function layout(ChartRequest $chart, bool $showLegend): array {
		$marginTop = 14;
		if ($chart->title() !== '') {
			$marginTop = 34;
		}

		$marginBottom = 40;
		if ($showLegend === true) {
			$marginBottom += 22;
		}

		$marginLeft = 46;
		$marginRight = 16;

		$plotWidth = (float)max(1, $chart->width - $marginLeft - $marginRight);
		$plotHeight = (float)max(1, $chart->height - $marginTop - $marginBottom);

		return [
			'marginTop' => $marginTop,
			'marginLeft' => $marginLeft,
			'plotWidth' => $plotWidth,
			'plotHeight' => $plotHeight,
			'baselineY' => ($marginTop + $plotHeight),
		];

	}//end layout()

	/**
	 * Render the chart chrome: background, optional title, y-axis gridlines
	 * with tick labels, and the axis baseline.
	 *
	 * @param ChartRequest $chart The chart: its title, value format and canvas size.
	 * @param array $plot Geometry from {@see layout()} plus the axis ceiling `niceMax`.
	 *
	 * @return string SVG markup fragment (opening tag included, not closed).
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(ChartRequest $chart, array $plot): string {
		$parts = [];
		$parts[] = $this->svg->svgOpenTag(width: $chart->width, height: $chart->height);

		$title = $chart->title();
		if ($title !== '') {
			$parts[] = $this->svg->textEl(x: $chart->width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
		}

		$niceMax = $plot['niceMax'];
		$valueFmt = $chart->valueFormat();
		$marginLeft = $plot['marginLeft'];
		$plotWidth = $plot['plotWidth'];

		for ($t = 0; $t <= self::TICK_COUNT; $t++) {
			$value = ($niceMax / self::TICK_COUNT) * $t;
			$y = $plot['marginTop'] + $plot['plotHeight'] - (($value / $niceMax) * $plot['plotHeight']);
			$valueLabel = $this->labels->formatValue(value: $value, format: $valueFmt);
			$parts[] = $this->svg->lineEl(x1: $marginLeft, y1: $y, x2: $marginLeft + $plotWidth, y2: $y, color: '#E2E2E2', width: 1);
			$parts[] = $this->svg->textEl(x: $marginLeft - 6, y: $y + 3, text: $valueLabel, anchor: 'end', size: 9, color: '#666666');
		}

		$baselineY = $plot['baselineY'];
		$parts[] = $this->svg->lineEl(
			x1: $marginLeft,
			y1: $baselineY,
			x2: $marginLeft + $plotWidth,
			y2: $baselineY,
			color: '#999999',
			width: 1
		);

		return implode('', $parts);
	}//end render()
}//end class
