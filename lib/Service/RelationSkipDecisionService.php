<?php

/**
 * Relation Skip Decision Service
 *
 * Guards and applies the per-relation skip/include decision the review UI
 * sends in place of PATCHing OpenRegister's relation endpoint directly.
 * Extracted verbatim from AnonymizationService.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Applies a guarded skip/include decision to one EntityRelation.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */
class RelationSkipDecisionService {

	/**
	 * The shared tier rule for a skip on a prohibition-matched entity.
	 *
	 * @var ProhibitionSkipTier
	 */
	private readonly ProhibitionSkipTier $tier;

	/**
	 * Constructor for RelationSkipDecisionService
	 *
	 * @param LoggerInterface $logger Logger for blocked decisions and outages.
	 * @param ContainerInterface $container Container the PolicyMatchService is resolved from.
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
	 * @param IUserSession $userSession The acting session, for the ownership guard + audit actor.
	 * @param IRootFolder $rootFolder Root folder, used to resolve the acting user's own file tree.
	 * @param ProhibitionOverrideAuthority $authority Who may release a prohibition match.
	 * @param ProhibitionOverrideCommitter $committer Records a release in prohibitionOverrideAudit.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly IUserSession $userSession,
		private readonly IRootFolder $rootFolder,
		private readonly ProhibitionOverrideAuthority $authority,
		private readonly ProhibitionOverrideCommitter $committer,
	) {
		$this->tier = new ProhibitionSkipTier();

	}//end __construct()

	/**
	 * Guard + apply a per-relation skip/include decision from the review UI.
	 *
	 * TWO independent guards run before any write:
	 *
	 *  1. Authorisation (`requireRelationAccess`). `EntityRelationMapper::find()`
	 *     is an unscoped primary-key lookup, so the caller-supplied `relationId`
	 *     addresses EVERY relation in the instance, not just the caller's own.
	 *     Without an ownership check this endpoint was an IDOR: any authenticated
	 *     user could flip `skipAnonymization` on a relation belonging to someone
	 *     else's document and thereby leave that document's PII un-redacted.
	 *     The relation carries the Nextcloud file id of the document it was
	 *     detected in, so access is decided the same way `AnonymizeRequestService
	 *     ::verifyFileAccess()` decides it for extract/anonymize — resolution
	 *     through the acting user's OWN file tree. A relation the caller cannot
	 *     reach returns the SAME 404 as a relation that does not exist, so the
	 *     endpoint is not an existence oracle.
	 *  2. Prohibition policy ({@see ProhibitionSkipTier::classify}) — unchanged.
	 *     Include / non-skip decisions are always allowed by this guard.
	 *
	 * Allowed decisions are forwarded to OpenRegister via
	 * `updateDecisionMetadata`, WITH the acting user, so OR's audit trail records
	 * who flipped the decision rather than an anonymous entry. A blocked decision
	 * performs no OpenRegister write.
	 *
	 * @param int $relationId The EntityRelation id.
	 * @param bool $skip The requested skipAnonymization value.
	 * @param array|null $bases Optional bases to set alongside the decision.
	 * @param bool $force Release a sub-threshold prohibition match.
	 *
	 * @return array{status: 200|401|404|422, body: array<string, mixed>} HTTP status + response body.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	public function apply(int $relationId, bool $skip, ?array $bases, bool $force): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return ['status' => 401, 'body' => ['error' => 'Not authenticated']];
		}

		$mapper = $this->locator->get(className: 'OCA\OpenRegister\Db\EntityRelationMapper');

		try {
			$relation = $mapper->find($relationId);
		} catch (Exception $e) {
			return ['status' => 404, 'body' => ['error' => 'Entity relation not found']];
		}

		if ($this->requireRelationAccess(relation: $relation, userId: $user->getUID()) === false) {
			return ['status' => 404, 'body' => ['error' => 'Entity relation not found']];
		}

		if ($skip === true) {
			$match = $this->findProhibitionMatch(mapper: $mapper, fileId: (int)$relation->getFileId(), relationId: $relationId);
			$block = null;
			if ($match !== null) {
				$block = $this->evaluateProhibitionSkip(match: $match, force: $force, userId: $user->getUID());
			}

			if ($block !== null) {
				return ['status' => 422, 'body' => $block];
			}
		}

		$fields = ['skipAnonymization' => $skip];
		if ($bases !== null) {
			$fields['bases'] = $bases;
		}

		$mapper->updateDecisionMetadata($relation, $fields, $user);

		return ['status' => 200, 'body' => ['status' => 'ok', 'skipAnonymization' => $skip]];
	}//end apply()

	/**
	 * Whether the acting user may decide on this relation.
	 *
	 * The relation is reachable exactly when the document it was detected in is
	 * reachable, and a Nextcloud file is reachable exactly when it resolves
	 * inside the user's OWN folder tree — `IRootFolder::getById()` would search
	 * every storage and therefore answer for documents the caller has no claim
	 * to. A relation with no file id is unattributable and is refused.
	 *
	 * @param mixed $relation The EntityRelation being decided.
	 * @param string $userId UID of the acting user.
	 *
	 * @return bool True when the decision may proceed.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function requireRelationAccess(mixed $relation, string $userId): bool {
		$fileId = (int)$relation->getFileId();
		if ($fileId === 0) {
			return false;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		} catch (Exception $e) {
			$this->logger->warning(
				'Relation decision denied: could not resolve the acting user file tree',
				['relationFileId' => $fileId, 'exception' => $e->getMessage()]
			);
			return false;
		}

		return empty($nodes) === false;
	}//end requireRelationAccess()

	/**
	 * Find the prohibition rule the occurrence behind one relation matches.
	 *
	 * Resolves the occurrence's entity value/type/confidence via the file join
	 * and matches it against the prohibition cache. Null when there is no match
	 * or it cannot be resolved (no row, no value, no policy matcher).
	 *
	 * @param mixed $mapper OpenRegister EntityRelationMapper (DI).
	 * @param int $fileId The relation's file id.
	 * @param int $relationId The relation id (for the file-join lookup + logs).
	 *
	 * @return array{row: array<string, mixed>, rule: array<string, mixed>, threshold: float, relationId: int, fileId: int}|null The match, else null.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function findProhibitionMatch(mixed $mapper, int $fileId, int $relationId): ?array {
		$row = $this->findRelationRow(mapper: $mapper, fileId: $fileId, relationId: $relationId);
		$value = (string)($row['entity_value'] ?? '');
		if ($row === null || $value === '') {
			return null;
		}

		try {
			$matcher = $this->container->get('OCA\Filinq\Service\PolicyMatchService');
		} catch (Exception $e) {
			$this->logger->warning('Policy matcher unavailable; skip guard is a no-op', ['exception' => $e->getMessage()]);
			return null;
		}

		$rule = $matcher->matchProhibition(entityText: $value, entityType: (string)($row['entity_type'] ?? 'OTHER'));
		if ($rule === null) {
			return null;
		}

		return [
			'row' => $row,
			'rule' => $rule,
			'threshold' => (float)$matcher->highConfidenceThreshold(),
			'relationId' => $relationId,
			'fileId' => $fileId,
		];
	}//end findProhibitionMatch()

	/**
	 * Evaluate the prohibition guard for a skip on a matched occurrence.
	 *
	 * Classifies the skip by confidence and force. A releasable match forced by
	 * a user with the override role is recorded as a release and allowed.
	 *
	 * @param array<string, mixed> $match The match from findProhibitionMatch().
	 * @param bool $force Whether the request set force.
	 * @param string $userId The acting user; only an override role may release a match.
	 *
	 * @return array<string, mixed>|null The 422 body when blocked, else null.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function evaluateProhibitionSkip(array $match, bool $force, string $userId): ?array {
		$confidence = (float)($match['row']['confidence'] ?? 0.0);
		$decision = $this->tier->classify(confidence: $confidence, threshold: $match['threshold'], force: $force);
		if ($decision === 'allow' && $this->authority->mayOverride(userId: $userId) === true) {
			$this->committer->recordRelease(
				ruleId: (string)$match['rule']['uuid'],
				relationId: $match['relationId'],
				fileId: $match['fileId'],
				reason: 'Released below the high-confidence threshold in the review',
				userId: $userId
			);
			return null;
		}

		$entityId = (int)($match['row']['entity_id'] ?? 0);
		$this->logger->warning(
			'Prohibition guard blocked a skip decision',
			['ruleId' => $match['rule']['uuid'], 'entityId' => $entityId, 'relationId' => $match['relationId']]
		);

		return [
			'error' => 'Entity is on the publication prohibition list; skipping is not allowed.',
			'threshold' => $match['threshold'],
			'prohibitionMatch' => [
				'entityId' => $entityId,
				'entityName' => (string)$match['row']['entity_value'],
				'ruleId' => $match['rule']['uuid'],
				'ruleName' => $match['rule']['primaryName'],
				'confidence' => $confidence,
				'absolute' => ($decision === 'block_absolute'),
			],
		];
	}//end evaluateProhibitionSkip()

	/**
	 * Find the file-join row that belongs to one relation id.
	 *
	 * @param mixed $mapper OpenRegister EntityRelationMapper (DI).
	 * @param int $fileId The relation's file id.
	 * @param int $relationId The relation id being looked up.
	 *
	 * @return array<string, mixed>|null The joined row, or null when it cannot be resolved.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function findRelationRow(mixed $mapper, int $fileId, int $relationId): ?array {
		foreach ($mapper->findEntitiesForFile($fileId) as $candidate) {
			if ((int)($candidate['relation_id'] ?? 0) === $relationId) {
				return $candidate;
			}
		}

		return null;
	}//end findRelationRow()
}//end class
