<?php

/**
 * Unit tests for ChartSvgRenderer.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Charts;

use OCA\DocuDesk\Service\Charts\ChartSvgRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Tests deterministic rendering, escaping, guardrails, and the three chart
 * kinds (bar/horizontal-bar, line, pie/donut) of ChartSvgRenderer.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ChartSvgRendererTest extends TestCase {

	/**
	 * The renderer under test.
	 *
	 * @var ChartSvgRenderer
	 */
	private ChartSvgRenderer $renderer;

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new ChartSvgRenderer();

	}//end setUp()

	/**
	 * A bar chart fixture used across several tests.
	 *
	 * @return array
	 */
	private function barFixture(): array {
		return [
			'labels' => ['Alpha', 'Beta', 'Gamma'],
			'series' => [
				['name' => 'Stars', 'values' => [120, 80, 45]],
			],
		];

	}//end barFixture()

	/**
	 * Same data rendered twice (and via a fresh instance) yields
	 * byte-identical SVG — the deterministic-output pin (REQ-DDTCH-001).
	 *
	 * @return void
	 */
	public function testDeterministicOutput(): void {
		$first = $this->renderer->render(type: 'bar', data: $this->barFixture());
		$second = (new ChartSvgRenderer())->render(type: 'bar', data: $this->barFixture());

		$this->assertSame($first, $second);

	}//end testDeterministicOutput()

	/**
	 * A label containing markup/script content is escaped as literal text,
	 * never emitted as an SVG element.
	 *
	 * @return void
	 */
	public function testLabelsEscaped(): void {
		$data = [
			'labels' => ['</svg><script>alert(1)</script>'],
			'series' => [
				['name' => 'S', 'values' => [10]],
			],
		];

		$svg = $this->renderer->render(type: 'bar', data: $data);

		$this->assertStringNotContainsString('<script>', $svg);
		// Label is truncated for axis display, but whatever survives is
		// HTML-entity-escaped rather than emitted as raw markup.
		$this->assertStringContainsString('&lt;/svg&gt;&lt;script', $svg);
		// Exactly one real <svg ...> open tag exists (the injected close tag
		// did not terminate the document early).
		$this->assertSame(1, substr_count($svg, '<svg '));

	}//end testLabelsEscaped()

	/**
	 * A series name containing markup is also escaped (legend text).
	 *
	 * @return void
	 */
	public function testSeriesNameEscaped(): void {
		$data = [
			'labels' => ['A', 'B'],
			'series' => [
				['name' => '<b>Bold</b>', 'values' => [1, 2]],
				['name' => 'Other', 'values' => [3, 4]],
			],
		];

		$svg = $this->renderer->render(type: 'bar', data: $data, options: ['showLegend' => true]);

		$this->assertStringNotContainsString('<b>Bold</b>', $svg);
		$this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt;', $svg);

	}//end testSeriesNameEscaped()

	/**
	 * Empty data (no labels) renders a placeholder box with a 'no data'
	 * message, never an exception.
	 *
	 * @return void
	 */
	public function testEmptyDataRendersPlaceholder(): void {
		$svg = $this->renderer->render(type: 'bar', data: ['labels' => [], 'series' => []]);

		$this->assertStringStartsWith('<svg', $svg);
		$this->assertStringContainsString('no data', $svg);
		$this->assertSame('chart error: no data', $this->renderer->getLastWarning());

	}//end testEmptyDataRendersPlaceholder()

	/**
	 * Malformed data (missing the "series" key entirely) degrades to a
	 * visible error marker rather than throwing.
	 *
	 * @return void
	 */
	public function testMalformedDataRendersPlaceholder(): void {
		$svg = $this->renderer->render(type: 'bar', data: ['labels' => ['A', 'B']]);

		$this->assertStringContainsString('chart error', $svg);
		$this->assertNotNull($this->renderer->getLastWarning());

	}//end testMalformedDataRendersPlaceholder()

	/**
	 * An unsupported chart type degrades to a visible error marker.
	 *
	 * @return void
	 */
	public function testUnsupportedTypeRendersPlaceholder(): void {
		$svg = $this->renderer->render(type: 'scatter', data: $this->barFixture());

		$this->assertStringContainsString('unsupported chart type', $svg);

	}//end testUnsupportedTypeRendersPlaceholder()

	/**
	 * A series length above the configured (or default) point cap degrades
	 * to a visible error marker instead of emitting an oversized SVG.
	 *
	 * @return void
	 */
	public function testMaxPointsCapEnforced(): void {
		$labels = array_map(static fn ($i) => 'L' . $i, range(1, 5));
		$values = array_map(static fn ($i) => $i, range(1, 5));
		$data = ['labels' => $labels, 'series' => [['name' => 'S', 'values' => $values]]];

		$svg = $this->renderer->render(type: 'bar', data: $data, options: ['maxPoints' => 3]);

		$this->assertStringContainsString('too many data points', $svg);

	}//end testMaxPointsCapEnforced()

	/**
	 * Non-numeric and missing values are silently skipped (gap), not an
	 * error — the chart still renders for the remaining valid points.
	 *
	 * @return void
	 */
	public function testNonNumericValuesAreSkipped(): void {
		$data = [
			'labels' => ['A', 'B', 'C'],
			'series' => [
				['name' => 'S', 'values' => [10, 'not-a-number', null]],
			],
		];

		$svg = $this->renderer->render(type: 'bar', data: $data);

		$this->assertStringStartsWith('<svg', $svg);
		$this->assertNull($this->renderer->getLastWarning());
		// Only one bar <rect> beyond the placeholder/background rects for
		// the single valid point — background + one bar.
		$this->assertSame(2, substr_count($svg, '<rect '));

	}//end testNonNumericValuesAreSkipped()

	/**
	 * A palette override is honoured (colors used verbatim).
	 *
	 * @return void
	 */
	public function testPaletteOverrideIsUsed(): void {
		$svg = $this->renderer->render(
			type: 'bar',
			data: $this->barFixture(),
			options: ['palette' => ['#123456']]
		);

		$this->assertStringContainsString('#123456', $svg);

	}//end testPaletteOverrideIsUsed()

	/**
	 * A huisstijl seed color that is too light (poor contrast) falls back
	 * to the fixed accessible palette rather than an unreadable ramp.
	 *
	 * @return void
	 */
	public function testPaletteFallsBackWhenSeedTooLight(): void {
		$light = $this->renderer->render(
			type: 'bar',
			data: $this->barFixture(),
			options: ['huisstijlPrimaryColor' => '#FEFEFE']
		);
		$default = $this->renderer->render(type: 'bar', data: $this->barFixture());

		$this->assertSame($default, $light);

	}//end testPaletteFallsBackWhenSeedTooLight()

	/**
	 * A usable huisstijl seed color changes the rendered palette away from
	 * the fixed fallback.
	 *
	 * @return void
	 */
	public function testPaletteSeededFromHuisstijl(): void {
		$seeded = $this->renderer->render(
			type: 'bar',
			data: $this->barFixture(),
			options: ['huisstijlPrimaryColor' => '#008040']
		);
		$default = $this->renderer->render(type: 'bar', data: $this->barFixture());

		$this->assertNotSame($default, $seeded);

	}//end testPaletteSeededFromHuisstijl()

	/**
	 * A vertical bar chart renders axis gridlines and one <rect> bar per
	 * data point.
	 *
	 * @return void
	 */
	public function testBarChartRendersBarsAndAxis(): void {
		$svg = $this->renderer->render(type: 'bar', data: $this->barFixture(), options: ['title' => 'Test']);

		$this->assertStringContainsString('<svg', $svg);
		$this->assertStringContainsString('Test', $svg);
		$this->assertStringContainsString('<line ', $svg);
		// Background + 3 bars = 4 <rect> elements.
		$this->assertSame(4, substr_count($svg, '<rect '));

	}//end testBarChartRendersBarsAndAxis()

	/**
	 * Horizontal orientation renders the first series as ranked rows.
	 *
	 * @return void
	 */
	public function testHorizontalBarChartRenders(): void {
		$svg = $this->renderer->render(
			type: 'bar',
			data: $this->barFixture(),
			options: ['orientation' => 'horizontal']
		);

		$this->assertStringContainsString('<svg', $svg);
		$this->assertSame(4, substr_count($svg, '<rect '));

	}//end testHorizontalBarChartRenders()

	/**
	 * A line chart renders a polyline per series and point markers.
	 *
	 * @return void
	 */
	public function testLineChartRendersPolyline(): void {
		$svg = $this->renderer->render(type: 'line', data: $this->barFixture());

		$this->assertStringContainsString('<polyline ', $svg);
		$this->assertStringContainsString('<circle ', $svg);

	}//end testLineChartRendersPolyline()

	/**
	 * A line chart with a gap (null value) still renders the surrounding
	 * points as separate polyline segments.
	 *
	 * @return void
	 */
	public function testLineChartHandlesGap(): void {
		$data = [
			'labels' => ['A', 'B', 'C', 'D'],
			'series' => [
				['name' => 'S', 'values' => [10, null, 30, 40]],
			],
		];

		$svg = $this->renderer->render(type: 'line', data: $data);

		// Two disjoint segments (before/after the gap) => two <polyline>.
		$this->assertSame(2, substr_count($svg, '<polyline '));

	}//end testLineChartHandlesGap()

	/**
	 * A pie chart renders one path slice per non-zero value plus a legend.
	 *
	 * @return void
	 */
	public function testPieChartRendersSlicesAndLegend(): void {
		$data = [
			'labels' => ['A', 'B', 'C'],
			'series' => [
				['name' => 'S', 'values' => [50, 30, 20]],
			],
		];

		$svg = $this->renderer->render(type: 'pie', data: $data);

		$this->assertSame(3, substr_count($svg, '<path '));
		$this->assertStringContainsString('50%', $svg);

	}//end testPieChartRendersSlicesAndLegend()

	/**
	 * A single non-zero slice renders as a full <circle> (arc math edge
	 * case for a 360° sweep).
	 *
	 * @return void
	 */
	public function testPieChartSingleSliceRendersCircle(): void {
		$data = ['labels' => ['Only'], 'series' => [['name' => 'S', 'values' => [42]]]];

		$svg = $this->renderer->render(type: 'pie', data: $data);

		$this->assertStringContainsString('<circle ', $svg);
		$this->assertSame(0, substr_count($svg, '<path '));

	}//end testPieChartSingleSliceRendersCircle()

	/**
	 * The donut option overlays a white center circle.
	 *
	 * @return void
	 */
	public function testDonutOptionRendersInnerCircle(): void {
		$data = ['labels' => ['A', 'B'], 'series' => [['name' => 'S', 'values' => [1, 1]]]];

		$svg = $this->renderer->render(type: 'pie', data: $data, options: ['donut' => true]);

		$this->assertStringContainsString('fill="#FFFFFF"', $svg);

	}//end testDonutOptionRendersInnerCircle()

	/**
	 * A pie chart where all values are zero/negative has no usable slices
	 * and degrades to the 'no data' placeholder.
	 *
	 * @return void
	 */
	public function testPieChartAllZeroRendersPlaceholder(): void {
		$data = ['labels' => ['A', 'B'], 'series' => [['name' => 'S', 'values' => [0, 0]]]];

		$svg = $this->renderer->render(type: 'pie', data: $data);

		$this->assertStringContainsString('no data', $svg);

	}//end testPieChartAllZeroRendersPlaceholder()

	/**
	 * getLastWarning() is null immediately after a successful render.
	 *
	 * @return void
	 */
	public function testGetLastWarningNullOnSuccess(): void {
		$this->renderer->render(type: 'bar', data: $this->barFixture());

		$this->assertNull($this->renderer->getLastWarning());

	}//end testGetLastWarningNullOnSuccess()

	/**
	 * value labels honour the 'currency' format.
	 *
	 * @return void
	 */
	public function testCurrencyValueFormat(): void {
		$data = ['labels' => ['A'], 'series' => [['name' => 'S', 'values' => [1234.5]]]];

		$svg = $this->renderer->render(type: 'bar', data: $data, options: ['valueFormat' => 'currency']);

		$this->assertStringContainsString('€ 1.234,50', $svg);

	}//end testCurrencyValueFormat()
}//end class
