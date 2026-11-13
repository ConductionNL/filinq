<?php

/**
 * Unit tests for BatchQaSampler (REQ-DDRAS-007).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchQaSampler;
use PHPUnit\Framework\TestCase;

/**
 * The sample is sized by rate, drawn from succeeded files only, and
 * reproducible from the stored seed.
 *
 * @covers \OCA\Filinq\Service\BatchQaSampler
 */
class BatchQaSamplerTest extends TestCase {

	/**
	 * Per-file entries: $ok anonymized and $failed in error.
	 *
	 * @param int $ok     Succeeded files.
	 * @param int $failed Failed files.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function files(int $ok, int $failed = 0): array {
		$files = [];
		for ($i = 1; $i <= $ok; $i++) {
			$files[] = ['fileId' => $i, 'status' => 'anonymized'];
		}

		for ($i = 1; $i <= $failed; $i++) {
			$files[] = ['fileId' => (1000 + $i), 'status' => 'error', 'error' => 'lock'];
		}

		return $files;
	}//end files()

	/**
	 * Five percent of 200 succeeded files is ten, all succeeded ones.
	 *
	 * @return void
	 */
	public function testFivePercentOfTwoHundredIsTen(): void {
		$sample = (new BatchQaSampler())->select(self::files(200, 20), 5, 'seed-a');

		$this->assertCount(10, $sample);
		$this->assertCount(10, array_unique($sample));
		foreach ($sample as $id) {
			$this->assertLessThanOrEqual(200, (int)$id);
		}
	}//end testFivePercentOfTwoHundredIsTen()

	/**
	 * The same seed selects the same files, in any input order; another seed selects others.
	 *
	 * @return void
	 */
	public function testTheSampleIsReproducibleFromTheSeed(): void {
		$sampler = new BatchQaSampler();
		$files = self::files(200);

		$first = $sampler->select($files, 5, 'seed-a');

		$this->assertSame($first, $sampler->select(array_reverse($files), 5, 'seed-a'));
		$this->assertNotSame($first, $sampler->select($files, 5, 'seed-b'));
	}//end testTheSampleIsReproducibleFromTheSeed()

	/**
	 * Rate zero samples nothing; a small batch still gets one sample; nothing succeeded, nothing sampled.
	 *
	 * @return void
	 */
	public function testRateZeroMinimumOneAndNoSuccesses(): void {
		$sampler = new BatchQaSampler();

		$this->assertSame([], $sampler->select(self::files(200), 0, 'seed'));
		$this->assertCount(1, $sampler->select(self::files(3), 5, 'seed'));
		$this->assertSame([], $sampler->select(self::files(0, 4), 50, 'seed'));
		$this->assertCount(3, $sampler->select(self::files(3), 250, 'seed'));
		$this->assertSame([], $sampler->select(self::files(3), -5, 'seed'));
	}//end testRateZeroMinimumOneAndNoSuccesses()

	/**
	 * Marking stores rate and seed on the batch and flags exactly the sample as pending,
	 * keeping an outcome already recorded.
	 *
	 * @return void
	 */
	public function testMarkFlagsTheSampleAndStoresTheSeed(): void {
		$sampler = new BatchQaSampler();
		$files = self::files(40, 2);
		$sample = $sampler->select($files, 10, 'audit-seed');
		$files[(int)$sample[0] - 1]['qaOutcome'] = 'confirmed';

		$batch = $sampler->mark(['batchId' => 'b-1', 'files' => $files], 10, 'audit-seed');

		$this->assertSame([10, 'audit-seed'], [$batch['qaSampleRate'], $batch['qaSampleSeed']]);
		$flagged = [];
		foreach ($batch['files'] as $file) {
			if (($file['qaSampled'] ?? false) === true) {
				$flagged[(string)$file['fileId']] = $file['qaOutcome'];
			}
		}

		$expected = array_fill_keys($sample, 'pending');
		$expected[$sample[0]] = 'confirmed';
		ksort($expected);
		ksort($flagged);
		$this->assertSame($expected, $flagged);
	}//end testMarkFlagsTheSampleAndStoresTheSeed()
}//end class
