<?php

/**
 * Signing Service
 *
 * Orchestrates the signing request lifecycle.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Exception\SigningRequestWithdrawnException;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\SigningAuditActor;
use OCA\Filinq\Kernel\SigningAuditEvent;
use OCA\Filinq\Kernel\SigningAuditSubject;
use OCA\Filinq\Service\Signing\FieldValueChecker;
use OCA\Filinq\Service\Signing\PortalSignatureAssurance;
use OCA\Filinq\Service\Signing\RecipientRole;
use OCA\Filinq\Service\Signing\SignerEntries;
use OCA\Filinq\Service\Signing\SigningAttribution;
use OCA\Filinq\Service\Signing\SigningAct;
use OCA\Filinq\Service\Signing\SigningTurn;
use OCA\Filinq\Service\Support\SigningGuards;
use OCA\Filinq\Service\Support\SigningOutputs;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\SaveActor;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use RuntimeException;

/**
 * Service for managing signing request lifecycle
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * The request validator, the guardian consent rule, the identity gate and the
 * mandate reach this class as one SigningGuards bundle; the audit trail, the
 * artifact producer and the conclusion emitter as one SigningOutputs bundle.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The audit value objects, the
 * signing act and the portal assurance this class builds count as well as its
 * four collaborators; inlining them elsewhere would split one lifecycle.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods) Each method is one step of the signing request lifecycle
 * (create, sign, accept, decline, progress); splitting the class would spread that lifecycle over
 * two.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods) Each public method is one lifecycle operation of
 * SigningServiceInterface; accept() is the accept-only recipient's counterpart of sign().
 *
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SigningService implements SigningServiceInterface {

	/**
	 * The refusal when an accept-only recipient tries to sign.
	 */
	public const ACCEPT_NOT_SIGN = 'This document asks you to accept it, not to sign it';

	/**
	 * The refusal when a recipient who signs tries to accept.
	 */
	public const SIGN_NOT_ACCEPT = 'This document asks you to sign it, not to accept it';

	/**
	 * How an agent's request is attributed: started via mcp, through Hermiq's approval gate.
	 *
	 * @var string
	 */
	private const VIA_MCP = 'mcp';

	/**
	 * The app a lifecycle move made on filinq's behalf is recorded against.
	 */
	private const APP_ID = 'filinq';

	/**
	 * The `signingRequest` lifecycle's transition to CANCELLED.
	 */
	private const CANCEL_TRANSITION = 'cancel';

	/**
	 * Valid status transitions for signing requests
	 *
	 * @var array<string, array<string>>
	 */
	private const STATUS_TRANSITIONS = [
		'DRAFT' => ['PENDING', 'CANCELLED'],
		'PENDING' => ['IN_PROGRESS', 'DECLINED', 'CANCELLED', 'EXPIRED'],
		'IN_PROGRESS' => ['COMPLETED', 'DECLINED', 'CANCELLED', 'EXPIRED'],
		'COMPLETED' => [],
		'DECLINED' => [],
		'EXPIRED' => [],
		'CANCELLED' => [],
	];

	/**
	 * Constructor
	 *
	 * @param SettingsService $settingsService Settings service
	 * @param SigningActorResolver $actorResolver Resolves the acting identity + authorises it
	 * @param SigningGuards $guards The request validator, guardian consent rule, identity gate and mandate
	 * @param SigningOutputs $outputs The audit trail, signed artifact producer and conclusion emitter
	 * @param FieldValueChecker $fieldValueChecker Checks what a signer typed into the fields placed for them
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly SigningActorResolver $actorResolver,
		private readonly SigningGuards $guards,
		private readonly SigningOutputs $outputs,
		private readonly FieldValueChecker $fieldValueChecker = new FieldValueChecker(),
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
		'subjectNoun',
		'externalReference',
		'correlationId',
	];

	// The attribute stands before the docblock for the reason DocumentAgentService
	// gives: the spec-coverage gate cannot walk past a multi-line attribute's `)]`.
	//
	// This is the only signing method an agent may reach, and only to START a
	// request: createRequest() (the form, bulk and envelope paths), sign(),
	// decline(), bulkSign(), cancelRequest() and every class under
	// Service/Signing/ stay attribute-free. It is a separate entry point so the
	// request it creates says it came via mcp; createRequest() cannot tell an
	// agent's call from the form's. The write hints route the tool
	// through Hermiq's human-approval gate; Filinq adds no gate of its own.

	#[McpTool(
		name: 'startSigningRequest',
		subject: 'signingRequest',
		action: 'create',
		description: 'Start a signing request for a document in your Files: give documentFileId, the signers '
			. '(each with a name and email) and optionally signatureLevel (SES/AES/QES), signingMode '
			. '(sequential/parallel), provider and deadline. Creates the request and notifies the signers, '
			. 'so a person must approve the resolved request first. It never signs, declines or cancels.',
		readOnlyHint: false,
		destructiveHint: false,
		idempotentHint: false,
		scope: 'create'
	)]
	/**
	 * Start a signing request on an agent's behalf, attributed `mcp`.
	 *
	 * The same creation createRequest() runs, as the signed-in user (the
	 * invoking principal), with every validation unchanged. The persisted
	 * request carries `initiatedVia: mcp` and its CREATED audit entry records
	 * `via: mcp` beside the actor, so an agent-started request is told apart
	 * from one started in Filinq. Neither can be set through the request data.
	 *
	 * @param array<string, mixed> $data The signing request data, as createRequest() takes it.
	 *
	 * @return array<string, mixed> The created signing request.
	 *
	 * @throws RuntimeException If creation fails.
	 * @throws \InvalidArgumentException With code 400 when the request or a guardian entry is invalid.
	 *
	 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-an-approval-gated-signing-request-initiation-tool-exists-req-ddhat-001
	 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-agent-initiated-writes-are-attributable-req-ddhat-003
	 */
	public function startSigningRequest(array $data): array {
		return $this->createRequest(data: $data, via: self::VIA_MCP);

	}//end startSigningRequest()

	/**
	 * Create a new signing request
	 *
	 * @param array<string, mixed> $data The signing request data
	 * @param string               $via  How it was started: `mcp` from startSigningRequest(), ''
	 *                                   (the default) for Filinq's own paths. The request data
	 *                                   cannot set it.
	 *
	 * @return array<string, mixed> The created signing request
	 *
	 * @throws RuntimeException If creation fails
	 * @throws \InvalidArgumentException With code 400 when a guardian entry is invalid,
	 *                                   or a signer under the age of consent has no guardian.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function createRequest(array $data, string $via=''): array {
		// Throws RuntimeException('No authenticated user') when there is no session user.
		[$initiatorUserId, $initiatorDisplayName] = $this->actorResolver->resolveActingIdentity();

		$objectService = $this->settingsService->getObjectService();

		// SettingsService owns these keys and defaults ('30', 'SES', 'native'); getFeatureToggles()
		// is the cheap accessor (plain IAppConfig reads, no register discovery), safe on a write path.
		$toggles = $this->settingsService->getFeatureToggles();
		$expiryDays = (int)$toggles['signing_request_expiry_days'];
		$deadline = (new DateTimeImmutable())->modify('+' . $expiryDays . ' days');
		$defaultLevel = (string)$toggles['signing_default_level'];
		$defaultProv = (string)$toggles['signing_provider'];

		$request = [
			'documentFileId' => $data['documentFileId'] ?? '',
			'documentName' => $data['documentName'] ?? '',
			'initiatorUserId' => $initiatorUserId,
			'signatureLevel' => $data['signatureLevel'] ?? $defaultLevel,
			'signingMode' => $data['signingMode'] ?? 'sequential',
			'status' => 'PENDING',
			'provider' => $data['provider'] ?? $defaultProv,
			'deadline' => $data['deadline'] ?? $deadline->format(DateTimeInterface::ATOM),
			'signerIds' => [],
		];

		$this->guards->validator->validateRequestData(data: $request);

		// Provider/level honesty at creation time (signing-trust-rebuild
		// REQ-DDSTR-002 point 1): reject an unsupported provider/level pair
		// with 400 BEFORE any object is persisted, so a QES request can never
		// be routed to a provider that will later silently complete it with a
		// lower-assurance (e.g. native SES) artifact.
		$this->guards->validator->validateProviderLevelPair(provider: (string)$request['provider'], level: (string)$request['signatureLevel']);

		// Guardian consent (signer-identity-rails REQ-DDSIR-008/009): every
		// request records the age of consent that governs it, and the signer
		// entries are validated and linked BEFORE anything is persisted. A
		// request naming a signer under that age without a guardian is refused
		// with a 400 and leaves no object behind.
		$request['guardianConsentAge'] = $this->guards->consentGuard->appliedAge(data: $data);
		// Required assurance, never below the level's floor (REQ-DDSIR-002).
		$request = $this->guards->assuranceGate->applyToRequest(request: $request, data: $data);
		$signers = (array)($data['signers'] ?? []);

		// A request that names nobody who can sign is refused before anything
		// is stored, instead of being saved as PENDING for ever (#1209).
		$this->guards->validator->validateSigners(signers: $signers);
		// A request of accept-only recipients signs nothing (signing-accept-only-recipient D1).
		RecipientRole::assertSomeoneSigns(entries: $signers);
		$prepared = $this->guards->consentGuard->prepareSigners(signers: $signers, age: $request['guardianConsentAge'], now: new DateTimeImmutable());

		$request = $this->withInitiatorDocument(request: $request);
		// Cross-app delegated-signing contract (filinq-signing-events): when a
		// consumer raised this request through DocumentSigningRequestedEvent it
		// carries provenance fields. Persist any present provenance onto the
		// signing-request object (additive/optional) so the terminal
		// SigningConcludedEvent can correlate back to the originating consumer.
		// Internal requests omit these and are unaffected.
		$request = $this->withProvenance(request: $request, data: $data);
		$request = $this->guards->validator->withPlacements(request: $request, data: $data, producer: $this->outputs->artifactProducer);
		$request = $this->outputs->artifactProducer->delegate(request: $request, signers: $signers);
		$request = (new SigningAttribution(via: $via))->onto(request: $request);

		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$savedRequest = $objectService->saveObject(
			object: $request,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema), actor: new SaveActor(unowned: true)),
			_rbac: false
		);
		$createdRequest = $this->toArray(object: $savedRequest);

		$requestId = $createdRequest['id'] ?? $createdRequest['uuid'] ?? '';
		$createdRequest['signerIds'] = $this->persistSigners(
			requestId: (string)$requestId,
			entries: new SignerEntries(entries: $signers, prepared: $prepared)
		);
		$objectService->saveObject(
			object: $createdRequest,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema)),
			_rbac: false
		);
		$createdRequest['assuranceFloor'] = $this->guards->assuranceGate->floorFor(request: $request);

		$this->logCreated(
			request: $request,
			requestId: (string)$requestId,
			initiator: [$initiatorUserId, $initiatorDisplayName],
			attribution: new SigningAttribution(via: $via)
		);
		$this->mailTurn(request: $createdRequest, after: null);

		return $createdRequest;
	}//end createRequest()

	/**
	 * Write the CREATED audit entry of a new request, with how it was started.
	 *
	 * @param array<string, mixed>     $request     The created request.
	 * @param string                   $requestId   Its id.
	 * @param array{0: string, 1: string} $initiator   The initiator's user id and display name.
	 * @param SigningAttribution       $attribution How the request was started.
	 *
	 * @return void
	 */
	private function logCreated(array $request, string $requestId, array $initiator, SigningAttribution $attribution): void {
		$this->outputs->auditService->logEvent(
			event: new SigningAuditEvent(
				request: new SigningAuditSubject(id: $requestId, signatureLevel: $request['signatureLevel'], provider: $request['provider']),
				action: 'CREATED',
				actor: new SigningAuditActor(
					userId: $initiator[0],
					displayName: $initiator[1],
					ipAddress: $this->actorResolver->getClientIp()
				),
				metadata: $attribution->auditMetadata()
			)
		);

	}//end logCreated()

	/**
	 * Copy the provenance a consuming app sent onto the request.
	 *
	 * @param array<string, mixed> $request The request being created.
	 * @param array<string, mixed> $data    What the consumer sent.
	 *
	 * @return array<string, mixed> The request with any provenance fields set.
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 */
	private function withProvenance(array $request, array $data): array {
		foreach ([...self::PROVENANCE_FIELDS, 'envelopeRef'] as $field) {
			if (empty($data[$field]) === false) {
				$request[$field] = $data[$field];
			}
		}

		return $request;

	}//end withProvenance()

	/**
	 * Point the request at the file as its initiator sees it.
	 *
	 * The file is resolved in the initiator's own folder and its name taken
	 * from the node: a request may only name a file its initiator can read.
	 *
	 * @param array<string, mixed> $request The request being created.
	 *
	 * @return array<string, mixed> The request with the file's own id and name.
	 *
	 * @throws RuntimeException With code 403 when the initiator cannot read the file.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	private function withInitiatorDocument(array $request): array {
		$document = $this->outputs->artifactProducer->initiatorDocument(
			fileId: (string)$request['documentFileId'],
			initiatorUserId: (string)$request['initiatorUserId']
		);
		$request['documentFileId'] = (string)$document->getId();
		$request['documentName'] = $document->getName();

		return $request;

	}//end withInitiatorDocument()

	/**
	 * Persist the signer records of a new request, guardians last.
	 *
	 * A guardian's record points at the signer it stands beside by record id,
	 * which exists only once that signer is saved. Signers are therefore saved
	 * first and guardians after; the returned ids keep the order of the entries.
	 *
	 * @param string $requestId The id of the saved signing request.
	 * @param SignerEntries $entries The signer entries, their guardian fields and guardian-to-signer links.
	 *
	 * @return list<string> The signer record ids, in entry order.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 *
	 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	private function persistSigners(string $requestId, SignerEntries $entries): array {
		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signers = $entries->entries;
		$links = $entries->links;
		$ids = [];
		$guardiansLast = array_diff_key($signers, $links) + array_intersect_key($signers, $links);

		foreach ($guardiansLast as $index => $signerData) {
			$signerData = (array)$signerData;
			$signerRecord = array_merge(
				[
					'signingRequestId' => $requestId,
					'userId' => $signerData['userId'] ?? '',
					'displayName' => $signerData['displayName'] ?? '',
					'order' => $signerData['order'] ?? $index,
					'status' => 'PENDING',
				],
				($entries->fields[$index] ?? []),
				// A signer named by user id alone has no address: `format: email` refuses ''.
				// An envelope's signer records carry envelopeRef, so the per-document notification skips them.
				array_filter(
					[
						'email' => trim((string) ($signerData['email'] ?? '')),
						'envelopeRef' => (string) ($signerData['envelopeRef'] ?? ''),
					]
				)
			);

			if (isset($links[$index]) === true) {
				$signerRecord['guardianForSignerId'] = $ids[$links[$index]];
			}

			// An accept-only recipient (signing-accept-only-recipient D1).
			if (RecipientRole::accepts(recipient: $signerData) === true) {
				$signerRecord['role'] = RecipientRole::ACCEPT;
			}

			$created = $this->toArray(
				object: $objectService->saveObject(
					object: $signerRecord,
					options: new ObjectSaveOptions(
						target: new ObjectFindTarget(register: $signerRegister, schema: $signerSchema),
						actor: new SaveActor(unowned: true)
					),
					_rbac: false
				)
			);
			$ids[$index] = (string)($created['id'] ?? $created['uuid'] ?? '');
		}//end foreach

		// Back into entry order: array_replace() keeps the key order of its first argument.
		return array_values(array_replace(array_fill_keys(array_keys($signers), ''), $ids));

	}//end persistSigners()

	/**
	 * Get a signing request by ID
	 *
	 * Access control: a SCOPED caller (non-empty $callerUserId) must be the
	 * initiator or a listed signer. Pass callerUserId='' to read UNSCOPED —
	 * that is the single, explicit bypass, used by an NC admin caller and by
	 * internal methods that have already verified access.
	 *
	 * There is deliberately no separate `isAdmin` flag: the previous guard was
	 * `$callerUserId !== '' && $isAdmin === false`, so an admin caller and an
	 * unscoped caller already took the identical path. Collapsing the two
	 * spellings into one means there is exactly ONE way to bypass scoping, and
	 * it is visible at the call site.
	 *
	 * @param string $requestId The signing request ID
	 * @param string $callerUserId UID to scope the read to ('' = unscoped)
	 *
	 * @return array<string, mixed>|null The signing request, or null when a
	 *                                   scoped caller (callerUserId set,
	 *                                   non-admin) is neither initiator nor
	 *                                   signer, or the request does not exist.
	 *
	 * @throws RegisterNotConfiguredException When the signing register is not configured.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
	 */
	public function getRequest(string $requestId, string $callerUserId = ''): ?array {
		$objectService = $this->settingsService->getObjectService();
		// Gate-50 did not flag this pair (the null-check below sits inside its
		// window), but it carries the same defect: a find() against register ''
		// returns null, and this method reports null as "not found". An
		// unconfigured instance therefore answered 404 for every signing
		// request that does exist.
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

		$object = $objectService->find(id: $requestId, options: new ObjectFindOptions(register: $register, schema: $schema), _rbac: false);
		if ($object === null) {
			// The same null as for a request that is not the caller's, so no
			// answer tells "does not exist" from "not yours".
			return null;
		}

		$request = $this->toArray(object: $object);

		// WF2 + Wilco #6 fix: scope single-record access to initiator or
		// signer. Access denied collapses to null (same shape as not-found),
		// so the controller can emit one 404 regardless.
		if ($this->actorResolver->mayRead(request: $request, requestId: $requestId, userId: $callerUserId) === false) {
			return null;
		}

		return $request;
	}//end getRequest()

	/**
	 * List signing requests scoped to the calling user
	 *
	 * A SCOPED caller (non-empty $callerUserId) sees only requests where they
	 * are the initiator or a listed signer (WF2 fix: previously returned all
	 * requests regardless of ownership — full cross-tenant data disclosure).
	 * Pass callerUserId='' to list UNSCOPED — that is the single, explicit
	 * bypass, used by an NC admin caller.
	 *
	 * As in getRequest(), there is deliberately no separate `isAdmin` flag:
	 * the previous guard was `$isAdmin === false && $callerUserId !== ''`, so
	 * an admin caller and an unscoped caller already took the identical path.
	 *
	 * @param string $callerUserId UID to scope the listing to ('' = unscoped)
	 *
	 * @return array<int, array<string, mixed>> List of signing requests
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
	 */
	public function listRequests(string $callerUserId = ''): array {
		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

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

		$paginated = $objectService->searchObjectsPaginated(query: $query, _rbac: false);
		$results = ($paginated['results'] ?? []);

		$signedRequestIds = $this->actorResolver->requestIdsForSigner(userId: $callerUserId);

		$requests = [];
		foreach ($results as $result) {
			$item = $this->toArray(object: $result);

			// WF2 security fix: a scoped caller sees only requests they
			// initiated or are listed as a signer on. Unscoped sees all.
			if ($this->actorResolver->isListedFor(request: $item, userId: $callerUserId, signedRequestIds: $signedRequestIds) === false) {
				continue;
			}

			$requests[] = $item;
		}

		return $requests;
	}//end listRequests()

	/**
	 * Sign a document within a signing request
	 *
	 * @param string $requestId The signing request ID
	 * @param string $signerId The signer record ID
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
	 * @param array<array-key, mixed>|null $fields     What the signer typed into the text fields
	 *                                                 placed for them, keyed by placement position
	 *                                                 (signing-field-validation REQ-SFV-003); null
	 *                                                 when the act carries no fields.
	 *
	 * @return array<string, mixed> The updated signer record
	 *
	 * @throws RuntimeException If signing fails
	 * @throws \OCA\Filinq\Exception\StepUpRequiredException With code 403, before any write, when the
	 *                                                         identity evidence is missing, stale, from an
	 *                                                         unregistered provider or below the assurance
	 *                                                         this signer needs (signer-identity-rails
	 *                                                         REQ-DDSIR-003). The evidence goes onto the
	 *                                                         record and the SIGNED audit entry.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
	 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-2.2
	 */
	public function sign(string $requestId, string $signerId, ?array $verifiedActor = null, ?array $signatureData = null, ?array $fields = null): array {
		return $this->signAct(
			act: [
				'requestId'     => $requestId,
				'signerId'      => $signerId,
				'verifiedActor' => $verifiedActor,
				'signatureData' => $signatureData,
				'onBehalfOf'    => null,
				'fields'        => $fields,
			]
		);
	}//end sign()

	/**
	 * Sign, as a delegate, a document that waits for the signer who delegated to them.
	 *
	 * The session user is the delegate. The delegation must cover today and the
	 * document's type, or the signature is refused naming the delegation, before
	 * anything is written. The signer record keeps both names: `signedBy` is the
	 * delegate, `signedOnBehalfOf` the signer; the audit entry is the delegate's.
	 *
	 * @param string $requestId The signing request ID
	 * @param string $signerId  The signer record ID of the signer who delegated
	 * @param string $delegator The signer who delegated
	 *
	 * @return array<string, mixed> The updated signer record
	 *
	 * @throws RuntimeException When no delegation covers it, or signing fails
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function signOnBehalfOf(string $requestId, string $signerId, string $delegator): array {
		if (trim($delegator) === '') {
			throw new RuntimeException('A delegated signature names the signer it is for');
		}

		return $this->signAct(
			act: [
				'requestId'     => $requestId,
				'signerId'      => $signerId,
				'verifiedActor' => null,
				'signatureData' => null,
				'onBehalfOf'    => $delegator,
				'fields'        => null,
			]
		);
	}//end signOnBehalfOf()

	/**
	 * The signing act behind sign() and signOnBehalfOf().
	 *
	 * The act holds the signing request id (`requestId`), the signer record id
	 * (`signerId`), the verified external actor or null (`verifiedActor`), the
	 * evidence for `signatureData` or null, and the signer a delegate signs for
	 * or null (`onBehalfOf`), and what the signer typed into the fields placed
	 * for them or null (`fields`).
	 *
	 * @param array<string, mixed> $act The act.
	 *
	 * @return array<string, mixed> The updated signer record
	 *
	 * @throws RuntimeException If signing fails
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 */
	private function signAct(array $act): array {
		$requestId     = $act['requestId'];
		$signerId      = $act['signerId'];
		$verifiedActor = $act['verifiedActor'];
		$signatureData = $act['signatureData'];
		$onBehalfOf    = $act['onBehalfOf'];
		$fields        = $act['fields'] ?? null;
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity(verifiedActor: $verifiedActor);

		// A delegated signature is refused before anything is read unless a
		// delegation from the signer to this user covers today (REQ-SCR-003).
		$signerUserId = $this->delegatedSignerUserId(onBehalfOf: $onBehalfOf, actorUserId: $actorUserId);

		$objectService = $this->settingsService->getObjectService();

		// Authorise the signer first, so someone who is not the signer learns
		// neither whether the request exists nor what its status is.
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $signerUserId,
			action: 'sign'
		);

		// Accepting is not signing: an accept-only recipient accepts (D2).
		if (RecipientRole::accepts(recipient: $signer) === true) {
			throw new RuntimeException(self::ACCEPT_NOT_SIGN, 422);
		}

		$request = $this->loadSignableRequest(requestId: $requestId);

		$this->assertDelegationCoversType(onBehalfOf: $onBehalfOf, actorUserId: $actorUserId, request: $request);

		// The mandate is declared in Nextcloud groups, which an invited portal
		// signer is not in; the portal path has its own verified-assertion gate.
		// A delegate exercises the signer's own authority, so the signer's mandate applies.
		if ($this->guards->mandateService !== null && $verifiedActor === null) {
			$this->guards->mandateService->assertMaySign(request: $request, userId: $signerUserId);
		}

		$act = new SigningAct(
			request: $request,
			target: ['requestId' => $requestId, 'signerId' => $signerId],
			actor: ['userId' => $actorUserId, 'displayName' => $actorDisplayName, 'verified' => $verifiedActor],
			signatureData: $signatureData
		);

		if (($signer['status'] ?? '') !== 'PENDING') {
			throw new RuntimeException('Signer has already responded to this request');
		}

		// Before any evidence is taken or anything is written: a sequential
		// signer whose turn has not come is refused outright.
		if ((string)($request['signingMode'] ?? '') !== SigningTurn::PARALLEL) {
			SigningTurn::assertTurn(
				request: $request,
				signerId: $signerId,
				signers: $this->signerRecords(request: $request, known: [$signerId => $signer])
			);
		}
		// Typed field values are checked before anything is written (REQ-SFV-003).
		$typed = $this->checkBeforeWrite(request: $request, signerId: $signerId, fields: $fields);

		$signer['identityEvidence'] = $this->guards->assuranceGate->evidenceForAct(
			request: $request + ['id' => $requestId],
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			now: $act->now
		)->toArray();
		$consent = $this->guards->consentGuard->guardSigningAct(
			requestId: $requestId,
			request: $request,
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			now: $act->now
		);

		$signer = $act->stampSigned(signer: array_merge($signer, $consent['record']), ipAddress: $this->actorResolver->getClientIp());
		$signer = (new PortalSignatureAssurance())->recordOn(signer: $signer, request: $request, verifiedActor: $verifiedActor);
		$signer = array_merge($signer, $typed);
		$signer    = $this->stampDelegation(signer: $signer, onBehalfOf: $onBehalfOf, actorUserId: $actorUserId);
		$delegated = array_filter(['signedOnBehalfOf' => $onBehalfOf], static fn ($value): bool => $value !== null);

		$objectService->saveObject(
			object: $signer,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $signerRegister, schema: $signerSchema)),
			_rbac: false
		);

		$this->logSigned(act: $act, metadata: array_merge($consent['audit'], ['identityEvidence' => $signer['identityEvidence']], $delegated));
		$this->guards->assuranceGate->consume(requestId: $requestId, signerId: $signerId);
		$this->updateRequestStatus(act: $act);

		return $signer;
	}//end signAct()

	/**
	 * What a signature checks while nothing is written yet: its typed field
	 * values (REQ-SFV-003), and that the request's provider could complete
	 * it at all. Without the second, a native request on a server with no
	 * signing secret stored the signature and its SIGNED audit entry and then
	 * failed at completion, leaving a request no signer could finish.
	 *
	 * @param array<string, mixed>      $request  The signing request.
	 * @param string                    $signerId The signer record id.
	 * @param array<string, mixed>|null $fields   The typed field values sent with the act.
	 *
	 * @return array<string, mixed> The checked typed values to store on the signer.
	 *
	 * @throws RuntimeException When a value is refused or no artifact could be produced.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	private function checkBeforeWrite(array $request, string $signerId, ?array $fields): array {
		$typed = $this->fieldValueChecker->valuesForAct(request: $request, signerId: $signerId, fields: $fields);
		$this->outputs->artifactProducer->assertCanProduce(request: $request);

		return $typed;
	}//end checkBeforeWrite()

	/**
	 * The user whose signing authority a signature exercises.
	 *
	 * Without a delegator that is the acting user. With one, delegated signing
	 * must be available and a delegation from the delegator to the acting user
	 * must cover today, or the signature is refused (REQ-SCR-003).
	 *
	 * @param string|null $onBehalfOf  The signer a delegate signs for, or null
	 * @param string      $actorUserId The acting user
	 *
	 * @return string The signer's user ID
	 *
	 * @throws RuntimeException When no delegation covers it
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function delegatedSignerUserId(?string $onBehalfOf, string $actorUserId): string {
		if ($onBehalfOf === null) {
			return $actorUserId;
		}

		if ($this->guards->delegations === null) {
			throw new RuntimeException('Signing refused: delegated signing is not available');
		}

		$this->guards->delegations->assertMayActFor(delegator: $onBehalfOf, delegate: $actorUserId, typeReference: null);

		return $onBehalfOf;
	}//end delegatedSignerUserId()

	/**
	 * Refuse a delegated signature whose delegation does not cover the request's document type.
	 *
	 * @param string|null          $onBehalfOf  The signer a delegate signs for, or null
	 * @param string               $actorUserId The acting user
	 * @param array<string, mixed> $request     The signing request
	 *
	 * @return void
	 *
	 * @throws RuntimeException When no delegation covers the document type
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function assertDelegationCoversType(?string $onBehalfOf, string $actorUserId, array $request): void {
		if ($onBehalfOf === null || $this->guards->delegations === null) {
			return;
		}

		$typeReference = (string) $this->guards->mandateService?->typeReference(request: $request);
		$this->guards->delegations->assertMayActFor(delegator: $onBehalfOf, delegate: $actorUserId, typeReference: $typeReference);
	}//end assertDelegationCoversType()

	/**
	 * Record both names on a delegated signature: `signedBy` is the delegate, `signedOnBehalfOf` the signer.
	 *
	 * @param array<string, mixed> $signer      The signer record
	 * @param string|null          $onBehalfOf  The signer a delegate signs for, or null
	 * @param string               $actorUserId The acting user (the delegate)
	 *
	 * @return array<string, mixed> The signer record, stamped when delegated
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function stampDelegation(array $signer, ?string $onBehalfOf, string $actorUserId): array {
		if ($onBehalfOf !== null) {
			$signer['signedBy']         = $actorUserId;
			$signer['signedOnBehalfOf'] = $onBehalfOf;
		}

		return $signer;
	}//end stampDelegation()

	/**
	 * Accept a request as an accept-only recipient (signing-accept-only-recipient D2, D4).
	 *
	 * The same checks as sign(): the authorised recipient, a request that can
	 * still be acted on, and a recipient still PENDING; in sequential mode
	 * every recipient before this one has signed or accepted. It writes
	 * ACCEPTED and acceptedAt, never signatureData, and the audit entry
	 * ACCEPTED. The signing mandate is not asked: accepting is not signing.
	 *
	 * @param string                    $requestId     The signing request ID.
	 * @param string                    $signerId      The recipient's signer record ID.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null for the session user.
	 *
	 * @return array<string, mixed> The updated recipient record.
	 *
	 * @throws RuntimeException With code 422 for a recipient who signs, or when the request or recipient cannot accept.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 *
	 * @spec openspec/changes/archive/2026-11-25-signing-accept-only-recipient/tasks.md#2-service-and-routes
	 */
	public function accept(string $requestId, string $signerId, ?array $verifiedActor = null): array {
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity(verifiedActor: $verifiedActor);
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			action: 'accept'
		);
		if (RecipientRole::accepts(recipient: $signer) === false) {
			throw new RuntimeException(self::SIGN_NOT_ACCEPT, 422);
		}

		$request = $this->loadSignableRequest(requestId: $requestId);
		if (($signer['status'] ?? '') !== 'PENDING') {
			throw new RuntimeException('Signer has already responded to this request');
		}

		$this->assertEarlierRecipientsDone(request: $request, signerId: $signerId, signer: $signer);

		$act = new SigningAct(
			request: $request,
			target: ['requestId' => $requestId, 'signerId' => $signerId],
			actor: ['userId' => $actorUserId, 'displayName' => $actorDisplayName, 'verified' => $verifiedActor],
			signatureData: null
		);
		$signer['status'] = RecipientRole::ACCEPTED;
		$signer['acceptedAt'] = $act->now->format(DateTimeInterface::ATOM);
		$signer['ipAddress'] = $this->actorResolver->getClientIp();
		$this->settingsService->getObjectService()->saveObject(
			object: $signer,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $signerRegister, schema: $signerSchema)),
			_rbac: false
		);

		$this->outputs->auditService->logEvent(
			event: new SigningAuditEvent(
				request: new SigningAuditSubject(
					id: $requestId,
					signatureLevel: $request['signatureLevel'] ?? 'SES',
					provider: $request['provider'] ?? 'native'
				),
				action: 'ACCEPTED',
				actor: new SigningAuditActor(userId: $actorUserId, displayName: $actorDisplayName, ipAddress: $signer['ipAddress']),
				metadata: $this->actorResolver->actorAuditMetadata(verifiedActor: $verifiedActor)
			)
		);
		$this->updateRequestStatus(act: $act);

		return $signer;
	}//end accept()

	/**
	 * In sequential mode, refuse to act before every earlier recipient signed or accepted.
	 *
	 * @param array<string, mixed> $request  The signing request.
	 * @param string               $signerId The acting recipient's record ID.
	 * @param array<string, mixed> $signer   The acting recipient's record.
	 *
	 * @return void
	 *
	 * @throws RuntimeException With code 409 when an earlier recipient has not responded.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 *
	 * @spec openspec/changes/archive/2026-11-25-signing-accept-only-recipient/tasks.md#2-service-and-routes
	 */
	private function assertEarlierRecipientsDone(array $request, string $signerId, array $signer): void {
		if (($request['signingMode'] ?? 'sequential') !== 'sequential') {
			return;
		}

		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();
		$order = (int)($signer['order'] ?? 0);
		foreach (($request['signerIds'] ?? []) as $otherId) {
			if ((string)$otherId === $signerId) {
				continue;
			}

			$other = $this->toArray(
				object: $objectService->find(
					id: (string)$otherId,
					options: new ObjectFindOptions(register: $signerRegister, schema: $signerSchema),
					_rbac: false
				)
			);
			if ((int)($other['order'] ?? 0) < $order && RecipientRole::isDone(recipient: $other) === false) {
				throw new RuntimeException('It is not your turn yet: an earlier recipient has not responded', 409);
			}
		}
	}//end assertEarlierRecipientsDone()

	/**
	 * Load a signing request that can still be signed.
	 *
	 * @param string $requestId The signing request ID
	 *
	 * @return array<string, mixed> The signing request
	 *
	 * @throws RuntimeException When the request does not exist or is not PENDING or IN_PROGRESS
	 * @throws SigningRequestWithdrawnException When the request was cancelled
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
	 */
	private function loadSignableRequest(string $requestId): array {
		// Without a caller user ID access-denied is impossible, so null means not found.
		$request = $this->getRequest(requestId: $requestId);
		if ($request === null) {
			throw new RuntimeException('Signing request not found: ' . $requestId);
		}

		$status = $request['status'] ?? '';
		if ($status === 'CANCELLED') {
			throw new SigningRequestWithdrawnException();
		}

		if (in_array($status, ['PENDING', 'IN_PROGRESS'], true) === false) {
			throw new RuntimeException('Signing request is not in a signable state: ' . $status);
		}

		return $request;
	}//end loadSignableRequest()

	/**
	 * Write the SIGNED audit entry.
	 *
	 * @param SigningAct           $act      The signing act
	 * @param array<string, mixed> $metadata Consent and identity evidence for the entry
	 *
	 * @return void
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
	 */
	private function logSigned(SigningAct $act, array $metadata): void {
		$this->outputs->auditService->logEvent(
			event: new SigningAuditEvent(
				request: new SigningAuditSubject(
					id: $act->requestId,
					signatureLevel: $act->request['signatureLevel'] ?? 'SES',
					provider: $act->request['provider'] ?? 'native'
				),
				action: 'SIGNED',
				actor: new SigningAuditActor(
					userId: $act->actor['userId'],
					displayName: $act->actor['displayName'],
					ipAddress: $this->actorResolver->getClientIp()
				),
				metadata: array_merge($this->actorResolver->actorAuditMetadata(verifiedActor: $act->verifiedActor), $metadata)
			)
		);
	}//end logSigned()

	/**
	 * Decline a signing request
	 *
	 * @param string $requestId The signing request ID
	 * @param string $signerId The signer record ID
	 * @param string $reason The decline reason
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
	public function decline(string $requestId, string $signerId, string $reason, ?array $verifiedActor = null): array {
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity(verifiedActor: $verifiedActor);

		$objectService = $this->settingsService->getObjectService();

		// Authorise the signer first, so someone who is not the signer learns
		// neither whether the request exists nor what its status is.
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			action: 'decline'
		);

		// Gate the terminal-state status machine BEFORE any signer/request
		// mutation (signing-trust-rebuild REQ-DDSTR-003, closing the #282
		// residual where decline() skipped isValidTransition() entirely — a
		// COMPLETED/CANCELLED/EXPIRED request could still be flipped to DECLINED).
		// Same latent defect as getRequest(): unconfigured, find() returns null
		// and this reports "not found" for a request that exists.
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$requestObject = $objectService->find(id: $requestId, options: new ObjectFindOptions(register: $register, schema: $schema), _rbac: false);
		if ($requestObject === null) {
			throw new RuntimeException('Signing request not found: ' . $requestId);
		}

		$request = $this->toArray(object: $requestObject);

		if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'DECLINED') === false) {
			throw new RuntimeException('Cannot decline request in status: ' . ($request['status'] ?? 'unknown'));
		}

		// The same identity gate as sign() (REQ-DDSIR-003), before any write.
		$signer['identityEvidence'] = $this->guards->assuranceGate->evidenceForAct(
			request: $request + ['id' => $requestId],
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			now: new DateTimeImmutable()
		)->toArray();

		$signer['status'] = 'DECLINED';
		$signer['declineReason'] = $reason;
		$objectService->saveObject(
			object: $signer,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $signerRegister, schema: $signerSchema)),
			_rbac: false
		);

		$signatureLevel = $request['signatureLevel'] ?? 'SES';
		$provider = $request['provider'] ?? 'native';

		$request['status'] = 'DECLINED';
		$objectService->saveObject(
			object: $request,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema)),
			_rbac: false
		);

		// Cross-app delegated-signing contract: a declined request is terminal —
		// emit SigningConcludedEvent (status=declined) for a delegated request.
		$this->outputs->emitter->emitIfDelegated(request: $request, status: 'declined');
		$this->mailDeclined(request: $request + ['id' => $requestId], signer: $signer, actorName: $actorDisplayName, reason: $reason);

		$metadata = $this->actorResolver->actorAuditMetadata(verifiedActor: $verifiedActor);
		$metadata['reason'] = $reason;
		$metadata['identityEvidence'] = $signer['identityEvidence'];

		$this->outputs->auditService->logEvent(
			event: new SigningAuditEvent(
				request: new SigningAuditSubject(id: $requestId, signatureLevel: $signatureLevel, provider: $provider),
				action: 'DECLINED',
				actor: new SigningAuditActor(userId: $actorUserId, displayName: $actorDisplayName, ipAddress: $this->actorResolver->getClientIp()),
				metadata: $metadata
			)
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
	 *                                   caller). Wilco #6 fix (filinq#100,
	 *                                   2026-06-06): callers must collapse
	 *                                   null to a single 404 — never split
	 *                                   into 404-vs-403, which would be an
	 *                                   existence-probing oracle.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 */
	public function cancelRequest(string $requestId): ?array {
		// Throws RuntimeException('No authenticated user') when there is no
		// session user — exactly as the inline check here did before.
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity();

		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

		// Use getRequest() so not-found / access-denied collapse to the
		// same null shape. The controller already gated on initiator/admin
		// before calling us; a null here means truly not-found from the
		// service's perspective (Wilco #6 fix).
		$request = $this->getRequest(requestId: $requestId);
		if ($request === null) {
			return null;
		}

		if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'CANCELLED') === false) {
			throw new RuntimeException('Cannot cancel request in status: ' . ($request['status'] ?? 'unknown'));
		}

		$this->moveToCancelled(request: $request, requestId: $requestId, store: [$objectService, $register, $schema]);
		$request['status'] = 'CANCELLED';

		// Cross-app delegated-signing contract: a cancelled request is terminal
		// — emit SigningConcludedEvent (status=cancelled) for a delegated request.
		$this->outputs->emitter->emitIfDelegated(request: $request, status: 'cancelled');

		$this->outputs->auditService->logEvent(
			event: new SigningAuditEvent(
				request: new SigningAuditSubject(id: $requestId),
				action: 'CANCELLED',
				actor: new SigningAuditActor(userId: $actorUserId, displayName: $actorDisplayName, ipAddress: $this->actorResolver->getClientIp())
			)
		);

		return $request;
	}//end cancelRequest()

	/**
	 * Move a signing request to CANCELLED through its declared lifecycle.
	 *
	 * The `signingRequest` schema declares `x-openregister-lifecycle` with a
	 * `cancel` transition, so the move belongs there (ADR-031): the lifecycle
	 * engine applies the named transition, its declared guards and actions, and
	 * the transitioned event. It runs as the system on filinq's behalf because
	 * the caller was already checked (SigningCancellationService admits only the
	 * creator, the bulk and envelope paths their own owner), as the save it
	 * replaces did with `_rbac: false`. An OpenRegister without the engine gets
	 * that save, so the request is still cancelled.
	 *
	 * @param array<string, mixed>                       $request   The request as read.
	 * @param string                                     $requestId The request id.
	 * @param array{0: object, 1: string, 2: string}     $store     The object service, register and schema of the fallback save.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md#requirement-a-cancelled-request-must-be-visibly-cancelled-and-must-not-be-re-signable
	 */
	private function moveToCancelled(array $request, string $requestId, array $store): void {
		$engine = $this->settingsService->getTransitionEngine();
		if ($engine !== null) {
			$engine->transitionAsSystem(objectId: $requestId, action: self::CANCEL_TRANSITION, app: self::APP_ID);
			return;
		}

		[$objectService, $register, $schema] = $store;
		$request['status'] = 'CANCELLED';
		$objectService->saveObject(
			object: $request,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema)),
			_rbac: false
		);
	}//end moveToCancelled()

	/**
	 * Bulk sign multiple signing requests
	 *
	 * @param array<string> $requestIds Array of request IDs to sign
	 *
	 * @return array<string, array<string, mixed>> Results keyed by request ID
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-4
	 */
	public function bulkSign(array $requestIds): array {
		$results = [];
		// Tolerant lookup: '' when there is no session user, so bulkSign()
		// reports a per-request failure instead of throwing, exactly as the
		// inline null-check here did before.
		$userId = $this->actorResolver->currentUserId();

		foreach ($requestIds as $requestId) {
			try {
				$request = $this->getRequest(requestId: $requestId, callerUserId: $userId);
				if ($request === null) {
					// Treat null as not-found in bulk context (Wilco #6
					// fix) — generic error, no existence leak.
					$results[$requestId] = [
						'success' => false,
						'error' => 'Request not accessible',
					];
					continue;
				}

				$signerIds = $request['signerIds'] ?? [];
				$targetSignerId = $this->actorResolver->findSignerForUser(signerIds: $signerIds, userId: $userId);

				$results[$requestId] = [
					'success' => false,
					'error' => 'No pending signer record found for current user',
				];
				if ($targetSignerId !== null) {
					$results[$requestId] = [
						'success' => true,
						'signer' => $this->sign(requestId: $requestId, signerId: $targetSignerId),
					];
				}
			} catch (Exception $e) {
				$results[$requestId] = [
					'success' => false,
					'error' => $e->getMessage(),
				];
			}//end try
		}//end foreach

		return $results;
	}//end bulkSign()

	/**
	 * Validate a status transition
	 *
	 * @param string $currentStatus The current status
	 * @param string $newStatus The proposed new status
	 *
	 * @return bool True if transition is valid
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 */
	public function isValidTransition(string $currentStatus, string $newStatus): bool {
		$allowed = self::STATUS_TRANSITIONS[$currentStatus] ?? [];
		return in_array($newStatus, $allowed, true) === true;
	}//end isValidTransition()

	/**
	 * Update the signing request status based on signer progress
	 *
	 * The act's verified external actor, when portal-originated (see `sign()`),
	 * is threaded through to the produced artifact's evidence binding
	 * (portal-signing-surface REQ-DDPSS-004).
	 *
	 * @param SigningAct $act The signing act: request ID, request data and verified actor.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 */
	private function updateRequestStatus(SigningAct $act): void {
		$requestId = $act->requestId;
		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		['allSigned' => $allSigned, 'signers' => $signers] = $this->signerProgress(request: $act->request);

		$freshObj = $objectService->find(id: $requestId, options: new ObjectFindOptions(register: $register, schema: $schema), _rbac: false);
		$freshRequest = $this->toArray(object: $freshObj);

		if ($allSigned === false) {
			$freshRequest['status'] = 'IN_PROGRESS';
			$objectService->saveObject(
				object: $freshRequest,
				options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema)),
				_rbac: false
			);
			$this->mailTurn(request: $freshRequest + ['id' => $requestId], after: $act->signerId);
			return;
		}

		// All signers have signed — the request completes only if the active
		// provider can produce a verifiable signed artifact. This is the
		// honest-completion gate (issue #304): the request is NEVER marked
		// COMPLETED with the unsigned original as its signed reference. If the
		// artifact cannot be produced, the request stays IN_PROGRESS and the
		// failure surfaces loudly to the completing signer.
		// Step through IN_PROGRESS when the request is still PENDING. A
		// single-signer request reaches here PENDING, and PENDING may only move
		// to IN_PROGRESS, CANCELLED or EXPIRED (`self::STATUS_TRANSITIONS` and
		// the schema's `x-openregister-lifecycle`, enforced on save), so the
		// declared `start` edge is taken rather than widening the state machine.
		if (($freshRequest['status'] ?? '') === 'PENDING') {
			$freshRequest['status'] = 'IN_PROGRESS';
			$freshRequest = $this->toArray(
				object: $objectService->saveObject(
					object: $freshRequest,
					options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema)),
					_rbac: false
				)
			);
		}

		// Guardian consent (signer-identity-rails REQ-DDSIR-008/010): no
		// artifact while a signer who signed under the age of consent has no
		// guardian who acted; the request then stays IN_PROGRESS, as it does
		// when the artifact cannot be produced. The basis travels onto the
		// request and, through the producer, into the MAC-covered assertion.
		$freshRequest = $this->guards->consentGuard->withConsentBasis(request: $freshRequest, signers: $signers);

		// Identity rails (REQ-DDSIR-004/007): each signer's recorded evidence and
		// the weakest assurance among them travel onto the request, into the
		// MAC-covered assertion and into the completion payload.
		$freshRequest = $this->guards->assuranceGate->withResolvedAssurance(request: $freshRequest, signers: $signers);

		// Accept-only recipients are listed as accepted, never as signers, in
		// the artifact's evidence and the concluded event (D3). Carried on a
		// copy, so the stored request does not gain the key.
		$accepterIds = RecipientRole::accepterIds(signers: $signers);

		// The verification token comes first, so the QR code linking to its
		// public record is part of the bytes that get signed (REQ-DDSVP-005).
		$verificationToken = $this->outputs->artifactProducer->newVerificationToken();
		$signedDocumentRef = $this->outputs->artifactProducer->produce(
			request: ($freshRequest + ['accepterIds' => $accepterIds]),
			verifiedActor: $act->verifiedActor,
			signers: $signers,
			verificationToken: $verificationToken
		);

		$freshRequest['status'] = 'COMPLETED';
		$freshRequest['signedDocumentRef'] = $signedDocumentRef;
		$verificationToken = $this->outputs->artifactProducer->recordVerification(
			request: $freshRequest,
			signedDocumentRef: $signedDocumentRef,
			token: $verificationToken
		);
		if ($verificationToken !== null) {
			$freshRequest['verificationToken'] = $verificationToken;
		}

		$objectService->saveObject(
			object: $freshRequest,
			options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $register, schema: $schema)),
			_rbac: false
		);

		// Cross-app delegated-signing contract: emit the terminal
		// SigningConcludedEvent (status=signed) for a delegated request. The
		// signed reference is the stored artifact (file id + version), never
		// the unsigned original documentFileId.
		$this->outputs->emitter->emitIfDelegated(
			request: ($freshRequest + ['accepterIds' => $accepterIds]),
			status: 'signed',
			signedDocumentRef: $signedDocumentRef
		);

	}//end updateRequestStatus()

	/**
	 * Mail the signers whose turn it now is, bound to the request.
	 *
	 * Every signer record is read, because who is next depends on all of them.
	 * Reading or mailing never stops the act it follows.
	 *
	 * @param array<string, mixed> $request The signing request, with its id and signerIds.
	 * @param string|null          $after   The signer who just acted, or null at creation.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	private function mailTurn(array $request, ?string $after): void {
		if ($this->outputs->mail === null) {
			return;
		}

		try {
			$objectService = $this->settingsService->getObjectService();
			['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();
			['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
			$signers = [];
			foreach (($request['signerIds'] ?? []) as $signerId) {
				$signers[(string)$signerId] = $this->toArray(
					object: $objectService->find(
						id: (string)$signerId,
						options: new ObjectFindOptions(register: $signerRegister, schema: $signerSchema),
						_rbac: false
					)
				);
			}

			$this->outputs->mail->turnStarted(
				request: $request,
				signers: $signers,
				target: new ObjectBoundMailTarget(uuid: (string)($request['id'] ?? ($request['uuid'] ?? '')), register: $register, schema: $schema),
				after: $after
			);
		} catch (\Throwable $e) {
			return;
		}
	}//end mailTurn()

	/**
	 * Mail the initiator that a signer declined, bound to the request.
	 *
	 * @param array<string, mixed> $request   The signing request, with its id.
	 * @param array<string, mixed> $signer    The signer record that declined.
	 * @param string               $actorName The acting user's name, when the record names nobody.
	 * @param string               $reason    The reason given.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) SigningTurn is the stateless rule for a signer's name.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	private function mailDeclined(array $request, array $signer, string $actorName, string $reason): void {
		if ($this->outputs->mail === null) {
			return;
		}

		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$this->outputs->mail->declined(
			request: $request,
			declinedBy: SigningTurn::nameOf(signerId: $actorName, signer: $signer),
			reason: $reason,
			target: new ObjectBoundMailTarget(uuid: (string)$request['id'], register: $register, schema: $schema)
		);
	}//end mailDeclined()

	/**
	 * The signer records listed before one signer on a request, plus that signer, by id.
	 *
	 * Only the signers listed before this one are read: a signer listed after
	 * it is placed after it unless its own order says otherwise, and reading
	 * every record on every signature would load the whole request each time.
	 * A record that cannot be read is kept as UNREADABLE, so it holds the turn
	 * rather than being skipped.
	 *
	 * @param array<string, mixed>                $request The signing request.
	 * @param array<string, array<string, mixed>> $known   The signing signer's record, by id.
	 *
	 * @return array<string, array<string, mixed>> The records, by id.
	 *
	 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#4-signing
	 */
	private function signerRecords(array $request, array $known): array {
		$earlier = [];
		foreach (($request['signerIds'] ?? []) as $signerId) {
			$signerId = (string)$signerId;
			if (isset($known[$signerId]) === true) {
				break;
			}

			$earlier[] = $signerId;
		}

		if ($earlier === []) {
			return $known;
		}

		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$records = [];
		foreach ($earlier as $signerId) {
			try {
				$records[$signerId] = $this->toArray(
					object: $objectService->find(
						id: $signerId,
						options: new ObjectFindOptions(register: $signerRegister, schema: $signerSchema),
						_rbac: false
					)
				);
			} catch (\Throwable $e) {
				$records[$signerId] = ['status' => 'UNREADABLE'];
			}
		}

		return $records + $known;
	}//end signerRecords()

	/**
	 * Read the signer records of a request in order, stopping at the first that has not signed.
	 *
	 * @param array<string, mixed> $request The signing request data.
	 *
	 * @return array{allSigned: bool, signers: array<string, array<string, mixed>>} Whether every
	 *     signer signed, and the records read, keyed by signer ID.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 */
	private function signerProgress(array $request): array {
		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();
		$allSigned = true;
		$signers = [];

		foreach (($request['signerIds'] ?? []) as $signerId) {
			$signerObj = $objectService->find(
				id: $signerId,
				options: new ObjectFindOptions(register: $signerRegister, schema: $signerSchema),
				_rbac: false
			);
			$signer = $this->toArray(object: $signerObj);
			$signers[(string)$signerId] = $signer;

			// A signer is done when SIGNED, an accept-only recipient when ACCEPTED (D3).
			if (RecipientRole::isDone(recipient: $signer) === false) {
				$allSigned = false;
				break;
			}
		}

		return ['allSigned' => $allSigned, 'signers' => $signers];

	}//end signerProgress()

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
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 *
	 * @return void
	 */
	public function emitExpiredConclusion(array $request): void {
		$this->outputs->emitter->emitIfDelegated(request: $request, status: 'expired');

	}//end emitExpiredConclusion()

	/**
	 * The recipients of a request, in order, with their role and state.
	 *
	 * The caller has already been allowed to read the request. The projection
	 * is an allow-list: no e-mail address, IP address or signature data of a
	 * co-recipient leaves this method. A record that cannot be read is listed
	 * as UNREADABLE, so the list keeps every place in the order.
	 *
	 * @param array<string, mixed> $request The signing request, as getRequest() returned it.
	 *
	 * @return list<array<string, mixed>> The recipients.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role
	 * (sign or accept); an instance would add nothing.
	 *
	 * @spec openspec/changes/archive/2026-11-25-signing-accept-only-recipient/tasks.md#4-screens
	 */
	public function recipientsOf(array $request): array {
		$signerIds = (array)($request['signerIds'] ?? []);
		if ($signerIds === []) {
			return [];
		}

		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$recipients = [];
		foreach (array_values($signerIds) as $index => $signerId) {
			$signerId = (string)$signerId;
			try {
				$record = $this->toArray(
					object: $objectService->find(id: $signerId, options: new ObjectFindOptions(register: $signerRegister, schema: $signerSchema), _rbac: false)
				);
			} catch (\Throwable $e) {
				$record = [];
			}

			$accepts = RecipientRole::accepts(recipient: $record);
			$name    = (string)($record['displayName'] ?? '');
			if ($name === '') {
				$name = (string)($record['userId'] ?? '');
			}

			$at = ($record['signedAt'] ?? '');
			if ($accepts === true) {
				$at = ($record['acceptedAt'] ?? '');
			}

			$role = 'sign';
			if ($accepts === true) {
				$role = RecipientRole::ACCEPT;
			}

			$recipients[] = [
				'id' => $signerId,
				'name' => $name,
				'role' => $role,
				'status' => (string)($record['status'] ?? 'UNREADABLE'),
				'order' => (int)($record['order'] ?? $index),
				'at' => (string)$at,
			];
		}//end foreach

		return $recipients;
	}//end recipientsOf()

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
	private function toArray(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return $object->jsonSerialize();
		}

		return (array)$object;
	}//end toArray()

	/**
	 * Resolve the signingRequest binding, failing closed when unconfigured.
	 *
	 * Every call site used to read these two keys inline with an empty-string
	 * default and pass the result straight into saveObject()/find(). On an
	 * instance where an administrator has not bound them, that wrote signing
	 * requests — the audit trail behind an eIDAS-level signature — into
	 * register '' and schema '', silently. SettingsService owns the read;
	 * this turns "unset" into the same RegisterNotConfiguredException
	 * SigningController already handles.
	 *
	 * @return array{register: string, schema: string} The resolved binding.
	 *
	 * @throws RegisterNotConfiguredException When either half is unset.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	private function requireSigningRequestBinding(): array {
		$binding = $this->settingsService->resolveSigningRequestBinding();
		if ($binding === null) {
			throw new RegisterNotConfiguredException(
				message: 'Signing request register/schema not configured'
			);
		}

		return $binding;
	}//end requireSigningRequestBinding()

	/**
	 * Resolve the signerRecord binding, failing closed when unconfigured.
	 *
	 * A signer record carries the identity a signature is attributed to, so an
	 * unconfigured binding loses exactly the evidence a signature exists to
	 * provide.
	 *
	 * @return array{register: string, schema: string} The resolved binding.
	 *
	 * @throws RegisterNotConfiguredException When either half is unset.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	private function requireSignerRecordBinding(): array {
		$binding = $this->settingsService->resolveSignerRecordBinding();
		if ($binding === null) {
			throw new RegisterNotConfiguredException(
				message: 'Signer record register/schema not configured'
			);
		}

		return $binding;
	}//end requireSignerRecordBinding()
}//end class
