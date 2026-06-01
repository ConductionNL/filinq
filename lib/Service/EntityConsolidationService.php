<?php
/**
 * Entity Consolidation Service
 *
 * Merges entity detections from every extracted file in a batch into a
 * single de-duplicated list. Entities are grouped by lower-cased value,
 * counted across files, and marked as "included" (subject to anonymization)
 * based on the WOO profile and a minimum-confidence threshold.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Consolidates per-file entity detections into a unified batch-level list.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityConsolidationService
{


    /**
     * Constructor for EntityConsolidationService
     *
     * @param LoggerInterface    $logger     Logger for error reporting.
     * @param WooProfileService  $wooProfile Profile service describing which entity types to anonymize.
     * @param IAppManager        $appManager App manager used to check for OpenRegister availability.
     * @param ContainerInterface $container  DI container used to resolve OpenRegister mappers at runtime.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WooProfileService $wooProfile,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
    ) {

    }//end __construct()


    /**
     * Consolidate entity detections across every extracted file in a batch.
     *
     * Entities below the supplied confidence threshold are kept in the
     * result but flagged as `included => false` so the UI can still show
     * them for manual review.
     *
     * @param array<string, mixed> $batch         Batch record whose file list should be consolidated.
     * @param float                $minConfidence Minimum confidence required for an entity to be included by default.
     *
     * @return array<int, array<string, mixed>> Consolidated, confidence-sorted list of entities.
     */
    public function consolidateEntities(array $batch, float $minConfidence=0.0): array
    {
        $map = [];
        foreach ($batch['files'] as $file) {
            if ($file['status'] !== 'extracted') {
                continue;
            }

            foreach ($this->getEntitiesForFile(fileId: (int) $file['fileId']) as $entity) {
                $map = $this->mergeEntity(map: $map, entity: $entity);
            }
        }

        foreach ($map as $k => $e) {
            if ($e['highestConfidence'] < $minConfidence) {
                $map[$k]['included'] = false;
            }
        }

        $result = array_values($map);
        usort($result, static fn($a, $b) => $b['highestConfidence'] <=> $a['highestConfidence']);
        return $result;

    }//end consolidateEntities()


    /**
     * Merge a single entity detection into the running consolidation map.
     *
     * Entries are keyed by lower-cased entity value. Duplicate detections
     * bump the file count and keep the highest confidence seen so far.
     *
     * @param array<string, array<string, mixed>> $map    Running consolidation map keyed by lower-cased value.
     * @param mixed                               $entity Raw entity detection (object or array-like).
     *
     * @return array<string, array<string, mixed>> Updated consolidation map.
     */
    private function mergeEntity(array $map, mixed $entity): array
    {
        if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
            $d = $entity->jsonSerialize();
        } else {
            $d = (array) $entity;
        }

        $type       = $d['entity_type'] ?? $d['entityType'] ?? 'UNKNOWN';
        $value      = $d['entity_value'] ?? $d['entityValue'] ?? '';
        $conf       = (float) ($d['confidence'] ?? 0.0);
        $relationId = $d['relation_id'] ?? $d['relationId'] ?? null;
        $key        = mb_strtolower((string) $value);
        if ($key === '') {
            return $map;
        }

        // Collect every underlying EntityRelation row so the folder-flow
        // review UI can PATCH grondslagen / skip decisions onto every
        // occurrence in one go.
        $seedRelationIds = [];
        if ($relationId !== null) {
            $seedRelationIds[] = (int) $relationId;
        }

        if (isset($map[$key]) === true) {
            $map[$key]['fileCount']++;
            if ($conf > $map[$key]['highestConfidence']) {
                $map[$key]['highestConfidence'] = $conf;
            }

            if ($relationId !== null) {
                $map[$key]['relationIds'][] = (int) $relationId;
            }
        } else {
            $map[$key] = [
                'type'              => $type,
                'value'             => $value,
                'highestConfidence' => $conf,
                'fileCount'         => 1,
                'included'          => $this->wooProfile->shouldAnonymize((string) $type),
                'relationIds'       => $seedRelationIds,
            ];
        }//end if

        return $map;

    }//end mergeEntity()


    /**
     * Fetch the entity detections stored for a single file by OpenRegister.
     *
     * Returns an empty array and logs a warning when OpenRegister is not
     * installed or the lookup fails — callers treat missing entities as a
     * non-fatal condition.
     *
     * @param int $fileId Nextcloud file ID whose entities should be fetched.
     *
     * @return array<int, mixed> Raw entity detections, or an empty array on failure.
     */
    private function getEntitiesForFile(int $fileId): array
    {
        try {
            if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
                throw new RuntimeException('OpenRegister not available');
            }

            return $this->container->get('OCA\\OpenRegister\\Db\\EntityRelationMapper')->findEntitiesForFile($fileId);
        } catch (RuntimeException $e) {
            $this->logger->warning('Could not get entities: '.$e->getMessage(), ['fileId' => $fileId]);
            return [];
        }

    }//end getEntitiesForFile()


}//end class
