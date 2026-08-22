<?php

/**
 * PolicyMatchService — detection-time matcher for the publication policy layer.
 *
 * Loads two policy surfaces from the consent register at first call:
 *
 *   - **Prohibitions** — `publicationProhibition` records with `active: true`
 *     and time bounds open. A match resolves to "anonymise" (deny-list).
 *   - **Standing consents** — `publicationConsent` records with `scope: "entity"`,
 *     `active: true`, and time bounds open. A match resolves to "consent_given"
 *     (allow-list, but the per-document workflow may still override).
 *
 * Match-types supported in v1: `exact`, `normalized`, `bsn`, `kvk`. Unknown
 * types are logged and skipped (defence-in-depth — the schema rejects them at
 * write time).
 *
 * Conflict resolution: prohibitions are consulted first. On multi-prohibition
 * match, the rule with the lexicographically lowest UUID wins (deterministic).
 *
 * The service caches loaded rules per-request. Cache invalidation on
 * upstream rule changes is deferred to Nextcloud event-listener wiring
 * (task 3.5). Within a single request the cache is stable; across requests
 * (one per HTTP call) the cache is rebuilt on first use.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;

/**
 * Detection-time policy matcher.
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 */
class PolicyMatchService {

	/**
	 * The Filinq app id, used as the app-config namespace.
	 */
	private const APP_ID = 'filinq';

	/**
	 * Match result kind — prohibition (force anonymise).
	 */
	public const KIND_PROHIBITION = 'prohibition';

	/**
	 * Match result kind — standing consent (allow publication).
	 */
	public const KIND_STANDING_CONSENT = 'standing_consent';

	/**
	 * Lazily-built cache: list of normalised rule records.
	 *
	 * Each entry: [
	 *   'uuid'        => string,
	 *   'kind'        => self::KIND_PROHIBITION | self::KIND_STANDING_CONSENT,
	 *   'entityType'  => 'PERSON' | 'ORGANIZATION' | 'OTHER',
	 *   'matchRules'  => array<int, array{type: string, value: string}>,
	 *   'validFrom'   => ?DateTimeInterface,
	 *   'validUntil'  => ?DateTimeInterface,
	 *   'primaryName' => string (for response/audit context),
	 * ]
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private ?array $rulesCache = null;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Structured log sink.
	 * @param IAppManager $appManager App manager (used to confirm OR is installed).
	 * @param IAppConfig $config App config (prohibition high-confidence threshold).
	 * @param ObjectServiceInterface $objectService OpenRegister's published object contract (ADR-084).
	 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to plain rows.
	 * @param TextNormaliser $textNormaliser Accent-stripping text normaliser.
	 * @param PolicyRuleNormaliser $ruleNormaliser Admission + normalisation of stored policy rows.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IAppManager $appManager,
		private readonly IAppConfig $config,
		private readonly ObjectServiceInterface $objectService,
		private readonly ObjectResultExtractor $resultExtractor = new ObjectResultExtractor(),
		private readonly TextNormaliser $textNormaliser = new TextNormaliser(),
		private readonly PolicyRuleNormaliser $ruleNormaliser = new PolicyRuleNormaliser(),
	) {

	}//end __construct()

	/**
	 * The confidence at or above which a prohibition match is "absolute".
	 *
	 * A prohibition match with detection confidence >= this threshold cannot be
	 * released by `force`; below it, `force` may release the skip. Read at call
	 * time so a runtime app-config change propagates without a restart. Same
	 * threshold governs `highConfidence` in the extract response and the gate.
	 *
	 * @return float The configured threshold (default 0.85).
	 */
	public function highConfidenceThreshold(): float {
		return (float)$this->config->getValueString(
			self::APP_ID,
			'filinq.prohibition.high_confidence_threshold',
			'0.85'
		);

	}//end highConfidenceThreshold()

