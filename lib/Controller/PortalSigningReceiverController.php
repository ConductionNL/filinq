<?php

/**
 * Portal Signing Receiver Controller
 *
 * The Filinq-side receiver for portaliq's ADR-046 contract-v2/v2.2 A6
 * endpoint-forward actions (`portal-signing-actions`, `portal-signing-surface`).
 * Gives an external, accountless **signer** a real signing surface: portaliq
 * forwards `sign`/`decline`/`viewDocument` server-to-server, attaching a
 * short-lived `X-Portal-Subject` HS256 assertion and NEVER the client's own
 * `Authorization` header. This controller verifies that assertion, derives
 * the acting signer's identity SERVER-SIDE from it (never from the request
 * body), resolves the invited `signerRecord` on the exact target request
 * (the anti-IDOR boundary), and drives the honest `SigningService::sign()` /
 * `decline()` primitive via its verified-actor entrypoint.
 *
 * Fail-closed ordering every A6 receiver follows (see the fleet reference,
 * `apps-extra/petstore/lib/Controller/PortalActionController.php`):
 *   verify (401) -> derive scope from claims (403) -> validate input (403) ->
 *   authorize against the domain row (403, uniform — no existence oracle) ->
 *   act -> relay (200/502).
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Kernel\Contracts\PortalSigningDocumentResolverInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Portal\PortalAssertionVerifier;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Receives portaliq's forwarded signing actions on the `signer` audience.
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */
class PortalSigningReceiverController extends Controller {
	/**
	 * Brute-force throttler action for rejected portal signing assertions.
	 *
	 * One action across sign / decline / viewDocument: they share
	 * `authoriseAct()`, so a caller must not be able to spread guesses over
	 * three endpoints to stay under a per-endpoint ceiling.
	 *
	 * @var string
	 */
	private const THROTTLE_ACTION = 'filinq_portal_signing_assertion';

	/**
	 * Minimum eIDAS-aligned portal trust required to act (mirrors
	 * `PortalContributionProvider::SIGNING_MIN_TRUST` — re-checked here as
	 * defence in depth; the receiver MUST NOT rely on portaliq's own gate).
	 */
	private const MIN_TRUST = 'substantial';

	/**
	 * Ordered trust levels, low to high, for the `>=` comparison.
	 *
	 * @var list<string>
	 */
	private const TRUST_ORDER = ['low', 'substantial', 'high'];

	/**
	 * The only audience this receiver serves.
	 */
	private const AUDIENCE_SIGNER = 'signer';

