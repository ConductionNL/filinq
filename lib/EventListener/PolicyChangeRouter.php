<?php

/**
 * Policy Change Router
 *
 * Routes a changed policy record to the retroactive policy layer.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

/**
 * Recognises prohibition and consent records by their shape and applies the matching retroactive policy step.
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
class PolicyChangeRouter {

	/**
	 * Route policy-surface mutations to the retroactive layer.
	 *
	 * Identifies the changed object as a `publicationProhibition` or a
	 * `publicationConsent` (scope=entity / scope=document) using payload-shape
	 * heuristics rather than schema-ID lookups, since:
	 *   - the event's `$object->getSchema()` returns a schema *ID* (numeric),
	 *     not a slug, so we would need an extra DB round-trip per event;
	 *   - the discriminating fields (`reason` + `legalAuthority` for
	 *     prohibitions; `scope` for consents) are stable across versions.
	 *
	 * For non-policy events this is a cheap no-op and returns immediately.
	 *
	 * @param ObjectChange $change The changed object's payload and the kind of change.
	 * @param ObjectEventServices $services The logger and the retroactive policy applicator.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function route(ObjectChange $change, ObjectEventServices $services): void {
		$objectData = $change->objectData;
		$reason = $change->reason;
		$logger = $services->logger;
		$retroactive = $services->retroactive;
		$shape = $this->detectPolicyShape(objectData: $objectData);
		if ($shape === null) {
			return;
		}

		try {
			if ($shape === 'prohibition') {
				if ($reason === 'deleted') {
					$retroactive->applyRuleRemoval();
					return;
				}

				$resolved = $retroactive->applyProhibitionMutation(prohibition: $objectData);
				if ($resolved > 0) {
					$logger->info(
						'Filinq: prohibition mutation force-resolved in-flight records',
						['resolved' => $resolved, 'reason' => $reason]
					);
				}

				return;
			}

			if ($shape === 'standing_consent') {
				if ($reason === 'deleted') {
					$retroactive->applyRuleRemoval();
					return;
				}

				$retroactive->applyStandingConsentMutation();
				return;
			}

			// Document_consent shape (workflow record): not a policy rule, no-op.
		} catch (\Exception $e) {
			$logger->warning(
				'Filinq: retroactive policy dispatch failed',
				['error' => $e->getMessage(), 'reason' => $reason]
			);
		}//end try

	}//end route()

	/**
	 * Classify a payload as a policy record by structural signature.
	 *
	 * @param array<string, mixed> $objectData The changed object's payload.
	 *
	 * @return string|null 'prohibition', 'standing_consent', 'document_consent', or null.
	 */
	private function detectPolicyShape(array $objectData): ?string {
		if ($this->looksLikeProhibition(objectData: $objectData) === true) {
			return 'prohibition';
		}

		if (isset($objectData['consentStatus']) === true) {
			return $this->detectConsentScope(objectData: $objectData);
		}

		return null;
	}//end detectPolicyShape()

	/**
	 * Structural test for a `publicationProhibition` payload.
	 *
	 * A prohibition is identified by the combination of `matchRules` plus
	 * `reason` or `legalAuthority`, and the absence of a `consentStatus` field
	 * (consent records always carry it).
	 *
	 * @param array<string, mixed> $objectData The changed object's payload.
	 *
	 * @return bool True when the payload is a prohibition record.
	 */
	private function looksLikeProhibition(array $objectData): bool {
		$hasMatchRules = isset($objectData['matchRules']) === true
			&& is_array($objectData['matchRules']) === true
			&& count($objectData['matchRules']) > 0;
		if ($hasMatchRules === false) {
			return false;
		}

		if (isset($objectData['consentStatus']) === true) {
			return false;
		}

		$hasReason = isset($objectData['reason']) === true && (string)$objectData['reason'] !== '';

		return $hasReason === true || isset($objectData['legalAuthority']) === true;
	}//end looksLikeProhibition()

	/**
	 * Classify a consent payload by its `scope` field.
	 *
	 * @param array<string, mixed> $objectData The changed object's payload.
	 *
	 * @return string 'standing_consent' for scope=entity, otherwise 'document_consent'.
	 */
	private function detectConsentScope(array $objectData): string {
		$scope = (string)($objectData['scope'] ?? 'document');
		if ($scope === 'entity') {
			return 'standing_consent';
		}

		return 'document_consent';
	}//end detectConsentScope()
}//end class
