<?php

/**
 * The request-side half of a Flow operation: what is queued, deduped and
 * skipped as own output (REQ-DDFLO-002, REQ-DDFLO-003).
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
use OCA\Filinq\Flow\FlowOperationRunRepository;
use OCA\Filinq\Flow\FlowOperationTrigger;
use OCA\Filinq\Flow\FlowOutputGuard;
use OCA\Filinq\Flow\Operation\RunOcrOperation;
use OCA\Filinq\Kernel\Contracts\OcrRunServiceInterface;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\SystemTag\MapperEvent;
use OCP\WorkflowEngine\IRuleMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests FlowOperationTrigger.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\FlowOperationTrigger
 * @uses   \OCA\Filinq\Flow\FlowOutputGuard
 * @uses   \OCA\Filinq\Flow\Operation\AbstractFileOperation
 * @uses   \OCA\Filinq\Flow\Operation\RunOcrOperation
 */
class FlowOperationTriggerTest extends TestCase {
	use FlowOperationDoubles;

	/**
	 * @var FlowOperationRunRepository&MockObject
	 */
	private FlowOperationRunRepository $runs;

	/**
	 * @var IJobList&MockObject
	 */
	private IJobList $jobs;

	/**
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder $root;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->runs = $this->createMock(FlowOperationRunRepository::class);
		$this->jobs = $this->createMock(IJobList::class);
		$this->root = $this->createMock(IRootFolder::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The trigger under test.
	 *
	 * @return FlowOperationTrigger The trigger.
	 */
	private function trigger(): FlowOperationTrigger {
		return new FlowOperationTrigger(runs: $this->runs, jobList: $this->jobs, rootFolder: $this->root, logger: $this->logger);

	}//end trigger()

	/**
	 * The OCR operation, the one the scenarios use.
	 *
	 * @return RunOcrOperation The operation.
	 */
	private function ocr(): RunOcrOperation {
		return new RunOcrOperation($this->l10n(), $this->urls(), $this->idleTrigger(), $this->createMock(OcrRunServiceInterface::class));

	}//end ocr()

	/**
	 * A file owned by w.devries.
	 *
	 * @param int $id The file id.
	 *
	 * @return File&MockObject The file.
	 */
	private function file(int $id=812006): File {
		$owner = $this->createMock(IUser::class);
		$owner->method('getUID')->willReturn('w.devries');
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn('scan-bezwaarschrift.pdf');
		$file->method('getOwner')->willReturn($owner);

		return $file;

	}//end file()

	/**
	 * A matcher whose rules for the operation matched with these options.
	 *
	 * @param array<int, array<string, mixed>> $flows The matching flows.
	 *
	 * @return IRuleMatcher&MockObject The matcher.
	 */
	private function matcher(array $flows=[['operation' => '']]): IRuleMatcher {
		$matcher = $this->createMock(IRuleMatcher::class);
		$matcher->method('getFlows')->with(false)->willReturn($flows);

		return $matcher;

	}//end matcher()

	/**
	 * A created file under a matching rule gets one queued run and one job; nothing is processed.
	 *
	 * @return void
	 */
	public function testAMatchedFileIsQueuedNotProcessed(): void {
		$operation = $this->ocr();
		$this->runs->method('isProducedFile')->with(812006)->willReturn(false);
		$this->runs->method('hasRunInFlight')->with('ocr', 812006)->willReturn(false);
		$this->runs->expects($this->once())->method('save')
			->with(
				[
					'operation' => 'ocr',
					'fileId' => 812006,
					'fileName' => 'scan-bezwaarschrift.pdf',
					'ownerUserId' => 'w.devries',
					'triggerEvent' => 'postCreate',
					'resultSummary' => [],
					'status' => 'queued',
				]
			)
			->willReturnCallback(static fn (array $run): array => $run + ['uuid' => 'run-1']);
		$this->jobs->expects($this->once())->method('add')->with(
			FlowOperationJob::class,
			$this->callback(
				static fn (array $argument): bool => $argument['operationClass'] === RunOcrOperation::class
					&& $argument['config'] === '{}'
					&& $argument['run']['uuid'] === 'run-1'
					&& $argument['run']['status'] === 'queued'
			)
		);

		$uuid = $this->trigger()->trigger($operation, '\OCP\Files::postCreate', new GenericEvent($this->file()), $this->matcher([['operation' => '{}'], ['operation' => '{"x":1}']]));

		$this->assertSame('run-1', $uuid);

	}//end testAMatchedFileIsQueuedNotProcessed()

	/**
	 * A second event for a file with a run in flight creates no second run.
	 *
	 * @return void
	 */
	public function testADuplicateTriggerIsNotQueuedTwice(): void {
		$this->runs->method('isProducedFile')->willReturn(false);
		$this->runs->method('hasRunInFlight')->with('ocr', 812006)->willReturn(true);
		$this->runs->expects($this->never())->method('save');
		$this->jobs->expects($this->never())->method('add');

		$this->assertNull($this->trigger()->trigger($this->ocr(), '\OCP\Files::postCreate', new GenericEvent($this->file()), $this->matcher()));

	}//end testADuplicateTriggerIsNotQueuedTwice()

