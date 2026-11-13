<?php

/**
 * Unit tests for ChartScale.
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

use OCA\Filinq\Service\Charts\ChartScale;
use PHPUnit\Framework\TestCase;

/**
 * Series maximum and the rounded axis ceiling.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartScale
 */
class ChartScaleTest extends TestCase {

	/**
	 * The largest non-null value across every series is the maximum.
	 *
	 * @return void
	 */
	public function testSeriesMaxTakesTheLargestValueAcrossSeries(): void {
		$scale = new ChartScale();
		$series = [
			['values' => [1.0, null, 3.5]],
			['values' => [null, 7.25, 2.0]],
		];

		$this->assertSame(7.25, $scale->seriesMax(series: $series));
	}//end testSeriesMaxTakesTheLargestValueAcrossSeries()

	/**
	 * Only negative values and gaps leave the maximum at zero.
	 *
	 * @return void
	 */
	public function testSeriesMaxIsZeroForNegativesAndGaps(): void {
		$scale = new ChartScale();

		$this->assertSame(0.0, $scale->seriesMax(series: [['values' => [-4.0, null, -1.0]]]));
		$this->assertSame(0.0, $scale->seriesMax(series: []));
	}//end testSeriesMaxIsZeroForNegativesAndGaps()

	/**
	 * The ceiling rounds up to 1, 2, 5 or 10 times the magnitude.
	 *
	 * @return array<string, array{0: float, 1: float}>
	 */
	public static function ceilings(): array {
		return [
			'exact power of ten' => [100.0, 100.0],
			'just over one'      => [1.2, 2.0],
			'at two'             => [20.0, 20.0],
			'between two, five'  => [3.0, 5.0],
			'at five'            => [500.0, 500.0],
			'over five'          => [7.0, 10.0],
			'below one'          => [0.03, 0.05],
			'large'              => [12345.0, 20000.0],
		];
	}//end ceilings()

	/**
	 * Positive values get a nice ceiling.
	 *
	 * @param float $value    The input value.
	 * @param float $expected The expected ceiling.
	 *
	 * @dataProvider ceilings
	 *
	 * @return void
	 */
	public function testAxisCeilingRoundsUpToANiceNumber(float $value, float $expected): void {
		$this->assertEqualsWithDelta($expected, (new ChartScale())->axisCeiling(value: $value), 1e-9);
	}//end testAxisCeilingRoundsUpToANiceNumber()

	/**
	 * Zero and negative values fall back to a ceiling of one.
	 *
	 * @return void
	 */
	public function testAxisCeilingIsOneForZeroOrNegative(): void {
		$scale = new ChartScale();

		$this->assertSame(1.0, $scale->axisCeiling(value: 0.0));
		$this->assertSame(1.0, $scale->axisCeiling(value: -25.0));
	}//end testAxisCeilingIsOneForZeroOrNegative()
}//end class
