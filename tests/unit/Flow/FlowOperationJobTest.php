<?php

/**
 * The cron-side half of a Flow operation: the run moves queued → running →
 * its outcome, the operation runs as the file owner inside the output guard,
 * and every failure ends the run failed with a reason (REQ-DDFLO-002).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FlowOperationJob;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\FlowOperationRunRepository;
use OCA\Filinq\Flow\FlowOutputGuard;
use OCA\Filinq\Flow\Operation\RunOcrOperation;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use UnexpectedValueException;

/**
 * Tests FlowOperationJob.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\FlowOperationJob
 * @uses   \OCA\Filinq\Flow\FlowOperationOutcome
 * @uses   \OCA\Filinq\Flow\FlowOutputGuard
 */
class FlowOperationJobTest extends TestCase {

	/**
	 * Every run the job stored, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * @var RunOcrOperation&MockObject
	 */
	private RunOcrOperation $operation;

	/**
	 * @var IUserSession&MockObject
	 */
	private IUserSession $session;

	/**
	 * @var IUserManager&MockObject
	 */
	private IUserManager $users;

	/**
	 * @var Folder&MockObject
	 */
	private Folder $home;

	/**
	 * The owner.
	 *
	 * @var IUser&MockObject
	 */
	private IUser $owner;

	/**
	 * Set up: a run for file 812006 of w.devries, who exists, whose file exists.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->saved = [];
		$this->operation = $this->createMock(RunOcrOperation::class);
		$this->operation->method('decodeConfig')->willReturn([]);
		$this->session = $this->createMock(IUserSession::class);
		$this->owner = $this->createMock(IUser::class);
		$this->users = $this->createMock(IUserManager::class);
		$this->users->method('get')->willReturnCallback(fn (string $uid): ?IUser => $uid === 'w.devries' ? $this->owner : null);
		$this->home = $this->createMock(Folder::class);

	}//end setUp()

	/**
	 * Run the job on a queued run.
	 *
	 * @param array<string, mixed> $argument The job argument; the defaults fill the rest.
	 *
	 * @return void
	 */
	private function runJob(array $argument=[]): void {
		$runs = $this->createMock(FlowOperationRunRepository::class);
		$runs->method('save')->willReturnCallback(
			function (array $run): array {
				$this->saved[] = $run;
				return $run;
			}
		);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('w.devries')->willReturn($this->home);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with(RunOcrOperation::class)->willReturn($this->operation);
		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getDateTime')->willReturn(new \DateTime('2026-07-17T09:12:04+00:00'));

		$job = new FlowOperationJob($clock, $runs, $root, $this->users, $this->session, $container, $this->createMock(LoggerInterface::class));
		$argument = array_replace(
			[
				'operationClass' => RunOcrOperation::class,
				'config' => '',
				'run' => ['uuid' => 'run-1', 'operation' => 'ocr', 'fileId' => 812006, 'ownerUserId' => 'w.devries', 'status' => 'queued'],
			],
			$argument
		);
		(new \ReflectionMethod($job, 'run'))->invoke($job, $argument);

	}//end runJob()

	/**
	 * The file exists in the owner's tree.
	 *
	 * @return File&MockObject The file.
	 */
	private function givenTheFile(): File {
		$file = $this->createMock(File::class);
		$this->home->method('getFirstNodeById')->with(812006)->willReturn($file);

		return $file;

	}//end givenTheFile()

	/**
	 * A successful run is started, executed as the owner inside the output guard, and finished with its summary.
	 *
	 * @return void
	 */
	public function testASuccessfulRunIsRecordedThroughItsLifecycle(): void {
		$file = $this->givenTheFile();
		$previous = $this->createMock(IUser::class);
		$this->session->method('getUser')->willReturn($previous);
		$this->session->expects($this->exactly(2))->method('setVolatileActiveUser')
			->willReturnCallback(
				function (?IUser $user) use ($previous): void {
					static $call = 0;
					$call++;
					$this->assertSame($call === 1 ? $this->owner : $previous, $user);
				}
			);
		$this->operation->expects($this->once())->method('execute')
			->with($file, [], $this->home)
			->willReturnCallback(
				function (): FlowOperationOutcome {
					$this->assertTrue(FlowOutputGuard::isProducing());
					return FlowOperationOutcome::succeeded(summary: ['confidence' => 88.2, 'textLength' => 3120]);
				}
			);

		$this->runJob();

		$this->assertCount(2, $this->saved);
		$this->assertSame('running', $this->saved[0]['status']);
		$this->assertSame('2026-07-17T09:12:04+00:00', $this->saved[0]['startedAt']);
		$this->assertSame('succeeded', $this->saved[1]['status']);
		$this->assertSame(['confidence' => 88.2, 'textLength' => 3120], $this->saved[1]['resultSummary']);
		$this->assertSame('2026-07-17T09:12:04+00:00', $this->saved[1]['finishedAt']);
		$this->assertNull($this->saved[1]['errorMessage']);
		$this->assertSame('run-1', $this->saved[1]['uuid']);
		$this->assertFalse(FlowOutputGuard::isProducing());

	}//end testASuccessfulRunIsRecordedThroughItsLifecycle()

