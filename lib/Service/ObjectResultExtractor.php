<?php

/**
 * ObjectResultExtractor — coerces OpenRegister result envelopes to plain rows.
 *
 * OpenRegister's ObjectService returns several shapes depending on the call and
 * the installed version: a bare list, a `['results' => [...]]` envelope, a
 * `Traversable`, and each element may be a plain array, an `ObjectEntity`
 * (exposing `getObject()` / `getUuid()`), or any `JsonSerializable`. Every
 * consumer used to re-implement that coercion; this collaborator owns it once.
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

use Traversable;

/**
 * Stateless coercion of ObjectService results into plain PHP rows.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude infrastructure adapter — normalises OpenRegister result shapes; carries no product behaviour of its own
 */
class ObjectResultExtractor {
	/**
	 * Extract every coercible row from an ObjectService result.
	 *
	 * @param mixed $result Whatever findAll/searchObjects returned.
	 *
	 * @return array<int, array<string, mixed>> The plain rows, in result order.
	 *
	 * @spec exclude infrastructure adapter — result-shape normalisation only
	 */
	public function extractRows(mixed $result): array {
		$rows = [];
		foreach ($this->candidatesOf(result: $result) as $candidate) {
			$row = $this->coerce(candidate: $candidate);
			if ($row !== null) {
				$rows[] = $row;
			}
		}

		return $rows;
	}//end extractRows()

	/**
	 * Extract the first coercible row from an ObjectService result.
	 *
	 * @param mixed $result Whatever findAll/searchObjects returned.
	 *
	 * @return array<string, mixed>|null The first row, or null when there is none.
	 *
	 * @spec exclude infrastructure adapter — result-shape normalisation only
	 */
	public function firstRow(mixed $result): ?array {
		foreach ($this->candidatesOf(result: $result) as $candidate) {
			$row = $this->coerce(candidate: $candidate);
			if ($row !== null) {
				return $row;
			}
		}

		return null;
	}//end firstRow()

	/**
	 * Coerce a single result item into a plain array.
	 *
	 * @param mixed $candidate One element of an ObjectService result.
	 *
	 * @return array<string, mixed> The plain row, or an empty array when the
	 *                              item carries no usable payload.
	 *
	 * @spec exclude infrastructure adapter — result-shape normalisation only
	 */
	public function extractRow(mixed $candidate): array {
		return ($this->coerce(candidate: $candidate) ?? []);
	}//end extractRow()

	/**
	 * Unwrap the result envelope into an iterable of candidate items.
	 *
	 * @param mixed $result Whatever findAll/searchObjects returned.
	 *
	 * @return iterable<mixed> The candidate items.
	 */
	private function candidatesOf(mixed $result): iterable {
		if (is_array($result) === true) {
			if (isset($result['results']) === true && is_array($result['results']) === true) {
				return $result['results'];
			}

			return $result;
		}

		if ($result instanceof Traversable) {
			return iterator_to_array(iterator: $result);
		}

		return [];
	}//end candidatesOf()

	/**
	 * Coerce one candidate, distinguishing "empty row" from "not a row".
	 *
	 * @param mixed $candidate One element of an ObjectService result.
	 *
	 * @return array<string, mixed>|null The plain row, or null when the item
	 *                                   exposes no array payload at all.
	 */
	private function coerce(mixed $candidate): ?array {
		if (is_array($candidate) === true) {
			return $candidate;
		}

		if (is_object($candidate) === false) {
			return null;
		}

		$payload = $this->payloadFromObjectAccessor(candidate: $candidate);
		if ($payload !== null) {
			return $this->withSelfEnvelope(candidate: $candidate, payload: $payload);
		}

		return $this->payloadFromJsonSerializable(candidate: $candidate);
	}//end coerce()

	/**
	 * Read an array payload from an ObjectEntity-style `getObject()` accessor.
	 *
	 * @param object $candidate The candidate object.
	 *
	 * @return array<string, mixed>|null The payload, or null when unavailable.
	 */
	private function payloadFromObjectAccessor(object $candidate): ?array {
		if (method_exists(object_or_class: $candidate, method: 'getObject') === false) {
			return null;
		}

		$payload = $candidate->getObject();
		if (is_array($payload) === false) {
			return null;
		}

		return $payload;
	}//end payloadFromObjectAccessor()

	/**
	 * Read an array payload from a `jsonSerialize()` accessor.
	 *
	 * @param object $candidate The candidate object.
	 *
	 * @return array<string, mixed>|null The payload, or null when unavailable.
	 */
	private function payloadFromJsonSerializable(object $candidate): ?array {
		if (method_exists(object_or_class: $candidate, method: 'jsonSerialize') === false) {
			return null;
		}

		$payload = $candidate->jsonSerialize();
		if (is_array($payload) === false) {
			return null;
		}

		return $payload;
	}//end payloadFromJsonSerializable()

	/**
	 * Ensure the payload carries an `@self.id` so update paths can address it.
	 *
	 * Only fills the envelope when it is absent and the candidate can supply a
	 * UUID — an existing `@self` is never overwritten.
	 *
	 * @param object $candidate The source object.
	 * @param array<string, mixed> $payload The extracted payload.
	 *
	 * @return array<string, mixed> The payload, with `@self` filled when possible.
	 */
	private function withSelfEnvelope(object $candidate, array $payload): array {
		if (isset($payload['@self']) === true) {
			return $payload;
		}

		if (method_exists(object_or_class: $candidate, method: 'getUuid') === false) {
			return $payload;
		}

		$uuid = $candidate->getUuid();
		if ($uuid === null) {
			return $payload;
		}

		$payload['@self'] = ['id' => $uuid];
		return $payload;
	}//end withSelfEnvelope()
}//end class
