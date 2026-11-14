<?php

/**
 * Dossier Entity Collector
 *
 * Walks a dossier folder and turns OpenRegister's raw EntityRelation rows into
 * the per-entity rows the grondslagen report renders: one row per distinct
 * `(entity_type, entity_id)`, carrying its scope-local placeholder, occurrence
 * count and the union of its grondslagen.
 *
 * Extracted from {@see LegalBasesSummaryService}. The privacy rule lives here:
 * an entity for which no SCOPE-LOCAL placeholder can be established is OMITTED
 * rather than rendered with its global id, because a global id is a relatable
 * cross-disclosure handle.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Collects and shapes the anonymised entity rows of a file or dossier.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DossierEntityCollector {

	/**
	 * Folder names holding redacted OUTPUT, excluded from the source-file walk.
	 *
	 * The EntityRelation rows are keyed by the SOURCE file ids, so the
	 * redacted outputs produced by `anonymisation-output-folder-layout` must
	 * not be walked.
	 *
	 * @var array<int, string>
	 */
	private const OUTPUT_FOLDER_NAMES = [
		'anonymised',
		'anonymized',
		'redacted',
	];

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $repository OpenRegister object access.
	 * @param BaseLabelResolver $labelResolver Grondslag label resolution.
	 * @param DossierPlaceholderRanker $ranker Placeholder sort keys.
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly BaseLabelResolver $labelResolver,
		private readonly DossierPlaceholderRanker $ranker,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Walk every file under the dossier folder and collect its anonymised entities.
	 *
	 * Folders found inside the dossier folder are recursed; the redacted-output
	 * subfolders are skipped.
	 *
	 * @param Folder $folder The dossier folder.
	 * @param array<string, string> $placeholderMap Dossier scope-local placeholder map
	 *                                              (global entity id → "[DATUM: 6]")
	 *                                              so each file's rows render the
	 *                                              dossier number, not the global id.
	 *
	 * @return array<int, array{fileId: int, filename: string, entities: array<int, array<string, mixed>>}> Per-file rows.
	 */
	public function walkDossierFiles(Folder $folder, array $placeholderMap = []): array {
		$rows = [];
		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof Folder) {
				if (in_array($node->getName(), self::OUTPUT_FOLDER_NAMES, true) === true) {
					continue;
				}

				$rows = array_merge($rows, $this->walkDossierFiles(folder: $node, placeholderMap: $placeholderMap));
				continue;
			}

			if (($node instanceof File) === false) {
				continue;
			}

			$entities = $this->loadAnonymisedEntitiesForFile(fileId: $node->getId(), placeholderMap: $placeholderMap);
			if (count($entities) === 0) {
				continue;
			}

			$rows[] = [
				'fileId' => $node->getId(),
				'filename' => $node->getName(),
				'entities' => $entities,
			];
		}//end foreach

		return $rows;
	}//end walkDossierFiles()

	/**
	 * Load the EntityRelation rows that this service cares about for a file.
	 *
	 * Filters to relations where `anonymized = true` (the report is "what was
	 * redacted under which grondslag" — non-anonymised relations are out of
	 * scope) and attaches the resolved base-label list onto each row for the
	 * template to render.
	 *
	 * @param int $fileId The Nextcloud file ID.
	 * @param array<string, string> $placeholderMap Optional global entity id → emitted
	 *                                              placeholder map; when set, each row uses that
	 *                                              placeholder (scope-local number + localized
	 *                                              label) instead of the per-document placeholder
	 *                                              OpenRegister persisted.
	 *
	 * @return array<int, array<string, mixed>> Rows shaped as
	 *                                          `{placeholder, entityId, entityType, entityText, count, bases, baseLabels, basesText}`.
	 */
	public function loadAnonymisedEntitiesForFile(int $fileId, array $placeholderMap = []): array {
		$rawRows = $this->fetchAnonymisedRows(fileId: $fileId);
		if (count($rawRows) === 0) {
			return [];
		}

		return $this->shapeGroups(
			grouped: $this->groupRows(run: new EntityGroupingRun(fileId: $fileId, placeholderMap: $placeholderMap), rawRows: $rawRows),
			labelMap: $this->labelMapFor(rawRows: $rawRows)
		);

	}//end loadAnonymisedEntitiesForFile()

	/**
	 * Fetch the raw anonymised EntityRelation rows for one file.
	 *
	 * Best-effort: an unavailable mapper or a failing query yields an empty
	 * set so the report still renders.
	 *
	 * @param int $fileId The Nextcloud file ID.
	 *
	 * @return array<int, array<string, mixed>> Raw relation rows.
	 */
	private function fetchAnonymisedRows(int $fileId): array {
		$mapper = $this->repository->entityRelationMapper();
		if ($mapper === null) {
			$this->logger->warning(
				'LegalBasesSummaryService: EntityRelationMapper unavailable; producing empty entity set',
				['fileId' => $fileId]
			);
			return [];
		}

		try {
			$rawRows = $mapper->findAnonymisedEntitiesWithBasesForFile($fileId);
		} catch (Exception $e) {
			$this->logger->error(
				'LegalBasesSummaryService: findAnonymisedEntitiesWithBasesForFile failed',
				['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return [];
		}

		if (is_array($rawRows) === false) {
			return [];
		}

		return $rawRows;
	}//end fetchAnonymisedRows()

	/**
	 * Resolve every distinct base reference across the rows in one batch.
	 *
	 * @param array<int, array<string, mixed>> $rawRows Raw relation rows.
	 *
	 * @return array<string, array{name: string, description: string}> The label map.
	 */
	private function labelMapFor(array $rawRows): array {
		$allRefs = [];
		foreach ($rawRows as $row) {
			foreach ($this->baseRefs(row: $row) as $ref) {
				$allRefs[$ref] = true;
			}
		}

		return $this->labelResolver->resolve(baseRefs: array_keys($allRefs));
	}//end labelMapFor()

	/**
	 * Group raw relation rows by `(entity_type, entity_id)`.
	 *
	 * Each group carries its scope-local placeholder, the number of relation
	 * rows it covers, and the set-union of its base references. Entities with
	 * no resolvable scope-local placeholder are omitted and counted for a
	 * PII-free log line.
	 *
	 * @param EntityGroupingRun                $run     The file and its placeholder map.
	 * @param array<int, array<string, mixed>> $rawRows Raw relation rows.
	 *
	 * @return array<string, array<string, mixed>> The grouped entities.
	 */
	private function groupRows(EntityGroupingRun $run, array $rawRows): array {
		foreach ($rawRows as $row) {
			$this->addRow(run: $run, row: $row);
		}

		if ($run->omitted > 0) {
			// PII-free: count only. Surfaces stale relations with no recoverable
			// scope-local placeholder, deliberately left out of the summary.
			$this->logger->info(
				'LegalBasesSummaryService: omitted entities with no scope-local placeholder (no global-id fallback)',
				['fileId' => $run->fileId, 'omitted' => $run->omitted]
			);
		}

		return $run->grouped;
	}//end groupRows()

	/**
	 * Count one row into its group, opening the group on its first row; a
	 * first row without a scope-local placeholder is counted as omitted.
	 *
	 * @param EntityGroupingRun    $run The pass.
	 * @param array<string, mixed> $row One raw relation row.
	 *
	 * @return void
	 */
	private function addRow(EntityGroupingRun $run, array $row): void {
		$key = (string)($row['entity_type'] ?? '') . ':' . (int)($row['entity_id'] ?? 0);

		if (isset($run->grouped[$key]) === false) {
			$placeholder = $this->resolvePlaceholder(row: $row, placeholderMap: $run->placeholderMap);
			if ($placeholder === null) {
				$run->omitted++;
				return;
			}

			$run->grouped[$key] = $this->newGroup(row: $row, placeholder: $placeholder);
		}

		$run->grouped[$key]['count']++;
		foreach ($this->baseRefs(row: $row) as $ref) {
			$run->grouped[$key]['basesSet'][$ref] = true;
		}

	}//end addRow()

	/**
	 * A new, empty group for a row's entity.
	 *
	 * @param array<string, mixed> $row         The entity's first row.
	 * @param string               $placeholder Its scope-local placeholder.
	 *
	 * @return array<string, mixed> The group.
	 */
	private function newGroup(array $row, string $placeholder): array {
		return [
			'entityId' => (int)($row['entity_id'] ?? 0),
			'entityType' => (string)($row['entity_type'] ?? ''),
			'entityText' => (string)($row['entity_value'] ?? ''),
			'placeholder' => $placeholder,
			'count' => 0,
			'basesSet' => [],
		];

	}//end newGroup()

	/**
	 * The base references on a row, as strings; none when `bases` is not a list.
	 *
	 * @param array<string, mixed> $row One raw relation row.
	 *
	 * @return array<int, string> The references.
	 */
	private function baseRefs(array $row): array {
		$bases = ($row['bases'] ?? null);
		if (is_array($bases) === false) {
			return [];
		}

		return array_map(static fn (mixed $ref): string => (string)$ref, array_values($bases));

	}//end baseRefs()

	/**
	 * Resolve one relation's SCOPE-LOCAL placeholder, or null to omit it.
	 *
	 * We NEVER fall back to the global entity_id: a global id is a relatable
	 * cross-disclosure handle, so an entity for which we cannot establish a
	 * scope-local placeholder is OMITTED from the summary entirely rather
	 * than leaked.
	 *
	 * The caller-supplied map wins: it is computed for THIS report's scope
	 * (dossier-wide for the per-dossier report, per-document for the
	 * single-file report), so it carries the numbering the reader expects.
	 * The placeholder OpenRegister persisted in `anonymized_value` is
	 * per-document; it is only a fallback for when no live map is available
	 * (e.g. a report regenerated long after anonymisation).
	 *
	 * @param array<string, mixed> $row One raw relation row.
	 * @param array<string, string> $placeholderMap Scope-local placeholder map.
	 *
	 * @return string|null The placeholder, or null when the entity must be omitted.
	 */
	private function resolvePlaceholder(array $row, array $placeholderMap): ?string {
		$entityId = (string)(int)($row['entity_id'] ?? 0);
		if (isset($placeholderMap[$entityId]) === true) {
			// Tier 1 — scope-correct map from the caller.
			return $placeholderMap[$entityId];
		}

		// Tier 2 — the per-document placeholder OpenRegister persisted.
		$stored = (string)($row['anonymized_value'] ?? '');
		if (preg_match('/^\[[^:\]]+:\s*\d+\]$/u', $stored) === 1) {
			return $stored;
		}

		return null;
	}//end resolvePlaceholder()

	/**
	 * Shape the grouped entities into the row set the templates render.
	 *
	 * Rows are ordered by TYPE then NUMERIC id ascending, so the blocks of
	 * each type are grouped and ordered 1,2,3,…,10,11 (not the lexical
	 * 1,10,11,2 a plain string sort produces). Diff-friendly across re-runs.
	 *
	 * @param array<string, array<string, mixed>> $grouped The grouped entities.
	 * @param array<string, array{name: string, description: string}> $labelMap Resolved base labels.
	 *
	 * @return array<int, array<string, mixed>> The shaped, sorted rows.
	 */
	private function shapeGroups(array $grouped, array $labelMap): array {
		$shaped = [];
		foreach ($grouped as $group) {
			$basesRefs = array_keys($group['basesSet']);
			$labels = [];
			foreach ($basesRefs as $ref) {
				$labels[] = ($labelMap[$ref]['name'] ?? $ref);
			}

			$shaped[] = [
				'placeholder' => $group['placeholder'],
				'entityId' => $group['entityId'],
				'entityType' => $group['entityType'],
				'entityText' => $group['entityText'],
				'count' => $group['count'],
				'bases' => $basesRefs,
				'baseLabels' => $labels,
				'basesText' => implode(', ', $labels),
			];
		}

		$ranker = $this->ranker;
		usort(
			$shaped,
			static function (array $a, array $b) use ($ranker): int {
				return ($ranker->sortKey(placeholder: $a['placeholder']) <=> $ranker->sortKey(placeholder: $b['placeholder']));
			}
		);

		return $shaped;
	}//end shapeGroups()
}//end class
