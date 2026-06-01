<?php

/**
 * Unit tests for SigningService — migrate-signing-to-or-approval-workflow
 *
 * Verifies that SigningService delegates all chain-state management to
 * OR's ApprovalService and dispatches typed events on step transitions.
 * No bespoke step-routing state machine code remains in the service.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
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

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Event\ApprovalStepApprovedEvent;
use OCA\DocuDesk\Event\ApprovalStepInitiatedEvent;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalChainMapper;
use OCA\OpenRegister\Db\ApprovalStep;
use OCA\OpenRegister\Db\ApprovalStepMapper;
use OCA\OpenRegister\Service\ApprovalService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for SigningService — OR approval-workflow delegation
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningServiceTest extends TestCase
{

    /**
     * @var SigningService
     */
    private SigningService $service;

    /**
     * @var ApprovalService|MockObject
     */
    private ApprovalService|MockObject $approvalService;

    /**
     * @var ApprovalChainMapper|MockObject
     */
    private ApprovalChainMapper|MockObject $chainMapper;

    /**
     * @var ApprovalStepMapper|MockObject
     */
    private ApprovalStepMapper|MockObject $stepMapper;

    /**
     * @var IEventDispatcher|MockObject
     */
    private IEventDispatcher|MockObject $eventDispatcher;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $userSession;

    /**
     * @var SigningAuditService|MockObject
     */
    private SigningAuditService|MockObject $auditService;

    /**
     * @var IUser|MockObject
     */
    private IUser|MockObject $user;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = $this->createMock(ApprovalService::class);
        $this->chainMapper     = $this->createMock(ApprovalChainMapper::class);
        $this->stepMapper      = $this->createMock(ApprovalStepMapper::class);
        $this->eventDispatcher = $this->createMock(IEventDispatcher::class);
        $this->userSession     = $this->createMock(IUserSession::class);
        $this->auditService    = $this->createMock(SigningAuditService::class);

        $this->user = $this->createMock(IUser::class);
        $this->user->method('getUID')->willReturn('alice');
        $this->user->method('getDisplayName')->willReturn('Alice');
        $this->userSession->method('getUser')->willReturn($this->user);

        $settingsService  = $this->createMock(SettingsService::class);
        $providerFactory  = $this->createMock(SigningProviderFactory::class);
        $logger           = $this->createMock(LoggerInterface::class);
        $request          = $this->createMock(IRequest::class);
        $request->method('getRemoteAddress')->willReturn('127.0.0.1');

        $this->service = new SigningService(
            settingsService: $settingsService,
            auditService: $this->auditService,
            providerFactory: $providerFactory,
            userSession: $this->userSession,
            logger: $logger,
            request: $request,
            approvalService: $this->approvalService,
            chainMapper: $this->chainMapper,
            stepMapper: $this->stepMapper,
            eventDispatcher: $this->eventDispatcher
        );

    }//end setUp()


    /**
     * createRequest() delegates chain creation to OR and dispatches
     * ApprovalStepInitiatedEvent for the first (pending) step.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D5.1
     */
    public function testCreateRequestCreatesOrChainAndDispatchesInitiatedEvent(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(42);
        $chain->method('getUuid')->willReturn('chain-uuid-1');
        $chain->method('getName')->willReturn('docudesk-signing-contract');

        $step1 = $this->createMock(ApprovalStep::class);
        $step1->method('getId')->willReturn(1);
        $step1->method('getStatus')->willReturn('pending');
        $step1->method('getObjectUuid')->willReturn('doc-xyz');

        $step2 = $this->createMock(ApprovalStep::class);
        $step2->method('getId')->willReturn(2);
        $step2->method('getStatus')->willReturn('waiting');
        $step2->method('getObjectUuid')->willReturn('doc-xyz');

        $this->chainMapper->method('createFromArray')->willReturn($chain);
        $this->approvalService->method('initializeChain')
            ->willReturn([$step1, $step2]);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with($this->isInstanceOf(ApprovalStepInitiatedEvent::class));

        $data = [
            'documentFileId' => 'doc-xyz',
            'documentName'   => 'contract',
            'signers'        => [
                ['userId' => 'signer-a', 'order' => 1],
                ['userId' => 'signer-b', 'order' => 2],
            ],
        ];

        $result = $this->service->createRequest(data: $data);

        $this->assertSame('42', $result['id']);
        $this->assertSame('doc-xyz', $result['documentFileId']);
        $this->assertSame('PENDING', $result['status']);
        $this->assertCount(2, $result['signerIds']);

    }//end testCreateRequestCreatesOrChainAndDispatchesInitiatedEvent()


    /**
     * createRequest() throws RuntimeException when no user is authenticated.
     *
     * @return void
     */
    public function testCreateRequestThrowsWhenNoUser(): void
    {
        $noUserSession = $this->createMock(IUserSession::class);
        $noUserSession->method('getUser')->willReturn(null);

        $settingsService = $this->createMock(SettingsService::class);
        $providerFactory = $this->createMock(SigningProviderFactory::class);
        $logger          = $this->createMock(LoggerInterface::class);
        $request         = $this->createMock(IRequest::class);

        $serviceWithNoUser = new SigningService(
            settingsService: $settingsService,
            auditService: $this->auditService,
            providerFactory: $providerFactory,
            userSession: $noUserSession,
            logger: $logger,
            request: $request,
            approvalService: $this->approvalService,
            chainMapper: $this->chainMapper,
            stepMapper: $this->stepMapper,
            eventDispatcher: $this->eventDispatcher
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No authenticated user');

        $serviceWithNoUser->createRequest(data: []);

    }//end testCreateRequestThrowsWhenNoUser()


    /**
     * sign() calls approveStep on OR and dispatches ApprovalStepApprovedEvent
     * when a next step exists.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D5.1
     */
    public function testSignCallsApproveStepAndDispatchesApprovedEvent(): void
    {
        $approvedStep = $this->createMock(ApprovalStep::class);
        $approvedStep->method('getId')->willReturn(1);
        $approvedStep->method('jsonSerialize')->willReturn(['id' => 1, 'status' => 'approved']);

        $nextStep = $this->createMock(ApprovalStep::class);
        $nextStep->method('getId')->willReturn(2);

        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(10);

        $this->approvalService->expects($this->once())
            ->method('approveStep')
            ->with(
                stepId: 1,
                userId: 'alice'
            )
            ->willReturn([
                'step'     => $approvedStep,
                'nextStep' => $nextStep,
                'chain'    => $chain,
            ]);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with($this->isInstanceOf(ApprovalStepApprovedEvent::class));

        $result = $this->service->sign(requestId: '10', signerId: '1');

        $this->assertSame(['id' => 1, 'status' => 'approved'], $result);

    }//end testSignCallsApproveStepAndDispatchesApprovedEvent()


    /**
     * sign() does NOT dispatch an event when there is no next step
     * (last signer just signed — chain complete).
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.2
     */
    public function testSignDoesNotDispatchEventWhenNoNextStep(): void
    {
        $approvedStep = $this->createMock(ApprovalStep::class);
        $approvedStep->method('getId')->willReturn(1);
        $approvedStep->method('jsonSerialize')->willReturn(['id' => 1, 'status' => 'approved']);

        $chain = $this->createMock(ApprovalChain::class);

        $this->approvalService->method('approveStep')
            ->willReturn([
                'step'     => $approvedStep,
                'nextStep' => null,
                'chain'    => $chain,
            ]);

        $this->eventDispatcher->expects($this->never())->method('dispatchTyped');

        $this->service->sign(requestId: '10', signerId: '1');

    }//end testSignDoesNotDispatchEventWhenNoNextStep()


    /**
     * decline() calls rejectStep on OR with the supplied reason.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D5.1
     */
    public function testDeclineCallsRejectStep(): void
    {
        $rejectedStep = $this->createMock(ApprovalStep::class);
        $rejectedStep->method('jsonSerialize')->willReturn(['id' => 1, 'status' => 'rejected']);

        $chain = $this->createMock(ApprovalChain::class);

        $this->approvalService->expects($this->once())
            ->method('rejectStep')
            ->with(
                stepId: 1,
                userId: 'alice',
                comment: 'Niet akkoord'
            )
            ->willReturn([
                'step'  => $rejectedStep,
                'chain' => $chain,
            ]);

        $result = $this->service->decline(requestId: '10', signerId: '1', reason: 'Niet akkoord');

        $this->assertSame('rejected', $result['status']);

    }//end testDeclineCallsRejectStep()


    /**
     * getRequest() returns COMPLETED when all steps are approved.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.2
     */
    public function testGetRequestReturnsCompletedWhenAllStepsApproved(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(5);
        $chain->method('getUuid')->willReturn('chain-uuid-5');
        $chain->method('getName')->willReturn('docudesk-signing-invoice');

        $step1 = $this->createMock(ApprovalStep::class);
        $step1->method('getStatus')->willReturn('approved');
        $step1->method('getId')->willReturn(10);
        $step1->method('getObjectUuid')->willReturn('inv-uuid');

        $step2 = $this->createMock(ApprovalStep::class);
        $step2->method('getStatus')->willReturn('approved');
        $step2->method('getId')->willReturn(11);
        $step2->method('getObjectUuid')->willReturn('inv-uuid');

        $this->chainMapper->method('find')->willReturn($chain);
        $this->stepMapper->method('findByChain')->willReturn([$step1, $step2]);

        $result = $this->service->getRequest(requestId: '5');

        $this->assertSame('COMPLETED', $result['status']);
        $this->assertCount(2, $result['signerIds']);

    }//end testGetRequestReturnsCompletedWhenAllStepsApproved()


    /**
     * getRequest() returns DECLINED when any step is rejected.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.2
     */
    public function testGetRequestReturnsDeclinedWhenStepRejected(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(6);
        $chain->method('getUuid')->willReturn('chain-uuid-6');
        $chain->method('getName')->willReturn('docudesk-signing-doc');

        $step1 = $this->createMock(ApprovalStep::class);
        $step1->method('getStatus')->willReturn('rejected');
        $step1->method('getId')->willReturn(20);
        $step1->method('getObjectUuid')->willReturn('doc-uuid');

        $this->chainMapper->method('find')->willReturn($chain);
        $this->stepMapper->method('findByChain')->willReturn([$step1]);

        $result = $this->service->getRequest(requestId: '6');

        $this->assertSame('DECLINED', $result['status']);

    }//end testGetRequestReturnsDeclinedWhenStepRejected()


    /**
     * cancelRequest() marks all pending/waiting steps as rejected.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.3
     */
    public function testCancelRequestMarksPendingStepsRejected(): void
    {
        $chain = $this->createMock(ApprovalChain::class);
        $chain->method('getId')->willReturn(7);
        $chain->method('getUuid')->willReturn('chain-uuid-7');
        $chain->method('getName')->willReturn('docudesk-signing-form');

        $pendingStep = $this->createMock(ApprovalStep::class);
        $pendingStep->method('getStatus')->willReturn('pending');
        $pendingStep->method('getId')->willReturn(30);
        $pendingStep->method('getObjectUuid')->willReturn('form-uuid');

        $waitingStep = $this->createMock(ApprovalStep::class);
        $waitingStep->method('getStatus')->willReturn('waiting');
        $waitingStep->method('getId')->willReturn(31);
        $waitingStep->method('getObjectUuid')->willReturn('form-uuid');

        $this->chainMapper->method('find')->willReturn($chain);

        $callCount = 0;
        $this->stepMapper->method('findByChain')
            ->willReturnCallback(function () use ($pendingStep, $waitingStep, &$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    return [$pendingStep, $waitingStep];
                }

                return [$pendingStep, $waitingStep];
            });

        $pendingStep->expects($this->once())->method('setStatus')->with('rejected');
        $pendingStep->expects($this->once())->method('setComment')->with('Cancelled by initiator');

        $waitingStep->expects($this->once())->method('setStatus')->with('rejected');
        $waitingStep->expects($this->once())->method('setComment')->with('Cancelled by initiator');

        $this->stepMapper->expects($this->exactly(2))->method('update');

        $result = $this->service->cancelRequest(requestId: '7');

        $this->assertSame('7', $result['id']);

    }//end testCancelRequestMarksPendingStepsRejected()


    /**
     * bulkSign() finds the pending step for the current user and calls sign().
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D5.1
     */
    public function testBulkSignSignsForCurrentUser(): void
    {
        $step = $this->createMock(ApprovalStep::class);
        $step->method('getStatus')->willReturn('pending');
        $step->method('getRole')->willReturn('alice');
        $step->method('getId')->willReturn(50);
        $step->method('jsonSerialize')->willReturn(['id' => 50, 'status' => 'approved']);

        $chain = $this->createMock(ApprovalChain::class);

        $this->stepMapper->method('findByChain')->willReturn([$step]);

        $this->approvalService->method('approveStep')
            ->willReturn([
                'step'     => $step,
                'nextStep' => null,
                'chain'    => $chain,
            ]);

        $this->eventDispatcher->method('dispatchTyped');

        $results = $this->service->bulkSign(requestIds: ['8']);

        $this->assertTrue($results['8']['success']);

    }//end testBulkSignSignsForCurrentUser()


    /**
     * bulkSign() returns an error entry when no pending step exists for the user.
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D5.1
     */
    public function testBulkSignReturnsErrorWhenNoPendingStep(): void
    {
        $step = $this->createMock(ApprovalStep::class);
        $step->method('getStatus')->willReturn('approved');
        $step->method('getRole')->willReturn('alice');

        $this->stepMapper->method('findByChain')->willReturn([$step]);

        $results = $this->service->bulkSign(requestIds: ['9']);

        $this->assertFalse($results['9']['success']);
        $this->assertStringContainsString('No pending step', $results['9']['error']);

    }//end testBulkSignReturnsErrorWhenNoPendingStep()


    /**
     * SigningService has NO bespoke STATUS_TRANSITIONS constant (D1.3 acceptance).
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.3
     */
    public function testNoBespokeStatusTransitionsConstant(): void
    {
        $this->assertFalse(
            defined('OCA\DocuDesk\Service\SigningService::STATUS_TRANSITIONS'),
            'SigningService must not contain the bespoke STATUS_TRANSITIONS constant after migration.'
        );

    }//end testNoBespokeStatusTransitionsConstant()


}//end class
