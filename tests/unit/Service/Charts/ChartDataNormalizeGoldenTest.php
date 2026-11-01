<?php

/**
 * Golden test for ChartDataNormalizer::normalize over every payload shape.
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

use OCA\Filinq\Service\Charts\ChartDataNormalizer;
use OCA\Filinq\Service\Charts\ChartRenderError;
use PHPUnit\Framework\TestCase;

/**
 * Every payload through normalize(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartDataNormalizer
 * @uses   \OCA\Filinq\Service\Charts\ChartRenderError
 */
class ChartDataNormalizeGoldenTest extends TestCase {

	/**
	 * The payloads and point budgets.
	 *
	 * @return array<string, array{0: array<mixed>, 1: int}>
	 */
	public static function scenarios(): array {
		$labels = ['jan', 'feb', 'mrt'];
		return [
			'one series' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => [1, 2.5, '3']]]], 10],
			'two series, one empty' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => [null, null, null]], ['name' => 'B', 'values' => [null, 4, null]]]], 10],
			'every value a gap' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => [null, 'x', true]]]], 10],
			'values not a list' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => 'x'], ['name' => 'B', 'values' => [1]]]], 10],
			'no values key' => [['labels' => $labels, 'series' => [['name' => 'A']]], 10],
			'a series that is not an array' => [['labels' => $labels, 'series' => ['A', ['name' => 'B', 'values' => [1, 2, 3]]]], 10],
			'only series that are not arrays' => [['labels' => $labels, 'series' => ['A', 7]], 10],
			'no name' => [['labels' => $labels, 'series' => [['values' => [1]]]], 10],
			'a numeric name' => [['labels' => $labels, 'series' => [['name' => 12, 'values' => [1]]]], 10],
			'numeric labels with keys' => [['labels' => ['x' => 1, 'y' => 2.5, 'z' => true], 'series' => [['name' => 'A', 'values' => [1, 2, 3]]]], 10],
			'keyed values' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => ['a' => 1, 1 => 2]]]], 10],
			'more values than labels' => [['labels' => ['jan'], 'series' => [['name' => 'A', 'values' => [1, 2, 3]]]], 10],
			'no labels key' => [['series' => [['name' => 'A', 'values' => [1]]]], 10],
			'no series key' => [['labels' => $labels], 10],
			'empty labels' => [['labels' => [], 'series' => [['values' => [1]]]], 10],
			'empty series' => [['labels' => $labels, 'series' => []], 10],
			'at the budget' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => [1, 2, 3]]]], 3],
			'over the budget' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => [1, 2, 3]]]], 2],
			'numeric strings' => [['labels' => $labels, 'series' => [['name' => 'A', 'values' => ['1e3', ' 2', '0x1A']]]], 10],
		];
	}//end scenarios()

	/**
	 * The result matches the recorded one.
	 *
	 * @param array<mixed> $data The payload.
	 * @param int $maxPoints The point budget.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testNormalize(array $data, int $maxPoints): void {
		$result = (new ChartDataNormalizer())->normalize($data, $maxPoints);
		$trace = $result instanceof ChartRenderError ? ['error' => [$result->message, $result->parameters]] : ['result' => $result];
		$trace = json_decode((string)json_encode($trace, JSON_PRESERVE_ZERO_FRACTION), true);

		$path = __DIR__ . '/golden/chart-data-normalize.json';
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
	}//end testNormalize()
}//end class
