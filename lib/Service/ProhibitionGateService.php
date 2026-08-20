<?php

/**
 * Prohibition Gate Service
 *
 * The publication-prohibition gate that runs before any OpenRegister
 * interaction on an anonymise call: it resolves the file's detected entities,
 * matches each against the active prohibition rules, validates the caller's
 * acknowledged overrides, checks that every high-confidence match is present
 * in the to-be-anonymised set, and commits the validated overrides (DocuDesk
 * audit entry followed by the OpenRegister PATCH).
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
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Exception\ProhibitionGateException;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Enforces the publication-prohibition gate before an anonymise call.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */
class ProhibitionGateService {
	/**
	 * Default prohibition high-confidence threshold (inclusive)
	 *
	 * @var float
	 */
	private const DEFAULT_HIGH_CONFIDENCE_THRESHOLD = 0.85;

	/**
	 * App config key for the high-confidence threshold
	 *
	 * @var string
	 */
	private const HIGH_CONFIDENCE_THRESHOLD_KEY = 'prohibition.high_confidence_threshold';

	/**
	 * App config key controlling the gate's fail-mode for backend errors.
	 *
	 * When `true` (default) any backend error inside the prohibition gate
	 * (PolicyMatchService unavailable, EntityRelationMapper lookup throws,
	 * per-entity matchProhibition throws) is treated as gate-firing: the
	 * call is rejected via ProhibitionGateException. This is the safety-
	 * critical default for a gate protecting witness/undercover-officer
	 * identities — silent fail-open would let any service outage disable
	 * the gate.
	 *
	 * Set to `false` to opt into the legacy fail-open behaviour for non-
	 * production environments.
	 *
	 * @var string
	 */
	private const FAIL_CLOSED_KEY = 'prohibition.fail_closed';

	/**
	 * Default for the fail-closed flag.
	 *
	 * @var bool
	 */
	private const DEFAULT_FAIL_CLOSED = true;

	/**
	 * Writes the audit entry + OpenRegister skip flag for released overrides.
	 *
	 * @var ProhibitionOverrideCommitter
	 */
	private readonly ProhibitionOverrideCommitter $committer;

	/**
	 * Constructor for ProhibitionGateService
	 *
	 * @param LoggerInterface $logger Logger for gate decisions and backend outages.
	 * @param IAppConfig $appConfig App configuration for threshold + fail-mode settings.
	 * @param ContainerInterface $container Container the PolicyMatchService is resolved from.
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IAppConfig $appConfig,
		private readonly ContainerInterface $container,
		private readonly OpenRegisterServiceLocator $locator,
	) {
		$this->committer = new ProhibitionOverrideCommitter(
			logger: $logger,
			locator: $locator
		);

	}//end __construct()

	/**
	 * Run the prohibition gate before forwarding to OpenRegister.
	 *
	 * Resolves detected entities for the file, matches each against active
	 * prohibition rules, validates overrides, checks that high-confidence
	 * matches are present in the to-be-anonymised set, and commits validated
	 * overrides (DocuDesk audit entry + OR PATCH). Throws
	 * ProhibitionGateException when the gate blocks the call.
	 *
	 * @param int $fileId Nextcloud file ID.
	 * @param array<int, array<string, mixed>> $requestEntities User-submitted entities[] to anonymize.
	 * @param array<int, array<string, mixed>> $overrides Override entries {ruleId, entityId, reason?}.
	 * @param string $userId UID of the acting user.
	 *
	 * @return void
	 *
	 * @throws ProhibitionGateException When the gate blocks the call.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
	 */
	public function run(int $fileId, array $requestEntities, array $overrides, string $userId): void {
		$policyService = $this->resolvePolicyService(fileId: $fileId);
		if ($policyService === null) {
			return;
		}

		$rawEntities = $this->loadFileEntities(fileId: $fileId);
		if ($rawEntities === null) {
			return;
		}

		// Build prohibition matches: [ruleId, ruleName, entityId, entityRelationId, confidence, entityValue].
		$matches = $this->buildProhibitionMatches(
			rawEntities: $rawEntities,
			policyService: $policyService
		);

		if ($matches === []) {
			return;
		}

		$split = $this->splitOverrides(matches: $matches, overrides: $overrides);
		$missing = $this->collectMissingMatches(
			matches: $matches,
			released: $split['released'],
			requestEntities: $requestEntities
		);

		if ($missing !== [] || $split['rejected'] !== []) {
			$this->logger->warning(
				'ProhibitionGate: 422 — prohibition gate fired',
				[
					'fileId' => $fileId,
					'missingCount' => count($missing),
					'rejectedOverrideCount' => count($split['rejected']),
					'ruleIds' => array_column($missing, 'ruleId'),
					'entityIds' => array_column($missing, 'entityId'),
				]
			);

			throw new ProhibitionGateException(
				missingMatches: $missing,
				rejectedOverrides: $split['rejected']
			);
		}

		// Gate passes — commit validated overrides.
		if ($split['released'] !== []) {
			$this->committer->commit(
				released: $split['released'],
				fileId: $fileId,
				userId: $userId
			);
		}

	}//end run()

