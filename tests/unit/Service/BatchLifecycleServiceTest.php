<?php

/**
 * Unit tests for BatchLifecycleService: cancel and resume of a batch.
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

use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Service\BatchLifecycleService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cancel and resume outcomes, and what each writes.
 *
 * @covers \OCA\Filinq\Service\BatchLifecycleService
 */
class BatchLifecycleServiceTest extends TestCase {

	/**
	 * Batch state double.
	 *
	 * @var BatchStateServiceInterface&MockObject
	 */
	private BatchStateServiceInterface&MockObject $state;

	/**
	 * Subject.
	 *
	 * @var BatchLifecycleService
	 */
	private BatchLifecycleService $service;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->state = $this->createMock(BatchStateServiceInterface::class);
		$this->service = new BatchLifecycleService(stateService: $this->state);

	}//end setUp()

	/**
	 * A batch with the given status and files.
	 *
	 * @param string                           $status The batch status.
	 * @param array<int, array<string, mixed>> $files  The files.
	 *
	 * @return array<string, mixed>
	 */
	private static function batch(string $status, array $files=[]): array {
		return ['batchId' => 'b-1', 'userId' => 'w.devries', 'status' => $status, 'files' => $files];

	}//end batch()

	/**
	 * A batch a worker is busy in becomes cancelling.
	 *
	 * @return void
	 */
	public function testCancellingARunningBatchWaitsForTheWorker(): void {
		$this->state->method('getBatch')->with('b-1')->willReturn(self::batch(status: 'anonymizing'));
		$this->state->expects(self::once())->method('updateBatch')
			->with('b-1', self::callback(static fn (array $b): bool => $b['status'] === 'cancelling'));

		self::assertSame(
			['status' => 202, 'batchId' => 'b-1', 'batchStatus' => 'cancelling'],
			$this->service->cancel(batchId: 'b-1')
		);

	}//end testCancellingARunningBatchWaitsForTheWorker()

	/**
	 * A batch nobody is working on is cancelled at once.
	 *
	 * @return void
	 */
	public function testCancellingAnIdleBatchCancelsItAtOnce(): void {
		$this->state->method('getBatch')->willReturn(self::batch(status: 'review'));
		$this->state->expects(self::once())->method('updateBatch')
			->with('b-1', self::callback(static fn (array $b): bool => $b['status'] === 'cancelled'));

		self::assertSame('cancelled', $this->service->cancel(batchId: 'b-1')['batchStatus']);

	}//end testCancellingAnIdleBatchCancelsItAtOnce()

	/**
	 * Cancelling twice changes nothing the second time.
	 *
	 * @return void
	 */
	public function testCancellingACancellingBatchWritesNothing(): void {
		$this->state->method('getBatch')->willReturn(self::batch(status: 'cancelling'));
		$this->state->expects(self::never())->method('updateBatch');

		self::assertSame(
			['status' => 202, 'batchId' => 'b-1', 'batchStatus' => 'cancelling'],
			$this->service->cancel(batchId: 'b-1')
		);

	}//end testCancellingACancellingBatchWritesNothing()

	/**
	 * Finished batches cannot be cancelled.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function finishedStatuses(): array {
		return ['completed' => ['completed'], 'error' => ['error'], 'cancelled' => ['cancelled']];

	}//end finishedStatuses()

	/**
	 * A finished batch answers 409 and is left alone.
	 *
	 * @param string $status The finished status.
	 *
	 * @dataProvider finishedStatuses
	 *
	 * @return void
	 */
	public function testCancellingAFinishedBatchIsRefused(string $status): void {
		$this->state->method('getBatch')->willReturn(self::batch(status: $status));
		$this->state->expects(self::never())->method('updateBatch');

		self::assertSame(
			['status' => 409, 'reason' => 'not-cancellable', 'batchStatus' => $status],
			$this->service->cancel(batchId: 'b-1')
		);

	}//end testCancellingAFinishedBatchIsRefused()

	/**
	 * A batch the session may not see answers 404 for cancel and resume.
	 *
	 * @return void
	 */
	public function testAnUnknownOrForeignBatchIsNotFound(): void {
		$this->state->method('getBatch')->willReturn(null);
		$this->state->expects(self::never())->method('updateBatch');

		self::assertSame(['status' => 404, 'reason' => 'not-found'], $this->service->cancel(batchId: 'b-9'));
		self::assertSame(['status' => 404, 'reason' => 'not-found'], $this->service->resume(batchId: 'b-9'));

	}//end testAnUnknownOrForeignBatchIsNotFound()

	/**
	 * Resume re-queues only the files that did not succeed.
	 *
	 * @return void
	 */
	public function testResumeRequeuesOnlyTheFilesThatDidNotSucceed(): void {
		$this->state->method('getBatch')->willReturn(
			self::batch(
				status: 'error',
				files: [
					['fileId' => 1, 'status' => 'anonymized', 'anonymizedFileId' => 'a1'],
					['fileId' => 2, 'status' => 'error', 'error' => 'Conversion failed', 'retryCount' => 1],
					['fileId' => 3, 'status' => 'extracted'],
				]
			)
		);
		$written = null;
		$this->state->expects(self::once())->method('updateBatch')
			->willReturnCallback(static function (string $id, array $batch) use (&$written): void {
				$written = $batch;
			});

		$result = $this->service->resume(batchId: 'b-1');

		self::assertSame(['status' => 202, 'batchId' => 'b-1', 'batchStatus' => 'extracting', 'requeued' => 2], $result);
		self::assertSame(['fileId' => 1, 'status' => 'anonymized', 'anonymizedFileId' => 'a1'], $written['files'][0]);
		self::assertSame(['fileId' => 2, 'status' => 'uploaded', 'retryCount' => 2], $written['files'][1]);
		self::assertSame(['fileId' => 3, 'status' => 'extracted'], $written['files'][2]);

	}//end testResumeRequeuesOnlyTheFilesThatDidNotSucceed()

	/**
	 * A cancelled batch whose files are all extracted resumes straight into review.
	 *
	 * @return void
	 */
	public function testResumeWithNothingToExtractGoesBackToReview(): void {
		$this->state->method('getBatch')->willReturn(
			self::batch(status: 'cancelled', files: [['fileId' => 1, 'status' => 'anonymized'], ['fileId' => 2, 'status' => 'extracted']])
		);
		$this->state->expects(self::once())->method('updateBatch')
			->with('b-1', self::callback(static fn (array $b): bool => $b['status'] === 'review'));

		self::assertSame(1, $this->service->resume(batchId: 'b-1')['requeued']);

	}//end testResumeWithNothingToExtractGoesBackToReview()

	/**
	 * A completed batch whose files failed re-queues only those files; the rest keep their outcome.
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 *
	 * @return void
	 */
	public function testACompletedBatchWithFailedFilesRequeuesOnlyThoseFiles(): void {
		$this->state->method('getBatch')->willReturn(
			self::batch(
				status: 'completed',
				files: [
					['fileId' => 1, 'status' => 'anonymized', 'anonymizedFileId' => 'a1'],
					['fileId' => 2, 'status' => 'error', 'error' => 'Refused by the review gate'],
					['fileId' => 3, 'status' => 'prohibitionViolation'],
				]
			)
		);
		$written = null;
		$this->state->expects(self::once())->method('updateBatch')
			->willReturnCallback(static function (string $id, array $batch) use (&$written): void {
				$written = $batch;
			});

		$result = $this->service->resume(batchId: 'b-1');

		self::assertSame(
			expected: ['status' => 202, 'batchId' => 'b-1', 'batchStatus' => 'extracting', 'requeued' => 1],
			actual: $result
		);
		self::assertSame(
			expected: ['fileId' => 1, 'status' => 'anonymized', 'anonymizedFileId' => 'a1'],
			actual: $written['files'][0]
		);
		self::assertSame(expected: ['fileId' => 2, 'status' => 'uploaded', 'retryCount' => 1], actual: $written['files'][1]);
		self::assertSame(expected: ['fileId' => 3, 'status' => 'prohibitionViolation'], actual: $written['files'][2]);
		self::assertIsInt(actual: $written['resumedAt']);

	}//end testACompletedBatchWithFailedFilesRequeuesOnlyThoseFiles()

	/**
	 * A completed batch without failed files still answers 409.
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 *
	 * @return void
	 */
	public function testACompletedBatchWithoutFailedFilesIsRefused(): void {
		$this->state->method('getBatch')->willReturn(
			self::batch(status: 'completed', files: [['fileId' => 1, 'status' => 'anonymized'], ['fileId' => 2, 'status' => 'prohibitionViolation']])
		);
		$this->state->expects(self::never())->method('updateBatch');

		self::assertSame(
			expected: ['status' => 409, 'reason' => 'not-resumable', 'batchStatus' => 'completed'],
			actual: $this->service->resume(batchId: 'b-1')
		);

	}//end testACompletedBatchWithoutFailedFilesIsRefused()

	/**
	 * Running and completed batches cannot be resumed.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function notResumableStatuses(): array {
		return ['completed' => ['completed'], 'anonymizing' => ['anonymizing'], 'cancelling' => ['cancelling'], 'review' => ['review']];

	}//end notResumableStatuses()

	/**
	 * A batch that is not errored or cancelled answers 409.
	 *
	 * @param string $status The status.
	 *
	 * @dataProvider notResumableStatuses
	 *
	 * @return void
	 */
	public function testResumeOfABatchNotErroredOrCancelledIsRefused(string $status): void {
		$this->state->method('getBatch')->willReturn(self::batch(status: $status));
		$this->state->expects(self::never())->method('updateBatch');

		self::assertSame(
			['status' => 409, 'reason' => 'not-resumable', 'batchStatus' => $status],
			$this->service->resume(batchId: 'b-1')
		);

	}//end testResumeOfABatchNotErroredOrCancelledIsRefused()
	/**
	 * A resume marks the run resumed, so a file finished out of band is taken from its link.
	 *
	 * @return void
	 */
	public function testResumeMarksTheRunResumed(): void {
		$this->state->method('getBatch')->willReturn(
			self::batch(status: 'error', files: [['fileId' => 1, 'status' => 'extracted']])
		);
		$written = null;
		$this->state->method('updateBatch')->willReturnCallback(
			static function (string $id, array $batch) use (&$written): void {
				$written = $batch;
			}
		);

		$this->service->resume(batchId: 'b-1');

		self::assertIsInt($written['resumedAt']);
		self::assertSame('review', $written['status']);

	}//end testResumeMarksTheRunResumed()

	/**
	 * A background batch with its stored request resumes straight into the anonymize phase.
	 *
	 * @return void
	 */
	public function testABackgroundBatchWithItsRequestResumesIntoAnonymizing(): void {
		$batch = self::batch(status: 'cancelled', files: [['fileId' => 1, 'status' => 'anonymized'], ['fileId' => 2, 'status' => 'extracted']]);
		$batch['background'] = true;
		$batch['anonymizeRequest'] = ['entities' => [], 'callOptions' => []];
		$this->state->method('getBatch')->willReturn($batch);
		$this->state->expects(self::once())->method('updateBatch')
			->with('b-1', self::callback(static fn (array $b): bool => $b['status'] === 'anonymizing'));

		$result = $this->service->resume(batchId: 'b-1');

		self::assertSame('anonymizing', $result['batchStatus']);
		self::assertSame(1, $result['requeued']);

	}//end testABackgroundBatchWithItsRequestResumesIntoAnonymizing()
}//end class
