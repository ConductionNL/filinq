<?php

/**
 * Unit tests for BatchProgress: the progress figures of a batch.
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
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchProgress;
use PHPUnit\Framework\TestCase;

/**
 * Counts, percentage per phase, and documents per hour.
 *
 * @covers \OCA\Filinq\Service\BatchProgress
 */
class BatchProgressTest extends TestCase {

	/**
	 * Files with the given statuses.
	 *
	 * @param array<int, string> $statuses The statuses.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function files(array $statuses): array {
		return array_map(static fn (string $s): array => ['status' => $s], $statuses);

	}//end files()

	/**
	 * In the anonymize phase only anonymized and failed files count as done.
	 *
	 * @return void
	 */
	public function testTheAnonymizePhaseCountsFinishedFiles(): void {
		$batch = [
			'status' => 'anonymizing',
			'startedAt' => 0,
			'files' => self::files(['anonymized', 'anonymized', 'error', 'extracted', 'prohibitionViolation', 'extracted', 'extracted', 'extracted']),
		];

		$progress = (new BatchProgress())->figuresFor(batch: $batch, now: 3600);

		$this->assertSame(2, $progress['filesSucceeded']);
		$this->assertSame(2, $progress['filesFailed']);
		$this->assertSame(4, $progress['filesPending']);
		$this->assertSame(50.0, $progress['progressPercent']);
		$this->assertSame(2.0, $progress['documentsPerHour']);

	}//end testTheAnonymizePhaseCountsFinishedFiles()

	/**
	 * In the extract phase an extracted file counts as done too.
	 *
	 * @return void
	 */
	public function testTheExtractPhaseCountsExtractedFiles(): void {
		$batch = ['status' => 'extracting', 'files' => self::files(['extracted', 'error', 'uploaded', 'uploaded', 'uploaded'])];

		$progress = (new BatchProgress())->figuresFor(batch: $batch, now: 100);

		$this->assertSame(40.0, $progress['progressPercent']);
		$this->assertNull($progress['documentsPerHour']);

	}//end testTheExtractPhaseCountsExtractedFiles()

	/**
	 * A completed batch is measured to its completion, and an empty one is 0%.
	 *
	 * @return void
	 */
	public function testThroughputEndsAtCompletion(): void {
		$batch = ['status' => 'completed', 'startedAt' => 1000, 'completedAt' => 1000 + 21600, 'files' => self::files(array_fill(0, 480, 'anonymized'))];

		$progress = (new BatchProgress())->figuresFor(batch: $batch, now: 999999);

		$this->assertSame(80.0, $progress['documentsPerHour']);
		$this->assertSame(100.0, $progress['progressPercent']);
		$this->assertSame(0.0, (new BatchProgress())->figuresFor(batch: ['status' => 'review', 'files' => []], now: 0)['progressPercent']);
		$this->assertNull((new BatchProgress())->figuresFor(batch: ['startedAt' => 0, 'files' => []], now: 30)['documentsPerHour']);

	}//end testThroughputEndsAtCompletion()

	/**
	 * The start is also written as a date and time, so a list shows it as a date
	 * rather than a bare number of seconds; before the start there is none.
	 *
	 * @return void
	 */
	public function testTheStartIsWrittenAsADateAndTime(): void {
		$started = (new BatchProgress())->figuresFor(batch: ['startedAt' => 1784189400, 'files' => []], now: 1784189500);
		$this->assertSame(expected: '2026-07-16T08:10:00+00:00', actual: $started['startedOn']);

		$notStarted = (new BatchProgress())->figuresFor(batch: ['files' => []], now: 1784189500);
		$this->assertNull(actual: $notStarted['startedOn']);
	}//end testTheStartIsWrittenAsADateAndTime()
}//end class
