<?php

/**
 * Anonymize Request Validator
 *
 * Validates the request body of the single-file anonymize endpoint
 * (`POST /api/anonymization/{fileId}/anonymize`). Extracted from
 * AnonymizationController to keep the controller a thin HTTP boundary.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IL10N;

/**
 * Validates and normalises the anonymize endpoint's request body.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */
class AnonymizeRequestValidator {

	/**
	 * Validator for the shared `unredactedEntities[]` payload shape.
	 *
	 * @var UnredactedEntitiesValidator
	 */
	private readonly UnredactedEntitiesValidator $unredactedValidator;

	/**
	 * Constructor for AnonymizeRequestValidator
	 *
	 * @param IL10N $l10n Translator for the user-facing validation messages.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IL10N $l10n,
	) {
		$this->unredactedValidator = new UnredactedEntitiesValidator($l10n);

	}//end __construct()

	/**
	 * Validate the anonymize request body and normalise it for the executor.
	 *
	 * The checks run in the same order the controller used to run them inline,
	 * so a malformed body still yields the same first-failing HTTP 400.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array{error: array{status: int, body: array<string, mixed>}|null,
	 *               request: array<string, mixed>|null} The first validation failure, or the
	 *                                                   normalised request under `request`.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	public function validateBody(array $params): array {
		$entities = ($params['entities'] ?? []);
		if (is_array($entities) === false || empty($entities) === true) {
			return $this->rejected(
				error: $this->badRequest(message: $this->l10n->t('No entities provided for anonymization'))
			);
		}

		$appendBasisSummary = $this->extractAppendBasisSummary(params: $params);
		if (is_bool($appendBasisSummary) === false) {
			return $this->rejected(error: $appendBasisSummary);
		}

		$unredacted = ($params['unredactedEntities'] ?? []);
		if (is_array($unredacted) === false) {
			return $this->rejected(
				error: $this->badRequest(message: $this->l10n->t('unredactedEntities must be an array'))
			);
		}

		$unredactedError = $this->unredactedValidator->validate(entries: $unredacted);
		if ($unredactedError !== null) {
			return $this->rejected(error: $unredactedError);
		}

		$overrides = ($params['acknowledgedOverrides'] ?? []);
		if (is_array($overrides) === false) {
			return $this->rejected(
				error: $this->badRequest(message: $this->l10n->t('acknowledgedOverrides must be an array'))
			);
		}

		$overridesError = $this->validateOverrides(overrides: $overrides);
		if ($overridesError !== null) {
			return $this->rejected(error: $overridesError);
		}

		return [
			'error' => null,
			'request' => [
				'entities' => $entities,
				'hasStrayBases' => $this->hasStrayBases(entities: $entities),
				'appendBasisSummary' => $appendBasisSummary,
				'unredactedEntities' => $unredacted,
				'overrides' => $overrides,
			],
		];

	}//end validateBody()

	/**
	 * Extract and validate the appendBasisSummary flag from request params.
	 *
	 * Returns the HTTP 400 status/body pair when the field is present but not boolean.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return bool|array{status: int, body: array<string, mixed>} False when omitted, the supplied
	 *                                                             boolean when set, or the 400
	 *                                                             payload on a type error.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
	 */
	private function extractAppendBasisSummary(array $params): bool|array {
		if (array_key_exists('appendBasisSummary', $params) === false) {
			return false;
		}

		$value = $params['appendBasisSummary'];
		if (is_bool($value) === false) {
			return $this->badRequest(message: $this->l10n->t('appendBasisSummary must be a boolean'));
		}

		return $value;
	}//end extractAppendBasisSummary()

	/**
	 * Validate the acknowledgedOverrides[] payload entries.
	 *
	 * Each entry must have:
	 *   - ruleId   (string, required)
	 *   - entityId (int, required)
	 * Optional:
	 *   - reason (string)
	 *
	 * @param array<int, mixed> $overrides The acknowledgedOverrides array from the request.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
	 *                                                             invalid entry, null when all valid.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function validateOverrides(array $overrides): ?array {
		foreach ($overrides as $idx => $override) {
			if (is_array($override) === false) {
				return $this->badRequest(
					message: $this->l10n->t('Each acknowledgedOverrides entry must be an object (index %s)', [$idx])
				);
			}

			if (empty($override['ruleId']) === true || is_string($override['ruleId']) === false) {
				return $this->badRequest(
					message: $this->l10n->t('acknowledgedOverrides[%s].ruleId is required and must be a string', [$idx])
				);
			}

			if (isset($override['entityId']) === false || is_int($override['entityId']) === false) {
				return $this->badRequest(
					message: $this->l10n->t('acknowledgedOverrides[%s].entityId is required and must be an integer', [$idx])
				);
			}

			if (isset($override['reason']) === true && is_string($override['reason']) === false) {
				return $this->badRequest(
					message: $this->l10n->t('acknowledgedOverrides[%s].reason must be a string', [$idx])
				);
			}
		}//end foreach

		return null;
	}//end validateOverrides()

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
	private function hasStrayBases(array $entities): bool {
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
	private function rejected(array $error): array {
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
	private function badRequest(string $message): array {
		return [
			'status' => 400,
			'body' => ['error' => $message],
		];

	}//end badRequest()
}//end class
