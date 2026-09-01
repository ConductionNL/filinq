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
use OCA\Filinq\EventListener\SigningTaskListener;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\OpenRegister\Db\Task;
use OCA\OpenRegister\Db\TaskSequence;
use OCA\OpenRegister\Event\TaskSequenceCompletedEvent;
use OCA\OpenRegister\Event\TaskTerminalEvent;
use OCA\OpenRegister\Event\TaskTransitionedEvent;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
	 * @var SigningProviderFactory&MockObject
	 */
	private SigningProviderFactory $providerFactory;

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

		$this->providerFactory = $this->createMock(SigningProviderFactory::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->settingsService = $this->createMock(SettingsService::class);
		$this->objectService = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->settingsService->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$this->settingsService->method('getObjectService')
			->willReturn($this->objectService);
		$this->objectService->method('find')->willReturnCallback(
			function (string $id = '', string $register = '', string $schema = ''): ?stdClass {
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
			logger: $this->logger
		);
	}//end makeListener()

	/**
	 * Build a sequence task.
	 *
	 * @param string $state Task state.
	 * @param string|null $outcome Task outcome.
	 * @param string|null $sequenceUuid Sequence uuid (null = plain task).
	 * @param string $objectUuid Anchor object uuid.
	 *
	 * @return Task
	 */
	private function makeTask(
		string $state,
		?string $outcome = null,
		?string $sequenceUuid = 'seq-1',
		string $objectUuid = self::OWNED_OBJECT,
	): Task {
		return new Task(
			uuid: 'task-1',
			state: $state,
			outcome: $outcome,
			candidateGroups: ['filinq-signers'],
			objectUuid: $objectUuid,
			sequenceUuid: $sequenceUuid,
			sequencePosition: 2,
			completedBy: 'alice',
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
		$provider = $this->createMock(SigningProviderInterface::class);
		$provider->method('getIdentifier')->willReturn('native');
		$this->providerFactory->expects($this->once())
			->method('getActiveProvider')
			->willReturn($provider);

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

		$event = new TaskTransitionedEvent(
			task: $this->makeTask(state: 'enabled'),
			previousState: 'available'
		);
		$this->listener->handle($event);

	}//end testEnabledTransitionDispatchesPendingAndInvokesProvider()

	/**
	 * A transition that keeps an enabled task enabled (e.g. reassignment)
	 * is not announced again.
	 *
	 * @return void
	 */
	public function testTransitionOfAlreadyEnabledTaskIsIgnored(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->providerFactory->expects($this->never())->method('getActiveProvider');

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
		$this->providerFactory->expects($this->never())->method('getActiveProvider');

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
				logger: $this->logger
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
}//end class
