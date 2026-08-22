<?php

/**
 * SignerStepPendingEvent
 *
 * Typed filinq-side event fired whenever an OR ApprovalStep becomes `pending`
 * for a filinq signing-request — either the first step (chain initiated) or a
 * subsequent step (previous step approved). Bridges OR's
 * `ApprovalStepInitiatedEvent` and the "next step now pending" branch of
 * `ApprovalStepApprovedEvent` into a single filinq-shaped event so
 * `SigningProviderInterface` implementations (and any other filinq
 * subscriber) can react without depending on OR's event surface directly.
 *
 * Per ADR-022 filinq consumes OR abstractions; this event is the typed
 * filinq wrapper that internal filinq components subscribe to in place of
 * the bespoke provider-invocation calls the legacy `SigningService` made
 * inline.
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
 * Fired when an approval step linked to a filinq sign-request becomes pending.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SignerStepPendingEvent extends Event {
	/**
	 * Constructor.
	 *
	 * @param ApprovalChain $chain The OR approval chain.
	 * @param ApprovalStep $step The OR approval step now in `pending`.
	 * @param string $objectUuid UUID of the filinq signing request.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $step,
		private readonly string $objectUuid,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the approval chain the step belongs to.
	 *
	 * @return ApprovalChain The OR approval chain.
	 */
	public function getChain(): ApprovalChain {
		return $this->chain;
	}//end getChain()

	/**
	 * Get the now-pending approval step.
	 *
	 * @return ApprovalStep The OR approval step.
	 */
	public function getStep(): ApprovalStep {
		return $this->step;
	}//end getStep()

	/**
	 * Get the filinq signing-request UUID this step relates to.
	 *
	 * @return string Signing-request UUID.
	 */
	public function getSigningRequestUuid(): string {
		return $this->objectUuid;
	}//end getSigningRequestUuid()
}//end class
