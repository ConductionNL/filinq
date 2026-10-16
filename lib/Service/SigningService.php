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
use OCA\Filinq\Service\SignerAuth\SigningAssuranceGate;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\Signing\PortalSignatureAssurance;
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
 * SignedArtifactProducer and SigningRequestValidator were extracted earlier;
 * SigningActorResolver (who is acting, and may they act as this signer) and
 * SigningConclusionEmitter (the cross-app conclusion contract) followed, so
 * the class now meets the length, complexity and parameter-list thresholds on
 * its own. Coupling went back over the line with the signing folder's mandate
 * service, which is a collaborator rather than work this class does itself.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The thirteenth collaborator is
 * SigningMandateService, added so a direct signing attempt is refused by the
 * same rule that leaves the document out of the folder. Inlining that rule here
 * would be the second copy of it. The fourteenth is GuardianConsentGuard, for
 * the same reason: the guardian rule is applied here and kept there. The
 * fifteenth is SigningAssuranceGate (signer-identity-rails REQ-DDSIR-003): the
 * identity gate runs here, at every act, and its rules live there.
 *
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SigningService {

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
	 * @param SigningAuditService $auditService Audit service
	 * @param SignedArtifactProducer $artifactProducer Produces + stores the verifiable signed artifact
	 * @param SigningRequestValidator $validator Validates request data + the provider/level pair
	 * @param SigningActorResolver $actorResolver Resolves the acting identity + authorises it
	 * @param SigningConclusionEmitter $emitter Emits the cross-app SigningConcludedEvent
	 * @param GuardianConsentGuard $consentGuard Holds the guardian rule for signers under the
	 *                                           age of consent (signer-identity-rails
	 *                                           REQ-DDSIR-008 to 010). Required, not a
	 *                                           nullable seam: an unwired safety guard
	 *                                           must fail construction, not pass silently.
	 * @param SigningAssuranceGate $assuranceGate The identity gate of every signing act
	 *                                           (signer-identity-rails REQ-DDSIR-003).
	 *                                           Required for the same reason as the
	 *                                           consent guard.
	 * @param SigningMandateService|null $mandateService Applies the consuming app's per-type
	 *                                                   mandate declaration to a direct signing
	 *                                                   attempt (signing-folder-across-cases
	 *                                                   REQ-SFC-04). An ADDITIVE seam: null
	 *                                                   behaves exactly as before, so callers
	 *                                                   constructing this service by hand are
	 *                                                   unchanged, while the DI container
	 *                                                   resolves the real one.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly SigningAuditService $auditService,
		private readonly SignedArtifactProducer $artifactProducer,
		private readonly SigningRequestValidator $validator,
		private readonly SigningActorResolver $actorResolver,
		private readonly SigningConclusionEmitter $emitter,
		private readonly GuardianConsentGuard $consentGuard,
		private readonly SigningAssuranceGate $assuranceGate,
		private readonly ?SigningMandateService $mandateService = null,
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
	 * @throws \InvalidArgumentException With code 400 when a guardian entry is invalid,
	 *                                   or a signer under the age of consent has no guardian.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function createRequest(array $data): array {
		// Throws RuntimeException('No authenticated user') when there is no
		// session user — exactly as the inline check here did before.
		[$initiatorUserId, $initiatorDisplayName] = $this->actorResolver->resolveActingIdentity();

		$objectService = $this->settingsService->getObjectService();

		// These three were read straight off IAppConfig here, duplicating both
		// the keys AND the defaults ('30', 'SES', 'native') that
		// SettingsService::loadFeatureToggles() already owns — two sources of
		// truth for the same three settings, free to drift. getFeatureToggles()
		// is the cheap accessor (plain IAppConfig reads, no register or schema
		// discovery), which is why it is safe on a write path.
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

		$this->validator->validateRequestData(data: $request);

		// Provider/level honesty at creation time (signing-trust-rebuild
		// REQ-DDSTR-002 point 1): reject an unsupported provider/level pair
		// with 400 BEFORE any object is persisted, so a QES request can never
		// be routed to a provider that will later silently complete it with a
		// lower-assurance (e.g. native SES) artifact.
		$this->validator->validateProviderLevelPair(provider: (string)$request['provider'], level: (string)$request['signatureLevel']);

		// Guardian consent (signer-identity-rails REQ-DDSIR-008/009): every
		// request records the age of consent that governs it, and the signer
		// entries are validated and linked BEFORE anything is persisted. A
		// request naming a signer under that age without a guardian is refused
		// with a 400 and leaves no object behind.
		$request['guardianConsentAge'] = $this->consentGuard->appliedAge(data: $data);
		// Required assurance, never below the level's floor (REQ-DDSIR-002).
		$request = $this->assuranceGate->applyToRequest(request: $request, data: $data);
		$signers = (array)($data['signers'] ?? []);

		// A request that names nobody who can sign is refused before anything
		// is stored, instead of being saved as PENDING for ever (#1209).
		$this->validator->validateSigners(signers: $signers);
		$prepared = $this->consentGuard->prepareSigners(
			signers: $signers,
			age: $request['guardianConsentAge'],
			now: new DateTimeImmutable()
		);

		// Cross-app delegated-signing contract (filinq-signing-events): when a
		// consumer raised this request through DocumentSigningRequestedEvent it
		// carries provenance fields. Persist any present provenance onto the
		// signing-request object (additive/optional) so the terminal
		// SigningConcludedEvent can correlate back to the originating consumer.
		// Internal requests omit these and are unaffected.
		foreach ([...self::PROVENANCE_FIELDS, 'envelopeRef'] as $field) {
			if (empty($data[$field]) === false) {
				$request[$field] = $data[$field];
			}
		}
		$request = $this->validator->withPlacements(request: $request, data: $data, producer: $this->artifactProducer);
		$request = $this->artifactProducer->delegate(request: $request, signers: $signers);

		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$savedRequest = $objectService->saveObject(object: $request, register: $register, schema: $schema);
		$createdRequest = $this->toArray(object: $savedRequest);

		$requestId = $createdRequest['id'] ?? $createdRequest['uuid'] ?? '';
		$createdRequest['signerIds'] = $this->persistSigners(
			requestId: (string)$requestId,
			signers: $signers,
			prepared: $prepared
		);
		$objectService->saveObject(object: $createdRequest, register: $register, schema: $schema);
		$createdRequest['assuranceFloor'] = $this->assuranceGate->floorFor(request: $request);

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'CREATED',
			actorUserId: $initiatorUserId,
			actorDisplayName: $initiatorDisplayName,
			ipAddress: $this->actorResolver->getClientIp(),
			signatureLevel: $request['signatureLevel'],
			provider: $request['provider']
		);

		return $createdRequest;
	}//end createRequest()

	/**
	 * Persist the signer records of a new request, guardians last.
	 *
	 * A guardian's record points at the signer it stands beside by record id,
	 * which exists only once that signer is saved. Signers are therefore saved
	 * first and guardians after; the returned ids keep the order of the entries.
	 *
	 * @param string $requestId The id of the saved signing request.
	 * @param array<int|string, mixed> $signers The signer entries as the consumer sent them.
	 * @param array{fields: array<int|string, array<string, mixed>>, links: array<int|string, int|string>} $prepared The guardian
	 *     fields per entry and the guardian-to-signer links, from GuardianConsentGuard::prepareSigners().
	 *
	 * @return list<string> The signer record ids, in entry order.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	private function persistSigners(string $requestId, array $signers, array $prepared): array {
		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$links = $prepared['links'];
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
				($prepared['fields'][$index] ?? []),
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

			$created = $this->toArray(
				object: $objectService->saveObject(object: $signerRecord, register: $signerRegister, schema: $signerSchema)
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
	 * Wilco #6 blocker fix (filinq#100, 2026-06-06): the access-denied path
	 * still returns null (indistinguishable from the controller's not-found
	 * 404 for a scoped caller), so an unrelated user cannot probe request-ID
	 * existence. Not-found now throws a fixed, ID-free message ('Signing
	 * request not found') — no UUID is echoed, so nothing is leaked.
	 */
	public function getRequest(string $requestId, string $callerUserId = ''): ?array {
		$objectService = $this->settingsService->getObjectService();
		// Gate-50 did not flag this pair (the null-check below sits inside its
		// window), but it carries the same defect: a find() against register ''
		// returns null, and this method reports null as "not found". An
		// unconfigured instance therefore answered 404 for every signing
		// request that does exist.
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

		$object = $objectService->find(id: $requestId, register: $register, schema: $schema);
		if ($object === null) {
			// Genuine not-found: throw so the controller maps it to a single
			// 404. Access-denied below still collapses to null — the two
			// shapes stay indistinguishable to a non-admin caller, preserving
			// the Wilco #6 anti-existence-probing contract.
			throw new RuntimeException('Signing request not found');
		}

		$request = $this->toArray(object: $object);

		// WF2 + Wilco #6 fix: scope single-record access to initiator or
		// signer. Access denied collapses to null (same shape as not-found),
		// so the controller can emit one 404 regardless.
		if ($callerUserId !== '') {
			$isInitiator = ($request['initiatorUserId'] ?? '') === $callerUserId;
			$isSignerInList = in_array($callerUserId, (array)($request['signerIds'] ?? []), true);

			if ($isInitiator === false && $isSignerInList === false) {
				return null;
			}
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

		$paginated = $objectService->searchObjectsPaginated(query: $query);
		$results = ($paginated['results'] ?? []);

		$requests = [];
		foreach ($results as $result) {
			$item = $this->toArray(object: $result);

			// WF2 security fix: a scoped caller sees only requests they
			// initiated or are listed as a signer on. Unscoped sees all.
			if ($callerUserId !== '') {
				$isInitiator = ($item['initiatorUserId'] ?? '') === $callerUserId;
				$isSignerInList = in_array($callerUserId, (array)($item['signerIds'] ?? []), true);

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
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function sign(string $requestId, string $signerId, ?array $verifiedActor = null, ?array $signatureData = null): array {
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity(verifiedActor: $verifiedActor);

		$objectService = $this->settingsService->getObjectService();
		$request = $this->getRequest(requestId: $requestId);
		if ($request === null) {
			// The getRequest() call returns null on not-found and access-denied
			// (Wilco #6 fix, filinq#100). Internal sign() doesn't pass a
			// callerUserId so access-denied is impossible from here — null
			// means truly not-found. Throw to keep callers' existing
			// exception contract.
			throw new RuntimeException('Signing request not found: ' . $requestId);
		}

		$status = $request['status'] ?? '';

		if (in_array($status, ['PENDING', 'IN_PROGRESS'], true) === false) {
			throw new RuntimeException('Signing request is not in a signable state: ' . $status);
		}

		// Change signing-folder-across-cases, REQ-SFC-04: the folder leaves out what
		// the signer has no mandate for, and the direct attempt on the same
		// document is refused here, naming the rule. The guard applies to the
		// in-app actor: a mandate is declared in Nextcloud groups, which an
		// invited external portal signer is not a member of, and the portal
		// path has its own verified-assertion gate.
		if ($this->mandateService !== null && $verifiedActor === null) {
			$this->mandateService->assertMaySign(request: $request, userId: $actorUserId);
		}

		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			action: 'sign'
		);

		if (($signer['status'] ?? '') !== 'PENDING') {
			throw new RuntimeException('Signer has already responded to this request');
		}

		// Guardian consent (signer-identity-rails REQ-DDSIR-008/009): a signer
		// under the age of consent signs only with a guardian on this request,
		// and a guardian may be neither the minor nor a minor. Refused here with
		// a 403, before anything is written. The guardian reached this line
		// through the same identity resolution and ownership check as anyone.
		$now = new DateTimeImmutable();
		$signer['identityEvidence'] = $this->assuranceGate->evidenceForAct(
			request: $request + ['id' => $requestId],
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			now: $now
		)->toArray();

		$consent = $this->consentGuard->guardSigningAct(
			requestId: $requestId,
			request: $request,
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			now: $now
		);

		$signer = array_merge($signer, $consent['record']);
		$signer['status'] = 'SIGNED';
		$signer['signedAt'] = $now->format(DateTimeInterface::ATOM);
		$signer['ipAddress'] = $this->actorResolver->getClientIp();
		if ($signatureData !== null) {
			// Portal-signing-surface REQ-DDPSS-002: consent and an optional drawn
			// signature, in the `visible:false` field, never used for identity.
			$signer['signatureData'] = $signatureData;
		}
		$signer = (new PortalSignatureAssurance())->recordOn(signer: $signer, request: $request, verifiedActor: $verifiedActor);

		$objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'SIGNED',
			actorUserId: $actorUserId,
			actorDisplayName: $actorDisplayName,
			ipAddress: $this->actorResolver->getClientIp(),
			signatureLevel: $request['signatureLevel'] ?? 'SES',
			provider: $request['provider'] ?? 'native',
			metadata: array_merge(
				$this->actorResolver->actorAuditMetadata(verifiedActor: $verifiedActor),
				$consent['audit'],
				['identityEvidence' => $signer['identityEvidence']]
			)
		);
		$this->assuranceGate->consume(requestId: $requestId, signerId: $signerId);

		$this->updateRequestStatus(requestId: $requestId, request: $request, verifiedActor: $verifiedActor);

		return $signer;
	}//end sign()

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

		// Load the request FIRST and gate the terminal-state status machine
		// BEFORE any signer/request mutation (signing-trust-rebuild
		// REQ-DDSTR-003, closing the #282 residual where decline() skipped
		// isValidTransition() entirely — a COMPLETED/CANCELLED/EXPIRED
		// request could still be flipped to DECLINED).
		// Same latent defect as getRequest(): unconfigured, find() returns null
		// and this reports "not found" for a request that exists.
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$requestObject = $objectService->find(id: $requestId, register: $register, schema: $schema);
		if ($requestObject === null) {
			throw new RuntimeException('Signing request not found: ' . $requestId);
		}

		$request = $this->toArray(object: $requestObject);

		if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'DECLINED') === false) {
			throw new RuntimeException('Cannot decline request in status: ' . ($request['status'] ?? 'unknown'));
		}

		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			action: 'decline'
		);

		// The same identity gate as sign() (REQ-DDSIR-003), before any write.
		$signer['identityEvidence'] = $this->assuranceGate->evidenceForAct(
			request: $request + ['id' => $requestId],
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			now: new DateTimeImmutable()
		)->toArray();

		$signer['status'] = 'DECLINED';
		$signer['declineReason'] = $reason;
		$objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

		$signatureLevel = $request['signatureLevel'] ?? 'SES';
		$provider = $request['provider'] ?? 'native';

		$request['status'] = 'DECLINED';
		$objectService->saveObject(object: $request, register: $register, schema: $schema);

		// Cross-app delegated-signing contract: a declined request is terminal —
		// emit SigningConcludedEvent (status=declined) for a delegated request.
		$this->emitter->emitIfDelegated(request: $request, status: 'declined');

		$metadata = $this->actorResolver->actorAuditMetadata(verifiedActor: $verifiedActor);
		$metadata['reason'] = $reason;
		$metadata['identityEvidence'] = $signer['identityEvidence'];

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'DECLINED',
			actorUserId: $actorUserId,
			actorDisplayName: $actorDisplayName,
			ipAddress: $this->actorResolver->getClientIp(),
			signatureLevel: $signatureLevel,
			provider: $provider,
			metadata: $metadata
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

		$request['status'] = 'CANCELLED';
		$objectService->saveObject(object: $request, register: $register, schema: $schema);

		// Cross-app delegated-signing contract: a cancelled request is terminal
		// — emit SigningConcludedEvent (status=cancelled) for a delegated request.
		$this->emitter->emitIfDelegated(request: $request, status: 'cancelled');

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'CANCELLED',
			actorUserId: $actorUserId,
			actorDisplayName: $actorDisplayName,
			ipAddress: $this->actorResolver->getClientIp()
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
	public function bulkSign(array $requestIds): array {
		$results = [];
		// Tolerant lookup: '' when there is no session user, so bulkSign()
		// reports a per-request failure instead of throwing, exactly as the
		// inline null-check here did before.
		$userId = $this->actorResolver->currentUserId();

		foreach ($requestIds as $requestId) {
			try {
				$request = $this->getRequest(requestId: $requestId);
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
	 * @param string $requestId The signing request ID
	 * @param array<string, mixed> $request The current request data
	 * @param array<string, mixed>|null $verifiedActor The verified external actor completing
	 *                                                 this act, when portal-originated (see
	 *                                                 `sign()`); threaded through to the
	 *                                                 produced artifact's evidence binding
	 *                                                 (portal-signing-surface REQ-DDPSS-004).
	 *
	 * @return void
	 */
	private function updateRequestStatus(string $requestId, array $request, ?array $verifiedActor = null): void {
		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();
		$signerIds = $request['signerIds'] ?? [];
		$allSigned = true;
		$signers = [];

		foreach ($signerIds as $signerId) {
			$signerObj = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);
			$signer = $this->toArray(object: $signerObj);
			$signers[(string)$signerId] = $signer;

			if (($signer['status'] ?? '') !== 'SIGNED') {
				$allSigned = false;
				break;
			}
		}//end foreach

		$freshObj = $objectService->find(id: $requestId, register: $register, schema: $schema);
		$freshRequest = $this->toArray(object: $freshObj);

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
			$freshRequest = $this->toArray(
				object: $objectService->saveObject(
					object: $freshRequest,
					register: $register,
					schema: $schema
				)
			);
		}

		// Guardian consent (signer-identity-rails REQ-DDSIR-008/010): no
		// artifact while a signer who signed under the age of consent has no
		// guardian who acted; the request then stays IN_PROGRESS, as it does
		// when the artifact cannot be produced. The basis travels onto the
		// request and, through the producer, into the MAC-covered assertion.
		$freshRequest = $this->consentGuard->withConsentBasis(request: $freshRequest, signers: $signers);

		// Identity rails (REQ-DDSIR-004/007): each signer's recorded evidence and
		// the weakest assurance among them travel onto the request, into the
		// MAC-covered assertion and into the completion payload.
		$freshRequest = $this->assuranceGate->withResolvedAssurance(request: $freshRequest, signers: $signers);

		$signedDocumentRef = $this->artifactProducer->produce(request: $freshRequest, verifiedActor: $verifiedActor, signers: $signers);

		$freshRequest['status'] = 'COMPLETED';
		$freshRequest['signedDocumentRef'] = $signedDocumentRef;
		$objectService->saveObject(object: $freshRequest, register: $register, schema: $schema);

		// Cross-app delegated-signing contract: emit the terminal
		// SigningConcludedEvent (status=signed) for a delegated request. The
		// signed reference is the stored artifact (file id + version), never
		// the unsigned original documentFileId.
		$this->emitter->emitIfDelegated(
			request: $freshRequest,
			status: 'signed',
			signedDocumentRef: $signedDocumentRef
		);

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
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 *
	 * @return void
	 */
	public function emitExpiredConclusion(array $request): void {
		$this->emitter->emitIfDelegated(request: $request, status: 'expired');

	}//end emitExpiredConclusion()

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
