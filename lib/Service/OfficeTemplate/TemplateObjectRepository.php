<?php

/**
 * Template object repository
 *
 * Reads and writes the objects office templates bring along
 * (`textFragment`, `templateImportJob`) in the filinq register through
 * OpenRegister's ObjectService, by slug. A caller-facing call keeps
 * OpenRegister's RBAC; a system call (rendering in a background job, the
 * import job writing its own state) says so with `asSystem`.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCA\Filinq\Service\DocumentObjectServiceResolver;

/**
 * Slug-addressed object access for one schema of the filinq register.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 */
class TemplateObjectRepository {

	/**
	 * The register slug.
	 *
	 * @var string
	 */
	private const REGISTER = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param string                        $schema         The schema slug.
	 * @param string                        $access         `caller` keeps RBAC, `system` bypasses it.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly string $schema = 'textFragment',
		private readonly string $access = 'caller',
	) {

	}//end __construct()

	/**
	 * The same repository for another schema.
	 *
	 * @param string $schema The schema slug.
	 *
	 * @return self The repository.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function forSchema(string $schema): self {
		return new self(objectResolver: $this->objectResolver, schema: $schema, access: $this->access);

	}//end forSchema()

	/**
	 * The same repository acting as the system (no RBAC, no multitenancy).
	 *
	 * @return self The repository.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function asSystem(): self {
		return new self(objectResolver: $this->objectResolver, schema: $this->schema, access: 'system');

	}//end asSystem()

	/**
	 * Create or update an object.
	 *
	 * @param array<string, mixed> $record The fields.
	 * @param string|null          $uuid   The uuid to update, or null to create.
	 *
	 * @return array<string, mixed> The stored object with its uuid.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function save(array $record, ?string $uuid=null): array {
		unset($record['uuid']);
		$service = $this->objectResolver->resolve();
		$stored = null;
		if ($this->access === 'system') {
			$stored = $service->saveObject(
				object: $record,
				register: self::REGISTER,
				schema: $this->schema,
				uuid: $uuid,
				_rbac: false,
				_multitenancy: false
			);
		}

		if ($this->access === 'caller') {
			$stored = $service->saveObject(object: $record, register: self::REGISTER, schema: $this->schema, uuid: $uuid);
		}

		$normalised = $this->normalise(row: $stored);
		if ($normalised['uuid'] === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * One object by uuid, or null.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The object.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function find(string $uuid): ?array {
		$service = $this->objectResolver->resolve();
		$row = null;
		if ($this->access === 'system') {
			$row = $service->find(id: $uuid, register: self::REGISTER, schema: $this->schema, _rbac: false, _multitenancy: false);
		}

		if ($this->access === 'caller') {
			$row = $service->find(id: $uuid, register: self::REGISTER, schema: $this->schema);
		}
		if ($row === null) {
			return null;
		}

		return $this->normalise(row: $row);

	}//end find()

	/**
	 * Objects by equality filters.
	 *
	 * @param array<string, mixed> $filters Field filters.
	 *
	 * @return array<int, array<string, mixed>> The objects.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function search(array $filters): array {
		// Slugs go through searchObjectsBySlug: searchObjects answers slugs with zero rows.
		$service = $this->objectResolver->resolve();
		$rows = null;
		if ($this->access === 'system') {
			$rows = $service->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: $this->schema,
				filters: $filters,
				_rbac: false,
				_multitenancy: false
			);
		}

		if ($this->access === 'caller') {
			$rows = $service->searchObjectsBySlug(registerSlug: self::REGISTER, schemaSlug: $this->schema, filters: $filters);
		}

		if (is_array($rows) === false) {
			return [];
		}

		return array_values(array_map(fn (mixed $row): array => $this->normalise(row: $row), $rows));

	}//end search()

	/**
	 * Delete an object.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function delete(string $uuid): void {
		$service = $this->objectResolver->resolve();
		if ($this->access === 'system') {
			$service->deleteObject(uuid: $uuid, register: self::REGISTER, schema: $this->schema, _rbac: false, _multitenancy: false);
			return;
		}

		$service->deleteObject(uuid: $uuid, register: self::REGISTER, schema: $this->schema);

	}//end delete()

	/**
	 * One stored row as a flat record with a uuid.
	 *
	 * @param mixed $row An ObjectEntity or array.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function normalise(mixed $row): array {
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return ['uuid' => ''];
		}

		$fields = $row;
		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$fields = $row['object'];
		}

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($row['uuid'] ?? ($row['@self']['id'] ?? ($row['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
