<?php

/**
 * Session Row Matcher
 *
 * Picks the signing session row with a given externalId out of what
 * OpenRegister's findAll() answers: plain arrays, serialisable objects or
 * ObjectEntity-style objects. Built by {@see NativeSigningProvider}.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

/**
 * Finds the session row whose externalId matches exactly.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
final class SessionRowMatcher {

	/**
	 * The first readable row whose externalId matches exactly
	 *
	 * @param iterable<mixed> $results    The raw entries from findAll()
	 * @param string          $externalId The externalId to match
	 *
	 * @return array<string, mixed>|null The session row or null
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function matching(iterable $results, string $externalId): ?array {
		foreach ($results as $entry) {
			$row = $this->normaliseEntry(entry: $entry);
			if ($row === null) {
				continue;
			}

			if (($row['externalId'] ?? null) === $externalId) {
				return $row;
			}
		}

		return null;
	}//end matching()

	/**
	 * Normalise an OR entry (ObjectEntity or array) into a plain array
	 *
	 * @param mixed $entry The raw entry from findAll()
	 *
	 * @return array<string, mixed>|null The normalised row, or null on failure
	 */
	private function normaliseEntry(mixed $entry): ?array {
		if (is_array($entry) === true) {
			return $entry;
		}

		if (is_object($entry) === false) {
			return null;
		}

		$serialised = $this->arrayFromAccessor(entry: $entry, accessor: 'jsonSerialize');
		if ($serialised !== null) {
			return $serialised;
		}

		return $this->arrayFromAccessor(entry: $entry, accessor: 'getObject');
	}//end normaliseEntry()

	/**
	 * The array an entry's accessor answers
	 *
	 * @param object $entry    The raw entry from findAll()
	 * @param string $accessor The accessor to call when the entry has it
	 *
	 * @return array<string, mixed>|null The array, or null when the accessor is missing or answers no array
	 */
	private function arrayFromAccessor(object $entry, string $accessor): ?array {
		if (method_exists($entry, $accessor) === false) {
			return null;
		}

		$value = $entry->$accessor();
		if (is_array($value) === false) {
			return null;
		}

		return $value;
	}//end arrayFromAccessor()
}//end class
