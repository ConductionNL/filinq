<?php

/**
 * Unit tests for BatchWorkUnit: budgets, phases, cancellation and lock-miss retries.
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
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchBackgroundPath;
use OCA\Filinq\Service\BatchRouting;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\BatchWorkUnit;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * One work unit of a batch, against a stateful store double.
 *
 * @covers \OCA\Filinq\Service\BatchWorkUnit
 * @uses   \OCA\Filinq\Service\BatchAnonymizeService
 * @uses   \OCA\Filinq\Service\BatchBackgroundPath
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRun
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRequest
 * @uses   \OCA\Filinq\Service\BatchLifecycleService
 * @uses   \OCA\Filinq\Service\BatchRetryPolicy
 * @uses   \OCA\Filinq\Service\AnonymizeOptions
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class BatchWorkUnitTest extends TestCase {

	/**
	 * The stored batch the store double serves.
	 *
	 * @var array<string, mixed>
	 */
	private array $stored = [];

	/**
	 * How many writes the store double took.
	 *
	 * @var int
	 */
	private int $writes = 0;

	/**
	 * Called on every store read; may change the stored batch (a cancel from another request).
	 *
	 * @var callable|null
	 */
	private $onRead = null;

	/**
	 * Single-document anonymization double.
	 *
	 * @var AnonymizationService&MockObject
	 */
	private AnonymizationService&MockObject $anon;

	/**
	 * What every anonymize call throws, or null for success.
	 *
	 * @var \Throwable|null
	 */
	private ?\Throwable $anonymizeFailure = null;

	/**
	 * The clock's current time.
	 *
	 * @var int
	 */
	private int $now = 1000;

	/**
	 * Seconds the clock moves on each extraction.
	 *
	 * @var int
	 */
	private int $secondsPerFile = 0;

	/**
	 * Subject.
	 *
	 * @var BatchWorkUnit
	 */
	private BatchWorkUnit $unit;

	/**
	 * Build the subject over a stateful store double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$state = $this->createMock(BatchStateService::class);
		$state->method('getBatch')->willReturnCallback(
			function (): ?array {
				if ($this->onRead !== null) {
					($this->onRead)();
				}

				return $this->stored;
			}
		);
		$state->method('updateBatch')->willReturnCallback(
			function (string $batchId, array $batch): void {
				$this->writes++;
				$this->stored = $batch;
			}
		);

		$this->anon = $this->createMock(AnonymizationService::class);
		$this->anon->method('anonymizeDocument')->willReturnCallback(
			function (int $fileId): array {
				if ($this->anonymizeFailure !== null) {
					throw $this->anonymizeFailure;
				}

				return ['replacementCount' => 2, 'anonymizedFileId' => (1000 + $fileId)];
			}
		);
		$mover = $this->createMock(OutputLayoutMover::class);
		$mover->method('relocate')->willReturnCallback(
			static fn (string $userId, int $anonymizedFileId, string $legacyPath): array => ['path' => $legacyPath, 'warning' => null]
		);

		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getTime')->willReturnCallback(fn (): int => $this->now);

		$anonymizer = new BatchAnonymizeService(anonService: $this->anon, stateService: $state, layoutMover: $mover);
		$this->unit = new BatchWorkUnit(
			stateService: $state,
			anonService: $this->anon,
			anonymizer: $anonymizer,
			background: new BatchBackgroundPath(
				stateService: $state,
				routing: $this->createMock(BatchRouting::class),
				linkReader: $this->createMock(AnonymizationLinkReader::class)
			),
			clock: $clock,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * Store a batch of files all in one status.
	 *
	 * @param int                  $count      Number of files.
	 * @param string               $fileStatus Every file's status.
	 * @param string               $status     The batch status.
	 * @param array<string, mixed> $extra      Extra batch fields.
	 *
	 * @return void
	 */
	private function store(int $count, string $fileStatus, string $status, array $extra=[]): void {
		$files = [];
		for ($i = 1; $i <= $count; $i++) {
			$files[] = ['fileId' => $i, 'fileName' => 'f' . $i . '.pdf', 'status' => $fileStatus];
		}

		$this->stored = array_merge(
			['batchId' => 'b-1', 'userId' => 'u', 'status' => $status, 'background' => true, 'files' => $files],
			$extra
		);

	}//end store()

	/**
	 * Make every extraction succeed and move the clock on.
	 *
	 * @return void
	 */
	private function extractionsSucceed(): void {
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			function (int $fileId): array {
				$this->now += $this->secondsPerFile;
				return ['entityCount' => $fileId];
			}
		);

	}//end extractionsSucceed()

	/**
	 * The number of stored files with a status.
	 *
	 * @param string $status The file status.
	 *
	 * @return int
	 */
	private function countFiles(string $status): int {
		return count(array_filter($this->stored['files'], static fn (array $f): bool => $f['status'] === $status));

	}//end countFiles()

	/**
	 * Sixty files at 25 per unit take three units: 25, 25, then the last 10.
	 *
	 * @return void
	 */
	public function testALargeBatchIsProcessedAcrossThreeUnits(): void {
		$this->store(count: 60, fileStatus: 'uploaded', status: 'extracting');
		$this->extractionsSucceed();

		$first = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 120);
		$this->assertSame(25, $first['processed']);
		$this->assertSame('extracting', $first['batchStatus']);
		$this->assertSame(25, $this->countFiles(status: 'extracted'));

		$second = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 120);
		$this->assertSame(25, $second['processed']);
		$this->assertSame(50, $this->countFiles(status: 'extracted'));

		$third = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 120);
		$this->assertSame(10, $third['processed']);
		$this->assertSame('review', $third['batchStatus']);
		$this->assertSame('review', $this->stored['status']);
		$this->assertSame(60, $this->countFiles(status: 'extracted'));
		$this->assertSame(17, $this->stored['files'][16]['entityCount']);

	}//end testALargeBatchIsProcessedAcrossThreeUnits()

	/**
	 * Every file is written as soon as it is done, so the file boundary is the recovery boundary.
	 *
	 * @return void
	 */
	public function testEachFileIsWrittenBeforeTheNextStarts(): void {
		$this->store(count: 3, fileStatus: 'uploaded', status: 'extracting');
		$seen = [];
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			function (int $fileId) use (&$seen): array {
				$seen[] = $this->countFiles(status: 'extracted');
				return ['entityCount' => 1];
			}
		);

		$this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame([0, 1, 2], $seen);
		$this->assertSame(4, $this->writes);
		$this->assertArrayHasKey('extractMs', $this->stored['files'][0]);

	}//end testEachFileIsWrittenBeforeTheNextStarts()

	/**
	 * The time budget ends a unit early after the file in progress, and a slow unit is throttled.
	 *
	 * @return void
	 */
	public function testTheTimeBudgetEndsTheUnitAndMarksItThrottled(): void {
		$this->store(count: 25, fileStatus: 'uploaded', status: 'extracting');
		$this->secondsPerFile = 30;
		$this->extractionsSucceed();

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 120);

		$this->assertSame(4, $outcome['processed']);
		$this->assertTrue($outcome['throttled']);
		$this->assertTrue($this->stored['throttled']);
		$this->assertSame('extracting', $this->stored['status']);
		$this->assertSame(4, $this->countFiles(status: 'extracted'));

	}//end testTheTimeBudgetEndsTheUnitAndMarksItThrottled()

	/**
	 * Running out of time after a fair share of the file budget is not throttling.
	 *
	 * @return void
	 */
	public function testATimeBudgetSpentOnEightFilesIsNotThrottled(): void {
		$this->store(count: 25, fileStatus: 'uploaded', status: 'extracting');
		$this->secondsPerFile = 15;
		$this->extractionsSucceed();

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 120);

		$this->assertSame(8, $outcome['processed']);
		$this->assertFalse($outcome['throttled']);

	}//end testATimeBudgetSpentOnEightFilesIsNotThrottled()

	/**
	 * A cancel that arrives while a file runs lets that file finish and starts no other.
	 *
	 * @return void
	 */
	public function testACancelTakesEffectBetweenFiles(): void {
		$this->store(count: 6, fileStatus: 'uploaded', status: 'extracting');
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			function (int $fileId): array {
				if ($fileId === 2) {
					$this->stored['status'] = 'cancelling';
				}

				return ['entityCount' => 1];
			}
		);

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('cancelled', $outcome['batchStatus']);
		$this->assertSame(2, $outcome['processed']);
		$this->assertSame('cancelled', $this->stored['status']);
		$this->assertSame(2, $this->countFiles(status: 'extracted'));
		$this->assertSame(4, $this->countFiles(status: 'uploaded'));

	}//end testACancelTakesEffectBetweenFiles()

	/**
	 * A batch left cancelling is closed as cancelled without touching a file.
	 *
	 * @return void
	 */
	public function testACancellingBatchIsClosedWithoutProcessing(): void {
		$this->store(count: 2, fileStatus: 'extracted', status: 'cancelling');
		$this->anon->expects($this->never())->method('anonymizeDocument');

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('cancelled', $outcome['batchStatus']);
		$this->assertSame(0, $outcome['processed']);
		$this->assertSame('cancelled', $this->stored['status']);

	}//end testACancellingBatchIsClosedWithoutProcessing()

	/**
	 * A conversion lock miss puts the file back in the queue with its retry count raised.
	 *
	 * @return void
	 */
	public function testALockMissQueuesTheFileAgain(): void {
		$this->store(count: 2, fileStatus: 'uploaded', status: 'extracting');
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			static function (int $fileId): array {
				if ($fileId === 1) {
					throw new ConversionFailedException(
						message: 'no backend',
						attempts: [['name' => 'libreoffice', 'reason' => 'could not acquire soffice:headless:convert lock: held']]
					);
				}

				return ['entityCount' => 3];
			}
		);

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('extracting', $outcome['batchStatus']);
		$this->assertSame('uploaded', $this->stored['files'][0]['status']);
		$this->assertSame(1, $this->stored['files'][0]['retryCount']);
		$this->assertNull($this->stored['files'][0]['error']);
		$this->assertSame('extracted', $this->stored['files'][1]['status']);

	}//end testALockMissQueuesTheFileAgain()

	/**
	 * A file whose lock-miss retries are used up becomes an error with the lock category.
	 *
	 * @return void
	 */
	public function testALockMissAfterTheLastRetryIsAnError(): void {
		$this->store(count: 1, fileStatus: 'uploaded', status: 'extracting');
		$this->stored['files'][0]['retryCount'] = 3;
		$this->anon->method('extractAndDetectEntities')->willThrowException(
			new ConversionFailedException(
				message: 'no backend',
				attempts: [['reason' => 'could not acquire soffice:headless:convert lock: held']]
			)
		);

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('review', $outcome['batchStatus']);
		$this->assertSame('error', $this->stored['files'][0]['status']);
		$this->assertSame('conversion-lock-timeout', $this->stored['files'][0]['errorCategory']);
		$this->assertSame(4, $this->stored['files'][0]['retryCount']);

	}//end testALockMissAfterTheLastRetryIsAnError()

	/**
	 * Any other extraction failure is that file's error, and the unit goes on with the next file.
	 *
	 * @return void
	 */
	public function testAnExtractionFailureDoesNotStopTheUnit(): void {
		$this->store(count: 3, fileStatus: 'uploaded', status: 'extracting');
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			static function (int $fileId): array {
				if ($fileId === 2) {
					throw new RuntimeException('unreadable');
				}

				return ['entityCount' => 1];
			}
		);

		$this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('error', $this->stored['files'][1]['status']);
		$this->assertSame('unreadable', $this->stored['files'][1]['error']);
		$this->assertSame('extracted', $this->stored['files'][2]['status']);
		$this->assertSame('review', $this->stored['status']);

	}//end testAnExtractionFailureDoesNotStopTheUnit()

	/**
	 * A resumed background batch with its stored request goes on to anonymize after extraction.
	 *
	 * @return void
	 */
	public function testAResumedBatchWithItsRequestContinuesToAnonymize(): void {
		$this->store(
			count: 1,
			fileStatus: 'uploaded',
			status: 'extracting',
			extra: ['anonymizeRequest' => ['entities' => [['text' => 'Jan']], 'callOptions' => []]]
		);
		$this->extractionsSucceed();

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('anonymizing', $outcome['batchStatus']);

	}//end testAResumedBatchWithItsRequestContinuesToAnonymize()

	/**
	 * The anonymize phase applies the stored request to every file and completes the batch.
	 *
	 * @return void
	 */
	public function testTheAnonymizePhaseCompletesTheBatch(): void {
		$entities = [['text' => 'Jan', 'type' => 'person']];
		$this->store(
			count: 3,
			fileStatus: 'extracted',
			status: 'anonymizing',
			extra: ['anonymizeRequest' => ['entities' => $entities, 'callOptions' => ['outputFormat' => 'pdf']]]
		);
		$this->anon->expects($this->exactly(3))->method('anonymizeDocument')->with(
			$this->anything(),
			$entities,
			$this->anything()
		)->willReturnCallback(
			static fn (int $fileId): array => ['replacementCount' => 2, 'anonymizedFileId' => (1000 + $fileId)]
		);

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('completed', $outcome['batchStatus']);
		$this->assertSame(3, $this->countFiles(status: 'anonymized'));
		$this->assertSame(1003, $this->stored['files'][2]['anonymizedFileId']);
		$this->assertArrayHasKey('anonymizeMs', $this->stored['files'][0]);
		$this->assertIsInt($this->stored['completedAt']);
		$this->assertSame(1000, $this->stored['startedAt']);

	}//end testTheAnonymizePhaseCompletesTheBatch()

	/**
	 * An anonymizing batch without its stored request is an error, not a silent no-op.
	 *
	 * @return void
	 */
	public function testAnAnonymizingBatchWithoutARequestIsAnError(): void {
		$this->store(count: 1, fileStatus: 'extracted', status: 'anonymizing');
		$this->anon->expects($this->never())->method('anonymizeDocument');

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('error', $outcome['batchStatus']);
		$this->assertSame('error', $this->stored['status']);

	}//end testAnAnonymizingBatchWithoutARequestIsAnError()

	/**
	 * A batch waiting for review is left alone.
	 *
	 * @return void
	 */
	public function testABatchInReviewIsNotTouched(): void {
		$this->store(count: 2, fileStatus: 'extracted', status: 'review');

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame(['batchId' => 'b-1', 'batchStatus' => 'review', 'processed' => 0, 'throttled' => false], $outcome);
		$this->assertSame(0, $this->writes);

	}//end testABatchInReviewIsNotTouched()
	/**
	 * A lock miss in the anonymize phase queues the file again as extracted, and the batch stays anonymizing.
	 *
	 * @return void
	 */
	public function testALockMissInTheAnonymizePhaseIsRetried(): void {
		$this->store(
			count: 1,
			fileStatus: 'extracted',
			status: 'anonymizing',
			extra: ['anonymizeRequest' => ['entities' => [['text' => 'Jan']], 'callOptions' => []]]
		);
		$this->anonymizeFailure = new ConversionFailedException(
			message: 'held',
			attempts: [['reason' => 'could not acquire soffice:headless:convert lock: x']]
		);

		$outcome = $this->unit->run(batchId: 'b-1', maxFiles: 25, maxSeconds: 0);

		$this->assertSame('anonymizing', $outcome['batchStatus']);
		$this->assertSame('extracted', $this->stored['files'][0]['status']);
		$this->assertSame(1, $this->stored['files'][0]['retryCount']);

	}//end testALockMissInTheAnonymizePhaseIsRetried()
}//end class
