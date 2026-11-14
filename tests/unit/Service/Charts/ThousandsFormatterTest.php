<?php

/**
 * Unit tests for ThousandsFormatter.
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

use OCA\Filinq\Service\Charts\ThousandsFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Dutch number formatting: dot thousands, comma decimals.
 *
 * @covers \OCA\Filinq\Service\Charts\ThousandsFormatter
 */
class ThousandsFormatterTest extends TestCase {

	/**
	 * Values, decimals and the expected text.
	 *
	 * @return array<string, array{0: float, 1: int, 2: string}>
	 */
	public static function cases(): array {
		return [
			'small integer'          => [7.0, 0, '7'],
			'three digits'           => [999.0, 0, '999'],
			'four digits'            => [1000.0, 0, '1.000'],
			'seven digits'           => [1234567.0, 0, '1.234.567'],
			'decimals with comma'    => [1234.5, 2, '1.234,50'],
			'rounding'               => [0.126, 2, '0,13'],
			'negative grouped'       => [-1234567.891, 2, '-1.234.567,89'],
			'negative small'         => [-5.0, 0, '-5'],
			'zero with decimals'     => [0.0, 3, '0,000'],
		];
	}//end cases()

	/**
	 * Each value is grouped and signed as expected.
	 *
	 * @param float  $value    The value.
	 * @param int    $decimals Decimal places.
	 * @param string $expected Expected text.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testFormat(float $value, int $decimals, string $expected): void {
		$this->assertSame($expected, (new ThousandsFormatter())->format(value: $value, decimals: $decimals));
	}//end testFormat()
}//end class
