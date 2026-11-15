<?php

/**
 * Unit tests for ChartLegendRenderer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartLabelFormatter;
use OCA\Filinq\Service\Charts\ChartLegendRenderer;
use OCA\Filinq\Service\Charts\ChartRequest;
use PHPUnit\Framework\TestCase;

/**
 * Legend swatches and names below the plot.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartLegendRenderer
 * @uses   \OCA\Filinq\Service\Charts\ChartRequest
 * @uses   \OCA\Filinq\Service\Charts\ChartLabelFormatter
 * @uses   \OCA\Filinq\Service\Charts\ThousandsFormatter
 * @uses   \OCA\Filinq\Service\Charts\SvgPrimitives
 * @uses   \OCA\Filinq\Service\Charts\SvgBox
 * @uses   \OCA\Filinq\Service\Charts\SvgPoint
 * @uses   \OCA\Filinq\Service\Charts\SvgSize
 * @uses   \OCA\Filinq\Service\Charts\SvgText
 * @uses   \OCA\Filinq\Service\Charts\SvgTextStyle
 */
class ChartLegendRendererTest extends TestCase {

	/**
	 * Build a chart request for the given series names.
	 *
	 * @param string[] $names   Series names.
	 * @param string[] $palette Palette.
	 *
	 * @return ChartRequest
	 */
	private function chart(array $names, array $palette): ChartRequest {
		$series = array_map(static fn (string $name): array => ['name' => $name, 'values' => [1.0]], $names);
		return new ChartRequest(normalized: ['labels' => ['a'], 'series' => $series], palette: $palette, width: 400, height: 200, options: []);
	}//end chart()

	/**
	 * Each entry is a swatch in the series colour followed by its name, advancing by name width.
	 *
	 * @return void
	 */
	public function testRenderPlacesSwatchAndNamePerSeries(): void {
		$svg = (new ChartLegendRenderer())->render(chart: $this->chart(names: ['Ab', 'Cd'], palette: ['#111111', '#222222']), x: 10.0);

		$expected = '<rect x="10" y="179" width="9" height="9" fill="#111111"/>'
			. '<text x="23" y="188" font-family="sans-serif" font-size="9" font-weight="normal" fill="#333333" text-anchor="start">Ab</text>'
			. '<rect x="48" y="179" width="9" height="9" fill="#222222"/>'
			. '<text x="61" y="188" font-family="sans-serif" font-size="9" font-weight="normal" fill="#333333" text-anchor="start">Cd</text>';
		$this->assertSame($expected, $svg);
	}//end testRenderPlacesSwatchAndNamePerSeries()

	/**
	 * Long names are truncated and escaped; colours cycle through the palette.
	 *
	 * @return void
	 */
	public function testRenderTruncatesEscapesAndCyclesColours(): void {
		$chart = $this->chart(names: ['<x>', 'A very long series name indeed', 'Third'], palette: ['#AAAAAA', '#BBBBBB']);
		$svg = (new ChartLegendRenderer())->render(chart: $chart, x: 0.0);

		$this->assertStringContainsString('>&lt;x&gt;</text>', $svg);
		$this->assertStringContainsString('>A very long serie…</text>', $svg);
		$this->assertSame(2, substr_count($svg, 'fill="#AAAAAA"'));
		$this->assertSame(1, substr_count($svg, 'fill="#BBBBBB"'));
	}//end testRenderTruncatesEscapesAndCyclesColours()

	/**
	 * No more than the labelled-entry limit is drawn.
	 *
	 * @return void
	 */
	public function testRenderStopsAtTheEntryLimit(): void {
		$names = array_map(static fn (int $i): string => 'S' . $i, range(1, ChartLabelFormatter::MAX_LABELLED_ENTRIES + 5));

		$svg = (new ChartLegendRenderer())->render(chart: $this->chart(names: $names, palette: ['#000000']), x: 0.0);

		$this->assertSame(ChartLabelFormatter::MAX_LABELLED_ENTRIES, substr_count($svg, '<rect '));
		$this->assertStringNotContainsString('>S21<', $svg);
	}//end testRenderStopsAtTheEntryLimit()

	/**
	 * No series means no legend.
	 *
	 * @return void
	 */
	public function testRenderWithoutSeriesIsEmpty(): void {
		$this->assertSame('', (new ChartLegendRenderer())->render(chart: $this->chart(names: [], palette: ['#000000']), x: 5.0));
	}//end testRenderWithoutSeriesIsEmpty()
}//end class
