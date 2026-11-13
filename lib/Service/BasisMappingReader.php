<?php

/**
 * Basis Mapping Reader
 *
 * Reads the stored entity-type → base-slug[] mapping JSON
 * (`filinq.grondslagen.entity_type_bases`) into a clean mapping for
 * {@see LegalBasisProposalService::getMapping}: entries with a non-string
 * type or a non-list value are dropped, as are empty and non-string slugs
 * and types left without slugs.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Turns the stored mapping JSON into a clean entity-type → base-slug[] mapping.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class BasisMappingReader {

	/**
	 * Read the mapping JSON.
	 *
	 * @param string $raw The stored JSON.
	 *
	 * @return array<string, array<int, string>> Mapping; empty when malformed.
	 */
	public function fromJson(string $raw): array {
		$decoded = json_decode($raw, true);
		if (is_array($decoded) === false) {
			return [];
		}

		$mapping = [];
		foreach ($decoded as $type => $slugs) {
			$clean = $this->cleanSlugs(type: $type, slugs: $slugs);
			if (count($clean) > 0) {
				$mapping[$type] = $clean;
			}
		}

		return $mapping;

	}//end fromJson()

	/**
	 * The non-empty string slugs of one mapping entry.
	 *
	 * @param mixed $type  The entry's key.
	 * @param mixed $slugs The entry's value.
	 *
	 * @return array<int, string> The slugs, or none when the key or the value has the wrong shape.
	 */
	private function cleanSlugs(mixed $type, mixed $slugs): array {
		if (is_string($type) === false || is_array($slugs) === false) {
			return [];
		}

		return $this->nonEmptyStrings(values: $slugs);

	}//end cleanSlugs()

	/**
	 * The non-empty strings of a list, in order.
	 *
	 * @param array<mixed> $values The list.
	 *
	 * @return array<int, string> The non-empty strings.
	 */
	private function nonEmptyStrings(array $values): array {
		$clean = [];
		foreach ($values as $value) {
			if (is_string($value) === true && $value !== '') {
				$clean[] = $value;
			}
		}

		return $clean;

	}//end nonEmptyStrings()
}//end class
