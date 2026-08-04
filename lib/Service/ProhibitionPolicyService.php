<?php
/**
 * Prohibition Policy Service
 *
 * Publication-policy decisions around detected entities: the post-detection
 * policy pass (prohibition hints + standing-consent auto-skip), the guarded
 * per-relation skip decision from the review UI, the pre-redaction backstop for
 * absolute-tier matches, and the unredacted-publication prohibition check.
 *
 * Extracted verbatim from AnonymizationService.
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
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\ProhibitionGateException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Applies publication policy to detected entities and guards skip decisions.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
 */
class ProhibitionPolicyService
{

    /**
     * Guard for the per-relation skip decision taken in the review UI.
     *
     * @var RelationSkipDecisionService
     */
    private readonly RelationSkipDecisionService $skipDecisions;

    /**
     * Constructor for ProhibitionPolicyService
     *
     * @param LoggerInterface            $logger    Logger for best-effort policy failures.
     * @param ContainerInterface         $container Container the PolicyMatchService is resolved from.
     * @param OpenRegisterServiceLocator $locator   Resolver for OpenRegister services and mappers.
     * @param ProhibitionGateService     $gate      The gate that runs before any OpenRegister
     *                                              interaction on an anonymise call.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly OpenRegisterServiceLocator $locator,
        private readonly ProhibitionGateService $gate
    ) {
        $this->skipDecisions = new RelationSkipDecisionService(
            logger: $logger,
            container: $container,
            locator: $locator
        );

    }//end __construct()

    /**
     * Run the publication-prohibition gate for an anonymise call.
     *
     * @param int                              $fileId          Nextcloud file ID.
     * @param array<int, array<string, mixed>> $requestEntities User-submitted entities[] to anonymize.
     * @param array<int, array<string, mixed>> $overrides       Override entries {ruleId, entityId, reason?}.
     * @param string                           $userId          UID of the acting user.
     *
     * @return void
     *
     * @throws ProhibitionGateException When the gate blocks the call.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     */
    public function runGate(int $fileId, array $requestEntities, array $overrides, string $userId): void
    {
        $this->gate->run(
            fileId: $fileId,
            requestEntities: $requestEntities,
            overrides: $overrides,
            userId: $userId
        );

    }//end runGate()

    /**
     * Apply publication policy to freshly-detected, normalized entities.
     *
     * Runs `PolicyMatchService::match()` (prohibition precedence) per entity:
     *  - a standing-consent winner is auto-skipped (`skip_anonymization = true`
     *    on the relation, via OpenRegister) unless it is already skipped;
     *  - a prohibition winner gets a read-only `prohibitionMatch`
     *    (`{ruleId, ruleName, highConfidence}`) for the review UI and is never
     *    auto-skipped.
     *
     * Every returned entity gains a `prohibitionMatch` key (null when none).
     * Best-effort: policy failures are logged and never block detection.
     *
     * @param array<int, array<string, mixed>> $entities             Normalized entities.
     * @param mixed                            $entityRelationMapper OpenRegister EntityRelationMapper (DI).
     *
     * @return array<int, array<string, mixed>> Entities with `prohibitionMatch` attached.
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
     */
    public function applyPolicyDecisions(array $entities, mixed $entityRelationMapper): array
    {
        $policy = $this->resolvePolicyMatcher();
        if ($policy === null) {
            foreach ($entities as &$plain) {
                $plain['prohibitionMatch'] = ($plain['prohibitionMatch'] ?? null);
            }

            unset($plain);
            return $entities;
        }

        foreach ($entities as &$entity) {
            $entity = $this->applyPolicyToEntity(
                entity: $entity,
                policy: $policy,
                entityRelationMapper: $entityRelationMapper
            );
        }

        unset($entity);

        return $entities;

    }//end applyPolicyDecisions()