	/**
	 * A file a run recorded as its output is skipped with reason own_output and never queued.
	 *
	 * @return void
	 */
	public function testAProducedFileIsSkippedAsOwnOutput(): void {
		$this->runs->method('isProducedFile')->with(812099)->willReturn(true);
		$this->runs->expects($this->once())->method('save')->with(
			$this->callback(
				static fn (array $run): bool => $run['status'] === 'skipped'
					&& $run['statusReason'] === 'own_output'
					&& $run['fileId'] === 812099
			)
		)->willReturnArgument(0);
		$this->jobs->expects($this->never())->method('add');

		$this->assertNull($this->trigger()->trigger($this->ocr(), '\OCP\Files::postCreate', new GenericEvent($this->file(812099)), $this->matcher()));

	}//end testAProducedFileIsSkippedAsOwnOutput()

	/**
	 * A file created while an operation is writing its output is that output: skipped, not queued.
	 *
	 * @return void
	 */
	public function testAFileCreatedWhileProducingIsSkipped(): void {
		$this->runs->method('isProducedFile')->willReturn(false);
		$this->runs->expects($this->once())->method('save')->with(
			$this->callback(static fn (array $run): bool => $run['status'] === 'skipped' && $run['statusReason'] === 'own_output')
		)->willReturnArgument(0);
		$this->jobs->expects($this->never())->method('add');

		$result = FlowOutputGuard::producing(
			fn (): ?string => $this->trigger()->trigger($this->ocr(), '\OCP\Files::postCreate', new GenericEvent($this->file()), $this->matcher())
		);

		$this->assertNull($result);
		$this->assertFalse(FlowOutputGuard::isProducing());

	}//end testAFileCreatedWhileProducingIsSkipped()

	/**
	 * A folder is ignored, and so is an event no rule matched.
	 *
	 * @return void
	 */
	public function testFoldersAndUnmatchedEventsAreIgnored(): void {
		$this->runs->expects($this->never())->method('save');
		$this->jobs->expects($this->never())->method('add');

		$trigger = $this->trigger();
		$this->assertNull($trigger->trigger($this->ocr(), '\OCP\Files::postCreate', new GenericEvent($this->createMock(Folder::class)), $this->matcher()));
		$this->assertNull($trigger->trigger($this->ocr(), '\OCP\Files::postCreate', new GenericEvent($this->file()), $this->matcher([])));

	}//end testFoldersAndUnmatchedEventsAreIgnored()

	/**
	 * A tag assigned to a file resolves the file by id and records the trigger as tag_assigned; other objects are ignored.
	 *
	 * @return void
	 */
	public function testATagAssignedToAFileQueuesIt(): void {
		$this->root->expects($this->once())->method('getFirstNodeById')->with(812007)->willReturn($this->file(812007));
		$this->runs->method('isProducedFile')->willReturn(false);
		$this->runs->method('hasRunInFlight')->willReturn(false);
		$this->runs->expects($this->once())->method('save')->with(
			$this->callback(static fn (array $run): bool => $run['triggerEvent'] === 'tag_assigned' && $run['fileId'] === 812007)
		)->willReturnCallback(static fn (array $run): array => $run + ['uuid' => 'run-2']);
		$this->jobs->expects($this->once())->method('add');

		$trigger = $this->trigger();
		$this->assertSame('run-2', $trigger->trigger($this->ocr(), MapperEvent::EVENT_ASSIGN, new MapperEvent(MapperEvent::EVENT_ASSIGN, 'files', '812007', [3]), $this->matcher()));
		$this->assertNull($trigger->trigger($this->ocr(), MapperEvent::EVENT_ASSIGN, new MapperEvent(MapperEvent::EVENT_ASSIGN, 'comments', '9', [3]), $this->matcher()));

	}//end testATagAssignedToAFileQueuesIt()

	/**
	 * A rename carries [source, target]: the target is queued.
	 *
	 * @return void
	 */
	public function testARenameQueuesTheTarget(): void {
		$this->runs->method('isProducedFile')->willReturn(false);
		$this->runs->method('hasRunInFlight')->willReturn(false);
		$this->runs->expects($this->once())->method('save')->with(
			$this->callback(static fn (array $run): bool => $run['fileId'] === 5 && $run['triggerEvent'] === 'postRename')
		)->willReturnCallback(static fn (array $run): array => $run + ['uuid' => 'run-3']);

		$this->trigger()->trigger($this->ocr(), '\OCP\Files::postRename', new GenericEvent([$this->file(4), $this->file(5)]), $this->matcher());

	}//end testARenameQueuesTheTarget()

	/**
	 * A store failure is logged and swallowed: the triggering upload never fails.
	 *
	 * @return void
	 */
	public function testAStoreFailureNeverBreaksTheTriggeringRequest(): void {
		$this->runs->method('isProducedFile')->willThrowException(new RuntimeException('OpenRegister is down'));
		$this->jobs->expects($this->never())->method('add');
		$this->logger->expects($this->once())->method('warning')->with(
			'Filinq Flow: the operation could not be queued',
			$this->callback(static fn (array $context): bool => $context['operation'] === 'ocr' && $context['exception'] === 'OpenRegister is down')
		);

		$this->assertNull($this->trigger()->trigger($this->ocr(), '\OCP\Files::postCreate', new GenericEvent($this->file()), $this->matcher()));

	}//end testAStoreFailureNeverBreaksTheTriggeringRequest()
}//end class
