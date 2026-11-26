<?php

/**
 * Unit tests for BatchResumeSelection.
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
 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchResumeSelection;
use PHPUnit\Framework\TestCase;

/**
 * Which batches resume, and which files a resume queues again.
 *
 * @covers \OCA\Filinq\Service\BatchResumeSelection
 */
class BatchResumeSelectionTest extends TestCase {

	/**
	 * Errored and cancelled batches resume every unfinished file.
	 *
	 * @return void
	 */
	public function testErroredAndCancelledBatchesResumeEveryUnfinishedFile(): void {
		foreach (['error', 'cancelled'] as $status) {
			self::assertSame(
				expected: ['resumable' => true, 'only' => null],
				actual: BatchResumeSelection::forBatch(batch: ['status' => $status, 'files' => []])
			);
		}

	}//end testErroredAndCancelledBatchesResumeEveryUnfinishedFile()

	/**
	 * A completed batch with a file in error resumes only its failed files.
	 *
	 * @return void
	 */
	public function testACompletedBatchWithAFailedFileResumesOnlyFailedFiles(): void {
		$batch = ['status' => 'completed', 'files' => [['status' => 'anonymized'], ['status' => 'error']]];

		self::assertSame(
			expected: ['resumable' => true, 'only' => ['error']],
			actual: BatchResumeSelection::forBatch(batch: $batch)
		);

	}//end testACompletedBatchWithAFailedFileResumesOnlyFailedFiles()

	/**
	 * A completed batch without failed files, or a running batch, is not resumable.
	 *
	 * @return void
	 */
	public function testOtherBatchesAreNotResumable(): void {
		$cases = [
			['status' => 'completed', 'files' => [['status' => 'anonymized'], ['status' => 'prohibitionViolation'], 'x']],
			['status' => 'completed'],
			['status' => 'anonymizing', 'files' => [['status' => 'error']]],
			[],
		];
		foreach ($cases as $batch) {
			self::assertFalse(condition: BatchResumeSelection::forBatch(batch: $batch)['resumable']);
		}

	}//end testOtherBatchesAreNotResumable()
}//end class