    /**
     * Guard + apply a per-relation skip/include decision from the review UI.
     *
     * @param int        $relationId The EntityRelation id.
     * @param bool       $skip       The requested skipAnonymization value.
     * @param array|null $bases      Optional bases to set alongside the decision.
     * @param bool       $force      Release a sub-threshold prohibition match.
     *
     * @return array{status: 200|404|422, body: array<string, mixed>} HTTP status + response body.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    public function applyRelationSkipDecision(int $relationId, bool $skip, ?array $bases, bool $force): array
    {
        return $this->skipDecisions->apply(
            relationId: $relationId,
            skip: $skip,
            bases: $bases,
            force: $force
        );

    }//end applyRelationSkipDecision()

    /**
     * Defence-in-depth backstop: absolute prohibition matches left un-redacted.
     *
     * OpenRegister's generic relation PATCH stays open, so a caller could skip a
     * prohibited relation directly, bypassing the DocuDesk skip endpoint. Before
     * redaction, this returns any prohibition-matched occurrence at confidence
     * >= threshold that is being left un-redacted (skipped). Only the absolute
     * tier is enforced here — the primary decision-time guard covers the rest.
     *
     * "Skipped" = detected for the file but absent from the anonymise set
     * (`findEntitiesForAnonymization`, which already excludes skipAnonymization).
     *
     * @param int $fileId The Nextcloud file id.
     *
     * @return array<int, array<string, mixed>> Absolute-tier violations (may be empty).
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
     */
    public function absoluteProhibitionViolations(int $fileId): array
    {
        try {
            $matcher = $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
        } catch (Exception $e) {
            $this->logger->warning(
                'Policy matcher unavailable; prohibition backstop is a no-op',
                ['exception' => $e->getMessage()]
            );
            return [];
        }

        $mapper    = $this->locator->get(className: 'OCA\OpenRegister\Db\EntityRelationMapper');
        $threshold = (float) $matcher->highConfidenceThreshold();
        $redactIds = $this->collectRedactionRelationIds(mapper: $mapper, fileId: $fileId);

        $violations = [];
        foreach ($mapper->findEntitiesForFile($fileId) as $row) {
            // Being redacted — fine.
            if (isset($redactIds[(int) ($row['relation_id'] ?? 0)]) === true) {
                continue;
            }

            $value = (string) ($row['entity_value'] ?? '');
            if ($value === '') {
                continue;
            }

            $match = $matcher->matchProhibition(
                entityText: $value,
                entityType: (string) ($row['entity_type'] ?? 'OTHER')
            );
            if ($match === null || ((float) ($row['confidence'] ?? 0.0)) < $threshold) {
                continue;
            }

            $this->logger->warning(
                'Prohibition backstop caught an un-redacted absolute match',
                ['ruleId' => $match['uuid'], 'entityId' => (int) ($row['entity_id'] ?? 0), 'fileId' => $fileId]
            );

            $violations[] = [
                'entityId'   => (int) ($row['entity_id'] ?? 0),
                'entityName' => $value,
                'ruleId'     => $match['uuid'],
                'ruleName'   => $match['primaryName'],
                'confidence' => (float) ($row['confidence'] ?? 0.0),
                'absolute'   => true,
            ];
        }//end foreach

        return $violations;

    }//end absoluteProhibitionViolations()

    /**
     * Check unredacted entities against publication-prohibition rules.
     *
     * Returns an array of violation records (one per matching entity).
     * An empty array means no violations — all entries may proceed to consent creation.
     * Uses PolicyMatchService at any confidence (operator made an explicit decision;
     * the 0.85-threshold logic of the regular gate does NOT apply here — D2).
     *
     * @param array<int, array<string, mixed>> $unredactedEntities Entries from the unredactedEntities[] payload field.
     *
     * @return array<int, array<string, mixed>> Violation records: [{entityId, entityText, ruleId, ruleName}]
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     */
    public function checkUnredactedProhibitions(array $unredactedEntities): array
    {
        $policyService = $this->tryGetPolicyMatchService();
        if ($policyService === null) {
            return [];
        }

        $violations = [];
        foreach ($unredactedEntities as $entry) {
            $entityText = (string) ($entry['entityText'] ?? '');

            try {
                $match = $policyService->matchProhibition(
                    entityType: (string) ($entry['entityType'] ?? ''),
                    entityValue: $entityText
                );
            } catch (Throwable $e) {
                $this->logger->debug(
                    'PolicyMatchService::matchProhibition threw during unredacted check; skipping',
                    ['exception' => $e->getMessage()]
                );
                continue;
            }

            if ($match !== null) {
                $violations[] = [
                    'entityId'   => $entry['entityId'] ?? null,
                    'entityText' => $entityText,
                    'ruleId'     => $match['ruleId'] ?? null,
                    'ruleName'   => $match['ruleName'] ?? null,
                ];
            }
        }//end foreach

        return $violations;

    }//end checkUnredactedProhibitions()

    /**
     * Try to get PolicyMatchService from the container without throwing.
     *
     * Returns null when the service is not registered (before anonymisation-prohibition-gate lands).
     *
     * @return mixed PolicyMatchService instance or null.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     */
    private function tryGetPolicyMatchService(): mixed
    {
        try {
            return $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
        } catch (Throwable) {
            return null;
        }

    }//end tryGetPolicyMatchService()

