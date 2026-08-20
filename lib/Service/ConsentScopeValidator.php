<?php

/**
 * Consent-scope validator
 *
 * Enforces the per-`scope` field-set contract on `publicationConsent`
 * writes (per `publication-consent-policy-fields` task 5):
 *
 *   - `scope=document` rows MUST carry `documentId`.
 *   - `scope=entity` rows MUST NOT carry `documentId` and MUST carry
 *     `matchRules` + `consentMethod`.
 *   - `scope=entity` rows MUST NOT carry `policyMatch` (only document-
 *     scope records are matched by the policy layer).
 *
 * The polymorphic-referent check (`policyMatch` may only point at a
 * `publicationProhibition` UUID or a `publicationConsent` UUID with
 * `scope=entity`) is enforced separately at the service layer because
 * it requires an OR lookup.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use InvalidArgumentException;
use OCP\IGroupManager;
use OCP\IUser;
use RuntimeException;

/**
 * Stateless validator for publicationConsent writes — service-layer
 * complement to the schema (the schema cannot express
 * "matchRules is required when scope=entity but forbidden when
 * scope=document").
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @spec openspec/changes/revive-dead-capabilities/tasks.md#task-2
 */
class ConsentScopeValidator {
	/**
	 * Group whose members may write scope=entity (standing-consent) records.
	 *
	 * Mirrored from PolicyCrudService to keep the validator self-contained.
	 */
	private const STANDING_CONSENT_GROUP = 'docudesk-standing-consent-admins';

	/**
	 * Constructor.
	 *
	 * @param IGroupManager $groupManager Group manager for RBAC checks.
	 */
	public function __construct(
		private readonly IGroupManager $groupManager,
	) {

	}//end __construct()

	/**
	 * Assert that the given user is a member of the standing-consent admin group
	 * (or is a Nextcloud admin, who implicitly bypasses all group gates).
	 *
	 * Called by ConsentService before any scope=entity write operation.
	 *
	 * @param IUser $user The acting user.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the user lacks the required group membership.
	 *
	 * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
	 */
	public function requireStandingConsentAdminGroup(IUser $user): void {
		if ($this->groupManager->isAdmin($user->getUID()) === true) {
			return;
		}

		if ($this->groupManager->isInGroup($user->getUID(), self::STANDING_CONSENT_GROUP) === true) {
			return;
		}

		throw new RuntimeException(
			sprintf(
				'Standing-consent write requires membership in the "%s" group.',
				self::STANDING_CONSENT_GROUP
			)
		);

	}//end requireStandingConsentAdminGroup()

	/**
	 * Validate a state transition on an existing publicationConsent record.
	 *
	 * Ensures that the update payload does not violate any transition rules
	 * relative to the persisted record. Currently enforces:
	 *   - The `scope` field MUST NOT change once set.
	 *   - The updated record (merged) must satisfy the same scope contract as
	 *     assertValid().
	 *
	 * @param array<string, mixed> $existing The persisted record.
	 * @param array<string, mixed> $update The incoming update payload.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the transition is not allowed.
	 *
	 * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
	 */
	public function validateTransition(array $existing, array $update): void {
		$existingScope = (string)($existing['scope'] ?? 'document');
		$updateScope = (string)($update['scope'] ?? $existingScope);

		if ($existingScope !== $updateScope) {
			throw new InvalidArgumentException(
				sprintf(
					'publicationConsent.scope cannot change from "%s" to "%s" after creation.',
					$existingScope,
					$updateScope
				)
			);
		}

		// Validate the merged record against the scope contract.
		$merged = array_merge($existing, $update);
		$merged['scope'] = $existingScope;
		$this->assertValid(consent: $merged);

	}//end validateTransition()

	/**
	 * Validate a candidate publicationConsent write.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When the scope contract is violated.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md
	 */
	public function assertValid(array $consent): void {
		$scope = (string)($consent['scope'] ?? 'document');

		if ($scope !== 'document' && $scope !== 'entity') {
			throw new InvalidArgumentException(
				'publicationConsent.scope must be "document" or "entity" (got "' . $scope . '")'
			);
		}

		if ($scope === 'document') {
			$this->assertDocumentScope(consent: $consent);
			return;
		}

		$this->assertEntityScope(consent: $consent);

	}//end assertValid()

