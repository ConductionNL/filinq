<?php

/**
 * Sanitization record repository
 *
 * Reads and writes `sanitizationRecord` objects in the filinq register
 * through OpenRegister's ObjectService.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Sanitization
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Sanitization;

use OCA\Filinq\Service\DocumentObjectServiceResolver;

/**
 * Sanitization records in OpenRegister.
 */
class SanitizationRecordRepository {

	public const SCHEMA = 'sanitizationRecord';

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
	 *
	 * @return array<string, mixed> The stored record with its uuid.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-1
	 */
	public function save(array $record): array {
		$stored = $this->objectResolver->resolve()->saveObject(object: $record, register: 'filinq', schema: self::SCHEMA);

		return $this->normalise(row: $stored);

	}//end save()

	/**
	 * The records whose derivative is the given file, as the caller.
	 *
	 * @param int $fileId The file.
	 *
	 * @return list<array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-5
	 */
	public function forSanitizedFile(int $fileId): array {
		return $this->search(filters: ['sanitizedFileId' => $fileId]);

	}//end forSanitizedFile()

	/**
	 * The records of runs on the given source file, as the caller.
	 *
	 * @param int $fileId The file.
	 *
	 * @return list<array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-1
	 */
	public function forSourceFile(int $fileId): array {
		return $this->search(filters: ['fileId' => $fileId]);

	}//end forSourceFile()

	/**
	 * Search by field.
	 *
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return list<array<string, mixed>> The records.
	 */
	private function search(array $filters): array {
		// Slugs go through searchObjectsBySlug: searchObjects answers slugs with zero rows.
		$rows = $this->objectResolver->resolve()->searchObjectsBySlug(registerSlug: 'filinq', schemaSlug: self::SCHEMA, filters: $filters);

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
			return [];
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
