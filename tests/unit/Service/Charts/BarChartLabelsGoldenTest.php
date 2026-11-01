<?php

/**
 * Golden test for the bar chart around the labelled-entries limit.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Bar charts with the most labelled categories and one more, and short and
 * tall bars, compared with SVG hashes recorded before drawBars() was split.
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
 */
class BarChartLabelsGoldenTest extends TestCase {

	/**
	 * The charts: data and options.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
	 */
	public static function charts(): array {
		$series = static fn (int $count): array => [
			['name' => 'a', 'values' => array_map(static fn (int $i): float => ($i % 4 === 0 ? 0.5 : (float)$i), range(1, $count))],
			['name' => 'b', 'values' => array_map(static fn (int $i): ?float => ($i % 3 === 0 ? null : 40.0 - $i), range(1, $count))],
		];
		$labels = static fn (int $count): array => array_map(static fn (int $i): string => 'Categorie ' . $i, range(1, $count));
		return [
			'twenty categories' => [['labels' => $labels(20), 'series' => $series(20)], ['valueFormat' => 'decimal:1']],
			'twenty-one categories' => [['labels' => $labels(21), 'series' => $series(21)], ['valueFormat' => 'decimal:1']],
			'one tall and one low bar' => [['labels' => ['x'], 'series' => [['name' => 'a', 'values' => [100]], ['name' => 'b', 'values' => [1]]]], []],
		];
	}//end charts()

	/**
	 * The SVG hash matches the recorded one.
	 *
	 * @param array<string, mixed> $data The chart data.
	 * @param array<string, mixed> $options The options.
	 *
	 * @dataProvider charts
	 *
	 * @return void
	 */
	public function testSvg(array $data, array $options): void {
		$svg = (new ChartSvgRenderer())->render(type: 'bar', data: $data, options: $options);
		$trace = ['sha1' => sha1($svg), 'texts' => substr_count($svg, '<text'), 'rects' => substr_count($svg, '<rect')];

		$path = __DIR__ . '/golden/bar-chart-labels.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testSvg()
}//end class
