<?php

/**
 * Unit tests for BatchAnonymizeService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BatchAnonymizeService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class BatchAnonymizeServiceTest extends TestCase {

	/**
	 * The BatchAnonymizeService under test
	 *
	 * @var BatchAnonymizeService
	 */
	private BatchAnonymizeService $service;

	/**
	 * Mocked AnonymizationService
	 *
	 * @var AnonymizationService|MockObject
	 */
	private AnonymizationService|MockObject $mockAnonService;

	/**
	 * Mocked BatchStateService
	 *
	 * @var BatchStateService|MockObject
	 */
	private BatchStateService|MockObject $mockStateService;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockAnonService = $this->createMock(AnonymizationService::class);
		$this->mockStateService = $this->createMock(BatchStateService::class);

		// Placement is covered by BatchAnonymizeServiceOutputLayoutTest; here
		// the mover leaves every output where OpenRegister wrote it.
		$mover = $this->createMock(OutputLayoutMover::class);
		$mover->method('relocate')->willReturnCallback(
			static fn (string $userId, int $fileId, string $legacyPath): array => ['path' => $legacyPath, 'warning' => null]
		);

		$this->service = new BatchAnonymizeService(
			$this->mockAnonService,
			$this->mockStateService,
			$mover
		);

	}//end setUp()

	/**
	 * Test anonymizeBatch throws when batch not found
	 *
	 * @return void
	 */
	public function testAnonymizeBatchThrowsWhenBatchNotFound(): void {
		$this->mockStateService->method('getBatch')->willReturn(null);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Batch not found or expired');

		$this->service->anonymizeBatch(batchId: 'missing-id', entities: []);

	}//end testAnonymizeBatchThrowsWhenBatchNotFound()

	/**
	 * Test anonymizeBatch skips files with error status
	 *
	 * @return void
	 */
	public function testAnonymizeBatchSkipsErrorFiles(): void {
		$batch = [
			'batchId' => 'batch-1',
			'status' => 'review',
			'files' => [
				['fileId' => 10, 'status' => 'error', 'error' => 'Upload failed'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->expects($this->never())->method('anonymizeDocument');

		$result = $this->service->anonymizeBatch(batchId: 'batch-1', entities: []);

		$this->assertSame('completed', $result['batchStatus']);
		$this->assertSame(0, $result['processedFiles']);
		$this->assertCount(1, $result['skippedFiles']);

	}//end testAnonymizeBatchSkipsErrorFiles()

	/**
	 * Test anonymizeBatch processes extracted files successfully
	 *
	 * @return void
	 */
	public function testAnonymizeBatchProcessesExtractedFiles(): void {
		$batch = [
			'batchId' => 'batch-2',
			'status' => 'review',
			'files' => [
				['fileId' => 20, 'status' => 'extracted'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->expects($this->once())
			->method('anonymizeDocument')
			->with(20, ['PERSON'])
			->willReturn(['replacementCount' => 5, 'anonymizedFileId' => 'anon-file-1']);

		$result = $this->service->anonymizeBatch(batchId: 'batch-2', entities: ['PERSON']);

		$this->assertSame('completed', $result['batchStatus']);
		$this->assertSame(1, $result['processedFiles']);
		$this->assertEmpty($result['skippedFiles']);

	}//end testAnonymizeBatchProcessesExtractedFiles()

	/**
	 * Each batch entry names the backend that looked and what it found.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-3
	 */
	public function testEachEntryCarriesTheDetectionOutcome(): void {
		$detection = ['ran' => true, 'backend' => 'presidio', 'entitiesRedacted' => 0, 'outcome' => 'nothing_found'];
		$this->mockStateService->method('getBatch')->willReturn(
			['batchId' => 'batch-d', 'status' => 'review', 'files' => [['fileId' => 21, 'status' => 'extracted']]]
		);
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['replacementCount' => 0, 'anonymizedFileId' => 'anon-21', 'detection' => $detection]);

		$saved = null;
		$this->mockStateService->method('updateBatch')->willReturnCallback(
			function (string $id, array $batch) use (&$saved): void {
				$saved = $batch;
			}
		);

		$this->service->anonymizeBatch(batchId: 'batch-d', entities: []);

		$this->assertSame($detection, $saved['files'][0]['detection']);

	}//end testEachEntryCarriesTheDetectionOutcome()

	/**
	 * Test anonymizeBatch records error when anonymization throws
	 *
	 * @return void
	 */
	public function testAnonymizeBatchRecordsErrorOnException(): void {
		$batch = [
			'batchId' => 'batch-3',
			'status' => 'review',
			'files' => [
				['fileId' => 30, 'status' => 'extracted'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('anonymizeDocument')
			->willThrowException(new Exception('Presidio unavailable'));

		$result = $this->service->anonymizeBatch(batchId: 'batch-3', entities: []);

		$this->assertSame(0, $result['processedFiles']);
		$this->assertCount(1, $result['skippedFiles']);
		$this->assertSame('Presidio unavailable', $result['skippedFiles'][0]['reason']);

	}//end testAnonymizeBatchRecordsErrorOnException()

	/**
	 * Test anonymizeBatch returns correct totalFiles count
	 *
	 * @return void
	 */
	public function testAnonymizeBatchReturnsTotalFilesCount(): void {
		$batch = [
			'batchId' => 'batch-4',
			'status' => 'review',
			'files' => [
				['fileId' => 1, 'status' => 'extracted'],
				['fileId' => 2, 'status' => 'extracted'],
				['fileId' => 3, 'status' => 'error', 'error' => 'failed'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'x']);

		$result = $this->service->anonymizeBatch(batchId: 'batch-4', entities: []);

		$this->assertSame(3, $result['totalFiles']);
		$this->assertSame(2, $result['processedFiles']);

	}//end testAnonymizeBatchReturnsTotalFilesCount()

	/**
	 * Flag true is forwarded to anonymizeDocument for each extracted file.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
	 */
	public function testAppendBasisSummaryFlagForwardedToEachFile(): void {
		$batch = [
			'batchId' => 'batch-5',
			'status' => 'review',
			'files' => [
				['fileId' => 50, 'status' => 'extracted'],
				['fileId' => 51, 'status' => 'extracted'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->expects($this->never())->method('anonymizeDocument');
		$this->mockAnonService->expects($this->exactly(2))
			->method('anonymizeDocumentWithBasisSummary')
			->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'f']);

		$result = $this->service->anonymizeBatchWithBasisSummary(
			batchId: 'batch-5',
			entities: []
		);

		$this->assertSame(2, $result['processedFiles']);

	}//end testAppendBasisSummaryFlagForwardedToEachFile()

	/**
	 * Flag false (default) means anonymizeDocument is called without the flag.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
	 */
	public function testAppendBasisSummaryFlagDefaultsFalse(): void {
		$batch = [
			'batchId' => 'batch-6',
			'status' => 'review',
			'files' => [
				['fileId' => 60, 'status' => 'extracted'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->expects($this->never())->method('anonymizeDocumentWithBasisSummary');
		$this->mockAnonService->expects($this->once())
			->method('anonymizeDocument')
			->willReturn(['replacementCount' => 0, 'anonymizedFileId' => 'g']);

		$this->service->anonymizeBatch(batchId: 'batch-6', entities: []);

	}//end testAppendBasisSummaryFlagDefaultsFalse()

	/**
	 * Per-file warnings from summary failure are propagated into batch file entries.
	 *
	 * The batch still completes (HTTP 200 shape) even when per-file warnings exist.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
	 */
	public function testPerFileSummaryWarningPropagated(): void {
		$batch = [
			'batchId' => 'batch-7',
			'status' => 'review',
			'files' => [
				['fileId' => 70, 'status' => 'extracted'],
			],
		];

		$warning = ['code' => 'SUMMARY_APPEND_FAILED', 'message' => 'Service unavailable.'];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('anonymizeDocumentWithBasisSummary')
			->willReturn(
				[
					'replacementCount' => 3,
					'anonymizedFileId' => 'h',
					'warning' => $warning,
				]
			);

		$result = $this->service->anonymizeBatchWithBasisSummary(
			batchId: 'batch-7',
			entities: []
		);

		$this->assertSame('completed', $result['batchStatus']);
		$this->assertSame(1, $result['processedFiles']);
		$this->assertEmpty($result['skippedFiles']);

	}//end testPerFileSummaryWarningPropagated()

	/**
	 * Preserve-mode summary fields (summaryFileId, summaryFilePath) are stored per file.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
	 */
	public function testPreserveModeSummaryFieldsStoredPerFile(): void {
		$batch = [
			'batchId' => 'batch-8',
			'status' => 'review',
			'files' => [
				['fileId' => 80, 'status' => 'extracted'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('anonymizeDocumentWithBasisSummary')
			->willReturn(
				[
					'replacementCount' => 1,
					'anonymizedFileId' => 'i',
					'summaryFileId' => 'summary-80',
					'summaryFilePath' => '/DocuDesk/doc_grondslagen.pdf',
				]
			);

		$result = $this->service->anonymizeBatchWithBasisSummary(
			batchId: 'batch-8',
			entities: []
		);

		$this->assertSame(1, $result['processedFiles']);
		$this->assertSame('completed', $result['batchStatus']);

	}//end testPreserveModeSummaryFieldsStoredPerFile()

	/**
	 * A batch with every kind of file: both stored states, the summary and
	 * the per-file calls are exactly these.
	 *
	 * @return void
	 */
	public function testAMixedBatchStoresAndReportsEveryFileExactly(): void {
		$batch = [
			'batchId' => 'batch-9',
			'userId' => 'anna',
			'status' => 'review',
			'files' => [
				['fileId' => 1, 'status' => 'error'],
				['fileId' => 2, 'status' => 'uploaded'],
				['fileId' => 3, 'status' => 'extracted'],
				['fileId' => 4, 'status' => 'extracted'],
				['fileId' => 5, 'status' => 'extracted'],
			],
		];
		$this->mockStateService->method('getBatch')->willReturn($batch);
		$stored = [];
		$this->mockStateService->method('updateBatch')->willReturnCallback(
			function (string $batchId, array $state) use (&$stored): void {
				$stored[] = [$batchId, $state];
			}
		);
		$this->mockAnonService->method('checkUnredactedProhibitions')->willReturn([]);
		$calls = [];
		$this->mockAnonService->method('anonymizeDocumentWithBasisSummary')->willReturnCallback(
			function (int $fileId, array $entities, string $outputFormat, array $unredactedEntities, array $overrides, string $userId, string $scope, ?string $dossierKey) use (&$calls): array {
				$calls[] = [$fileId, $entities, $outputFormat, $unredactedEntities, $overrides, $userId, $scope, $dossierKey];
				if ($fileId === 4) {
					throw new ConversionFailedException(message: 'no backend', attempts: [['backend' => 'office']]);
				}

				if ($fileId === 5) {
					throw new Exception('disk full');
				}

				return ['replacementCount' => 2, 'anonymizedFileId' => 33, 'anonymizedFilePath' => '/a/b_anonymized.pdf'];
			}
		);

		$result = $this->service->anonymizeBatchWithBasisSummary(
			batchId: 'batch-9',
			entities: [['text' => 'Jan']],
			unredactedEntities: [['text' => 'Gemeente']],
			outputFormat: 'preserve',
			scope: 'document'
		);

		$this->assertSame(
			[
				'batchId' => 'batch-9',
				'batchStatus' => 'completed',
				'processedFiles' => 1,
				'skippedFiles' => [
					['fileId' => 1, 'reason' => 'Previous error'],
					['fileId' => 4, 'reason' => 'no backend'],
					['fileId' => 5, 'reason' => 'disk full'],
				],
				'prohibitionSkippedFiles' => 0,
				'totalFiles' => 5,
			],
			$result
		);
		$this->assertCount(3, $calls);
		$this->assertSame(
			[3, [['text' => 'Jan']], 'preserve', [['text' => 'Gemeente']], [], '', 'document', null],
			$calls[0]
		);
		$this->assertSame('anonymizing', $stored[0][1]['status']);
		$this->assertSame($batch['files'], $stored[0][1]['files']);
		$this->assertSame('batch-9', $stored[1][0]);
		$this->assertSame(
			[
				['fileId' => 1, 'status' => 'error'],
				['fileId' => 2, 'status' => 'uploaded'],
				['fileId' => 3, 'status' => 'anonymized', 'replacementCount' => 2, 'anonymizedFileId' => 33, 'anonymizedFilePath' => '/a/b_anonymized.pdf'],
				['fileId' => 4, 'status' => 'error', 'error' => 'no backend', 'conversionAttempts' => [['backend' => 'office']]],
				['fileId' => 5, 'status' => 'error', 'error' => 'disk full'],
			],
			$stored[1][1]['files']
		);
		$this->assertSame('completed', $stored[1][1]['status']);

	}//end testAMixedBatchStoresAndReportsEveryFileExactly()

	/**
	 * Unredacted entities the prohibition gate refuses skip every extracted
	 * file with a 422, and nothing is anonymised.
	 *
	 * @return void
	 */
	public function testAProhibitionViolationSkipsEveryExtractedFile(): void {
		$this->mockStateService->method('getBatch')->willReturn(
			[
				'batchId' => 'batch-10',
				'files' => [
					['fileId' => 7, 'status' => 'extracted'],
					['fileId' => 8, 'status' => 'error', 'error' => 'Upload failed'],
					['fileId' => 9, 'status' => 'extracted'],
				],
			]
		);
		$stored = [];
		$this->mockStateService->method('updateBatch')->willReturnCallback(
			function (string $batchId, array $state) use (&$stored): void {
				$stored[] = $state;
			}
		);
		$violation = [['entityType' => 'BSN', 'reason' => 'never published']];
		$this->mockAnonService->expects($this->exactly(2))->method('checkUnredactedProhibitions')
			->with([['text' => '123456782']])
			->willReturn($violation);
		$this->mockAnonService->expects($this->never())->method('anonymizeDocument');

		$result = $this->service->anonymizeBatch(batchId: 'batch-10', entities: [], unredactedEntities: [['text' => '123456782']]);

		$this->assertSame(2, $result['prohibitionSkippedFiles']);
		$this->assertSame(0, $result['processedFiles']);
		$this->assertSame(
			[
				['fileId' => 7, 'reason' => 'Prohibition violation on unredacted entities', 'httpStatus' => 422],
				['fileId' => 8, 'reason' => 'Upload failed'],
				['fileId' => 9, 'reason' => 'Prohibition violation on unredacted entities', 'httpStatus' => 422],
			],
			$result['skippedFiles']
		);
		$this->assertSame(['fileId' => 7, 'status' => 'prohibitionViolation', 'prohibitedEntries' => $violation], $stored[1]['files'][0]);

	}//end testAProhibitionViolationSkipsEveryExtractedFile()
}//end class