	/**
	 * Match a detected entity against the policy layer.
	 *
	 * @param string $entityText Detected entity text (e.g. "Pieter de Vries").
	 * @param string $entityType 'PERSON', 'ORGANIZATION', or 'OTHER'.
	 * @param array<string, mixed> $resolvedIdentifiers Optional structured identifiers
	 *                                                  attached to the entity (BSN, KvK, etc.).
	 *                                                  Shape: `['bsn' => '123456789', 'kvk' => '12345678']`.
	 *
	 * @return array<string, mixed>|null Match data, or null when no rule matches.
	 *
	 * @phpstan-return null|array{
	 *   uuid: string,
	 *   kind: 'prohibition'|'standing_consent',
	 *   entityType: string,
	 *   primaryName: string
	 * }
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	public function match(
		string $entityText,
		string $entityType,
		array $resolvedIdentifiers = [],
	): ?array {
		$rules = $this->loadRules();

		// Prohibitions win on conflict — split into two passes.
		$prohibitionMatch = $this->firstMatchOf(
			kind: self::KIND_PROHIBITION,
			rules: $rules,
			entityText: $entityText,
			entityType: $entityType,
			resolvedIdentifiers: $resolvedIdentifiers
		);

		if ($prohibitionMatch !== null) {
			return $prohibitionMatch;
		}

		return $this->firstMatchOf(
			kind: self::KIND_STANDING_CONSENT,
			rules: $rules,
			entityText: $entityText,
			entityType: $entityType,
			resolvedIdentifiers: $resolvedIdentifiers
		);

	}//end match()

	/**
	 * Match a detected entity against the prohibition layer only.
	 *
	 * Unlike {@see match}, this never returns a standing-consent match: the
	 * prohibition gate (anonymisation-prohibition-gate) is read-only safety
	 * layered on generic anonymisation and MUST NOT consult standing consents.
	 * Same return shape as {@see match}.
	 *
	 * @param string $entityText Detected entity text.
	 * @param string $entityType 'PERSON', 'ORGANIZATION', or 'OTHER'.
	 * @param array<string, mixed> $resolvedIdentifiers Optional structured identifiers (BSN, KvK).
	 *
	 * @return array<string, mixed>|null Prohibition match, or null when none matches.
	 *
	 * @phpstan-return null|array{uuid: string, kind: 'prohibition', entityType: string, primaryName: string}
	 */
	public function matchProhibition(
		string $entityText,
		string $entityType,
		array $resolvedIdentifiers = [],
	): ?array {
		return $this->firstMatchOf(
			kind: self::KIND_PROHIBITION,
			rules: $this->loadRules(),
			entityText: $entityText,
			entityType: $entityType,
			resolvedIdentifiers: $resolvedIdentifiers
		);

	}//end matchProhibition()

	/**
	 * Find the first rule of the given kind that matches the entity.
	 *
	 * Sorts candidates by UUID lexicographically so multi-match resolution
	 * is deterministic.
	 *
	 * @param string $kind Rule kind to scan.
	 * @param array<int, array<string,mixed>> $rules Cached rule list.
	 * @param string $entityText Entity literal text.
	 * @param string $entityType Entity type.
	 * @param array<string, mixed> $resolvedIdentifiers Structured identifiers.
	 *
	 * @return array<string, mixed>|null
	 */
	private function firstMatchOf(
		string $kind,
		array $rules,
		string $entityText,
		string $entityType,
		array $resolvedIdentifiers,
	): ?array {
		$matchesKind = static function (array $r) use ($kind, $entityType): bool {
			if ($r['kind'] !== $kind) {
				return false;
			}

			return $r['entityType'] === $entityType || $r['entityType'] === 'OTHER';
		};

		$candidates = array_values(array_filter($rules, $matchesKind));

		usort(
			$candidates,
			static fn (array $a, array $b): int => strcmp($a['uuid'], $b['uuid'])
		);

		$entityTextNormalised = $this->textNormaliser->normalise(value: $entityText);

		foreach ($candidates as $rule) {
			foreach ($rule['matchRules'] as $matchRule) {
				if ($this->ruleMatches(
					type: $matchRule['type'] ?? '',
					value: $matchRule['value'] ?? '',
					entityText: $entityText,
					entityTextNormalised: $entityTextNormalised,
					resolvedIdentifiers: $resolvedIdentifiers
				) === true
				) {
					return [
						'uuid' => $rule['uuid'],
						'kind' => $rule['kind'],
						'entityType' => $rule['entityType'],
						'primaryName' => $rule['primaryName'],
					];
				}
			}
		}

		return null;
	}//end firstMatchOf()

	/**
	 * Test whether any rule in a `matchRules` array matches the given entity.
	 *
	 * Public so the retroactive layer (`PolicyRetroactiveService`) can ask the
	 * inverse question — "does this new rule match this existing entity?" —
	 * without duplicating the type-by-type semantics.
	 *
	 * @param array<int, array<string, mixed>> $matchRules List of {type, value} rules.
	 * @param string $entityText Entity literal text.
	 * @param array<string, mixed> $resolvedIdentifiers Structured identifiers (BSN, KvK).
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	public function entityMatchesAnyRule(
		array $matchRules,
		string $entityText,
		array $resolvedIdentifiers = [],
	): bool {
		$entityTextNormalised = $this->textNormaliser->normalise(value: $entityText);
		foreach ($matchRules as $rule) {
			if ($this->ruleMatches(
				type: (string)($rule['type'] ?? ''),
				value: (string)($rule['value'] ?? ''),
				entityText: $entityText,
				entityTextNormalised: $entityTextNormalised,
				resolvedIdentifiers: $resolvedIdentifiers
			) === true
			) {
				return true;
			}
		}

		return false;
	}//end entityMatchesAnyRule()

	/**
	 * Test a single match rule against an entity.
	 *
	 * @param string $type Match type ('exact', 'normalized', 'bsn', 'kvk').
	 * @param string $value Match value (literal or wildcard).
	 * @param string $entityText Entity literal text.
	 * @param string $entityTextNormalised Lower-cased + accent-stripped text.
	 * @param array<string, mixed> $resolvedIdentifiers Structured identifiers (BSN, KvK).
	 *
	 * @return bool
	 */
	private function ruleMatches(
		string $type,
		string $value,
		string $entityText,
		string $entityTextNormalised,
		array $resolvedIdentifiers,
	): bool {
		switch ($type) {
			case 'exact':
				return $entityText === $value;
			case 'normalized':
				return $entityTextNormalised === $this->textNormaliser->normalise(value: $value);
			case 'bsn':
				$bsn = (string)($resolvedIdentifiers['bsn'] ?? '');
				if ($bsn === '') {
					return false;
				}
				return $value === '*' || $bsn === $value;
			case 'kvk':
				$kvk = (string)($resolvedIdentifiers['kvk'] ?? '');
				if ($kvk === '') {
					return false;
				}
				return $value === '*' || $kvk === $value;
			default:
				$this->logger->warning(
					'PolicyMatchService: unknown match type, skipping',
					['type' => $type]
				);
				return false;
		}//end switch

	}//end ruleMatches()

