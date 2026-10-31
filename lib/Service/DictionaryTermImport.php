<?php

/**
 * Dictionary Term Import
 *
 * One import run into one custom dictionary: where new terms go and which
 * values are already taken.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The dictionary reference new terms are saved under, and the values taken so
 * far, compared case-insensitively: the dictionary's existing terms and every
 * value this run has admitted.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
final class DictionaryTermImport {

	/**
	 * Lower-cased values already taken.
	 *
	 * @var array<string, true>
	 */
	private array $taken = [];

	/**
	 * @param string                           $dictionary    The reference new terms carry.
	 * @param array<int, array<string, mixed>> $existingTerms The dictionary's terms before the import.
	 */
	public function __construct(
		public readonly string $dictionary,
		array $existingTerms,
	) {
		foreach ($existingTerms as $term) {
			$this->taken[mb_strtolower(trim((string)($term['value'] ?? '')))] = true;
		}
	}//end __construct()

	/**
	 * Whether a trimmed, non-blank value is new; a new value is taken from then on.
	 *
	 * @param string $value The value.
	 *
	 * @return bool True when the value was not taken yet.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function admits(string $value): bool {
		$key = mb_strtolower($value);
		if (isset($this->taken[$key]) === true) {
			return false;
		}

		$this->taken[$key] = true;
		return true;
	}//end admits()
}//end class
