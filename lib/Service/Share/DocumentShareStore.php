<?php

/**
 * The documentShare records in Filinq's register.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Share
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/share-document-links/specs/document-share-links/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Share;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use RuntimeException;
use Throwable;

/**
 * Saves and reads documentShare objects through OpenRegister.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
 */
class DocumentShareStore {

	/**
	 * Filinq's register.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The share record schema.
	 *
	 * @var string
	 */
	public const SCHEMA = 'documentShare';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects OpenRegister's object service.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
	) {

	}//end __construct()

	/**
	 * Save a share record.
	 *
	 * @param array<string, mixed> $record The record.
	 * @param string|null          $uuid   The record's uuid, or null for a new one.
	 *
	 * @return array<string, mixed> The saved record, with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	public function save(array $record, ?string $uuid = null): array {
		unset($record['uuid'], $record['@self']);
		if ($uuid === '') {
			$uuid = null;
		}

		try {
			$stored = $this->normalise(
				row: $this->objects->resolve()->saveObject(
					object: $record,
					register: self::REGISTER,
					schema: self::SCHEMA,
					uuid: $uuid,
					_rbac: false,
					_unowned: true
				)
			);
		} catch (Throwable $e) {
			throw new RuntimeException('OpenRegister refused the share record: ' . $e->getMessage(), 500, $e);
		}

		if (($stored['uuid'] ?? '') === '' && $uuid !== null) {
			$stored['uuid'] = $uuid;
		}

		return $stored;

	}//end save()

	/**
	 * One share record.
	 *
	 * @param string $uuid The record's uuid.
	 *
	 * @return array<string, mixed>|null The record, or null when it does not exist.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$row = $this->objects->resolve()->find(id: $uuid, register: self::REGISTER, schema: self::SCHEMA);
		} catch (Throwable) {
			return null;
		}

		if ($row === null) {
			return null;
		}

		$record = $this->normalise(row: $row);
		if ($record === []) {
			return null;
		}

		return $record;

	}//end find()

	/**
	 * A stored row as a plain array with its uuid on top.
	 *
	 * @param mixed $row The row OpenRegister answered.
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$uuid = (string) ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? '')));
		if ($uuid !== '') {
			$data['uuid'] = $uuid;
		}

		return $data;

	}//end normalise()
}//end class
