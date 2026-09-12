<?php

/**
 * Dossier Object Reader
 *
 * WHICH REGISTER THE DOSSIER SURFACE READS, AND HOW TO READ AN OBJECT OUT OF
 * IT. OpenRegister hands back objects of several shapes depending on how they
 * were loaded — an entity with `jsonSerialize()`, one with `getObject()`, or a
 * plain stdClass from a cached read — and every caller that guessed wrong got
 * an empty payload rather than an error. Reading them in one place is what
 * keeps "this dossier has no documents" from meaning "I asked the object the
 * wrong question".
 *
 * The register and schema slugs live here rather than on the service because
 * three classes now need them and one of them has to own the definition.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Reads the shape of an OpenRegister dossier object.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */
class DossierObjectReader {

	/**
	 * The register every filinq schema lives in.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The dossier schema slug.
	 *
	 * @var string
	 */
	public const SCHEMA = 'dossier';

	/**
	 * The status a dossier without one is read as.
	 *
	 * `status` is optional and existing objects were deliberately not
	 * migrated, so absence is a value with a meaning, not missing data.
	 *
	 * @var string
	 */
	public const DEFAULT_STATUS = 'open';

	/**
	 * The object's payload as an array.
	 *
	 * @param object $object The OpenRegister object.
	 *
	 * @return array<string, mixed> The payload.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function payloadOf(object $object): array {
		if (method_exists($object, 'jsonSerialize') === true) {
			$payload = $object->jsonSerialize();
			if (is_array($payload) === true) {
				return $payload;
			}
		}

		if (method_exists($object, 'getObject') === true) {
			$payload = $object->getObject();
			if (is_array($payload) === true) {
				return $payload;
			}
		}

		return (array)$object;

	}//end payloadOf()

	/**
	 * The object's UUID.
	 *
	 * @param object $object The OpenRegister object.
	 * @param array<string, mixed> $payload Its payload.
	 *
	 * @return string The UUID, or '' when it has none.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function uuidOf(object $object, array $payload): string {
		$uuid = ($payload['@self']['id'] ?? $payload['id'] ?? $payload['uuid'] ?? '');
		if ($uuid === '' && method_exists($object, 'getUuid') === true) {
			$uuid = $object->getUuid();
		}

		return (string)$uuid;

	}//end uuidOf()

	/**
	 * The dossier's status, defaulting for objects that predate the property.
	 *
	 * @param array<string, mixed> $payload The dossier payload.
	 *
	 * @return string The status.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function statusOf(array $payload): string {
		$status = trim((string)($payload['status'] ?? ''));

		return $this->firstNonEmpty(value: $status, fallback: self::DEFAULT_STATUS);

	}//end statusOf()

	/**
	 * The dossier's explicit membership references.
	 *
	 * @param array<string, mixed> $payload The dossier payload.
	 *
	 * @return array<int, string> The references.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function documentRefs(array $payload): array {
		$refs = ($payload['documents'] ?? []);
		if (is_array($refs) === false) {
			return [];
		}

		return array_values(array_map(static fn ($ref): string => (string)$ref, $refs));

	}//end documentRefs()

	/**
	 * Every object of one schema in this app's register.
	 *
	 * `ObjectService::findAll()` takes a CONFIG ARRAY, not `register:` /
	 * `schema:` named arguments — those exist on `find()` and `saveObject()`
	 * but not here. Calling it the other way throws "Unknown named parameter
	 * $register" at runtime, which every guarded caller then swallows.
	 *
	 * @param object $objectService OpenRegister's object service.
	 * @param string $schema The schema slug.
	 *
	 * @return array<int, object> The objects.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function findAllOf(object $objectService, string $schema): array {
		return $objectService->findAll(
			config: [
				'filters' => [
					'register' => self::REGISTER,
					'schema' => $schema,
				],
			]
		);

	}//end findAllOf()

	/**
	 * The first value that is not an empty string.
	 *
	 * @param string $value The preferred value.
	 * @param string $fallback The value to use when $value is empty.
	 *
	 * @return string Whichever is non-empty.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function firstNonEmpty(string $value, string $fallback): string {
		if ($value !== '') {
			return $value;
		}

		return $fallback;

	}//end firstNonEmpty()

}//end class
