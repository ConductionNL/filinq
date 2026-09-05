<?php

/**
 * SigningTaskListener
 *
 * Bridges OpenRegister's task-sequence events into filinq's typed
 * `Signer*Event` family, and triggers the matching
 * `SigningProviderInterface` invocation when a sequence position linked to a
 * filinq signing-request becomes enabled. Replaces the retired
 * `ApprovalStepListener`: openregister#3302 (flow-approval-consolidation)
 * removed the four `ApprovalStep*Event` classes with no alias, and this
 * listener consumes their published replacements — a committed
 * `TaskTransitionedEvent` to `enabled` (position pending), a committed
 * `TaskTerminalEvent` with state `completed` (approved or rejected by
 * outcome), and `TaskSequenceCompletedEvent` (final approval).
 *
 * This is the ONLY file that touches OpenRegister's task surface, and only
 * inside {@see handle()}: events are routed by class-name string and every
 * value is read through {@see read()}, a dynamic getter call that works for
 * real methods and for the NC `Entity::__call` magic getters alike
 * (`method_exists()` answers false for the latter, which is why it is not
 * the guard here — `is_callable()` is). Everything is reduced to scalars
 * before it reaches {@see SignerEventTranslator}, so filinq loads with
 * OpenRegister older, newer or absent.
 *
 * Ownership: an event belongs to filinq when its anchor object
 * (`task.objectUuid`, or `sequence.anchorObjectUuid`) resolves in the
 * configured signingRequest register/schema. When the binding is not
 * configured (fresh install, schema not yet imported), every event is
 * treated as foreign and skipped — the retired listener's exact behaviour.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\SettingsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener for OR task-sequence events relevant to filinq signing requests.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @implements IEventListener<Event>
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SigningTaskListener implements IEventListener {

	/**
	 * FQN of OR's committed task-transition event, as a string on purpose:
	 * a cross-app class name is a runtime lookup, and a literal cannot
	 * accidentally autoload or hard-couple (openregister#3302 mapping).
	 *
	 * @var string
	 */
	public const EVENT_TASK_TRANSITIONED = 'OCA\\OpenRegister\\Event\\TaskTransitionedEvent';

	/**
	 * FQN of OR's terminal-task event.
	 *
	 * @var string
	 */
	public const EVENT_TASK_TERMINAL = 'OCA\\OpenRegister\\Event\\TaskTerminalEvent';

	/**
	 * FQN of OR's sequence-completed event.
	 *
	 * @var string
	 */
	public const EVENT_SEQUENCE_COMPLETED = 'OCA\\OpenRegister\\Event\\TaskSequenceCompletedEvent';

	/**
	 * FQN of OR's task-state vocabulary class.
	 *
	 * @var string
	 */
	private const TASK_STATE_CLASS = 'OCA\\OpenRegister\\Service\\Task\\TaskState';

	/**
	 * The task state meaning "this position is the one a person can act on".
	 *
	 * @var string
	 */
	private const STATE_ENABLED = 'enabled';

	/**
	 * The task state meaning "the work finished with an explicit outcome".
	 *
	 * @var string
	 */
	private const STATE_COMPLETED = 'completed';

	/**
	 * OR's published rejecting-outcome vocabulary, as a fallback when
	 * `TaskState` cannot be resolved. On the live path it always can — OR
	 * just dispatched the event — so the fallback exists for test
	 * environments and defence in depth, mirroring
	 * `TaskState::REJECTING_OUTCOMES` (approval-events-migration.md).
	 *
	 * @var array<int, string>
	 */
	private const REJECTING_OUTCOMES_FALLBACK = ['rejected', 'returned', 'declined', 'denied'];

	/**
	 * Constructor.
	 *
	 * @param SignerEventTranslator $translator Translates the extracted
	 *                                          scalars into filinq signer
	 *                                          events and notifies the provider.
	 * @param SettingsService $settingsService Resolves the signingRequest
	 *                                         binding and the object service
	 *                                         for the ownership check.
	 * @param LoggerInterface $logger Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SignerEventTranslator $translator,
		private readonly SettingsService $settingsService,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Handle an OR task-sequence event.
	 *
	 * @param Event $event The OR-dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function handle(Event $event): void {
		try {
			switch ($event::class) {
				case self::EVENT_TASK_TRANSITIONED:
					$this->handleTransitioned(event: $event);
					return;
				case self::EVENT_TASK_TERMINAL:
					$this->handleTerminal(event: $event);
					return;
				case self::EVENT_SEQUENCE_COMPLETED:
					$this->handleSequenceCompleted(event: $event);
					return;
				default:
					return;
			}
		} catch (Throwable $e) {
			// The task event surface is best-effort for consumers; a listener
			// failure must not break OR's own write-path. Log and move on so
			// other listeners (audit, notifications) still run.
			$this->logger->error(
				'SigningTaskListener failed handling ' . $event::class . ': ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

	}//end handle()

	/**
	 * A committed task transition: a position becoming enabled is a signer's
	 * turn — the replacement for the retired `ApprovalStepInitiatedEvent`
	 * and the retired approved event's `nextStep` branch.
	 *
	 * @param Event $event OR's TaskTransitionedEvent.
	 *
	 * @return void
	 */
	private function handleTransitioned(Event $event): void {
		$task = $this->read(subject: $event, getter: 'getTask');
		if (is_object($task) === false) {
			return;
		}

		if ((string) ($this->read(subject: $task, getter: 'getState') ?? '') !== self::STATE_ENABLED) {
			return;
		}

		// A transition that keeps an already-enabled task enabled (e.g. a
		// reassignment) is not a new turn; announce each position once.
		$previousState = $this->read(subject: $event, getter: 'getPreviousState');
		if ((string) ($previousState ?? '') === self::STATE_ENABLED) {
			return;
		}

		$sequenceUuid = (string) ($this->read(subject: $task, getter: 'getSequenceUuid') ?? '');
		$objectUuid = (string) ($this->read(subject: $task, getter: 'getObjectUuid') ?? '');
		if ($this->isFilinqSequenceTask(sequenceUuid: $sequenceUuid, objectUuid: $objectUuid) === false) {
			return;
		}

		$candidateGroups = $this->read(subject: $task, getter: 'getCandidateGroups');
		$role = null;
		if (is_array($candidateGroups) === true && $candidateGroups !== []) {
			$role = (string) reset($candidateGroups);
		}

		$this->translator->onPositionEnabled(
			sequenceUuid: $sequenceUuid,
			taskUuid: (string) ($this->read(subject: $task, getter: 'getUuid') ?? ''),
			position: (int) ($this->read(subject: $task, getter: 'getSequencePosition') ?? 0),
			role: $role,
			objectUuid: $objectUuid
		);

	}//end handleTransitioned()

	/**
	 * A terminal task: state `completed` on a filinq sequence task is a
	 * signer's decision — the replacement for the retired approved and
	 * rejected events, told apart by the outcome vocabulary. Uncommitted
	 * dispatches (the in-transaction one from TaskMapper) and terminal
	 * states that are not completions (cancel, moot, run termination) are
	 * skipped: the retired surface had no equivalent for those.
	 *
	 * @param Event $event OR's TaskTerminalEvent.
	 *
	 * @return void
	 */
	private function handleTerminal(Event $event): void {
		if ((bool) $this->read(subject: $event, getter: 'isCommitted') === false) {
			return;
		}

		$task = $this->read(subject: $event, getter: 'getTask');
		if (is_object($task) === false) {
			return;
		}

		if ((string) ($this->read(subject: $task, getter: 'getState') ?? '') !== self::STATE_COMPLETED) {
			return;
		}

		$sequenceUuid = (string) ($this->read(subject: $task, getter: 'getSequenceUuid') ?? '');
		$objectUuid = (string) ($this->read(subject: $task, getter: 'getObjectUuid') ?? '');
		if ($this->isFilinqSequenceTask(sequenceUuid: $sequenceUuid, objectUuid: $objectUuid) === false) {
			return;
		}

		$userId = null;
		$completedBy = $this->read(subject: $task, getter: 'getCompletedBy');
		if ($completedBy !== null) {
			$userId = (string) $completedBy;
		}

		$comment = null;
		$rawComment = $this->read(subject: $task, getter: 'getComment');
		if ($rawComment !== null) {
			$comment = (string) $rawComment;
		}

		$this->translator->onTaskDecided(
			sequenceUuid: $sequenceUuid,
			taskUuid: (string) ($this->read(subject: $task, getter: 'getUuid') ?? ''),
			position: (int) ($this->read(subject: $task, getter: 'getSequencePosition') ?? 0),
			userId: $userId,
			comment: $comment,
			objectUuid: $objectUuid,
			isRejecting: $this->isRejectingOutcome(
				outcome: (string) ($this->read(subject: $task, getter: 'getOutcome') ?? '')
			)
		);

	}//end handleTerminal()

	/**
	 * A completed sequence: the final position approved — the replacement
	 * for the retired `ApprovalStepCompletedEvent`, dispatched by OR at
	 * exactly the same moment.
	 *
	 * @param Event $event OR's TaskSequenceCompletedEvent.
	 *
	 * @return void
	 */
	private function handleSequenceCompleted(Event $event): void {
		$sequence = $this->read(subject: $event, getter: 'getSequence');
		$finalTask = $this->read(subject: $event, getter: 'getFinalTask');
		if (is_object($sequence) === false || is_object($finalTask) === false) {
			return;
		}

		$sequenceUuid = (string) ($this->read(subject: $sequence, getter: 'getUuid') ?? '');
		$objectUuid = (string) ($this->read(subject: $sequence, getter: 'getAnchorObjectUuid') ?? '');
		if ($this->isFilinqSequenceTask(sequenceUuid: $sequenceUuid, objectUuid: $objectUuid) === false) {
			return;
		}

		$decider = null;
		$rawDecider = $this->read(subject: $event, getter: 'getDecider');
		if ($rawDecider !== null) {
			$decider = (string) $rawDecider;
		}

		$this->translator->onSequenceCompleted(
			sequenceUuid: $sequenceUuid,
			finalTaskUuid: (string) ($this->read(subject: $finalTask, getter: 'getUuid') ?? ''),
			userId: $decider,
			statusOnApprove: (string) ($this->read(subject: $event, getter: 'getStatusOnApprove') ?? ''),
			objectUuid: $objectUuid
		);

	}//end handleSequenceCompleted()

	/**
	 * Read one getter off a cross-app object, ducking the type system.
	 *
	 * `is_callable()` rather than `method_exists()`, deliberately: OR's
	 * Task entity serves its getters through `Entity::__call`, for which
	 * `method_exists()` answers false while the call works fine. A getter
	 * that is not callable reads as null, and every caller treats null as
	 * "absent", which fails closed.
	 *
	 * @param object $subject The OR event or entity.
	 * @param string $getter The getter name.
	 *
	 * @return mixed The getter's value, or null when not callable.
	 */
	private function read(object $subject, string $getter): mixed {
		if (is_callable([$subject, $getter]) === false) {
			return null;
		}

		return $subject->{$getter}();
	}//end read()

	/**
	 * Decide whether a sequence task (or a sequence) belongs to a filinq
	 * signing-request.
	 *
	 * It does iff BOTH hold: the task is part of a sequence (plain workflow
	 * tasks never reach the object lookup), and its anchor object resolves
	 * in the configured signingRequest register/schema. With the binding
	 * unconfigured every event is foreign — fail closed, exactly as the
	 * retired slug filter did.
	 *
	 * @param string $sequenceUuid The task's sequence uuid ('' when none).
	 * @param string $objectUuid The anchor object uuid ('' when none).
	 *
	 * @return bool True when the event targets a filinq signing-request.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	private function isFilinqSequenceTask(string $sequenceUuid, string $objectUuid): bool {
		if ($sequenceUuid === '' || $objectUuid === '') {
			return false;
		}

		$binding = $this->settingsService->resolveSigningRequestBinding();
		if ($binding === null) {
			return false;
		}

		$objectService = $this->settingsService->getObjectService();
		if ($objectService === null) {
			return false;
		}

		try {
			$object = $objectService->find(
				id: $objectUuid,
				register: $binding['register'],
				schema: $binding['schema']
			);
		} catch (Throwable $e) {
			// A lookup failure cannot prove ownership: fail closed.
			$this->logger->warning(
				'SigningTaskListener: ownership lookup failed for object ' . $objectUuid . ': ' . $e->getMessage()
			);
			return false;
		}

		return $object !== null;
	}//end isFilinqSequenceTask()

	/**
	 * Classify an outcome against OR's rejecting vocabulary.
	 *
	 * Delegates to `TaskState::isRejectingOutcome()` when the class
	 * resolves — on the live path it always does, because OR just
	 * dispatched the event — and otherwise falls back to the published
	 * vocabulary. The `class_exists()` here runs at event time, never at
	 * register() time, so the bootstrap-order invariant holds.
	 *
	 * @param string $outcome The task's outcome.
	 *
	 * @return bool True when the outcome is rejecting.
	 */
	private function isRejectingOutcome(string $outcome): bool {
		$classifier = [self::TASK_STATE_CLASS, 'isRejectingOutcome'];
		if (class_exists('\\' . self::TASK_STATE_CLASS) === true && is_callable($classifier) === true) {
			return (bool) call_user_func($classifier, $outcome);
		}

		return in_array(strtolower(trim($outcome)), self::REJECTING_OUTCOMES_FALLBACK, true);
	}//end isRejectingOutcome()
}//end class