	/**
	 * Constructor.
	 *
	 * @param string $appName App name.
	 * @param IRequest $request Request object.
	 * @param PortalAssertionVerifier $verifier Verifies the X-Portal-Subject assertion.
	 * @param SigningServiceInterface $signingService The honest signing primitive.
	 * @param SettingsServiceInterface $settingsService Settings service (resolves OR's ObjectService).
	 * @param OpenRegisterResolverInterface $registerResolver Resolves register/schema bindings, failing closed.
	 * @param LoggerInterface $logger Logger.
	 * @param PortalSigningDocumentResolverInterface $documentResolver Resolves the target document for viewDocument.
	 * @param IThrottler $throttler Brute-force throttler; rejected portal assertions are registered against it.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PortalAssertionVerifier $verifier,
		private readonly SigningServiceInterface $signingService,
		private readonly SettingsServiceInterface $settingsService,
		private readonly OpenRegisterResolverInterface $registerResolver,
		private readonly LoggerInterface $logger,
		private readonly PortalSigningDocumentResolverInterface $documentResolver,
		private readonly IThrottler $throttler,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * POST /apps/filinq/api/portal/signing/sign
	 *
	 * The signDocument act (portal-signing-actions REQ-DDPSA-005,
	 * portal-signing-surface REQ-DDPSS-002): records the signer's consent
	 * confirmation + optional drawn-signature payload, then drives
	 * `SigningService::sign()` acting as the resolved, verified external
	 * signer. A refusal by the guardian consent rule answers 403
	 * `signing_refused` rather than 502 (signer-identity-rails REQ-DDSIR-008).
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 20, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function signDocument(): JSONResponse {
		$context = $this->authoriseAct();
		if ($context instanceof JSONResponse) {
			return $context;
		}

		[$verifiedActor, $signerRecord] = $context;

		try {
			$signer = $this->recordSignature(verifiedActor: $verifiedActor, signerRecord: $signerRecord);
		} catch (Throwable $e) {
			return $this->signRefusalOrFailure(exception: $e);
		}

		return $this->signedAnswer(signer: $signer);

	}//end signDocument()

	/**
	 * Drive the signing service as the verified signer, with the consent and drawn signature of the request.
	 *
	 * @param array<string, mixed> $verifiedActor The actor built from the verified assertion.
	 * @param array<string, mixed> $signerRecord  The resolved, invited signerRecord.
	 *
	 * @return array<string, mixed> The signer as the signing service returns it.
	 *
	 * @throws Throwable When the signing service refuses or fails.
	 */
	private function recordSignature(array $verifiedActor, array $signerRecord): array {
		$signatureData = $this->signatureData();

		return $this->signingService->sign(
			requestId: (string)$signerRecord['signingRequestId'],
			signerId: (string)($signerRecord['id'] ?? $signerRecord['uuid'] ?? ''),
			verifiedActor: $verifiedActor,
			signatureData: $signatureData
		);

	}//end recordSignature()

	/**
	 * The signer's consent confirmation and, when drawn, the signature payload.
	 *
	 * @return array<string, mixed> The signature data handed to the signing service.
	 */
	private function signatureData(): array {
		$consent = (bool)$this->request->getParam('consent', false);
		$signature = $this->request->getParam('signature');

		$signatureData = ['consent' => $consent];
		if (is_string($signature) === true && $signature !== '') {
			$signatureData['signature'] = $signature;
		}

		return $signatureData;

	}//end signatureData()

	/**
	 * Answer a failed signDocument act: a refusal or a downstream failure.
	 *
	 * @param Throwable $exception The caught failure.
	 *
	 * @return JSONResponse
	 */
	private function signRefusalOrFailure(Throwable $exception): JSONResponse {
		// A refusal by the guardian consent rule (signer-identity-rails
		// REQ-DDSIR-008/009) carries code 403: the act was refused, nothing
		// failed downstream. It is not a rejected assertion either, so it
		// does not go through forbidden() and the brute-force counter.
		if ($exception->getCode() === Http::STATUS_FORBIDDEN) {
			$this->logger->info('Filinq: portal signing refused: ' . $exception->getMessage());
			return new JSONResponse(['error' => 'signing_refused'], Http::STATUS_FORBIDDEN);
		}

		return $this->downstreamFailure(context: 'signDocument', exception: $exception);

	}//end signRefusalOrFailure()

	/**
	 * The answer to a recorded signature.
	 *
	 * REQ-DDPSS-005: the answer names the assurance the signature
	 * recorded, which is never above the session trust and never QES.
	 *
	 * @param array<string, mixed> $signer The signer as the signing service returned it.
	 *
	 * @return JSONResponse
	 */
	private function signedAnswer(array $signer): JSONResponse {
		return new JSONResponse(
			[
				'status' => ($signer['status'] ?? 'signed'),
				'assurance' => ($signer['signatureAssurance'] ?? 'SES'),
			]
		);

	}//end signedAnswer()


