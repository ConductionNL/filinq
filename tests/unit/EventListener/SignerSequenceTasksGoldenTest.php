<?php

/**
 * Golden tests for the earlier signer positions of a sequence step.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\SignerSequenceTasks;
use OCA\Filinq\EventListener\SignerStepTask;
use OCA\OpenRegister\Db\Task;
use OCA\OpenRegister\Db\TaskSequence;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Which earlier positions are proven, and which proofs fail closed.
 *
 * @covers \OCA\Filinq\EventListener\SignerSequenceTasks
 * @uses   \OCA\Filinq\EventListener\SignerStepTask
 */
final class SignerSequenceTasksGoldenTest extends TestCase {

	/**
	 * The mapper reads asked for, in order.
	 *
	 * @var list<string>
	 */
	private array $reads = [];

	/**
	 * Proven earlier positions: tasks above the step are ignored, duplicate declarations collapse,
	 * numeric strings count as positions, and a non-array resolved tier falls back to the snapshot.
	 *
	 * @return void
	 */
	public function testProvenEarlierPositions(): void {
		$first = new Task(uuid: 't1', objectUuid: 'doc-1', sequencePosition: 1);
		$second = new Task(uuid: 't2', objectUuid: 'doc-1', sequencePosition: 2);
		$later = new Task(uuid: 't4', objectUuid: 'doc-2', sequencePosition: 4);
		$snapshot = ['positions' => [['order' => 2], ['order' => 1], ['order' => '1'], ['order' => 3], ['order' => 4]]];
		$sequence = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: $snapshot, resolvedTier: ['name' => 'no positions']);

		$reader = $this->reader(tasks: [$later, $second, $first], sequence: $sequence);
		$this->assertSame([$first, $second], $reader->earlierPositions(step: $this->step(position: 3)));
		$this->assertSame(['sequence', 'tasks'], $this->reads);

	}//end testProvenEarlierPositions()

	/**
	 * Every unproven case fails closed, and the tasks are still read when the sequence is not usable.
	 *
	 * @return void
	 */
	public function testUnprovenPositionsFailClosed(): void {
		$first = new Task(uuid: 't1', objectUuid: 'doc-1', sequencePosition: 1);
		$positions = ['positions' => [['order' => 1], ['order' => 2], ['order' => 3]]];
		$declared = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: $positions);

		// The step's position is not declared.
		$this->assertNull($this->reader(tasks: [$first], sequence: $declared)->earlierPositions(step: $this->step(position: 5)));
		$this->assertSame(['sequence', 'tasks'], $this->reads);

		// A declared earlier position has no task.
		$this->assertNull($this->reader(tasks: [$first], sequence: $declared)->earlierPositions(step: $this->step(position: 3)));

		// A task sits at an undeclared earlier position.
		$gap = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => [['order' => 1], ['order' => 3]]]);
		$stray = new Task(uuid: 't2', objectUuid: 'doc-1', sequencePosition: 2);
		$this->assertNull($this->reader(tasks: [$first, $stray], sequence: $gap)->earlierPositions(step: $this->step(position: 3)));

		// A task above the step without a position still fails closed.
		$loose = new Task(uuid: 'tx', objectUuid: 'doc-1', sequencePosition: null);
		$this->assertNull($this->reader(tasks: [$loose, $first], sequence: $declared)->earlierPositions(step: $this->step(position: 2)));

		// An unusable sequence still reads the tasks before failing closed.
		$this->assertNull($this->reader(tasks: [$first], sequence: null)->earlierPositions(step: $this->step(position: 2)));
		$this->assertSame(['sequence', 'tasks'], $this->reads);

		// Positions that are not a list fail closed.
		$broken = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => 'one']);
		$this->assertNull($this->reader(tasks: [$first], sequence: $broken)->earlierPositions(step: $this->step(position: 2)));

	}//end testUnprovenPositionsFailClosed()

	/**
	 * The step at a position of doc-1 in seq-1.
	 *
	 * @param int $position The position.
	 *
	 * @return SignerStepTask
	 */
	private function step(int $position): SignerStepTask {
		return new SignerStepTask(sequenceUuid: 'seq-1', taskUuid: 'task-' . $position, position: $position, objectUuid: 'doc-1');

	}//end step()

	/**
	 * A reader whose mappers answer $tasks and $sequence and record the order they are read in.
	 *
	 * @param array<int, object> $tasks    The sequence's tasks.
	 * @param mixed              $sequence What the sequence mapper returns.
	 *
	 * @return SignerSequenceTasks
	 */
	private function reader(array $tasks, mixed $sequence): SignerSequenceTasks {
		$this->reads = [];
		$reads = &$this->reads;
		$taskMapper = new class($tasks, $reads) {
			/**
			 * Constructor.
			 *
			 * @param array<int, object> $tasks The tasks.
			 * @param list<string>       $reads The read log.
			 */
			public function __construct(private array $tasks, private array &$reads) {
			}

			/**
			 * The tasks.
			 *
			 * @param string $sequenceUuid The sequence.
			 *
			 * @return array<int, object>
			 */
			public function findBySequence(string $sequenceUuid): array {
				$this->reads[] = 'tasks';
				return $this->tasks;
			}
		};
		$sequenceMapper = new class($sequence, $reads) {
			/**
			 * Constructor.
			 *
			 * @param mixed        $sequence The sequence.
			 * @param list<string> $reads    The read log.
			 */
			public function __construct(private mixed $sequence, private array &$reads) {
			}

			/**
			 * The sequence.
			 *
			 * @param string $uuid The sequence uuid.
			 *
			 * @return mixed
			 */
			public function findByUuid(string $uuid): mixed {
				$this->reads[] = 'sequence';
				return $this->sequence;
			}
		};
		$mappers = ['OCA\OpenRegister\Db\TaskMapper' => $taskMapper, 'OCA\OpenRegister\Db\TaskSequenceMapper' => $sequenceMapper];
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static fn (string $id): object => $mappers[$id]);

		return new SignerSequenceTasks(container: $container, logger: $this->createMock(LoggerInterface::class));

	}//end reader()
}//end class
