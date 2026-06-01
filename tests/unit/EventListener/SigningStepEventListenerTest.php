<?php

/**
 * Unit tests for SigningStepEventListener
 *
 * Verifies that the listener correctly routes ApprovalStepInitiatedEvent and
 * ApprovalStepApprovedEvent to the appropriate provider invocation path.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\EventListener
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D5.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\EventListener;

use OCA\DocuDesk\Event\ApprovalStepApprovedEvent;
use OCA\DocuDesk\Event\ApprovalStepInitiatedEvent;
use OCA\DocuDesk\EventListener\SigningStepEventListener;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for SigningStepEventListener
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningStepEventListenerTest extends TestCase
{

    /**
     * @var SigningStepEventListener
     */
    private SigningStepEventListener $listener;

    /**
     * @var SigningProviderFactory|MockObject
     */
    private SigningProviderFactory|MockObject $providerFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->providerFactory = $this->createMock(SigningProviderFactory::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->listener = new SigningStepEventListener(
            providerFactory: $this->providerFactory,
            logger: $this->logger
        );

    }//end setUp()


    /**
     * handle() logs info when an ApprovalStepInitiatedEvent is received.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function testHandleLogsOnStepInitiatedEvent(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(1);

        $step = $this->createMock(ApprovalStep::class);
        $step->method('getId')->willReturn(10);
        $step->method('getStepOrder')->willReturn(1);
        $step->method('getRole')->willReturn('signing-group');

        $event = new ApprovalStepInitiatedEvent(chain: $chain, step: $step);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Step pending — invoking provider'),
                $this->arrayHasKey('chainId')
            );

        $this->listener->handle(event: $event);

    }//end testHandleLogsOnStepInitiatedEvent()


    /**
     * handle() logs info for the next step when ApprovalStepApprovedEvent is received.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function testHandleLogsNextStepOnApprovedEvent(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(2);

        $approvedStep = $this->createMock(ApprovalStep::class);
        $approvedStep->method('getId')->willReturn(20);
        $approvedStep->method('getStepOrder')->willReturn(1);

        $nextStep = $this->createMock(ApprovalStep::class);
        $nextStep->method('getId')->willReturn(21);
        $nextStep->method('getStepOrder')->willReturn(2);
        $nextStep->method('getRole')->willReturn('next-group');

        $event = new ApprovalStepApprovedEvent(
            chain: $chain,
            approvedStep: $approvedStep,
            nextStep: $nextStep
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Step pending — invoking provider'),
                $this->arrayHasKey('chainId')
            );

        $this->listener->handle(event: $event);

    }//end testHandleLogsNextStepOnApprovedEvent()


    /**
     * handle() is a no-op for unrelated events.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function testHandleIgnoresUnrelatedEvents(): void
    {
        $unrelated = new Event();

        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('error');

        $this->listener->handle(event: $unrelated);

    }//end testHandleIgnoresUnrelatedEvents()


    /**
     * The listener's constructor descriptor lists ApprovalStepInitiatedEvent and
     * ApprovalStepApprovedEvent as handled event types (D2.1 acceptance).
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D2.1
     */
    public function testListenerHandlesCorrectEventTypes(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(99);

        $step = $this->createMock(ApprovalStep::class);
        $step->method('getId')->willReturn(99);
        $step->method('getStepOrder')->willReturn(1);
        $step->method('getRole')->willReturn('role');

        $initiated = new ApprovalStepInitiatedEvent(chain: $chain, step: $step);
        $approved  = new ApprovalStepApprovedEvent(chain: $chain, approvedStep: $step, nextStep: $step);

        $this->logger->expects($this->exactly(2))->method('info');

        $this->listener->handle(event: $initiated);
        $this->listener->handle(event: $approved);

    }//end testListenerHandlesCorrectEventTypes()


}//end class
