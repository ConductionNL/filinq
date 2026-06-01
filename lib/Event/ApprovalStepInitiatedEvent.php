<?php

/**
 * Approval Step Initiated Event
 *
 * Fired by SigningService when an OR ApprovalStep first becomes pending (chain
 * initialisation). Mirrors the contract of
 * OCA\OpenRegister\Event\ApprovalStepInitiatedEvent that will be shipped in
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
 * Event fired when the first ApprovalStep becomes pending on chain initialisation.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
 */
class ApprovalStepInitiatedEvent extends Event
{
    /**
     * Constructor.
     *
     * @param ApprovalChain $chain The chain that was initiated
     * @param ApprovalStep  $step  The step now in pending status (order 1)
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function __construct(
        private readonly ApprovalChain $chain,
        private readonly ApprovalStep $step
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
     * Get the pending step.
     *
     * @return ApprovalStep
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function getStep(): ApprovalStep
    {
        return $this->step;

    }//end getStep()
}//end class
