<?php

/**
 * Pseudonym Pairs
 *
 * Joins the placeholders OpenRegister emitted during an anonymisation with the
 * original values that were sent to it, and turns placeholders in a text back
 * into those values. Pure: no storage, no crypto, no I/O.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

/**
 * Placeholder to original value, both ways.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymPairs {

	/**
	 * Build the pairs a reversible run keeps.
	 *
	 * OpenRegister keys its placeholder map by its own entity id, not by the
	 * value, so the join goes through the file's entity rows: value to entity id
	 * (what OpenRegister looked up while it replaced), entity id to placeholder
	 * (what it emitted). OpenRegister replaces the TRIMMED value, so the value
	 * kept is the trimmed one: that is the text the placeholder stands for.
	 *
	 * An entity OpenRegister gave no stable placeholder (no entity row on the
	 * file) is left out: its placeholder carries a random key that appears in no
	 * map, so there is nothing to turn back, and the count says so.
	 *
	 * @param array<int, array<string, mixed>> $entities       The entities sent to OpenRegister
	 *                                                        (`text`, `entityType`).
	 * @param array<string, array<string, mixed>> $idsByValue Value to `{id, type}`, OpenRegister's
	 *                                                        entity rows for the file.
	 * @param array<string, string> $placeholderMap           Entity id to emitted placeholder.
	 *
	 * @return array<int, array{placeholder: string, originalValue: string, entityType: string}> The pairs.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
	 */
	public function build(array $entities, array $idsByValue, array $placeholderMap): array {
		$pairs = [];
		foreach ($entities as $entity) {
			$text = (string) ($entity['text'] ?? '');
			$value = trim($text);
			if ($value === '' || isset($idsByValue[$text]) === false) {
				continue;
			}

			$placeholder = (string) ($placeholderMap[(string) ($idsByValue[$text]['id'] ?? '')] ?? '');
			if ($placeholder === '' || isset($pairs[$placeholder]) === true) {
				continue;
			}

			$pairs[$placeholder] = [
				'placeholder' => $placeholder,
				'originalValue' => $value,
				'entityType' => (string) ($entity['entityType'] ?? ($idsByValue[$text]['type'] ?? '')),
			];
		}

		return array_values($pairs);

	}//end build()

	/**
	 * Put the original values back into a text.
	 *
	 * `strtr` with an array tries the LONGEST key first at every position and
	 * never rescans what it inserted, so `[PERSOON: 1]` cannot eat the start of
	 * `[PERSOON: 10]`, and an original value that happens to contain a
	 * placeholder is not replaced a second time. A sequence of `str_replace`
	 * calls gives neither guarantee.
	 *
	 * @param string $text The anonymised text.
	 * @param array<int, array<string, string>> $pairs The pairs.
	 *
	 * @return array{text: string, restored: int} The restored text and how many
	 *                                            placeholders were found in it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
	 */
	public function reverse(string $text, array $pairs): array {
		$table = [];
		$restored = 0;
		foreach ($pairs as $pair) {
			$placeholder = (string) ($pair['placeholder'] ?? '');
			if ($placeholder === '') {
				continue;
			}

			$table[$placeholder] = (string) ($pair['originalValue'] ?? '');
			$restored += substr_count($text, $placeholder);
		}

		if ($table === []) {
			return ['text' => $text, 'restored' => 0];
		}

		return ['text' => strtr($text, $table), 'restored' => $restored];

	}//end reverse()
}//end class
