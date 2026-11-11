<?php

/**
 * Saved Object Reader
 *
 * Reads the identifier and the payload of an OpenRegister object as
 * ObjectService answers it: an entity with accessors, a serialisable object or
 * a plain array. Built by {@see AnonymizationPersistenceService} where it needs
 * one of them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Reads ids and payloads of saved OpenRegister objects, whatever shape they arrive in.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class SavedObjectReader {

	/**
	 * Read an OR entity object's payload, restoring its `@self` id when absent.
	 *
	 * @param object $candidate The OR entity object.
	 *
	 * @return array<string, mixed> The payload, or an empty array when it cannot be read.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function payloadOf(object $candidate): array {
		$payload = $this->objectPayloadOf(candidate: $candidate);
		if ($payload !== null) {
			return $payload;
		}

		return $this->serialisedPayloadOf(candidate: $candidate);
	}//end payloadOf()

	/**
	 * The payload getObject() answers, with the uuid restored as `@self` when absent.
	 *
	 * @param object $candidate The OR entity object.
	 *
	 * @return array<string, mixed>|null The payload, or null when getObject() is missing or answers no array.
	 */
	private function objectPayloadOf(object $candidate): ?array {
		if (method_exists(object_or_class: $candidate, method: 'getObject') === false) {
			return null;
		}

		$payload = $candidate->getObject();
		if (is_array($payload) === false) {
			return null;
		}

		return $this->withSelfUuid(payload: $payload, candidate: $candidate);
	}//end objectPayloadOf()

	/**
	 * Restore the candidate's uuid as the payload's `@self` id when the payload has no `@self`.
	 *
	 * @param array<string, mixed> $payload   The payload.
	 * @param object               $candidate The OR entity object.
	 *
	 * @return array<string, mixed> The payload.
	 */
	private function withSelfUuid(array $payload, object $candidate): array {
		if (isset($payload['@self']) === true
			|| method_exists(object_or_class: $candidate, method: 'getUuid') === false
		) {
			return $payload;
		}

		$uuid = $candidate->getUuid();
		if ($uuid !== null) {
			$payload['@self'] = ['id' => $uuid];
		}

		return $payload;
	}//end withSelfUuid()

	/**
	 * The payload jsonSerialize() answers.
	 *
	 * @param object $candidate The OR entity object.
	 *
	 * @return array<string, mixed> The payload, or an empty array when it is missing or not an array.
	 */
	private function serialisedPayloadOf(object $candidate): array {
		if (method_exists(object_or_class: $candidate, method: 'jsonSerialize') === false) {
			return [];
		}

		$payload = $candidate->jsonSerialize();
		if (is_array($payload) === false) {
			return [];
		}

		return $payload;
	}//end serialisedPayloadOf()

	/**
	 * Extract the persisted object's identifier from a saveObject() return value.
	 *
	 * @param mixed $saved The value returned by ObjectService::saveObject.
	 *
	 * @return string|null The object id/uuid, or null when it cannot be determined.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function savedId(mixed $saved): ?string {
		if (is_object($saved) === true) {
			$accessorId = $this->idFromAccessors(saved: $saved);
			if ($accessorId !== null) {
				return $accessorId;
			}

			if (method_exists(object_or_class: $saved, method: 'jsonSerialize') === true) {
				$saved = $saved->jsonSerialize();
			}
		}

		return $this->idFromArray(saved: $saved);
	}//end savedId()

	/**
	 * The first non-empty uuid or id the saved object's accessors answer, in that order.
	 *
	 * @param object $saved The saved object.
	 *
	 * @return string|null The id, or null when neither accessor answers one.
	 */
	private function idFromAccessors(object $saved): ?string {
		foreach (['getUuid', 'getId'] as $accessor) {
			if (method_exists(object_or_class: $saved, method: $accessor) === false) {
				continue;
			}

			$value = $saved->$accessor();
			if (empty($value) === false) {
				return (string)$value;
			}
		}

		return null;
	}//end idFromAccessors()

	/**
	 * The id of a saved array: its `@self` id, else its `@self` uuid, else its own id.
	 *
	 * @param mixed $saved The saved value.
	 *
	 * @return string|null The id, or null when it is not an array or carries no non-empty id.
	 */
	private function idFromArray(mixed $saved): ?string {
		if (is_array($saved) === false) {
			return null;
		}

		$self = ($saved['@self'] ?? []);
		$id = ($self['id'] ?? ($self['uuid'] ?? ($saved['id'] ?? null)));
		if (empty($id) === false) {
			return (string)$id;
		}

		return null;
	}//end idFromArray()
}//end class
