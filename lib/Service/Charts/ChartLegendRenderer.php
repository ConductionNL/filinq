<?php

/**
 * Chart Legend Renderer
 *
 * Renders the horizontal color-swatch + series-name legend shared by the bar
 * and line chart renderers.
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
 * Renders the series legend of a cartesian chart.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
 */
class ChartLegendRenderer {

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
	 * Render a legend row of color-swatch + series name entries.
	 *
	 * Draws at most {@see ChartLabelFormatter::MAX_LABELLED_ENTRIES} entries on
	 * a baseline 12px above the bottom of the canvas.
	 *
	 * @param ChartRequest $chart The chart: its series, palette and canvas height.
	 * @param float $x Left x position.
	 *
	 * @return string SVG markup fragment.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(ChartRequest $chart, float $x): string {
		$y = (float)($chart->height - 12);
		$parts = [];
		$cursorX = $x;
		foreach ($chart->normalized['series'] as $index => $oneSeries) {
			if ($index >= ChartLabelFormatter::MAX_LABELLED_ENTRIES) {
				break;
			}

			$color = $chart->color(index: (int)$index);
			$name = $this->labels->truncate(text: $oneSeries['name'], max: 18);

			$swatch = new SvgBox(origin: new SvgPoint(x: $cursorX, y: $y - 9), size: new SvgSize(width: 9, height: 9));
			$parts[] = $this->svg->rectEl(box: $swatch, color: $color);
			$parts[] = $this->svg->textEl(position: new SvgPoint(x: $cursorX + 13, y: $y), text: new SvgText(content: $name, style: SvgTextStyle::LabelStart));
			$cursorX += 13 + (strlen($name) * 5.5) + 14;
		}

		return implode('', $parts);
	}//end render()
}//end class
