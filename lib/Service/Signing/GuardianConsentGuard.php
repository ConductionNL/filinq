<?php

/**
 * Guardian Consent Guard
 *
 * A signer under the age of consent signs only with a guardian beside them.
 * This guard holds that rule at the three moments it can break: when a
 * request is created, when a signer acts, and when the request completes.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use OCA\Filinq\Service\SettingsService;
use RuntimeException;

/**
 * Guards signatures by signers under the age of consent.
 *
 * A guardian is a signer record on the same request with `role: guardian`
 * and `guardianForSignerId`. The guardian acts through the same `sign()` path,
 * and so the same identity rails, as every other signer; this guard adds only
 * what is specific to guardians. Refusals at an act are RuntimeExceptions with
 * code 403 and happen before anything is written.
 *
 * `SigningService` takes this guard as a required dependency rather than a
 * nullable seam: a safety rule that silently does nothing when unwired is the
 * failure the guard exists to prevent.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class GuardianConsentGuard {

	/**
	 * The assurance levels an identity can carry, weakest first.
	 *
	 * @var list<string>
	 */
	private const ASSURANCES = ['low', 'substantial', 'high'];

	/**
	 * Answers which age applies and whether a signer was under it.
	 *
	 * @var GuardianAgePolicy
	 */
	private readonly GuardianAgePolicy $agePolicy;

	/**
	 * Validates the signer entries of a new request.
	 *
	 * @var GuardianEntryPreparer
	 */
	private readonly GuardianEntryPreparer $preparer;

	/**
	 * Builds the consent basis of a completing request.
	 *
	 * @var ConsentBasisBuilder
	 */
	private readonly ConsentBasisBuilder $basisBuilder;

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settingsService Reads the configured age and loads signer records.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settingsService,
	) {
		$this->agePolicy = new GuardianAgePolicy(settingsService: $settingsService);
		$this->preparer = new GuardianEntryPreparer(agePolicy: $this->agePolicy);
		$this->basisBuilder = new ConsentBasisBuilder(agePolicy: $this->agePolicy);

	}//end __construct()

	/**
	 * The age of consent that applies to a request.
	 *
	 * @param array<string, mixed> $data The request, or its creation payload.
	 *
	 * @return int The configured age (default 16), raised to the request's own when that is higher.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function appliedAge(array $data): int {
		return $this->agePolicy->appliedAge(data: $data);

	}//end appliedAge()

	/**
	 * Validate the signer entries of a new request and link guardians to signers.
	 *
	 * @param array<int|string, mixed> $signers The signer entries as the consumer sent them.
	 * @param int $age The age of consent that applies.
	 * @param DateTimeImmutable $now The moment of creation.
	 *
	 * @return array{fields: array<int|string, array<string, mixed>>, links: array<int|string, int|string>}
	 *
	 * @throws InvalidArgumentException With code 400 when an entry is invalid.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function prepareSigners(array $signers, int $age, DateTimeImmutable $now): array {
		return $this->preparer->prepare(signers: $signers, age: $age, now: $now);

	}//end prepareSigners()

	/**
	 * Guard one signing act, before anything is written.
	 *
	 * @param string $requestId The signing request id.
	 * @param array<string, mixed> $request The signing request.
	 * @param array<string, mixed> $signer The authorised signer record about to sign.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null for a Nextcloud session.
	 * @param DateTimeImmutable $now The moment of the act.
	 *
	 * @return array{record: array<string, mixed>, audit: array<string, mixed>}
	 *     Fields to add to the signer record, and audit metadata; both [] for
	 *     a signer outside the rule.
	 *
	 * @throws RuntimeException With code 403 when the act must be refused.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function guardSigningAct(
		string $requestId,
		array $request,
		array $signer,
		?array $verifiedActor,
		DateTimeImmutable $now,
	): array {
		if (($signer['role'] ?? '') === 'guardian') {
			return $this->guardGuardianAct(requestId: $requestId, request: $request, guardian: $signer, verifiedActor: $verifiedActor, now: $now);
		}

		$age = $this->agePolicy->appliedAge(data: $request);
		$birthDate = (string)($signer['birthDate'] ?? '');
		if ($birthDate === '' || $this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $now) === false) {
			return ['record' => [], 'audit' => []];
		}

		$guardianIds = $this->guardiansFor(request: $request, signerId: (string)($signer['id'] ?? ''));
		if ($guardianIds === []) {
			throw new RuntimeException(
				'A signer under ' . $age . ' signs only with a guardian, and this request names no guardian for them',
				403
			);
		}

		return [
			'record' => ['actingIdentity' => $this->actingIdentity(signer: $signer, verifiedActor: $verifiedActor, now: $now)],
			'audit' => [
				'guardianConsent' => [
					'required' => true,
					'guardianConsentAge' => $age,
					'guardianSignerIds' => $guardianIds,
				],
			],
		];

	}//end guardSigningAct()

	/**
	 * The consent basis of a request whose signers have all signed.
	 *
	 * @param array<string, mixed> $request The completing request.
	 * @param array<string, array<string, mixed>> $signers Its signer records, keyed by signer record id.
	 *
	 * @return list<array<string, mixed>> One entry per signer who signed under the age; [] when there is none.
	 *
	 * @throws RuntimeException With code 409 when a signer signed under the age and no guardian acted for them.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function consentBasis(array $request, array $signers): array {
		return $this->basisBuilder->build(request: $request, signers: $signers);

	}//end consentBasis()

	/**
	 * The completing request, with its consent basis when it has one.
	 *
	 * A request between adults comes back unchanged, so neither the stored
	 * request nor the artifact assertion gains a field it did not have.
	 *
	 * @param array<string, mixed> $request The completing request.
	 * @param array<string, array<string, mixed>> $signers Its signer records, keyed by signer record id.
	 *
	 * @return array<string, mixed> The request, with `consentBasis` set when a signer signed under the age.
	 *
	 * @throws RuntimeException With code 409 when a signer signed under the age and no guardian acted for them.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function withConsentBasis(array $request, array $signers): array {
		$basis = $this->consentBasis(request: $request, signers: $signers);
		if ($basis !== []) {
			$request['consentBasis'] = $basis;
		}

		return $request;

	}//end withConsentBasis()

	/**
	 * Guard a guardian's act.
	 *
	 * @param string $requestId The signing request id.
	 * @param array<string, mixed> $request The signing request.
	 * @param array<string, mixed> $guardian The guardian's signer record.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null.
	 * @param DateTimeImmutable $now The moment of the act.
	 *
	 * @return array{record: array<string, mixed>, audit: array<string, mixed>}
	 *
	 * @throws RuntimeException With code 403 when the guardian may not act.
	 */
	private function guardGuardianAct(
		string $requestId,
		array $request,
		array $guardian,
		?array $verifiedActor,
		DateTimeImmutable $now,
	): array {
		$age = $this->agePolicy->appliedAge(data: $request);
		$birthDate = (string)($guardian['birthDate'] ?? '');
		if ($birthDate !== '' && $this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $now) === true) {
			throw new RuntimeException('A guardian must have reached the age of ' . $age, 403);
		}

		$minorId = (string)($guardian['guardianForSignerId'] ?? '');
		$minor = $this->signerOnRequest(requestId: $requestId, request: $request, signerId: $minorId);
		if ($minor === null) {
			throw new RuntimeException('This guardian record points at no signer on this request', 403);
		}

		if ($this->samePerson(first: $guardian, second: $minor) === true) {
			throw new RuntimeException('A signer cannot act as their own guardian', 403);
		}

		return [
			'record' => ['actingIdentity' => $this->actingIdentity(signer: $guardian, verifiedActor: $verifiedActor, now: $now)],
			'audit' => [
				'guardianConsent' => [
					'role' => 'guardian',
					'guardianAct' => (string)($guardian['guardianAct'] ?? 'co-sign'),
					'guardianForSignerId' => $minorId,
				],
			],
		];

	}//end guardGuardianAct()

	/**
	 * The ids of the guardian records on this request that stand beside a signer.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $signerId The signer record id.
	 *
	 * @return list<string> The guardian signer record ids; a guardian who declined does not count.
	 */
	private function guardiansFor(array $request, string $signerId): array {
		$guardianIds = [];
		foreach ((array)($request['signerIds'] ?? []) as $candidateId) {
			$candidateId = (string)$candidateId;
			if ($candidateId === '' || $candidateId === $signerId) {
				continue;
			}

			$candidate = $this->loadSigner(signerId: $candidateId);
			$standsHere = ($candidate['guardianForSignerId'] ?? '') === $signerId;
			if (($candidate['role'] ?? '') === 'guardian' && $standsHere === true && ($candidate['status'] ?? '') !== 'DECLINED') {
				$guardianIds[] = $candidateId;
			}
		}

		return $guardianIds;

	}//end guardiansFor()

	/**
	 * Load a signer record that belongs to this request.
	 *
	 * @param string $requestId The signing request id.
	 * @param array<string, mixed> $request The signing request.
	 * @param string $signerId The signer record id.
	 *
	 * @return array<string, mixed>|null The record, or null when it is not a signer on this request.
	 */
	private function signerOnRequest(string $requestId, array $request, string $signerId): ?array {
		if ($signerId === '' || in_array($signerId, (array)($request['signerIds'] ?? []), true) === false) {
			return null;
		}

		$signer = $this->loadSigner(signerId: $signerId);
		if (($signer['signingRequestId'] ?? '') !== $requestId) {
			return null;
		}

		return $signer;

	}//end signerOnRequest()

	/**
	 * Is this the same person, by Nextcloud user id or by email.
	 *
	 * @param array<string, mixed> $first One signer record.
	 * @param array<string, mixed> $second The other signer record.
	 *
	 * @return bool True when the user ids or the emails match.
	 */
	private function samePerson(array $first, array $second): bool {
		$firstUid = (string)($first['userId'] ?? '');
		$firstEmail = (string)($first['email'] ?? '');
		$sameUid = $firstUid !== '' && $firstUid === (string)($second['userId'] ?? '');
		$sameEmail = $firstEmail !== '' && strcasecmp($firstEmail, (string)($second['email'] ?? '')) === 0;

		return $sameUid === true || $sameEmail === true;

	}//end samePerson()

	/**
	 * The identity the rails resolved for this act, and nothing more identifying.
	 *
	 * Identity evidence from the signer-authentication seam wins when the
	 * record carries it. Otherwise a portal actor is `portaliq` at the
	 * verified trust, and a Nextcloud session is `nextcloud-session` at `low`.
	 * No pseudonym, no subject reference and no token leave this method.
	 *
	 * @param array<string, mixed> $signer The signer record.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null.
	 * @param DateTimeImmutable $now The moment of the act.
	 *
	 * @return array{provider: string, assurance: string, authenticatedAt: string}
	 */
	private function actingIdentity(array $signer, ?array $verifiedActor, DateTimeImmutable $now): array {
		$moment = $now->format(DateTimeInterface::ATOM);
		$evidence = $signer['identityEvidence'] ?? null;
		if (is_array($evidence) === true && (string)($evidence['provider'] ?? '') !== '') {
			return [
				'provider' => (string)$evidence['provider'],
				'assurance' => $this->assurance(value: ($evidence['assurance'] ?? '')),
				'authenticatedAt' => (string)($evidence['authenticatedAt'] ?? $moment),
			];
		}

		if ($verifiedActor !== null) {
			return [
				'provider' => 'portaliq',
				'assurance' => $this->assurance(value: ($verifiedActor['trust'] ?? '')),
				'authenticatedAt' => $moment,
			];
		}

		return ['provider' => 'nextcloud-session', 'assurance' => 'low', 'authenticatedAt' => $moment];

	}//end actingIdentity()

	/**
	 * An assurance level, where anything undeclared degrades to the weakest.
	 *
	 * @param mixed $value The candidate level.
	 *
	 * @return string `low`, `substantial` or `high`.
	 */
	private function assurance(mixed $value): string {
		if (in_array($value, self::ASSURANCES, true) === true) {
			return (string)$value;
		}

		return 'low';

	}//end assurance()

	/**
	 * Load one signer record as an array.
	 *
	 * @param string $signerId The signer record id.
	 *
	 * @return array<string, mixed> The record, or [] when it does not exist.
	 *
	 * @throws RuntimeException When the signer record binding is not configured.
	 */
	private function loadSigner(string $signerId): array {
		$binding = $this->settingsService->resolveSignerRecordBinding();
		if ($binding === null) {
			throw new RuntimeException('Signer record register/schema not configured');
		}

		$object = $this->settingsService->getObjectService()->find(
			id: $signerId,
			register: $binding['register'],
			schema: $binding['schema']
		);

		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return (array)$object->jsonSerialize();
		}

		return (array)$object;

	}//end loadSigner()
}//end class
