<?php

/**
 * SignerChainCompletedEvent
 *
 * Typed docudesk-side event fired ONCE per signing-request when the final OR
 * approval step is approved — i.e. every signer has signed. Bridges OR's
 * `ApprovalStepCompletedEvent`. Internal docudesk subscribers (notifications,
 * downstream archival, signed-document assembly) react to this event in place
 * of polling the legacy `SigningService::updateRequestStatus()` flag.
 *
 * @category  Event
 * @package   OCA\DocuDesk\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#D1-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;

/**
 * Fired once when the OR approval chain backing a sign-request completes.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SignerChainCompletedEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param ApprovalChain $chain The OR approval chain that completed.
	 * @param ApprovalStep $finalStep The final approved step.
	 * @param string $userId UID of the user who approved the final step.
	 * @param string $objectUuid Signing-request UUID.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $finalStep,
		private readonly string $userId,
		private readonly string $objectUuid,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the completed chain.
	 *
	 * @return ApprovalChain The OR approval chain.
	 */
	public function getChain(): ApprovalChain {
		return $this->chain;
	}//end getChain()

	/**
	 * Get the final approved step.
	 *
	 * @return ApprovalStep The final OR approval step.
	 */
	public function getFinalStep(): ApprovalStep {
		return $this->finalStep;
	}//end getFinalStep()

	/**
	 * Get the UID of the user who approved the final step.
	 *
	 * @return string Nextcloud user ID.
	 */
	public function getUserId(): string {
		return $this->userId;
	}//end getUserId()

	/**
	 * Get the docudesk signing-request UUID this chain backed.
	 *
	 * @return string Signing-request UUID.
	 */
	public function getSigningRequestUuid(): string {
		return $this->objectUuid;
	}//end getSigningRequestUuid()
}//end class
