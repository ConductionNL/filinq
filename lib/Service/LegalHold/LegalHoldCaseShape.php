<?php

/**
 * The shape of a legal hold case: its scope, its placement entries, its protection.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\LegalHold
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCA\Filinq\Exception\LegalHoldRefusedException;

/**
 * Pure rules over a case array: what is in scope, which active cases cover a
 * record, what one record's placement says, and whether the case protects
 * everything it lists. No I/O: LegalHoldCaseService keeps the order of the
 * writes, this class keeps the arithmetic.
 */
class LegalHoldCaseShape {

	/**
	 * The matter types a case can have.
	 *
	 * @var array<int, string>
	 */
	public const HOLD_TYPES = ['litigation', 'audit', 'woo-appeal', 'other'];

	/**
	 * The active cases other than this one that list a record.
	 *
	 * @param string $ref         The record uuid.
	 * @param string $exceptUuid  The case to leave out.
	 * @param array  $activeCases The active cases, read once per run.
	 *
	 * @return array<int, array<string, mixed>> The covering cases.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function covering(string $ref, string $exceptUuid, array $activeCases): array {
		$covering = [];
		foreach ($activeCases as $case) {
			if (($case['uuid'] ?? '') === $exceptUuid || ($case['status'] ?? '') !== 'active') {
				continue;
			}

			if (array_key_exists($ref, $this->scope(case: $case)) === true) {
				$covering[] = $case;
			}
		}

		return $covering;

	}//end covering()

	/**
	 * Complete only when every record in scope is verifiably frozen by this app.
	 *
	 * @param array<string, mixed> $case The case.
	 *
	 * @return string complete or partial.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function protection(array $case): string {
		if ($case['status'] !== 'active') {
			return (string) ($case['protection'] ?? 'complete');
		}

		foreach (array_keys($this->scope(case: $case)) as $ref) {
			$entry = $this->entryFor(case: $case, ref: (string) $ref);
			if ($entry === null || ($entry['record'] ?? '') !== 'held' || ($entry['file'] ?? '') === 'failed') {
				return 'partial';
			}
		}

		return 'complete';

	}//end protection()

	/**
	 * Replace or add the fan-out entry for one record.
	 *
	 * @param array<string, mixed> $case  The case.
	 * @param array<string, mixed> $entry The entry.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function withEntry(array $case, array $entry): array {
		$entries = [];
		foreach ((array) ($case['fanOut'] ?? []) as $existing) {
			if (($existing['ref'] ?? '') !== $entry['ref']) {
				$entries[] = $existing;
			}
		}

		$entries[] = $entry;
		$case['fanOut'] = $entries;

		return $case;

	}//end withEntry()

	/**
	 * The fan-out entry for a record, or null.
	 *
	 * @param array<string, mixed> $case The case.
	 * @param string               $ref  The record uuid.
	 *
	 * @return array<string, mixed>|null The entry.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function entryFor(array $case, string $ref): ?array {
		foreach ((array) ($case['fanOut'] ?? []) as $entry) {
			if (($entry['ref'] ?? '') === $ref) {
				return $entry;
			}
		}

		return null;

	}//end entryFor()

	/**
	 * Every record in the case's scope.
	 *
	 * @param array<string, mixed> $case The case.
	 *
	 * @return array<string, string> Record uuid => document|dossier.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function scope(array $case): array {
		$scope = [];
		foreach ((array) ($case['scopeDocuments'] ?? []) as $ref) {
			$scope[(string) $ref] = 'document';
		}

		foreach ((array) ($case['scopeDossiers'] ?? []) as $ref) {
			$scope[(string) $ref] = 'dossier';
		}

		return $scope;

	}//end scope()

	/**
	 * A required text field.
	 *
	 * @param array<string, mixed> $input The input.
	 * @param string               $field The field.
	 * @param int                  $max   The longest allowed value.
	 *
	 * @return string The value.
	 *
	 * @throws LegalHoldRefusedException When it is empty.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function required(array $input, string $field, int $max): string {
		$value = trim((string) ($input[$field] ?? ''));
		if ($value === '') {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_INVALID, message: 'A hold needs a ' . $field . '.');
		}

		return mb_substr($value, 0, $max);

	}//end required()

	/**
	 * The matter type.
	 *
	 * @param array<string, mixed> $input The input.
	 *
	 * @return string The type.
	 *
	 * @throws LegalHoldRefusedException When it is not a known type.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function holdType(array $input): string {
		$type = (string) ($input['holdType'] ?? '');
		if (in_array($type, self::HOLD_TYPES, true) === false) {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_INVALID, message: 'Unknown matter type ' . $type . '.');
		}

		return $type;

	}//end holdType()

	/**
	 * Clean record references: strings, trimmed, each once.
	 *
	 * @param mixed $value The submitted list.
	 *
	 * @return array<int, string> The references.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function refs(mixed $value): array {
		if (is_array($value) === false) {
			return [];
		}

		$refs = [];
		foreach ($value as $ref) {
			if (is_scalar($ref) === false) {
				continue;
			}

			$ref = trim((string) $ref);
			if ($ref !== '' && mb_strlen($ref) <= 64 && in_array($ref, $refs, true) === false) {
				$refs[] = $ref;
			}
		}

		return $refs;

	}//end refs()
}//end class
