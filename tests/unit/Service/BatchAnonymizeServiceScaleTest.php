<?php

/**
 * Unit tests for BatchAnonymizeService's cooperative cancellation and QA sampling.
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
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchQaSampler;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cancellation between files, and the QA sample marked on completion.
 *
 * @covers \OCA\Filinq\Service\BatchAnonymizeService
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRun
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRequest
 * @uses   \OCA\Filinq\Service\BatchQaSampler
 * @uses   \OCA\Filinq\Service\AnonymizeOptions
 */
class BatchAnonymizeServiceScaleTest extends TestCase {

	/**
	 * Single-document anonymization double.
	 *
	 * @var AnonymizationService&MockObject
	 */
	private AnonymizationService&MockObject $anon;

	/**
	 * Batch state double.
	 *
	 * @var BatchStateService&MockObject
	 */
	private BatchStateService&MockObject $state;

	/**
	 * Output mover that leaves every file in place.
	 *
	 * @var OutputLayoutMover&MockObject
	 */
	private OutputLayoutMover&MockObject $mover;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->anon = $this->createMock(AnonymizationService::class);
		$this->anon->method('anonymizeDocument')->willReturnCallback(
			static fn (int $fileId): array => ['replacementCount' => 1, 'anonymizedFileId' => 'anon-' . $fileId]
		);
		$this->state = $this->createMock(BatchStateService::class);
		$this->mover = $this->createMock(OutputLayoutMover::class);
		$this->mover->method('relocate')->willReturnCallback(
			static fn (string $userId, int $fileId, string $legacyPath): array => ['path' => $legacyPath, 'warning' => null]
		);

	}//end setUp()

	/**
	 * A batch of extracted files.
	 *
	 * @param int                  $count Number of files.
	 * @param array<string, mixed> $extra Extra batch fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function batch(int $count, array $extra=[]): array {
		$files = [];
		for ($i = 1; $i <= $count; $i++) {
			$files[] = ['fileId' => $i, 'status' => 'extracted'];
		}

		return array_merge(['batchId' => 'b-1', 'userId' => 'u', 'status' => 'review', 'files' => $files], $extra);

	}//end batch()

	/**
	 * A cancel stored after the second file stops the run before the third.
	 *
	 * @return void
	 */
	public function testACancelStopsTheRunBetweenFiles(): void {
		$batch = self::batch(count: 4);
		$reads = 0;
		$this->state->method('getBatch')->willReturnCallback(
			static function () use ($batch, &$reads): array {
				$reads++;
				// Read 1 starts the run; reads 2 and 3 precede files 1 and 2;
				// from read 4 on (before file 3) the owner has cancelled.
				if ($reads >= 4) {
					$batch['status'] = 'cancelling';
				}

				return $batch;
			}
		);
		$this->anon->expects(self::exactly(2))->method('anonymizeDocument');
		$last = null;
		$this->state->method('updateBatch')->willReturnCallback(
			static function (string $id, array $b) use (&$last): void {
				$last = $b;
			}
		);

		$service = new BatchAnonymizeService($this->anon, $this->state, $this->mover);
		$result = $service->anonymizeBatch(batchId: 'b-1', entities: ['PERSON']);

		self::assertSame('cancelled', $result['batchStatus']);
		self::assertSame(2, $result['processedFiles']);
		self::assertSame('cancelled', $last['status']);
		self::assertSame(['anonymized', 'anonymized', 'extracted', 'extracted'], array_column($last['files'], 'status'));

	}//end testACancelStopsTheRunBetweenFiles()

	/**
	 * On completion the configured share of succeeded files is sampled, with the seed stored.
	 *
	 * @return void
	 */
	public function testCompletionMarksTheQaSampleWithTheConfiguredRate(): void {
		$this->state->method('getBatch')->willReturn(self::batch(count: 20, extra: ['qaSampleSeed' => 'seed-1']));
		$last = null;
		$this->state->method('updateBatch')->willReturnCallback(
			static function (string $id, array $b) use (&$last): void {
				$last = $b;
			}
		);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->with('filinq', 'batch.qa_sample_rate', '5')->willReturn('10');

		$service = new BatchAnonymizeService($this->anon, $this->state, $this->mover, new BatchQaSampler(), $config);
		$result = $service->anonymizeBatch(batchId: 'b-1', entities: ['PERSON']);

		self::assertSame('completed', $result['batchStatus']);
		self::assertSame('seed-1', $last['qaSampleSeed']);
		self::assertSame(10, $last['qaSampleRate']);
		$sampled = array_values(array_filter($last['files'], static fn (array $f): bool => ($f['qaSampled'] ?? false) === true));
		self::assertCount(2, $sampled);
		self::assertSame(['pending', 'pending'], array_column($sampled, 'qaOutcome'));
		$expected = (new BatchQaSampler())->select(files: $last['files'], rate: 10, seed: 'seed-1');
		self::assertEqualsCanonicalizing($expected, array_map('strval', array_column($sampled, 'fileId')));

	}//end testCompletionMarksTheQaSampleWithTheConfiguredRate()

	/**
	 * Without a stored seed one is generated and stored, so the sample can be recomputed.
	 *
	 * @return void
	 */
	public function testCompletionStoresAGeneratedSeed(): void {
		$this->state->method('getBatch')->willReturn(self::batch(count: 3));
		$last = null;
		$this->state->method('updateBatch')->willReturnCallback(
			static function (string $id, array $b) use (&$last): void {
				$last = $b;
			}
		);

		$service = new BatchAnonymizeService($this->anon, $this->state, $this->mover, new BatchQaSampler());
		$service->anonymizeBatch(batchId: 'b-1', entities: ['PERSON']);

		self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $last['qaSampleSeed']);
		self::assertSame(5, $last['qaSampleRate']);
		self::assertCount(1, array_filter($last['files'], static fn (array $f): bool => ($f['qaSampled'] ?? false) === true));

	}//end testCompletionStoresAGeneratedSeed()
}//end class
