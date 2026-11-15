<?php

/**
 * Unit tests for SignerSequenceTasks.
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

/**
 * Reads a sequence's tasks through OR's task mapper, failing closed.
 *
 * @covers \OCA\Filinq\EventListener\SignerSequenceTasks
 * @uses   \OCA\Filinq\EventListener\SignerStepTask
 */
final class SignerSequenceTasksTest extends TestCase {

	/**
	 * Build the reader around a container answering with $mapper.
	 *
	 * @param mixed                $mapper The resolved mapper.
	 * @param LoggerInterface|null $logger Logger, a mock when null.
	 *
	 * @return SignerSequenceTasks
	 */
	private function reader(mixed $mapper, ?LoggerInterface $logger = null): SignerSequenceTasks {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Db\TaskMapper')->willReturn($mapper);

		return new SignerSequenceTasks(container: $container, logger: $logger ?? $this->createMock(LoggerInterface::class));
	}//end reader()

	/**
	 * A mapper answering findBySequence with $result.
	 *
	 * @param mixed $result What findBySequence returns.
	 *
	 * @return object
	 */
	private function mapper(mixed $result): object {
		return new class ($result) {
			/**
			 * Sequence uuids asked for.
			 *
			 * @var array<int, string>
			 */
			public array $asked = [];

			/**
			 * Constructor.
			 *
			 * @param mixed $result What findBySequence returns.
			 */
			public function __construct(private mixed $result) {
			}

			/**
			 * The tasks of a sequence.
			 *
			 * @param string $sequenceUuid The sequence.
			 *
			 * @return mixed
			 */
			public function findBySequence(string $sequenceUuid): mixed {
				$this->asked[] = $sequenceUuid;
				return $this->result;
			}
		};
	}//end mapper()

	/**
	 * The mapper's tasks come back in order, without non-objects.
	 *
	 * @return void
	 */
	public function testReturnsTheSequenceTasks(): void {
		$first  = new stdClass();
		$second = new stdClass();
		$mapper = $this->mapper(result: [3 => $first, 'x' => 'not a task', 7 => $second]);

		$this->assertSame([$first, $second], $this->reader(mapper: $mapper)->forSequence(sequenceUuid: 'seq-1'));
		$this->assertSame(['seq-1'], $mapper->asked);
	}//end testReturnsTheSequenceTasks()

	/**
	 * A reader whose container answers both the task and the sequence mapper.
	 *
	 * @param array<int, object>|null $tasks    The sequence's tasks, null when unreadable.
	 * @param mixed                   $sequence What the sequence mapper returns.
	 *
	 * @return SignerSequenceTasks
	 */
	private function declaredReader(?array $tasks, mixed $sequence): SignerSequenceTasks {
		$sequences = new class($sequence) {
			/**
			 * Constructor.
			 *
			 * @param mixed $sequence What findByUuid returns.
			 */
			public function __construct(private mixed $sequence) {
			}

			/**
			 * The sequence.
			 *
			 * @param string $uuid The sequence uuid.
			 *
			 * @return mixed
			 */
			public function findByUuid(string $uuid): mixed {
				return $this->sequence;
			}
		};
		$mappers = [
			'OCA\OpenRegister\Db\TaskMapper' => $this->mapper(result: $tasks),
			'OCA\OpenRegister\Db\TaskSequenceMapper' => $sequences,
		];
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static fn (string $id): object => $mappers[$id]);