	/**
	 * Read the high-confidence threshold from app config.
	 *
	 * Default 0.85; configurable via docudesk.prohibition.high_confidence_threshold.
	 *
	 * @return float Threshold value (inclusive boundary).
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
	 */
	public function getHighConfidenceThreshold(): float {
		return $this->appConfig->getValueFloat(
			app: 'docudesk',
			key: self::HIGH_CONFIDENCE_THRESHOLD_KEY,
			default: self::DEFAULT_HIGH_CONFIDENCE_THRESHOLD
		);

	}//end getHighConfidenceThreshold()

	/**
	 * Read the fail-closed flag from app config.
	 *
	 * Defaults to TRUE — the gate fails closed by default for any backend
	 * outage path. Operators can flip to false for non-production via
	 * docudesk.prohibition.fail_closed.
	 *
	 * @return bool True when a backend outage must reject the call.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 */
	public function isFailClosed(): bool {
		return $this->appConfig->getValueBool(
			app: 'docudesk',
			key: self::FAIL_CLOSED_KEY,
			default: self::DEFAULT_FAIL_CLOSED
		);

	}//end isFailClosed()

	/**
	 * Resolve PolicyMatchService, honouring the configured fail-mode.
	 *
	 * PolicyMatchService not available — fail-CLOSED by default for a
	 * privacy-critical safety gate. Silent fail-open would let any service
	 * outage disable witness/undercover-officer protection. Operators can opt
	 * into legacy fail-open via docudesk.prohibition.fail_closed=false in
	 * non-production environments.
	 *
	 * @param int $fileId Nextcloud file ID (for the log context).
	 *
	 * @return mixed The PolicyMatchService, or null when the gate must fail open.
	 *
	 * @throws ProhibitionGateException When the gate is fail-closed and the service is absent.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 */
	private function resolvePolicyService(int $fileId): mixed {
		try {
			return $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
		} catch (Throwable) {
			// Fall through to the fail-mode handling below.
		}

		if ($this->isFailClosed() === true) {
			$this->logger->warning(
				'ProhibitionGate: PolicyMatchService unavailable — failing closed',
				['fileId' => $fileId]
			);
			throw new ProhibitionGateException(
				missingMatches: [],
				rejectedOverrides: [],
				backendUnavailable: 'PolicyMatchService unavailable'
			);
		}

		$this->logger->warning(
			'ProhibitionGate: PolicyMatchService unavailable — fail-open (legacy mode)',
			['fileId' => $fileId]
		);

		return null;
	}//end resolvePolicyService()

