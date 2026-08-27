<?php

/**
 * SignerStepRejectedEvent
 *
 * Typed filinq-side event fired when a `pending` OR approval step linked to
 * a filinq signing-request is rejected (i.e. a signer declined). Bridges
 * OR's `ApprovalStepRejectedEvent`. A rejection terminates the chain.
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
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#D2-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;

/**
 * Fired after an approval step linked to a filinq sign-request is rejected.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SignerStepRejectedEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param ApprovalChain $chain The OR approval chain.
	 * @param ApprovalStep $step The rejected OR approval step.
	 * @param string $userId UID of the user who rejected.
	 * @param string $objectUuid Signing-request UUID.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $step,
		private readonly string $userId,
		private readonly string $objectUuid,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the approval chain.
	 *
	 * @return ApprovalChain The OR approval chain.
	 */
	public function getChain(): ApprovalChain {
		return $this->chain;
	}//end getChain()

	/**
	 * Get the rejected step.
	 *
	 * @return ApprovalStep The OR approval step.
	 */
	public function getStep(): ApprovalStep {
		return $this->step;
	}//end getStep()

	/**
	 * Get the UID of the user who rejected this step.
	 *
	 * @return string Nextcloud user ID.
	 */
	public function getUserId(): string {
		return $this->userId;
	}//end getUserId()

	/**
	 * Get the filinq signing-request UUID this step relates to.
	 *
	 * @return string Signing-request UUID.
	 */
	public function getSigningRequestUuid(): string {
		return $this->objectUuid;
	}//end getSigningRequestUuid()
}//end class
