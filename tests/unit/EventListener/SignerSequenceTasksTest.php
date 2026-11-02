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
use OCA\OpenRegister\Db\Task;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

/**
 * Reads a sequence's tasks through OR's task mapper, failing closed.
 *
 * @covers \OCA\Filinq\EventListener\SignerSequenceTasks
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
	 * Only the tasks at an earlier position come back; an unreadable sequence gives none.
	 *
	 * @return void
	 */
	public function testBeforeReturnsOnlyEarlierPositions(): void {
		$first  = new Task(uuid: 'task-1', sequencePosition: 1);
		$second = new Task(uuid: 'task-2', sequencePosition: 2);
		$third  = new Task(uuid: 'task-3', sequencePosition: 3);
		$reader = $this->reader(mapper: $this->mapper(result: [$first, $second, $third, new stdClass()]));

		$this->assertSame([$first, $second], $reader->before(sequenceUuid: 'seq-1', position: 3));
		$this->assertSame([], $reader->before(sequenceUuid: 'seq-1', position: 1));
		$this->assertSame([], $this->reader(mapper: $this->mapper(result: null))->before(sequenceUuid: 'seq-1', position: 3));
	}//end testBeforeReturnsOnlyEarlierPositions()

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
