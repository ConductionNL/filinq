<?php

/**
 * Characterization tests for BatchCorrespondenceJob's per-recipient step
 *
 * Pins the arguments the correspondence service receives for each recipient,
 * the success and error entries the job records, the failure log line, and
 * that the owner stored at dispatch time stays on every progress update.
 *
 * @category  Tests
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
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Characterization of BatchCorrespondenceJob::generateFor().
 *
 * @covers \OCA\Filinq\BackgroundJob\BatchCorrespondenceJob
 * @uses   \OCA\Filinq\BackgroundJob\BatchTally
 */
class BatchCorrespondenceJobGenerateForTraceTest extends TestCase {

	private CorrespondenceServiceInterface&MockObject $correspondence;

	/**
	 * @var array<int, array{0: string, 1: string, 2: array<string, mixed>}> Every log line.
	 */
	private array $logs = [];

	/**
	 * @var array<int, array<string, mixed>> Every stored progress.
	 */
	private array $progress = [];

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->correspondence = $this->createMock(CorrespondenceServiceInterface::class);

	}//end setUp()

	/**
	 * Each recipient is generated with its own data reference and the job's options.
	 *
	 * @return void
	 */
	public function testSuccessEntriesAndServiceArguments(): void {
		$options = ['register' => 'reg', 'schema' => 'sch', 'userId' => 'alice', 'channel' => 'post'];
		$calls = [];
		$this->correspondence->method('generate')->willReturnCallback(
			function (string $templateId, array $dataRefs, array $options) use (&$calls): array {
				$calls[] = [$templateId, $dataRefs, $options];
				return ['ignored' => true];
			}
		);

		$results = $this->runJob(recipientIds: ['a', 7], options: $options);

		$this->assertSame(
			[
				['tpl', [['register' => 'reg', 'schema' => 'sch', 'id' => 'a']], $options],
				['tpl', [['register' => 'reg', 'schema' => 'sch', 'id' => 7]], $options],
			],
			$calls
		);
		$this->assertSame([['recipientId' => 'a', 'status' => 'success'], ['recipientId' => 7, 'status' => 'success']], $results);
		$this->assertSame([], array_filter($this->logs, fn (array $line): bool => $line[0] !== 'info'));
		foreach ($this->progress as $progress) {
			$this->assertSame('alice', $progress['ownerUserId']);
		}

	}//end testSuccessEntriesAndServiceArguments()

	/**
	 * Without a register or schema in the options both read empty.
	 *
	 * @return void
	 */
	public function testMissingRegisterAndSchemaReadEmpty(): void {
		$this->correspondence->expects($this->once())->method('generate')->with(
			$this->identicalTo('tpl'),
			$this->identicalTo([['register' => '', 'schema' => '', 'id' => 'x']]),
			$this->identicalTo([])
		)->willReturn([]);

		$this->runJob(recipientIds: ['x'], options: []);

	}//end testMissingRegisterAndSchemaReadEmpty()

	/**
	 * A failing recipient is logged with the job and recorded as an error; the next one still runs.
	 *
	 * @return void
	 */
	public function testFailureIsLoggedAndRecorded(): void {
		$this->correspondence->method('generate')->willReturnCallback(
			function (string $templateId, array $dataRefs): array {
				if ($dataRefs[0]['id'] === 'bad') {
					throw new Exception('address unknown');
				}

				return [];
			}
		);

		$results = $this->runJob(recipientIds: ['bad', 'ok'], options: []);

		$this->assertSame(
			[['recipientId' => 'bad', 'status' => 'error', 'error' => 'address unknown'], ['recipientId' => 'ok', 'status' => 'success']],
			$results
		);
		$this->assertSame(
			[['warning', 'Batch correspondence failed for recipient: address unknown', ['jobId' => 'j1', 'recipientId' => 'bad']]],
			array_values(array_filter($this->logs, fn (array $line): bool => $line[0] !== 'info'))
		);

	}//end testFailureIsLoggedAndRecorded()

	/**
	 * Run the job and return the results of its last progress update.
	 *
	 * @param array<int, mixed>    $recipientIds The recipients.
	 * @param array<string, mixed> $options      The job options.
	 *
	 * @return array<int, array<string, mixed>> The recorded results.
	 */
	private function runJob(array $recipientIds, array $options): array {
		$this->correspondence->method('storeJobStatus')->willReturnCallback(
			function (string $jobId, array $data): void {
				$this->progress[] = $data;
			}
		);

		$logger = $this->createMock(LoggerInterface::class);
		foreach (['info', 'warning', 'error'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string $message, array $context = []) use ($level): void {
					$this->logs[] = [$level, $message, $context];
				}
			);
		}

		$job = new BatchCorrespondenceJob($this->createMock(ITimeFactory::class), $this->correspondence, $logger);
		(new ReflectionMethod($job, 'run'))->invoke($job, ['jobId' => 'j1', 'templateId' => 'tpl', 'recipientIds' => $recipientIds, 'options' => $options]);

		return end($this->progress)['results'];

	}//end runJob()
}//end class
