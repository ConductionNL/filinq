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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
 */

namespace OCA\DocuDesk\Tests\Unit\BackgroundJob;

use Exception;
use OCA\DocuDesk\BackgroundJob\BatchCorrespondenceJob;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchCorrespondenceJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class BatchCorrespondenceJobTest extends TestCase
{

    /**
     * The job under test (accessed via reflection to invoke run())
     *
     * @var BatchCorrespondenceJob
     */
    private BatchCorrespondenceJob $job;

    /**
     * Mock CorrespondenceService
     *
     * @var CorrespondenceService&MockObject
     */
    private CorrespondenceService $mockCorrSvc;

    /**
     * Mock LoggerInterface
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $mockLogger;

    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCorrSvc = $this->createMock(originalClassName: CorrespondenceService::class);
        $this->mockLogger  = $this->createMock(originalClassName: LoggerInterface::class);

        $timeFactory = $this->createMock(originalClassName: ITimeFactory::class);

        $this->job = new BatchCorrespondenceJob(
            time: $timeFactory,
            corrSvc: $this->mockCorrSvc,
            logger: $this->mockLogger
        );

    }//end setUp()

    /**
     * Invoke the protected run() method via reflection
     *
     * @param array $argument Job argument array
     *
     * @return void
     */
    private function invokeRun(array $argument): void
    {
        $reflection = new \ReflectionMethod(BatchCorrespondenceJob::class, 'run');
        $reflection->setAccessible(true);
        $reflection->invoke($this->job, $argument);

    }//end invokeRun()

    /**
     * Test successful run processes all recipients and marks job complete
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testRunProcessesAllRecipientsSuccessfully(): void
    {
        $recipientIds = ['r1', 'r2', 'r3'];

        $this->mockCorrSvc->expects($this->atLeast(count($recipientIds)))
            ->method('generate')
            ->willReturn(
                [
                    'content'       => '%PDF%',
                    'format'        => 'pdf',
                    'warnings'      => [],
                    'registerEntry' => [],
                ]
            );

        // storeJobStatus: once for init, N times mid-progress, once for complete.
        $this->mockCorrSvc->expects($this->atLeast(count($recipientIds) + 2))
            ->method('storeJobStatus');

        $this->invokeRun(
            [
                'jobId'        => 'job-1',
                'templateId'   => 'tmpl-1',
                'recipientIds' => $recipientIds,
                'options'      => ['register' => 'brp', 'schema' => 'persoon', 'userId' => 'user1'],
            ]
        );

    }//end testRunProcessesAllRecipientsSuccessfully()

    /**
     * Test run silently returns when required arguments are missing
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testRunWithMissingArgumentsLogsErrorAndReturns(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');

        $this->mockCorrSvc->expects($this->never())
            ->method('generate');

        $this->invokeRun(
            [
                'jobId'      => '',
                'templateId' => '',
            ]
        );

    }//end testRunWithMissingArgumentsLogsErrorAndReturns()

    /**
     * Test partial error: one recipient fails but the rest succeed
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testRunContinuesOnPartialError(): void
    {
        $call = 0;
        $this->mockCorrSvc->method('generate')
            ->willReturnCallback(
                    function () use (&$call) {
                        $call++;
                        if ($call === 2) {
                            throw new Exception('Recipient lookup failed');
                        }

                        return [
                            'content'       => '%PDF%',
                            'format'        => 'pdf',
                            'warnings'      => [],
                            'registerEntry' => [],
                        ];
                    }
                    );

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('warning');

        $this->invokeRun(
            [
                'jobId'        => 'job-2',
                'templateId'   => 'tmpl-1',
                'recipientIds' => ['r1', 'r2', 'r3'],
                'options'      => ['register' => 'brp', 'schema' => 'persoon'],
            ]
        );

    }//end testRunContinuesOnPartialError()

    /**
     * Test ownerUserId is propagated through all storeJobStatus calls
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testOwnerUserIdIsPropagatedToAllStatusUpdates(): void
    {
        $this->mockCorrSvc->method('generate')
            ->willReturn(
                [
                    'content'       => '%PDF%',
                    'format'        => 'pdf',
                    'warnings'      => [],
                    'registerEntry' => [],
                ]
            );

        $this->mockCorrSvc->expects($this->atLeast(3))
            ->method('storeJobStatus')
            ->with(
                $this->anything(),
                $this->callback(
                        static function ($data) {
                            return ($data['ownerUserId'] ?? '') === 'owner-uid';
                        }
                        )
            );

        $this->invokeRun(
            [
                'jobId'        => 'job-3',
                'templateId'   => 'tmpl-1',
                'recipientIds' => ['r1'],
                'options'      => ['userId' => 'owner-uid'],
            ]
        );

    }//end testOwnerUserIdIsPropagatedToAllStatusUpdates()
}//end class
