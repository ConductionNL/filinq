<?php

/**
 * PolicyRetroactiveService — applies rule mutations to in-flight workflow records.
 *
 * Spec §5 of `entity-publication-policies`. The retroactive layer is asymmetric:
 *
 *   - **Prohibition INSERT / re-activation / scope widening** → all in-flight
 *     `scope: "document"` `publicationConsent` records (status in {pending,
 *     consent_given, objection_received, no_response}) whose entity now matches
 *     are force-resolved to `anonymized`. `notificationSentAt` /
 *     `objectionReceivedAt` are preserved for audit; `objectionDeadline` is cleared.
 *   - **Standing-consent INSERT / re-activation** → no-op. The standing consent
 *     applies to FUTURE detections only; existing decisions stand. The
 *     asymmetry is deliberate and matches the spec — "privacy default wins on
 *     retroactive sweep".
 *   - **Rule DELETE / expiry / deactivation** → no-op. Past records keep their
 *     final state with their `policyMatch` reference intact (the reference
 *     becomes dangling, which OpenRegister's referential-integrity surface
 *     handles separately).
 *
 * The actual matching is delegated to `PolicyMatchService::entityMatchesAnyRule()`
 * so the type-by-type semantics (`exact`, `normalized`, `bsn`, `kvk`) live in
 * exactly one place.
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
 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-adding-a-prohibition-must-force-resolve-in-flight-per-document-records
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use Exception;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Retroactive rule-mutation handler.
 */
class PolicyRetroactiveService {