		return new SignerSequenceTasks(container: $container, logger: $this->createMock(LoggerInterface::class));
	}//end declaredReader()

	/**
	 * The step at a declared position.
	 *
	 * @param int $position The position.
	 *
	 * @return SignerStepTask
	 */
	private function step(int $position): SignerStepTask {
		return new SignerStepTask(sequenceUuid: 'seq-1', taskUuid: 'task-' . $position, position: $position, objectUuid: 'doc-1');
	}//end step()

	/**
	 * The tasks at the declared earlier positions come back in position order.
	 *
	 * @return void
	 */
	public function testEarlierPositionsFollowTheDeclaration(): void {
		$second = new Task(uuid: 'task-2', objectUuid: 'doc-1', sequencePosition: 2);
		$first  = new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1);
		$third  = new Task(uuid: 'task-3', objectUuid: 'doc-1', sequencePosition: 3);
		$sequence = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => [['order' => 1], ['role' => 'b'], ['order' => 3]]]);
		$reader = $this->declaredReader(tasks: [$second, $first, $third], sequence: $sequence);

		$this->assertSame([$first, $second], $reader->earlierPositions(step: $this->step(position: 3)));
		$this->assertSame([], $reader->earlierPositions(step: $this->step(position: 1)));
	}//end testEarlierPositionsFollowTheDeclaration()

	/**
	 * Declarations that cannot be used fail closed.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function unusableSequences(): array {
		return [
			'no sequence' => [null],
			'no positions' => [new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['name' => 'sign'])],
			'empty positions' => [new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => []])],
			'a non-list entry' => [new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => [['order' => 1], 'two']])],
			'another anchor' => [new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-2', templateSnapshot: ['positions' => [['order' => 1], ['order' => 2]]])],
		];
	}//end unusableSequences()

	/**
	 * An unusable sequence gives no earlier positions.
	 *
	 * @param mixed $sequence What the sequence mapper returns.
	 *
	 * @return void
	 */
	#[DataProvider('unusableSequences')]
	public function testAnUnusableSequenceFailsClosed(mixed $sequence): void {
		$reader = $this->declaredReader(tasks: [new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1)], sequence: $sequence);

		$this->assertNull($reader->earlierPositions(step: $this->step(position: 2)));
	}//end testAnUnusableSequenceFailsClosed()

	/**
	 * The resolved tier wins over the template snapshot.
	 *
	 * @return void
	 */
	public function testTheResolvedTierWinsOverTheSnapshot(): void {
		$first = new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1);
		$sequence = new TaskSequence(
			uuid: 'seq-1',
			anchorObjectUuid: 'doc-1',
			templateSnapshot: ['positions' => [['order' => 1], ['order' => 3]]],
			resolvedTier: ['positions' => [['order' => 1], ['order' => 2]]]
		);

		$this->assertSame([$first], $this->declaredReader(tasks: [$first], sequence: $sequence)->earlierPositions(step: $this->step(position: 2)));
	}//end testTheResolvedTierWinsOverTheSnapshot()

	/**
	 * A non-list entry between declared positions fails closed rather than being skipped.
	 *
	 * @return void
	 */
	public function testANonListEntryIsNotSkipped(): void {
		$first = new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1);
		$sequence = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => [['order' => 1], 'two', ['order' => 3]]]);

		$this->assertNull($this->declaredReader(tasks: [$first], sequence: $sequence)->earlierPositions(step: $this->step(position: 3)));
	}//end testANonListEntryIsNotSkipped()

	/**
	 * A sequence task without a position, or unreadable tasks, fail closed.
	 *
	 * @return void
	 */
	public function testAPositionlessTaskOrUnreadableTasksFailClosed(): void {
		$sequence = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => [['order' => 1], ['order' => 2]]]);
		$first = new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1);
		$loose = new Task(uuid: 'task-x', objectUuid: 'doc-1', sequencePosition: null);

		$this->assertNull($this->declaredReader(tasks: [$first, $loose], sequence: $sequence)->earlierPositions(step: $this->step(position: 2)));

		$this->assertNull($this->declaredReader(tasks: null, sequence: $sequence)->earlierPositions(step: $this->step(position: 1)));
	}//end testAPositionlessTaskOrUnreadableTasksFailClosed()

	/**
	 * Two tasks at one earlier position, or an earlier task on another document, fail closed.
	 *
	 * @return void
	 */
	public function testADoubledPositionOrAnotherDocumentFailsClosed(): void {
		$sequence = new TaskSequence(uuid: 'seq-1', anchorObjectUuid: 'doc-1', templateSnapshot: ['positions' => [['order' => 1], ['order' => 2]]]);
		$first = new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1);
		$extra = new Task(uuid: 'task-x', objectUuid: 'doc-1', sequencePosition: 1);
		$other = new Task(uuid: 'task-y', objectUuid: 'doc-2', sequencePosition: 1);

		$this->assertNull($this->declaredReader(tasks: [$first, $extra], sequence: $sequence)->earlierPositions(step: $this->step(position: 2)));
		$this->assertNull($this->declaredReader(tasks: [$first, $other], sequence: $sequence)->earlierPositions(step: $this->step(position: 2)));
	}//end testADoubledPositionOrAnotherDocumentFailsClosed()

	/**
	 * A sequence object without the expected getters proves nothing.
	 *
	 * @return void
	 */
	public function testASequenceWithoutGettersFailsClosed(): void {
		$first = new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1);

		$this->assertNull($this->declaredReader(tasks: [$first], sequence: new stdClass())->earlierPositions(step: $this->step(position: 2)));
	}//end testASequenceWithoutGettersFailsClosed()

	/**
	 * A sequence mapper failure fails closed and logs the sequence id only.
	 *
	 * @return void
	 */
	public function testASequenceReadFailureFailsClosedAndWarns(): void {
		$tasks = $this->mapper(result: [new Task(uuid: 'task-1', objectUuid: 'doc-1', sequencePosition: 1)]);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static function (string $id) use ($tasks): object {
			if ($id === 'OCA\OpenRegister\Db\TaskMapper') {
				return $tasks;
			}

			throw new RuntimeException('no sequence mapper');
		});
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning')
			->with('SigningTaskListener: could not read a sequence', ['sequenceUuid' => 'seq-1']);

		$reader = new SignerSequenceTasks(container: $container, logger: $logger);

		$this->assertNull($reader->earlierPositions(step: $this->step(position: 2)));
	}//end testASequenceReadFailureFailsClosedAndWarns()

	/**
	 * A mapper without findBySequence reads as null and warns.
	 *
	 * @return void
	 */
	public function testUnusableMapperReadsAsNullAndWarns(): void {
		foreach ([new stdClass(), 'not an object'] as $mapper) {
			$logger = $this->createMock(LoggerInterface::class);
			$logger->expects($this->once())->method('warning');

			$this->assertNull($this->reader(mapper: $mapper, logger: $logger)->forSequence(sequenceUuid: 'seq-1'));
		}
	}//end testUnusableMapperReadsAsNullAndWarns()

	/**
	 * A non-array answer reads as null.
	 *
	 * @return void
	 */
	public function testNonArrayAnswerReadsAsNull(): void {
		$this->assertNull($this->reader(mapper: $this->mapper(result: null))->forSequence(sequenceUuid: 'seq-1'));
	}//end testNonArrayAnswerReadsAsNull()

	/**
	 * A container or mapper failure reads as null and logs the sequence id only.
	 *
	 * @return void
	 */
	public function testFailureReadsAsNullAndWarns(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('no openregister'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning')
			->with($this->isType('string'), ['sequenceUuid' => 'seq-1']);

		$reader = new SignerSequenceTasks(container: $container, logger: $logger);

		$this->assertNull($reader->forSequence(sequenceUuid: 'seq-1'));
	}//end testFailureReadsAsNullAndWarns()
}//end class