	/**
	 * A produced file is recorded on the run.
	 *
	 * @return void
	 */
	public function testAProducedFileIsRecordedOnTheRun(): void {
		$this->givenTheFile();
		$this->operation->method('execute')->willReturn(FlowOperationOutcome::succeeded(summary: ['producedFileId' => 9], producedFileId: 9));

		$this->runJob();

		$this->assertSame(9, $this->saved[1]['producedFileId']);

	}//end testAProducedFileIsRecordedOnTheRun()

	/**
	 * A class outside the four operations is never resolved; the run fails.
	 *
	 * @return void
	 */
	public function testAnUnknownOperationClassFailsTheRun(): void {
		$this->operation->expects($this->never())->method('execute');

		$this->runJob(['operationClass' => \stdClass::class]);

		$this->assertSame('failed', $this->saved[1]['status']);
		$this->assertSame('invalid_operation', $this->saved[1]['statusReason']);

	}//end testAnUnknownOperationClassFailsTheRun()

	/**
	 * Options the operation now refuses fail the run.
	 *
	 * @return void
	 */
	public function testOptionsRefusedAtRunTimeFailTheRun(): void {
		$this->operation->method('validateOperation')->willThrowException(new UnexpectedValueException('No validation profile exists'));
		$this->operation->expects($this->never())->method('execute');

		$this->runJob(['config' => '{"documentType":"gone"}']);

		$this->assertSame('invalid_operation', $this->saved[1]['statusReason']);
		$this->assertSame('No validation profile exists', $this->saved[1]['errorMessage']);

	}//end testOptionsRefusedAtRunTimeFailTheRun()

	/**
	 * An owner who no longer exists fails the run without touching the session.
	 *
	 * @return void
	 */
	public function testAMissingOwnerFailsTheRun(): void {
		$this->session->expects($this->never())->method('setVolatileActiveUser');

		$this->runJob(['run' => ['uuid' => 'run-1', 'operation' => 'ocr', 'fileId' => 812006, 'ownerUserId' => 'gone', 'status' => 'queued']]);

		$this->assertSame('owner_not_found', $this->saved[1]['statusReason']);

	}//end testAMissingOwnerFailsTheRun()

	/**
	 * A file no longer in the owner's tree fails the run, and the session is restored.
	 *
	 * @return void
	 */
	public function testAMissingFileFailsTheRun(): void {
		$this->home->method('getFirstNodeById')->willReturn(null);
		$this->session->expects($this->exactly(2))->method('setVolatileActiveUser');
		$this->operation->expects($this->never())->method('execute');

		$this->runJob();

		$this->assertSame('failed', $this->saved[1]['status']);
		$this->assertSame('file_not_found', $this->saved[1]['statusReason']);

	}//end testAMissingFileFailsTheRun()

	/**
	 * An engine that throws ends the run failed with its message, never stuck in running.
	 *
	 * @return void
	 */
	public function testAThrowingOperationFailsTheRun(): void {
		$this->givenTheFile();
		$this->operation->method('execute')->willThrowException(new RuntimeException('Tesseract crashed'));

		$this->runJob();

		$this->assertSame('failed', $this->saved[1]['status']);
		$this->assertSame('operation_error', $this->saved[1]['statusReason']);
		$this->assertSame('Tesseract crashed', $this->saved[1]['errorMessage']);
		$this->assertFalse(FlowOutputGuard::isProducing());

	}//end testAThrowingOperationFailsTheRun()

	/**
	 * A job without a run stores nothing.
	 *
	 * @return void
	 */
	public function testAJobWithoutARunStoresNothing(): void {
		$this->runJob(['run' => []]);

		$this->assertSame([], $this->saved);

	}//end testAJobWithoutARunStoresNothing()
}//end class
