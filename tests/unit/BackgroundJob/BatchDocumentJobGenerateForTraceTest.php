<?php

/**
 * Characterization tests for BatchDocumentJob's per-object generation
 *
 * Pins the data reference and options handed to the document service and
 * the result entry recorded for a success and for a failure.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\BatchDocumentJob;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Characterization of the per-object generation step.
 *
 * @covers \OCA\Filinq\BackgroundJob\BatchDocumentJob
 * @uses   \OCA\Filinq\BackgroundJob\BatchTally
 */
class BatchDocumentJobGenerateForTraceTest extends TestCase {

	private DocumentServiceInterface&MockObject $documents;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->documents = $this->createMock(DocumentServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Each object gets one data reference built from the options; results carry the output.
	 *
	 * @return void
	 */
	public function testSuccessEntriesAndServiceArguments(): void {
		$options = ['register' => 'reg', 'schema' => 'sch', 'format' => 'pdf'];
		$calls = [];
		$this->documents->method('generateDocument')->willReturnCallback(
			function (string $templateId, array $dataRefs, array $options) use (&$calls): array {
				$calls[] = [$templateId, $dataRefs, $options];
				if ($dataRefs[0]['id'] === 'a') {
					return ['warnings' => ['w1'], 'output' => ['fileId' => 11, 'path' => '/f/a.pdf']];
				}

				return ['warnings' => [], 'output' => []];
			}
		);
		$this->logger->expects($this->never())->method('warning');

		$results = $this->runJob(objectIds: ['a', 7], options: $options);

		$this->assertSame(
			[
				['tpl', [['register' => 'reg', 'schema' => 'sch', 'id' => 'a']], $options],
				['tpl', [['register' => 'reg', 'schema' => 'sch', 'id' => 7]], $options],
			],
			$calls
		);
		$this->assertSame(
			[
				['objectId' => 'a', 'status' => 'success', 'warnings' => ['w1'], 'fileId' => 11, 'path' => '/f/a.pdf'],
				['objectId' => 7, 'status' => 'success', 'warnings' => [], 'fileId' => null, 'path' => null],
			],
			$results
		);

	}//end testSuccessEntriesAndServiceArguments()

	/**
	 * Without register or schema options the reference carries empty strings.
	 *
	 * @return void
	 */
	public function testMissingRegisterAndSchemaReadEmpty(): void {
		$this->documents->expects($this->once())->method('generateDocument')
			->with('tpl', [['register' => '', 'schema' => '', 'id' => 'x']], [])
			->willReturn(['warnings' => [], 'output' => []]);

		$this->runJob(objectIds: ['x'], options: []);

	}//end testMissingRegisterAndSchemaReadEmpty()

	/**
	 * A failure is logged and recorded as an error entry; later objects still run.
	 *
	 * @return void
	 */
	public function testFailureIsLoggedAndRecorded(): void {
		$this->documents->method('generateDocument')->willReturnCallback(
			function (string $templateId, array $dataRefs): array {
				if ($dataRefs[0]['id'] === 'bad') {
					throw new Exception('template broken');
				}

				return ['warnings' => [], 'output' => ['fileId' => 2]];
			}
		);
		$this->logger->expects($this->once())->method('warning')->with(
			'BatchDocumentJob: failed to generate document for object',
			['objectId' => 'bad', 'templateId' => 'tpl', 'error' => 'template broken']
		);

		$results = $this->runJob(objectIds: ['bad', 'ok'], options: []);

		$this->assertSame(
			[
				['objectId' => 'bad', 'status' => 'error', 'error' => 'template broken'],
				['objectId' => 'ok', 'status' => 'success', 'warnings' => [], 'fileId' => 2, 'path' => null],
			],
			$results
		);

	}//end testFailureIsLoggedAndRecorded()

	/**
	 * Run the job and return the results of the last stored progress.
	 *
	 * @param array<int, mixed> $objectIds The object ids.
	 * @param array<string, mixed> $options The job options.
	 *
	 * @return array<int, array<string, mixed>> The recorded results.
	 */
	private function runJob(array $objectIds, array $options): array {
		$last = [];
		$this->documents->method('updateJobStatus')->willReturnCallback(
			function (string $jobId, array $status) use (&$last): void {
				$last = $status;
			}
		);

		$time = $this->createMock(ITimeFactory::class);
		$job = new BatchDocumentJob($time, $this->documents, $this->logger);
		(new ReflectionMethod($job, 'run'))->invoke($job, ['jobId' => 'j1', 'templateId' => 'tpl', 'objectIds' => $objectIds, 'options' => $options]);

		return $last['results'];
	}//end runJob()
}//end class
