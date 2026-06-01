<?php

/**
 * Unit tests for BatchCorrespondenceJob
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
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-6.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\BackgroundJob;

use OCA\DocuDesk\BackgroundJob\BatchCorrespondenceJob;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchCorrespondenceJob
 *
 * Tests BatchCorrespondenceJob by exercising the public surface (constructor)
 * and the run() method via a test double that exposes it.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchCorrespondenceJobTest extends TestCase
{

    /**
     * @var CorrespondenceService|MockObject
     */
    private CorrespondenceService|MockObject $mockCorrSvc;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var ITimeFactory|MockObject
     */
    private ITimeFactory|MockObject $mockTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCorrSvc = $this->createMock(CorrespondenceService::class);
        $this->mockLogger  = $this->createMock(LoggerInterface::class);
        $this->mockTime    = $this->createMock(ITimeFactory::class);

    }//end setUp()

    /**
     * Test that the job can be instantiated.
     *
     * @return void
     */
    public function testJobCanBeInstantiated(): void
    {
        $job = new BatchCorrespondenceJob(
            time: $this->mockTime,
            corrSvc: $this->mockCorrSvc,
            logger: $this->mockLogger,
        );

        $this->assertInstanceOf(BatchCorrespondenceJob::class, $job);

    }//end testJobCanBeInstantiated()

    /**
     * Test that run with missing arguments logs error without processing.
     *
     * We expose run() via an anonymous subclass since it is protected.
     *
     * @return void
     */
    public function testRunLogsErrorForMissingArguments(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('BatchCorrespondenceJob: missing required arguments'),
                $this->anything()
            );

        $job = new class(
            $this->mockTime,
            $this->mockCorrSvc,
            $this->mockLogger,
        ) extends BatchCorrespondenceJob {
            public function runPublic(mixed $argument): void
            {
                $this->run($argument);
            }//end runPublic()
        };

        $job->runPublic([]);

    }//end testRunLogsErrorForMissingArguments()

    /**
     * Test that run with empty jobId logs error.
     *
     * @return void
     */
    public function testRunLogsErrorForEmptyJobId(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');

        $job = new class(
            $this->mockTime,
            $this->mockCorrSvc,
            $this->mockLogger,
        ) extends BatchCorrespondenceJob {
            public function runPublic(mixed $argument): void
            {
                $this->run($argument);
            }//end runPublic()
        };

        $job->runPublic(['jobId' => '', 'templateId' => '']);

    }//end testRunLogsErrorForEmptyJobId()

    /**
     * Test that run calls corrSvc.storeJobStatus when arguments are valid.
     *
     * @return void
     */
    public function testRunInitializesJobStatusForValidArguments(): void
    {
        $this->mockCorrSvc->expects($this->atLeastOnce())
            ->method('storeJobStatus');

        $job = new class(
            $this->mockTime,
            $this->mockCorrSvc,
            $this->mockLogger,
        ) extends BatchCorrespondenceJob {
            public function runPublic(mixed $argument): void
            {
                $this->run($argument);
            }//end runPublic()
        };

        $job->runPublic(
                [
                    'jobId'        => 'job-uuid',
                    'templateId'   => 'tmpl-uuid',
                    'recipientIds' => [],
                    'options'      => [],
                ]
                );

    }//end testRunInitializesJobStatusForValidArguments()
}//end class
