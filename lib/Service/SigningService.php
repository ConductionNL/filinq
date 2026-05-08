<?php

/**
 * Signing Service
 *
 * Orchestrates the signing request lifecycle.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use Psr\Log\LoggerInterface;

/**
 * Service for managing signing request lifecycle
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SigningService
{

    /**
     * Valid status transitions for signing requests
     *
     * @var array<string, array<string>>
     */
    private const STATUS_TRANSITIONS = [
        'DRAFT'       => ['PENDING', 'CANCELLED'],
        'PENDING'     => ['IN_PROGRESS', 'CANCELLED', 'EXPIRED'],
        'IN_PROGRESS' => ['COMPLETED', 'DECLINED', 'CANCELLED', 'EXPIRED'],
        'COMPLETED'   => [],
        'DECLINED'    => [],
        'EXPIRED'     => [],
        'CANCELLED'   => [],
    ];


    /**
     * Constructor
     *
     * @param SettingsService        $settingsService     Settings service
     * @param SigningAuditService    $auditService        Audit service
     * @param SigningProviderFactory $providerFactory     Provider factory
     * @param IAppConfig             $config              App config
     * @param IUserSession           $userSession         User session
     * @param INotificationManager   $notificationManager Notification manager
     * @param LoggerInterface        $logger              Logger
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly SigningAuditService $auditService,
        private readonly SigningProviderFactory $providerFactory,
        private readonly IAppConfig $config,
        private readonly IUserSession $userSession,
        private readonly INotificationManager $notificationManager,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Create a new signing request
     *
     * @param array<string, mixed> $data The signing request data
     *
     * @return array<string, mixed> The created signing request
     *
     * @throws \RuntimeException If creation fails
     */
    public function createRequest(array $data): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException('No authenticated user');
        }

        $objectService = $this->settingsService->getObjectService();
        $expiryDays    = (int) $this->config->getValueString('docudesk', 'signing_request_expiry_days', '30');
        $deadline      = (new \DateTimeImmutable())->modify('+'.$expiryDays.' days');
        $defaultLevel  = $this->config->getValueString('docudesk', 'signing_default_level', 'SES');
        $defaultProv   = $this->config->getValueString('docudesk', 'signing_provider', 'native');

        $request = [
            'documentFileId'  => $data['documentFileId'] ?? '',
            'documentName'    => $data['documentName'] ?? '',
            'initiatorUserId' => $user->getUID(),
            'signatureLevel'  => $data['signatureLevel'] ?? $defaultLevel,
            'signingMode'     => $data['signingMode'] ?? 'sequential',
            'status'          => 'PENDING',
            'provider'        => $data['provider'] ?? $defaultProv,
            'deadline'        => $data['deadline'] ?? $deadline->format(\DateTimeInterface::ATOM),
            'signerIds'       => [],
        ];

        $this->validateRequestData(data: $request);

        $register       = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema         = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $createdRequest = $objectService->saveObject($register, $schema, $request);

        $signers        = $data['signers'] ?? [];
        $signerIds      = [];
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');

        foreach ($signers as $index => $signerData) {
            $signerRecord = [
                'signingRequestId' => $createdRequest['id'] ?? $createdRequest['uuid'] ?? '',
                'userId'           => $signerData['userId'] ?? '',
                'displayName'      => $signerData['displayName'] ?? '',
                'email'            => $signerData['email'] ?? '',
                'order'            => $signerData['order'] ?? $index,
                'status'           => 'PENDING',
            ];

            $created     = $objectService->saveObject($signerRegister, $signerSchema, $signerRecord);
            $signerIds[] = $created['id'] ?? $created['uuid'] ?? '';
        }//end foreach

        $requestId = $createdRequest['id'] ?? $createdRequest['uuid'] ?? '';
        $createdRequest['signerIds'] = $signerIds;
        $objectService->saveObject($register, $schema, $createdRequest);

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'CREATED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp(),
            signatureLevel: $request['signatureLevel'],
            provider: $request['provider']
        );

        return $createdRequest;

    }//end createRequest()


    /**
     * Get a signing request by ID
     *
     * @param string $requestId The signing request ID
     *
     * @return array<string, mixed> The signing request
     *
     * @throws \RuntimeException If not found
     */
    public function getRequest(string $requestId): array
    {
        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        $request = $objectService->getObject($register, $schema, $requestId);
        if (empty($request) === true) {
            throw new \RuntimeException('Signing request not found: '.$requestId);
        }

        return $request;

    }//end getRequest()


    /**
     * List signing requests
     *
     * @return array<int, array<string, mixed>> List of signing requests
     */
    public function listRequests(): array
    {
        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        return $objectService->getObjects($register, $schema);

    }//end listRequests()


    /**
     * Sign a document within a signing request
     *
     * @param string $requestId The signing request ID
     * @param string $signerId  The signer record ID
     *
     * @return array<string, mixed> The updated signer record
     *
     * @throws \RuntimeException If signing fails
     */
    public function sign(string $requestId, string $signerId): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException('No authenticated user');
        }

        $objectService = $this->settingsService->getObjectService();
        $request       = $this->getRequest(requestId: $requestId);
        $status        = $request['status'] ?? '';

        if (in_array($status, ['PENDING', 'IN_PROGRESS'], true) === false) {
            throw new \RuntimeException('Signing request is not in a signable state: '.$status);
        }

        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signer         = $objectService->getObject($signerRegister, $signerSchema, $signerId);

        if (empty($signer) === true) {
            throw new \RuntimeException('Signer record not found: '.$signerId);
        }

        if (($signer['status'] ?? '') !== 'PENDING') {
            throw new \RuntimeException('Signer has already responded to this request');
        }

        $now = new \DateTimeImmutable();
        $signer['status']    = 'SIGNED';
        $signer['signedAt']  = $now->format(\DateTimeInterface::ATOM);
        $signer['ipAddress'] = $this->getClientIp();
        $objectService->saveObject($signerRegister, $signerSchema, $signer);

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'SIGNED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp(),
            signatureLevel: $request['signatureLevel'] ?? 'SES',
            provider: $request['provider'] ?? 'native'
        );

        $this->updateRequestStatus(requestId: $requestId, request: $request);

        return $signer;

    }//end sign()


    /**
     * Decline a signing request
     *
     * @param string $requestId The signing request ID
     * @param string $signerId  The signer record ID
     * @param string $reason    The decline reason
     *
     * @return array<string, mixed> The updated signer record
     */
    public function decline(string $requestId, string $signerId, string $reason): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException('No authenticated user');
        }

        $objectService  = $this->settingsService->getObjectService();
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signer         = $objectService->getObject($signerRegister, $signerSchema, $signerId);

        if (empty($signer) === true) {
            throw new \RuntimeException('Signer record not found: '.$signerId);
        }

        $signer['status']        = 'DECLINED';
        $signer['declineReason'] = $reason;
        $objectService->saveObject($signerRegister, $signerSchema, $signer);

        $register = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema   = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $request  = $objectService->getObject($register, $schema, $requestId);

        $signatureLevel = $request['signatureLevel'] ?? 'SES';
        $provider       = $request['provider'] ?? 'native';

        $request['status'] = 'DECLINED';
        $objectService->saveObject($register, $schema, $request);

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'DECLINED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp(),
            signatureLevel: $signatureLevel,
            provider: $provider,
            metadata: ['reason' => $reason]
        );

        return $signer;

    }//end decline()


    /**
     * Cancel a signing request
     *
     * @param string $requestId The signing request ID
     *
     * @return array<string, mixed> The cancelled request
     */
    public function cancelRequest(string $requestId): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException('No authenticated user');
        }

        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $request       = $objectService->getObject($register, $schema, $requestId);

        if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'CANCELLED') === false) {
            throw new \RuntimeException('Cannot cancel request in status: '.($request['status'] ?? 'unknown'));
        }

        $request['status'] = 'CANCELLED';
        $objectService->saveObject($register, $schema, $request);

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'CANCELLED',
            actorUserId: $user->getUID(),
            actorDisplayName: $user->getDisplayName(),
            ipAddress: $this->getClientIp()
        );

        return $request;

    }//end cancelRequest()


    /**
     * Bulk sign multiple signing requests
     *
     * @param array<string> $requestIds Array of request IDs to sign
     *
     * @return array<string, array<string, mixed>> Results keyed by request ID
     */
    public function bulkSign(array $requestIds): array
    {
        $results = [];
        $user    = $this->userSession->getUser();
        if ($user !== null) {
            $userId = $user->getUID();
        } else {
            $userId = '';
        }

        foreach ($requestIds as $requestId) {
            try {
                $request        = $this->getRequest(requestId: $requestId);
                $signerIds      = $request['signerIds'] ?? [];
                $targetSignerId = $this->findSignerForUser(signerIds: $signerIds, userId: $userId);

                if ($targetSignerId !== null) {
                    $results[$requestId] = [
                        'success' => true,
                        'signer'  => $this->sign(requestId: $requestId, signerId: $targetSignerId),
                    ];
                } else {
                    $results[$requestId] = [
                        'success' => false,
                        'error'   => 'No pending signer record found for current user',
                    ];
                }
            } catch (\Exception $e) {
                $results[$requestId] = [
                    'success' => false,
                    'error'   => $e->getMessage(),
                ];
            }//end try
        }//end foreach

        return $results;

    }//end bulkSign()


    /**
     * Validate a status transition
     *
     * @param string $currentStatus The current status
     * @param string $newStatus     The proposed new status
     *
     * @return bool True if transition is valid
     */
    public function isValidTransition(string $currentStatus, string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$currentStatus] ?? [];
        return in_array($newStatus, $allowed, true) === true;

    }//end isValidTransition()


    /**
     * Validate signing request data
     *
     * @param array<string, mixed> $data The request data
     *
     * @return void
     *
     * @throws \RuntimeException If validation fails
     */
    private function validateRequestData(array $data): void
    {
        if (empty($data['documentFileId']) === true) {
            throw new \RuntimeException('Document file ID is required');
        }

        if (empty($data['documentName']) === true) {
            throw new \RuntimeException('Document name is required');
        }

        if (in_array($data['signatureLevel'] ?? '', ['SES', 'AdES', 'QES'], true) === false) {
            throw new \RuntimeException('Invalid signature level');
        }

        if (in_array($data['signingMode'] ?? '', ['sequential', 'parallel'], true) === false) {
            throw new \RuntimeException('Invalid signing mode');
        }

    }//end validateRequestData()


    /**
     * Update the signing request status based on signer progress
     *
     * @param string               $requestId The signing request ID
     * @param array<string, mixed> $request   The current request data
     *
     * @return void
     */
    private function updateRequestStatus(string $requestId, array $request): void
    {
        $objectService  = $this->settingsService->getObjectService();
        $register       = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema         = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signerIds      = $request['signerIds'] ?? [];
        $allSigned      = true;

        foreach ($signerIds as $signerId) {
            $signer = $objectService->getObject($signerRegister, $signerSchema, $signerId);
            if (($signer['status'] ?? '') !== 'SIGNED') {
                $allSigned = false;
                break;
            }
        }//end foreach

        $freshRequest = $objectService->getObject($register, $schema, $requestId);

        if ($allSigned === true) {
            $freshRequest['status'] = 'COMPLETED';
        } else {
            $freshRequest['status'] = 'IN_PROGRESS';
        }

        $objectService->saveObject($register, $schema, $freshRequest);

    }//end updateRequestStatus()


    /**
     * Find the signer record ID for a given user
     *
     * @param array<string> $signerIds The signer record IDs
     * @param string        $userId    The user ID to find
     *
     * @return string|null The signer record ID, or null
     */
    private function findSignerForUser(array $signerIds, string $userId): ?string
    {
        $objectService  = $this->settingsService->getObjectService();
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');

        foreach ($signerIds as $signerId) {
            $signer = $objectService->getObject($signerRegister, $signerSchema, $signerId);
            if (($signer['userId'] ?? '') === $userId && ($signer['status'] ?? '') === 'PENDING') {
                return $signerId;
            }
        }//end foreach

        return null;

    }//end findSignerForUser()


    /**
     * Get the client IP address
     *
     * @return string The client IP address
     */
    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    }//end getClientIp()


}//end class