	/**
	 * Load the file's detected entity relations, honouring the fail-mode.
	 *
	 * @param int $fileId Nextcloud file ID.
	 *
	 * @return array<int, mixed>|null The raw relation rows, or null when the gate must fail open.
	 *
	 * @throws ProhibitionGateException When the gate is fail-closed and the lookup fails.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 */
	private function loadFileEntities(int $fileId): ?array {
		try {
			$entityRelationMapper = $this->locator->get(
				className: 'OCA\OpenRegister\Db\EntityRelationMapper'
			);
			return $entityRelationMapper->findEntitiesForFile($fileId);
		} catch (Throwable $e) {
			if ($this->isFailClosed() === true) {
				$this->logger->warning(
					'ProhibitionGate: failed to load entities for file — failing closed',
					['fileId' => $fileId, 'error' => $e->getMessage()]
				);
				throw new ProhibitionGateException(
					missingMatches: [],
					rejectedOverrides: [],
					backendUnavailable: 'EntityRelationMapper unavailable: ' . $e->getMessage()
				);
			}

			$this->logger->warning(
				'ProhibitionGate: failed to load entities for file — fail-open (legacy mode)',
				['fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return null;
		}//end try

	}//end loadFileEntities()

	/**
	 * Build prohibition matches from raw EntityRelation data.
	 *
	 * For each raw entity, calls PolicyMatchService::matchProhibition and
	 * collects matches into a structured list.
	 *
	 * @param array<int, mixed> $rawEntities Raw EntityRelation rows from findEntitiesForFile.
	 * @param mixed $policyService PolicyMatchService instance.
	 *
	 * @return array<int, array<string, mixed>> Match entries with ruleId, ruleName, entityId,
	 *                                          entityRelationId, confidence, entityValue.
	 *
	 * @throws ProhibitionGateException When fail-closed and a per-entity match throws.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 */
	private function buildProhibitionMatches(array $rawEntities, mixed $policyService): array {
		$matches = [];
		$failClosed = $this->isFailClosed();

		foreach ($rawEntities as $raw) {
			$entityData = (array)$raw;
			if (is_object($raw) === true && method_exists($raw, 'jsonSerialize') === true) {
				$entityData = $raw->jsonSerialize();
			}

			$entityValue = (string)($entityData['entity_value'] ?? $entityData['entityValue'] ?? '');
			if ($entityValue === '') {
				continue;
			}

			$entityType = (string)($entityData['entity_type'] ?? $entityData['entityType'] ?? 'UNKNOWN');
			$entityId = (int)($entityData['entity_id'] ?? $entityData['entityId'] ?? 0);

			$match = $this->matchOrEscalate(
				policyService: $policyService,
				entityType: $entityType,
				entityValue: $entityValue,
				entityId: $entityId,
				failClosed: $failClosed
			);
			if ($match === null) {
				continue;
			}

			$matches[] = [
				'ruleId' => (string)($match['ruleId'] ?? ''),
				'ruleName' => (string)($match['ruleName'] ?? ''),
				'entityId' => $entityId,
				'entityRelationId' => (int)($entityData['relation_id'] ?? $entityData['relationId'] ?? 0),
				'confidence' => (float)($entityData['confidence'] ?? 0.0),
				'entityValue' => $entityValue,
			];
		}//end foreach

		return $matches;
	}//end buildProhibitionMatches()

	/**
	 * Match one entity against the prohibition rules, escalating when fail-closed.
	 *
	 * A per-entity match failure under fail-closed is escalated so run() surfaces
	 * a 422/503 rather than silently skipping the entity (which would allow the
	 * anonymise call to proceed without a check).
	 *
	 * @param mixed $policyService PolicyMatchService instance.
	 * @param string $entityType The occurrence's entity type.
	 * @param string $entityValue The occurrence's detected text.
	 * @param int $entityId The global entity id (for the log context).
	 * @param bool $failClosed Whether a backend error must reject the call.
	 *
	 * @return array<string, mixed>|null The rule match, or null when there is none.
	 *
	 * @throws ProhibitionGateException When fail-closed and the match throws.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 */
	private function matchOrEscalate(
		mixed $policyService,
		string $entityType,
		string $entityValue,
		int $entityId,
		bool $failClosed,
	): ?array {
		try {
			return $policyService->matchProhibition(
				entityType: $entityType,
				entityValue: $entityValue
			);
		} catch (Throwable $e) {
			if ($failClosed === true) {
				$this->logger->warning(
					'ProhibitionGate: matchProhibition threw — failing closed',
					[
						'entityId' => $entityId,
						'entityType' => $entityType,
						'exception' => $e->getMessage(),
					]
				);
				throw new ProhibitionGateException(
					missingMatches: [],
					rejectedOverrides: [],
					backendUnavailable: 'PolicyMatchService::matchProhibition threw: ' . $e->getMessage()
				);
			}

			$this->logger->debug(
				'ProhibitionGate: matchProhibition threw; skipping entity (legacy fail-open)',
				['exception' => $e->getMessage()]
			);

			return null;
		}//end try

	}//end matchOrEscalate()

	/**
	 * Split acknowledged overrides into released and rejected sets.
	 *
	 * A non-matching (ruleId, entityId) combination is silently ignored; an
	 * override on a high-confidence match is rejected; an override on a
	 * low-confidence match releases that match.
	 *
	 * @param array<int, array<string, mixed>> $matches Prohibition matches for the file.
	 * @param array<int, array<string, mixed>> $overrides Override entries {ruleId, entityId, reason?}.
	 *
	 * @return array{released: array<string, array<string, mixed>>,
	 *               rejected: array<int, array<string, mixed>>} Released overrides keyed by
	 *                                                           'ruleId|entityId', plus the rejections.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function splitOverrides(array $matches, array $overrides): array {
		$threshold = $this->getHighConfidenceThreshold();
		$released = [];
		$rejected = [];

		foreach ($overrides as $override) {
			$overrideRuleId = (string)($override['ruleId'] ?? '');
			$overrideEntityId = (int)($override['entityId'] ?? 0);

			$foundMatch = $this->findMatch(
				matches: $matches,
				ruleId: $overrideRuleId,
				entityId: $overrideEntityId
			);

			// Non-matching combination: silently ignore.
			if ($foundMatch === null) {
				continue;
			}

			// High-confidence match: override is rejected.
			if ((float)$foundMatch['confidence'] >= $threshold) {
				$rejected[] = [
					'ruleId' => $overrideRuleId,
					'entityId' => $overrideEntityId,
					'reason' => 'override not allowed for high-confidence matches',
				];
				continue;
			}

			// Low-confidence match: override is valid — mark as released.
			$released[$overrideRuleId . '|' . $overrideEntityId] = [
				'match' => $foundMatch,
				'reason' => (string)($override['reason'] ?? ''),
			];
		}//end foreach

		return ['released' => $released, 'rejected' => $rejected];
	}//end splitOverrides()

	/**
	 * Find the prohibition match for a (ruleId, entityId) pair.
	 *
	 * @param array<int, array<string, mixed>> $matches Prohibition matches for the file.
	 * @param string $ruleId The rule id being looked up.
	 * @param int $entityId The entity id being looked up.
	 *
	 * @return array<string, mixed>|null The match, or null when the pair does not occur.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function findMatch(array $matches, string $ruleId, int $entityId): ?array {
		foreach ($matches as $match) {
			if ($match['ruleId'] === $ruleId && (int)$match['entityId'] === $entityId) {
				return $match;
			}
		}

		return null;
	}//end findMatch()

	/**
	 * Identify high-confidence matches missing from the to-be-anonymised set.
	 *
	 * @param array<int, array<string, mixed>> $matches Prohibition matches for the file.
	 * @param array<string, array<string, mixed>> $released Overrides released by splitOverrides().
	 * @param array<int, array<string, mixed>> $requestEntities User-submitted entities[].
	 *
	 * @return array<int, array<string, mixed>> Missing entries for the 422 body.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
	 */
	private function collectMissingMatches(array $matches, array $released, array $requestEntities): array {
		$threshold = $this->getHighConfidenceThreshold();

		// Build the request entity value set for fast lookup.
		$requestValues = [];
		foreach ($requestEntities as $ent) {
			$val = (string)($ent['value'] ?? $ent['text'] ?? '');
			if ($val !== '') {
				$requestValues[mb_strtolower($val)] = true;
			}
		}

		$missing = [];
		foreach ($matches as $match) {
			$key = $match['ruleId'] . '|' . (int)$match['entityId'];

			// Released by a valid override, or low-confidence with no override:
			// not required in entities[].
			if (isset($released[$key]) === true || (float)$match['confidence'] < $threshold) {
				continue;
			}

			// High-confidence match — must be in entities[].
			$entityValue = (string)($match['entityValue'] ?? '');
			if (isset($requestValues[mb_strtolower($entityValue)]) === true) {
				continue;
			}

			$resolvedEntityName = $this->tryGetEntityCanonicalName(entityId: (int)$match['entityId']);
			if ($resolvedEntityName === '') {
				$resolvedEntityName = $entityValue;
			}

			$missing[] = [
				'entityId' => (int)$match['entityId'],
				'entityName' => $resolvedEntityName,
				'ruleId' => $match['ruleId'],
				'ruleName' => $match['ruleName'],
				'confidence' => (float)$match['confidence'],
			];
		}//end foreach

		return $missing;
	}//end collectMissingMatches()

	/**
	 * Try to get the canonical name of an OR Entity record.
	 *
	 * Best-effort: returns empty string when OR is unavailable or the entity
	 * has no canonical name field. The gate falls back to the detected text
	 * when this returns empty.
	 *
	 * @param int $entityId OR Entity record ID.
	 *
	 * @return string Canonical name, or empty string on failure.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
	 */
	private function tryGetEntityCanonicalName(int $entityId): string {
		if ($entityId <= 0) {
			return '';
		}

		try {
			$objectService = $this->locator->get(
				className: 'OCA\OpenRegister\Service\ObjectService'
			);
			$entity = $objectService->find(
				id: (string)$entityId,
				register: 'entities',
				schema: 'entity'
			);

			if (is_array($entity) === false) {
				return '';
			}

			return (string)(
				$entity['canonicalName'] ?? $entity['canonical_name'] ?? $entity['name'] ?? $entity['displayName'] ?? $entity['primaryName'] ?? ''
			);
		} catch (Throwable) {
			return '';
		}//end try

	}//end tryGetEntityCanonicalName()
}//end class
