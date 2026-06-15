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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\DocuDesk\Event\SigningConcludedEvent;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use Psr\Log\LoggerInterface;
use RuntimeException;

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
     * @param IRequest               $request             HTTP request
     * @param IEventDispatcher       $eventDispatcher     Dispatches the SigningConcludedEvent cross-app contract
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
        private readonly LoggerInterface $logger,
        private readonly IRequest $request,
        private readonly IEventDispatcher $eventDispatcher
    ) {

    }//end __construct()

    /**
     * Provenance keys threaded from a cross-app DocumentSigningRequestedEvent
     * onto the persisted signing-request object so the terminal
     * SigningConcludedEvent can correlate back to the originating consumer.
     *
     * @var list<string>
     */
    private const PROVENANCE_FIELDS = [
        'sourceApp',
        'subjectRegister',
        'subjectSchema',
        'subjectId',
        'subjectLabel',
        'externalReference',
        'correlationId',
    ];

    /**
     * Create a new signing request
     *
     * @param array<string, mixed> $data The signing request data
     *
     * @return array<string, mixed> The created signing request
     *
     * @throws RuntimeException If creation fails
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
     */
    public function createRequest(array $data): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $objectService = $this->settingsService->getObjectService();
        $expiryDays    = (int) $this->config->getValueString('docudesk', 'signing_request_expiry_days', '30');
        $deadline      = (new DateTimeImmutable())->modify('+'.$expiryDays.' days');
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
            'deadline'        => $data['deadline'] ?? $deadline->format(DateTimeInterface::ATOM),
            'signerIds'       => [],
        ];

        $this->validateRequestData(data: $request);

        // Cross-app delegated-signing contract (docudesk-signing-events): when a
        // consumer raised this request through DocumentSigningRequestedEvent it
        // carries provenance fields. Persist any present provenance onto the
        // signing-request object (additive/optional) so the terminal
        // SigningConcludedEvent can correlate back to the originating consumer.
        // Internal requests omit these and are unaffected.
        foreach (self::PROVENANCE_FIELDS as $field) {
            if (empty($data[$field]) === false) {
                $request[$field] = $data[$field];
            }
        }

        $register       = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema         = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $savedRequest   = $objectService->saveObject(object: $request, register: $register, schema: $schema);
        $createdRequest = $this->toArray(object: $savedRequest);

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

            $savedSigner = $objectService->saveObject(object: $signerRecord, register: $signerRegister, schema: $signerSchema);
            $created     = $this->toArray(object: $savedSigner);
            $signerIds[] = $created['id'] ?? $created['uuid'] ?? '';
        }//end foreach

        $requestId = $createdRequest['id'] ?? $createdRequest['uuid'] ?? '';
        $createdRequest['signerIds'] = $signerIds;
        $objectService->saveObject(object: $createdRequest, register: $register, schema: $schema);

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
     * Access control: the caller must be the initiator, a listed signer, or an
     * admin. Pass callerUserId='' and isAdmin=true to bypass the check (e.g.
     * when called from an internal method that already verified access).
     *
     * @param string $requestId    The signing request ID
     * @param string $callerUserId UID of the calling user ('' = skip check)
     * @param bool   $isAdmin      True when the caller is an NC admin
     *
     * @return array<string, mixed>|null The signing request, or null when a
     *                                   scoped caller (callerUserId set,
     *                                   non-admin) is neither initiator nor
     *                                   signer (access denied collapses to
     *                                   null). A genuinely not-found request
     *                                   throws RuntimeException('Signing request
     *                                   not found') so the controller can map it
     *                                   to a 404.
     *
     * @throws RuntimeException When the underlying record does not exist.
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
     *
     * Wilco #6 blocker fix (docudesk#100, 2026-06-06): the access-denied path
     * still returns null (indistinguishable from the controller's not-found
     * 404 for a scoped caller), so an unrelated user cannot probe request-ID
     * existence. Not-found now throws a fixed, ID-free message ('Signing
     * request not found') — no UUID is echoed, so nothing is leaked.
     */
    public function getRequest(string $requestId, string $callerUserId='', bool $isAdmin=false): ?array
    {
        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        $object = $objectService->find(id: $requestId, register: $register, schema: $schema);
        if ($object === null) {
            // Genuine not-found: throw so the controller maps it to a single
            // 404. Access-denied below still collapses to null — the two
            // shapes stay indistinguishable to a non-admin caller, preserving
            // the Wilco #6 anti-existence-probing contract.
            throw new RuntimeException('Signing request not found');
        }

        if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
            $request = $object->jsonSerialize();
        } else {
            $request = (array) $object;
        }

        // WF2 + Wilco #6 fix: scope single-record access to initiator,
        // signer, or admin. Access denied collapses to null (same shape as
        // not-found), so the controller can emit one 404 regardless.
        if ($callerUserId !== '' && $isAdmin === false) {
            $isInitiator    = ($request['initiatorUserId'] ?? '') === $callerUserId;
            $isSignerInList = in_array($callerUserId, (array) ($request['signerIds'] ?? []), true);

            if ($isInitiator === false && $isSignerInList === false) {
                return null;
            }
        }

        return $request;

    }//end getRequest()

    /**
     * List signing requests scoped to the calling user
     *
     * Admins see all requests. Regular users see only requests where they are
     * the initiator or a listed signer (WF2 fix: previously returned all
     * requests regardless of ownership — full cross-tenant data disclosure).
     *
     * @param string $callerUserId UID of the calling user
     * @param bool   $isAdmin      True when the caller is an NC admin
     *
     * @return array<int, array<string, mixed>> List of signing requests
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
     */
    public function listRequests(string $callerUserId='', bool $isAdmin=false): array
    {
        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        // OpenRegister's findAll() resolves the register/schema from its own
        // context, not from a filters array — passing them as filters yields an
        // empty result ("called without register/schema context"). Use the
        // canonical buildSearchQuery()+searchObjectsPaginated() surface, which
        // takes register/schema explicitly, exactly as TemplateService does.
        $query = $objectService->buildSearchQuery(
            requestParams: ['_limit' => 1000],
            register: $register,
            schema: $schema
        );

        $paginated = $objectService->searchObjectsPaginated(query: $query);
        $results   = ($paginated['results'] ?? []);

        $requests = [];
        foreach ($results as $result) {
            if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
                $item = $result->jsonSerialize();
            } else {
                $item = (array) $result;
            }

            // WF2 security fix: non-admins see only requests they initiated or
            // are listed as a signer on. Admins see all.
            if ($isAdmin === false && $callerUserId !== '') {
                $isInitiator    = ($item['initiatorUserId'] ?? '') === $callerUserId;
                $isSignerInList = in_array($callerUserId, (array) ($item['signerIds'] ?? []), true);

                if ($isInitiator === false && $isSignerInList === false) {
                    continue;
                }
            }

            $requests[] = $item;
        }

        return $requests;

    }//end listRequests()

    /**
     * Sign a document within a signing request
     *
     * @param string $requestId The signing request ID
     * @param string $signerId  The signer record ID
     *
     * @return array<string, mixed> The updated signer record
     *
     * @throws RuntimeException If signing fails
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
     */
    public function sign(string $requestId, string $signerId): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $objectService = $this->settingsService->getObjectService();
        $request       = $this->getRequest(requestId: $requestId);
        if ($request === null) {
            // The getRequest() call returns null on not-found and access-denied
            // (Wilco #6 fix, docudesk#100). Internal sign() doesn't pass a
            // callerUserId so access-denied is impossible from here — null
            // means truly not-found. Throw to keep callers' existing
            // exception contract.
            throw new RuntimeException('Signing request not found: '.$requestId);
        }

        $status = $request['status'] ?? '';

        if (in_array($status, ['PENDING', 'IN_PROGRESS'], true) === false) {
            throw new RuntimeException('Signing request is not in a signable state: '.$status);
        }

        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signerObject   = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);

        if ($signerObject === null) {
            throw new RuntimeException('Signer record not found: '.$signerId);
        }

        if (is_object($signerObject) === true && method_exists($signerObject, 'jsonSerialize') === true) {
            $signer = $signerObject->jsonSerialize();
        } else {
            $signer = (array) $signerObject;
        }

        // C4 security fix: verify the signer record belongs to this signing
        // request. Without this check, an attacker who knows any valid
        // signerId can sign under an arbitrary requestId they do not own.
        if (($signer['signingRequestId'] ?? '') !== $requestId) {
            throw new RuntimeException('Signer record does not belong to this signing request');
        }

        // Security finding #282: ensure the authenticated user is the signer
        // they claim to be. Without this check any authenticated user could
        // sign on behalf of another signer by supplying their signer ID.
        if (($signer['userId'] ?? '') !== $user->getUID()) {
            throw new RuntimeException('Not authorized to sign as this signer');
        }

        if (($signer['status'] ?? '') !== 'PENDING') {
            throw new RuntimeException('Signer has already responded to this request');
        }

        $now = new DateTimeImmutable();
        $signer['status']    = 'SIGNED';
        $signer['signedAt']  = $now->format(DateTimeInterface::ATOM);
        $signer['ipAddress'] = $this->getClientIp();
        $objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

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
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
     */
    public function decline(string $requestId, string $signerId, string $reason): array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $objectService  = $this->settingsService->getObjectService();
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signerObject   = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);

        if ($signerObject === null) {
            throw new RuntimeException('Signer record not found: '.$signerId);
        }

        if (is_object($signerObject) === true && method_exists($signerObject, 'jsonSerialize') === true) {
            $signer = $signerObject->jsonSerialize();
        } else {
            $signer = (array) $signerObject;
        }

        // C4 security fix: verify the signer record belongs to this signing
        // request. Without this check, an attacker who knows any valid
        // signerId can decline under an arbitrary requestId they do not own.
        if (($signer['signingRequestId'] ?? '') !== $requestId) {
            throw new RuntimeException('Signer record does not belong to this signing request');
        }

        // Security finding #282: ensure the authenticated user is the signer
        // they claim to be before allowing them to decline on its behalf.
        if (($signer['userId'] ?? '') !== $user->getUID()) {
            throw new RuntimeException('Not authorized to decline as this signer');
        }

        $signer['status']        = 'DECLINED';
        $signer['declineReason'] = $reason;
        $objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $requestObject = $objectService->find(id: $requestId, register: $register, schema: $schema);
        if ($requestObject !== null && is_object($requestObject) === true && method_exists($requestObject, 'jsonSerialize') === true) {
            $request = $requestObject->jsonSerialize();
        } else {
            $request = (array) $requestObject;
        }

        $signatureLevel = $request['signatureLevel'] ?? 'SES';
        $provider       = $request['provider'] ?? 'native';

        $request['status'] = 'DECLINED';
        $objectService->saveObject(object: $request, register: $register, schema: $schema);

        // Cross-app delegated-signing contract: a declined request is terminal —
        // emit SigningConcludedEvent (status=declined) for a delegated request.
        $this->emitConclusionIfDelegated(request: $request, status: 'declined');

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
     * @return array<string, mixed>|null The cancelled request, or null
     *                                   when the request is not found (or
     *                                   not accessible to a non-admin
     *                                   caller). Wilco #6 fix (docudesk#100,
     *                                   2026-06-06): callers must collapse
     *                                   null to a single 404 — never split
     *                                   into 404-vs-403, which would be an
     *                                   existence-probing oracle.
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
     */
    public function cancelRequest(string $requestId): ?array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        // Use getRequest() so not-found / access-denied collapse to the
        // same null shape. The controller already gated on initiator/admin
        // before calling us; a null here means truly not-found from the
        // service's perspective (Wilco #6 fix).
        $request = $this->getRequest(requestId: $requestId);
        if ($request === null) {
            return null;
        }

        if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'CANCELLED') === false) {
            throw new RuntimeException('Cannot cancel request in status: '.($request['status'] ?? 'unknown'));
        }

        $request['status'] = 'CANCELLED';
        $objectService->saveObject(object: $request, register: $register, schema: $schema);

        // Cross-app delegated-signing contract: a cancelled request is terminal
        // — emit SigningConcludedEvent (status=cancelled) for a delegated request.
        $this->emitConclusionIfDelegated(request: $request, status: 'cancelled');

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
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-4
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
                $request = $this->getRequest(requestId: $requestId);
                if ($request === null) {
                    // Treat null as not-found in bulk context (Wilco #6
                    // fix) — generic error, no existence leak.
                    $results[$requestId] = [
                        'success' => false,
                        'error'   => 'Request not accessible',
                    ];
                    continue;
                }

                $signerIds      = $request['signerIds'] ?? [];
                $targetSignerId = $this->findSignerForUser(signerIds: $signerIds, userId: $userId);

                $results[$requestId] = [
                    'success' => false,
                    'error'   => 'No pending signer record found for current user',
                ];
                if ($targetSignerId !== null) {
                    $results[$requestId] = [
                        'success' => true,
                        'signer'  => $this->sign(requestId: $requestId, signerId: $targetSignerId),
                    ];
                }
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
     * Validate a status transition
     *
     * @param string $currentStatus The current status
     * @param string $newStatus     The proposed new status
     *
     * @return bool True if transition is valid
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
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
     * @throws RuntimeException If validation fails
     */
    private function validateRequestData(array $data): void
    {
        if (empty($data['documentFileId']) === true) {
            throw new RuntimeException('Document file ID is required', 400);
        }

        if (empty($data['documentName']) === true) {
            throw new RuntimeException('Document name is required', 400);
        }

        if (in_array($data['signatureLevel'] ?? '', ['SES', 'AdES', 'QES'], true) === false) {
            throw new RuntimeException('Invalid signature level', 400);
        }

        if (in_array($data['signingMode'] ?? '', ['sequential', 'parallel'], true) === false) {
            throw new RuntimeException('Invalid signing mode', 400);
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
            $signerObj = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);
            if ($signerObj !== null && is_object($signerObj) === true && method_exists($signerObj, 'jsonSerialize') === true) {
                $signer = $signerObj->jsonSerialize();
            } else {
                $signer = (array) $signerObj;
            }

            if (($signer['status'] ?? '') !== 'SIGNED') {
                $allSigned = false;
                break;
            }
        }//end foreach

        $freshObj = $objectService->find(id: $requestId, register: $register, schema: $schema);
        if ($freshObj !== null && is_object($freshObj) === true && method_exists($freshObj, 'jsonSerialize') === true) {
            $freshRequest = $freshObj->jsonSerialize();
        } else {
            $freshRequest = (array) $freshObj;
        }

        $freshRequest['status'] = 'IN_PROGRESS';
        if ($allSigned === true) {
            $freshRequest['status'] = 'COMPLETED';
        }

        $objectService->saveObject(object: $freshRequest, register: $register, schema: $schema);

        // Cross-app delegated-signing contract: when all signers have signed the
        // request is COMPLETED — emit the terminal SigningConcludedEvent
        // (status=signed) for a delegated (provenance-carrying) request. The
        // signed document reference is the persisted documentFileId.
        if ($allSigned === true) {
            $this->emitConclusionIfDelegated(
                request: $freshRequest,
                status: 'signed',
                signedDocumentRef: ($freshRequest['documentFileId'] ?? null)
            );
        }

    }//end updateRequestStatus()

    /**
     * Emit a terminal SigningConcludedEvent for an expired request.
     *
     * Public entry point for the SigningExpirationJob, which marks requests
     * EXPIRED outside this service. Delegates to the shared fail-soft helper so
     * the cross-app contract has a single emission source. Only fires for a
     * delegated (provenance-carrying) request; internal requests emit nothing.
     *
     * @param array<string, mixed> $request The persisted (EXPIRED) signing-request array
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     *
     * @return void
     */
    public function emitExpiredConclusion(array $request): void
    {
        $this->emitConclusionIfDelegated(request: $request, status: 'expired');

    }//end emitExpiredConclusion()

    /**
     * Emit a SigningConcludedEvent when a delegated request concludes.
     *
     * Cross-app delegated-signing contract (docudesk-signing-events): only
     * fires for a signing request that carries provenance (`sourceApp` set and
     * non-empty) — internal DocuDesk requests emit nothing. The outcome
     * envelope is built from the persisted request fields (signers, signed
     * document reference) and dispatched via IEventDispatcher so the originating
     * consumer (e.g. shillinq) can run its own downstream side effects.
     * Fail-soft: any dispatch error is logged and the already-persisted
     * terminal transition is never rolled back.
     *
     * @param array<string, mixed> $request           The persisted (terminal) signing-request array
     * @param string               $status            Normalised status (signed|declined|expired|cancelled)
     * @param string|null          $signedDocumentRef Reference to the signed document, when signed
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     *
     * @return void
     */
    private function emitConclusionIfDelegated(array $request, string $status, ?string $signedDocumentRef=null): void
    {
        $sourceApp = (string) ($request['sourceApp'] ?? '');
        if ($sourceApp === '') {
            // Internal request (no consumer is waiting) — emit nothing.
            return;
        }

        try {
            $event = SigningConcludedEvent::fromRequest(
                request: $request,
                status: $status,
                signedDocumentRef: $signedDocumentRef
            );

            $this->eventDispatcher->dispatchTyped($event);

            $this->logger->info(
                'DocuDesk: dispatched SigningConcludedEvent',
                [
                    'signingRequestId' => $event->getSigningRequestId(),
                    'sourceApp'        => $sourceApp,
                    'status'           => $status,
                ]
            );
        } catch (\Throwable $e) {
            // The terminal transition has already persisted; a dispatch failure
            // must not roll it back.
            $this->logger->error(
                'DocuDesk: signing request concluded but SigningConcludedEvent dispatch failed',
                [
                    'sourceApp' => $sourceApp,
                    'status'    => $status,
                    'exception' => $e->getMessage(),
                ]
            );
        }//end try

    }//end emitConclusionIfDelegated()

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
            $signerObj = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);
            if ($signerObj !== null && is_object($signerObj) === true && method_exists($signerObj, 'jsonSerialize') === true) {
                $signer = $signerObj->jsonSerialize();
            } else {
                $signer = (array) $signerObj;
            }

            if (($signer['userId'] ?? '') === $userId && ($signer['status'] ?? '') === 'PENDING') {
                return $signerId;
            }
        }//end foreach

        return null;

    }//end findSignerForUser()

    /**
     * Normalise an ObjectService result to an array
     *
     * OpenRegister's ObjectService::saveObject()/find() return an ObjectEntity
     * instance, not a plain array. Callers that need array access must serialize
     * it first. This helper mirrors the pattern TemplateService already uses.
     *
     * @param mixed $object The ObjectEntity (or array) to normalise
     *
     * @return array<string, mixed> The serialized object
     */
    private function toArray(mixed $object): array
    {
        if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
            return $object->jsonSerialize();
        }

        return (array) $object;

    }//end toArray()

    /**
     * Get the client IP address
     *
     * @return string The client IP address
     */
    private function getClientIp(): string
    {
        return $this->request->getRemoteAddress();

    }//end getClientIp()
}//end class
