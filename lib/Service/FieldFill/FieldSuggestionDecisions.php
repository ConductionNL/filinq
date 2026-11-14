<?php

/**
 * Field Suggestion Decisions
 *
 * The clerk's half of model filling: read the record a suggestion is for,
 * take a decision per field (accept, change or reject), write the accepted
 * and changed values to the record as the clerk, with the clerk's rights,
 * and record every decision beside the suggestion (design D4, REQ-EMF-003).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Service
 * @package  OCA\Filinq\Service\FieldFill
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\FieldFill;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes what a person decided, and only that.
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class FieldSuggestionDecisions {

	/**
	 * Checks a changed value against its field.
	 *
	 * @var FieldValueCheck
	 */
	private readonly FieldValueCheck $valueCheck;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param FieldFillProfileService       $profiles       The record schemas' properties.
	 * @param IGroupManager                 $groupManager   Tells an administrator apart.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly FieldFillProfileService $profiles,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
		$this->valueCheck = new FieldValueCheck();

	}//end __construct()

	/**
	 * Take the person's decision on every suggested field and write what they accepted or changed.
	 *
	 * @param string               $id        The suggestion.
	 * @param array<string, mixed> $decisions Per field: `decision` (accept, change or reject) and, for change, `value`.
	 * @param string               $userId    The session user, who decides.
	 *
	 * @return array<string, mixed> The decided suggestion.
	 *
	 * @throws FieldFillRefused 404 for an unknown suggestion or record, 403 for someone else's suggestion
	 *                          or a record the user may not change, 409 when already decided,
	 *                          400 for a missing decision or a changed value that does not fit.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function decide(string $id, array $decisions, string $userId): array {
		$suggestion = $this->readSuggestion(id: $id, userId: $userId);
		$properties = $this->profiles->properties(schema: (string)$suggestion['schema']);
		$suggested  = (array)($suggestion['suggestions'] ?? []);
		$now        = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		$updates = [];
		$taken   = [];
		foreach ($suggested as $field => $entry) {
			$field    = (string)$field;
			$decision = $this->decisionFor(decisions: $decisions, field: $field);
			$value    = $this->decidedValue(decision: $decision, entry: (array)$entry, property: ($properties[$field] ?? []), field: $field);

			if ($decision['decision'] !== 'reject') {
				$updates[$field] = $value;
			}

			$taken[] = [
				'field' => $field,
				'decision' => $decision['decision'],
				'suggested' => ($entry['value'] ?? null),
				'written' => $value,
				'decidedBy' => $userId,
				'decidedAt' => $now,
			];
		}

		if ($updates !== []) {
			$this->writeRecord(suggestion: $suggestion, updates: $updates);
		}

		$suggestion['decisions'] = $taken;
		$suggestion['decidedBy'] = $userId;
		$suggestion['decidedAt'] = $now;
		$suggestion['status']    = 'decided';

		$object = $suggestion;
		unset($object['uuid'], $object['@self'], $object['id']);
		$this->objectResolver->resolve()->saveObject(
			object: $object,
			register: IntakeRepository::REGISTER,
			schema: SchemaFieldFillService::SCHEMA,
			uuid: $id
		);

		return $suggestion;

	}//end decide()


	/**
	 * A suggestion the user may decide, still undecided.
	 *
	 * @param string $id     The suggestion.
	 * @param string $userId The session user.
	 *
	 * @return array<string, mixed> The suggestion.
	 *
	 * @throws FieldFillRefused 404, 403 or 409.
	 */
	private function readSuggestion(string $id, string $userId): array {
		try {
			$found = $this->objectResolver->resolve()->find(id: $id, register: IntakeRepository::REGISTER, schema: SchemaFieldFillService::SCHEMA);
		} catch (Throwable $e) {
			$found = null;
		}

		if ($found === null) {
			throw new FieldFillRefused(message: 'The suggestion was not found.', code: 404);
		}

		$suggestion = $this->row(object: $found);
		if ((string)($suggestion['requestedBy'] ?? '') !== $userId && $this->groupManager->isAdmin($userId) === false) {
			throw new FieldFillRefused(message: 'Only the person who asked for the suggestion decides it.', code: 403);
		}

		if (($suggestion['status'] ?? 'suggested') !== 'suggested') {
			throw new FieldFillRefused(message: 'This suggestion has already been decided.', code: 409);
		}

		return $suggestion;

	}//end readSuggestion()


	/**
	 * The decision for one field.
	 *
	 * @param array<string, mixed> $decisions The decisions by field.
	 * @param string               $field     The field.
	 *
	 * @return array{decision: string, value: mixed} The decision.
	 *
	 * @throws FieldFillRefused 400 when the field has no valid decision.
	 */
	private function decisionFor(array $decisions, string $field): array {
		$given = ($decisions[$field] ?? null);
		if (is_string($given) === true) {
			$given = ['decision' => $given];
		}

		$decision = '';
		if (is_array($given) === true) {
			$decision = (string)($given['decision'] ?? '');
		}
		if (in_array($decision, SchemaFieldFillService::DECISIONS, true) === false) {
			throw new FieldFillRefused(
				message: sprintf('The field "%s" needs a decision: accept, change or reject.', $field),
				code: 400
			);
		}

		return ['decision' => $decision, 'value' => ($given['value'] ?? null)];

	}//end decisionFor()


	/**
	 * The value a decision writes.
	 *
	 * @param array{decision: string, value: mixed} $decision The decision.
	 * @param array<string, mixed>                  $entry    The suggested entry.
	 * @param array<string, mixed>                  $property The field's property.
	 * @param string                                $field    The field.
	 *
	 * @return mixed The value; null for a rejection.
	 *
	 * @throws FieldFillRefused 400 when a changed value does not fit the field.
	 */
	private function decidedValue(array $decision, array $entry, array $property, string $field): mixed {
		if ($decision['decision'] === 'reject') {
			return null;
		}

		if ($decision['decision'] === 'accept') {
			return ($entry['value'] ?? null);
		}

		if ($this->valueCheck->fits(value: $decision['value'], property: $property) === false) {
			throw new FieldFillRefused(
				message: sprintf('The changed value for "%s" does not fit the field.', $field),
				code: 400
			);
		}

		return $decision['value'];

	}//end decidedValue()


	/**
	 * Write the accepted and changed values to the record, as the session user.
	 *
	 * @param array<string, mixed> $suggestion The suggestion.
	 * @param array<string, mixed> $updates    The values by field.
	 *
	 * @return void
	 *
	 * @throws FieldFillRefused 404 or 403 when the record cannot be changed with the user's rights.
	 */
	private function writeRecord(array $suggestion, array $updates): void {
		$register = (string)$suggestion['register'];
		$schema   = (string)$suggestion['schema'];
		$objectId = (string)$suggestion['objectId'];

		$record = $this->record(register: $register, schema: $schema, objectId: $objectId);
		unset($record['@self'], $record['id'], $record['uuid']);

		try {
			// RBAC and multitenancy stay on: the write is the clerk's, with the clerk's rights (design D4).
			$this->objectResolver->resolve()->saveObject(
				object: array_merge($record, $updates),
				register: $register,
				schema: $schema,
				uuid: $objectId
			);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[SchemaFieldFillService] the record write was refused',
				context: ['error' => $e->getMessage()]
			);
			throw new FieldFillRefused(message: 'You may not change this record.', code: 403);
		}

	}//end writeRecord()


	/**
	 * The record, read with the session user's rights.
	 *
	 * @param string $register The register.
	 * @param string $schema   The schema.
	 * @param string $objectId The record.
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @throws FieldFillRefused 404 when it is not there or the user may not read it.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function record(string $register, string $schema, string $objectId): array {
		if ($register === '' || $schema === '' || $objectId === '') {
			throw new FieldFillRefused(message: 'Name the record: register, schema and id.', code: 400);
		}

		try {
			$found = $this->objectResolver->resolve()->find(id: $objectId, register: $register, schema: $schema);
		} catch (Throwable $e) {
			$found = null;
		}

		if ($found === null) {
			throw new FieldFillRefused(message: 'The record was not found or you may not read it.', code: 404);
		}

		return $this->row(object: $found);

	}//end record()


	/**
	 * A row's fields: its wrapped `object` payload when it has one, with the uuid.
	 *
	 * @param array<string, mixed> $row The row.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function fieldsOf(array $row): array {
		$fields = $row;
		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$fields = $row['object'];
		}

		$uuid = (string)($fields['uuid'] ?? ($row['uuid'] ?? ($row['@self']['id'] ?? ($row['id'] ?? ''))));
		if ($uuid !== '') {
			$fields['uuid'] = $uuid;
		}

		return $fields;

	}//end fieldsOf()


	/**
	 * An ObjectService result as an array.
	 *
	 * @param mixed $object The entity or array.
	 *
	 * @return array<string, mixed> The array.
	 */
	private function toArray(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return (array)$object->jsonSerialize();
		}

		return (array)$object;

	}//end toArray()

	/**
	 * A stored object as its fields, with its uuid.
	 *
	 * @param mixed $object The object as OpenRegister returns it.
	 *
	 * @return array<string, mixed> The fields.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function row(mixed $object): array {
		return $this->fieldsOf(row: $this->toArray(object: $object));

	}//end row()
}//end class
