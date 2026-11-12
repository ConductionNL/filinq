<?php

/**
 * Redaction Match Set
 *
 * The anonymise set of one comparison (non-skip entity relations with their
 * replacement keys) and the entity metadata, matched against diff hunks.
 * Records which entities matched, so the ones that matched no hunk can be
 * reported as possibly unredacted.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Comparison
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Comparison;

/**
 * Matches the hunks of one comparison to the entities of its anonymise set.
 *
 * Built by {@see RedactionAnnotator} for a single annotate() call.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
final class RedactionMatchSet {
	/**
	 * Constructor.
	 *
	 * @param array<int, array{replacement:string, matched:bool}>                   $anonymiseSet The anonymise set.
	 * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta   Entity metadata.
	 *
	 * @return void
	 */
	public function __construct(
		private array $anonymiseSet,
		private readonly array $entityMeta,
	) {

	}//end __construct()

	/**
	 * Annotate the hunks that match an entity, marking that entity matched.
	 *
	 * @param array<int, array<string, mixed>> $hunks The diff hunks.
	 *
	 * @return array<int, array<string, mixed>> The hunks, matched ones with a `redaction` block.
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function annotateHunks(array $hunks): array {
		$annotatedHunks = [];
		foreach ($hunks as $hunk) {
			$annotatedHunks[] = $this->annotateHunk(hunk: $hunk);
		}

		return $annotatedHunks;

	}//end annotateHunks()

	/**
	 * The anonymise-set entities that matched zero hunks.
	 *
	 * @return array<int, array<string, mixed>> The unredacted entities.
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function unmatchedEntities(): array {
		$unredacted = [];
		foreach ($this->anonymiseSet as $eid => $info) {
			if ($info['matched'] === false) {
				$unredacted[] = [
					'entityId' => $eid,
					'entityName' => ($this->entityMeta[$eid]['entityName'] ?? ''),
				];
			}
		}

		return $unredacted;

	}//end unmatchedEntities()

	/**
	 * Annotate one hunk when it matches an entity.
	 *
	 * @param array<string, mixed> $hunk A diff hunk.
	 *
	 * @return array<string, mixed> The hunk, with a `redaction` block when it matched.
	 */
	private function annotateHunk(array $hunk): array {
		$match = $this->matchHunk(hunk: $hunk);
		if ($match === null) {
			return $hunk;
		}

		$hunk['redaction'] = [
			'entityId' => $match['entityId'],
			'entityType' => $match['entityType'],
			'matchedBy' => $match['matchedBy'],
		];
		$this->anonymiseSet[$match['entityId']]['matched'] = true;

		return $hunk;

	}//end annotateHunk()

	/**
	 * Match a hunk to an entity by replacement-key (insert) or value (delete).
	 *
	 * @param array<string, mixed> $hunk A diff hunk.
	 *
	 * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
	 */
	private function matchHunk(array $hunk): ?array {
		if ($hunk['type'] === 'unchanged') {
			return null;
		}

		// Key-based: inserted span equals an entity's replacement key; value-based
		// fallback: removed span equals an entity canonical value.
		return $this->matchByReplacementKey(insertedText: (string)($hunk['rightText'] ?? ''))
			?? $this->matchByCanonicalValue(removedText: (string)($hunk['leftText'] ?? ''));

	}//end matchHunk()

	/**
	 * Match an inserted span against the entities' replacement keys.
	 *
	 * @param string $insertedText The inserted span.
	 *
	 * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
	 */
	private function matchByReplacementKey(string $insertedText): ?array {
		if ($insertedText === '') {
			return null;
		}

		$entityId = $this->entityWithReplacementIn(insertedText: $insertedText);
		if ($entityId === null) {
			return null;
		}

		return $this->matchOf(entityId: $entityId, matchedBy: 'key');

	}//end matchByReplacementKey()

	/**
	 * The first entity whose replacement key occurs in an inserted span.
	 *
	 * @param string $insertedText The inserted span.
	 *
	 * @return int|null The entity id, or null when none occurs.
	 */
	private function entityWithReplacementIn(string $insertedText): ?int {
		foreach ($this->anonymiseSet as $eid => $info) {
			if ($info['replacement'] !== '' && str_contains($insertedText, $info['replacement']) === true) {
				return $eid;
			}
		}

		return null;

	}//end entityWithReplacementIn()

	/**
	 * Match a removed span against the entities' canonical values.
	 *
	 * @param string $removedText The removed span.
	 *
	 * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
	 */
	private function matchByCanonicalValue(string $removedText): ?array {
		if ($removedText === '') {
			return null;
		}

		$entityId = $this->entityWithValueIn(removedText: $removedText);
		if ($entityId === null) {
			return null;
		}

		return $this->matchOf(entityId: $entityId, matchedBy: 'value');

	}//end matchByCanonicalValue()

	/**
	 * The first entity whose canonical value occurs in a removed span.
	 *
	 * @param string $removedText The removed span.
	 *
	 * @return int|null The entity id, or null when none occurs.
	 */
	private function entityWithValueIn(string $removedText): ?int {
		foreach (array_keys($this->anonymiseSet) as $eid) {
			$value = (string)($this->entityMeta[$eid]['value'] ?? '');
			if ($value !== '' && str_contains($removedText, $value) === true) {
				return $eid;
			}
		}

		return null;

	}//end entityWithValueIn()

	/**
	 * The match record for one entity.
	 *
	 * @param int    $entityId  The entity id.
	 * @param string $matchedBy How it matched: key or value.
	 *
	 * @return array{entityId:int, entityType:string, matchedBy:string}
	 */
	private function matchOf(int $entityId, string $matchedBy): array {
		return [
			'entityId' => $entityId,
			'entityType' => ($this->entityMeta[$entityId]['entityType'] ?? ''),
			'matchedBy' => $matchedBy,
		];

	}//end matchOf()
}//end class
