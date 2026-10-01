<?php

/**
 * Email document repository
 *
 * Reads and writes `emailDocument` objects in the filinq register through
 * OpenRegister's ObjectService. Every call passes `_rbac: false`: the cron
 * job files mail as the system, with no user to check, and the status page
 * is served to admins only by EmailIngestionController (see the bypass
 * table in docs/authorization-decisions.md).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

use OCA\Filinq\Service\DocumentObjectServiceResolver;

/**
 * Email documents in OpenRegister.
 */
class EmailDocumentRepository {

	public const SCHEMA = 'emailDocument';

	private const REGISTER = 'filinq';

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
	 * Store a record.
	 *
	 * @param array<string, mixed> $record The record.
	 * @param string|null          $uuid   The uuid when updating.
	 *
	 * @return array<string, mixed> The stored record with its uuid.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function save(array $record, ?string $uuid = null): array {
		unset($record['uuid']);
		$stored = $this->objectResolver->resolve()->saveObject(
			object: $record,
			register: self::REGISTER,
			schema: self::SCHEMA,
			uuid: $uuid,
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
	 * A filed record of the same email for the same dossier: same bytes, or same Message-ID.
	 *
	 * @param string $dossierRef  The dossier.
	 * @param string $contentHash The sha256 of the file.
	 * @param string $messageId   The Message-ID, '' when unknown.
	 *
	 * @return array<string, mixed>|null The record, or null.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function findFiledDuplicate(string $dossierRef, string $contentHash, string $messageId): ?array {
		$byHash = $this->search(filters: ['dossierRef' => $dossierRef, 'contentHash' => $contentHash, 'status' => 'filed']);
		if ($byHash !== []) {
			return $byHash[0];
		}

		if ($messageId === '') {
			return null;
		}

		return ($this->search(filters: ['dossierRef' => $dossierRef, 'messageId' => $messageId, 'status' => 'filed'])[0] ?? null);

	}//end findFiledDuplicate()

	/**
	 * The file ids that already have a failed record, so a tick does not record them again.
	 *
	 * @return list<string> The ids.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function failedSourceRefs(): array {
		return array_values(array_unique(array_map(static fn (array $row): string => (string) ($row['sourceFileRef'] ?? ''), $this->search(filters: ['status' => 'failed']))));

	}//end failedSourceRefs()

	/**
	 * One record.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The record, or null.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
	 */
	public function findByUuid(string $uuid): ?array {
		$row = $this->objectResolver->resolve()->find(id: $uuid, register: self::REGISTER, schema: self::SCHEMA, _rbac: false, _multitenancy: false);
		if ($row === null) {
			return null;
		}

		$record = $this->normalise(row: $row);
		if ($record['uuid'] === '') {
			$record['uuid'] = $uuid;
		}

		return $record;

	}//end findByUuid()

	/**
	 * Records matching equality filters.
	 *
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return list<array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
	 */
	public function search(array $filters): array {
		// Slugs go through searchObjectsBySlug: searchObjects answers slugs with zero rows.
		$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
			registerSlug: self::REGISTER,
			schemaSlug: self::SCHEMA,
			filters: $filters,
			_rbac: false,
			_multitenancy: false
		);

		if (is_array($rows) === false) {
			return [];
		}

		return array_values(array_map(fn (mixed $row): array => $this->normalise(row: $row), $rows));

	}//end search()

	/**
	 * An ObjectEntity or array as a flat array with its uuid.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The fields.
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
