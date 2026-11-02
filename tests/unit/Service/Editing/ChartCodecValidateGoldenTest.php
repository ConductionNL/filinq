<?php

/**
 * Golden test for ChartCodec's validation of a chart definition.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\ChartCodec;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Every definition through validate(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Editing\ChartCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 * @uses   \OCA\Filinq\Service\Editing\XmlBlockScanner
 */
class ChartCodecValidateGoldenTest extends TestCase {

	/**
	 * The chart definitions.
	 *
	 * @return array<string, array{0: array<mixed>}>
	 */
	public static function scenarios(): array {
		$categories = ['2024', '2025'];
		return [
			'a bar chart' => [['type' => 'bar', 'title' => 'Aanvragen', 'categories' => $categories, 'series' => [['name' => 'Woo', 'values' => [3, '4.5']]]]],
			'an upper-case type' => [['type' => 'LINE', 'categories' => $categories, 'series' => [['values' => [1, 2]], ['name' => 7, 'values' => ['a' => 1, 'b' => 2]]]]],
			'a pie chart' => [['type' => 'pie', 'categories' => ['x'], 'series' => [['name' => 'Deel', 'values' => [1]]]]],
			'a pie with two series' => [['type' => 'pie', 'categories' => ['x'], 'series' => [['values' => [1]], ['values' => [2]]]]],
			'a line with two series' => [['type' => 'line', 'categories' => ['x'], 'series' => [['values' => [1]], ['values' => [2]]]]],
			'no type' => [['categories' => $categories, 'series' => [['values' => [1, 2]]]]],
			'an unknown type' => [['type' => 'radar', 'categories' => $categories, 'series' => [['values' => [1, 2]]]]],
			'no categories' => [['type' => 'bar', 'series' => [['values' => [1]]]]],
			'empty categories' => [['type' => 'bar', 'categories' => [], 'series' => [['values' => [1]]]]],
			'a single category string' => [['type' => 'bar', 'categories' => 'alles', 'series' => [['values' => [5]]]]],
			'keyed categories' => [['type' => 'bar', 'categories' => ['a' => 'x', 'b' => 2], 'series' => [['values' => [1, 2]]]]],
			'no series' => [['type' => 'bar', 'categories' => $categories]],
			'empty series' => [['type' => 'bar', 'categories' => $categories, 'series' => []]],
			'a series too short' => [['type' => 'bar', 'categories' => $categories, 'series' => [['values' => [1, 2]], ['values' => [1]]]]],
			'a series too long' => [['type' => 'bar', 'categories' => $categories, 'series' => [['values' => [1, 2, 3]]]]],
			'a series without values' => [['type' => 'bar', 'categories' => $categories, 'series' => [['name' => 'Leeg']]]],
			'a series that is a scalar' => [['type' => 'bar', 'categories' => ['x'], 'series' => [5]]],
			'keyed series' => [['type' => 'bar', 'categories' => ['x'], 'series' => ['eerste' => ['values' => [1]], 'tweede' => ['values' => [2]]]]],
			'a numeric title' => [['type' => 'bar', 'title' => 2026, 'categories' => ['x'], 'series' => [['values' => [true]]]]],
		];
	}//end scenarios()

	/**
	 * The normalised definition, or the refusal, matches the recorded one.
	 *
	 * @param array<mixed> $chart The definition.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testValidate(array $chart): void {
		$codec = new ChartCodec();
		try {
			$trace = ['chart' => (new ReflectionMethod($codec, 'validate'))->invoke($codec, $chart)];
		} catch (RuntimeException $e) {
			$trace = ['error' => $e->getMessage()];
		}

		$trace = json_decode((string)json_encode($trace, JSON_PRESERVE_ZERO_FRACTION), true);

		$path = __DIR__ . '/golden/chart-codec-validate.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testValidate()
}//end class
