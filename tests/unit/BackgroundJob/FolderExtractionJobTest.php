<?php

/**
 * Unit tests for FolderExtractionJob: the fallback extraction of an in-request folder batch.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-background-extraction-via-queuedjob
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\FolderExtractionJob;
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
use ReflectionMethod;

/**
 * The job extracts every queued file through the work unit and leaves the batch in review.
 *
 * @covers \OCA\Filinq\BackgroundJob\FolderExtractionJob
 * @uses   \OCA\Filinq\Service\BatchWorkUnit
 * @uses   \OCA\Filinq\Service\BatchAnonymizeService
 * @uses   \OCA\Filinq\Service\BatchBackgroundPath
 * @uses   \OCA\Filinq\Service\BatchLifecycleService
 * @uses   \OCA\Filinq\Service\BatchRetryPolicy
 */
class FolderExtractionJobTest extends TestCase {

	/**
	 * The stored batch, or null for none.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $stored = null;

	/**
	 * Every batch status written, in order.
	 *
	 * @var array<int, string>
	 */
	private array $statuses = [];

	/**
	 * Single-document extraction double.
	 *
	 * @var AnonymizationService&MockObject
	 */
	private AnonymizationService&MockObject $anon;

	/**
	 * Logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The job under test.
	 *
	 * @var FolderExtractionJob
	 */
	private FolderExtractionJob $job;

	/**
	 * Build the job over a real work unit and a stateful store double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$state = $this->createMock(BatchStateService::class);
		$state->method('getBatch')->willReturnCallback(fn (): ?array => $this->stored);
		$state->method('updateBatch')->willReturnCallback(
			function (string $batchId, array $batch): void {
				$this->stored = $batch;
				$this->statuses[] = (string)$batch['status'];
			}
		);
		$this->anon = $this->createMock(AnonymizationService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getTime')->willReturn(1000);
		$unit = new BatchWorkUnit(
			stateService: $state,
			anonService: $this->anon,
			anonymizer: new BatchAnonymizeService(
				anonService: $this->anon,
				stateService: $state,
				layoutMover: $this->createMock(OutputLayoutMover::class)
			),
			background: new BatchBackgroundPath(
				stateService: $state,
				routing: $this->createMock(BatchRouting::class),
				linkReader: $this->createMock(AnonymizationLinkReader::class)
			),
			clock: $time,
			logger: $this->logger
		);
		$this->job = new FolderExtractionJob(time: $time, stateService: $state, workUnit: $unit, logger: $this->logger);

	}//end setUp()

	/**
	 * Run the job's protected run().
	 *
	 * @param mixed $argument The job argument.
	 *
	 * @return void
	 */
	private function runJob(mixed $argument): void {
		(new ReflectionMethod($this->job, 'run'))->invoke($this->job, $argument);

	}//end runJob()

	/**
	 * Store a batch of uploaded files.
	 *
	 * @param int                  $count Number of files.
	 * @param array<string, mixed> $extra Extra batch fields.
	 *
	 * @return void
	 */
	private function store(int $count, array $extra=[]): void {
		$files = [];
		for ($i = 1; $i <= $count; $i++) {
			$files[] = ['fileId' => $i, 'fileName' => 'f' . $i . '.pdf', 'status' => 'uploaded'];
		}

		$this->stored = array_merge(['batchId' => 'b-1', 'userId' => 'u', 'status' => 'uploading', 'files' => $files], $extra);

	}//end store()

	/**
	 * Three files are extracted one after another and the batch ends in review, not anonymized.
	 *
	 * @return void
	 */
	public function testEveryFileIsExtractedAndTheBatchGoesToReview(): void {
		$this->store(count: 3);
		$order = [];
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			static function (int $fileId) use (&$order): array {
				$order[] = $fileId;
				return ['entityCount' => ($fileId * 2)];
			}
		);
		$this->anon->expects($this->never())->method('anonymizeDocument');

		$this->runJob(argument: ['batchId' => 'b-1']);

		$this->assertSame([1, 2, 3], $order);
		$this->assertSame('review', $this->stored['status']);
		$this->assertSame('extracting', $this->statuses[0]);
		$this->assertSame(['extracted', 'extracted', 'extracted'], array_column($this->stored['files'], 'status'));
		$this->assertSame(6, $this->stored['files'][2]['entityCount']);

	}//end testEveryFileIsExtractedAndTheBatchGoesToReview()

	/**
	 * More files than one unit holds are still all extracted, unit after unit.
	 *
	 * @return void
	 */
	public function testABatchLargerThanOneUnitIsExtractedCompletely(): void {
		$this->store(count: 60);
		$this->anon->method('extractAndDetectEntities')->willReturn(['entityCount' => 1]);

		$this->runJob(argument: ['batchId' => 'b-1']);

		$this->assertSame('review', $this->stored['status']);
		$this->assertCount(60, array_filter($this->stored['files'], static fn (array $f): bool => $f['status'] === 'extracted'));

	}//end testABatchLargerThanOneUnitIsExtractedCompletely()

	/**
	 * One failing file does not stop the others.
	 *
	 * @return void
	 */
	public function testOneFailingFileDoesNotAbortTheBatch(): void {
		$this->store(count: 3);
		$this->anon->method('extractAndDetectEntities')->willReturnCallback(
			static function (int $fileId): array {
				if ($fileId === 2) {
					throw new Exception('unreadable');
				}

				return ['entityCount' => 1];
			}
		);

		$this->runJob(argument: ['batchId' => 'b-1']);

		$this->assertSame(['extracted', 'error', 'extracted'], array_column($this->stored['files'], 'status'));
		$this->assertSame('unreadable', $this->stored['files'][1]['error']);
		$this->assertSame('review', $this->stored['status']);

	}//end testOneFailingFileDoesNotAbortTheBatch()

	/**
	 * A background batch belongs to the cron coordinator: the job leaves it alone.
	 *
	 * @return void
	 */
	public function testABackgroundBatchIsLeftToTheCoordinator(): void {
		$this->store(count: 2, extra: ['background' => true, 'status' => 'extracting']);
		$this->anon->expects($this->never())->method('extractAndDetectEntities');

		$this->runJob(argument: ['batchId' => 'b-1']);

		$this->assertSame([], $this->statuses);

	}//end testABackgroundBatchIsLeftToTheCoordinator()

	/**
	 * A batch already past extraction is not extracted again.
	 *
	 * @return void
	 */
	public function testABatchInReviewIsNotExtractedAgain(): void {
		$this->store(count: 2, extra: ['status' => 'review']);
		$this->anon->expects($this->never())->method('extractAndDetectEntities');

		$this->runJob(argument: ['batchId' => 'b-1']);

		$this->assertSame([], $this->statuses);

	}//end testABatchInReviewIsNotExtractedAgain()

	/**
	 * A missing batch id or an unknown batch is logged as an error.
	 *
	 * @return void
	 */
	public function testAMissingOrUnknownBatchIsLogged(): void {
		$this->logger->expects($this->exactly(2))->method('error')->with(
			$this->logicalOr('FolderExtractionJob: missing batchId', 'FolderExtractionJob: batch not found')
		);

		$this->runJob(argument: []);
		$this->runJob(argument: ['batchId' => 'gone']);

	}//end testAMissingOrUnknownBatchIsLogged()
}//end class
