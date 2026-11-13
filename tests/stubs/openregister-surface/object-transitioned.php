<?php

/**
 * OpenRegister ObjectTransitionedEvent and its value objects.
 *
 * Mirrors openregister lib/Event/ObjectTransitionedEvent.php,
 * lib/Service/Lifecycle/TransitionMove.php, TransitionActor.php and
 * lib/Kernel/Contracts/RegisterSchemaRef.php: the same constructors and the
 * getters filinq reads. Loaded by tests/stubs/OpenRegisterStubs.php.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\OpenRegister
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Service\Lifecycle;

if (class_exists(TransitionMove::class) === false) {
	/**
	 * Stub for TransitionMove: the action and the states it moves between.
	 */
	final class TransitionMove {
		public function __construct(
			public readonly string $action,
			public readonly string $from,
			public readonly string $to,
		) {
		}
	}
}

if (class_exists(TransitionActor::class) === false) {
	/**
	 * Stub for TransitionActor: who made the move.
	 */
	final class TransitionActor {
		public function __construct(
			public readonly ?string $userId = null,
			public readonly bool $automatic = false,
		) {
		}
	}
}

namespace OCA\OpenRegister\Kernel\Contracts;

if (class_exists(RegisterSchemaRef::class) === false) {
	/**
	 * Stub for RegisterSchemaRef: a register and schema pair.
	 */
	final class RegisterSchemaRef {
		public function __construct(
			public readonly string|int $register,
			public readonly string|int $schema,
		) {
		}
	}
}

namespace OCA\OpenRegister\Event;

use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Kernel\Contracts\RegisterSchemaRef;
use OCA\OpenRegister\Service\Lifecycle\TransitionActor;
use OCA\OpenRegister\Service\Lifecycle\TransitionMove;
use OCP\EventDispatcher\Event;

if (class_exists(ObjectTransitionedEvent::class) === false) {
	/**
	 * Stub for ObjectTransitionedEvent: an object took a lifecycle transition.
	 */
	class ObjectTransitionedEvent extends Event {
		private readonly TransitionActor $actor;

		public function __construct(
			private readonly ObjectEntity $object,
			private readonly TransitionMove $move,
			private readonly RegisterSchemaRef $scope,
			?TransitionActor $actor = null,
		) {
			parent::__construct();
			$this->actor = $actor ?? new TransitionActor();
		}

		public function isAutomatic(): bool {
			return $this->actor->automatic;
		}

		public function getObject(): ObjectEntity {
			return $this->object;
		}

		public function getAction(): string {
			return $this->move->action;
		}

		public function getFrom(): string {
			return $this->move->from;
		}

		public function getTo(): string {
			return $this->move->to;
		}

		public function getUserId(): ?string {
			return $this->actor->userId;
		}

		public function getRegister(): string {
			return (string) $this->scope->register;
		}

		public function getSchema(): string {
			return (string) $this->scope->schema;
		}
	}
}