	/**
	 * POST /apps/filinq/api/portal/signing/decline
	 *
	 * The declineDocument act (portal-signing-actions REQ-DDPSA-005,
	 * portal-signing-surface REQ-DDPSS-003): records the client-supplied
	 * reason, then drives `SigningService::decline()` acting as the resolved,
	 * verified external signer.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 20, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function declineDocument(): JSONResponse {
		$context = $this->authoriseAct();
		if ($context instanceof JSONResponse) {
			return $context;
		}

		[$verifiedActor, $signerRecord] = $context;

		$reason = (string)$this->request->getParam('reason', '');

		try {
			$signer = $this->signingService->decline(
				requestId: (string)$signerRecord['signingRequestId'],
				signerId: (string)($signerRecord['id'] ?? $signerRecord['uuid'] ?? ''),
				reason: $reason,
				verifiedActor: $verifiedActor
			);
		} catch (Throwable $e) {
			// The identity gate (signer-identity-rails REQ-DDSIR-003/007) refuses
			// a portal trust below the request's assurance with code 403: the
			// same refusal as sign, not a downstream failure.
			if ($e->getCode() === Http::STATUS_FORBIDDEN) {
				$this->logger->info('Filinq: portal decline refused: ' . $e->getMessage());
				return new JSONResponse(['error' => 'signing_refused'], Http::STATUS_FORBIDDEN);
			}

			return $this->downstreamFailure(context: 'declineDocument', exception: $e);
		}

		return new JSONResponse(
			[
				'status' => ($signer['status'] ?? 'declined'),
			]
		);

	}//end declineDocument()


	/**
	 * GET /apps/filinq/api/portal/signing/viewDocument
	 *
	 * The viewDocument act (portal-signing-actions REQ-DDPSA-006): lets the
	 * verified, invited signer read the target document BEFORE signing.
	 * Scoped by the IDENTICAL invited-signer guard as sign/decline.
	 * portaliq's A6 forward relays a decoded JSON body only, so the document
	 * is returned as `{documentName, mimeType, contentBase64}` inside the
	 * single JSON hop.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 60, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function viewDocument(): JSONResponse {
		$context = $this->authoriseAct();
		if ($context instanceof JSONResponse) {
			return $context;
		}

		[, $signerRecord] = $context;

		return $this->requestDocumentAnswer(signerRecord: $signerRecord);

	}//end viewDocument()

	/**
	 * Answer viewDocument for the signing request of an invited signer.
	 *
	 * @param array<string, mixed> $signerRecord The resolved, invited signerRecord.
	 *
	 * @return JSONResponse
	 */
	private function requestDocumentAnswer(array $signerRecord): JSONResponse {
		try {
			$signingRequest = $this->signingService->getRequest(requestId: (string)$signerRecord['signingRequestId']);
		} catch (Throwable $e) {
			return $this->downstreamFailure(context: 'viewDocument', exception: $e);
		}

		if ($signingRequest === null) {
			// Uniform not-authorised — no existence oracle (REQ-DDPSA-004).
			return $this->forbidden();
		}

		return $this->documentAnswer(signingRequest: $signingRequest);

	}//end requestDocumentAnswer()

	/**
	 * Answer viewDocument with the target document of a resolved signing request.
	 *
	 * @param array<string, mixed> $signingRequest The signing request the signer is invited to.
	 *
	 * @return JSONResponse
	 */
	private function documentAnswer(array $signingRequest): JSONResponse {
		$file = $this->documentResolver->resolve(signingRequest: $signingRequest);
		if ($file === null) {
			return new JSONResponse(['error' => 'document_unavailable'], Http::STATUS_NOT_FOUND);
		}

		return $this->fileAnswer(signingRequest: $signingRequest, file: $file);

	}//end documentAnswer()

	/**
	 * The document as `{documentName, mimeType, contentBase64}`, or 502 when it cannot be read.
	 *
	 * @param array<string, mixed> $signingRequest The signing request, for its document name.
	 * @param File                 $file           The resolved document.
	 *
	 * @return JSONResponse
	 */
	private function fileAnswer(array $signingRequest, File $file): JSONResponse {
		try {
			$content = $file->getContent();
		} catch (Throwable $e) {
			return $this->downstreamFailure(context: 'viewDocument', exception: $e);
		}

		return new JSONResponse(
			[
				'documentName' => (string)($signingRequest['documentName'] ?? $file->getName()),
				'mimeType' => $file->getMimeType(),
				'contentBase64' => base64_encode($content),
			]
		);

	}//end fileAnswer()


