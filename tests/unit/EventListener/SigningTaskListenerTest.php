<?php

/**
 * Unit tests for SigningTaskListener
 *
 * Verifies the OR task-sequence → filinq Signer*Event bridge that replaced
 * the retired ApprovalStep bridge (openregister#3302): ownership filtering
 * through the anchored object, the committed/state/sequence pre-filters,
 * typed-event re-emission, provider invocation on position-enabled, and the
 * error-swallowing contract. Every guard in the ownership filter has a test
 * that fails when the comparison is flipped (the manual mutation check for
 * this authorization-relevant filter).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-tasks/tasks.md#4-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\SignerChainCompletedEvent;
use OCA\Filinq\Event\SignerStepApprovedEvent;
use OCA\Filinq\Event\SignerStepPendingEvent;
use OCA\Filinq\Event\SignerStepRejectedEvent;
use OCA\Filinq\EventListener\SignerEventTranslator;
use OCA\Filinq\EventListener\SignerSequenceTasks;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCA\Filinq\Kernel\Contracts\ActiveSigningProviderInterface;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Db\Task;
use OCA\OpenRegister\Db\TaskSequence;
use OCA\OpenRegister\Event\TaskSequenceCompletedEvent;
use OCA\OpenRegister\Event\TaskTerminalEvent;
use OCA\OpenRegister\Event\TaskTransitionedEvent;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

/**
 * Tests for SigningTaskListener.
 *
 * @covers \OCA\Filinq\EventListener\SigningTaskListener
 *
 * @uses \OCA\Filinq\EventListener\SignerEventTranslator
 * @uses \OCA\Filinq\Event\SignerStepPendingEvent
 * @uses \OCA\Filinq\Event\SignerStepApprovedEvent
 * @uses \OCA\Filinq\Event\SignerStepRejectedEvent
 * @uses \OCA\Filinq\Event\SignerChainCompletedEvent
 * @uses \OCA\Filinq\EventListener\SignerStepDecision
 * @uses \OCA\Filinq\EventListener\SignerStepTask
 * @uses \OCA\Filinq\EventListener\SignerApprovals
 * @uses \OCA\Filinq\EventListener\SignerSequenceTasks
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class SigningTaskListenerTest extends TestCase {

	/**
	 * UUID of the owned signing-request object every helper uses.
	 *
	 * @var string
	 */
	private const OWNED_OBJECT = 'sign-req-1';

	/**
	 * Provider factory mock.
	 *
	 * @var ActiveSigningProviderInterface&MockObject
	 */
	private ActiveSigningProviderInterface $providerFactory;

	/**
	 * Dispatcher mock used for re-emitting typed filinq events.
	 *
	 * @var IEventDispatcher&MockObject
	 */
	private IEventDispatcher $dispatcher;

	/**
	 * Settings service mock supplying the binding and the object service.
	 *
	 * @var SettingsService&MockObject
	 */
	private SettingsService $settingsService;

	/**
	 * Object service mock backing the ownership lookup.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objectService;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Reader of a completed sequence's positions.
	 *
	 * @var SignerSequenceTasks&MockObject
	 */
	private SignerSequenceTasks $sequenceTasks;

	/**
	 * Listener under test.
	 *
	 * @var SigningTaskListener
	 */
	private SigningTaskListener $listener;

	/**
	 * Configure mocks: a configured binding and an object store that
	 * resolves exactly the owned signing-request UUID.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->providerFactory = $this->createMock(ActiveSigningProviderInterface::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->settingsService = $this->createMock(SettingsService::class);
		$this->objectService = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->sequenceTasks = $this->getMockBuilder(SignerSequenceTasks::class)
			->setConstructorArgs([$this->sequenceContainer(), $this->logger])
			->onlyMethods(['forSequence'])
			->getMock();

		$this->settingsService->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$this->settingsService->method('getObjectService')
			->willReturn($this->objectService);
		$this->objectService->method('find')->willReturnCallback(
			function (int|string $id = '', ?ObjectFindOptions $options = null, bool $_rbac = true, bool $_multitenancy = true): ?stdClass {
				$register = (string) ($options?->register ?? '');
				$schema = (string) ($options?->schema ?? '');
				if ($id === self::OWNED_OBJECT && $register === 'filinq' && $schema === 'signingRequest') {
					return new stdClass();
				}

				return null;
			}
		);

		$this->listener = $this->makeListener(settingsService: $this->settingsService);

	}//end setUp()

	/**
	 * Build a listener around a specific settings-service mock.
	 *
	 * @param SettingsService&MockObject $settingsService The settings mock.
	 *
	 * @return SigningTaskListener
	 */
	private function makeListener(SettingsService $settingsService): SigningTaskListener {
		return new SigningTaskListener(
			translator: new SignerEventTranslator(
				providerFactory: $this->providerFactory,
				dispatcher: $this->dispatcher,
				logger: $this->logger
			),
			settingsService: $settingsService,
			logger: $this->logger,
			sequenceTasks: $this->sequenceTasks
		);
	}//end makeListener()

	/**
	 * The declared sequence the fake TaskSequenceMapper answers, or null for none.
	 *
	 * @var TaskSequence|null
	 */
	private ?TaskSequence $declaredSequence = null;

	/**
	 * A container whose OR TaskSequenceMapper answers the declared sequence.
	 *
	 * @return ContainerInterface
	 */
	private function sequenceContainer(): ContainerInterface {
		$test = $this;
		$mapper = new class($test) {
			/**
			 * Constructor.
			 *
			 * @param SigningTaskListenerTest $test The test holding the declared sequence.
			 */
			public function __construct(private SigningTaskListenerTest $test) {
			}

			/**
			 * The sequence by uuid.
			 *
			 * @param string $uuid The sequence uuid.
			 *
			 * @return TaskSequence
			 */
			public function findByUuid(string $uuid): TaskSequence {
				$sequence = $this->test->declared();
				if ($sequence === null || $sequence->getUuid() !== $uuid) {
					throw new RuntimeException('no such sequence');
				}

				return $sequence;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($mapper): object {
				if ($id === 'OCA\\OpenRegister\\Db\\TaskSequenceMapper') {
					return $mapper;
				}

				throw new RuntimeException('not in this container: ' . $id);
			}
		);

		return $container;
	}//end sequenceContainer()

	/**
	 * The declared sequence, read by the fake mapper.
	 *
	 * @return TaskSequence|null
	 */
	public function declared(): ?TaskSequence {
		return $this->declaredSequence;
	}//end declared()

	/**
	 * Declare the signing chain's positions on sequence `seq-1`.
	 *
	 * @param array<int, int> $orders The declared position ordinals.
	 * @param string          $anchor The sequence's anchor object.
	 *
	 * @return void
	 */
	private function declareChain(array $orders, string $anchor = self::OWNED_OBJECT): void {
		$positions = [];
		foreach ($orders as $order) {
			$positions[] = ['order' => $order, 'role' => 'filinq-signers'];
		}

		$this->declaredSequence = new TaskSequence(
			uuid: 'seq-1',
			anchorObjectUuid: $anchor,
			templateSnapshot: ['name' => 'sign', 'positions' => $positions]
		);
	}//end declareChain()

	/**
	 * Announce an enabled task and expect no signer to be asked.
	 *
	 * @param Task $enabled The enabled task.
	 *
	 * @return void
	 */
	private function expectNoSignerAsked(Task $enabled): void {
		$this->providerFactory->expects($this->never())->method('getActiveProviderIdentifier');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->listener->handle(new TaskTransitionedEvent(task: $enabled, previousState: 'available'));
	}//end expectNoSignerAsked()

	/**
	 * Build a sequence task.
	 *
	 * @param string $state Task state.
	 * @param string|null $outcome Task outcome.
	 * @param string|null $sequenceUuid Sequence uuid (null = plain task).
	 * @param string $objectUuid Anchor object uuid.
	 * @param string|null $completedBy Completing identity.
	 *
	 * @return Task
	 */
	private function makeTask(
		string $state,
		?string $outcome = null,
		?string $sequenceUuid = 'seq-1',
		string $objectUuid = self::OWNED_OBJECT,
		?string $completedBy = 'alice',
	): Task {
		return new Task(
			uuid: 'task-1',
			state: $state,
			outcome: $outcome,
			candidateGroups: ['filinq-signers'],
			objectUuid: $objectUuid,
			sequenceUuid: $sequenceUuid,
			sequencePosition: 2,
			completedBy: $completedBy,
			comment: 'akkoord'
		);
	}//end makeTask()

	/**
	 * A committed transition to enabled on an owned sequence task dispatches
	 * SignerStepPendingEvent with the extracted scalars and invokes the
	 * active provider.
	 *
	 * @return void
	 */
	public function testEnabledTransitionDispatchesPendingAndInvokesProvider(): void {
		$this->providerFactory->expects($this->once())
			->method('getActiveProviderIdentifier')
			->willReturn('native');

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerStepPendingEvent::class, $emitted);
						$this->assertSame('seq-1', $emitted->getSequenceUuid());
						$this->assertSame('task-1', $emitted->getTaskUuid());
						$this->assertSame(2, $emitted->getPosition());
						$this->assertSame('filinq-signers', $emitted->getRole());
						$this->assertSame(self::OWNED_OBJECT, $emitted->getObjectUuid());

						return true;
					}
				)
			);

		$this->declareChain(orders: [1, 2, 3]);
		$this->sequenceTasks->method('forSequence')->with('seq-1')->willReturn(
			[
				$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'),
				$this->makeTask(state: 'enabled'),
				new Task(uuid: 'task-3', state: 'available', sequenceUuid: 'seq-1', sequencePosition: 3),
			]
		);

		$event = new TaskTransitionedEvent(
			task: $this->makeTask(state: 'enabled'),
			previousState: 'available'
		);
		$this->listener->handle($event);

	}//end testEnabledTransitionDispatchesPendingAndInvokesProvider()

	/**
	 * Earlier positions that are not a signed approval.
	 *
	 * @return array<string, array{0: string|null, 1: string|null}>
	 */
	public static function unsignedEarlierPositions(): array {
		return [
			'done' => ['done', 'alice'],
			'empty outcome' => ['', 'alice'],
			'no outcome' => [null, 'alice'],
			'resolved' => ['resolved', 'alice'],
			'approved without signer' => ['approved', null],
			'approved with blank signer' => ['approved', '  '],
		];
	}//end unsignedEarlierPositions()

	/**
	 * A position enabled after an earlier position that was not a signed
	 * approval is not a signer's turn: no pending event, no provider call.
	 *
	 * @param string|null $outcome     The earlier position's outcome.
	 * @param string|null $completedBy The earlier position's completing user.
	 *
	 * @return void
	 */
	#[DataProvider('unsignedEarlierPositions')]
	public function testEnabledPositionAfterAnUnsignedEarlierPositionInvokesNoProvider(?string $outcome, ?string $completedBy): void {
		$this->declareChain(orders: [1, 2]);
		$this->sequenceTasks->method('forSequence')->with('seq-1')->willReturn(
			[$this->positionTask(position: 1, outcome: $outcome, completedBy: $completedBy), $this->makeTask(state: 'enabled')]
		);
		$this->providerFactory->expects($this->never())->method('getActiveProviderIdentifier');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())->method('warning')->with(
			$this->stringContains('no signer is asked'),
			['sequenceUuid' => 'seq-1', 'taskUuid' => 'task-1', 'objectUuid' => self::OWNED_OBJECT]
		);

		$this->listener->handle(new TaskTransitionedEvent(task: $this->makeTask(state: 'enabled'), previousState: 'available'));

	}//end testEnabledPositionAfterAnUnsignedEarlierPositionInvokesNoProvider()

	/**
	 * Earlier positions that cannot be read, or are missing, fail closed.
	 *
	 * @return array<string, array{0: array<int, Task>|null}>
	 */
	public static function unreadableEarlierPositions(): array {
		return [
			'unreadable' => [null],
			'empty' => [[]],
			'only the enabled position' => [[new Task(uuid: 'task-1', state: 'enabled', sequenceUuid: 'seq-1', sequencePosition: 2)]],
		];
	}//end unreadableEarlierPositions()

	/**
	 * A position whose earlier positions cannot be read is not announced.
	 *
	 * @param array<int, Task>|null $tasks What the reader returns.
	 *
	 * @return void
	 */
	#[DataProvider('unreadableEarlierPositions')]
	public function testUnreadableEarlierPositionsInvokeNoProvider(?array $tasks): void {
		$this->declareChain(orders: [1, 2]);
		$this->sequenceTasks->method('forSequence')->willReturn($tasks);
		$this->providerFactory->expects($this->never())->method('getActiveProviderIdentifier');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->listener->handle(new TaskTransitionedEvent(task: $this->makeTask(state: 'enabled'), previousState: 'available'));

	}//end testUnreadableEarlierPositionsInvokeNoProvider()

	/**
	 * The first position has no earlier signer to wait for.
	 *
	 * @return void
	 */
	public function testTheFirstDeclaredPositionIsAnnounced(): void {
		$this->declareChain(orders: [1, 2]);
		$this->providerFactory->expects($this->once())->method('getActiveProviderIdentifier')->willReturn('native');
		$this->dispatcher->expects($this->once())->method('dispatchTyped')
			->with($this->isInstanceOf(SignerStepPendingEvent::class));

		$first = new Task(
			uuid: 'task-1',
			state: 'enabled',
			candidateGroups: ['filinq-signers'],
			objectUuid: self::OWNED_OBJECT,
			sequenceUuid: 'seq-1',
			sequencePosition: 1
		);
		$this->sequenceTasks->method('forSequence')->willReturn([$first]);
		$this->listener->handle(new TaskTransitionedEvent(task: $first, previousState: 'available'));

	}//end testTheFirstDeclaredPositionIsAnnounced()

	/**
	 * An enabled task without a position is not a signer's turn.
	 *
	 * @return void
	 */
	public function testAPositionlessTaskAsksNoSigner(): void {
		$this->declareChain(orders: [1, 2]);
		$this->sequenceTasks->method('forSequence')->willReturn([]);

		$this->expectNoSignerAsked(
			new Task(uuid: 'task-1', state: 'enabled', objectUuid: self::OWNED_OBJECT, sequenceUuid: 'seq-1', sequencePosition: null)
		);
	}//end testAPositionlessTaskAsksNoSigner()

	/**
	 * A missing position is not read as position 0, even when the chain declares 0.
	 *
	 * @return void
	 */
	public function testAPositionlessTaskIsNotPositionZero(): void {
		$this->declareChain(orders: [0, 1]);
		$loose = new Task(uuid: 'task-1', state: 'enabled', objectUuid: self::OWNED_OBJECT, sequenceUuid: 'seq-1', sequencePosition: null);
		$this->sequenceTasks->method('forSequence')->willReturn([]);

		$this->expectNoSignerAsked($loose);
	}//end testAPositionlessTaskIsNotPositionZero()

	/**
	 * A task at a lower position makes position 1 not the first.
	 *
	 * @return void
	 */
	public function testATaskAtPositionZeroMakesPositionOneWait(): void {
		$this->declareChain(orders: [1, 2]);
		$first = new Task(uuid: 'task-1', state: 'enabled', objectUuid: self::OWNED_OBJECT, sequenceUuid: 'seq-1', sequencePosition: 1);
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 0, outcome: 'done', completedBy: 'mallory'), $first]
		);

		$this->expectNoSignerAsked($first);
	}//end testATaskAtPositionZeroMakesPositionOneWait()

	/**
	 * An earlier approval on another document does not count.
	 *
	 * @return void
	 */
	public function testAnEarlierApprovalOfAnotherObjectAsksNoSigner(): void {
		$this->declareChain(orders: [1, 2]);
		$this->sequenceTasks->method('forSequence')->willReturn(
			[
				new Task(uuid: 'task-x', state: 'completed', outcome: 'approved', objectUuid: 'other-doc', sequenceUuid: 'seq-1', sequencePosition: 1, completedBy: 'alice'),
				$this->makeTask(state: 'enabled'),
			]
		);

		$this->expectNoSignerAsked($this->makeTask(state: 'enabled'));
	}//end testAnEarlierApprovalOfAnotherObjectAsksNoSigner()

	/**
	 * A sequence anchored on another document asks no signer.
	 *
	 * @return void
	 */
	public function testASequenceAnchoredOnAnotherObjectAsksNoSigner(): void {
		$this->declareChain(orders: [1, 2], anchor: 'other-doc');
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'), $this->makeTask(state: 'enabled')]
		);

		$this->expectNoSignerAsked($this->makeTask(state: 'enabled'));
	}//end testASequenceAnchoredOnAnotherObjectAsksNoSigner()

	/**
	 * A declared position without its task fails closed.
	 *
	 * @return void
	 */
	public function testAGapInTheDeclaredPositionsAsksNoSigner(): void {
		$this->declareChain(orders: [1, 2, 3]);
		$third = new Task(uuid: 'task-3', state: 'enabled', objectUuid: self::OWNED_OBJECT, sequenceUuid: 'seq-1', sequencePosition: 3);
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'), $third]
		);

		$this->expectNoSignerAsked($third);
	}//end testAGapInTheDeclaredPositionsAsksNoSigner()

	/**
	 * A reopened task that still carries `approved` is not a signed approval.
	 *
	 * @return void
	 */
	public function testAReopenedApprovedTaskAsksNoSigner(): void {
		$this->declareChain(orders: [1, 2]);
		$this->sequenceTasks->method('forSequence')->willReturn(
			[
				new Task(uuid: 'task-r', state: 'enabled', outcome: 'approved', objectUuid: self::OWNED_OBJECT, sequenceUuid: 'seq-1', sequencePosition: 1, completedBy: 'alice'),
				$this->makeTask(state: 'enabled'),
			]
		);

		$this->expectNoSignerAsked($this->makeTask(state: 'enabled'));
	}//end testAReopenedApprovedTaskAsksNoSigner()

	/**
	 * Two tasks at one earlier position fail closed.
	 *
	 * @return void
	 */
	public function testTwoTasksAtOneEarlierPositionAskNoSigner(): void {
		$this->declareChain(orders: [1, 2]);
		$this->sequenceTasks->method('forSequence')->willReturn(
			[
				$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'),
				$this->positionTask(position: 1, outcome: 'approved', completedBy: 'bob'),
				$this->makeTask(state: 'enabled'),
			]
		);

		$this->expectNoSignerAsked($this->makeTask(state: 'enabled'));
	}//end testTwoTasksAtOneEarlierPositionAskNoSigner()

	/**
	 * A position the chain does not declare asks no signer.
	 *
	 * @return void
	 */
	public function testAnUndeclaredPositionAsksNoSigner(): void {
		$this->declareChain(orders: [1, 3]);
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'), $this->makeTask(state: 'enabled')]
		);

		$this->expectNoSignerAsked($this->makeTask(state: 'enabled'));
	}//end testAnUndeclaredPositionAsksNoSigner()

	/**
	 * A sequence whose declaration cannot be read asks no signer.
	 *
	 * @return void
	 */
	public function testAnUnreadableDeclarationAsksNoSigner(): void {
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'), $this->makeTask(state: 'enabled')]
		);

		$this->expectNoSignerAsked($this->makeTask(state: 'enabled'));
	}//end testAnUnreadableDeclarationAsksNoSigner()

	/**
	 * A resolved amount tier replaces the declared positions.
	 *
	 * @return void
	 */
	public function testTheResolvedTierDecidesThePositions(): void {
		$this->declaredSequence = new TaskSequence(
			uuid: 'seq-1',
			anchorObjectUuid: self::OWNED_OBJECT,
			templateSnapshot: ['positions' => [['order' => 1], ['order' => 2], ['order' => 3]]],
			resolvedTier: ['positions' => [['order' => 1], ['order' => 2]]]
		);
		$this->providerFactory->expects($this->once())->method('getActiveProviderIdentifier')->willReturn('native');
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'), $this->makeTask(state: 'enabled')]
		);

		$this->listener->handle(new TaskTransitionedEvent(task: $this->makeTask(state: 'enabled'), previousState: 'available'));
	}//end testTheResolvedTierDecidesThePositions()

	/**
	 * A transition that keeps an enabled task enabled (e.g. reassignment)
	 * is not announced again.
	 *
	 * @return void
	 */
	public function testTransitionOfAlreadyEnabledTaskIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->providerFactory->expects($this->never())->method('getActiveProviderIdentifier');

		$event = new TaskTransitionedEvent(
			task: $this->makeTask(state: 'enabled'),
			previousState: 'enabled'
		);
		$this->listener->handle($event);

	}//end testTransitionOfAlreadyEnabledTaskIsIgnored()

	/**
	 * Transitions to states other than enabled emit nothing.
	 *
	 * @return void
	 */
	public function testNonEnabledTransitionIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$event = new TaskTransitionedEvent(task: $this->makeTask(state: 'active'));
		$this->listener->handle($event);

	}//end testNonEnabledTransitionIsIgnored()

	/**
	 * A plain workflow task (no sequence uuid) never reaches the object
	 * lookup: sequence membership is the cheap pre-filter.
	 *
	 * @return void
	 */
	public function testTaskWithoutSequenceNeverReachesTheObjectLookup(): void {
		$this->objectService->expects($this->never())->method('find');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$event = new TaskTransitionedEvent(
			task: $this->makeTask(state: 'enabled', sequenceUuid: null),
			previousState: 'available'
		);
		$this->listener->handle($event);

	}//end testTaskWithoutSequenceNeverReachesTheObjectLookup()

	/**
	 * A task with no anchor object never reaches the store either: an empty
	 * id must not be handed to the object lookup.
	 *
	 * @return void
	 */
	public function testTaskWithoutAnchorObjectNeverReachesTheObjectLookup(): void {
		$this->objectService->expects($this->never())->method('find');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$event = new TaskTransitionedEvent(
			task: $this->makeTask(state: 'enabled', objectUuid: ''),
			previousState: 'available'
		);
		$this->listener->handle($event);

	}//end testTaskWithoutAnchorObjectNeverReachesTheObjectLookup()

	/**
	 * A sequence task anchored on a foreign object is ignored.
	 *
	 * @return void
	 */
	public function testForeignAnchorObjectIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->providerFactory->expects($this->never())->method('getActiveProviderIdentifier');

		$event = new TaskTransitionedEvent(
			task: $this->makeTask(state: 'enabled', objectUuid: 'someone-elses-object'),
			previousState: 'available'
		);
		$this->listener->handle($event);

	}//end testForeignAnchorObjectIsIgnored()

	/**
	 * With the binding unconfigured every event is foreign — fail closed,
	 * and the object lookup is never attempted.
	 *
	 * @return void
	 */
	public function testUnconfiguredBindingSkipsEverything(): void {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('resolveSigningRequestBinding')->willReturn(null);
		$settings->expects($this->never())->method('getObjectService');
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$listener = $this->makeListener(settingsService: $settings);
		$listener->handle(
			new TaskTransitionedEvent(
				task: $this->makeTask(state: 'enabled'),
				previousState: 'available'
			)
		);

	}//end testUnconfiguredBindingSkipsEverything()

	/**
	 * With no object service available ownership cannot be proven: skip.
	 *
	 * @return void
	 */
	public function testMissingObjectServiceFailsClosed(): void {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settings->method('getObjectService')->willReturn(null);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		// The skip must be the guard's clean verdict, not a swallowed crash
		// from calling find() on null (the crash path logs a warning, the
		// outer catch an error; a clean skip logs neither).
		$this->logger->expects($this->never())->method('error');
		$this->logger->expects($this->never())->method('warning');

		$listener = $this->makeListener(settingsService: $settings);
		$listener->handle(
			new TaskTransitionedEvent(
				task: $this->makeTask(state: 'enabled'),
				previousState: 'available'
			)
		);

	}//end testMissingObjectServiceFailsClosed()

	/**
	 * An ownership lookup that throws proves nothing: fail closed and log.
	 *
	 * @return void
	 */
	public function testOwnershipLookupFailureFailsClosed(): void {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willThrowException(new RuntimeException('store down'));
		$settings->method('getObjectService')->willReturn($objectService);

		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())->method('warning');

		$listener = $this->makeListener(settingsService: $settings);
		$listener->handle(
			new TaskTransitionedEvent(
				task: $this->makeTask(state: 'enabled'),
				previousState: 'available'
			)
		);

	}//end testOwnershipLookupFailureFailsClosed()

	/**
	 * The in-transaction dispatch (committed=false) is skipped; only the
	 * after-commit dispatch is consumed, per the migration mapping.
	 *
	 * @return void
	 */
	public function testUncommittedTerminalDispatchIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$event = new TaskTerminalEvent(
			task: $this->makeTask(state: 'completed', outcome: 'approved'),
			committed: false
		);
		$this->listener->handle($event);

	}//end testUncommittedTerminalDispatchIsIgnored()

	/**
	 * A committed completion with an approving outcome re-dispatches
	 * SignerStepApprovedEvent carrying the completing identity and comment.
	 *
	 * @return void
	 */
	public function testApprovingCompletionDispatchesApproved(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerStepApprovedEvent::class, $emitted);
						$this->assertSame('seq-1', $emitted->getSequenceUuid());
						$this->assertSame('task-1', $emitted->getTaskUuid());
						$this->assertSame(2, $emitted->getPosition());
						$this->assertSame('alice', $emitted->getUserId());
						$this->assertSame('akkoord', $emitted->getComment());
						$this->assertSame(self::OWNED_OBJECT, $emitted->getObjectUuid());

						return true;
					}
				)
			);

		$event = new TaskTerminalEvent(task: $this->makeTask(state: 'completed', outcome: 'approved'));
		$this->listener->handle($event);

	}//end testApprovingCompletionDispatchesApproved()

	/**
	 * A committed completion with a rejecting outcome re-dispatches
	 * SignerStepRejectedEvent.
	 *
	 * @return void
	 */
	public function testRejectingCompletionDispatchesRejected(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerStepRejectedEvent::class, $emitted);
						$this->assertSame('akkoord', $emitted->getComment());
						$this->assertSame('alice', $emitted->getUserId());

						return true;
					}
				)
			);

		$event = new TaskTerminalEvent(task: $this->makeTask(state: 'completed', outcome: 'rejected'));
		$this->listener->handle($event);

	}//end testRejectingCompletionDispatchesRejected()

	/**
	 * Every entry of the published rejecting vocabulary classifies as a
	 * rejection.
	 *
	 * @return void
	 */
	public function testTheWholeRejectingVocabularyClassifiesAsRejection(): void {
		foreach (['rejected', 'returned', 'declined', 'denied'] as $outcome) {
			$dispatcher = $this->createMock(IEventDispatcher::class);
			$dispatcher->expects($this->once())
				->method('dispatchTyped')
				->with($this->isInstanceOf(SignerStepRejectedEvent::class));

			$listener = new SigningTaskListener(
				translator: new SignerEventTranslator(
					providerFactory: $this->providerFactory,
					dispatcher: $dispatcher,
					logger: $this->logger
				),
				settingsService: $this->settingsService,
				logger: $this->logger,
				sequenceTasks: $this->sequenceTasks
			);

			$listener->handle(
				new TaskTerminalEvent(task: $this->makeTask(state: 'completed', outcome: $outcome))
			);
		}

	}//end testTheWholeRejectingVocabularyClassifiesAsRejection()

	/**
	 * Terminal states that are not completions (cancel, moot, run
	 * termination) emit nothing: the retired surface had no equivalent.
	 *
	 * @return void
	 */
	public function testTerminatedTaskEmitsNothing(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$event = new TaskTerminalEvent(task: $this->makeTask(state: 'terminated', outcome: 'cancelled'));
		$this->listener->handle($event);

	}//end testTerminatedTaskEmitsNothing()

	/**
	 * A completed sequence anchored on an owned object re-dispatches
	 * SignerChainCompletedEvent with decider and resolved status.
	 *
	 * @return void
	 */
	public function testSequenceCompletionDispatchesChainCompleted(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerChainCompletedEvent::class, $emitted);
						$this->assertSame('seq-1', $emitted->getSequenceUuid());
						$this->assertSame('task-1', $emitted->getFinalTaskUuid());
						$this->assertSame('bob', $emitted->getUserId());
						$this->assertSame('signed', $emitted->getStatusOnApprove());
						$this->assertSame(self::OWNED_OBJECT, $emitted->getObjectUuid());

						return true;
					}
				)
			);

		$this->sequenceTasks->method('forSequence')->with('seq-1')
			->willReturn([$this->makeTask(state: 'completed', outcome: 'approved')]);

		$event = new TaskSequenceCompletedEvent(
			sequence: new TaskSequence(uuid: 'seq-1', anchorObjectUuid: self::OWNED_OBJECT),
			finalTask: $this->makeTask(state: 'completed', outcome: 'approved'),
			decider: 'bob',
			statusOnApprove: 'signed'
		);
		$this->listener->handle($event);

	}//end testSequenceCompletionDispatchesChainCompleted()

	/**
	 * A completed sequence anchored on a foreign object is ignored.
	 *
	 * @return void
	 */
	public function testForeignSequenceCompletionIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$event = new TaskSequenceCompletedEvent(
			sequence: new TaskSequence(uuid: 'seq-9', anchorObjectUuid: 'foreign-object'),
			finalTask: $this->makeTask(state: 'completed', outcome: 'approved'),
			decider: 'bob',
			statusOnApprove: 'signed'
		);
		$this->listener->handle($event);

	}//end testForeignSequenceCompletionIsIgnored()

	/**
	 * Events outside the three task events are ignored without touching any
	 * collaborator.
	 *
	 * @return void
	 */
	public function testUnrelatedEventIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->objectService->expects($this->never())->method('find');

		$this->listener->handle(new class extends Event {
		});

	}//end testUnrelatedEventIsIgnored()

	/**
	 * A failing downstream dispatch is logged, never rethrown: the listener
	 * must not break OR's own write path.
	 *
	 * @return void
	 */
	public function testListenerFailureIsLoggedNotThrown(): void {
		$this->dispatcher->method('dispatchTyped')
			->willThrowException(new RuntimeException('downstream broke'));
		$this->logger->expects($this->once())->method('error');

		$event = new TaskTerminalEvent(task: $this->makeTask(state: 'completed', outcome: 'approved'));
		$this->listener->handle($event);

		// Reaching this line is the assertion: handle() swallowed the failure.
		$this->assertTrue(true);

	}//end testListenerFailureIsLoggedNotThrown()

	/**
	 * Completions that are neither approving nor rejecting.
	 *
	 * @return array<string, array{0: string|null}>
	 */
	public static function undecidedOutcomes(): array {
		return [
			'null' => [null],
			'empty' => [''],
			'blank' => ['  '],
			'unknown' => ['reject'],
			'generic completion' => ['done'],
		];
	}//end undecidedOutcomes()

	/**
	 * A completion without an approving outcome dispatches no signer event
	 * and logs a warning carrying only ids.
	 *
	 * @param string|null $outcome The task outcome.
	 *
	 * @return void
	 */
	#[DataProvider('undecidedOutcomes')]
	public function testCompletionWithoutApprovingOutcomeDispatchesNothing(?string $outcome): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				$this->isType('string'),
				['sequenceUuid' => 'seq-1', 'taskUuid' => 'task-1', 'objectUuid' => self::OWNED_OBJECT]
			);

		$this->listener->handle(
			new TaskTerminalEvent(task: $this->makeTask(state: 'completed', outcome: $outcome))
		);

	}//end testCompletionWithoutApprovingOutcomeDispatchesNothing()

	/**
	 * Missing completing identities.
	 *
	 * @return array<string, array{0: string|null}>
	 */
	public static function missingSigners(): array {
		return [
			'null' => [null],
			'empty' => [''],
			'blank' => ['  '],
		];
	}//end missingSigners()

	/**
	 * An approving completion without a completing identity dispatches no
	 * signer event and logs a warning.
	 *
	 * @param string|null $completedBy The completing identity.
	 *
	 * @return void
	 */
	#[DataProvider('missingSigners')]
	public function testApprovingCompletionWithoutSignerDispatchesNothing(?string $completedBy): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())->method('warning');

		$this->listener->handle(
			new TaskTerminalEvent(
				task: $this->makeTask(state: 'completed', outcome: 'approved', completedBy: $completedBy)
			)
		);

	}//end testApprovingCompletionWithoutSignerDispatchesNothing()

	/**
	 * Spellings of the approving outcome.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function approvingOutcomes(): array {
		return [
			'canonical' => ['approved'],
			'padded' => [' Approved '],
			'upper' => ['APPROVED'],
		];
	}//end approvingOutcomes()

	/**
	 * Every spelling of the approving outcome with a signer approves.
	 *
	 * @param string $outcome The task outcome.
	 *
	 * @return void
	 */
	#[DataProvider('approvingOutcomes')]
	public function testApprovingOutcomeWithSignerApproves(string $outcome): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(SignerStepApprovedEvent::class));
		$this->logger->expects($this->never())->method('warning');

		$this->listener->handle(
			new TaskTerminalEvent(task: $this->makeTask(state: 'completed', outcome: $outcome))
		);

	}//end testApprovingOutcomeWithSignerApproves()

	/**
	 * A rejecting outcome rejects with or without a completing identity.
	 *
	 * @param string|null $completedBy The completing identity.
	 *
	 * @return void
	 */
	#[DataProvider('missingSigners')]
	public function testRejectingOutcomeWithoutSignerStillRejects(?string $completedBy): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(SignerStepRejectedEvent::class));

		$this->listener->handle(
			new TaskTerminalEvent(
				task: $this->makeTask(state: 'completed', outcome: 'declined', completedBy: $completedBy)
			)
		);

	}//end testRejectingOutcomeWithoutSignerStillRejects()

	/**
	 * A sequence position's task.
	 *
	 * @param int         $position    The 1-based position.
	 * @param string|null $outcome     Task outcome.
	 * @param string|null $completedBy Completing identity.
	 *
	 * @return Task
	 */
	private function positionTask(int $position, ?string $outcome, ?string $completedBy): Task {
		return new Task(
			uuid: 'task-'.$position,
			state: 'completed',
			outcome: $outcome,
			objectUuid: self::OWNED_OBJECT,
			sequenceUuid: 'seq-1',
			sequencePosition: $position,
			completedBy: $completedBy
		);
	}//end positionTask()

	/**
	 * Hand a completed owned sequence to the listener.
	 *
	 * @param Task $finalTask The final position's task.
	 *
	 * @return void
	 */
	private function completeSequence(Task $finalTask): void {
		$this->listener->handle(
			new TaskSequenceCompletedEvent(
				sequence: new TaskSequence(uuid: 'seq-1', anchorObjectUuid: self::OWNED_OBJECT),
				finalTask: $finalTask,
				decider: $finalTask->getCompletedBy(),
				statusOnApprove: 'signed'
			)
		);
	}//end completeSequence()

	/**
	 * Final completions that are not a signed approval.
	 *
	 * @return array<string, array{0: string|null, 1: string|null}>
	 */
	public static function unsignedFinalCompletions(): array {
		return [
			'done, no signer' => ['done', null],
			'empty, no signer' => ['', null],
			'null, no signer' => [null, null],
			'done, signer' => ['done', 'alice'],
			'approved, blank signer' => ['approved', '  '],
			'approved, empty signer' => ['approved', ''],
		];
	}//end unsignedFinalCompletions()

	/**
	 * A single-signer sequence that OR completed without a signed approval
	 * dispatches no chain event and logs ids only.
	 *
	 * @param string|null $outcome     The final task outcome.
	 * @param string|null $completedBy The final task's completing identity.
	 *
	 * @return void
	 */
	#[DataProvider('unsignedFinalCompletions')]
	public function testSingleSignerSequenceWithoutSignedApprovalDispatchesNothing(?string $outcome, ?string $completedBy): void {
		$final = $this->positionTask(position: 1, outcome: $outcome, completedBy: $completedBy);
		$this->sequenceTasks->method('forSequence')->willReturn([$final]);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				$this->isType('string'),
				['sequenceUuid' => 'seq-1', 'finalTaskUuid' => 'task-1', 'objectUuid' => self::OWNED_OBJECT]
			);

		$this->completeSequence(finalTask: $final);

	}//end testSingleSignerSequenceWithoutSignedApprovalDispatchesNothing()

	/**
	 * An earlier position completed without an approving outcome blocks the
	 * chain event even when the final position approved.
	 *
	 * @return void
	 */
	public function testEarlierUnapprovedPositionBlocksChainCompletion(): void {
		$final = $this->positionTask(position: 2, outcome: 'approved', completedBy: 'bob');
		$this->sequenceTasks->method('forSequence')->with('seq-1')->willReturn(
			[$this->positionTask(position: 1, outcome: '', completedBy: 'alice'), $final]
		);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())->method('warning');

		$this->completeSequence(finalTask: $final);

	}//end testEarlierUnapprovedPositionBlocksChainCompletion()

	/**
	 * An earlier position without a signer blocks the chain event.
	 *
	 * @return void
	 */
	public function testEarlierUnsignedPositionBlocksChainCompletion(): void {
		$final = $this->positionTask(position: 2, outcome: 'approved', completedBy: 'bob');
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: null), $final]
		);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->completeSequence(finalTask: $final);

	}//end testEarlierUnsignedPositionBlocksChainCompletion()

	/**
	 * A final task from the event that disagrees with the read positions
	 * blocks the chain event.
	 *
	 * @return void
	 */
	public function testUnsignedFinalTaskFromTheEventBlocksChainCompletion(): void {
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice')]
		);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->completeSequence(finalTask: $this->positionTask(position: 1, outcome: 'done', completedBy: null));

	}//end testUnsignedFinalTaskFromTheEventBlocksChainCompletion()

	/**
	 * Unreadable or empty positions fail closed.
	 *
	 * @return array<string, array{0: array<int, Task>|null}>
	 */
	public static function unreadablePositions(): array {
		return [
			'unreadable' => [null],
			'empty' => [[]],
		];
	}//end unreadablePositions()

	/**
	 * A sequence whose positions cannot be read dispatches no chain event.
	 *
	 * @param array<int, Task>|null $tasks What the reader returns.
	 *
	 * @return void
	 */
	#[DataProvider('unreadablePositions')]
	public function testUnreadablePositionsBlockChainCompletion(?array $tasks): void {
		$this->sequenceTasks->method('forSequence')->willReturn($tasks);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->once())->method('warning');

		$this->completeSequence(finalTask: $this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'));

	}//end testUnreadablePositionsBlockChainCompletion()

	/**
	 * Two signers who both approved complete the chain.
	 *
	 * @return void
	 */
	public function testEverySignerApprovedCompletesTheChain(): void {
		$final = $this->positionTask(position: 2, outcome: ' Approved ', completedBy: 'bob');
		$this->sequenceTasks->method('forSequence')->willReturn(
			[$this->positionTask(position: 1, outcome: 'approved', completedBy: 'alice'), $final]
		);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(SignerChainCompletedEvent::class));
		$this->logger->expects($this->never())->method('warning');

		$this->completeSequence(finalTask: $final);

	}//end testEverySignerApprovedCompletesTheChain()
}//end class
