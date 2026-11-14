<?php

/**
 * Signer Sequence Tasks
 *
 * Reads every position's task of an OR task sequence through OR's task
 * mapper, resolved by FQN string at call time so this class loads with OR
 * absent.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
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

namespace OCA\Filinq\EventListener;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The tasks of one task sequence, or null when they cannot be read.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerSequenceTasks {

	/**
	 * OR's task mapper, resolved by name so no OR type is imported.
	 *
	 * @var string
	 */
	private const TASK_MAPPER_CLASS = 'OCA\OpenRegister\Db\TaskMapper';

	/**
	 * OR's task sequence mapper, resolved by name so no OR type is imported.
	 *
	 * @var string
	 */
	private const SEQUENCE_MAPPER_CLASS = 'OCA\OpenRegister\Db\TaskSequenceMapper';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves OR's task mapper.
	 * @param LoggerInterface    $logger    Logger.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Every task of a sequence, in position order.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 *
	 * @return array<int, object>|null The tasks, or null when the mapper is
	 *                                 unavailable or the read fails.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function forSequence(string $sequenceUuid): ?array {
		try {
			$tasks = $this->container->get(self::TASK_MAPPER_CLASS)->findBySequence($sequenceUuid);
		} catch (Throwable) {
			$this->logger->warning(
				'SigningTaskListener: could not read the tasks of a sequence',
				['sequenceUuid' => $sequenceUuid]
			);
			return null;
		}

		if (is_array($tasks) === false) {
			return null;
		}

		return array_values(array_filter($tasks, 'is_object'));
	}//end forSequence()

	/**
	 * The task at every declared position before a step, one per position.
	 *
	 * The positions come from the sequence's frozen declaration (its resolved
	 * tier, else its template snapshot), not from the tasks that happen to
	 * exist. Every task of the sequence below the step must sit at a declared
	 * position, belong to the step's document, and be the only task there.
	 * An empty list means the step is the first declared position.
	 *
	 * @param SignerStepTask $step The enabled step.
	 *
	 * @return array<int, object>|null The earlier tasks, or null when the
	 *                                 sequence, its declaration or an earlier
	 *                                 position cannot be proven.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function earlierPositions(SignerStepTask $step): ?array {
		$declared = $this->declaredOrdersBefore(step: $step);
		$tasks = $this->forSequence(sequenceUuid: $step->sequenceUuid);
		if ($declared === null || $tasks === null) {
			return null;
		}

		$earlier = $this->tasksBelow(tasks: $tasks, step: $step);
		if ($this->coversDeclared(earlier: $earlier, declared: $declared) === false) {
			return null;
		}

		return $this->onePerPosition(earlier: $earlier);
	}//end earlierPositions()

	/**
	 * Whether the grouped earlier tasks sit at exactly the declared earlier positions.
	 *
	 * @param array<int, array<int, object>>|null $earlier  The grouped earlier tasks, null when unproven.
	 * @param array<int, int>                     $declared The declared earlier positions, sorted.
	 *
	 * @return bool True when every declared position, and no other, holds tasks.
	 */
	private function coversDeclared(?array $earlier, array $declared): bool {
		return $earlier !== null && array_keys($earlier) === $declared;

	}//end coversDeclared()

	/**
	 * The one task at each earlier position, or null when a position holds more than one.
	 *
	 * @param array<int, array<int, object>> $earlier The grouped earlier tasks.
	 *
	 * @return array<int, object>|null The tasks in position order.
	 */
	private function onePerPosition(array $earlier): ?array {
		$single = [];
		foreach ($earlier as $atPosition) {
			if (count($atPosition) !== 1) {
				return null;
			}

			$single[] = $atPosition[0];
		}

		return $single;

	}//end onePerPosition()

	/**
	 * The step's sequence tasks below its position, grouped and sorted by position.
	 *
	 * @param array<int, object> $tasks The sequence's tasks.
	 * @param SignerStepTask     $step  The enabled step.
	 *
	 * @return array<int, array<int, object>>|null The grouped tasks, or null
	 *                                             when one has no position or
	 *                                             belongs to another document.
	 */
	private function tasksBelow(array $tasks, SignerStepTask $step): ?array {
		if ($this->allPositioned(tasks: $tasks) === false) {
			return null;
		}

		$below = $this->groupBelow(tasks: $tasks, step: $step);
		if ($this->allOnDocument(below: $below, objectUuid: $step->objectUuid) === false) {
			return null;
		}

		return $below;
	}//end tasksBelow()

	/**
	 * Whether every task of the sequence has a numeric position.
	 *
	 * @param array<int, object> $tasks The sequence's tasks.
	 *
	 * @return bool
	 */
	private function allPositioned(array $tasks): bool {
		foreach ($tasks as $task) {
			if (is_numeric($this->value(subject: $task, getter: 'getSequencePosition')) === false) {
				return false;
			}
		}

		return true;

	}//end allPositioned()

	/**
	 * The tasks below the step's position, grouped and sorted by position.
	 *
	 * @param array<int, object> $tasks The sequence's tasks, every one with a numeric position.
	 * @param SignerStepTask     $step  The enabled step.
	 *
	 * @return array<int, array<int, object>> The grouped tasks.
	 */
	private function groupBelow(array $tasks, SignerStepTask $step): array {
		$below = [];
		foreach ($tasks as $task) {
			$position = (int) $this->value(subject: $task, getter: 'getSequencePosition');
			if ($position < $step->position) {
				$below[$position][] = $task;
			}
		}

		ksort($below);

		return $below;

	}//end groupBelow()

	/**
	 * Whether every grouped task belongs to the step's document.
	 *
	 * @param array<int, array<int, object>> $below      The grouped earlier tasks.
	 * @param string                         $objectUuid The step's document.
	 *
	 * @return bool
	 */
	private function allOnDocument(array $below, string $objectUuid): bool {
		foreach (array_merge(...array_values($below)) as $task) {
			if ((string) ($this->value(subject: $task, getter: 'getObjectUuid') ?? '') !== $objectUuid) {
				return false;
			}
		}

		return true;

	}//end allOnDocument()

	/**
	 * The declared positions before a step, sorted, when the step's sequence
	 * is anchored on the step's document and declares the step's position.
	 *
	 * @param SignerStepTask $step The enabled step.
	 *
	 * @return array<int, int>|null The earlier declared positions, or null.
	 */
	private function declaredOrdersBefore(SignerStepTask $step): ?array {
		$sequence = $this->anchoredSequence(step: $step);
		if ($sequence === null) {
			return null;
		}

		$orders = $this->declaredOrders(sequence: $sequence);
		if ($orders === null || in_array($step->position, $orders, true) === false) {
			return null;
		}

		$before = array_values(array_unique(array_filter($orders, static fn (int $order): bool => $order < $step->position)));
		sort($before);

		return $before;
	}//end declaredOrdersBefore()

	/**
	 * The step's sequence, when it is anchored on the step's document.
	 *
	 * @param SignerStepTask $step The enabled step.
	 *
	 * @return object|null The sequence, or null when unreadable or anchored elsewhere.
	 */
	private function anchoredSequence(SignerStepTask $step): ?object {
		$sequence = $this->sequence(sequenceUuid: $step->sequenceUuid);
		if ($sequence === null || (string) ($this->value(subject: $sequence, getter: 'getAnchorObjectUuid') ?? '') !== $step->objectUuid) {
			return null;
		}

		return $sequence;

	}//end anchoredSequence()

	/**
	 * The position ordinals a sequence declares, the way OpenRegister numbers
	 * them: an entry's `order`, else its place in the list.
	 *
	 * @param object $sequence The OR task sequence.
	 *
	 * @return array<int, int>|null The ordinals, or null when no usable declaration exists.
	 */
	private function declaredOrders(object $sequence): ?array {
		$positions = $this->declaredPositions(sequence: $sequence);
		if (is_array($positions) === false) {
			return null;
		}

		return $this->ordinalsOf(positions: $positions);
	}//end declaredOrders()

	/**
	 * The `positions` of the sequence's resolved tier, else of its template snapshot.
	 *
	 * @param object $sequence The OR task sequence.
	 *
	 * @return array<int, mixed>|null The positions, or null when neither declares any.
	 */
	private function declaredPositions(object $sequence): ?array {
		$positions = $this->positionsOf(declaration: $this->value(subject: $sequence, getter: 'getResolvedTier'));
		if ($positions === null) {
			$positions = $this->positionsOf(declaration: $this->value(subject: $sequence, getter: 'getTemplateSnapshot'));
		}

		return $positions;

	}//end declaredPositions()

	/**
	 * The ordinal of every declared position: its `order`, else its place in the list.
	 *
	 * @param array<int, mixed> $positions The declared positions.
	 *
	 * @return array<int, int>|null The ordinals, or null when an entry is not a list.
	 */
	private function ordinalsOf(array $positions): ?array {
		$orders = [];
		$index = 0;
		foreach ($positions as $entry) {
			$index++;
			if (is_array($entry) === false) {
				return null;
			}

			$orders[] = (int) ($entry['order'] ?? $index);
		}

		return $orders;

	}//end ordinalsOf()

	/**
	 * The `positions` list of a declaration, or null when it has none.
	 *
	 * @param mixed $declaration A resolved tier or a template snapshot.
	 *
	 * @return array<int, mixed>|null The positions.
	 */
	private function positionsOf(mixed $declaration): ?array {
		if (is_array($declaration) === false || is_array(($declaration['positions'] ?? null)) === false) {
			return null;
		}

		return $declaration['positions'];
	}//end positionsOf()

	/**
	 * A sequence by uuid, or null when it cannot be read.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 *
	 * @return object|null The sequence.
	 */
	private function sequence(string $sequenceUuid): ?object {
		try {
			$sequence = $this->container->get(self::SEQUENCE_MAPPER_CLASS)->findByUuid(uuid: $sequenceUuid);
		} catch (Throwable) {
			$this->logger->warning(
				'SigningTaskListener: could not read a sequence',
				['sequenceUuid' => $sequenceUuid]
			);
			return null;
		}

		if (is_object($sequence) === false) {
			return null;
		}

		return $sequence;
	}//end sequence()

	/**
	 * One getter's value off a cross-app object, or null when it is not callable.
	 *
	 * @param object $subject The OR entity.
	 * @param string $getter  The getter name.
	 *
	 * @return mixed The value.
	 */
	private function value(object $subject, string $getter): mixed {
		if (is_callable([$subject, $getter]) === false) {
			return null;
		}

		return $subject->$getter();
	}//end value()
}//end class