	/**
	 * Verify the assertion, derive identity, validate the target, and resolve
	 * the invited signer — the SAME guard for sign/decline/viewDocument
	 * (portal-signing-actions REQ-DDPSA-002/003/004).
	 *
	 * @return array{0: array<string, mixed>, 1: array<string, mixed>}|JSONResponse
	 *                                                                              On success: `[verifiedActor, signerRecord]`. On any
	 *                                                                              failure: the fail-closed JSONResponse to return directly.
	 */
	private function authoriseAct(): array|JSONResponse {
		// 1. Verify — the assertion is the ONLY credential (fail-closed 401).
		$claims = $this->verifier->verify((string)$this->request->getHeader(PortalAssertionVerifier::HEADER));
		if ($claims === null) {
			return $this->unauthorised();
		}

		// 2. Derive scope from the verified claims.
		$signerEmail = $this->authorisedSignerEmail(claims: $claims);
		if ($signerEmail === null) {
			return $this->forbidden();
		}

		// 3 + 4. Validate the target and resolve the invited signer on it.
		$signerRecord = $this->invitedSignerForTarget(signerEmail: $signerEmail);
		if ($signerRecord === null) {
			return $this->forbidden();
		}

		return [$this->verifiedActor(claims: $claims, signerEmail: $signerEmail), $signerRecord];
	}//end authoriseAct()

	/**
	 * The fail-closed 401 for an assertion that does not verify.
	 *
	 * @return JSONResponse
	 */
	private function unauthorised(): JSONResponse {
		// The assertion is the only credential, so a failed verify is a
		// presented-secret failure -- exactly what the counter is for.
		$this->registerRejectedAssertion();
		return new JSONResponse(['error' => 'unauthorized'], Http::STATUS_UNAUTHORIZED);

	}//end unauthorised()

	/**
	 * The invited email of verified claims that authorise a signer act, or null.
	 *
	 * Re-checks audience + trust server-side as defence in depth
	 * (REQ-DDPSA-003); never trusts that portaliq already gated this.
	 *
	 * @param array<string, mixed> $claims The verified assertion claims.
	 *
	 * @return string|null The signer email, or null when the claims do not authorise the act.
	 */
	private function authorisedSignerEmail(array $claims): ?string {
		if ($this->claimsReachSigner(claims: $claims) === false) {
			return null;
		}

		return $this->signerEmailClaim(claims: $claims);

	}//end authorisedSignerEmail()

	/**
	 * Whether the claims carry the signer audience and at least the minimum trust.
	 *
	 * @param array<string, mixed> $claims The verified assertion claims.
	 *
	 * @return bool True when audience and trust both allow the act.
	 */
	private function claimsReachSigner(array $claims): bool {
		$audience = (string)($claims['audience'] ?? '');
		if ($audience !== self::AUDIENCE_SIGNER) {
			return false;
		}

		$trust = (string)($claims['trust'] ?? '');
		return $this->trustAtLeast(trust: $trust, minimum: self::MIN_TRUST);

	}//end claimsReachSigner()

	/**
	 * The invited email from the assertion's resolved `signerEmail` scope claim.
	 *
	 * The same claim `portal-contribution` scopes the signer collections by,
	 * sourced ONLY from the verified assertion, NEVER the request body. Until
	 * the named portaliq A6 amendment lands (design.md Open Question 1), this
	 * claim is absent and every act fails closed 403, which is the intended,
	 * safe posture.
	 *
	 * @param array<string, mixed> $claims The verified assertion claims.
	 *
	 * @return string|null The signer email, or null when absent or empty.
	 */
	private function signerEmailClaim(array $claims): ?string {
		$signerEmail = $claims['signerEmail'] ?? null;
		if (is_string($signerEmail) === false || $signerEmail === '') {
			return null;
		}

		return $signerEmail;

	}//end signerEmailClaim()

