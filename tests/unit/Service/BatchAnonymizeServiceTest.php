<?php

/**
 * Unit tests for BatchAnonymizeService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchStateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchAnonymizeService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class BatchAnonymizeServiceTest extends TestCase
{

    /**
     * The BatchAnonymizeService under test
     *
     * @var BatchAnonymizeService
     */
    private BatchAnonymizeService $service;

    /**
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

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
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger       = $this->createMock(LoggerInterface::class);
        $this->mockAnonService  = $this->createMock(AnonymizationService::class);
        $this->mockStateService = $this->createMock(BatchStateService::class);

        $this->service = new BatchAnonymizeService(
            $this->mockLogger,
            $this->mockAnonService,
            $this->mockStateService
        );

    }//end setUp()


    /**
     * Test anonymizeBatch throws when batch not found
     *
     * @return void
     */
    public function testAnonymizeBatchThrowsWhenBatchNotFound(): void
    {
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
    public function testAnonymizeBatchSkipsErrorFiles(): void
    {
        $batch = [
            'batchId' => 'batch-1',
            'status'  => 'review',
            'files'   => [
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
    public function testAnonymizeBatchProcessesExtractedFiles(): void
    {
        $batch = [
            'batchId' => 'batch-2',
            'status'  => 'review',
            'files'   => [
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
     * Test anonymizeBatch records error when anonymization throws
     *
     * @return void
     */
    public function testAnonymizeBatchRecordsErrorOnException(): void
    {
        $batch = [
            'batchId' => 'batch-3',
            'status'  => 'review',
            'files'   => [
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
    public function testAnonymizeBatchReturnsTotalFilesCount(): void
    {
        $batch = [
            'batchId' => 'batch-4',
            'status'  => 'review',
            'files'   => [
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
    public function testAppendBasisSummaryFlagForwardedToEachFile(): void
    {
        $batch = [
            'batchId' => 'batch-5',
            'status'  => 'review',
            'files'   => [
                ['fileId' => 50, 'status' => 'extracted'],
                ['fileId' => 51, 'status' => 'extracted'],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockAnonService->expects($this->exactly(2))
            ->method('anonymizeDocument')
            ->with(
                $this->anything(),
                $this->anything(),
                true
            )
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 'f']);

        $result = $this->service->anonymizeBatch(
            batchId: 'batch-5',
            entities: [],
            appendBasisSummary: true
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
    public function testAppendBasisSummaryFlagDefaultsFalse(): void
    {
        $batch = [
            'batchId' => 'batch-6',
            'status'  => 'review',
            'files'   => [
                ['fileId' => 60, 'status' => 'extracted'],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->with(
                $this->anything(),
                $this->anything(),
                false
            )
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
    public function testPerFileSummaryWarningPropagated(): void
    {
        $batch = [
            'batchId' => 'batch-7',
            'status'  => 'review',
            'files'   => [
                ['fileId' => 70, 'status' => 'extracted'],
            ],
        ];

        $warning = ['code' => 'SUMMARY_APPEND_FAILED', 'message' => 'Service unavailable.'];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(
                [
                    'replacementCount' => 3,
                    'anonymizedFileId' => 'h',
                    'warning'          => $warning,
                ]
            );

        $result = $this->service->anonymizeBatch(
            batchId: 'batch-7',
            entities: [],
            appendBasisSummary: true
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
    public function testPreserveModeSummaryFieldsStoredPerFile(): void
    {
        $batch = [
            'batchId' => 'batch-8',
            'status'  => 'review',
            'files'   => [
                ['fileId' => 80, 'status' => 'extracted'],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(
                [
                    'replacementCount' => 1,
                    'anonymizedFileId' => 'i',
                    'summaryFileId'    => 'summary-80',
                    'summaryFilePath'  => '/DocuDesk/doc_grondslagen.pdf',
                ]
            );

        $result = $this->service->anonymizeBatch(
            batchId: 'batch-8',
            entities: [],
            appendBasisSummary: true
        );

        $this->assertSame(1, $result['processedFiles']);
        $this->assertSame('completed', $result['batchStatus']);

    }//end testPreserveModeSummaryFieldsStoredPerFile()


}//end class
