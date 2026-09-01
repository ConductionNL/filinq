<?php

/**
 * SignerChainCompletedEvent
 *
 * Typed filinq-side event fired when an OR task sequence belonging to a
 * filinq signing-request completes: the final position completed with an
 * approving outcome. Bridges OR's `TaskSequenceCompletedEvent`, which is
 * dispatched at exactly that moment. Internal filinq subscribers
 * (notifications, artifact production, UI state) react here.
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
 * Fired when the task sequence of a filinq sign-request completes.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerChainCompletedEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param string $sequenceUuid UUID of the completed OR task sequence.
	 * @param string $finalTaskUuid UUID of the final position's task.
	 * @param string|null $userId The identity that decided the final position.
	 * @param string $statusOnApprove The approving status the frozen
	 *                                declaration resolves to.
	 * @param string $objectUuid UUID of the filinq signing request.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function __construct(
		private readonly string $sequenceUuid,
		private readonly string $finalTaskUuid,
		private readonly ?string $userId,
		private readonly string $statusOnApprove,
		private readonly string $objectUuid,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the sequence UUID.
	 *
	 * @return string UUID of the completed OR task sequence.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getSequenceUuid(): string {
		return $this->sequenceUuid;
	}//end getSequenceUuid()

	/**
	 * Get the final task's UUID.
	 *
	 * @return string UUID of the final position's task.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getFinalTaskUuid(): string {
		return $this->finalTaskUuid;
	}//end getFinalTaskUuid()

	/**
	 * Get the deciding identity.
	 *
	 * @return string|null Who decided the final position, when known.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}//end getUserId()

	/**
	 * Get the resolved approving status.
	 *
	 * @return string The `statusOnApprove` the frozen declaration resolves to.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function getStatusOnApprove(): string {
		return $this->statusOnApprove;
	}//end getStatusOnApprove()

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
