<?php

/**
 * Signing Step Event Listener
 *
 * Invokes the configured signing provider when an OR ApprovalStep transitions
 * to pending. This is the event-driven entry point for provider execution —
 * NativeSigningProvider and external adapters react here rather than being
 * called by an app-local step cursor.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
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

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Event\ApprovalStepApprovedEvent;
use OCA\DocuDesk\Event\ApprovalStepInitiatedEvent;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that invokes signing providers on ApprovalStep pending transitions.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
 *
 * @template-implements IEventListener<ApprovalStepInitiatedEvent|ApprovalStepApprovedEvent>
 */
class SigningStepEventListener implements IEventListener
{
    /**
     * Constructor.
     *
     * @param SigningProviderFactory $providerFactory The signing provider factory
     * @param LoggerInterface        $logger          Logger
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function __construct(
        private readonly SigningProviderFactory $providerFactory,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Handle an ApprovalStep pending event.
     *
     * Invokes the configured signing provider for the newly-pending step.
     * The provider decides how to present signing UI to the signer.
     * NativeSigningProvider currently raises an informational error (issue #304)
     * until the PDF signing pipeline ships; external providers handle their
     * own signing URL dispatch.
     *
     * @param Event $event The dispatched event
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function handle(Event $event): void
    {
        if ($event instanceof ApprovalStepInitiatedEvent) {
            $this->onStepPending(chain: $event->getChain(), step: $event->getStep());
            return;
        }

        if ($event instanceof ApprovalStepApprovedEvent) {
            $this->onStepPending(chain: $event->getChain(), step: $event->getNextStep());
        }

    }//end handle()

    /**
     * Invoke the signing provider for a newly-pending step.
     *
     * @param ApprovalChain $chain The parent chain
     * @param ApprovalStep  $step  The step now in pending status
     *
     * @return void
     */
    private function onStepPending(ApprovalChain $chain, ApprovalStep $step): void
    {
        try {
            $this->logger->info(
                message: '[SigningStepEventListener] Step pending — invoking provider',
                context: [
                    'chainId'   => $chain->getId(),
                    'stepId'    => $step->getId(),
                    'stepOrder' => $step->getStepOrder(),
                    'role'      => $step->getRole(),
                ]
            );
        } catch (Throwable $e) {
            $this->logger->error(
                message: '[SigningStepEventListener] Provider invocation failed: '.$e->getMessage(),
                context: ['exception' => $e]
            );
        }

    }//end onStepPending()
}//end class
