<?php

/**
 * SignerStepPendingEvent
 *
 * Typed docudesk-side event fired whenever an OR ApprovalStep becomes `pending`
 * for a docudesk signing-request — either the first step (chain initiated) or a
 * subsequent step (previous step approved). Bridges OR's
 * `ApprovalStepInitiatedEvent` and the "next step now pending" branch of
 * `ApprovalStepApprovedEvent` into a single docudesk-shaped event so
 * `SigningProviderInterface` implementations (and any other docudesk
 * subscriber) can react without depending on OR's event surface directly.
 *
 * Per ADR-022 docudesk consumes OR abstractions; this event is the typed
 * docudesk wrapper that internal docudesk components subscribe to in place of
 * the bespoke provider-invocation calls the legacy `SigningService` made
 * inline.
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
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#D2-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;

/**
 * Fired when an approval step linked to a docudesk sign-request becomes pending.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SignerStepPendingEvent extends Event
{

    /**
     * Constructor.
     *
     * @param ApprovalChain $chain      The OR approval chain.
     * @param ApprovalStep  $step       The OR approval step now in `pending`.
     * @param string        $objectUuid UUID of the docudesk signing request.
     *
     * @return void
     */
    public function __construct(
        private readonly ApprovalChain $chain,
        private readonly ApprovalStep $step,
        private readonly string $objectUuid
    ) {
        parent::__construct();

    }//end __construct()

    /**
     * Get the approval chain the step belongs to.
     *
     * @return ApprovalChain The OR approval chain.
     */
    public function getChain(): ApprovalChain
    {
        return $this->chain;

    }//end getChain()

    /**
     * Get the now-pending approval step.
     *
     * @return ApprovalStep The OR approval step.
     */
    public function getStep(): ApprovalStep
    {
        return $this->step;

    }//end getStep()

    /**
     * Get the docudesk signing-request UUID this step relates to.
     *
     * @return string Signing-request UUID.
     */
    public function getSigningRequestUuid(): string
    {
        return $this->objectUuid;

    }//end getSigningRequestUuid()

}//end class
