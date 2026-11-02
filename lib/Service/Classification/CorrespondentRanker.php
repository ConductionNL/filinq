<?php

/**
 * Correspondent ranking over already-detected entities
 *
 * Picks who a document is from out of the PERSON and ORGANIZATION entities
 * OpenRegister already detected in it. No second detection engine: the rows
 * are the ones EntityRelationMapper::findEntitiesForFile() returns. A
 * candidate in the letterhead zone (the first characters of the text) is
 * ranked first, then how often it occurs, then whether its name carries an
 * organisation suffix such as B.V. or Gemeente. No candidate means no
 * correspondent: the ranker never makes a name up.
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
 * Ranks detected PERSON and ORGANIZATION entities as the correspondent.
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 */
class CorrespondentRanker {

	/**
	 * Characters from the start of the text that count as the letterhead zone.
	 *
	 * @var int
	 */
	public const LETTERHEAD_ZONE = 600;

	/**
	 * Name endings and beginnings that mark an organisation.
	 *
	 * @var string[]
	 */
	private const ORGANISATION_MARKS = [
		'b.v.',
		'bv',
		'n.v.',
		'nv',
		'v.o.f.',
		'gemeente',
		'stichting',
		'provincie',
		'waterschap',
		'vereniging',
		'ministerie',
	];

	/**
	 * The entity types that can be a correspondent.
	 *
	 * @var string[]
	 */
	private const TYPES = ['PERSON', 'ORGANIZATION'];

	/**
	 * The best correspondent candidate, or null when there is none.
	 *
	 * @param array<int, array<string, mixed>> $rows The detected entity rows of one file
	 *                                               (entity_type, entity_value, position_start).
	 *
	 * @return array{name: string, entityType: string, source: string}|null The candidate.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
	 */
	public function rank(array $rows): ?array {
		$candidates = [];
		foreach ($rows as $row) {
			$type = strtoupper((string) ($row['entity_type'] ?? ''));
			$name = trim((string) ($row['entity_value'] ?? ''));
			if ($name === '' || in_array($type, self::TYPES, true) === false) {
				continue;
			}

			$key = $type . '|' . mb_strtolower($name);
			$candidates[$key] ??= ['name' => $name, 'entityType' => $type, 'count' => 0, 'first' => PHP_INT_MAX];
			$candidates[$key]['count']++;
			$candidates[$key]['first'] = min($candidates[$key]['first'], (int) ($row['position_start'] ?? PHP_INT_MAX));
		}

		if ($candidates === []) {
			return null;
		}

		usort($candidates, fn (array $left, array $right): int => $this->score(candidate: $right) <=> $this->score(candidate: $left));

		return ['name' => $candidates[0]['name'], 'entityType' => $candidates[0]['entityType'], 'source' => 'ner'];

	}//end rank()

	/**
	 * The ranking score of one candidate.
	 *
	 * @param array{name: string, entityType: string, count: int, first: int} $candidate The candidate.
	 *
	 * @return float The score; higher ranks first.
	 */
	private function score(array $candidate): float {
		$score = (float) $candidate['count'];
		if ($candidate['first'] < self::LETTERHEAD_ZONE) {
			$score += 3.0;
		}

		if ($candidate['entityType'] === 'ORGANIZATION' || $this->hasOrganisationMark(name: $candidate['name']) === true) {
			$score += 2.0;
		}

		// Ties go to the earlier mention.
		return $score - ($candidate['first'] / 1000000.0);

	}//end score()

	/**
	 * Whether a name starts or ends with an organisation mark.
	 *
	 * @param string $name The name.
	 *
	 * @return bool True for "Heijmans B.V." or "Gemeente Tilburg".
	 */
	private function hasOrganisationMark(string $name): bool {
		$words = preg_split('/\s+/', mb_strtolower(trim($name)));
		if ($words === false || $words === []) {
			return false;
		}

		return in_array($words[0], self::ORGANISATION_MARKS, true) === true
			|| in_array($words[count($words) - 1], self::ORGANISATION_MARKS, true) === true;

	}//end hasOrganisationMark()
}//end class
