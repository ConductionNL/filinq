<?php

/**
 * Unredacted Entities Validator
 *
 * Validates the `unredactedEntities[]` request payload shared by the single-file
 * and batch anonymization endpoints. Extracted from AnonymizationController and
 * BatchAnonymizationController, which carried byte-identical copies of the same
 * per-entry validation.
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
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IL10N;

/**
 * Validates `unredactedEntities[]` payload entries for the anonymize endpoints.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */
class UnredactedEntitiesValidator {
	/**
	 * Constructor for UnredactedEntitiesValidator
	 *
	 * @param IL10N $l10n Translator for the user-facing validation messages.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IL10N $l10n,
	) {

	}//end __construct()

	/**
	 * Validate the unredactedEntities[] payload entries.
	 *
	 * Each entry must have:
	 *   - entityId   (int, required)
	 *   - entityText (string, required)
	 *   - entityType (string, required)
	 *   - publicationBases (array of strings, required, non-empty)
	 * Optional:
	 *   - contactEmail   (string)
	 *   - contactAddress (string)
	 *
	 * @param array<int, mixed> $entries The unredactedEntities array from the request.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
	 *                                                             invalid entry, null when all valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	public function validate(array $entries): ?array {
		foreach ($entries as $idx => $entry) {
			$error = $this->entryError(entry: $entry, idx: $idx);
			if ($error !== null) {
				return $error;
			}
		}

		return null;
	}//end validate()

	/**
	 * Validate one entry: its shape, then its identity, publication basis and contact fields.
	 *
	 * @param mixed      $entry The entry being validated.
	 * @param int|string $idx   The entry's index, used in the error message.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function entryError(mixed $entry, int|string $idx): ?array {
		if (is_array($entry) === false) {
			return $this->badRequest(
				message: $this->l10n->t('Each unredactedEntities entry must be an object (index %s)', [$idx])
			);
		}

		return $this->validateIdentityFields(entry: $entry, idx: $idx)
			?? $this->validatePublicationBases(entry: $entry, idx: $idx)
			?? $this->validateContactFields(entry: $entry, idx: $idx);
	}//end entryError()

	/**
	 * Validate the required identity fields of one entry.
	 *
	 * @param array<string, mixed> $entry The entry being validated.
	 * @param int|string $idx The entry's index, used in the error message.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function validateIdentityFields(array $entry, int|string $idx): ?array {
		if (isset($entry['entityId']) === false || is_int($entry['entityId']) === false) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].entityId is required and must be an integer', [$idx])
			);
		}

		return $this->validateTextFields(entry: $entry, idx: $idx);
	}//end validateIdentityFields()

	/**
	 * Validate the required entity text and entity type of one entry.
	 *
	 * @param array<string, mixed> $entry The entry being validated.
	 * @param int|string $idx The entry's index, used in the error message.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function validateTextFields(array $entry, int|string $idx): ?array {
		if ($this->isFilledString(value: ($entry['entityText'] ?? null)) === false) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].entityText is required and must be a string', [$idx])
			);
		}

		if ($this->isFilledString(value: ($entry['entityType'] ?? null)) === false) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].entityType is required and must be a string', [$idx])
			);
		}

		return null;
	}//end validateTextFields()

	/**
	 * Validate the publicationBases[] field of one entry.
	 *
	 * @param array<string, mixed> $entry The entry being validated.
	 * @param int|string $idx The entry's index, used in the error message.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function validatePublicationBases(array $entry, int|string $idx): ?array {
		if (isset($entry['publicationBases']) === false || is_array($entry['publicationBases']) === false) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].publicationBases is required and must be an array', [$idx])
			);
		}

		return $this->validatePublicationBaseItems(bases: $entry['publicationBases'], idx: $idx);
	}//end validatePublicationBases()

	/**
	 * Validate that a publicationBases[] list holds at least one basis, all strings.
	 *
	 * @param array<mixed> $bases The publication bases of one entry.
	 * @param int|string   $idx   The entry's index, used in the error message.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function validatePublicationBaseItems(array $bases, int|string $idx): ?array {
		if (empty($bases) === true) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].publicationBases must contain at least one basis', [$idx])
			);
		}

		if ($this->allStrings(values: $bases) === false) {
			return $this->badRequest(
				message: $this->l10n->t('Each entry in unredactedEntities[%s].publicationBases must be a string', [$idx])
			);
		}

		return null;
	}//end validatePublicationBaseItems()

	/**
	 * Validate the optional contact fields of one entry.
	 *
	 * @param array<string, mixed> $entry The entry being validated.
	 * @param int|string $idx The entry's index, used in the error message.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function validateContactFields(array $entry, int|string $idx): ?array {
		if ($this->isGivenNonString(value: ($entry['contactEmail'] ?? null)) === true) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].contactEmail must be a string', [$idx])
			);
		}

		if ($this->isGivenNonString(value: ($entry['contactAddress'] ?? null)) === true) {
			return $this->badRequest(
				message: $this->l10n->t('unredactedEntities[%s].contactAddress must be a string', [$idx])
			);
		}

		return null;
	}//end validateContactFields()

	/**
	 * Whether a value is a non-empty string.
	 *
	 * @param mixed $value The field value, null when absent.
	 *
	 * @return bool True for a string that is not empty().
	 */
	private function isFilledString(mixed $value): bool {
		return empty($value) === false && is_string($value) === true;

	}//end isFilledString()

	/**
	 * Whether an optional field is given but is not a string.
	 *
	 * @param mixed $value The field value, null when absent.
	 *
	 * @return bool True when the value is set and not a string.
	 */
	private function isGivenNonString(mixed $value): bool {
		return $value !== null && is_string($value) === false;

	}//end isGivenNonString()

	/**
	 * Whether every value in a list is a string.
	 *
	 * @param array<mixed> $values The values.
	 *
	 * @return bool True when none of them is something else.
	 */
	private function allStrings(array $values): bool {
		foreach ($values as $value) {
			if (is_string($value) === false) {
				return false;
			}
		}

		return true;

	}//end allStrings()

	/**
	 * Build the HTTP 400 status/body pair for a validation failure.
	 *
	 * @param string $message The already-translated error message.
	 *
	 * @return array{status: int, body: array<string, mixed>} The 400 payload.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	private function badRequest(string $message): array {
		return [
			'status' => 400,
			'body' => ['error' => $message],
		];

	}//end badRequest()
}//end class