	/**
	 * Validate the client-chosen target and resolve the invited signer on it.
	 *
	 * The target is an opaque object reference ONLY: anything resembling a
	 * URL/path/scheme is rejected before any lookup (SSRF hardening,
	 * REQ-DDPSA-004). The acting signer MUST be a genuinely invited
	 * signerRecord on the EXACT target request; a foreign request, wrong
	 * email and non-existent request id all collapse to the SAME null (no
	 * existence oracle).
	 *
	 * @param string $signerEmail The verified assertion's signer email.
	 *
	 * @return array<string, mixed>|null The invited signerRecord, or null.
	 */
	private function invitedSignerForTarget(string $signerEmail): ?array {
		$signingRequestId = $this->request->getParam('signingRequestId');
		if ($this->isValidOpaqueId(value: $signingRequestId) === false) {
			return null;
		}

		return $this->resolveInvitedSigner(email: $signerEmail, signingRequestId: (string)$signingRequestId);

	}//end invitedSignerForTarget()

	/**
	 * The actor the signing service acts as, built from the verified claims only.
	 *
	 * @param array<string, mixed> $claims      The verified assertion claims.
	 * @param string               $signerEmail The verified signer email.
	 *
	 * @return array<string, mixed> The verified actor.
	 */
	private function verifiedActor(array $claims, string $signerEmail): array {
		return [
			'email' => $signerEmail,
			// The frozen A6 wire format carries only `sub` as the portal
			// subject reference; there is no distinct `identityRef` claim.
			// Until portaliq's contract grows one, `identityRef` mirrors
			// `subjectRef` (design.md Open Question 2 — coordinated at apply
			// time) so the portal-signing-surface evidence binding
			// (REQ-DDPSS-004) always has both keys populated from the SAME
			// verified value.
			'subjectRef' => (string)$claims['sub'],
			'identityRef' => (string)$claims['sub'],
			'trust' => (string)($claims['trust'] ?? ''),
			'jti' => (string)($claims['jti'] ?? ''),
		];

	}//end verifiedActor()


	/**
	 * Resolve the invited signerRecord for the given email + target request.
	 *
	 * The anti-IDOR boundary (REQ-DDPSA-004): a row is returned ONLY when a
	 * `signerRecord` exists whose `email` equals the verified assertion email
	 * AND whose `signingRequestId` equals the target. Wrong email, wrong
	 * request, or a non-existent request id all return null identically.
	 *
	 * @param string $email The verified assertion's signer email.
	 * @param string $signingRequestId The client-supplied (opaque) target request id.
	 *
	 * @return array<string, mixed>|null The resolved signerRecord, or null.
	 */
	private function resolveInvitedSigner(string $email, string $signingRequestId): ?array {
		$results = $this->findSignerRows(email: $email, signingRequestId: $signingRequestId);
		foreach ($this->readableRows(results: $results) as $row) {
			if ($this->hasEmail(row: $row, email: $email) === true && $this->isForRequest(row: $row, signingRequestId: $signingRequestId) === true) {
				return $row;
			}
		}

		return null;
	}//end resolveInvitedSigner()

