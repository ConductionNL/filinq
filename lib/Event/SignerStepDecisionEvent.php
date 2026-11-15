<?php

/**
 * SignerStepDecisionEvent
 *
 * Shared payload of the filinq-side events fired when a position of an OR
 * task sequence belonging to a filinq signing-request completes with a
 * decision: the sequence, the task, the position, the deciding identity, the
 * decision comment and the signing-request object. The concrete events
 * (`SignerStepApprovedEvent`, `SignerStepRejectedEvent`) name the outcome.
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
 * Payload of a decided sequence position linked to a filinq sign-request.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
abstract class SignerStepDecisionEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 * @param string $taskUuid UUID of the decided task.
	 * @param int $position Ordinal of the position (1-based).
	 * @param string|null $userId The deciding identity (`task.completedBy`).
	 * @param string|null $comment The decision comment, when one was given.
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
	 * Get the decided task's UUID.
	 *
	 * @return string UUID of the decided task.
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
	 * Get the deciding identity.
	 *
	 * @return string|null Who decided the position, when known.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}//end getUserId()

	/**
	 * Get the decision comment.
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
