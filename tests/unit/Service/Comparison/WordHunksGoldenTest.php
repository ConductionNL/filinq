<?php

/**
 * Golden hunk lists of the word-level diff.
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each pair's hunks, key order included, compared with JSON recorded from the
 * implementation before diff() and backtrack() were split.
 *
 * @covers \OCA\Filinq\Service\Comparison\WordDiffer
 * @uses   \OCA\Filinq\Service\Comparison\HunkWalk
 * @uses   \OCA\Filinq\Service\Comparison\LcsWalk
 */
class WordHunksGoldenTest extends TestCase {

	/**
	 * Text pairs and their recorded hunks.
	 *
	 * @return array<string,array{0:string,1:string,2:string}>
	 */
	public static function pairs(): array {
		return [
			' / ' => [
				'',
				'',
				'[]',
			],
			'a b c / a b c' => [
				'a b c',
				'a b c',
				'[{"type":"unchanged","left":{"offset":0,"length":5},"right":{"offset":0,"length":5},"leftText":"a b c","rightText":"a b c"}]',
			],
			'a b c / a x c' => [
				'a b c',
				'a x c',
				'[{"type":"unchanged","left":{"offset":0,"length":1},"right":{"offset":0,"length":1},"leftText":"a","rightText":"a"},{"type":"changed","left":{"offset":2,"length":1},"leftText":"b","right":{"offset":2,"length":1},"rightText":"x"},{"type":"unchanged","left":{"offset":4,"length":1},"right":{"offset":4,"length":1},"leftText":"c","rightText":"c"}]',
			],
			'a b / a b c d' => [
				'a b',
				'a b c d',
				'[{"type":"unchanged","left":{"offset":0,"length":3},"right":{"offset":0,"length":3},"leftText":"a b","rightText":"a b"},{"type":"added","left":null,"right":{"offset":4,"length":3},"rightText":"c d"}]',
			],
			'a b c d / c d' => [
				'a b c d',
				'c d',
				'[{"type":"removed","left":{"offset":0,"length":3},"leftText":"a b","right":null},{"type":"unchanged","left":{"offset":4,"length":3},"right":{"offset":0,"length":3},"leftText":"c d","rightText":"c d"}]',
			],
			'the quick brown fox / a quick red fox jumps' => [
				'the quick brown fox',
				'a quick red fox jumps',
				'[{"type":"changed","left":{"offset":0,"length":3},"leftText":"the","right":{"offset":0,"length":1},"rightText":"a"},{"type":"unchanged","left":{"offset":4,"length":5},"right":{"offset":2,"length":5},"leftText":"quick","rightText":"quick"},{"type":"changed","left":{"offset":10,"length":5},"leftText":"brown","right":{"offset":8,"length":3},"rightText":"red"},{"type":"unchanged","left":{"offset":16,"length":3},"right":{"offset":12,"length":3},"leftText":"fox","rightText":"fox"},{"type":"added","left":null,"right":{"offset":16,"length":5},"rightText":"jumps"}]',
			],
			' / new words' => [
				'',
				'new words',
				'[{"type":"added","left":null,"right":{"offset":0,"length":9},"rightText":"new words"}]',
			],
			'old words / ' => [
				'old words',
				'',
				'[{"type":"removed","left":{"offset":0,"length":9},"leftText":"old words","right":null}]',
			],
		];

	}//end pairs()

	/**
	 * The hunks match the recorded ones exactly.
	 *
	 * @param string $left  The left text
	 * @param string $right The right text
	 * @param string $json  The recorded hunks
	 *
	 * @return void
	 */
	#[DataProvider('pairs')]
	public function testHunksMatchTheRecordedOnes(string $left, string $right, string $json): void {
		$this->assertSame($json, json_encode((new WordDiffer())->diff($left, $right)));

	}//end testHunksMatchTheRecordedOnes()
}//end class
