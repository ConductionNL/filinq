<?php

/**
 * Signing Service
 *
 * Orchestrates the signing request lifecycle via OR's ApprovalChain/ApprovalStep
 * entities. All chain-state management (who must sign, sequential advance, completion
 * detection) is delegated to OR's ApprovalService. This service translates docudesk's
 * signing API into OR approval-workflow operations and dispatches typed events so that
 * signing providers can react to step transitions.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Event\ApprovalStepApprovedEvent;
use OCA\DocuDesk\Event\ApprovalStepInitiatedEvent;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalChainMapper;
use OCA\OpenRegister\Db\ApprovalStep;
use OCA\OpenRegister\Db\ApprovalStepMapper;
use OCA\OpenRegister\Service\ApprovalService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Service for managing signing request lifecycle via OR ApprovalChain
 *
 * Signing flows are backed by OR's ApprovalChain + ApprovalStep entities.
 * The bespoke step-state machine (STATUS_TRANSITIONS, sequential-advance,
 * completion-detection) has been removed; OR handles all of that via
 * ApprovalService::initializeChain / approveStep / rejectStep.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SigningService
{
    /**
     * Constructor
     *
     * @param SettingsService        $settingsService Settings service
     * @param SigningAuditService    $auditService    Audit service
     * @param SigningProviderFactory $providerFactory Provider factory
     * @param IUserSession           $userSession     User session
     * @param LoggerInterface        $logger          Logger
     * @param IRequest               $request         HTTP request
     * @param ApprovalService        $approvalService OR ApprovalService
     * @param ApprovalChainMapper    $chainMapper     OR chain mapper
     * @param ApprovalStepMapper     $stepMapper      OR step mapper
     * @param IEventDispatcher       $eventDispatcher Event dispatcher
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly SigningAuditService $auditService,
        private readonly SigningProviderFactory $providerFactory,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
        private readonly IRequest $request,
        private readonly ApprovalService $approvalService,
        private readonly ApprovalChainMapper $chainMapper,
        private readonly ApprovalStepMapper $stepMapper,
        private readonly IEventDispatcher $eventDispatcher
    ) {

    }//end __construct()

    /**
     * Create a new signing request backed by an OR ApprovalChain.
     *
     * One ApprovalStep is created per signer. The first step starts as
     * 'pending'; subsequent steps start as 'waiting' and are advanced by
     * OR's advance-on-approval logic. After chain initialization an
     * ApprovalStepInitiatedEvent is dispatched so that the signing provider
     * can present signing UI to the first signer.
     *
     * @param array<string, mixed> $data The signing request data
     *
     * @return array<string, mixed> The created signing request (chain representation)
     *
     * @throws RuntimeException If no authenticated user or creation fails
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
     */
    public function createRequest(array $data): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $signers = $data['signers'] ?? [];
        $steps   = [];
        foreach ($signers as $index => $signer) {
            $steps[] = [
                'role'  => $signer['groupId'] ?? $signer['userId'] ?? '',
                'order' => (int) ($signer['order'] ?? ($index + 1)),
            ];
        }

        $documentName = (string) ($data['documentName'] ?? '');
        $chain        = $this->chainMapper->createFromArray(
            [
                'name'    => 'docudesk-signing-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $documentName),
                'steps'   => json_encode($steps),
                'enabled' => true,
            ]
        );

        $objectUuid   = (string) ($data['documentFileId'] ?? '');
        $createdSteps = $this->approvalService->initializeChain(chain: $chain, objectUuid: $objectUuid);

        $this->auditService->logEvent(
            signingRequestId: (string) $chain->getId(),
            action: 'CREATED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp(),
            signatureLevel: (string) ($data['signatureLevel'] ?? 'SES'),
            provider: (string) ($data['provider'] ?? 'native')
        );

        if (count($createdSteps) > 0) {
            $this->eventDispatcher->dispatchTyped(
                new ApprovalStepInitiatedEvent(chain: $chain, step: $createdSteps[0])
            );
        }

        return $this->buildRequestResponse(chain: $chain, steps: $createdSteps, data: $data, userId: $user->getUID());

    }//end createRequest()

    /**
     * Get a signing request by chain ID.
     *
     * @param string $requestId The signing request ID (= ApprovalChain ID)
     *
     * @return array<string, mixed> The signing request
     *
     * @throws RuntimeException If not found
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.2
     */
    public function getRequest(string $requestId): array
    {
        $chain = $this->chainMapper->find(id: (int) $requestId);
        $steps = $this->stepMapper->findByChain(chainId: $chain->getId());

        return $this->buildRequestResponse(chain: $chain, steps: $steps);

    }//end getRequest()

    /**
     * List all signing requests (approval chains).
     *
     * @return array<int, array<string, mixed>> List of signing requests
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.2
     */
    public function listRequests(): array
    {
        $chains   = $this->chainMapper->findAll();
        $requests = [];

        foreach ($chains as $chain) {
            $steps      = $this->stepMapper->findByChain(chainId: $chain->getId());
            $requests[] = $this->buildRequestResponse(chain: $chain, steps: $steps);
        }

        return $requests;

    }//end listRequests()

    /**
     * Sign (approve) the pending step for this signer.
     *
     * Delegates to OR's ApprovalService::approveStep(), which advances the
     * next step to 'pending'. If a next step exists, an
     * ApprovalStepApprovedEvent is dispatched for the signing provider.
     *
     * @param string $requestId The signing request ID (chain ID)
     * @param string $signerId  The signer record ID (step ID)
     *
     * @return array<string, mixed> The updated step as an array
     *
     * @throws RuntimeException If not authenticated or step operation fails
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
     */
    public function sign(string $requestId, string $signerId): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $result   = $this->approvalService->approveStep(
            stepId: (int) $signerId,
            userId: $user->getUID()
        );
        $step     = $result['step'];
        $nextStep = $result['nextStep'];
        $chain    = $result['chain'];

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'SIGNED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp()
        );

        if ($nextStep !== null) {
            $this->eventDispatcher->dispatchTyped(
                new ApprovalStepApprovedEvent(
                    chain: $chain,
                    approvedStep: $step,
                    nextStep: $nextStep
                )
            );
        }

        return $step->jsonSerialize();

    }//end sign()

    /**
     * Decline (reject) the pending step for this signer.
     *
     * Delegates to OR's ApprovalService::rejectStep(). OR marks the step
     * 'rejected' and stops chain advance; no further steps are triggered.
     *
     * @param string $requestId The signing request ID (chain ID)
     * @param string $signerId  The signer record ID (step ID)
     * @param string $reason    The decline reason stored as step comment
     *
     * @return array<string, mixed> The updated step as an array
     *
     * @throws RuntimeException If not authenticated or step operation fails
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
     */
    public function decline(string $requestId, string $signerId, string $reason): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $result = $this->approvalService->rejectStep(
            stepId: (int) $signerId,
            userId: $user->getUID(),
            comment: $reason
        );

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'DECLINED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp(),
            metadata: ['reason' => $reason]
        );

        return $result['step']->jsonSerialize();

    }//end decline()

    /**
     * Cancel a signing request by marking all open steps as rejected.
     *
     * @param string $requestId The signing request ID (chain ID)
     *
     * @return array<string, mixed> The cancelled request representation
     *
     * @throws RuntimeException If not authenticated
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.3
     */
    public function cancelRequest(string $requestId): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $chain = $this->chainMapper->find(id: (int) $requestId);
        $steps = $this->stepMapper->findByChain(chainId: $chain->getId());

        foreach ($steps as $step) {
            if (in_array($step->getStatus(), ['pending', 'waiting'], strict: true) === true) {
                $step->setStatus('rejected');
                $step->setComment('Cancelled by initiator');
                $this->stepMapper->update(entity: $step);
            }
        }

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'CANCELLED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp()
        );

        $updatedSteps = $this->stepMapper->findByChain(chainId: $chain->getId());

        return $this->buildRequestResponse(chain: $chain, steps: $updatedSteps);

    }//end cancelRequest()

    /**
     * Bulk sign multiple signing requests for the current user.
     *
     * For each request, finds the pending step whose role matches the current
     * user and calls sign() on it.
     *
     * @param array<string> $requestIds Array of request (chain) IDs to sign
     *
     * @return array<string, array<string, mixed>> Results keyed by request ID
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.1
     */
    public function bulkSign(array $requestIds): array
    {
        $results = [];
        $user    = $this->userSession->getUser();
        $userId  = '';
        if ($user !== null) {
            $userId = $user->getUID();
        }

        foreach ($requestIds as $requestId) {
            try {
                $steps       = $this->stepMapper->findByChain(chainId: (int) $requestId);
                $pendingStep = $this->findPendingStepForUser(steps: $steps, userId: $userId);

                if ($pendingStep === null) {
                    $results[$requestId] = [
                        'success' => false,
                        'error'   => 'No pending step found for current user',
                    ];
                    continue;
                }

                $results[$requestId] = [
                    'success' => true,
                    'signer'  => $this->sign(requestId: $requestId, signerId: (string) $pendingStep->getId()),
                ];
            } catch (Exception $e) {
                $results[$requestId] = [
                    'success' => false,
                    'error'   => $e->getMessage(),
                ];
            }//end try
        }//end foreach

        return $results;

    }//end bulkSign()

    /**
     * Build the signing-request response array from an ApprovalChain and its steps.
     *
     * Derives the overall status from step statuses:
     * - all approved  → COMPLETED
     * - any rejected  → DECLINED
     * - any approved  → IN_PROGRESS
     * - otherwise     → PENDING
     *
     * @param ApprovalChain            $chain  The chain entity
     * @param array<int, ApprovalStep> $steps  All steps for this chain
     * @param array<string, mixed>     $data   Optional original request data for
     *                                         fields not stored in the chain
     * @param string                   $userId Optional initiator user ID
     *
     * @return array<string, mixed>
     *
     * @spec openspec/changes/migrate-signing-to-or-approval-workflow/tasks.md#task-D1.2
     */
    private function buildRequestResponse(
        ApprovalChain $chain,
        array $steps=[],
        array $data=[],
        string $userId=''
    ): array {
        $allApproved = count($steps) > 0;
        $anyRejected = false;
        $anyApproved = false;

        foreach ($steps as $step) {
            if ($step->getStatus() !== 'approved') {
                $allApproved = false;
            }

            if ($step->getStatus() === 'rejected') {
                $anyRejected = true;
            }

            if ($step->getStatus() === 'approved') {
                $anyApproved = true;
            }
        }

        $status = 'PENDING';
        if ($anyApproved === true) {
            $status = 'IN_PROGRESS';
        }

        if ($anyRejected === true) {
            $status = 'DECLINED';
        }

        if ($allApproved === true) {
            $status = 'COMPLETED';
        }

        $objectUuid = '';
        if (count($steps) > 0) {
            $objectUuid = (string) ($steps[0]->getObjectUuid() ?? '');
        }

        return [
            'id'              => (string) $chain->getId(),
            'chainId'         => (string) $chain->getId(),
            'chainUuid'       => (string) ($chain->getUuid() ?? ''),
            'documentFileId'  => $data['documentFileId'] ?? $objectUuid,
            'documentName'    => $data['documentName'] ?? $chain->getName(),
            'initiatorUserId' => $data['initiatorUserId'] ?? $userId,
            'signatureLevel'  => $data['signatureLevel'] ?? 'SES',
            'signingMode'     => $data['signingMode'] ?? 'sequential',
            'status'          => $status,
            'provider'        => $data['provider'] ?? 'native',
            'signerIds'       => array_map(static fn(ApprovalStep $s) => (string) $s->getId(), $steps),
        ];

    }//end buildRequestResponse()

    /**
     * Find the first pending step whose role matches the given user ID.
     *
     * @param array<int, ApprovalStep> $steps  All steps to search
     * @param string                   $userId The user ID to match against step role
     *
     * @return ApprovalStep|null The matching step or null
     */
    private function findPendingStepForUser(array $steps, string $userId): ?ApprovalStep
    {
        foreach ($steps as $step) {
            if ($step->getStatus() === 'pending' && $step->getRole() === $userId) {
                return $step;
            }
        }

        return null;

    }//end findPendingStepForUser()

    /**
     * Get the client IP address.
     *
     * @return string
     */
    private function getClientIp(): string
    {
        return $this->request->getRemoteAddress();

    }//end getClientIp()
}//end class
