<?php

/**
 * Wizard definitions in OpenRegister
 *
 * Reads and writes `wizardDefinition` objects in the filinq register as the
 * calling user: the schema's authorization decides who may author a wizard
 * (template editors) and who may read one (every signed-in user). No RBAC
 * bypass.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * CRUD of wizardDefinition objects.
 */
class WizardRepository {

	/**
	 * The schema slug.
	 *
	 * @var string
	 */
	public const SCHEMA = 'wizardDefinition';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * One wizard by uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The wizard, or null when absent or not readable.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function find(string $uuid): ?array {
		if (trim($uuid) === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(id: $uuid, register: IntakeRepository::REGISTER, schema: self::SCHEMA);
		} catch (Throwable) {
			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * The wizards attached to a template.
	 *
	 * @param string $templateId The template uuid.
	 *
	 * @return array<int, array<string, mixed>> The wizards.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function forTemplate(string $templateId): array {
		if (trim($templateId) === '') {
			return [];
		}

		return $this->search(filters: ['templateId' => $templateId]);

	}//end forTemplate()

	/**
	 * The active wizards.
	 *
	 * @return array<int, array<string, mixed>> The wizards.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-3
	 */
	public function active(): array {
		return $this->search(filters: ['active' => true]);

	}//end active()

	/**
	 * Wizards matching equality filters, as the caller may read them.
	 *
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return array<int, array<string, mixed>> The wizards.
	 */
	private function search(array $filters): array {
		// Slugs go through searchObjectsBySlug: searchObjects answers slugs with zero rows.
		$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
			registerSlug: IntakeRepository::REGISTER,
			schemaSlug: self::SCHEMA,
			filters: $filters
		);
		if (is_array($rows) === false) {
			return [];
		}

		$normalised = array_map(fn (mixed $row): array => $this->normalise(row: $row), $rows);

		return array_values(array_filter($normalised, static fn (array $row): bool => $row !== []));

	}//end search()

	/**
	 * Save a wizard.
	 *
	 * @param array<string, mixed> $wizard The fields.
	 * @param string|null          $uuid   The uuid when updating.
	 *
	 * @return array<string, mixed> The stored wizard.
	 *
	 * @throws RuntimeException 422 when OpenRegister refuses it, 403 when the caller may not.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function save(array $wizard, ?string $uuid=null): array {
		unset($wizard['uuid'], $wizard['@self'], $wizard['id'], $wizard['version']);
		$arguments = [
			'object' => $wizard,
			'register' => IntakeRepository::REGISTER,
			'schema' => self::SCHEMA,
		];
		if ($uuid !== null && $uuid !== '') {
			$arguments['uuid'] = $uuid;
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			$code = 422;
			if (in_array($e->getCode(), [401, 403], true) === true) {
				$code = 403;
			}

			throw new RuntimeException(message: 'The wizard could not be saved: ' . $e->getMessage(), code: $code, previous: $e);
		}

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * Delete a wizard.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return void
	 *
	 * @throws RuntimeException 403 when the caller may not.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function delete(string $uuid): void {
		try {
			$this->objectResolver->resolve()->deleteObject(uuid: $uuid);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The wizard could not be deleted: ' . $e->getMessage(), code: 403, previous: $e);
		}

	}//end delete()

	/**
	 * Flatten an OpenRegister object into its fields plus uuid and version.
	 *
	 * @param mixed $row The object or array.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$self = (array) ($data['@self'] ?? []);
		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($self['id'] ?? ($data['id'] ?? ''))));
		$fields['version'] = (string) ($self['version'] ?? ($data['version'] ?? ''));

		return $fields;

	}//end normalise()
}//end class
