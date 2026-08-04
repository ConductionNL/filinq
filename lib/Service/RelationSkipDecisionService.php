<?php
/**
 * Relation Skip Decision Service
 *
 * Guards and applies the per-relation skip/include decision the review UI
 * sends in place of PATCHing OpenRegister's relation endpoint directly.
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
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Applies a guarded skip/include decision to one EntityRelation.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */
class RelationSkipDecisionService
{

    /**
     * The shared tier rule for a skip on a prohibition-matched entity.
     *
     * @var ProhibitionSkipTier
     */
    private readonly ProhibitionSkipTier $tier;

    /**
     * Constructor for RelationSkipDecisionService
     *
     * @param LoggerInterface            $logger    Logger for blocked decisions and outages.
     * @param ContainerInterface         $container Container the PolicyMatchService is resolved from.
     * @param OpenRegisterServiceLocator $locator   Resolver for OpenRegister services and mappers.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly OpenRegisterServiceLocator $locator
    ) {
        $this->tier = new ProhibitionSkipTier();

    }//end __construct()

    /**
     * Guard + apply a per-relation skip/include decision from the review UI.
     *
     * Setting `skipAnonymization = true` on a prohibition-matched relation is
     * guarded per {@see ProhibitionSkipTier::classify}. Include / non-skip
     * decisions are always allowed. Allowed decisions are forwarded to
     * OpenRegister via `updateDecisionMetadata` (so OR's audit-trail records the
     * flip). A blocked decision performs no OpenRegister write.
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
    public function apply(int $relationId, bool $skip, ?array $bases, bool $force): array
    {
        $mapper = $this->locator->get(className: 'OCA\OpenRegister\Db\EntityRelationMapper');

        try {
            $relation = $mapper->find($relationId);
        } catch (Exception $e) {
            return ['status' => 404, 'body' => ['error' => 'Entity relation not found']];
        }

        if ($skip === true) {
            $block = $this->evaluateProhibitionSkip(
                mapper: $mapper,
                relation: $relation,
                relationId: $relationId,
                force: $force
            );
            if ($block !== null) {
                return ['status' => 422, 'body' => $block];
            }
        }

        $fields = ['skipAnonymization' => $skip];
        if ($bases !== null) {
            $fields['bases'] = $bases;
        }

        $mapper->updateDecisionMetadata($relation, $fields);

        return ['status' => 200, 'body' => ['status' => 'ok', 'skipAnonymization' => $skip]];

    }//end apply()

    /**
     * Evaluate the prohibition guard for a skip on one relation.
     *
     * Resolves the occurrence's entity value/type/confidence via the file join,
     * matches it against the prohibition cache, and classifies the skip. Returns
     * the 422 body when the skip is blocked, or null when it is allowed (not a
     * prohibition match, released by force, or the match cannot be resolved).
     *
     * @param mixed $mapper     OpenRegister EntityRelationMapper (DI).
     * @param mixed $relation   The EntityRelation being decided.
     * @param int   $relationId The relation id (for the file-join lookup + logs).
     * @param bool  $force      Whether the request set force.
     *
     * @return array<string, mixed>|null The 422 body when blocked, else null.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    private function evaluateProhibitionSkip(mixed $mapper, mixed $relation, int $relationId, bool $force): ?array
    {
        $row = $this->findRelationRow(
            mapper: $mapper,
            fileId: (int) $relation->getFileId(),
            relationId: $relationId
        );
        if ($row === null) {
            return null;
        }

        $value = (string) ($row['entity_value'] ?? '');
        if ($value === '') {
            return null;
        }

        try {
            $matcher = $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
        } catch (Exception $e) {
            $this->logger->warning('Policy matcher unavailable; skip guard is a no-op', ['exception' => $e->getMessage()]);
            return null;
        }

        $match = $matcher->matchProhibition(
            entityText: $value,
            entityType: (string) ($row['entity_type'] ?? 'OTHER')
        );
        if ($match === null) {
            return null;
        }

        $confidence = (float) ($row['confidence'] ?? 0.0);
        $threshold  = (float) $matcher->highConfidenceThreshold();
        $decision   = $this->tier->classify(confidence: $confidence, threshold: $threshold, force: $force);
        if ($decision === 'allow') {
            return null;
        }

        $this->logger->warning(
            'Prohibition guard blocked a skip decision',
            [
                'ruleId'     => $match['uuid'],
                'entityId'   => (int) ($row['entity_id'] ?? 0),
                'relationId' => $relationId,
            ]
        );

        return [
            'error'            => 'Entity is on the publication prohibition list; skipping is not allowed.',
            'threshold'        => $threshold,
            'prohibitionMatch' => [
                'entityId'   => (int) ($row['entity_id'] ?? 0),
                'entityName' => $value,
                'ruleId'     => $match['uuid'],
                'ruleName'   => $match['primaryName'],
                'confidence' => $confidence,
                'absolute'   => ($decision === 'block_absolute'),
            ],
        ];

    }//end evaluateProhibitionSkip()

    /**
     * Find the file-join row that belongs to one relation id.
     *
     * @param mixed $mapper     OpenRegister EntityRelationMapper (DI).
     * @param int   $fileId     The relation's file id.
     * @param int   $relationId The relation id being looked up.
     *
     * @return array<string, mixed>|null The joined row, or null when it cannot be resolved.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    private function findRelationRow(mixed $mapper, int $fileId, int $relationId): ?array
    {
        foreach ($mapper->findEntitiesForFile($fileId) as $candidate) {
            if ((int) ($candidate['relation_id'] ?? 0) === $relationId) {
                return $candidate;
            }
        }

        return null;

    }//end findRelationRow()
}//end class
