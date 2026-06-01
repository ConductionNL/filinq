<?php

/**
 * Approval Step Approved Event
 *
 * Fired by SigningService when an OR ApprovalStep is approved and the next step
 * becomes pending. Mirrors the contract of
 * OCA\OpenRegister\Event\ApprovalStepApprovedEvent that will be shipped in
 * openregister/openspec/changes/add-approval-step-events. Replace this class
 * with the OR version once that change lands.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;

/**
 * Event fired when an ApprovalStep is approved and the next step becomes pending.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
 */
class ApprovalStepApprovedEvent extends Event
{
    /**
     * Constructor.
     *
     * @param ApprovalChain $chain        The parent chain
     * @param ApprovalStep  $approvedStep The step that was just approved
     * @param ApprovalStep  $nextStep     The next step now in pending status
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function __construct(
        private readonly ApprovalChain $chain,
        private readonly ApprovalStep $approvedStep,
        private readonly ApprovalStep $nextStep
    ) {
        parent::__construct();

    }//end __construct()

    /**
     * Get the approval chain.
     *
     * @return ApprovalChain
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function getChain(): ApprovalChain
    {
        return $this->chain;

    }//end getChain()

    /**
     * Get the step that was approved.
     *
     * @return ApprovalStep
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function getApprovedStep(): ApprovalStep
    {
        return $this->approvedStep;

    }//end getApprovedStep()

    /**
     * Get the next step now in pending status.
     *
     * @return ApprovalStep
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function getNextStep(): ApprovalStep
    {
        return $this->nextStep;

    }//end getNextStep()
}//end class
