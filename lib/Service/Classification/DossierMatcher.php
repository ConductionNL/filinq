<?php

/**
 * Conservative dossier matching for a classification suggestion
 *
 * Suggests a dossier only on a high-precision match: the dossier's name
 * starts with the suggested correspondent's name, or a case reference found
 * in the text (such as Z-2026-114 or BZ-2026-0114) occurs in the dossier's
 * name. No fuzzy scoring: a wrong filing suggestion costs more trust than
 * no suggestion. Two dossiers that match equally well suggest neither.
 * The match only ever becomes a suggestion; filing waits for a person.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Classification;

/**
 * Exact and prefix matching of a document onto one dossier.
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 */
class DossierMatcher {

	/**
	 * A case reference: letters, a year and a number, joined by dashes.
	 *
	 * @var string
	 */
	private const CASE_REFERENCE = '/\b[A-Z]{1,5}-\d{4}-\d{1,6}\b/';

	/**
	 * The dossier to suggest, or null.
	 *
	 * @param array<int, array<string, mixed>> $dossiers      The dossiers (uuid, name).
	 * @param string|null                      $correspondent The suggested correspondent's name.
	 * @param string                           $text          The document text.
	 *
	 * @return string|null The dossier uuid, or null when no single dossier matches.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
	 */
	public function match(array $dossiers, ?string $correspondent, string $text): ?string {
		$references = [];
		if (preg_match_all(self::CASE_REFERENCE, $text, $found) > 0) {
			$references = array_unique($found[0]);
		}

		$prefix = $this->normalise(value: (string) $correspondent);
		$matches = [];
		foreach ($dossiers as $dossier) {
			$name = $this->normalise(value: (string) ($dossier['name'] ?? ''));
			$uuid = (string) ($dossier['uuid'] ?? '');
			if ($name === '' || $uuid === '') {
				continue;
			}

			if ($this->matches(name: $name, prefix: $prefix, references: $references) === true) {
				$matches[$uuid] = true;
			}
		}

		if (count($matches) !== 1) {
			return null;
		}

		return (string) array_key_first($matches);

	}//end match()

	/**
	 * Whether one normalised dossier name matches.
	 *
	 * @param string   $name       The normalised dossier name.
	 * @param string   $prefix     The normalised correspondent name ('' for none).
	 * @param string[] $references The case references in the text.
	 *
	 * @return bool True on an exact or prefix match.
	 */
	private function matches(string $name, string $prefix, array $references): bool {
		if ($prefix !== '' && ($name === $prefix || str_starts_with($name, $prefix . ' ') === true)) {
			return true;
		}

		foreach ($references as $reference) {
			if (str_contains(' ' . $name . ' ', ' ' . $this->normalise(value: $reference) . ' ') === true) {
				return true;
			}
		}

		return false;

	}//end matches()

	/**
	 * Lower case, punctuation other than the dash dropped, single spaces.
	 *
	 * @param string $value The value.
	 *
	 * @return string The normalised value.
	 */
	private function normalise(string $value): string {
		$value = (string) preg_replace('/[^\p{L}\p{N}-]+/u', ' ', mb_strtolower($value));

		return trim((string) preg_replace('/\s+/', ' ', $value));

	}//end normalise()
}//end class
