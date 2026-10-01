<?php

/**
 * Classification result repository
 *
 * Reads and writes `classificationResult` objects in the filinq register
 * through OpenRegister's ObjectService. Every call passes `_rbac: false`:
 * classification runs from the enrichment listener with no user session,
 * and the confirmation endpoints check the requesting user's access to the
 * file itself before they read or write a record (see the bypass table in
 * docs/authorization-decisions.md).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Classification;

use OCA\Filinq\Service\DocumentObjectServiceResolver;

/**
 * Storage of classificationResult records.
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-2
 */
class ClassificationResultRepository {

	/**
	 * The schema slug.
	 *
	 * @var string
	 */
	public const SCHEMA = 'classificationResult';

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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Create or update a record.
	 *
	 * @param array<string, mixed> $record The fields.
	 * @param string|null          $uuid   The uuid to update, or null to create.
	 *
	 * @return array<string, mixed> The stored record with its uuid.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-2
	 */
	public function save(array $record, ?string $uuid=null): array {
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
	 * The active (not superseded) record of a file, or null.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The record.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-2
	 */
	public function activeFor(int $fileId): ?array {
		foreach ($this->search(filters: ['fileId' => $fileId]) as $record) {
			if (($record['status'] ?? '') !== 'superseded') {
				return $record;
			}
		}

		return null;

	}//end activeFor()

	/**
	 * Records by filter.
	 *
	 * @param array<string, mixed> $filters Field filters.
	 *
	 * @return array<int, array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
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