	/**
	 * Statuses considered "in-flight" — eligible for retroactive force-resolve.
	 *
	 * `anonymized` is terminal (the goal state); excluded so we don't re-write
	 * already-resolved records. `published_*` derivatives, if they existed, would
	 * also be terminal — the current schema does not surface them as `consentStatus`
	 * values, so we don't filter on them here.
	 *
	 * @var array<int, string>
	 */
	private const IN_FLIGHT_STATUSES = [
		'pending',
		'consent_given',
		'objection_received',
		'no_response',
	];

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Structured log sink.
	 * @param PolicyMatchService $policyMatcher Reusable rule-evaluation primitives.
	 * @param ObjectServiceInterface $objectService OpenRegister's published object contract (ADR-084).
	 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to plain rows.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly PolicyMatchService $policyMatcher,
		private readonly ObjectServiceInterface $objectService,
		private readonly ObjectResultExtractor $resultExtractor = new ObjectResultExtractor(),
	) {

	}//end __construct()

	/**
	 * Apply a prohibition mutation retroactively to in-flight workflow records.
	 *
	 * Call this after a `publicationProhibition` has been created OR updated to
	 * `active: true` OR had its `matchRules` widened OR had `validUntil` extended
	 * — the caller decides which updates qualify; this method is the worker.
	 *
	 * @param array<string, mixed> $prohibition The prohibition record's plain data.
	 *
	 * @return int Number of in-flight records that were force-resolved.
	 */
	public function applyProhibitionMutation(array $prohibition): int {
		if ($this->isProhibitionEligible(prohibition: $prohibition) === false) {
			return 0;
		}

		$prohibitionUuid = $this->readRecordUuid(record: $prohibition);
		if ($prohibitionUuid === '') {
			$this->logger->warning(
				'PolicyRetroactiveService: prohibition has no UUID; skipping retroactive sweep'
			);
			return 0;
		}

		$matchRules = ($prohibition['matchRules'] ?? []);
		if (is_array($matchRules) === false || count($matchRules) === 0) {
			return 0;
		}

		$resolved = $this->sweepInFlightRecords(
			matchRules: $matchRules,
			entityType: (string)($prohibition['entityType'] ?? 'OTHER'),
			prohibitionUuid: $prohibitionUuid
		);

		$this->policyMatcher->invalidateCache();

		if ($resolved > 0) {
			$this->logger->info(
				'PolicyRetroactiveService: prohibition retroactively force-resolved records',
				[
					'prohibitionUuid' => $prohibitionUuid,
					'resolved' => $resolved,
				]
			);
		}

		return $resolved;
	}//end applyProhibitionMutation()

	/**
	 * Force-resolve every in-flight record the given rules now match.
	 *
	 * @param array<int, array<string, mixed>> $matchRules The prohibition's match rules.
	 * @param string $entityType Entity type the prohibition targets.
	 * @param string $prohibitionUuid UUID recorded on each resolved record.
	 *
	 * @return int Number of records that were successfully force-resolved.
	 */
	private function sweepInFlightRecords(
		array $matchRules,
		string $entityType,
		string $prohibitionUuid,
	): int {
		$resolved = 0;

		foreach ($this->loadInFlightDocumentRecords(entityType: $entityType) as $candidate) {
			if ($this->candidateMatches(candidate: $candidate, matchRules: $matchRules) === false) {
				continue;
			}

			if ($this->forceResolveToAnonymized(
				record: $candidate,
				prohibitionUuid: $prohibitionUuid
			) === true
			) {
				$resolved++;
			}
		}

		return $resolved;
	}//end sweepInFlightRecords()

	/**
	 * Test one in-flight record against the prohibition's match rules.
	 *
	 * @param array<string, mixed> $candidate The in-flight record.
	 * @param array<int, array<string, mixed>> $matchRules The prohibition's match rules.
	 *
	 * @return bool True when the record's entity is covered by the rules.
	 */
	private function candidateMatches(array $candidate, array $matchRules): bool {
		$entityText = (string)($candidate['entityText'] ?? '');
		if ($entityText === '') {
			return false;
		}

		return $this->policyMatcher->entityMatchesAnyRule(
			matchRules: $matchRules,
			entityText: $entityText,
			resolvedIdentifiers: $this->extractIdentifiers(object: $candidate)
		);

	}//end candidateMatches()

	/**
	 * Read a record's UUID from its `@self` envelope or its top-level keys.
	 *
	 * @param array<string, mixed> $record The record's plain data.
	 *
	 * @return string The UUID, or an empty string when the record carries none.
	 */
	private function readRecordUuid(array $record): string {
		$self = ($record['@self'] ?? []);

		return (string)(
			$self['id'] ?? $self['uuid'] ?? $record['id'] ?? $record['uuid'] ?? ''
		);

	}//end readRecordUuid()

	/**
	 * Standing-consent mutations are intentionally NOT applied retroactively.
	 *
	 * Future detections benefit; past detections retain their already-decided
	 * outcomes (spec §5.2). The only effect of this method is cache invalidation.
	 *
	 * @return void
	 */
	public function applyStandingConsentMutation(): void {
		$this->policyMatcher->invalidateCache();

	}//end applyStandingConsentMutation()

	/**
	 * Rule deletions / deactivations / expiries do NOT modify past records.
	 *
	 * Past `publicationConsent` records keep their final state and their
	 * `policyMatch` reference (which becomes dangling). Spec §5.3. Cache is
	 * invalidated so future detections see the rule absence.
	 *
	 * @return void
	 */
	public function applyRuleRemoval(): void {
		$this->policyMatcher->invalidateCache();

	}//end applyRuleRemoval()

	/**
	 * Decide whether a prohibition is eligible to drive a retroactive sweep.
	 *
	 * Inactive rules and rules outside their validity window are skipped — we
	 * only sweep when the rule would also produce new matches.
	 *
	 * @param array<string, mixed> $prohibition The prohibition's plain data.
	 *
	 * @return bool
	 */
	private function isProhibitionEligible(array $prohibition): bool {
		if (($prohibition['active'] ?? true) !== true) {
			return false;
		}

		$now = new DateTimeImmutable();
		$validFrom = $this->parseDateTime(value: (string)($prohibition['validFrom'] ?? ''));
		$validUntil = $this->parseDateTime(value: (string)($prohibition['validUntil'] ?? ''));

		if ($validFrom !== null && $validFrom > $now) {
			return false;
		}

		if ($validUntil !== null && $validUntil < $now) {
			return false;
		}

		return true;
	}//end isProhibitionEligible()

	/**
	 * Load in-flight `scope: "document"` `publicationConsent` records.
	 *
	 * Filters: scope=document, consentStatus in IN_FLIGHT_STATUSES, policyMatch
	 * not already set (records pre-empted by a different policy stay as-is and
	 * are not re-pointed by this routine).
	 *
	 * @param string $entityType Optional entity-type filter.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function loadInFlightDocumentRecords(string $entityType): array {
		try {
			// Push the scope filter down to the DB. The schema is shared
			// with scope=entity (standing-consent) records, so a naive
			// `findAll(register, schema)` loads every consent row across
			// every tenant / every file and discards most in PHP. Adding
			// `scope=document` to the filter bounds the result set to
			// what this method actually needs. The defensive PHP scope
			// check below is retained as a belt-and-braces.
			$result = $this->objectService->findAll(
				config: [
					'filters' => [
						'register' => 'filinq',
						'schema' => 'publicationConsent',
						'scope' => 'document',
					],
				],
				_rbac: false
			);

			$records = $this->resultExtractor->extractRows(result: $result);
			return array_values(
				array_filter(
					$records,
					function (array $r) use ($entityType): bool {
						if (($r['scope'] ?? 'document') !== 'document') {
							return false;
						}

						$status = (string)($r['consentStatus'] ?? '');
						if (in_array($status, self::IN_FLIGHT_STATUSES, true) === false) {
							return false;
						}

						// `!== null` dropped: isset() already excludes null. The
						// `!== ''` stays — isset() is true for an empty string.
						if (isset($r['policyMatch']) === true
							&& $r['policyMatch'] !== ''
						) {
							return false;
						}

						$recordType = (string)($r['entityType'] ?? '');
						return $recordType === $entityType || $entityType === 'OTHER';
					}
				)
			);
		} catch (Exception $e) {
			$this->logger->warning(
				'PolicyRetroactiveService: failed to load in-flight records',
				['error' => $e->getMessage()]
			);
			return [];
		}//end try

	}//end loadInFlightDocumentRecords()

	/**
	 * Persist the force-resolved state on a single record.
	 *
	 * Preserves: `notificationSentAt`, `objectionReceivedAt`, all entity fields,
	 * and the rest of the original payload. Sets: `consentStatus: "anonymized"`,
	 * `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`,
	 * `objectionDeadline: null`, `policyMatch: <prohibition uuid>`.
	 *
	 * @param array<string, mixed> $record The current record data.
	 * @param string $prohibitionUuid UUID of the prohibition that triggered this.
	 *
	 * @return bool True on success, false on write failure.
	 */
	private function forceResolveToAnonymized(array $record, string $prohibitionUuid): bool {
		$self = ($record['@self'] ?? []);
		$uuid = (string)($self['id'] ?? $self['uuid'] ?? $record['id'] ?? $record['uuid'] ?? '');
		if ($uuid === '') {
			return false;
		}

		try {
			$newData = array_merge(
				$record,
				[
					'consentStatus' => 'anonymized',
					'notificationStatus' => 'skipped',
					'publicationDecision' => 'anonymize',
					'objectionDeadline' => null,
					'policyMatch' => $prohibitionUuid,
				]
			);

			// Strip the @self envelope before save — OR adds it back.
			unset($newData['@self']);

			$this->objectService->saveObject(
				object: $newData,
				register: 'filinq',
				schema: 'publicationConsent',
				uuid: $uuid,
				_rbac: false,
				_multitenancy: false
			);
			return true;
		} catch (Exception $e) {
			$this->logger->error(
				'PolicyRetroactiveService: failed to force-resolve record',
				[
					'recordUuid' => $uuid,
					'prohibitionUuid' => $prohibitionUuid,
					'error' => $e->getMessage(),
				]
			);
			return false;
		}//end try

	}//end forceResolveToAnonymized()

	/**
	 * Pull `bsn` / `kvk` identifiers from a record if present.
	 *
	 * The schema does not currently formalise these fields on
	 * `publicationConsent`, but seed data and future extensions may. This
	 * method is forward-compatible — empty result is fine.
	 *
	 * @param array<string, mixed> $object The record.
	 *
	 * @return array<string, string>
	 */
	private function extractIdentifiers(array $object): array {
		$identifiers = [];
		if (isset($object['bsn']) === true && is_string($object['bsn']) === true) {
			$identifiers['bsn'] = $object['bsn'];
		}

		if (isset($object['kvk']) === true && is_string($object['kvk']) === true) {
			$identifiers['kvk'] = $object['kvk'];
		}

		$resolved = ($object['resolvedIdentifiers'] ?? null);
		if (is_array($resolved) === true) {
			foreach (['bsn', 'kvk'] as $key) {
				if (isset($resolved[$key]) === true && is_string($resolved[$key]) === true) {
					$identifiers[$key] = $resolved[$key];
				}
			}
		}

		return $identifiers;
	}//end extractIdentifiers()

	/**
	 * Parse ISO-8601 to DateTimeImmutable; null on failure.
	 *
	 * @param string $value Raw value.
	 *
	 * @return DateTimeImmutable|null
	 */
	private function parseDateTime(string $value): ?DateTimeImmutable {
		if ($value === '') {
			return null;
		}

		try {
			return new DateTimeImmutable($value);
		} catch (Exception) {
			return null;
		}

	}//end parseDateTime()
}//end class
