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
use OCP\Files\File;
use OCP\Files\IRootFolder;
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
 * Exceeds PHPMD's class-length threshold (1214 lines vs 1000). The class is the
 * whole signing-request lifecycle, and the bulk of those lines are the
 * documented per-state transition rules rather than logic density. This is
 * flagged as a candidate for a genuine split (extracting the provider-dispatch
 * and verification halves), which is a behavioural refactor and deliberately
 * not folded into a quality-gate change.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 * @spec openspec/specs/document-signing/spec.md
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
     * @param IRootFolder            $rootFolder          Root folder (reads the document, stores the signed version)
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
        private readonly IEventDispatcher $eventDispatcher,
        private readonly IRootFolder $rootFolder
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

        // Provider/level honesty at creation time (signing-trust-rebuild
        // REQ-DDSTR-002 point 1): reject an unsupported provider/level pair
        // with 400 BEFORE any object is persisted, so a QES request can never
        // be routed to a provider that will later silently complete it with a
        // lower-assurance (e.g. native SES) artifact.
        $this->validateProviderLevelPair(provider: (string) $request['provider'], level: (string) $request['signatureLevel']);

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
     * @param string                    $requestId     The signing request ID
     * @param string                    $signerId      The signer record ID
     * @param array<string, mixed>|null $verifiedActor The already-resolved, verified
     *                                                 external actor (portal-signing-actions
     *                                                 REQ-DDPSA-005): `email` (invited signer
     *                                                 identity), and optionally `subjectRef`,
     *                                                 `identityRef`, `trust`, `jti` from the
     *                                                 verified portal assertion. When null
     *                                                 (default) the actor is the Nextcloud
     *                                                 session user, exactly as before — this
     *                                                 parameter is an ADDITIVE seam, not a
     *                                                 behaviour change for existing callers.
     * @param array<string, mixed>|null $signatureData Optional evidence to record on the
     *                                                 signer record's `signatureData` field
     *                                                 (portal-signing-surface REQ-DDPSS-002:
     *                                                 consent confirmation + optional drawn
     *                                                 signature). Never trusted for identity.
     *
     * @return array<string, mixed> The updated signer record
     *
     * @throws RuntimeException If signing fails
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
     * @spec openspec/specs/portal-signing-actions/spec.md
     * @spec openspec/specs/portal-signing-surface/spec.md
     */
    public function sign(string $requestId, string $signerId, ?array $verifiedActor=null, ?array $signatureData=null): array
    {
        [$actorUserId, $actorDisplayName] = $this->resolveActingIdentity(verifiedActor: $verifiedActor);

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

        // Security finding #282 / portal-signing-actions REQ-DDPSA-005: ensure
        // the acting identity is the signer they claim to be — the Nextcloud
        // session user for an in-app signer, or the verified assertion's
        // resolved email for an external portal signer. Either way this check
        // is unchanged in spirit: an actor cannot sign on behalf of another
        // signer by supplying their signer ID.
        if ($this->actorMatchesSigner(signer: $signer, verifiedActor: $verifiedActor, actorUserId: $actorUserId) === false) {
            throw new RuntimeException('Not authorized to sign as this signer');
        }

        if (($signer['status'] ?? '') !== 'PENDING') {
            throw new RuntimeException('Signer has already responded to this request');
        }

        $now = new DateTimeImmutable();
        $signer['status']    = 'SIGNED';
        $signer['signedAt']  = $now->format(DateTimeInterface::ATOM);
        $signer['ipAddress'] = $this->getClientIp();
        if ($signatureData !== null) {
            // Portal-signing-surface REQ-DDPSS-002: consent confirmation +
            // optional drawn signature, recorded into the existing
            // `visible:false` field — never used for identity.
            $signer['signatureData'] = $signatureData;
        }

        $objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'SIGNED',
            actorUserId: $actorUserId,
            actorDisplayName: $actorDisplayName,
            ipAddress: $this->getClientIp(),
            signatureLevel: $request['signatureLevel'] ?? 'SES',
            provider: $request['provider'] ?? 'native',
            metadata: $this->actorAuditMetadata(verifiedActor: $verifiedActor)
        );

        $this->updateRequestStatus(requestId: $requestId, request: $request, verifiedActor: $verifiedActor);

        return $signer;

    }//end sign()

    /**
     * Decline a signing request
     *
     * @param string                    $requestId     The signing request ID
     * @param string                    $signerId      The signer record ID
     * @param string                    $reason        The decline reason
     * @param array<string, mixed>|null $verifiedActor The already-resolved, verified
     *                                                 external actor (see `sign()`); null
     *                                                 (default) behaves exactly as before.
     *
     * @return array<string, mixed> The updated signer record
     *
     * @throws RuntimeException If the request is not in a state that can be
     *                          declined, or the actor is not authorised.
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
     * @spec openspec/specs/document-signing/spec.md
     * @spec openspec/specs/portal-signing-actions/spec.md
     * @spec openspec/specs/portal-signing-surface/spec.md
     */
    public function decline(string $requestId, string $signerId, string $reason, ?array $verifiedActor=null): array
    {
        [$actorUserId, $actorDisplayName] = $this->resolveActingIdentity(verifiedActor: $verifiedActor);

        $objectService = $this->settingsService->getObjectService();

        // Load the request FIRST and gate the terminal-state status machine
        // BEFORE any signer/request mutation (signing-trust-rebuild
        // REQ-DDSTR-003, closing the #282 residual where decline() skipped
        // isValidTransition() entirely — a COMPLETED/CANCELLED/EXPIRED
        // request could still be flipped to DECLINED).
        $register      = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        $requestObject = $objectService->find(id: $requestId, register: $register, schema: $schema);
        if ($requestObject === null) {
            throw new RuntimeException('Signing request not found: '.$requestId);
        }

        $request = $this->toArray(object: $requestObject);

        if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'DECLINED') === false) {
            throw new RuntimeException('Cannot decline request in status: '.($request['status'] ?? 'unknown'));
        }

        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signerObject   = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);

        if ($signerObject === null) {
            throw new RuntimeException('Signer record not found: '.$signerId);
        }

        $signer = $this->toArray(object: $signerObject);

        // C4 security fix: verify the signer record belongs to this signing
        // request. Without this check, an attacker who knows any valid
        // signerId can decline under an arbitrary requestId they do not own.
        if (($signer['signingRequestId'] ?? '') !== $requestId) {
            throw new RuntimeException('Signer record does not belong to this signing request');
        }

        // Security finding #282 / portal-signing-actions REQ-DDPSA-005: same
        // acting-identity check as sign() — Nextcloud uid or verified
        // assertion email, never the request body.
        if ($this->actorMatchesSigner(signer: $signer, verifiedActor: $verifiedActor, actorUserId: $actorUserId) === false) {
            throw new RuntimeException('Not authorized to decline as this signer');
        }

        $signer['status']        = 'DECLINED';
        $signer['declineReason'] = $reason;
        $objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

        $signatureLevel = $request['signatureLevel'] ?? 'SES';
        $provider       = $request['provider'] ?? 'native';

        $request['status'] = 'DECLINED';
        $objectService->saveObject(object: $request, register: $register, schema: $schema);

        // Cross-app delegated-signing contract: a declined request is terminal —
        // emit SigningConcludedEvent (status=declined) for a delegated request.
        $this->emitConclusionIfDelegated(request: $request, status: 'declined');

        $metadata           = $this->actorAuditMetadata(verifiedActor: $verifiedActor);
        $metadata['reason'] = $reason;

        $this->auditService->logEvent(
            signingRequestId: $requestId,
            action: 'DECLINED',
            actorUserId: $actorUserId,
            actorDisplayName: $actorDisplayName,
            ipAddress: $this->getClientIp(),
            signatureLevel: $signatureLevel,
            provider: $provider,
            metadata: $metadata
        );

        return $signer;

    }//end decline()

    /**
     * Resolve the acting identity for `sign()`/`decline()`.
     *
     * Default (no verified actor): the Nextcloud session user — behaviour is
     * byte-identical to before this seam was added. With a verified actor
     * (portal-signing-actions REQ-DDPSA-005): the resolved portal signer's
     * email, never a Nextcloud uid.
     *
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     *
     * @return array{0: string, 1: string} [actorUserId, actorDisplayName]
     *
     * @throws RuntimeException When no verified actor is supplied and there is
     *                          no authenticated Nextcloud user.
     */
    private function resolveActingIdentity(?array $verifiedActor): array
    {
        if ($verifiedActor !== null) {
            $email = (string) ($verifiedActor['email'] ?? '');

            $displayName = 'External signer';
            if ($email !== '') {
                $displayName = $email;
            }

            // Namespaced so a portal actor identity can never collide with (or
            // be mistaken for) a Nextcloud uid in the audit trail.
            return ['portal:'.$email, $displayName];
        }

        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        return [$user->getUID(), $user->getDisplayName()];

    }//end resolveActingIdentity()

    /**
     * Check whether the acting identity matches the target signer record.
     *
     * @param array<string, mixed>      $signer        The loaded signer record.
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     * @param string                    $actorUserId   The resolved acting identity (unused for
     *                                                 the verified-actor branch; kept for the
     *                                                 in-app branch's exact pre-existing check).
     *
     * @return bool True when the actor is authorised to act as this signer.
     */
    private function actorMatchesSigner(array $signer, ?array $verifiedActor, string $actorUserId): bool
    {
        if ($verifiedActor !== null) {
            $signerEmail = (string) ($signer['email'] ?? '');
            $actorEmail  = (string) ($verifiedActor['email'] ?? '');

            return $signerEmail !== '' && $actorEmail !== '' && strcasecmp($signerEmail, $actorEmail) === 0;
        }

        return ($signer['userId'] ?? '') === $actorUserId;

    }//end actorMatchesSigner()

    /**
     * Build the audit `metadata` for a portal-originated act.
     *
     * Records the assertion `jti` so the portal act is traceable to its
     * originating portaliq session (portal-signing-actions REQ-DDPSA-005).
     * Returns an empty array for an in-app (Nextcloud session) actor.
     *
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     *
     * @return array<string, mixed>
     */
    private function actorAuditMetadata(?array $verifiedActor): array
    {
        if ($verifiedActor === null) {
            return [];
        }

        return ['portalJti' => (string) ($verifiedActor['jti'] ?? '')];

    }//end actorAuditMetadata()

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
     * Validate that the requested provider actually supports the requested level.
     *
     * Provider/level honesty at request creation (signing-trust-rebuild
     * REQ-DDSTR-002 point 1): an unknown provider, or a provider that does not
     * support the requested signature level (via
     * `SigningProviderInterface::supportsLevel()`), is rejected with HTTP 400
     * before anything is persisted — the completion path (REQ-DDSTR-002 point
     * 2) never has to silently substitute a provider or level because an
     * invalid pair can never be created in the first place.
     *
     * @param string $provider The requested provider identifier.
     * @param string $level    The requested signature level.
     *
     * @return void
     *
     * @throws RuntimeException With HTTP code 400 when the provider is unknown
     *                          or does not support the requested level.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    private function validateProviderLevelPair(string $provider, string $level): void
    {
        try {
            $providerInstance = $this->providerFactory->getProvider(identifier: $provider);
        } catch (\Throwable $e) {
            throw new RuntimeException('Unknown signing provider: '.$provider, 400);
        }

        if ($providerInstance->supportsLevel(level: $level) === false) {
            throw new RuntimeException(
                'Signing provider "'.$provider.'" does not support signature level "'.$level.'"',
                400
            );
        }

    }//end validateProviderLevelPair()

    /**
     * Update the signing request status based on signer progress
     *
     * @param string                    $requestId     The signing request ID
     * @param array<string, mixed>      $request       The current request data
     * @param array<string, mixed>|null $verifiedActor The verified external actor completing
     *                                                 this act, when portal-originated (see
     *                                                 `sign()`); threaded through to the
     *                                                 produced artifact's evidence binding
     *                                                 (portal-signing-surface REQ-DDPSS-004).
     *
     * @return void
     */
    private function updateRequestStatus(string $requestId, array $request, ?array $verifiedActor=null): void
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

        if ($allSigned === false) {
            $freshRequest['status'] = 'IN_PROGRESS';
            $objectService->saveObject(object: $freshRequest, register: $register, schema: $schema);
            return;
        }

        // All signers have signed — the request completes only if the active
        // provider can produce a verifiable signed artifact. This is the
        // honest-completion gate (issue #304): the request is NEVER marked
        // COMPLETED with the unsigned original as its signed reference. If the
        // artifact cannot be produced, the request stays IN_PROGRESS and the
        // failure surfaces loudly to the completing signer.
        // Step through IN_PROGRESS when the request is still PENDING.
        //
        // A single-signer request is fully signed the moment that one signer
        // signs, so control reaches here with the request still PENDING — the
        // `$allSigned === false` branch above, which is what normally moves it
        // to IN_PROGRESS, never ran. Writing COMPLETED straight from PENDING is
        // not a legal move: `self::STATUS_TRANSITIONS` allows PENDING only to
        // IN_PROGRESS / CANCELLED / EXPIRED, and the `x-openregister-lifecycle`
        // block on the signingRequest schema declares exactly the same edges
        // (`start`: PENDING -> IN_PROGRESS, `complete`: IN_PROGRESS ->
        // COMPLETED). OpenRegister enforces that declaration on save, so the
        // jump was rejected with 'No transition allows moving "status" from
        // "PENDING" to "COMPLETED"' and the whole sign action 500'd — every
        // single-signer request was impossible to complete.
        //
        // Take the declared `start` edge explicitly rather than widening the
        // state machine: "a signer has opened the request" is exactly what
        // happened, and the intermediate state is what the audit trail and the
        // list view expect to see.
        if (($freshRequest['status'] ?? '') === 'PENDING') {
            $freshRequest['status'] = 'IN_PROGRESS';
            $freshRequest           = $this->toArray(
                object: $objectService->saveObject(
                    object: $freshRequest,
                    register: $register,
                    schema: $schema
                )
            );
        }

        $signedDocumentRef = $this->produceAndStoreSignedArtifact(request: $freshRequest, verifiedActor: $verifiedActor);

        $freshRequest['status']            = 'COMPLETED';
        $freshRequest['signedDocumentRef'] = $signedDocumentRef;
        $objectService->saveObject(object: $freshRequest, register: $register, schema: $schema);

        // Cross-app delegated-signing contract: emit the terminal
        // SigningConcludedEvent (status=signed) for a delegated request. The
        // signed reference is the stored artifact (file id + version), never
        // the unsigned original documentFileId.
        $this->emitConclusionIfDelegated(
            request: $freshRequest,
            status: 'signed',
            signedDocumentRef: $signedDocumentRef
        );

    }//end updateRequestStatus()

    /**
     * Produce the signed artifact and store it as a new Nextcloud file version.
     *
     * Resolves the active provider (via SigningProviderFactory), reads the
     * original document bytes, asks the provider to produce the signed artifact,
     * and writes the result back to the same Nextcloud file — which creates a
     * new file version of the prior content via `files_versions`. Returns a
     * reference to the stored artifact (`<fileId>:<versionMtime>`).
     *
     * Honest-completion gate: any failure (no file, unreadable, provider cannot
     * produce an artifact) throws, so the caller never marks the request
     * COMPLETED without a real signed document. Provider/level honesty
     * (signing-trust-rebuild REQ-DDSTR-002 point 2): the request's named
     * provider is resolved STRICTLY — an unknown provider name fails the
     * completion loudly and is never silently substituted with
     * `getActiveProvider()` / the native provider.
     *
     * @param array<string, mixed>      $request       The completing signing-request array.
     * @param array<string, mixed>|null $verifiedActor The verified external actor completing
     *                                                 this act, when portal-originated —
     *                                                 folded into the produced artifact's
     *                                                 evidence binding (portal-signing-surface
     *                                                 REQ-DDPSS-004).
     *
     * @return string The stored signed-artifact reference (file id + version).
     *
     * @throws RuntimeException When no verifiable artifact can be produced/stored,
     *                          or when the request names an unregistered provider.
     *
     * @spec openspec/specs/document-signing/spec.md
     * @spec openspec/specs/portal-signing-surface/spec.md
     */
    private function produceAndStoreSignedArtifact(array $request, ?array $verifiedActor=null): string
    {
        $fileId = (int) ($request['documentFileId'] ?? 0);
        if ($fileId <= 0) {
            throw new RuntimeException('Cannot produce a signed artifact: the request has no document file id');
        }

        $file = $this->resolveDocumentFile(fileId: $fileId, request: $request);

        try {
            $originalContent = $file->getContent();
        } catch (\Throwable $e) {
            throw new RuntimeException('Cannot read the document to sign: '.$e->getMessage());
        }

        // Provider/level honesty (REQ-DDSTR-002 point 2): resolve the request's
        // named provider strictly. An unknown provider name MUST fail the
        // completion loudly — no fallback to getActiveProvider()/native. This
        // is the honest-completion gate closing the #304 residual where a
        // request naming a misconfigured/unregistered provider silently
        // completed with a substituted (native) artifact.
        $providerName = (string) ($request['provider'] ?? 'native');
        $provider     = $this->providerFactory->getProvider(identifier: $providerName);

        $context = [
            'signer'    => $this->resolveSignerLabel(verifiedActor: $verifiedActor),
            'signers'   => ($request['signerIds'] ?? []),
            'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'ip'        => $this->getClientIp(),
            'level'     => (string) ($request['signatureLevel'] ?? 'SES'),
        ];

        if ($verifiedActor !== null) {
            // Portal-signature evidence binding (portal-signing-surface
            // REQ-DDPSS-004): fold the verified assertion's portal subject
            // claims into the provider context so they land inside the SAME
            // MAC as the rest of the assertion. Sourced ONLY from the
            // already-verified actor (never the request body).
            $portalFieldMap = [
                'subjectRef'  => 'portalSubjectRef',
                'identityRef' => 'portalIdentityRef',
                'trust'       => 'portalTrust',
                'jti'         => 'portalJti',
            ];

            foreach ($portalFieldMap as $actorKey => $contextKey) {
                if (empty($verifiedActor[$actorKey]) === false) {
                    $context[$contextKey] = (string) $verifiedActor[$actorKey];
                }
            }
        }

        $signedBytes = $provider->produceSignedArtifact(documentContent: $originalContent, context: $context);

        // Writing new content to the existing file creates a new Nextcloud file
        // version of the prior (unsigned) content automatically (files_versions).
        try {
            $file->putContent($signedBytes);
        } catch (\Throwable $e) {
            throw new RuntimeException('Cannot store the signed artifact as a new file version: '.$e->getMessage());
        }

        // The signed-artifact reference is the file id plus a content-derived
        // version tag identifying this specific signed version — never the bare
        // original file id.
        return $fileId.':signed:'.substr(hash('sha256', $signedBytes), 0, 16);

    }//end produceAndStoreSignedArtifact()

    /**
     * Resolve the document File node for the signing request.
     *
     * Resolves through the initiator's user folder (the request owner), falling
     * back to the current signer's folder — either way a node that is not a
     * readable/writeable file throws rather than silently skipping the artifact.
     *
     * @param int                  $fileId  The Nextcloud file id.
     * @param array<string, mixed> $request The signing-request array.
     *
     * @return File The resolved file node.
     *
     * @throws RuntimeException When the file cannot be resolved.
     */
    private function resolveDocumentFile(int $fileId, array $request): File
    {
        $candidates = [];
        $initiator  = (string) ($request['initiatorUserId'] ?? '');
        if ($initiator !== '') {
            $candidates[] = $initiator;
        }

        $current = $this->userSession->getUser();
        if ($current !== null) {
            $candidates[] = $current->getUID();
        }

        foreach (array_unique($candidates) as $uid) {
            try {
                $nodes = $this->rootFolder->getUserFolder($uid)->getById($fileId);
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($nodes as $node) {
                if ($node instanceof File) {
                    return $node;
                }
            }
        }

        throw new RuntimeException('Cannot resolve the document file to sign: '.$fileId);

    }//end resolveDocumentFile()

    /**
     * Resolve a human label for the completing signer.
     *
     * @param array<string, mixed>|null $verifiedActor The verified external actor completing
     *                                                 this act, when portal-originated.
     *
     * @return string The signer display name, verified portal email, UID, or 'Unknown'.
     */
    private function resolveSignerLabel(?array $verifiedActor=null): string
    {
        if ($verifiedActor !== null) {
            $email = (string) ($verifiedActor['email'] ?? '');
            if ($email !== '') {
                return $email;
            }

            return 'External signer';
        }

        $user = $this->userSession->getUser();
        if ($user === null) {
            return 'Unknown';
        }

        // IUser::getDisplayName() is guaranteed non-empty by contract — it
        // falls back to the UID itself when no display name is set.
        return $user->getDisplayName();

    }//end resolveSignerLabel()

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