	/**
	 * The signerRecord rows OpenRegister returns for the email and target request.
	 *
	 * This is the anti-IDOR boundary (REQ-DDPSA-004), and the register and
	 * schema are two of the four filters that scope the lookup. They used
	 * to be read with an empty-string default and passed straight through,
	 * so the boundary's correctness depended entirely on OpenRegister
	 * choosing to match nothing for an empty filter — an assumption this
	 * code never stated and does not control. If findAll ever read an empty
	 * register as "unscoped", this lookup would resolve a signerRecord from
	 * ANY register, which is precisely the cross-request signer resolution
	 * the requirement forbids.
	 *
	 * requireSignerRecordBinding() throws instead. The catch collapses any
	 * failure to no rows, which is the same answer the wrong-email and
	 * wrong-request cases give, so no new signal is exposed to a caller
	 * probing the endpoint.
	 *
	 * @param string $email            The verified assertion's signer email.
	 * @param string $signingRequestId The client-supplied (opaque) target request id.
	 *
	 * @return mixed The rows as findAll returns them, empty when there is no store or the lookup fails.
	 */
	private function findSignerRows(string $email, string $signingRequestId): mixed {
		$objectService = $this->objectServiceOrNull();
		if ($objectService === null) {
			return [];
		}

		try {
			$filters = $this->signerFilters(email: $email, signingRequestId: $signingRequestId);
			$results = $objectService->findAll(['filters' => $filters], _rbac: false, _multitenancy: false);
		} catch (Throwable $e) {
			$this->logger->debug('Filinq: portal signer lookup failed', ['reason' => $e->getMessage()]);
			return [];
		}

		return $results;

	}//end findSignerRows()

	/**
	 * OpenRegister's ObjectService, or null when it is not available.
	 *
	 * @return object|null
	 */
	private function objectServiceOrNull(): ?object {
		try {
			return $this->settingsService->getObjectService();
		} catch (Throwable $e) {
			return null;
		}

	}//end objectServiceOrNull()

	/**
	 * The four filters that scope the signerRecord lookup.
	 *
	 * @param string $email            The verified assertion's signer email.
	 * @param string $signingRequestId The client-supplied (opaque) target request id.
	 *
	 * @return array<string, string> Register, schema, email and target request.
	 *
	 * @throws Throwable When the signerRecord register and schema are not configured.
	 */
	private function signerFilters(string $email, string $signingRequestId): array {
		['register' => $register, 'schema' => $schema] = $this->registerResolver->getSignerRecordRegisterAndSchema();

		return [
			'register' => $register,
			'schema' => $schema,
			'email' => $email,
			'signingRequestId' => $signingRequestId,
		];

	}//end signerFilters()

	/**
	 * The fetched rows that read as arrays, one at a time; nothing when the result is not iterable.
	 *
	 * @param mixed $results The fetched objects.
	 *
	 * @return \Generator<int, array<string, mixed>> The readable rows, in order.
	 */
	private function readableRows(mixed $results): \Generator {
		if (is_iterable($results) === false) {
			return;
		}

		foreach ($results as $entry) {
			$row = $this->normalise(row: $entry);
			if ($row !== null) {
				yield $row;
			}
		}

	}//end readableRows()

	/**
	 * Whether the row's email equals the verified email, ignoring case.
	 *
	 * @param array<string, mixed> $row   The signerRecord row.
	 * @param string               $email The verified assertion's signer email.
	 *
	 * @return bool
	 */
	private function hasEmail(array $row, string $email): bool {
		return strcasecmp((string)($row['email'] ?? ''), $email) === 0;

	}//end hasEmail()

	/**
	 * Whether the row belongs to exactly the target request.
	 *
	 * @param array<string, mixed> $row              The signerRecord row.
	 * @param string               $signingRequestId The target request id.
	 *
	 * @return bool
	 */
	private function isForRequest(array $row, string $signingRequestId): bool {
		return (string)($row['signingRequestId'] ?? '') === $signingRequestId;

	}//end isForRequest()


	/**
	 * Normalise an OpenRegister result (array or ObjectEntity) to an array.
	 *
	 * @param mixed $row The fetched object.
	 *
	 * @return array<string, mixed>|null
	 */
	private function normalise(mixed $row): ?array {
		if (is_array($row) === true) {
			return $row;
		}

		return $this->serialisedRow(row: $row);
	}//end normalise()

	/**
	 * The array an ObjectEntity-like object serialises to, or null.
	 *
	 * @param mixed $row The fetched object.
	 *
	 * @return array<string, mixed>|null
	 */
	private function serialisedRow(mixed $row): ?array {
		if (is_object($row) === false || method_exists($row, 'jsonSerialize') === false) {
			return null;
		}

		$data = $row->jsonSerialize();
		if (is_array($data) === false) {
			return null;
		}

		return $data;

	}//end serialisedRow()


