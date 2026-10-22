<?php

/**
 * Guardian Entry Preparer
 *
 * Validates the guardian fields on the signer entries of a new signing request
 * and works out which guardian stands beside which signer, before anything is
 * persisted.
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
use InvalidArgumentException;

/**
 * Turns signer entries into the guardian fields their records carry.
 *
 * A consumer names a guardian with `role: guardian` and `guardianFor`, the
 * `userId` or email of another entry in the same list. Every refusal here is
 * an InvalidArgumentException with code 400, thrown before the request or any
 * signer record is written.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class GuardianEntryPreparer {

	/**
	 * The acts a guardian can perform.
	 *
	 * @var list<string>
	 */
	public const ACTS = ['co-sign', 'consent'];

	/**
	 * Constructor.
	 *
	 * @param GuardianAgePolicy $agePolicy Parses birth dates and evaluates the age.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly GuardianAgePolicy $agePolicy,
	) {

	}//end __construct()

	/**
	 * Validate the entries and link each guardian to their signer.
	 *
	 * @param array<int|string, mixed> $signers The signer entries as the consumer sent them.
	 * @param int $age The age of consent that applies to the request.
	 * @param DateTimeImmutable $now The moment of creation.
	 *
	 * @return array{fields: array<int|string, array<string, mixed>>, links: array<int|string, int|string>}
	 *     `fields` holds, per entry, the guardian fields to store on its signer
	 *     record ([] for an entry outside the rule); `links` maps each guardian
	 *     entry to the entry it stands beside.
	 *
	 * @throws InvalidArgumentException With code 400 when an entry is invalid.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function prepare(array $signers, int $age, DateTimeImmutable $now): array {
		$fields = [];
		foreach ($signers as $index => $entry) {
			$fields[$index] = $this->entryFields(entry: (array)$entry);
		}

		$links = $this->resolveLinks(signers: $signers, fields: $fields);
		$this->assertMinorsGuarded(fields: $fields, links: $links, age: $age, now: $now);

		return ['fields' => $fields, 'links' => $links];

	}//end prepare()

	/**
	 * The guardian fields one entry carries.
	 *
	 * @param array<string, mixed> $entry The signer entry.
	 *
	 * @return array<string, mixed> The fields to store, or [] for an entry outside the rule.
	 *
	 * @throws InvalidArgumentException With code 400 when the entry is invalid.
	 */
	private function entryFields(array $entry): array {
		$birthDate = trim((string)($entry['birthDate'] ?? ''));
		$role = $this->roleOf(entry: $entry);
		if ($role === '') {
			return [];
		}

		$fields = ['role' => $role];
		if ($birthDate !== '') {
			if ($this->agePolicy->parseBirthDate(value: $birthDate) === null) {
				throw new InvalidArgumentException('A birth date must be an ISO 8601 date (YYYY-MM-DD)', 400);
			}

			$fields['birthDate'] = $birthDate;
		}

		if ($role === 'guardian') {
			$fields = array_merge($fields, $this->guardianFields(entry: $entry));
		}

		return $fields;

	}//end entryFields()

	/**
	 * The role an entry declares, or '' when it takes no part in the rule.
	 *
	 * @param array<string, mixed> $entry The signer entry.
	 *
	 * @return string `signer`, `guardian`, or ''.
	 *
	 * @throws InvalidArgumentException With code 400 for an unknown role.
	 */
	private function roleOf(array $entry): string {
		$role = trim((string)($entry['role'] ?? ''));
		$named = trim((string)($entry['guardianFor'] ?? '')) !== '';
		$hasBirthDate = trim((string)($entry['birthDate'] ?? '')) !== '';

		if ($role === '' && $named === true) {
			$role = 'guardian';
		}

		if ($role === '' && $hasBirthDate === true) {
			$role = 'signer';
		}

		if ($role !== '' && in_array($role, ['signer', 'guardian'], true) === false) {
			throw new InvalidArgumentException('A signer role is signer or guardian, not "' . $role . '"', 400);
		}

		return $role;

	}//end roleOf()

	/**
	 * The fields only a guardian entry carries.
	 *
	 * @param array<string, mixed> $entry The guardian entry.
	 *
	 * @return array<string, mixed> The act, and the statement and reference when present.
	 *
	 * @throws InvalidArgumentException With code 400 for an unknown act or a consent without a statement.
	 */
	private function guardianFields(array $entry): array {
		$act = trim((string)($entry['guardianAct'] ?? 'co-sign'));
		if (in_array($act, self::ACTS, true) === false) {
			throw new InvalidArgumentException('A guardian act is co-sign or consent, not "' . $act . '"', 400);
		}

		$fields = ['guardianAct' => $act];
		$statement = trim((string)($entry['consentStatement'] ?? ''));
		if ($act === 'consent' && $statement === '') {
			throw new InvalidArgumentException('A consent act needs the statement the guardian consents to', 400);
		}

		if ($statement !== '') {
			$fields['consentStatement'] = $statement;
		}

		$reference = trim((string)($entry['guardianRef'] ?? ''));
		if ($reference !== '') {
			$fields['guardianRef'] = $reference;
		}

		return $fields;

	}//end guardianFields()

	/**
	 * Link each guardian entry to the entry it names in `guardianFor`.
	 *
	 * @param array<int|string, mixed> $signers The signer entries.
	 * @param array<int|string, array<string, mixed>> $fields The prepared fields per entry.
	 *
	 * @return array<int|string, int|string> Guardian entry index to signer entry index.
	 *
	 * @throws InvalidArgumentException With code 400 when a guardian names nobody, themselves or another guardian.
	 */
	private function resolveLinks(array $signers, array $fields): array {
		$links = [];
		foreach ($fields as $index => $entryFields) {
			if (($entryFields['role'] ?? '') !== 'guardian') {
				continue;
			}

			$named = trim((string)(((array)$signers[$index])['guardianFor'] ?? ''));
			$target = $this->findEntry(signers: $signers, key: $named, except: $index);
			if ($target === null || ($fields[$target]['role'] ?? 'signer') === 'guardian') {
				throw new InvalidArgumentException(
					'A guardian entry must name another signer on this request in guardianFor',
					400
				);
			}

			$links[$index] = $target;
		}

		return $links;

	}//end resolveLinks()

	/**
	 * Find the entry a guardian names, by user id or by email.
	 *
	 * @param array<int|string, mixed> $signers The signer entries.
	 * @param string $key The `userId` or email the guardian named.
	 * @param int|string $except The guardian's own index, never a match.
	 *
	 * @return int|string|null The matching entry's index, or null.
	 */
	private function findEntry(array $signers, string $key, int|string $except): int|string|null {
		if ($key === '') {
			return null;
		}

		foreach ($signers as $index => $entry) {
			if ($index !== $except && $this->entryMatches(entry: (array)$entry, key: $key) === true) {
				return $index;
			}
		}

		return null;

	}//end findEntry()

	/**
	 * Does this entry answer to the key a guardian named.
	 *
	 * @param array<string, mixed> $entry The candidate entry.
	 * @param string $key The `userId` or email named.
	 *
	 * @return bool True on a user id match, or a case-insensitive email match.
	 */
	private function entryMatches(array $entry, string $key): bool {
		$userId = (string)($entry['userId'] ?? '');
		$email = (string)($entry['email'] ?? '');

		return ($userId !== '' && $userId === $key) || ($email !== '' && strcasecmp($email, $key) === 0);

	}//end entryMatches()

	/**
	 * Refuse a request that names a minor without a guardian, or a minor as guardian.
	 *
	 * @param array<int|string, array<string, mixed>> $fields The prepared fields per entry.
	 * @param array<int|string, int|string> $links Guardian entry index to signer entry index.
	 * @param int $age The age of consent.
	 * @param DateTimeImmutable $now The moment of creation.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException With code 400.
	 */
	private function assertMinorsGuarded(array $fields, array $links, int $age, DateTimeImmutable $now): void {
		foreach ($fields as $index => $entryFields) {
			$birthDate = (string)($entryFields['birthDate'] ?? '');
			if ($birthDate === '' || $this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $now) === false) {
				continue;
			}

			if (($entryFields['role'] ?? '') === 'guardian') {
				throw new InvalidArgumentException('A guardian must have reached the age of ' . $age, 400);
			}

			if (in_array($index, $links, true) === false) {
				throw new InvalidArgumentException(
					'A signer under ' . $age . ' needs a guardian on this request, and this request names none for them',
					400
				);
			}
		}

	}//end assertMinorsGuarded()
}//end class