	/**
	 * Load both rule sources and normalise into a single cache.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function loadRules(): array {
		if ($this->rulesCache !== null) {
			return $this->rulesCache;
		}

		$rules = [];

		try {
			$rules = array_merge(
				$this->loadProhibitions(),
				$this->loadStandingConsents()
			);
		} catch (Exception $e) {
			$this->logger->warning(
				'PolicyMatchService: failed to load rules — falling through to no-match',
				['error' => $e->getMessage()]
			);
		}

		$this->rulesCache = $rules;
		return $rules;
	}//end loadRules()

	/**
	 * Load active prohibition records.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function loadProhibitions(): array {
		// OR's findAll/searchObjects require NUMERIC register/schema ids and
		// silently return nothing for slugs; searchObjectsBySlug resolves the
		// slugs first (same call PolicyCrudService uses for the admin list).
		// _multitenancy is off so this safety policy is not scoped away by the
		// active organisation.
		$result = $this->objectService->searchObjectsBySlug(
			registerSlug: 'consent',
			schemaSlug: 'publicationProhibition',
			_rbac: false,
			_multitenancy: false
		);
		if (is_int($result) === true) {
			$result = [];
		}

		$rules = [];
		foreach ($this->resultExtractor->extractRows(result: $result) as $obj) {
			$normalised = $this->ruleNormaliser->normaliseRule(
				kind: self::KIND_PROHIBITION,
				object: $obj
			);
			if ($normalised !== null) {
				$rules[] = $normalised;
			}
		}

		return $rules;
	}//end loadProhibitions()

	/**
	 * Load active standing-consent records (scope=entity).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function loadStandingConsents(): array {
		// Push the scope filter down to the DB. The schema is shared with
		// scope=document records, so a naive `findAll(register, schema)` loads
		// every consent record across every tenant and every file, then
		// discards most of them in PHP. Adding `scope=entity` to the filter
		// bounds the result to standing-consent rows and lets ObjectService
		// index on the column. The defensive PHP scope check is retained as
		// a belt-and-braces in case the filter is later dropped.
		$result = $this->objectService->searchObjectsBySlug(
			registerSlug: 'consent',
			schemaSlug: 'publicationConsent',
			filters: ['scope' => 'entity', 'active' => true],
			_rbac: false,
			_multitenancy: false
		);
		if (is_int($result) === true) {
			$result = [];
		}

		$rules = [];
		foreach ($this->resultExtractor->extractRows(result: $result) as $obj) {
			if (($obj['scope'] ?? 'document') !== 'entity') {
				continue;
			}

			$normalised = $this->ruleNormaliser->normaliseRule(
				kind: self::KIND_STANDING_CONSENT,
				object: $obj
			);
			if ($normalised !== null) {
				$rules[] = $normalised;
			}
		}

		return $rules;
	}//end loadStandingConsents()

	/**
	 * Match a detected entity against prohibition rules only.
	 *
	 * Convenience wrapper used by the extract and consolidated-entities
	 * endpoints. Returns only prohibition matches (standing-consent matches
	 * are excluded).
	 *
	 * @param string $entityType 'PERSON', 'ORGANIZATION', or 'OTHER'.
	 * @param string $entityValue Detected entity text (e.g. "Pieter de Vries").
	 *
	 * @return array<string, mixed>|null `{ruleId, ruleName}` when a prohibition
	 *                                   rule matches, null otherwise.
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
	 */
	public function matchProhibitionHint(string $entityType, string $entityValue): ?array {
		$result = $this->match(entityText: $entityValue, entityType: $entityType);
		if ($result === null || $result['kind'] !== self::KIND_PROHIBITION) {
			return null;
		}

		return [
			'ruleId' => (string)$result['uuid'],
			'ruleName' => (string)$result['primaryName'],
		];

	}//end matchProhibitionHint()

	/**
	 * Invalidate the in-memory rule cache.
	 *
	 * Public so an external event subscriber can call it when policy records
	 * change. Until that wiring lands (task 3.5), the cache is naturally
	 * stable within a single request and rebuilt on the next one.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	public function invalidateCache(): void {
		$this->rulesCache = null;

	}//end invalidateCache()
}//end class
