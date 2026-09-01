<?php

/**
 * SignerStepApprovedEvent
 *
 * Typed filinq-side event fired when a position of an OR task sequence
 * belonging to a filinq signing-request completes with an approving outcome.
 * Bridges OR's committed `TaskTerminalEvent` (state `completed`, outcome not
 * in the rejecting vocabulary). The retired `nextStep` payload is gone by
 * design: OR enables the next position in the same request as the approving
 * decision, and that position's own enabled transition arrives as a
 * `SignerStepPendingEvent`.
 *
 * Carries scalars only, on purpose: the payload survives with OpenRegister
 * older, newer or absent, which is what lets filinq load on either side of
 * openregister#3302 (flow-approval-consolidation).
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-tasks/tasks.md#2-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * Fired when a sequence position linked to a filinq sign-request is approved.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerStepApprovedEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 * @param string $taskUuid UUID of the completed task.
	 * @param int $position Ordinal of the position (1-based).
	 * @param string|null $userId The completing identity (`task.completedBy`).
	 * @param string|null $comment The completion comment, when one was given.
	 * @param string $objectUuid UUID of the filinq signing request.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function __construct(
		private readonly string $sequenceUuid,
		private readonly string $taskUuid,
		private readonly int $position,
		private readonly ?string $userId,
		private readonly ?string $comment,
		private readonly string $objectUuid,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the sequence UUID.
	 *
	 * @return string UUID of the OR task sequence.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getSequenceUuid(): string {
		return $this->sequenceUuid;
	}//end getSequenceUuid()

	/**
	 * Get the completed task's UUID.
	 *
	 * @return string UUID of the completed task.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getTaskUuid(): string {
		return $this->taskUuid;
	}//end getTaskUuid()

	/**
	 * Get the position ordinal.
	 *
	 * @return int Ordinal of the position (1-based).
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getPosition(): int {
		return $this->position;
	}//end getPosition()

	/**
	 * Get the completing identity.
	 *
	 * @return string|null Who completed the position, when known.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}//end getUserId()

	/**
	 * Get the completion comment.
	 *
	 * @return string|null The comment, or null when none was given.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getComment(): ?string {
		return $this->comment;
	}//end getComment()

	/**
	 * Get the signing-request object UUID.
	 *
	 * @return string UUID of the filinq signing request.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getObjectUuid(): string {
		return $this->objectUuid;
	}//end getObjectUuid()
}//end class
