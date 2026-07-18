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
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\App\IAppManager;
use OCP\IAppConfig;
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
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 */
class EntityConsolidationService
{
    /**
     * App config key for the high-confidence threshold.
     *
     * Mirrors AnonymizationService::HIGH_CONFIDENCE_THRESHOLD_KEY.
     */
    private const HIGH_CONFIDENCE_THRESHOLD_KEY = 'prohibition.high_confidence_threshold';

    /**
     * Default threshold value (inclusive boundary).
     */
    private const DEFAULT_HIGH_CONFIDENCE_THRESHOLD = 0.85;

    /**
     * Constructor for EntityConsolidationService
     *
     * @param LoggerInterface      $logger        Logger for error reporting.
     * @param WooProfileService    $wooProfile    Profile service describing which entity types to anonymize.
     * @param IAppManager          $appManager    App manager used to check for OpenRegister availability.
     * @param ContainerInterface   $container     DI container used to resolve OpenRegister mappers at runtime.
     * @param PolicyMatchService   $policyMatch   Policy matcher for per-entity prohibition lookups.
     * @param BasesResolverService $basesResolver Resolver for suggested dossier bases.
     * @param IAppConfig           $appConfig     Tenant configuration (high-confidence threshold).
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WooProfileService $wooProfile,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
        private readonly PolicyMatchService $policyMatch,
        private readonly BasesResolverService $basesResolver,
        private readonly IAppConfig $appConfig,
    ) {

    }//end __construct()

    /**
     * Consolidate entity detections across every extracted file in a batch.
     *
     * Entities below the supplied confidence threshold are kept in the
     * result but flagged as `included => false` so the UI can still show
     * them for manual review.
     *
     * Each returned entity also carries:
     *   - `prohibitionMatch` — `null` or `{ruleId, ruleName, highConfidence}`.
     *   - `suggestedBases`   — array of dossier-derived Woo Art. 5 grondslag
     *                          UUIDs/slugs (empty when no dossier is found).
     *
     * Both additions are non-breaking (strict superset of the pre-change shape).
     *
     * @param array<string, mixed> $batch         Batch record whose file list should be consolidated.
     * @param float                $minConfidence Minimum confidence required for an entity to be included by default.
     *
     * @return array<int, array<string, mixed>> Consolidated, confidence-sorted list of entities.
     *
     * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
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

        // Enrich: suggestedBases is the same for every entity in the batch.
        $suggestedBases = $this->basesResolver->resolveBasesForBatch(batch: $batch);
        $threshold      = $this->getHighConfidenceThreshold();

        foreach ($result as &$entity) {
            $entity['prohibitionMatch'] = $this->computeProhibitionMatch(
                entity: $entity,
                threshold: $threshold
            );
            $entity['suggestedBases']   = $suggestedBases;
        }

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
     *
     * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
     */
    private function mergeEntity(array $map, mixed $entity): array
    {
        $data = (array) $entity;
        if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
            $data = $entity->jsonSerialize();
        }

        $type  = $data['entity_type'] ?? $data['entityType'] ?? 'UNKNOWN';
        $value = $data['entity_value'] ?? $data['entityValue'] ?? '';
        $conf  = (float) ($data['confidence'] ?? 0.0);
        $key   = mb_strtolower((string) $value);
        if ($key === '') {
            return $map;
        }

        if (isset($map[$key]) === false) {
            $map[$key] = [
                'type'              => $type,
                'value'             => $value,
                'highestConfidence' => $conf,
                'fileCount'         => 1,
                'included'          => $this->wooProfile->shouldAnonymize((string) $type),
            ];

            return $map;
        }

        $map[$key]['fileCount']++;
        if ($conf > $map[$key]['highestConfidence']) {
            $map[$key]['highestConfidence'] = $conf;
        }

        return $map;

    }//end mergeEntity()

    /**
     * Compute the `prohibitionMatch` value for a single consolidated entity.
     *
     * Uses `highestConfidence` (the worst-case confidence across the batch)
     * for the `highConfidence` flag so a single high-confidence detection in
     * any file marks the whole rollup as high-confidence.
     *
     * @param array<string, mixed> $entity    Consolidated entity entry.
     * @param float                $threshold High-confidence threshold (inclusive).
     *
     * @return array<string, mixed>|null Match object or null.
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
     */
    private function computeProhibitionMatch(array $entity, float $threshold): ?array
    {
        try {
            $match = $this->policyMatch->matchProhibitionHint(
                entityType: (string) ($entity['type'] ?? ''),
                entityValue: (string) ($entity['value'] ?? '')
            );
        } catch (\Throwable $e) {
            $this->logger->debug(
                'EntityConsolidationService: matchProhibition threw; returning null',
                ['exception' => $e->getMessage()]
            );
            return null;
        }

        if ($match === null) {
            return null;
        }

        $highestConfidence = (float) ($entity['highestConfidence'] ?? 0.0);

        return [
            'ruleId'         => $match['ruleId'] ?? null,
            'ruleName'       => $match['ruleName'] ?? null,
            'highConfidence' => $highestConfidence >= $threshold,
        ];

    }//end computeProhibitionMatch()

    /**
     * Read the high-confidence threshold from app config.
     *
     * Mirrors AnonymizationService; uses the same config key so one admin
     * setting covers both surfaces.
     *
     * @return float Threshold value (inclusive boundary).
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
     */
    private function getHighConfidenceThreshold(): float
    {
        return $this->appConfig->getValueFloat(
            app: 'docudesk',
            key: self::HIGH_CONFIDENCE_THRESHOLD_KEY,
            default: self::DEFAULT_HIGH_CONFIDENCE_THRESHOLD
        );

    }//end getHighConfidenceThreshold()

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
     *
     * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
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
