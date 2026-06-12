<?php

/**
 * SignerStepApprovedEvent
 *
 * Typed docudesk-side event fired when a `pending` OR approval step linked to
 * a docudesk signing-request is approved (i.e. a signer signed). Bridges OR's
 * `ApprovalStepApprovedEvent`; carries the next step (if any) so internal
 * docudesk subscribers can decide whether the chain has advanced or stalled.
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
 * Fired after an approval step linked to a docudesk sign-request is approved.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SignerStepApprovedEvent extends Event
{

    /**
     * Constructor.
     *
     * @param ApprovalChain     $chain      The OR approval chain.
     * @param ApprovalStep      $step       The approved OR approval step.
     * @param string            $userId     UID of the user who approved.
     * @param ApprovalStep|null $nextStep   Next step now pending (null = final).
     * @param string            $objectUuid Signing-request UUID.
     *
     * @return void
     */
    public function __construct(
        private readonly ApprovalChain $chain,
        private readonly ApprovalStep $step,
        private readonly string $userId,
        private readonly ?ApprovalStep $nextStep,
        private readonly string $objectUuid
    ) {
        parent::__construct();

    }//end __construct()

    /**
     * Get the approval chain.
     *
     * @return ApprovalChain The OR approval chain.
     */
    public function getChain(): ApprovalChain
    {
        return $this->chain;

    }//end getChain()

    /**
     * Get the approved step.
     *
     * @return ApprovalStep The OR approval step.
     */
    public function getStep(): ApprovalStep
    {
        return $this->step;

    }//end getStep()

    /**
     * Get the UID of the user who approved this step.
     *
     * @return string Nextcloud user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;

    }//end getUserId()

    /**
     * Get the next step now pending, or null when this was the final step.
     *
     * @return ApprovalStep|null Next pending step, or null.
     */
    public function getNextStep(): ?ApprovalStep
    {
        return $this->nextStep;

    }//end getNextStep()

    /**
     * Convenience: is this the final step?
     *
     * @return bool True when no next step is pending.
     */
    public function isFinalStep(): bool
    {
        return $this->nextStep === null;

    }//end isFinalStep()

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
