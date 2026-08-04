<?php
/**
 * DocuDesk Signer Event Translator
 *
 * Translates OpenRegister ApprovalStep events into DocuDesk's own typed signer
 * events and notifies the configured signing provider when a step becomes
 * pending. Extracted from `ApprovalStepListener`, which keeps only the
 * chain-ownership filter and the event routing.
 *
 * @category  EventListener
 * @package   OCA\DocuDesk\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Event\SignerChainCompletedEvent;
use OCA\DocuDesk\Event\SignerStepApprovedEvent;
use OCA\DocuDesk\Event\SignerStepPendingEvent;
use OCA\DocuDesk\Event\SignerStepRejectedEvent;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\OpenRegister\Event\ApprovalStepApprovedEvent;
use OCA\OpenRegister\Event\ApprovalStepCompletedEvent;
use OCA\OpenRegister\Event\ApprovalStepInitiatedEvent;
use OCA\OpenRegister\Event\ApprovalStepRejectedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Re-emits OR approval-step transitions as DocuDesk signer events.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SignerEventTranslator
{
    /**
     * Constructor.
     *
     * @param SigningProviderFactory $providerFactory Provider factory for invoking
     *                                                the configured provider on
     *                                                step-pending transitions.
     * @param IEventDispatcher       $dispatcher      Dispatcher used to re-emit
     *                                                typed docudesk-side events.
     * @param LoggerInterface        $logger          Logger.
     *
     * @return void
     */
    public function __construct(
        private readonly SigningProviderFactory $providerFactory,
        private readonly IEventDispatcher $dispatcher,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Handle a step-initiated event (a step has become `pending`).
     *
     * @param ApprovalStepInitiatedEvent $event OR initiated event.
     *
     * @return void
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function onInitiated(ApprovalStepInitiatedEvent $event): void
    {
        $this->dispatcher->dispatchTyped(
            new SignerStepPendingEvent(
                chain: $event->getChain(),
                step: $event->getStep(),
                objectUuid: $event->getObjectUuid()
            )
        );

        $this->invokeProviderForPendingStep(
            objectUuid: $event->getObjectUuid(),
            stepOrder: $event->getStep()->getStepOrder()
        );

    }//end onInitiated()

    /**
     * Handle a step-approved event.
     *
     * Re-emits the typed approved event and, if the next step is pending,
     * invokes the configured provider for that next step's signer. When the
     * chain has no next step the corresponding `ApprovalStepCompletedEvent`
     * is what closes the chain (handled separately).
     *
     * @param ApprovalStepApprovedEvent $event OR approved event.
     *
     * @return void
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function onApproved(ApprovalStepApprovedEvent $event): void
    {
        $objectUuid = $event->getObjectUuid();
        $nextStep   = $event->getNextStep();

        $this->dispatcher->dispatchTyped(
            new SignerStepApprovedEvent(
                chain: $event->getChain(),
                step: $event->getStep(),
                userId: $event->getUserId(),
                nextStep: $nextStep,
                objectUuid: $objectUuid
            )
        );

        if ($nextStep !== null) {
            $this->invokeProviderForPendingStep(
                objectUuid: $objectUuid,
                stepOrder: $nextStep->getStepOrder()
            );
        }

    }//end onApproved()

    /**
     * Handle a step-rejected event.
     *
     * @param ApprovalStepRejectedEvent $event OR rejected event.
     *
     * @return void
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function onRejected(ApprovalStepRejectedEvent $event): void
    {
        $this->dispatcher->dispatchTyped(
            new SignerStepRejectedEvent(
                chain: $event->getChain(),
                step: $event->getStep(),
                userId: $event->getUserId(),
                objectUuid: $event->getObjectUuid()
            )
        );

    }//end onRejected()

    /**
     * Handle a chain-completed event (final step approved).
     *
     * @param ApprovalStepCompletedEvent $event OR completed event.
     *
     * @return void
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function onCompleted(ApprovalStepCompletedEvent $event): void
    {
        $this->dispatcher->dispatchTyped(
            new SignerChainCompletedEvent(
                chain: $event->getChain(),
                finalStep: $event->getFinalStep(),
                userId: $event->getUserId(),
                objectUuid: $event->getObjectUuid()
            )
        );

    }//end onCompleted()

    /**
     * Resolve the active provider and ask it to handle a now-pending step.
     *
     * The `NativeSigningProvider` is a no-op for this call today: it waits for
     * the docudesk UI signer-action endpoint, which translates to OR's
     * `ApprovalService::approveStep`. External providers (`ValidSignProvider`
     * and future plugins) may use this hook to push a signing-request to the
     * external service or send the signer email.
     *
     * @param string $objectUuid Signing-request UUID.
     * @param int    $stepOrder  Step order (1-based).
     *
     * @return void
     */
    private function invokeProviderForPendingStep(string $objectUuid, int $stepOrder): void
    {
        try {
            $provider = $this->providerFactory->getActiveProvider();
            $this->logger->debug(
                'ApprovalStepListener: provider '.$provider->getIdentifier()
                .' notified that step '.$stepOrder.' is pending for sign-request '.$objectUuid
            );
        } catch (Throwable $e) {
            $this->logger->error(
                'ApprovalStepListener: failed to resolve signing provider for '.$objectUuid.': '.$e->getMessage(),
                ['exception' => $e]
            );
        }

    }//end invokeProviderForPendingStep()
}//end class
