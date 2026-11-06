<?php

/**
 * Golden tests for the SVG of every chart type
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the exact SVG each chart type and option set renders, byte for byte.
 *
 * Set FILINQ_WRITE_GOLDEN=1 to (re)write the fixtures.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\Charts\ChartSvgRenderer
 * @covers \OCA\Filinq\Service\Charts\BarChartRenderer
 * @covers \OCA\Filinq\Service\Charts\LineChartRenderer
 * @covers \OCA\Filinq\Service\Charts\PieChartRenderer
 * @covers \OCA\Filinq\Service\Charts\CartesianFrame
 * @covers \OCA\Filinq\Service\Charts\ChartLegendRenderer
 * @covers \OCA\Filinq\Service\Charts\ChartRequest
 * @uses   \OCA\Filinq\Service\Charts\ChartDataNormalizer
 * @uses   \OCA\Filinq\Service\Charts\ChartLabelFormatter
 * @uses   \OCA\Filinq\Service\Charts\ChartPalette
 * @uses   \OCA\Filinq\Service\Charts\ChartRenderError
 * @uses   \OCA\Filinq\Service\Charts\ChartScale
 * @uses   \OCA\Filinq\Service\Charts\SvgArc
 * @uses   \OCA\Filinq\Service\Charts\SvgBox
 * @uses   \OCA\Filinq\Service\Charts\SvgCircle
 * @uses   \OCA\Filinq\Service\Charts\SvgPoint
 * @uses   \OCA\Filinq\Service\Charts\SvgPrimitives
 * @uses   \OCA\Filinq\Service\Charts\SvgSector
 * @uses   \OCA\Filinq\Service\Charts\SvgSegment
 * @uses   \OCA\Filinq\Service\Charts\SvgSize
 * @uses   \OCA\Filinq\Service\Charts\SvgText
 * @uses   \OCA\Filinq\Service\Charts\SvgTextStyle
 * @uses   \OCA\Filinq\Service\Charts\ThousandsFormatter
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ChartRenderGoldenTest extends TestCase {

	/**
	 * Every chart case: fixture name => [type, data, options].
	 *
	 * @return array<string, array{0: string, 1: array, 2: array}>
	 */
	public static function charts(): array {
		$two = [
			'labels' => ['Q1', 'Q2', 'Q3', 'A very long category name'],
			'series' => [
				['name' => 'Revenue', 'values' => [120, 340.5, null, 90]],
				['name' => 'Cost', 'values' => [80, 100, 60, 0]],
			],
		];
		$one = ['labels' => ['North', 'South', 'East'], 'series' => [['name' => 'Share', 'values' => [50, 30, 20]]]];
		$many = ['labels' => array_map(static fn (int $i): string => 'L' . $i, range(1, 14)), 'series' => [['name' => 'n', 'values' => range(1, 14)]]];

		return [
			'bar-vertical'      => ['bar', $two, ['title' => 'Revenue and cost', 'valueFormat' => 'currency']],
			'bar-vertical-bare' => ['bar', $one, ['width' => 320, 'height' => 200]],
			'bar-many-labels'   => ['bar', $many, ['title' => 'Many']],
			'bar-horizontal'    => ['bar', $two, ['orientation' => 'horizontal', 'title' => 'Ranked', 'valueFormat' => 'decimal:1']],
			'bar-horizontal-bare' => ['bar', $one, ['orientation' => 'horizontal', 'palette' => ['#112233']]],
			'line'              => ['line', $two, ['title' => 'Trend', 'valueFormat' => 'percent', 'showLegend' => true]],
			'line-bare'         => ['line', $one, ['showLegend' => false]],
			'pie'               => ['pie', $one, ['title' => 'Split']],
			'pie-donut'         => ['pie', $one, ['donut' => true, 'width' => 300, 'height' => 300]],
			'pie-many'          => ['pie', $many, []],
			'unsupported'       => ['radar', $one, []],
			'empty'             => ['bar', ['labels' => [], 'series' => []], []],
		];
	}//end charts()

	/**
	 * The rendered SVG equals the golden fixture.
	 *
	 * @param string $type    Chart type
	 * @param array  $data    Chart data
	 * @param array  $options Rendering options
	 *
	 * @return void
	 */
	#[DataProvider('charts')]
	public function testRenderMatchesGolden(string $type, array $data, array $options): void {
		$name = (string)$this->dataName();
		$path = dirname(__DIR__, 3) . '/fixtures/charts/' . $name . '.svg';
		$svg  = (new ChartSvgRenderer())->render(type: $type, data: $data, options: $options);

		if (getenv('FILINQ_WRITE_GOLDEN') === '1') {
			file_put_contents($path, $svg);
		}

		$this->assertFileExists($path);
		$this->assertSame((string)file_get_contents($path), $svg);

	}//end testRenderMatchesGolden()
}//end class
