<?php

/**
 * Characterization tests for SigningTaskListener's terminal-task path
 *
 * Pins which completed tasks reach the translator and the exact decision
 * handed on: the signer is the task's own completedBy, never anyone else.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\SignerEventTranslator;
use OCA\Filinq\EventListener\SignerStepDecision;
use OCA\Filinq\EventListener\SignerStepTask;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\OpenRegister\Db\Task;
use OCA\OpenRegister\Event\TaskTerminalEvent;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Characterization of the terminal-task handling.
 *
 * @covers \OCA\Filinq\EventListener\SigningTaskListener
 * @uses \OCA\Filinq\EventListener\SignerStepDecision
 * @uses \OCA\Filinq\EventListener\SignerStepTask
 */
class SigningTaskListenerTerminalTraceTest extends TestCase {

	private const OWNED = 'sign-req-1';

	private SignerEventTranslator&MockObject $translator;

	private ObjectService&MockObject $objects;

	/**
	 * @var array<int, array<string, mixed>> The ownership lookups made.
	 */
	private array $lookups = [];

	/**
	 * Fresh doubles; only OWNED in the bound register and schema, looked up without RBAC, is ours.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->translator = $this->createMock(SignerEventTranslator::class);
		$this->objects = $this->createMock(ObjectService::class);
		$this->objects->method('find')->willReturnCallback(
			function (string $id = '', string $register = '', string $schema = '', bool $_rbac = true): ?stdClass {
				$this->lookups[] = ['id' => $id, 'register' => $register, 'schema' => $schema, 'rbac' => $_rbac];
				if ($id === self::OWNED && $register === 'filinq' && $schema === 'signingRequest' && $_rbac === false) {
					return new stdClass();
				}

				return null;
			}
		);

	}//end setUp()

	/**
	 * A completed task on our object hands on its own signer, comment, position and outcome.
	 *
	 * @return void
	 */
	public function testCompletedTaskHandsOnItsOwnDecision(): void {
		$this->expectDecision(
			task: ['sequenceUuid' => 'seq-1', 'taskUuid' => 'task-1', 'position' => 2, 'objectUuid' => self::OWNED],
			decision: ['userId' => 'alice', 'comment' => 'akkoord', 'isRejecting' => false]
		);

		$this->dispatch(task: $this->task(completedBy: 'alice', comment: 'akkoord', outcome: 'approved'));

		$this->assertSame([['id' => self::OWNED, 'register' => 'filinq', 'schema' => 'signingRequest', 'rbac' => false]], $this->lookups);

	}//end testCompletedTaskHandsOnItsOwnDecision()

	/**
	 * The signer handed on is the task's completedBy for each task, not a fixed or earlier user.
	 *
	 * @return void
	 */
	public function testTheSignerFollowsTheTask(): void {
		$signers = [];
		$this->translator->method('onTaskDecided')->willReturnCallback(
			function (SignerStepTask $task, SignerStepDecision $decision) use (&$signers): void {
				$signers[] = $decision->userId;
			}
		);

		$this->dispatch(task: $this->task(completedBy: 'bob'));
		$this->dispatch(task: $this->task(completedBy: 'admin'));
		$this->dispatch(task: $this->task(completedBy: null));

		$this->assertSame(['bob', 'admin', null], $signers);

	}//end testTheSignerFollowsTheTask()

	/**
	 * Missing comment, uuid and position read as null, '' and 0; a rejecting outcome is classified.
	 *
	 * @return void
	 */
	public function testDefaultsAndRejection(): void {
		$this->expectDecision(
			task: ['sequenceUuid' => 'seq-1', 'taskUuid' => '', 'position' => 0, 'objectUuid' => self::OWNED],
			decision: ['userId' => 'carol', 'comment' => null, 'isRejecting' => true]
		);

		$this->dispatch(
			task: new Task(state: 'completed', outcome: ' Rejected ', objectUuid: self::OWNED, sequenceUuid: 'seq-1', completedBy: 'carol')
		);

	}//end testDefaultsAndRejection()

	/**
	 * Uncommitted, non-completed, unsequenced, unanchored and foreign tasks reach nothing.
	 *
	 * @return void
	 */
	public function testTasksThatReachNothing(): void {
		$this->translator->expects($this->never())->method('onTaskDecided');

		$this->dispatch(task: $this->task(), committed: false);
		$this->dispatch(task: $this->task(state: 'terminated'));
		$this->dispatch(task: $this->task(sequenceUuid: null));
		$this->dispatch(task: $this->task(objectUuid: ''));
		$this->assertSame([], $this->lookups);

		$this->dispatch(task: $this->task(objectUuid: 'someone-elses'));
		$this->assertCount(1, $this->lookups);

	}//end testTasksThatReachNothing()

	/**
	 * Expect one decision with exactly these task and decision fields.
	 *
	 * @param array<string, mixed> $task     The SignerStepTask fields.
	 * @param array<string, mixed> $decision The SignerStepDecision fields.
	 *
	 * @return void
	 */
	private function expectDecision(array $task, array $decision): void {
		$this->translator->expects($this->once())->method('onTaskDecided')->with(
			$this->callback(fn (SignerStepTask $actual): bool => get_object_vars($actual) === $task),
			$this->callback(fn (SignerStepDecision $actual): bool => get_object_vars($actual) === $decision)
		);
	}//end expectDecision()

	/**
	 * Dispatch a terminal event through a listener bound to filinq/signingRequest.
	 *
	 * @param Task $task The task.
	 * @param bool $committed Whether the transition committed.
	 *
	 * @return void
	 */
	private function dispatch(Task $task, bool $committed = true): void {
		$settings = $this->createMock(SettingsServiceInterface::class);
		$settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settings->method('getObjectService')->willReturn($this->objects);

		$listener = new SigningTaskListener(translator: $this->translator, settingsService: $settings, logger: $this->createMock(LoggerInterface::class));
		$listener->handle(new TaskTerminalEvent(task: $task, committed: $committed));
	}//end dispatch()

	/**
	 * A task on the owned object.
	 *
	 * @param string $state The state.
	 * @param string|null $completedBy The completing user.
	 * @param string|null $comment The comment.
	 * @param string|null $outcome The outcome.
	 * @param string|null $sequenceUuid The sequence.
	 * @param string $objectUuid The anchor object.
	 *
	 * @return Task The task.
	 */
	private function task(
		string $state = 'completed',
		?string $completedBy = 'alice',
		?string $comment = null,
		?string $outcome = 'approved',
		?string $sequenceUuid = 'seq-1',
		string $objectUuid = self::OWNED,
	): Task {
		return new Task(
			uuid: 'task-1',
			state: $state,
			outcome: $outcome,
			objectUuid: $objectUuid,
			sequenceUuid: $sequenceUuid,
			sequencePosition: 2,
			completedBy: $completedBy,
			comment: $comment
		);
	}//end task()
}//end class
