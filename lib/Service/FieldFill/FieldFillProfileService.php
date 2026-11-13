<?php

/**
 * Field fill profile service
 *
 * Reads and stores which fields of a record schema a text model may suggest values for, and reads that schema's property definitions.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\FieldFill;

use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The declared field-fill profiles and the schemas they name.
 *
 * @category Service
 * @package  OCA\Filinq\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class FieldFillProfileService {

	/**
	 * The schema slug of a profile.
	 *
	 * @var string
	 */
	public const SCHEMA = 'fieldFillProfile';

	/**
	 * The refusal for a profile that names a field with the bsn format (UAVG art. 46).
	 *
	 * @var string
	 */
	public const BSN_REFUSAL = 'A BSN field cannot be filled by a model.';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param ContainerInterface            $container      Resolves OpenRegister's SchemaMapper lazily.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The active profile for a register and schema, or null when there is none.
	 *
	 * @param string $register The register slug.
	 * @param string $schema   The schema slug.
	 *
	 * @return array<string, mixed>|null The profile.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function activeFor(string $register, string $schema): ?array {
		if ($register === '' || $schema === '') {
			return null;
		}

		foreach ($this->search(filters: ['register' => $register, 'schema' => $schema]) as $profile) {
			if (($profile['active'] ?? false) === true && $this->fieldNames(profile: $profile) !== []) {
				return $profile;
			}
		}

		return null;

	}//end activeFor()

	/**
	 * Every declared profile, active or not.
	 *
	 * @return array<int, array<string, mixed>> The profiles.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function all(): array {
		return $this->search(filters: []);

	}//end all()

	/**
	 * Store a profile after checking it against the schema it names.
	 *
	 * @param array<string, mixed> $profile The profile: declaringApp, register, schema, fields and active.
	 * @param string|null          $uuid    The profile to replace, or null for a new one.
	 *
	 * @return array<string, mixed> The stored profile.
	 *
	 * @throws FieldFillRefused 400 when the profile is incomplete, names an unknown field or a bsn field.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function save(array $profile, ?string $uuid = null): array {
		$clean = [
			'declaringApp' => trim((string)($profile['declaringApp'] ?? 'filinq')),
			'register' => trim((string)($profile['register'] ?? '')),
			'schema' => trim((string)($profile['schema'] ?? '')),
			'fields' => $this->fieldNames(profile: $profile),
			'active' => (($profile['active'] ?? false) === true),
		];

		if ($clean['declaringApp'] === '') {
			$clean['declaringApp'] = 'filinq';
		}

		if ($clean['register'] === '' || $clean['schema'] === '' || $clean['fields'] === []) {
			throw new FieldFillRefused(message: 'A profile names a register, a schema and at least one field.', code: 400);
		}

		$this->refuseFields(fields: $clean['fields'], properties: $this->properties(schema: $clean['schema']));

		$arguments = ['object' => $clean, 'register' => IntakeRepository::REGISTER, 'schema' => self::SCHEMA];
		if ($uuid !== null && $uuid !== '') {
			$arguments['uuid'] = $uuid;
		}

		$saved = $this->objectResolver->resolve()->saveObject(...$arguments);
		$row   = $this->normalise(row: $saved);

		return ($row ?? $clean);

	}//end save()

	/**
	 * The property definitions of a schema, by property name.
	 *
	 * @param string $schema The schema slug or id.
	 *
	 * @return array<string, array<string, mixed>> The properties; empty when the schema cannot be read.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function properties(string $schema): array {
		try {
			$found = $this->container->get('OCA\OpenRegister\Db\SchemaMapper')->find($schema);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[FieldFillProfileService] could not read schema {schema}',
				context: ['schema' => $schema, 'error' => $e->getMessage()]
			);
			return [];
		}

		if (is_object($found) === false || method_exists($found, 'getProperties') === false) {
			return [];
		}

		$properties = [];
		foreach ((array)$found->getProperties() as $name => $definition) {
			if (is_array($definition) === true) {
				$properties[(string)$name] = $definition;
			}
		}

		return $properties;

	}//end properties()

	/**
	 * Whether a property is a BSN.
	 *
	 * @param array<string, mixed> $property The property definition.
	 *
	 * @return bool True for the bsn format.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function isBsn(array $property): bool {
		return strtolower(trim((string)($property['format'] ?? ''))) === 'bsn';

	}//end isBsn()

	/**
	 * The field names a profile lists, trimmed, without blanks or repeats.
	 *
	 * @param array<string, mixed> $profile The profile.
	 *
	 * @return array<int, string> The field names.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function fieldNames(array $profile): array {
		$fields = ($profile['fields'] ?? []);
		if (is_array($fields) === false) {
			return [];
		}

		$names = [];
		foreach ($fields as $field) {
			if (is_string($field) === false) {
				continue;
			}

			$field = trim($field);
			if ($field !== '' && in_array($field, $names, true) === false) {
				$names[] = $field;
			}
		}

		return $names;

	}//end fieldNames()

	/**
	 * Refuse a field the schema does not have, and any bsn field.
	 *
	 * @param array<int, string>                  $fields     The declared fields.
	 * @param array<string, array<string, mixed>> $properties The schema's properties.
	 *
	 * @return void
	 *
	 * @throws FieldFillRefused 400 on the first refused field.
	 */
	private function refuseFields(array $fields, array $properties): void {
		if ($properties === []) {
			throw new FieldFillRefused(message: 'The schema could not be read.', code: 400);
		}

		foreach ($fields as $field) {
			if (isset($properties[$field]) === false) {
				throw new FieldFillRefused(message: sprintf('The schema has no field "%s".', $field), code: 400);
			}

			if ($this->isBsn(property: $properties[$field]) === true) {
				throw new FieldFillRefused(message: self::BSN_REFUSAL, code: 400);
			}
		}

	}//end refuseFields()

	/**
	 * Profiles matching the filters.
	 *
	 * @param array<string, string> $filters Bare-key filters.
	 *
	 * @return array<int, array<string, mixed>> The profiles.
	 */
	private function search(array $filters): array {
		try {
			// Slugs go through searchObjectsBySlug: searchObjects reads register
			// and schema as numeric ids and answers a slug with no rows.
			$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: $filters
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[FieldFillProfileService] could not read the field fill profiles',
				context: ['error' => $e->getMessage()]
			);
			return [];
		}

		$profiles = [];
		foreach ((array)$rows as $row) {
			$profile = $this->normalise(row: $row);
			if ($profile !== null) {
				$profiles[] = $profile;
			}
		}

		return $profiles;

	}//end search()

	/**
	 * One stored row in the flat shape this service uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed>|null The profile, or null when the row is not one.
	 */
	private function normalise(mixed $row): ?array {
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$fields = $row;
		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$fields = $row['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($row['uuid'] ?? ($row['@self']['id'] ?? ($row['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
