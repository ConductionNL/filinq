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


}//end class