    /**
     * Resolve the policy matcher together with its high-confidence threshold.
     *
     * Both the container lookup and the threshold read share one guard, so a
     * failure in either degrades the whole policy pass to a no-op rather than
     * bubbling out of detection.
     *
     * @return array{matcher: mixed, threshold: float}|null The matcher + threshold, or null when
     *                                                      the policy pass must be skipped.
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
     */
    private function resolvePolicyMatcher(): ?array
    {
        try {
            $matcher = $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
            return [
                'matcher'   => $matcher,
                'threshold' => (float) $matcher->highConfidenceThreshold(),
            ];
        } catch (Exception $e) {
            $this->logger->warning(
                'Policy matcher unavailable; skipping publication-policy pass',
                ['exception' => $e->getMessage()]
            );
            return null;
        }

    }//end resolvePolicyMatcher()

    /**
     * Apply the policy decision for one normalized entity.
     *
     * @param array<string, mixed>                    $entity               The normalized entity.
     * @param array{matcher: mixed, threshold: float} $policy               The resolved matcher + threshold.
     * @param mixed                                   $entityRelationMapper OpenRegister EntityRelationMapper (DI).
     *
     * @return array<string, mixed> The entity with `prohibitionMatch` (and possibly the auto-skip) applied.
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
     */
    private function applyPolicyToEntity(array $entity, array $policy, mixed $entityRelationMapper): array
    {
        $entity['prohibitionMatch'] = null;
        $value = (string) ($entity['value'] ?? '');
        if ($value === '') {
            return $entity;
        }

        try {
            $match = $policy['matcher']->match(
                entityText: $value,
                entityType: (string) ($entity['type'] ?? 'OTHER')
            );
        } catch (Exception $e) {
            $this->logger->warning('Policy match failed for entity', ['exception' => $e->getMessage()]);
            return $entity;
        }

        if ($match === null) {
            return $entity;
        }

        if ($match['kind'] === PolicyMatchService::KIND_PROHIBITION) {
            $entity['prohibitionMatch'] = [
                'ruleId'         => $match['uuid'],
                'ruleName'       => $match['primaryName'],
                'highConfidence' => (((float) ($entity['confidence'] ?? 0.0)) >= $policy['threshold']),
            ];
            return $entity;
        }

        if ($match['kind'] === PolicyMatchService::KIND_STANDING_CONSENT) {
            return $this->autoSkipStandingConsent(
                entity: $entity,
                entityRelationMapper: $entityRelationMapper
            );
        }

        return $entity;

    }//end applyPolicyToEntity()

    /**
     * Auto-skip a standing-consent occurrence unless it is already skipped.
     *
     * @param array<string, mixed> $entity               The normalized entity.
     * @param mixed                $entityRelationMapper OpenRegister EntityRelationMapper (DI).
     *
     * @return array<string, mixed> The entity, with `skipAnonymization` set when the write succeeded.
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
     */
    private function autoSkipStandingConsent(array $entity, mixed $entityRelationMapper): array
    {
        if (((bool) ($entity['skipAnonymization'] ?? false)) === true
            || ($entity['relationId'] ?? null) === null
        ) {
            return $entity;
        }

        try {
            $relation = $entityRelationMapper->find((int) $entity['relationId']);
            $entityRelationMapper->updateDecisionMetadata($relation, ['skipAnonymization' => true]);
            $entity['skipAnonymization'] = true;
        } catch (Exception $e) {
            $this->logger->warning(
                'Failed to auto-skip standing-consent entity',
                ['relationId' => $entity['relationId'], 'exception' => $e->getMessage()]
            );
        }

        return $entity;

    }//end autoSkipStandingConsent()

    /**
     * Collect the relation ids that are actually going to be redacted.
     *
     * @param mixed $mapper OpenRegister EntityRelationMapper.
     * @param int   $fileId The Nextcloud file id.
     *
     * @return array<int, bool> Relation ids present in the anonymise set, keyed for isset() lookup.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
     */
    private function collectRedactionRelationIds(mixed $mapper, int $fileId): array
    {
        $redactIds = [];
        foreach ($mapper->findEntitiesForAnonymization($fileId) as $row) {
            $redactIds[(int) ($row['relation_id'] ?? 0)] = true;
        }

        return $redactIds;

    }//end collectRedactionRelationIds()
}//end class
