<?php

/**
 * Signing Assurance Gate
 *
 * Refuses a signing act whose identity evidence is missing, stale, from an
 * unregistered provider, or below the assurance the act needs.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SignerAuth;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Exception\StepUpRequiredException;

/**
 * The REQ-DDSIR-003 gate, and the assurance bookkeeping around it.
 *
 * Where the evidence comes from:
 * - an external portal signer: the verified portal assertion, as provider
 *   `portaliq` at the assertion's trust (portal-signing-actions REQ-DDPSA-005
 *   already verified it; an unknown trust is `low`);
 * - an in-app signer who stepped up: the evidence the callback kept for this
 *   request and signer;
 * - otherwise: the Nextcloud session, through the `nextcloud-session`
 *   provider, at `low`.
 *
 * A guardian is held to the stronger of the request's required assurance,
 * the request's `guardianRequiredAssurance` and the admin's guardian minimum.
 * That is the stronger check for the person who consents for a minor: a
 * consumer such as learniq's OPP can ask `substantial` of the parent while
 * the pupil signs with a Nextcloud login.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SigningAssuranceGate {

	/**
	 * The provider id of evidence taken from a verified portal assertion.
	 *
	 * @var string
	 */
	public const PORTAL_PROVIDER = 'portaliq';

	/**
	 * The assurance scale.
	 *
	 * @var AssuranceLevel
	 */
	private readonly AssuranceLevel $levels;

	/**
	 * Constructor.
	 *
	 * @param SignerAuthProviderFactory $providers The registered providers.
	 * @param SignerAuthSettings $settings Evidence window and guardian minimum.
	 * @param IdentityEvidenceStore $store Evidence kept by a step-up.
	 * @param SubjectPseudonymiser $pseudonymiser Salts the portal subject.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SignerAuthProviderFactory $providers,
		private readonly SignerAuthSettings $settings,
		private readonly IdentityEvidenceStore $store,
		private readonly SubjectPseudonymiser $pseudonymiser,
	) {
		$this->levels = new AssuranceLevel();

	}//end __construct()

	/**
	 * Put the assurance fields on a new request: never below the floor.
	 *
	 * A QES request that asks for `low` is stored at `high` (REQ-DDSIR-002).
	 * A guardian's assurance is stored only when the consumer asked for one,
	 * and never below the request's own.
	 *
	 * @param array<string, mixed> $request The request about to be saved.
	 * @param array<string, mixed> $data The creation payload.
	 *
	 * @return array<string, mixed> The request with `requiredAssurance` (and `guardianRequiredAssurance`).
	 *
	 * @throws InvalidArgumentException With code 400 when an assurance value is not on the scale.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function applyToRequest(array $request, array $data): array {
		foreach (['requiredAssurance', 'guardianRequiredAssurance'] as $field) {
			$asked = $data[$field] ?? null;
			if ($asked !== null && $asked !== '' && $this->levels->isLevel(value: $asked) === false) {
				throw new InvalidArgumentException($field . ' must be low, substantial or high', 400);
			}
		}

		$required = $this->levels->requiredFor(request: ['signatureLevel' => $request['signatureLevel'] ?? 'SES'] + $data);
		$request['requiredAssurance'] = $required;

		$guardian = (string)($data['guardianRequiredAssurance'] ?? '');
		if ($guardian !== '') {
			$request['guardianRequiredAssurance'] = $this->levels->strongest(levels: [$guardian, $required]);
		}

		return $request;

	}//end applyToRequest()

	/**
	 * The floor of a request's signature level, for the creation response.
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return string The floor.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function floorFor(array $request): string {
		return $this->levels->floorFor(signatureLevel: (string)($request['signatureLevel'] ?? 'SES'));

	}//end floorFor()

	/**
	 * The assurance one signer needs on one request.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param array<string, mixed> $signer The signer record.
	 *
	 * @return string `low`, `substantial` or `high`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function requiredFor(array $request, array $signer): string {
		$required = $this->levels->requiredFor(request: $request);
		if (($signer['role'] ?? '') !== 'guardian') {
			return $required;
		}

		return $this->levels->strongest(
			levels: [
				$required,
				(string)($request['guardianRequiredAssurance'] ?? 'low'),
				$this->settings->guardianMinimumAssurance(),
			]
		);

	}//end requiredFor()

	/**
	 * The evidence a signing act rests on, or a refusal before anything is written.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param array<string, mixed> $signer The authorised signer record, with `id`.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null.
	 * @param string $actorUserId The resolved acting identity.
	 * @param DateTimeImmutable $now The moment of the act.
	 *
	 * @return IdentityEvidence The evidence to record.
	 *
	 * @throws StepUpRequiredException With code 403 when the evidence does not carry the act.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function evidenceForAct(
		array $request,
		array $signer,
		?array $verifiedActor,
		string $actorUserId,
		DateTimeImmutable $now,
	): IdentityEvidence {
		$required = $this->requiredFor(request: $request, signer: $signer);
		$evidence = $this->resolveEvidence(
			requestId: (string)($request['id'] ?? $request['uuid'] ?? $signer['signingRequestId'] ?? ''),
			signerId: (string)($signer['id'] ?? ''),
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			now: $now
		);

		$registered = $this->providers->has(identifier: $evidence->provider)
			|| ($verifiedActor !== null && $evidence->provider === self::PORTAL_PROVIDER);
		$reason = '';
		if ($registered === false) {
			$reason = 'unregistered';
		} else if ($evidence->isFreshAt(now: $now, maxAgeSeconds: $this->settings->evidenceMaxAgeSeconds()) === false) {
			$reason = 'stale';
		} else if ($this->levels->meets(held: $evidence->assurance, required: $required) === false) {
			$reason = 'insufficient';
		}

		if ($reason !== '') {
			throw new StepUpRequiredException(
				reason: $reason,
				requiredAssurance: $required,
				heldAssurance: $evidence->assurance,
				provider: $this->settings->providerId()
			);
		}

		return $evidence;

	}//end evidenceForAct()

	/**
	 * Forget the step-up evidence of an act that has used it.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function consume(string $requestId, string $signerId): void {
		$this->store->clear(requestId: $requestId, signerId: $signerId);

	}//end consume()

	/**
	 * The completing request, with each signer's evidence and the weakest assurance among them.
	 *
	 * The resolved assurance is read from the recorded evidence, never from
	 * request input (REQ-DDSIR-007). A signer who signed before the rails
	 * existed has no evidence and counts as `low`.
	 *
	 * @param array<string, mixed> $request The completing request.
	 * @param array<string, array<string, mixed>> $signers Its signer records, keyed by id.
	 *
	 * @return array<string, mixed> The request with `resolvedAssurance` and `signerEvidence`.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) IdentityEvidence::fromArray() is the value
	 * object's named constructor; there is no state to inject.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function withResolvedAssurance(array $request, array $signers): array {
		$levels = [];
		$evidence = [];
		foreach ($signers as $signerId => $signer) {
			$tuple = $signer['identityEvidence'] ?? null;
			if (is_array($tuple) === false || (string)($tuple['provider'] ?? '') === '') {
				$levels[] = 'low';
				continue;
			}

			$levels[] = $this->levels->normalise(value: ($tuple['assurance'] ?? ''));
			$evidence[] = ['signerId' => (string)$signerId] + IdentityEvidence::fromArray(data: $tuple)->toArray();
		}

		$request['resolvedAssurance'] = $this->levels->weakest(levels: $levels);
		if ($evidence !== []) {
			$request['signerEvidence'] = $evidence;
		}

		return $request;

	}//end withResolvedAssurance()

	/**
	 * Find the evidence for an act.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null.
	 * @param string $actorUserId The resolved acting identity.
	 * @param DateTimeImmutable $now The moment of the act.
	 *
	 * @return IdentityEvidence The evidence, not yet checked.
	 */
	private function resolveEvidence(
		string $requestId,
		string $signerId,
		?array $verifiedActor,
		string $actorUserId,
		DateTimeImmutable $now,
	): IdentityEvidence {
		if ($verifiedActor !== null) {
			return $this->portalEvidence(verifiedActor: $verifiedActor, now: $now);
		}

		$stored = $this->store->get(requestId: $requestId, signerId: $signerId);
		if ($stored !== null) {
			return $stored;
		}

		return $this->providers->get(identifier: NextcloudSessionProvider::IDENTIFIER)->completeAuthentication(
			['requestId' => $requestId, 'signerId' => $signerId, 'userId' => $actorUserId]
		);

	}//end resolveEvidence()

	/**
	 * Evidence from a verified portal assertion.
	 *
	 * @param array<string, mixed> $verifiedActor The verified portal actor.
	 * @param DateTimeImmutable $now The moment of the act (the assertion was verified just now).
	 *
	 * @return IdentityEvidence Evidence as provider `portaliq`, with the subject salted.
	 */
	private function portalEvidence(array $verifiedActor, DateTimeImmutable $now): IdentityEvidence {
		$subject = (string)($verifiedActor['subjectRef'] ?? '');
		if ($subject === '') {
			$subject = (string)($verifiedActor['email'] ?? 'unknown');
		}

		$claims = [
			'jti' => (string)($verifiedActor['jti'] ?? ''),
			'sub' => $subject,
			'trust' => (string)($verifiedActor['trust'] ?? ''),
		];

		return new IdentityEvidence(
			provider: self::PORTAL_PROVIDER,
			means: 'portal',
			assurance: $claims['trust'],
			subjectPseudonym: $this->pseudonymiser->pseudonym(issuer: self::PORTAL_PROVIDER, subject: $subject),
			authenticatedAt: $now,
			evidenceHash: hash('sha256', (string)json_encode($claims))
		);

	}//end portalEvidence()
}//end class