	/**
	 * Enforce the document-scope contract.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When the document-scope contract is violated.
	 *
	 * @return void
	 */
	private function assertDocumentScope(array $consent): void {
		$documentId = (string)($consent['documentId'] ?? '');
		if ($documentId === '') {
			throw new InvalidArgumentException(
				'scope=document publicationConsent records require a non-empty documentId'
			);
		}

	}//end assertDocumentScope()

	/**
	 * Enforce the entity-scope contract.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When the entity-scope contract is violated.
	 *
	 * @return void
	 */
	private function assertEntityScope(array $consent): void {
		$this->assertEntityHasNoDocumentId(consent: $consent);
		$this->assertEntityMatchRules(consent: $consent);
		$this->assertEntityConsentMethod(consent: $consent);
		$this->assertEntityHasNoPolicyMatch(consent: $consent);

	}//end assertEntityScope()

	/**
	 * Enforce that an entity-scope record carries no documentId.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When a documentId is present.
	 *
	 * @return void
	 */
	private function assertEntityHasNoDocumentId(array $consent): void {
		$documentId = $consent['documentId'] ?? null;
		if ($documentId !== null && $documentId !== '') {
			throw new InvalidArgumentException(
				'scope=entity publicationConsent records MUST NOT carry a documentId'
			);
		}

	}//end assertEntityHasNoDocumentId()

	/**
	 * Enforce that an entity-scope record carries at least one well-formed matchRule.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When matchRules are missing or malformed.
	 *
	 * @return void
	 */
	private function assertEntityMatchRules(array $consent): void {
		$matchRules = $consent['matchRules'] ?? null;
		if (is_array($matchRules) === false || count($matchRules) === 0) {
			throw new InvalidArgumentException(
				'scope=entity publicationConsent records require at least one matchRule'
			);
		}

		foreach ($matchRules as $idx => $rule) {
			$this->assertEntityMatchRule(rule: $rule, idx: (string)$idx);
		}

	}//end assertEntityMatchRules()

	/**
	 * Enforce the shape and type vocabulary of a single matchRule.
	 *
	 * @param mixed $rule The candidate matchRule entry.
	 * @param string $idx The rule's index, used in the error message.
	 *
	 * @throws InvalidArgumentException When the rule is malformed or the type is unknown.
	 *
	 * @return void
	 */
	private function assertEntityMatchRule(mixed $rule, string $idx): void {
		if (is_array($rule) === false
			|| isset($rule['type'], $rule['value']) === false
		) {
			throw new InvalidArgumentException(
				'scope=entity publicationConsent matchRules[' . $idx . '] must be a {type, value} object'
			);
		}

		$type = (string)$rule['type'];
		if (in_array($type, ['exact', 'normalized', 'bsn', 'kvk'], true) === false) {
			throw new InvalidArgumentException(
				'scope=entity publicationConsent matchRules[' . $idx . '].type must be one of exact|normalized|bsn|kvk (got "' . $type . '")'
			);
		}

	}//end assertEntityMatchRule()

	/**
	 * Enforce that an entity-scope record declares a recognised consentMethod.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When the consentMethod is missing or unknown.
	 *
	 * @return void
	 */
	private function assertEntityConsentMethod(array $consent): void {
		$consentMethod = (string)($consent['consentMethod'] ?? '');
		if (in_array(
			$consentMethod,
			['paper', 'digital_signature', 'verbal_recorded', 'opt_in_form'],
			true
		) === false
		) {
			throw new InvalidArgumentException(
				'scope=entity publicationConsent records require consentMethod ∈ {paper, digital_signature, verbal_recorded, opt_in_form}'
			);
		}

	}//end assertEntityConsentMethod()

	/**
	 * Enforce that an entity-scope record carries no policyMatch.
	 *
	 * Standing-consent rows are never themselves matched — they are
	 * referenced from scope=document rows via policyMatch.
	 *
	 * @param array<string, mixed> $consent Candidate publicationConsent record.
	 *
	 * @throws InvalidArgumentException When a policyMatch is present.
	 *
	 * @return void
	 */
	private function assertEntityHasNoPolicyMatch(array $consent): void {
		if (isset($consent['policyMatch']) === true
			&& $consent['policyMatch'] !== null
			&& $consent['policyMatch'] !== ''
		) {
			throw new InvalidArgumentException(
				'scope=entity publicationConsent records MUST NOT carry policyMatch (only scope=document records are policy-matched)'
			);
		}

	}//end assertEntityHasNoPolicyMatch()
}//end class
