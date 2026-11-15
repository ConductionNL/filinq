<?php

/**
 * Stored record fields
 *
 * Reads the fields of a record stored in OpenRegister, as the final-document
 * and intake repositories hand them out.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude Shape adapter over an OpenRegister response; no behaviour of its own.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The payload of a stored record with its uuid, `@self` kept.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Shape adapter over an OpenRegister response; no behaviour of its own.
 */
final class StoredRecordFields {

	use ReadsStoredObjectRows;

	/**
	 * Normalise an OpenRegister object (entity or array) to a flat record:
	 * the payload under `object` (or the row itself), with `uuid` taken from
	 * the payload, the row, `@self.uuid` or the row id, in that order.
	 *
	 * @param mixed $row ObjectEntity or array.
	 *
	 * @return array<string, mixed> The record, or [] when the row is no array.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response; no behaviour of its own.
	 */
	public function read(mixed $row): array {
		$data = $this->storedRowArray(row: $row);
		if ($data === null) {
			return [];
		}

		$fields = $this->storedRowPayload(data: $data);
		$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($this->selfUuid(data: $data) ?? ($data['id'] ?? ''))));

		return $fields;

	}//end read()

	/**
	 * The uuid in the row's `@self` block, when that block is an array.
	 *
	 * @param array<string, mixed> $data The stored row.
	 *
	 * @return mixed The uuid, or null.
	 */
	private function selfUuid(array $data): mixed {
		if (isset($data['@self']) === true && is_array($data['@self']) === true) {
			return ($data['@self']['uuid'] ?? null);
		}

		return null;

	}//end selfUuid()
}//end class
