<?php

/**
 * ApprovalStepListener
 *
 * Bridges OpenRegister `ApprovalStep*Event`s (Initiated, Approved, Rejected,
 * Completed) into docudesk's typed `Signer*Event` family, and triggers the
 * matching `SigningProviderInterface` invocation when a step linked to a
 * docudesk signing-request becomes pending.
 *
 * Per ADR-022 docudesk's signing workflow delegates state to OR's
 * ApprovalService; this listener is the single ingress point for OR's
 * dispatched events. The listener:
 *
 *  1. Filters OR events to those belonging to a docudesk signing-request
 *     (by `register_slug` / `schema_slug` recorded on the chain).
 *  2. Re-dispatches a typed docudesk-shaped event so other docudesk
 *     components (audit, notifications, UI state) can subscribe without
 *     depending on OR's event surface directly.
 *  3. On `pending` (Initiated, Approved with next step), invokes the active
 *     `SigningProviderInterface` for the now-pending step. The provider's
 *     async signing flow is then responsible for calling
 *     `ApprovalService::approveStep` / `rejectStep` back, closing the loop.
 *
 * @category  EventListener
 * @package   OCA\DocuDesk\EventListener
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
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener for OR ApprovalStep events relevant to docudesk signing requests.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @implements IEventListener<Event>
 */
class ApprovalStepListener implements IEventListener
{
    /**
     * Constructor.
     *
     * @param SigningProviderFactory $providerFactory Provider factory for invoking
     *                                                the configured provider on
     *                                                step-pending transitions.
     * @param IEventDispatcher       $dispatcher      Dispatcher used to re-emit
     *                                                typed docudesk-side events.
     * @param IAppConfig             $config          App config (reads the
     *                                                docudesk signing-request
     *                                                register/schema slugs to
     *                                                filter foreign chains).
     * @param LoggerInterface        $logger          Logger.
     *
     * @return void
     */
    public function __construct(
        private readonly SigningProviderFactory $providerFactory,
        private readonly IEventDispatcher $dispatcher,
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Handle an OR ApprovalStep event.
     *
     * @param Event $event The OR-dispatched event.
     *
     * @return void
     */
    public function handle(Event $event): void
    {
        if ($this->isDocudeskChain(event: $event) === false) {
            return;
        }

        try {
            if ($event instanceof ApprovalStepInitiatedEvent) {
                $this->onInitiated(event: $event);
                return;
            }

            if ($event instanceof ApprovalStepApprovedEvent) {
                $this->onApproved(event: $event);
                return;
            }

            if ($event instanceof ApprovalStepRejectedEvent) {
                $this->onRejected(event: $event);
                return;
            }

            if ($event instanceof ApprovalStepCompletedEvent) {
                $this->onCompleted(event: $event);
                return;
            }
        } catch (Throwable $e) {
            // The OR ApprovalService event surface is best-effort; a listener
            // failure must not break OR's own write-path. Log and move on so
            // other listeners (audit, notifications) still run.
            $this->logger->error(
                'ApprovalStepListener failed handling '.get_class($event).': '.$e->getMessage(),
                ['exception' => $e]
            );
        }//end try

    }//end handle()

    /**
     * Decide whether an event belongs to a docudesk signing-request chain.
     *
     * A chain belongs to docudesk iff its `registerSlug` + `schemaSlug` match
     * the docudesk signing-request register + schema configured in app config.
     * When neither slug is configured (fresh install, schema not yet imported),
     * the listener treats the event as foreign and skips it.
     *
     * @param Event $event The OR event.
     *
     * @return bool True when the event targets a docudesk signing-request.
     */
    private function isDocudeskChain(Event $event): bool
    {
        $chain = $this->extractChain(event: $event);
        if ($chain === null) {
            return false;
        }

        $expectedRegister = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $expectedSchema   = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        if ($expectedRegister === '' || $expectedSchema === '') {
            return false;
        }

        $register = (string) ($chain->getRegisterSlug() ?? '');
        $schema   = (string) ($chain->getSchemaSlug() ?? '');

        return $register === $expectedRegister && $schema === $expectedSchema;

    }//end isDocudeskChain()

    /**
     * Extract the ApprovalChain from any of the four OR event types.
     *
     * @param Event $event The OR event.
     *
     * @return \OCA\OpenRegister\Db\ApprovalChain|null The chain, or null.
     */
    private function extractChain(Event $event): ?\OCA\OpenRegister\Db\ApprovalChain
    {
        if ($event instanceof ApprovalStepInitiatedEvent
            || $event instanceof ApprovalStepApprovedEvent
            || $event instanceof ApprovalStepRejectedEvent
            || $event instanceof ApprovalStepCompletedEvent
        ) {
            return $event->getChain();
        }

        return null;

    }//end extractChain()

    /**
     * Handle a step-initiated event (a step has become `pending`).
     *
     * @param ApprovalStepInitiatedEvent $event OR initiated event.
     *
     * @return void
     */
    private function onInitiated(ApprovalStepInitiatedEvent $event): void
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
     */
    private function onApproved(ApprovalStepApprovedEvent $event): void
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
     */
    private function onRejected(ApprovalStepRejectedEvent $event): void
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
     */
    private function onCompleted(ApprovalStepCompletedEvent $event): void
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
