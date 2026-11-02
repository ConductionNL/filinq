<?php

/**
 * Golden op lists of the word-level LCS diff.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Comparison;

use OCA\Filinq\Service\Comparison\WordDiffer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Each pair's equal, delete and insert ops, compared with JSON recorded from
 * the implementation before backtrack() was split.
 *
 * @covers \OCA\Filinq\Service\Comparison\WordDiffer
 */
class WordOpsGoldenTest extends TestCase {

	/**
	 * The word list pairs.
	 *
	 * @return array<string, array{0: array<int, string>, 1: array<int, string>}>
	 */
	public static function pairs(): array {
		return [
			'both empty' => [[], []],
			'only left' => [['a', 'b'], []],
			'only right' => [[], ['a', 'b']],
			'equal' => [['de', 'brief', 'is', 'klaar'], ['de', 'brief', 'is', 'klaar']],
			'one word replaced' => [['de', 'brief', 'is', 'klaar'], ['de', 'nota', 'is', 'klaar']],
			'a tail removed' => [['a', 'b', 'c', 'd'], ['a', 'b']],
			'a tail added' => [['a', 'b'], ['a', 'b', 'c', 'd']],
			'a head added' => [['c', 'd'], ['a', 'b', 'c', 'd']],
			'crossing' => [['a', 'b', 'c', 'a', 'b', 'b', 'a'], ['c', 'b', 'a', 'b', 'a', 'c']],
			'a tie' => [['x', 'y'], ['y', 'x']],
			'nothing shared' => [['a', 'b'], ['c', 'd', 'e']],
			'repeats' => [['a', 'a', 'b', 'a'], ['a', 'b', 'a', 'a']],
		];
	}//end pairs()

	/**
	 * The ops match the recorded ones.
	 *
	 * @param array<int, string> $left  The left words.
	 * @param array<int, string> $right The right words.
	 *
	 * @dataProvider pairs
	 *
	 * @return void
	 */
	public function testOpsMatchTheRecordedOnes(array $left, array $right): void {
		$ops = (new ReflectionMethod(WordDiffer::class, 'lcsDiff'))->invoke(new WordDiffer(), $left, $right);

		$path = __DIR__ . '/golden/word-ops.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $ops;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $ops);
	}//end testOpsMatchTheRecordedOnes()
}//end class
