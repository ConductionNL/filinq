<?php

/**
 * Unit tests for ChartLabelFormatter.
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
use PHPUnit\Framework\TestCase;

/**
 * Value formats and label truncation.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartLabelFormatter
 * @uses   \OCA\Filinq\Service\Charts\ThousandsFormatter
 */
class ChartLabelFormatterTest extends TestCase {

	/**
	 * Values, formats and the expected label.
	 *
	 * @return array<string, array{0: float, 1: string, 2: string}>
	 */
	public static function formats(): array {
		return [
			'percent'                  => [0.256, 'percent', '26%'],
			'percent over hundred'     => [1.5, 'percent', '150%'],
			'decimal two'              => [3.14159, 'decimal:2', '3.14'],
			'decimal zero'             => [3.6, 'decimal:0', '4'],
			'decimal clamped to six'   => [1.0, 'decimal:9', '1.000000'],
			'decimal negative clamped' => [2.7, 'decimal:-3', '3'],
			'decimal not a number'     => [2.4, 'decimal:abc', '2'],
			'currency'                 => [1234.5, 'currency', '€ 1.234,50'],
			'integer default'          => [12345.6, 'integer', '12.346'],
			'unknown format'           => [999.4, 'whatever', '999'],
		];
	}//end formats()

	/**
	 * Each format renders the value as expected.
	 *
	 * @param float  $value    The value.
	 * @param string $format   The format.
	 * @param string $expected Expected label.
	 *
	 * @dataProvider formats
	 *
	 * @return void
	 */
	public function testFormatValue(float $value, string $format, string $expected): void {
		$this->assertSame($expected, (new ChartLabelFormatter())->formatValue(value: $value, format: $format));
	}//end testFormatValue()

	/**
	 * Text within the limit is returned unchanged.
	 *
	 * @return void
	 */
	public function testTruncateKeepsShortText(): void {
		$formatter = new ChartLabelFormatter();

		$this->assertSame('abc', $formatter->truncate(text: 'abc', max: 3));
		$this->assertSame('', $formatter->truncate(text: '', max: 0));
	}//end testTruncateKeepsShortText()

	/**
	 * Longer text is cut to max - 1 bytes plus an ellipsis.
	 *
	 * @return void
	 */
	public function testTruncateCutsLongTextWithEllipsis(): void {
		$formatter = new ChartLabelFormatter();

		$this->assertSame('abcd…', $formatter->truncate(text: 'abcdefgh', max: 5));
	}//end testTruncateCutsLongTextWithEllipsis()

	/**
	 * A max below two still keeps the first byte.
	 *
	 * @return void
	 */
	public function testTruncateKeepsAtLeastOneCharacter(): void {
		$formatter = new ChartLabelFormatter();

		$this->assertSame('a…', $formatter->truncate(text: 'abc', max: 1));
		$this->assertSame('a…', $formatter->truncate(text: 'abc', max: 0));
	}//end testTruncateKeepsAtLeastOneCharacter()
}//end class
