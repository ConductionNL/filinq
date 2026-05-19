<?php

/**
 * Unit tests for FolderExtractionJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\BackgroundJob;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchStateService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FolderExtractionJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class FolderExtractionJobTest extends TestCase
{

    /**
     * The FolderExtractionJob under test
     *
     * @var FolderExtractionJob
     */
    private FolderExtractionJob $job;

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
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAnonService  = $this->createMock(AnonymizationService::class);
        $this->mockStateService = $this->createMock(BatchStateService::class);
        $this->mockLogger       = $this->createMock(LoggerInterface::class);
        $mockTime               = $this->createMock(ITimeFactory::class);

        $this->job = new FolderExtractionJob(
            $mockTime,
            $this->mockAnonService,
            $this->mockStateService,
            $this->mockLogger
        );

    }//end setUp()


    /**
     * Test sequential extraction of all files
     *
     * @return void
     */
    public function testProcessesAllFilesSequentially(): void
    {
        $batch = [
            'batchId' => 'batch-1',
            'status'  => 'uploading',
            'files'   => [
                ['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
                ['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);

        $this->mockAnonService->expects($this->exactly(2))
            ->method('extractAndDetectEntities')
            ->willReturnMap([
                [1, ['entityCount' => 5, 'entities' => []]],
                [2, ['entityCount' => 3, 'entities' => []]],
            ]);

        // Capture all updateBatch calls to verify state transitions.
        $updates = [];
        $this->mockStateService->method('updateBatch')
            ->willReturnCallback(function (string $id, array $b) use (&$updates) {
                $updates[] = $b;
            });

        // Use reflection to call protected run().
        $ref = new \ReflectionMethod($this->job, 'run');
        $ref->setAccessible(true);
        $ref->invoke($this->job, ['batchId' => 'batch-1']);

        // Final update should be 'review'.
        $last = end($updates);
        $this->assertEquals('review', $last['status']);
        $this->assertEquals('extracted', $last['files'][0]['status']);
        $this->assertEquals('extracted', $last['files'][1]['status']);
        $this->assertEquals(5, $last['files'][0]['entityCount']);

    }//end testProcessesAllFilesSequentially()


    /**
     * Test that a single file failure does not abort the batch
     *
     * @return void
     */
    public function testSingleFileFailureDoesNotAbortBatch(): void
    {
        $batch = [
            'batchId' => 'batch-2',
            'status'  => 'uploading',
            'files'   => [
                ['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
                ['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
                ['fileId' => 3, 'fileName' => 'c.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);

        $this->mockAnonService->method('extractAndDetectEntities')
            ->willReturnCallback(function (int $fileId) {
                if ($fileId === 2) {
                    throw new Exception('Extraction failed');
                }
                return ['entityCount' => 3, 'entities' => []];
            });

        $updates = [];
        $this->mockStateService->method('updateBatch')
            ->willReturnCallback(function (string $id, array $b) use (&$updates) {
                $updates[] = $b;
            });

        $ref = new \ReflectionMethod($this->job, 'run');
        $ref->setAccessible(true);
        $ref->invoke($this->job, ['batchId' => 'batch-2']);

        $last = end($updates);
        $this->assertEquals('review', $last['status']);
        $this->assertEquals('extracted', $last['files'][0]['status']);
        $this->assertEquals('error', $last['files'][1]['status']);
        $this->assertEquals('extracted', $last['files'][2]['status']);
        $this->assertNotEmpty($last['files'][1]['error']);

    }//end testSingleFileFailureDoesNotAbortBatch()


    /**
     * Test that missing batchId logs error and returns
     *
     * @return void
     */
    public function testMissingBatchIdLogsError(): void
    {
        $this->mockLogger->expects($this->once())->method('error');
        $this->mockStateService->expects($this->never())->method('getBatch');

        $ref = new \ReflectionMethod($this->job, 'run');
        $ref->setAccessible(true);
        $ref->invoke($this->job, []);

    }//end testMissingBatchIdLogsError()


    /**
     * Test that expired batch logs error and returns
     *
     * @return void
     */
    public function testExpiredBatchLogsError(): void
    {
        $this->mockStateService->method('getBatch')->willReturn(null);
        $this->mockLogger->expects($this->once())->method('error');
        $this->mockAnonService->expects($this->never())->method('extractAndDetectEntities');

        $ref = new \ReflectionMethod($this->job, 'run');
        $ref->setAccessible(true);
        $ref->invoke($this->job, ['batchId' => 'expired-batch']);

    }//end testExpiredBatchLogsError()


    /**
     * Test status transitions through extracting to review
     *
     * @return void
     */
    public function testStatusTransitionsExtractingToReview(): void
    {
        $batch = [
            'batchId' => 'batch-3',
            'status'  => 'uploading',
            'files'   => [
                ['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockAnonService->method('extractAndDetectEntities')->willReturn(['entityCount' => 1, 'entities' => []]);

        $statuses = [];
        $this->mockStateService->method('updateBatch')
            ->willReturnCallback(function (string $id, array $b) use (&$statuses) {
                $statuses[] = $b['status'];
            });

        $ref = new \ReflectionMethod($this->job, 'run');
        $ref->setAccessible(true);
        $ref->invoke($this->job, ['batchId' => 'batch-3']);

        $this->assertEquals('extracting', $statuses[0]);
        $this->assertEquals('review', end($statuses));

    }//end testStatusTransitionsExtractingToReview()


}//end class
