<?php

/**
 * Unit tests for CartesianFrame.
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

use OCA\Filinq\Service\Charts\CartesianFrame;
use OCA\Filinq\Service\Charts\ChartRequest;
use PHPUnit\Framework\TestCase;

/**
 * Plot-area layout and the axis frame.
 *
 * @covers \OCA\Filinq\Service\Charts\CartesianFrame
 * @uses   \OCA\Filinq\Service\Charts\ChartRequest
 * @uses   \OCA\Filinq\Service\Charts\ChartLabelFormatter
 * @uses   \OCA\Filinq\Service\Charts\ThousandsFormatter
 * @uses   \OCA\Filinq\Service\Charts\SvgPrimitives
 * @uses   \OCA\Filinq\Service\Charts\SvgPoint
 * @uses   \OCA\Filinq\Service\Charts\SvgSegment
 * @uses   \OCA\Filinq\Service\Charts\SvgText
 * @uses   \OCA\Filinq\Service\Charts\SvgTextStyle
 */
class CartesianFrameTest extends TestCase {

	/**
	 * Build a chart request.
	 *
	 * @param int   $width   Canvas width.
	 * @param int   $height  Canvas height.
	 * @param array $options Rendering options.
	 *
	 * @return ChartRequest
	 */
	private function chart(int $width, int $height, array $options): ChartRequest {
		return new ChartRequest(normalized: ['labels' => [], 'series' => []], palette: ['#000000'], width: $width, height: $height, options: $options);
	}//end chart()

	/**
	 * Without title or legend the margins are the minimum ones.
	 *
	 * @return void
	 */
	public function testLayoutWithoutTitleOrLegend(): void {
		$layout = (new CartesianFrame())->layout(chart: $this->chart(width: 400, height: 300, options: []), showLegend: false);

		$this->assertSame(
			['marginTop' => 14, 'marginLeft' => 46, 'plotWidth' => 338.0, 'plotHeight' => 246.0, 'baselineY' => 260.0],
			$layout
		);
	}//end testLayoutWithoutTitleOrLegend()

	/**
	 * A title widens the top margin and a legend the bottom one.
	 *
	 * @return void
	 */
	public function testLayoutWithTitleAndLegend(): void {
		$layout = (new CartesianFrame())->layout(chart: $this->chart(width: 400, height: 300, options: ['title' => 'Omzet']), showLegend: true);

		$this->assertSame(34, $layout['marginTop']);
		$this->assertSame(204.0, $layout['plotHeight']);
		$this->assertSame(238.0, $layout['baselineY']);
	}//end testLayoutWithTitleAndLegend()

	/**
	 * A canvas smaller than the margins still gets a one-pixel plot.
	 *
	 * @return void
	 */
	public function testLayoutNeverCollapsesBelowOnePixel(): void {
		$layout = (new CartesianFrame())->layout(chart: $this->chart(width: 10, height: 10, options: []), showLegend: true);

		$this->assertSame(1.0, $layout['plotWidth']);
		$this->assertSame(1.0, $layout['plotHeight']);
		$this->assertSame(15.0, $layout['baselineY']);
	}//end testLayoutNeverCollapsesBelowOnePixel()

	/**
	 * The frame draws the canvas, title, five ticks and the baseline.
	 *
	 * @return void
	 */
	public function testRenderDrawsTitleTicksAndBaseline(): void {
		$chart = $this->chart(width: 200, height: 100, options: ['title' => 'A & B', 'valueFormat' => 'percent']);
		$plot = ['niceMax' => 1.0, 'marginTop' => 10, 'marginLeft' => 40, 'plotWidth' => 100.0, 'plotHeight' => 80.0, 'baselineY' => 90.0];

		$svg = (new CartesianFrame())->render(chart: $chart, plot: $plot);

		$this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100" ', $svg);
		$title = '<text x="100" y="18" font-family="sans-serif" font-size="13" font-weight="bold" fill="#000000" '
			. 'text-anchor="middle">A &amp; B</text>';
		$this->assertStringContainsString($title, $svg);
		foreach (['0%', '25%', '50%', '75%', '100%'] as $tick) {
			$this->assertStringContainsString('>' . $tick . '</text>', $svg);
		}

		$this->assertSame(5, substr_count($svg, 'stroke="#E2E2E2"'));
		$this->assertStringContainsString('x1="40" y1="10" x2="140" y2="10" stroke="#E2E2E2"', $svg);
		$this->assertStringEndsWith('<line x1="40" y1="90" x2="140" y2="90" stroke="#999999" stroke-width="1"/>', $svg);
	}//end testRenderDrawsTitleTicksAndBaseline()

	/**
	 * Without a title no title text is drawn; ticks use the integer default.
	 *
	 * @return void
	 */
	public function testRenderWithoutTitleUsesIntegerTicks(): void {
		$chart = $this->chart(width: 200, height: 100, options: []);
		$plot = ['niceMax' => 2000.0, 'marginTop' => 10, 'marginLeft' => 40, 'plotWidth' => 100.0, 'plotHeight' => 80.0, 'baselineY' => 90.0];

		$svg = (new CartesianFrame())->render(chart: $chart, plot: $plot);

		$this->assertStringNotContainsString('font-weight="bold"', $svg);
		$this->assertStringContainsString('>1.500</text>', $svg);
		$this->assertStringContainsString('>2.000</text>', $svg);
	}//end testRenderWithoutTitleUsesIntegerTicks()
}//end class
