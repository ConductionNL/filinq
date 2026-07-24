<?php

/**
 * Unit tests for BatchDocumentJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-2
 */

namespace OCA\DocuDesk\Tests\Unit\BackgroundJob;

use Exception;
use OCA\DocuDesk\BackgroundJob\BatchDocumentJob;
use OCA\DocuDesk\Service\DocumentService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchDocumentJob
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
class BatchDocumentJobTest extends TestCase
{

    /**
     * The job under test (exposed for protected method testing).
     *
     * @var BatchDocumentJob
     */
    private BatchDocumentJob $job;

    /**
     * Mock document service.
     *
     * @var DocumentService&MockObject
     */
    private DocumentService $documentSvc;

    /**
     * Mock logger.
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentSvc = $this->createMock(DocumentService::class);
        $this->logger      = $this->createMock(LoggerInterface::class);

        $timeFactory = $this->createMock(ITimeFactory::class);
        $timeFactory->method('getTime')->willReturn(0);

        $this->job = new BatchDocumentJob(
            $timeFactory,
            $this->documentSvc,
            $this->logger
        );

    }//end setUp()

    /**
     * Test that missing jobId or templateId logs an error and returns early.
     *
     * @return void
     */
    public function testRunLogsErrorWhenArgumentsMissing(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('missing required arguments'));

        $this->documentSvc->expects($this->never())
            ->method('generateDocument');

        // Call run via reflection to test the protected method.
        $reflect = new \ReflectionMethod(BatchDocumentJob::class, 'run');
        $reflect->setAccessible(true);
        $reflect->invoke($this->job, ['jobId' => '', 'templateId' => '']);

    }//end testRunLogsErrorWhenArgumentsMissing()

    /**
     * Test that run processes all objects and marks job completed.
     *
     * @return void
     */
    public function testRunProcessesAllObjectsAndMarksDone(): void
    {
        $this->documentSvc->method('generateDocument')
            ->willReturn([
                'content'  => '%PDF%',
                'format'   => 'pdf',
                'metadata' => [],
                'warnings' => [],
                'output'   => [
                    'mode'   => 'files',
                    'fileId' => 7,
                    'path'   => '/user1/files/DocuDesk/procest/job-1/x.pdf',
                    'name'   => 'x.pdf',
                    'size'   => 10,
                ],
            ]);

        $statusUpdates = [];
        $this->documentSvc->method('updateJobStatus')
            ->willReturnCallback(
                function (string $jobId, array $status) use (&$statusUpdates): void {
                    $statusUpdates[] = $status;
                }
            );

        $reflect = new \ReflectionMethod(BatchDocumentJob::class, 'run');
        $reflect->setAccessible(true);
        $reflect->invoke(
            $this->job,
            [
                'jobId'      => 'test-job-1',
                'templateId' => 'tmpl-1',
                'objectIds'  => ['o1', 'o2', 'o3'],
                'options'    => ['register' => 'brp', 'schema' => 'persoon', 'output' => ['mode' => 'files']],
            ]
        );

        $lastStatus = end($statusUpdates);
        $this->assertNotFalse($lastStatus);
        $this->assertStringContainsString('completed', $lastStatus['status']);
        $this->assertEquals(3, $lastStatus['total']);
        $this->assertEquals(3, $lastStatus['completed']);
        $this->assertEquals(0, $lastStatus['errors']);

        foreach ($lastStatus['results'] as $result) {
            $this->assertEquals('success', $result['status']);
            $this->assertEquals(7, $result['fileId']);
            $this->assertEquals('/user1/files/DocuDesk/procest/job-1/x.pdf', $result['path']);
        }

    }//end testRunProcessesAllObjectsAndMarksDone()

    /**
     * Test that individual failures do not abort the batch.
     *
     * @return void
     */
    public function testRunDoesNotAbortOnPartialFailure(): void
    {
        $callCount = 0;
        $this->documentSvc->method('generateDocument')
            ->willReturnCallback(
                function () use (&$callCount) {
                    $callCount++;
                    if ($callCount === 2) {
                        throw new Exception('Data not found');
                    }

                    return [
                        'content'  => '%PDF%',
                        'format'   => 'pdf',
                        'metadata' => [],
                        'warnings' => [],
                    ];
                }
            );

        $statusUpdates = [];
        $this->documentSvc->method('updateJobStatus')
            ->willReturnCallback(
                function (string $jobId, array $status) use (&$statusUpdates): void {
                    $statusUpdates[] = $status;
                }
            );

        $reflect = new \ReflectionMethod(BatchDocumentJob::class, 'run');
        $reflect->setAccessible(true);
        $reflect->invoke(
            $this->job,
            [
                'jobId'      => 'test-job-2',
                'templateId' => 'tmpl-1',
                'objectIds'  => ['o1', 'o2', 'o3'],
                'options'    => [],
            ]
        );

        $lastStatus = end($statusUpdates);
        $this->assertNotFalse($lastStatus);
        $this->assertEquals('completed_with_errors', $lastStatus['status']);
        $this->assertEquals(3, $lastStatus['total']);
        $this->assertEquals(2, $lastStatus['completed']);
        $this->assertEquals(1, $lastStatus['errors']);

    }//end testRunDoesNotAbortOnPartialFailure()
}//end class
