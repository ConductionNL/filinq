<?php

/**
 * Batch jobs: every progress write and log line, in order.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\BatchCorrespondenceJob;
use OCA\Filinq\BackgroundJob\BatchDocumentJob;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Locks the progress both batch jobs report, item by item.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchJobProgressTest extends TestCase {

	/**
	 * Every status write, generate call and log line, in order.
	 *
	 * @var array<int, mixed>
	 */
	private array $calls = [];

	/**
	 * A logger that records into the call log.
	 *
	 * @return LoggerInterface The logger.
	 */
	private function logger(): LoggerInterface {
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		foreach (['error', 'warning', 'info'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string $message, array $context = []) use ($level): void {
					$this->calls[] = ['log ' . $level => $message, 'context' => $context];
				}
			);
		}

		return $logger;
	}//end logger()

	/**
	 * Run a job's protected run().
	 *
	 * @param QueuedJob            $job      The job.
	 * @param array<string, mixed> $argument The job argument.
	 *
	 * @return void
	 */
	private function runJob(QueuedJob $job, array $argument): void {
		(new ReflectionMethod(objectOrMethod: $job, method: 'run'))->invoke($job, $argument);
	}//end runJob()

	/**
	 * A document job whose generation fails for object ids starting with `bad`.
	 *
	 * @return BatchDocumentJob The job.
	 */
	private function documentJob(): BatchDocumentJob {
		$service = $this->createMock(originalClassName: DocumentServiceInterface::class);
		$service->method('generateDocument')->willReturnCallback(
			function (string $templateId, array $dataRefs, array $options): array {
				$this->calls[] = ['generate' => $templateId, 'dataRefs' => $dataRefs, 'options' => $options];
				$id = (string)$dataRefs[0]['id'];
				if (str_starts_with($id, 'bad') === true) {
					throw new Exception(message: 'no data for ' . $id);
				}

				if ($id === 'bare') {
					return ['warnings' => ['w1']];
				}

				return ['warnings' => [], 'output' => ['fileId' => 7, 'path' => '/p/' . $id . '.pdf']];
			}
		);
		$service->method('updateJobStatus')->willReturnCallback(
			function (string $jobId, array $status): void {
				$this->calls[] = ['status ' . $jobId => $status];
			}
		);

		return new BatchDocumentJob(
			time: $this->createMock(originalClassName: ITimeFactory::class),
			documentSvc: $service,
			logger: $this->logger()
		);
	}//end documentJob()

	/**
	 * A correspondence job whose generation fails for recipient ids starting with `bad`.
	 *
	 * @return BatchCorrespondenceJob The job.
	 */
	private function correspondenceJob(): BatchCorrespondenceJob {
		$service = $this->createMock(originalClassName: CorrespondenceServiceInterface::class);
		$service->method('generate')->willReturnCallback(
			function (string $templateId, array $dataRefs, array $options): array {
				$this->calls[] = ['generate' => $templateId, 'dataRefs' => $dataRefs, 'options' => $options];
				$id = (string)$dataRefs[0]['id'];
				if (str_starts_with($id, 'bad') === true) {
					throw new Exception(message: 'no address for ' . $id);
				}

				return ['ignored' => true];
			}
		);
		$service->method('storeJobStatus')->willReturnCallback(
			function (string $jobId, array $data): void {
				$this->calls[] = ['status ' . $jobId => $data];
			}
		);

		return new BatchCorrespondenceJob(
			time: $this->createMock(originalClassName: ITimeFactory::class),
			corrSvc: $service,
			logger: $this->logger()
		);
	}//end correspondenceJob()

	/**
	 * Documents: success, failure and a result without output, then the final status.
	 *
	 * @return void
	 */
	public function testDocumentJobGolden(): void {
		$options = ['register' => 'brp', 'schema' => 'persoon', 'userId' => 'anna'];
		$this->runJob(
			job: $this->documentJob(),
			argument: ['jobId' => 'j-1', 'templateId' => 't-1', 'objectIds' => ['o1', 'bad2', 'bare'], 'options' => $options]
		);

		$refs = static fn (string $id): array => [['register' => 'brp', 'schema' => 'persoon', 'id' => $id]];
		$ok = ['objectId' => 'o1', 'status' => 'success', 'warnings' => [], 'fileId' => 7, 'path' => '/p/o1.pdf'];
		$bad = ['objectId' => 'bad2', 'status' => 'error', 'error' => 'no data for bad2'];
		$bare = ['objectId' => 'bare', 'status' => 'success', 'warnings' => ['w1'], 'fileId' => null, 'path' => null];
		$status = static fn (string $state, int $done, int $errors, array $results): array => [
			'status j-1' => [
				'jobId'     => 'j-1',
				'status'    => $state,
				'total'     => 3,
				'completed' => $done,
				'errors'    => $errors,
				'results'   => $results,
				'options'   => $options,
			],
		];

		$this->assertSame(
			expected: [
				$status('processing', 0, 0, []),
				['generate' => 't-1', 'dataRefs' => $refs('o1'), 'options' => $options],
				$status('processing', 1, 0, [$ok]),
				['generate' => 't-1', 'dataRefs' => $refs('bad2'), 'options' => $options],
				[
					'log warning' => 'BatchDocumentJob: failed to generate document for object',
					'context'     => ['objectId' => 'bad2', 'templateId' => 't-1', 'error' => 'no data for bad2'],
				],
				$status('processing', 1, 1, [$ok, $bad]),
				['generate' => 't-1', 'dataRefs' => $refs('bare'), 'options' => $options],
				$status('processing', 2, 1, [$ok, $bad, $bare]),
				$status('completed_with_errors', 2, 1, [$ok, $bad, $bare]),
			],
			actual: $this->calls
		);
	}//end testDocumentJobGolden()

	/**
	 * Documents: no objects and no register options still report a completed job.
	 *
	 * @return void
	 */
	public function testDocumentJobWithoutObjects(): void {
		$this->runJob(job: $this->documentJob(), argument: ['jobId' => 'j-2', 'templateId' => 't-1']);

		$status = static fn (string $state): array => [
			'status j-2' => ['jobId' => 'j-2', 'status' => $state, 'total' => 0, 'completed' => 0, 'errors' => 0, 'results' => [], 'options' => []],
		];
		$this->assertSame(expected: [$status('processing'), $status('completed')], actual: $this->calls);
	}//end testDocumentJobWithoutObjects()

	/**
	 * Documents: a missing template id is logged and nothing is written.
	 *
	 * @return void
	 */
	public function testDocumentJobWithoutTemplate(): void {
		$this->runJob(job: $this->documentJob(), argument: ['jobId' => 'j-3', 'objectIds' => ['o1']]);

		$this->assertSame(
			expected: [
				['log error' => 'BatchDocumentJob: missing required arguments', 'context' => ['argument' => ['jobId' => 'j-3', 'objectIds' => ['o1']]]],
			],
			actual: $this->calls
		);
	}//end testDocumentJobWithoutTemplate()

	/**
	 * Letters: success and failure, the owner kept on every write, then the summary line.
	 *
	 * @return void
	 */
	public function testCorrespondenceJobGolden(): void {
		$options = ['register' => 'brp', 'schema' => 'persoon', 'userId' => 'anna'];
		$this->runJob(
			job: $this->correspondenceJob(),
			argument: ['jobId' => 'c-1', 'templateId' => 't-2', 'recipientIds' => ['r1', 'bad2', 'r3'], 'options' => $options]
		);

		$refs = static fn (string $id): array => [['register' => 'brp', 'schema' => 'persoon', 'id' => $id]];
		$ok = static fn (string $id): array => ['recipientId' => $id, 'status' => 'success'];
		$bad = ['recipientId' => 'bad2', 'status' => 'error', 'error' => 'no address for bad2'];
		$status = static fn (string $state, int $done, int $errors, array $results): array => [
			'status c-1' => [
				'status'      => $state,
				'total'       => 3,
				'completed'   => $done,
				'errors'      => $errors,
				'results'     => $results,
				'ownerUserId' => 'anna',
			],
		];

		$this->assertSame(
			expected: [
				$status('processing', 0, 0, []),
				['generate' => 't-2', 'dataRefs' => $refs('r1'), 'options' => $options],
				$status('processing', 1, 0, [$ok('r1')]),
				['generate' => 't-2', 'dataRefs' => $refs('bad2'), 'options' => $options],
				['log warning' => 'Batch correspondence failed for recipient: no address for bad2', 'context' => ['jobId' => 'c-1', 'recipientId' => 'bad2']],
				$status('processing', 1, 1, [$ok('r1'), $bad]),
				['generate' => 't-2', 'dataRefs' => $refs('r3'), 'options' => $options],
				$status('processing', 2, 1, [$ok('r1'), $bad, $ok('r3')]),
				$status('completed', 2, 1, [$ok('r1'), $bad, $ok('r3')]),
				['log info' => 'BatchCorrespondenceJob completed: 2/3 successful, 1 errors', 'context' => ['jobId' => 'c-1']],
			],
			actual: $this->calls
		);
	}//end testCorrespondenceJobGolden()

	/**
	 * Letters: no recipients and no owner still report a completed job.
	 *
	 * @return void
	 */
	public function testCorrespondenceJobWithoutRecipients(): void {
		$this->runJob(job: $this->correspondenceJob(), argument: ['jobId' => 'c-2', 'templateId' => 't-2']);

		$status = static fn (string $state): array => [
			'status c-2' => ['status' => $state, 'total' => 0, 'completed' => 0, 'errors' => 0, 'results' => [], 'ownerUserId' => ''],
		];
		$this->assertSame(
			expected: [
				$status('processing'),
				$status('completed'),
				['log info' => 'BatchCorrespondenceJob completed: 0/0 successful, 0 errors', 'context' => ['jobId' => 'c-2']],
			],
			actual: $this->calls
		);
	}//end testCorrespondenceJobWithoutRecipients()

	/**
	 * Letters: a missing job id is logged and nothing is written.
	 *
	 * @return void
	 */
	public function testCorrespondenceJobWithoutJobId(): void {
		$this->runJob(job: $this->correspondenceJob(), argument: ['templateId' => 't-2']);

		$this->assertSame(
			expected: [['log error' => 'BatchCorrespondenceJob: missing required arguments', 'context' => ['argument' => ['templateId' => 't-2']]]],
			actual: $this->calls
		);
	}//end testCorrespondenceJobWithoutJobId()
}//end class
