<?php

/**
 * Anonymize Request Rules
 *
 * The request-body rules the single-file and batch anonymize validators
 * share: spotting stray `bases[]` fields on entity entries, and the shape of
 * a validation refusal. Extracted from AnonymizeRequestValidator and
 * BatchRequestService, which carried identical copies.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Shared rules of the anonymize request validators.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */
final class AnonymizeRequestRules {

	/**
	 * Whether the body lacks a non-empty `entities` list.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return bool True when `entities` is absent, not an array, or empty.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	public function hasNoEntities(array $params): bool {
		$entities = ($params['entities'] ?? []);
		return is_array($entities) === false || empty($entities) === true;
	}//end hasNoEntities()

	/**
	 * Detect stray `bases[]` fields on entity entries.
	 *
	 * Bases are set through OpenRegister's PATCH /api/entity-relations/{id};
	 * a stray field on the anonymize payload is ignored but reported back as
	 * `ignoredFields` for GDPR accountability.
	 *
	 * @param array<int, mixed> $entities The submitted entity entries.
	 *
	 * @return bool True when at least one entry carries a `bases` key.
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
	 */
	public function hasStrayBases(array $entities): bool {
		foreach ($entities as $entity) {
			if (is_array($entity) === true && array_key_exists('bases', $entity) === true) {
				return true;
			}
		}

		return false;
	}//end hasStrayBases()

	/**
	 * Wrap a validation failure in the validateBody() return shape.
	 *
	 * @param array{status: int, body: array<string, mixed>} $error The failing status/body pair.
	 *
	 * @return array{error: array{status: int, body: array<string, mixed>}, request: null} The rejection.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	public function rejected(array $error): array {
		return ['error' => $error, 'request' => null];
	}//end rejected()

	/**
	 * Build the HTTP 400 status/body pair for a validation failure.
	 *
	 * @param string $message The already-translated error message.
	 *
	 * @return array{status: int, body: array<string, mixed>} The 400 payload.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	public function badRequest(string $message): array {
		return [
			'status' => 400,
			'body' => ['error' => $message],
		];

	}//end badRequest()

	/**
	 * The 400 refusal, already wrapped in the validateBody() return shape.
	 *
	 * @param string $message The already-translated error message.
	 *
	 * @return array{error: array{status: int, body: array<string, mixed>}, request: null} The rejection.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	public function refused(string $message): array {
		return $this->rejected(error: $this->badRequest(message: $message));
	}//end refused()
}//end class
