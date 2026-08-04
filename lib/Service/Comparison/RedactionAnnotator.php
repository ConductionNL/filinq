<?php
/**
 * Redaction Annotator
 *
 * Annotates document-comparison diff hunks with redaction metadata from the
 * OpenRegister NER pipeline (EntityRelation rows for the source file) and
 * derives the redaction-completeness signal.
 *
 * Resolves the OpenRegister mapper lazily so the comparison degrades to a
 * plain diff (status 'unavailable') when OpenRegister is absent, rather than
 * failing.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Comparison
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Comparison;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Annotates diff hunks with redaction metadata and completeness.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
class RedactionAnnotator
{
    /**
     * Constructor.
     *
     * @param LoggerInterface    $logger     Logger for diagnostics.
     * @param IAppManager        $appManager App manager (OpenRegister availability check).
     * @param ContainerInterface $container  DI container for lazy OR resolution.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container
    ) {

    }//end __construct()

    /**
     * Annotate change hunks with redaction metadata + completeness signal.
     *
     * Falls back to a plain diff (status 'unavailable') when OpenRegister is
     * absent, and 'none' when no relations exist for the source file
     * (unrelated pair).
     *
     * @param array<int, array<string, mixed>> $hunks        The diff hunks.
     * @param int                              $sourceFileId The source (left) file id.
     *
     * @return array{hunks: array<int, array<string, mixed>>, status: string, unredactedEntities: array<int, array<string, mixed>>}
     *
     * @spec openspec/specs/document-comparison/spec.md
     */
    public function annotate(array $hunks, int $sourceFileId): array
    {
        $result = [
            'hunks'              => $hunks,
            'status'             => 'none',
            'unredactedEntities' => [],
        ];

        $mapper = $this->resolveMapper();
        if ($mapper === null) {
            $result['status'] = 'unavailable';
            return $result;
        }

        $lookup = $this->lookupRelations(mapper: $mapper, sourceFileId: $sourceFileId);
        if ($lookup === null) {
            $result['status'] = 'unavailable';
            return $result;
        }

        if (empty($lookup['relations']) === true) {
            // No anonymisation link for this file: plain diff, no annotation.
            return $result;
        }

        // Index entity metadata (type + canonical value/name) by entity id.
        $entityMeta = $this->indexEntityMeta(joined: $lookup['joined']);

        // Build the anonymise set: non-skip relations with their replacement keys.
        $anonymiseSet = $this->buildAnonymiseSet(relations: $lookup['relations']);

        // Annotate hunks: match inserted (replacement key) or removed (value) spans.
        $annotatedHunks = [];
        foreach ($result['hunks'] as $hunk) {
            $match = $this->matchHunkToEntity(hunk: $hunk, anonymiseSet: $anonymiseSet, entityMeta: $entityMeta);
            if ($match !== null) {
                $hunk['redaction'] = [
                    'entityId'   => $match['entityId'],
                    'entityType' => $match['entityType'],
                    'matchedBy'  => $match['matchedBy'],
                ];
                $anonymiseSet[$match['entityId']]['matched'] = true;
            }

            $annotatedHunks[] = $hunk;
        }

        $result['hunks']  = $annotatedHunks;
        $result['status'] = 'annotated';

        // Completeness signal: anonymise-set entities that matched zero hunks.
        $result['unredactedEntities'] = $this->unredactedEntities(anonymiseSet: $anonymiseSet, entityMeta: $entityMeta);

        return $result;

    }//end annotate()

    /**
     * Resolve the OpenRegister EntityRelationMapper, or null when unavailable.
     *
     * @return mixed The mapper or null.
     */
    private function resolveMapper(): mixed
    {
        if ($this->isOpenRegisterAvailable() === false) {
            return null;
        }

        return $this->tryGetEntityRelationMapper();

    }//end resolveMapper()

    /**
     * Read the entity relations and joined entity rows for a source file.
     *
     * @param mixed $mapper       The OpenRegister EntityRelationMapper.
     * @param int   $sourceFileId The source (left) file id.
     *
     * @return array{relations: mixed, joined: mixed}|null The rows, or null when
     *                                                     the lookup failed.
     */
    private function lookupRelations(mixed $mapper, int $sourceFileId): ?array
    {
        try {
            return [
                'relations' => $mapper->findByFileId(fileId: $sourceFileId),
                'joined'    => $mapper->findEntitiesForFile(fileId: $sourceFileId),
            ];
        } catch (Throwable $e) {
            $this->logger->debug('Entity relation lookup failed', ['fileId' => $sourceFileId]);
            return null;
        }

    }//end lookupRelations()

    /**
     * Index entity metadata (type + canonical value/name) by entity id.
     *
     * @param mixed $joined The joined entity rows.
     *
     * @return array<int, array{entityType:string, entityName:string, value:string}>
     */
    private function indexEntityMeta(mixed $joined): array
    {
        $entityMeta = [];
        foreach ($joined as $row) {
            $eid = (int) ($row['entity_id'] ?? 0);
            if ($eid === 0) {
                continue;
            }

            $entityMeta[$eid] = [
                'entityType' => (string) ($row['entity_type'] ?? ''),
                'entityName' => (string) ($row['entity_name'] ?? ($row['entity_value'] ?? '')),
                'value'      => (string) ($row['entity_value'] ?? ''),
            ];
        }

        return $entityMeta;

    }//end indexEntityMeta()

    /**
     * Build the anonymise set: non-skip relations with their replacement keys.
     *
     * @param mixed $relations The EntityRelation rows.
     *
     * @return array<int, array{replacement:string, matched:bool}>
     */
    private function buildAnonymiseSet(mixed $relations): array
    {
        $anonymiseSet = [];
        foreach ($relations as $relation) {
            if ($this->isSkipFlagged(relation: $relation) === true) {
                continue;
            }

            $eid = (int) $relation->getEntityId();
            $anonymiseSet[$eid] = [
                'replacement' => (string) ($relation->getAnonymizedValue() ?? ''),
                'matched'     => false,
            ];
        }

        return $anonymiseSet;

    }//end buildAnonymiseSet()

    /**
     * Collect the anonymise-set entities that matched zero hunks.
     *
     * @param array<int, array{replacement:string, matched:bool}>                   $anonymiseSet The anonymise set.
     * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta   Entity metadata.
     *
     * @return array<int, array<string, mixed>> The unredacted entities.
     */
    private function unredactedEntities(array $anonymiseSet, array $entityMeta): array
    {
        $unredacted = [];
        foreach ($anonymiseSet as $eid => $info) {
            if ($info['matched'] === false) {
                $unredacted[] = [
                    'entityId'   => $eid,
                    'entityName' => ($entityMeta[$eid]['entityName'] ?? ''),
                ];
            }
        }

        return $unredacted;

    }//end unredactedEntities()

    /**
     * Match a hunk to an entity by replacement-key (insert) or value (delete).
     *
     * @param array<string, mixed>                                                  $hunk         A diff hunk.
     * @param array<int, array{replacement:string, matched:bool}>                   $anonymiseSet The anonymise set.
     * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta   Entity metadata.
     *
     * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
     */
    private function matchHunkToEntity(array $hunk, array $anonymiseSet, array $entityMeta): ?array
    {
        if ($hunk['type'] === 'unchanged') {
            return null;
        }

        // Key-based: inserted span equals an entity's replacement key.
        $match = $this->matchByReplacementKey(
            insertedText: (string) ($hunk['rightText'] ?? ''),
            anonymiseSet: $anonymiseSet,
            entityMeta: $entityMeta
        );
        if ($match !== null) {
            return $match;
        }

        // Value-based fallback: removed span equals an entity canonical value.
        return $this->matchByCanonicalValue(
            removedText: (string) ($hunk['leftText'] ?? ''),
            anonymiseSet: $anonymiseSet,
            entityMeta: $entityMeta
        );

    }//end matchHunkToEntity()

    /**
     * Match an inserted span against the entities' replacement keys.
     *
     * @param string                                                                $insertedText The inserted span.
     * @param array<int, array{replacement:string, matched:bool}>                   $anonymiseSet The anonymise set.
     * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta   Entity metadata.
     *
     * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
     */
    private function matchByReplacementKey(string $insertedText, array $anonymiseSet, array $entityMeta): ?array
    {
        if ($insertedText === '') {
            return null;
        }

        foreach ($anonymiseSet as $eid => $info) {
            if ($info['replacement'] !== '' && str_contains($insertedText, $info['replacement']) === true) {
                return [
                    'entityId'   => $eid,
                    'entityType' => ($entityMeta[$eid]['entityType'] ?? ''),
                    'matchedBy'  => 'key',
                ];
            }
        }

        return null;

    }//end matchByReplacementKey()

    /**
     * Match a removed span against the entities' canonical values.
     *
     * @param string                                                                $removedText  The removed span.
     * @param array<int, array{replacement:string, matched:bool}>                   $anonymiseSet The anonymise set.
     * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta   Entity metadata.
     *
     * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
     */
    private function matchByCanonicalValue(string $removedText, array $anonymiseSet, array $entityMeta): ?array
    {
        if ($removedText === '') {
            return null;
        }

        foreach (array_keys($anonymiseSet) as $eid) {
            $value = (string) ($entityMeta[$eid]['value'] ?? '');
            if ($value !== '' && str_contains($removedText, $value) === true) {
                return [
                    'entityId'   => $eid,
                    'entityType' => ($entityMeta[$eid]['entityType'] ?? ''),
                    'matchedBy'  => 'value',
                ];
            }
        }

        return null;

    }//end matchByCanonicalValue()

    /**
     * Determine whether a relation is skip-flagged (operator-released override).
     *
     * @param mixed $relation The EntityRelation object.
     *
     * @return bool True when skip-flagged.
     */
    private function isSkipFlagged(mixed $relation): bool
    {
        if (method_exists($relation, 'getSkipAnonymization') === true) {
            return ($relation->getSkipAnonymization() === true);
        }

        return false;

    }//end isSkipFlagged()

    /**
     * Whether OpenRegister is installed/available.
     *
     * @return bool True when available.
     */
    private function isOpenRegisterAvailable(): bool
    {
        return in_array('openregister', $this->appManager->getInstalledApps(), true);

    }//end isOpenRegisterAvailable()

    /**
     * Try to resolve the OR EntityRelationMapper, returning null on failure.
     *
     * @return mixed The mapper or null.
     */
    private function tryGetEntityRelationMapper(): mixed
    {
        try {
            return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
        } catch (Throwable $e) {
            $this->logger->debug('EntityRelationMapper unavailable: '.$e->getMessage());
            return null;
        }

    }//end tryGetEntityRelationMapper()
}//end class
