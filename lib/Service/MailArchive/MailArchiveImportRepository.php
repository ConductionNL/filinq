<?php

/**
 * Mail archive import repository
 *
 * Reads and writes `mailArchiveImport` objects in the filinq register through
 * OpenRegister's ObjectService. Every call passes `_rbac: false`: the import
 * job works as the system from a cron tick, with no user to check, and the
 * schema lets nobody write an import through the REST API. The import routes
 * answer only the user who started an import (MailArchiveImportService), see
 * the bypass table in docs/authorization-decisions.md.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\Filinq\Service\SlugSearch;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;

/**
 * Mail archive imports in OpenRegister.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-an-archivist-starts-an-import-from-a-file-in-nextcloud-req-mai-001
 */
class MailArchiveImportRepository {

	use ReadsStoredObjectRows;

	public const REGISTER = 'filinq';

	public const SCHEMA = 'mailArchiveImport';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Store an import.
	 *
	 * @param array<string, mixed> $record The import.
	 * @param string|null          $uuid   The uuid when updating.
	 *
	 * @return array<string, mixed> The stored import with its uuid.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function save(array $record, ?string $uuid = null): array {
		unset($record['uuid']);
		$stored = $this->objectResolver->resolve()->saveObject(
			object: $record,
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA),
				uuid: $uuid
			),
			_rbac: false,
			_multitenancy: false
		);

		$normalised = $this->normalise(row: $stored);
		if ($normalised['uuid'] === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * One import.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The import, or null.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function find(string $uuid): ?array {
		$row = $this->objectResolver->resolve()->find(
			id: $uuid,
			options: new ObjectFindOptions(register: self::REGISTER, schema: self::SCHEMA),
			_rbac: false,
			_multitenancy: false
		);
		if ($row === null) {
			return null;
		}

		$record = $this->normalise(row: $row);
		if ($record['uuid'] === '') {
			$record['uuid'] = $uuid;
		}

		return $record;

	}//end find()

	/**
	 * Imports matching equality filters.
	 *
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return list<array<string, mixed>> The imports.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function search(array $filters): array {
		$rows = (new SlugSearch(objects: $this->objectResolver->resolve(), _rbac: false, _multitenancy: false))->search(
			register: self::REGISTER,
			schema: self::SCHEMA,
			filters: $filters
		);

		if (is_array($rows) === false) {
			return [];
		}

		return array_values(array_map(fn (mixed $row): array => $this->normalise(row: $row), $rows));

	}//end search()

	/**
	 * The import that has waited longest in `importing`.
	 *
	 * @return array<string, mixed>|null The import, or null when none is importing.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function oldestImporting(): ?array {
		$rows = $this->search(filters: ['status' => MailArchiveImportService::STATUS_IMPORTING]);
		usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['startedAt'] ?? ''), (string) ($right['startedAt'] ?? '')));

		return ($rows[0] ?? null);

	}//end oldestImporting()

	/**
	 * An ObjectEntity or array as a flat array with its uuid.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function normalise(mixed $row): array {
		$fields = $this->storedFields(row: $row);
		if ($fields === []) {
			return ['uuid' => ''];
		}

		return $fields;

	}//end normalise()
}//end class
