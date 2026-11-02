<?php

/**
 * Golden: the signer events a completed sequence task turns into
 *
 * Drives SigningTaskListener with the real SignerEventTranslator and records
 * every typed event dispatched, with all its fields, for tasks with every
 * signer, comment, outcome, uuid and position shape, on our object and on
 * objects that are not ours. Recorded on the translator method that took
 * seven parameters.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\SignerEventTranslator;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCA\Filinq\Kernel\Contracts\ActiveSigningProviderInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\OpenRegister\Db\Task;
use OCA\OpenRegister\Event\TaskTerminalEvent;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use stdClass;

/**
 * Golden for completed sequence tasks.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\EventListener\SigningTaskListener
 * @covers \OCA\Filinq\EventListener\SignerEventTranslator
 * @uses   \OCA\Filinq\Event\SignerStepApprovedEvent
 * @uses   \OCA\Filinq\Event\SignerStepRejectedEvent
 * @uses   \OCA\Filinq\EventListener\SignerStepDecision
 * @uses   \OCA\Filinq\EventListener\SignerStepTask
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningTaskDecisionGoldenTest extends TestCase {

	private const GOLDEN = __DIR__ . '/golden/signing-task-decision.json';

	private const OWNED = 'sign-req-1';

	/**
	 * What the current case dispatched and looked up.
	 *
	 * @var array<int, mixed>
	 */
	private array $seen = [];

	/**
	 * Every case dispatches as recorded.
	 *
	 * @return void
	 */
	public function testGolden(): void {
		$actual = [];
		foreach (['approved', 'rejected', ' Rejected ', 'RETURNED', 'declined', 'denied', 'reject', '', null] as $outcome) {
			foreach (['alice', 'admin', '', null] as $signer) {
				foreach (['akkoord', '', null] as $comment) {
					$name          = var_export($outcome, true).' '.var_export($signer, true).' '.var_export($comment, true);
					$actual[$name] = $this->record(
						task: new Task(
							uuid: 'task-1',
							state: 'completed',
							outcome: $outcome,
							objectUuid: self::OWNED,
							sequenceUuid: 'seq-1',
							sequencePosition: 2,
							completedBy: $signer,
							comment: $comment
						)
					);
				}
			}
		}

		$shapes = [
			'no uuid or position' => new Task(state: 'completed', outcome: 'approved', objectUuid: self::OWNED, sequenceUuid: 'seq-1', completedBy: 'bob'),
			'position 0'          => new Task(uuid: 't', state: 'completed', outcome: 'rejected', objectUuid: self::OWNED, sequenceUuid: 's', sequencePosition: 0),
			'position 9'          => new Task(uuid: 't', state: 'completed', outcome: 'approved', objectUuid: self::OWNED, sequenceUuid: 's', sequencePosition: 9),
			'not ours'            => new Task(uuid: 't', state: 'completed', outcome: 'approved', objectUuid: 'other', sequenceUuid: 's', sequencePosition: 1),
			'no sequence'         => new Task(uuid: 't', state: 'completed', outcome: 'approved', objectUuid: self::OWNED, sequencePosition: 1),
			'no object'           => new Task(uuid: 't', state: 'completed', outcome: 'approved', sequenceUuid: 's', sequencePosition: 1),
			'terminated'          => new Task(uuid: 't', state: 'terminated', outcome: 'rejected', objectUuid: self::OWNED, sequenceUuid: 's'),
		];
		foreach ($shapes as $name => $task) {
			$actual[$name] = $this->record(task: $task);
		}

		$actual['uncommitted'] = $this->record(task: $shapes['position 9'], committed: false);

		if (getenv('GOLDEN_RECORD') === '1') {
			file_put_contents(self::GOLDEN, json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
		}

		$recorded = json_decode((string) file_get_contents(self::GOLDEN), true);
		$this->assertSame($recorded, json_decode((string) json_encode($actual), true));

	}//end testGolden()

	/**
	 * Dispatch one terminal event through the listener and the real translator.
	 *
	 * @param Task $task      The task.
	 * @param bool $committed Whether the transition committed.
	 *
	 * @return array<int, mixed> The lookups and the events, in order.
	 */
	private function record(Task $task, bool $committed = true): array {
		$this->seen = [];
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(function (object $event): void {
			$fields = [];
			foreach (['getSequenceUuid', 'getTaskUuid', 'getPosition', 'getUserId', 'getComment', 'getObjectUuid'] as $getter) {
				$fields[$getter] = $event->$getter();
			}

			$this->seen[] = ['event' => get_class($event), 'fields' => $fields];
		});
		$translator = new SignerEventTranslator(
			providerFactory: $this->createMock(ActiveSigningProviderInterface::class),
			dispatcher: $dispatcher,
			logger: $this->createMock(LoggerInterface::class),
		);

		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(function (mixed ...$args): stdClass {
			$named = [];
			foreach ((new ReflectionMethod(ObjectService::class, 'find'))->getParameters() as $index => $param) {
				if (array_key_exists($index, $args) === true) {
					$named[$param->getName()] = $args[$index];
				}
			}

			$this->seen[] = ['find' => array_intersect_key($named, ['id' => 1, 'register' => 1, 'schema' => 1, '_rbac' => 1])];
			if (($named['id'] ?? null) !== self::OWNED) {
				throw new DoesNotExistException('Object not found');
			}

			return new stdClass();
		});
		$settings = $this->createMock(SettingsServiceInterface::class);
		$settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settings->method('getObjectService')->willReturn($objects);

		$listener = new SigningTaskListener(translator: $translator, settingsService: $settings, logger: $this->createMock(LoggerInterface::class));
		$listener->handle(new TaskTerminalEvent(task: $task, committed: $committed));

		return $this->seen;

	}//end record()
}//end class
