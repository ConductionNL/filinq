<?php

/**
 * Unit tests for ApprovalStepListener
 *
 * Verifies the OR ApprovalStep* → docudesk Signer*Event bridge: ownership
 * filtering, typed-event re-emission, and provider invocation on the
 * step-pending transitions.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#D2-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\EventListener;

use OCA\DocuDesk\Event\SignerChainCompletedEvent;
use OCA\DocuDesk\Event\SignerStepApprovedEvent;
use OCA\DocuDesk\Event\SignerStepPendingEvent;
use OCA\DocuDesk\Event\SignerStepRejectedEvent;
use OCA\DocuDesk\EventListener\ApprovalStepListener;
use OCA\DocuDesk\EventListener\SignerEventTranslator;
use OCA\DocuDesk\Service\Signing\NativeSigningProvider;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\DocuDesk\Service\Signing\SigningProviderInterface;
use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCA\OpenRegister\Event\ApprovalStepApprovedEvent;
use OCA\OpenRegister\Event\ApprovalStepCompletedEvent;
use OCA\OpenRegister\Event\ApprovalStepInitiatedEvent;
use OCA\OpenRegister\Event\ApprovalStepRejectedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ApprovalStepListener.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
final class ApprovalStepListenerTest extends TestCase
{

    /**
     * Provider factory mock.
     *
     * @var SigningProviderFactory&MockObject
     */
    private SigningProviderFactory $providerFactory;

    /**
     * Dispatcher mock used for re-emitting typed docudesk events.
     *
     * @var IEventDispatcher&MockObject
     */
    private IEventDispatcher $dispatcher;

    /**
     * App config mock supplying register / schema slugs.
     *
     * @var IAppConfig&MockObject
     */
    private IAppConfig $config;

    /**
     * Logger mock.
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * Listener under test.
     *
     * @var ApprovalStepListener
     */
    private ApprovalStepListener $listener;

    /**
     * Configure mocks for the docudesk signing-request slugs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->providerFactory = $this->createMock(SigningProviderFactory::class);
        $this->dispatcher      = $this->createMock(IEventDispatcher::class);
        $this->config          = $this->createMock(IAppConfig::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->config->method('getValueString')->willReturnMap(
            [
                ['docudesk', 'signingRequest_register', '', 'docudesk'],
                ['docudesk', 'signingRequest_schema', '', 'signingRequest'],
            ]
        );

        $this->listener = new ApprovalStepListener(
            translator: new SignerEventTranslator(
                providerFactory: $this->providerFactory,
                dispatcher: $this->dispatcher,
                logger: $this->logger
            ),
            config: $this->config,
            logger: $this->logger
        );

    }//end setUp()

    /**
     * Build a docudesk-owned ApprovalChain.
     *
     * @return ApprovalChain
     */
    private function makeDocudeskChain(): ApprovalChain
    {
        $chain = new ApprovalChain();
        $chain->setId(101);
        $chain->setUuid('chain-101');
        $chain->setRegisterSlug('docudesk');
        $chain->setSchemaSlug('signingRequest');

        return $chain;

    }//end makeDocudeskChain()

    /**
     * Build a step.
     *
     * @param int    $order      Step order.
     * @param string $objectUuid Object UUID.
     *
     * @return ApprovalStep
     */
    private function makeStep(int $order, string $objectUuid='sign-req-1'): ApprovalStep
    {
        $step = new ApprovalStep();
        $step->setUuid('step-'.$order);
        $step->setChainId(101);
        $step->setObjectUuid($objectUuid);
        $step->setStepOrder($order);
        $step->setRole('docudesk-signers');
        $step->setStatus('pending');

        return $step;

    }//end makeStep()

    /**
     * An initiated event on a docudesk chain dispatches SignerStepPendingEvent
     * and invokes the active provider.
     *
     * @return void
     */
    public function testInitiatedOnDocudeskChainDispatchesAndInvokesProvider(): void
    {
        $chain = $this->makeDocudeskChain();
        $step  = $this->makeStep(order: 1);

        $provider = $this->createMock(SigningProviderInterface::class);
        $provider->method('getIdentifier')->willReturn('native');
        $this->providerFactory->expects($this->once())
            ->method('getActiveProvider')
            ->willReturn($provider);

        $this->dispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with($this->isInstanceOf(SignerStepPendingEvent::class));

        $event = new ApprovalStepInitiatedEvent(chain: $chain, step: $step, objectUuid: 'sign-req-1');
        $this->listener->handle($event);

    }//end testInitiatedOnDocudeskChainDispatchesAndInvokesProvider()

    /**
     * Events for foreign chains (different register/schema) are ignored.
     *
     * @return void
     */
    public function testForeignChainIsIgnored(): void
    {
        $chain = new ApprovalChain();
        $chain->setRegisterSlug('decidesk');
        $chain->setSchemaSlug('decision');

        $step = $this->makeStep(order: 1);

        $this->providerFactory->expects($this->never())->method('getActiveProvider');
        $this->dispatcher->expects($this->never())->method('dispatchTyped');

        $event = new ApprovalStepInitiatedEvent(chain: $chain, step: $step, objectUuid: 'decision-99');
        $this->listener->handle($event);

    }//end testForeignChainIsIgnored()

    /**
     * Approved with a next step dispatches SignerStepApprovedEvent AND invokes
     * the provider for the next pending step.
     *
     * @return void
     */
    public function testApprovedWithNextStepDispatchesAndInvokesProvider(): void
    {
        $chain    = $this->makeDocudeskChain();
        $step     = $this->makeStep(order: 1);
        $nextStep = $this->makeStep(order: 2);

        $provider = $this->createMock(SigningProviderInterface::class);
        $provider->method('getIdentifier')->willReturn('native');
        $this->providerFactory->expects($this->once())
            ->method('getActiveProvider')
            ->willReturn($provider);

        $this->dispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with(
                    $this->callback(
                    static function ($event): bool {
                        return $event instanceof SignerStepApprovedEvent
                        && $event->isFinalStep() === false
                        && $event->getUserId() === 'alice';
                    }
                    )
                    );

        $event = new ApprovalStepApprovedEvent(
            chain: $chain,
            step: $step,
            userId: 'alice',
            statusOnApprove: 'IN_PROGRESS',
            nextStep: $nextStep
        );
        $this->listener->handle($event);

    }//end testApprovedWithNextStepDispatchesAndInvokesProvider()

    /**
     * Approved with no next step (final) does not invoke the provider — the
     * ApprovalStepCompletedEvent will run shortly after and is what closes
     * the chain.
     *
     * @return void
     */
    public function testApprovedAsFinalStepDoesNotInvokeProvider(): void
    {
        $chain = $this->makeDocudeskChain();
        $step  = $this->makeStep(order: 2);

        $this->providerFactory->expects($this->never())->method('getActiveProvider');
        $this->dispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with(
                    $this->callback(
                    static function ($event): bool {
                        return $event instanceof SignerStepApprovedEvent && $event->isFinalStep() === true;
                    }
                    )
                    );

        $event = new ApprovalStepApprovedEvent(
            chain: $chain,
            step: $step,
            userId: 'bob',
            statusOnApprove: 'COMPLETED',
            nextStep: null
        );
        $this->listener->handle($event);

    }//end testApprovedAsFinalStepDoesNotInvokeProvider()

    /**
     * Rejected event dispatches SignerStepRejectedEvent and never invokes
     * the provider.
     *
     * @return void
     */
    public function testRejectedDispatchesAndDoesNotInvokeProvider(): void
    {
        $chain = $this->makeDocudeskChain();
        $step  = $this->makeStep(order: 1);

        $this->providerFactory->expects($this->never())->method('getActiveProvider');
        $this->dispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with(
                    $this->callback(
                    static function ($event): bool {
                        return $event instanceof SignerStepRejectedEvent
                        && $event->getUserId() === 'mallory';
                    }
                    )
                    );

        $event = new ApprovalStepRejectedEvent(
            chain: $chain,
            step: $step,
            userId: 'mallory',
            statusOnReject: 'DECLINED'
        );
        $this->listener->handle($event);

    }//end testRejectedDispatchesAndDoesNotInvokeProvider()

    /**
     * Completed event dispatches SignerChainCompletedEvent.
     *
     * @return void
     */
    public function testCompletedDispatchesChainCompletedEvent(): void
    {
        $chain     = $this->makeDocudeskChain();
        $finalStep = $this->makeStep(order: 3);

        $this->providerFactory->expects($this->never())->method('getActiveProvider');
        $this->dispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with(
                    $this->callback(
                    static function ($event): bool {
                        return $event instanceof SignerChainCompletedEvent
                        && $event->getUserId() === 'carol';
                    }
                    )
                    );

        $event = new ApprovalStepCompletedEvent(
            chain: $chain,
            finalStep: $finalStep,
            userId: 'carol',
            statusOnApprove: 'COMPLETED'
        );
        $this->listener->handle($event);

    }//end testCompletedDispatchesChainCompletedEvent()

    /**
     * When no signing-request slugs are configured (fresh install), the
     * listener treats every event as foreign and skips it.
     *
     * @return void
     */
    public function testUnconfiguredAppSkipsAllEvents(): void
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')->willReturn('');

        $listener = new ApprovalStepListener(
            translator: new SignerEventTranslator(
                providerFactory: $this->providerFactory,
                dispatcher: $this->dispatcher,
                logger: $this->logger
            ),
            config: $config,
            logger: $this->logger
        );

        $this->providerFactory->expects($this->never())->method('getActiveProvider');
        $this->dispatcher->expects($this->never())->method('dispatchTyped');

        $event = new ApprovalStepInitiatedEvent(
            chain: $this->makeDocudeskChain(),
            step: $this->makeStep(order: 1),
            objectUuid: 'sign-req-1'
        );
        $listener->handle($event);

    }//end testUnconfiguredAppSkipsAllEvents()

    /**
     * Provider resolution failure is swallowed — the listener must not throw
     * out of `handle()` (OR's write-path runs other listeners after it).
     *
     * @return void
     */
    public function testProviderResolutionFailureIsLoggedNotThrown(): void
    {
        $this->providerFactory->expects($this->once())
            ->method('getActiveProvider')
            ->willThrowException(new \RuntimeException('no provider'));

        $this->logger->expects($this->atLeastOnce())->method('error');

        // Dispatch still happens — the typed event is independent of the
        // provider call.
        $this->dispatcher->expects($this->once())->method('dispatchTyped');

        $event = new ApprovalStepInitiatedEvent(
            chain: $this->makeDocudeskChain(),
            step: $this->makeStep(order: 1),
            objectUuid: 'sign-req-1'
        );

        $this->listener->handle($event);

    }//end testProviderResolutionFailureIsLoggedNotThrown()
}//end class
