<?php

/**
 * Consent Basis Builder
 *
 * Builds the record a completed signing request keeps for every signer who
 * signed under the age of consent: who they are, which guardian acted for
 * them, how, and on which basis.
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

use RuntimeException;

/**
 * The consent basis of a completing request.
 *
 * Each entry names the minor's signer record, the age that applied, the
 * moment it was evaluated (the minor's own signature), and every guardian who
 * acted for them. An entry never carries the birth date: the record says the
 * signer was under the age, not how old they were.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class ConsentBasisBuilder {

	/**
	 * Constructor.
	 *
	 * @param GuardianAgePolicy $agePolicy Evaluates the age at the moment of each signature.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly GuardianAgePolicy $agePolicy,
	) {

	}//end __construct()

	/**
	 * The consent basis for a request whose signers have all signed.
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
	public function build(array $request, array $signers): array {
		$age = $this->agePolicy->appliedAge(data: $request);
		$basis = [];
		foreach ($signers as $signerId => $signer) {
			$entry = $this->entryFor(signerId: (string)$signerId, signer: $signer, signers: $signers, age: $age);
			if ($entry !== null) {
				$basis[] = $entry;
			}
		}

		return $basis;

	}//end build()

	/**
	 * The entry for one signer, or null when they signed at or over the age.
	 *
	 * @param string $signerId The signer record id.
	 * @param array<string, mixed> $signer The signer record.
	 * @param array<string, array<string, mixed>> $signers Every signer record of the request.
	 * @param int $age The age of consent.
	 *
	 * @return array<string, mixed>|null The entry, or null.
	 *
	 * @throws RuntimeException With code 409 when no guardian acted for a signer under the age.
	 */
	private function entryFor(string $signerId, array $signer, array $signers, int $age): ?array {
		$birthDate = (string)($signer['birthDate'] ?? '');
		$signedAt = (string)($signer['signedAt'] ?? '');
		if (($signer['role'] ?? 'signer') === 'guardian' || $birthDate === '') {
			return null;
		}

		$moment = $this->agePolicy->moment(value: $signedAt);
		if ($this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $moment) === false) {
			return null;
		}

		$guardians = $this->actedGuardians(signerId: $signerId, signers: $signers);
		if ($guardians === []) {
			throw new RuntimeException(
				'Signer ' . $signerId . ' signed under ' . $age . ' and no guardian has acted for them, so the request cannot complete',
				409
			);
		}

		return [
			'signerId' => $signerId,
			'displayName' => (string)($signer['displayName'] ?? ''),
			'guardianConsentAge' => $age,
			'evaluatedAt' => $signedAt,
			'basis' => $this->basisOf(guardians: $guardians),
			'guardians' => $guardians,
		];

	}//end entryFor()

	/**
	 * The guardians who signed for this signer.
	 *
	 * @param string $signerId The minor's signer record id.
	 * @param array<string, array<string, mixed>> $signers Every signer record of the request.
	 *
	 * @return list<array<string, mixed>> One entry per guardian who acted.
	 */
	private function actedGuardians(string $signerId, array $signers): array {
		$guardians = [];
		foreach ($signers as $guardianId => $candidate) {
			$pointsHere = ($candidate['guardianForSignerId'] ?? '') === $signerId;
			$acted = ($candidate['status'] ?? '') === 'SIGNED';
			if (($candidate['role'] ?? '') === 'guardian' && $pointsHere === true && $acted === true) {
				$guardians[] = $this->guardianEntry(guardianId: (string)$guardianId, guardian: $candidate);
			}
		}

		return $guardians;

	}//end actedGuardians()

	/**
	 * What the record keeps of one guardian's act.
	 *
	 * @param string $guardianId The guardian's signer record id.
	 * @param array<string, mixed> $guardian The guardian's signer record.
	 *
	 * @return array<string, mixed> Id, name, act, moment and identity, plus the statement and reference when present.
	 */
	private function guardianEntry(string $guardianId, array $guardian): array {
		$act = (string)($guardian['guardianAct'] ?? 'co-sign');
		$entry = [
			'signerId' => $guardianId,
			'displayName' => (string)($guardian['displayName'] ?? ''),
			'guardianAct' => $act,
			'actedAt' => (string)($guardian['signedAt'] ?? ''),
			'identity' => (array)($guardian['actingIdentity'] ?? []),
		];

		if ($act === 'consent') {
			$entry['consentStatement'] = (string)($guardian['consentStatement'] ?? '');
		}

		if ((string)($guardian['guardianRef'] ?? '') !== '') {
			$entry['guardianRef'] = (string)$guardian['guardianRef'];
		}

		return $entry;

	}//end guardianEntry()

	/**
	 * The basis the guardians' acts give: a co-signature when any guardian co-signed.
	 *
	 * @param list<array<string, mixed>> $guardians The guardians who acted.
	 *
	 * @return string `guardian-co-signature` or `guardian-consent`.
	 */
	private function basisOf(array $guardians): string {
		foreach ($guardians as $guardian) {
			if (($guardian['guardianAct'] ?? '') === 'co-sign') {
				return 'guardian-co-signature';
			}
		}

		return 'guardian-consent';

	}//end basisOf()
}//end class
