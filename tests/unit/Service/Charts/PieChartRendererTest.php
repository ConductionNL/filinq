<?php

/**
 * Unit tests for PieChartRenderer.
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

use OCA\Filinq\Service\Charts\ChartRenderError;
use OCA\Filinq\Service\Charts\ChartRequest;
use OCA\Filinq\Service\Charts\PieChartRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Drawing a pie or donut chart from the first series.
 *
 * @covers \OCA\Filinq\Service\Charts\PieChartRenderer
 * @covers \OCA\Filinq\Service\Charts\ChartRequest
 *
 * @uses \OCA\Filinq\Service\Charts\ChartLabelFormatter
 * @uses \OCA\Filinq\Service\Charts\ChartRenderError
 * @uses \OCA\Filinq\Service\Charts\SvgArc
 * @uses \OCA\Filinq\Service\Charts\SvgBox
 * @uses \OCA\Filinq\Service\Charts\SvgCircle
 * @uses \OCA\Filinq\Service\Charts\SvgPoint
 * @uses \OCA\Filinq\Service\Charts\SvgPrimitives
 * @uses \OCA\Filinq\Service\Charts\SvgSector
 * @uses \OCA\Filinq\Service\Charts\SvgSize
 * @uses \OCA\Filinq\Service\Charts\SvgText
 * @uses \OCA\Filinq\Service\Charts\SvgTextStyle
 * @uses \OCA\Filinq\Service\Charts\ThousandsFormatter
 */
class PieChartRendererTest extends TestCase {

	/**
	 * A 400x300 chart request over one series.
	 *
	 * @param array<int, string>     $labels  The labels.
	 * @param array<int, float|null> $values  The values.
	 * @param array<string, mixed>   $options The options.
	 *
	 * @return ChartRequest
	 */
	private static function chart(array $labels, array $values, array $options = []): ChartRequest {
		return new ChartRequest(
			normalized: ['labels' => $labels, 'series' => [['name' => 'S', 'values' => $values]]],
			palette: ['#c1', '#c2', '#c3'],
			width: 400,
			height: 300,
			options: $options,
		);
	}//end chart()

	/**
	 * Nothing positive to draw is refused as no data.
	 *
	 * @return void
	 */
	public function testNothingPositiveIsNoData(): void {
		$result = (new PieChartRenderer())->render(chart: self::chart(['a', 'b', 'c'], [0.0, -4.0, null]));

		self::assertInstanceOf(ChartRenderError::class, $result);
		self::assertSame('chart error: no data', $result->message);
	}//end testNothingPositiveIsNoData()

	/**
	 * One positive slice is a full circle in the first colour, and its legend row takes that colour too.
	 *
	 * @return void
	 */
	public function testOneSliceIsAFullCircle(): void {
		$svg = (string)(new PieChartRenderer())->render(chart: self::chart(['zero', 'only'], [0.0, 5.0]));

		self::assertStringContainsString('<circle cx="120" cy="150" r="112" fill="#c1"/>', $svg);
		self::assertStringNotContainsString('<path', $svg);
		self::assertStringContainsString('<rect x="252" y="12" width="10" height="10" fill="#c1"/>', $svg);
		self::assertStringContainsString('>only (100%)</text>', $svg);
		self::assertStringStartsWith('<svg ', $svg);
		self::assertStringEndsWith('</svg>', $svg);
	}//end testOneSliceIsAFullCircle()

	/**
	 * Slices start at the top, run clockwise in proportion, and the legend shows rounded percentages.
	 *
	 * @return void
	 */
	public function testSlicesRunClockwiseFromTheTop(): void {
		$svg = (string)(new PieChartRenderer())->render(chart: self::chart(['a', 'b', 'c', 'd'], [1.0, 1.0, 2.0, 0.0]));

		self::assertSame(3, substr_count($svg, '<path '));
		self::assertStringContainsString('<path d="M 120 150 L 120 38 A 112 112 0 0 1 232 150 Z" fill="#c1"/>', $svg);
		self::assertStringContainsString('<path d="M 120 150 L 232 150 A 112 112 0 0 1 120 262 Z" fill="#c2"/>', $svg);
		self::assertStringContainsString('<path d="M 120 150 L 120 262 A 112 112 0 0 1 120 38 Z" fill="#c3"/>', $svg);
		self::assertStringContainsString('>a (25%)</text>', $svg);
		self::assertStringContainsString('>c (50%)</text>', $svg);
		self::assertStringNotContainsString('>d (', $svg);
	}//end testSlicesRunClockwiseFromTheTop()

	/**
	 * A title moves the pie down; without a legend the pie takes the full width.
	 *
	 * @return void
	 */
	public function testTitleAndNoLegendChangeTheGeometry(): void {
		$svg = (string)(new PieChartRenderer())->render(chart: self::chart(['only'], [3.0], ['title' => 'Split', 'showLegend' => false]));

		self::assertStringContainsString('<text x="200" y="18" font-family="sans-serif" font-size="13" font-weight="bold" fill="#000000" text-anchor="middle">Split</text>', $svg);
		self::assertStringContainsString('<circle cx="200" cy="160" r="118" fill="#c1"/>', $svg);
		self::assertStringNotContainsString('(100%)', $svg);
	}//end testTitleAndNoLegendChangeTheGeometry()

	/**
	 * A donut punches a white hole of 55% of the radius.
	 *
	 * @return void
	 */
	public function testADonutHasAWhiteCentre(): void {
		$svg = (string)(new PieChartRenderer())->render(chart: self::chart(['a', 'b'], [1.0, 3.0], ['donut' => true]));

		self::assertStringContainsString('<circle cx="120" cy="150" r="61.6" fill="#FFFFFF"/>', $svg);
	}//end testADonutHasAWhiteCentre()

	/**
	 * The legend lists at most twenty slices, truncating long labels, while every slice is drawn.
	 *
	 * @return void
	 */
	public function testTheLegendIsCappedAndTruncated(): void {
		$labels = [];
		for ($i = 0; $i < 25; $i++) {
			$labels[] = 'A rather long label number ' . $i;
		}

		$svg = (string)(new PieChartRenderer())->render(chart: self::chart($labels, array_fill(0, 25, 1.0)));

		self::assertSame(25, substr_count($svg, '<path '));
		self::assertSame(20, substr_count($svg, 'text-anchor="start"'));
		self::assertStringContainsString('>A rather long label…', $svg);
		self::assertStringContainsString('fill="#c1"', $svg);
		self::assertStringContainsString('<path d="M 120 150 L 120 38 A 112 112 0 0 1 ', $svg);
	}//end testTheLegendIsCappedAndTruncated()

	/**
	 * The request's helpers default the title and value format and cycle the palette.
	 *
	 * @return void
	 */
	public function testTheRequestDefaultsAndCyclesThePalette(): void {
		$chart = self::chart(['a'], [1.0]);

		self::assertSame('', $chart->title());
		self::assertSame('integer', $chart->valueFormat());
		self::assertSame('#c1', $chart->color(index: 3));
		self::assertSame('#c3', $chart->color(index: 5));
		self::assertSame('percent', self::chart(['a'], [1.0], ['valueFormat' => 'percent'])->valueFormat());
	}//end testTheRequestDefaultsAndCyclesThePalette()
}//end class