	/**
	 * Check the client-supplied target is an opaque id — never a URL/path.
	 *
	 * SSRF hardening (REQ-DDPSA-004): accepts only the character set OR
	 * ids/uuids actually use; anything containing a scheme, host, or path
	 * separator is rejected before any lookup.
	 *
	 * @param mixed $value The candidate signingRequestId.
	 *
	 * @return bool True when the value is a safe opaque identifier.
	 */
	private function isValidOpaqueId(mixed $value): bool {
		if (is_string($value) === false || $value === '') {
			return false;
		}

		return (bool)preg_match('/^[A-Za-z0-9_-]+$/', $value);
	}//end isValidOpaqueId()

	/**
	 * Compare a trust level against a minimum on the ordered trust scale.
	 *
	 * @param string $trust The candidate trust level.
	 * @param string $minimum The minimum required trust level.
	 *
	 * @return bool True when the candidate is at or above the minimum.
	 */
	private function trustAtLeast(string $trust, string $minimum): bool {
		$trustIndex = array_search($trust, self::TRUST_ORDER, true);
		$minimumIndex = array_search($minimum, self::TRUST_ORDER, true);

		if ($trustIndex === false || $minimumIndex === false) {
			return false;
		}

		return $trustIndex >= $minimumIndex;
	}//end trustAtLeast()

	/**
	 * The uniform not-authorised response — identical for wrong audience,
	 * insufficient trust, no signer-identifying claim, a malformed target, a
	 * foreign request, and a non-existent request (no existence oracle,
	 * REQ-DDPSA-004/007).
	 *
	 * @return JSONResponse
	 */
	private function forbidden(): JSONResponse {
		// A verified assertion that fails the audience or trust check is still
		// a rejected credential presentation: the signature held but the claims
		// did not authorise this act. Counted with the 401s, since an attacker
		// probing for a usable assertion sees both.
		$this->registerRejectedAssertion();
		return new JSONResponse(['error' => 'forbidden'], Http::STATUS_FORBIDDEN);
	}//end forbidden()

	/**
	 * Record a rejected assertion with the brute-force throttler.
	 *
	 * This is the half that COUNTS. The half that ENFORCES is the
	 * `#[BruteForceProtection]` attribute on sign / decline / viewDocument —
	 * `BruteForceMiddleware` only calls `sleepDelayOrThrowOnMax()` when that
	 * attribute is present, so registering without it writes a counter nothing
	 * ever reads. Both are required; see ADR-082.
	 *
	 * @return void
	 */
	private function registerRejectedAssertion(): void {
		try {
			$this->throttler->registerAttempt(
				action: self::THROTTLE_ACTION,
				ip: $this->request->getRemoteAddress()
			);
		} catch (\Throwable $throttlerFailure) {
			// Never let throttler bookkeeping change a fail-closed 401/403 into
			// a 500 — the fail-closed answer is the security-relevant one.
			$this->logger->warning(
				'PortalSigningReceiverController: registerAttempt failed: ' . $throttlerFailure->getMessage()
			);
		}
	}//end registerRejectedAssertion()

	/**
	 * Relay a downstream/OpenRegister failure as 502 — never leaking
	 * transport or exception internals (REQ-DDPSA-005/007).
	 *
	 * @param string $context Log context (which act failed).
	 * @param Throwable $exception The caught failure.
	 *
	 * @return JSONResponse
	 */
	private function downstreamFailure(string $context, Throwable $exception): JSONResponse {
		$this->logger->error(
			'Filinq: portal signing receiver ' . $context . ' failed: ' . $exception->getMessage(),
			['exception' => $exception]
		);

		return new JSONResponse(['error' => 'downstream_failure'], Http::STATUS_BAD_GATEWAY);
	}//end downstreamFailure()
}//end class
