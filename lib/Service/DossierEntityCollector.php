<?php

/**
 * Dossier Entity Collector
 *
 * Walks a dossier folder and turns OpenRegister's raw EntityRelation rows into
 * the per-entity rows the grondslagen report renders: one row per distinct
 * `(entity_type, entity_id)`, carrying its scope-local placeholder, occurrence
 * count and the union of its grondslagen.
 *
 * Extracted from {@see GrondslagenSummaryService}. The privacy rule lives here:
 * an entity for which no SCOPE-LOCAL placeholder can be established is OMITTED
 * rather than rendered with its global id, because a global id is a relatable
 * cross-disclosure handle.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Collects and shapes the anonymised entity rows of a file or dossier.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DossierEntityCollector
{

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
     * @param DossierObjectRepository  $repository    OpenRegister object access.
     * @param BaseLabelResolver        $labelResolver Grondslag label resolution.
     * @param DossierPlaceholderRanker $ranker        Placeholder sort keys.
     * @param LoggerInterface          $logger        Structured logger.
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
     * @param Folder                $folder         The dossier folder.
     * @param array<string, string> $placeholderMap Dossier scope-local placeholder map
     *                                              (global entity id → "[DATUM: 6]")
     *                                              so each file's rows render the
     *                                              dossier number, not the global id.
     *
     * @return array<int, array{fileId: int, filename: string, entities: array<int, array<string, mixed>>}> Per-file rows.
     */
    public function walkDossierFiles(Folder $folder, array $placeholderMap=[]): array
    {
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
                'fileId'   => $node->getId(),
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
     * @param int                   $fileId         The Nextcloud file ID.
     * @param array<string, string> $placeholderMap Optional global entity id → emitted
     *                                              placeholder map; when set, each row uses that
     *                                              placeholder (scope-local number + localized
     *                                              label) instead of the per-document placeholder
     *                                              OpenRegister persisted.
     *
     * @return array<int, array<string, mixed>> Rows shaped as
     *         `{placeholder, entityId, entityType, entityText, count, bases, baseLabels, basesText}`.
     */
    public function loadAnonymisedEntitiesForFile(int $fileId, array $placeholderMap=[]): array
    {
        $rawRows = $this->fetchAnonymisedRows(fileId: $fileId);
        if (count($rawRows) === 0) {
            return [];
        }

        return $this->shapeGroups(
            grouped: $this->groupRows(rawRows: $rawRows, placeholderMap: $placeholderMap, fileId: $fileId),
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
    private function fetchAnonymisedRows(int $fileId): array
    {
        $mapper = $this->repository->entityRelationMapper();
        if ($mapper === null) {
            $this->logger->warning(
                'GrondslagenSummaryService: EntityRelationMapper unavailable; producing empty entity set',
                ['fileId' => $fileId]
            );
            return [];
        }

        try {
            $rawRows = $mapper->findAnonymisedEntitiesWithBasesForFile($fileId);
        } catch (Exception $e) {
            $this->logger->error(
                'GrondslagenSummaryService: findAnonymisedEntitiesWithBasesForFile failed',
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
    private function labelMapFor(array $rawRows): array
    {
        $allRefs = [];
        foreach ($rawRows as $row) {
            $bases = ($row['bases'] ?? null);
            if (is_array($bases) === false) {
                continue;
            }

            foreach ($bases as $ref) {
                $allRefs[(string) $ref] = true;
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
     * @param array<int, array<string, mixed>> $rawRows        Raw relation rows.
     * @param array<string, string>            $placeholderMap Scope-local placeholder map.
     * @param int                              $fileId         The file id (log context).
     *
     * @return array<string, array<string, mixed>> The grouped entities.
     */
    private function groupRows(array $rawRows, array $placeholderMap, int $fileId): array
    {
        $grouped = [];
        $omitted = 0;
        foreach ($rawRows as $row) {
            $entityId   = (int) ($row['entity_id'] ?? 0);
            $entityType = (string) ($row['entity_type'] ?? '');
            $key        = $entityType.':'.$entityId;

            if (isset($grouped[$key]) === false) {
                $placeholder = $this->resolvePlaceholder(
                    row: $row,
                    entityId: $entityId,
                    placeholderMap: $placeholderMap
                );
                if ($placeholder === null) {
                    $omitted++;
                    continue;
                }

                $grouped[$key] = [
                    'entityId'    => $entityId,
                    'entityType'  => $entityType,
                    'entityText'  => (string) ($row['entity_value'] ?? ''),
                    'placeholder' => $placeholder,
                    'count'       => 0,
                    'basesSet'    => [],
                ];
            }//end if

            $grouped[$key]['count']++;

            $bases = ($row['bases'] ?? null);
            if (is_array($bases) === true) {
                foreach ($bases as $ref) {
                    $grouped[$key]['basesSet'][(string) $ref] = true;
                }
            }
        }//end foreach

        if ($omitted > 0) {
            // PII-free: count only. Surfaces stale relations with no recoverable
            // scope-local placeholder, deliberately left out of the summary.
            $this->logger->info(
                'GrondslagenSummaryService: omitted entities with no scope-local placeholder (no global-id fallback)',
                ['fileId' => $fileId, 'omitted' => $omitted]
            );
        }

        return $grouped;

    }//end groupRows()

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
     * @param array<string, mixed>  $row            One raw relation row.
     * @param int                   $entityId       The row's global entity id.
     * @param array<string, string> $placeholderMap Scope-local placeholder map.
     *
     * @return string|null The placeholder, or null when the entity must be omitted.
     */
    private function resolvePlaceholder(array $row, int $entityId, array $placeholderMap): ?string
    {
        if (isset($placeholderMap[(string) $entityId]) === true) {
            // Tier 1 — scope-correct map from the caller.
            return $placeholderMap[(string) $entityId];
        }

        // Tier 2 — the per-document placeholder OpenRegister persisted.
        $stored = (string) ($row['anonymized_value'] ?? '');
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
     * @param array<string, array<string, mixed>>                     $grouped  The grouped entities.
     * @param array<string, array{name: string, description: string}> $labelMap Resolved base labels.
     *
     * @return array<int, array<string, mixed>> The shaped, sorted rows.
     */
    private function shapeGroups(array $grouped, array $labelMap): array
    {
        $shaped = [];
        foreach ($grouped as $group) {
            $basesRefs = array_keys($group['basesSet']);
            $labels    = [];
            foreach ($basesRefs as $ref) {
                $labels[] = ($labelMap[$ref]['name'] ?? $ref);
            }

            $shaped[] = [
                'placeholder' => $group['placeholder'],
                'entityId'    => $group['entityId'],
                'entityType'  => $group['entityType'],
                'entityText'  => $group['entityText'],
                'count'       => $group['count'],
                'bases'       => $basesRefs,
                'baseLabels'  => $labels,
                'basesText'   => implode(', ', $labels),
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
