<?php

/**
 * ReadsStoredObjectRows — turns one stored OpenRegister object into its plain fields.
 *
 * A row reaches a repository as an array, a `JsonSerializable` (whose payload
 * may sit under `object`), or an unrendered `ObjectEntity`. The repositories
 * read it in one of four ways, each a method here.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Stateless readings of one stored OpenRegister row, for repositories.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude infrastructure adapter — normalises OpenRegister row shapes; carries no product behaviour of its own
 */
trait ReadsStoredObjectRows {
	/**
	 * The row's fields without `@self`, with `uuid` always set.
	 *
	 * @param mixed $row An array, a JsonSerializable or anything else.
	 *
	 * @return array<string, mixed> The fields, or [] when the row has no array payload.
	 */
	private function storedFields(mixed $row): array {
		$data = $this->storedRowArray(row: $row);
		if ($data === null) {
			return [];
		}

		$fields = $this->storedRowPayload(data: $data);
		unset($fields['@self']);

		return $this->storedRowWithUuid(fields: $fields, data: $data);
	}//end storedFields()

	/**
	 * The row's fields as stored, `@self` included, with `uuid` always set.
	 *
	 * @param mixed $row An array, a JsonSerializable or anything else.
	 *
	 * @return array<string, mixed> The fields, or [] when the row has no array payload.
	 */
	private function storedFieldsKeepingSelf(mixed $row): array {
		$data = $this->storedRowArray(row: $row);
		if ($data === null) {
			return [];
		}

		return $this->storedRowWithUuid(fields: $this->storedRowPayload(data: $data), data: $data);
	}//end storedFieldsKeepingSelf()

	/**
	 * The row's payload as stored, without adding a `uuid`.
	 *
	 * @param mixed $row An array, a JsonSerializable or anything else.
	 *
	 * @return array<string, mixed> The payload, or [] when the row has no array payload.
	 */
	private function storedPayload(mixed $row): array {
		$data = $this->storedRowArray(row: $row);
		if ($data === null) {
			return [];
		}

		return $this->storedRowPayload(data: $data);
	}//end storedPayload()

	/**
	 * Like storedFields(), but an unrendered ObjectEntity is read from getObject():
	 * its jsonSerialize is the render shape, not the stored payload.
	 *
	 * @param mixed $row An ObjectEntity, an array, a JsonSerializable or anything else.
	 *
	 * @return array<string, mixed> The fields, or [] when the row has no array payload.
	 */
	private function storedEntityFields(mixed $row): array {
		if (is_object($row) === true && method_exists($row, 'getObject') === true && method_exists($row, 'getUuid') === true) {
			return array_merge((array) $row->getObject(), ['uuid' => (string) $row->getUuid()]);
		}

		return $this->storedFields(row: $row);
	}//end storedEntityFields()

	/**
	 * The row as an array, serialising a JsonSerializable first.
	 *
	 * @param mixed $row The raw row.
	 *
	 * @return array<string, mixed>|null The array, or null when there is none.
	 */
	private function storedRowArray(mixed $row): ?array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return null;
		}

		return $data;
	}//end storedRowArray()

	/**
	 * The payload under `object` when there is one, else the row itself.
	 *
	 * @param array<string, mixed> $data The serialised row.
	 *
	 * @return array<string, mixed> The payload.
	 */
	private function storedRowPayload(array $data): array {
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			return $data['object'];
		}

		return $data;
	}//end storedRowPayload()

	/**
	 * Set `uuid` from the fields, the row, its `@self` id or its id, in that order.
	 *
	 * @param array<string, mixed> $fields The payload.
	 * @param array<string, mixed> $data The serialised row.
	 *
	 * @return array<string, mixed> The payload with `uuid` set.
	 */
	private function storedRowWithUuid(array $fields, array $data): array {
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;
	}//end storedRowWithUuid()
}//end trait
