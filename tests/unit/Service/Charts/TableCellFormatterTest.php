<?php

/**
 * Unit tests for TableCellFormatter.
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

use OCA\Filinq\Service\Charts\TableCellFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Cell values per column format.
 *
 * @covers \OCA\Filinq\Service\Charts\TableCellFormatter
 * @uses   \OCA\Filinq\Service\Charts\ThousandsFormatter
 */
class TableCellFormatterTest extends TestCase {

	/**
	 * The timezone in force before the test.
	 *
	 * @var string
	 */
	private string $previousTimezone;

	/**
	 * Pin the timezone so date output is deterministic.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->previousTimezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
	}//end setUp()

	/**
	 * Restore the timezone.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		date_default_timezone_set($this->previousTimezone);
		parent::tearDown();
	}//end tearDown()

	/**
	 * Values, formats and the expected cell text.
	 *
	 * @return array<string, array{0: mixed, 1: string, 2: string}>
	 */
	public static function cells(): array {
		return [
			'null is empty'              => [null, 'number', ''],
			'text passthrough'           => ['Hello', 'text', 'Hello'],
			'int as text'                => [42, 'text', '42'],
			'unknown format as text'     => [3.5, 'weird', '3.5'],
			'whole number no decimals'   => [1234567, 'number', '1.234.567'],
			'whole float no decimals'    => [2000.0, 'number', '2.000'],
			'fraction two decimals'      => [1234.5, 'number', '1.234,50'],
			'numeric string'             => ['987.654', 'number', '987,65'],
			'non numeric number'         => ['n/a', 'number', 'n/a'],
			'currency whole'             => [1500, 'currency', '€ 1.500'],
			'currency fraction'          => ['12.3', 'currency', '€ 12,30'],
			'currency non numeric'       => ['free', 'currency', 'free'],
			'negative number'            => [-1234.25, 'number', '-1.234,25'],
			'date string'                => ['2026-03-05', 'date', '05-03-2026'],
			'date timestamp int'         => [86400 * 2, 'date', '03-01-1970'],
			'date timestamp string'      => ['0', 'date', '01-01-1970'],
			'unparseable date string'    => ['not a date', 'date', 'not a date'],
			'date bool passthrough'      => [true, 'date', '1'],
		];
	}//end cells()

	/**
	 * Each value renders as expected.
	 *
	 * @param mixed  $value    The cell value.
	 * @param string $format   The column format.
	 * @param string $expected Expected text.
	 *
	 * @dataProvider cells
	 *
	 * @return void
	 */
	public function testFormat($value, string $format, string $expected): void {
		$this->assertSame($expected, (new TableCellFormatter())->format(value: $value, format: $format));
	}//end testFormat()
}//end class
