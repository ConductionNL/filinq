<?php

/**
 * Consent Scope Validator
 *
 * Service for validating publication consent records by scope,
 * enforcing transition rules on policy-matched records, and
 * applying the override-up flow for anonymization decisions.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-5
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-10
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\AppFramework\OCS\OCSForbiddenException;

/**
 * Validates scope-discriminated consent writes and policy-transition rules
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 */
class ConsentScopeValidator
{

    /**
     * The group name required for entity-scope admin operations.
     *
     * @var string
     */
    private const STANDING_CONSENT_ADMIN_GROUP = 'docudesk-standing-consent-admins';

    /**
     * Terminal consent status values (from publicationConsent schema enum).
     *
     * @var array<string>
     */
    private const TERMINAL_STATUSES = [
        'consent_given',
        'objection_received',
        'no_response',
        'anonymized',
    ];

    /**
     * Constructor for ConsentScopeValidator
     *
     * @param IGroupManager $groupManager Group manager interface for RBAC checks
     *
     * @return void
     */
    public function __construct(
        private readonly IGroupManager $groupManager
    ) {

    }//end __construct()

    /**
     * Validate a consent write operation against scope-discriminated rules
     *
     * @param array<string, mixed> $data The consent record data to validate
     *
     * @return void
     *
     * @throws \InvalidArgumentException When a scope-specific constraint is violated
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-5
     */
    public function validateWrite(array $data): void
    {
        $scope         = $data['scope'] ?? null;
        $documentId    = $data['documentId'] ?? null;
        $matchRules    = $data['matchRules'] ?? null;
        $consentMethod = $data['consentMethod'] ?? null;
        $policyMatch   = $data['policyMatch'] ?? null;

        if ($scope === 'document') {
            if ($documentId === null || $documentId === '') {
                throw new \InvalidArgumentException('documentId is required for scope: document records');
            }
        }

        if ($scope === 'entity') {
            if ($documentId !== null && $documentId !== '') {
                throw new \InvalidArgumentException('documentId must not be set on scope: entity records');
            }

            if ($matchRules === null || $matchRules === [] || $matchRules === '') {
                throw new \InvalidArgumentException('matchRules is required for scope: entity records');
            }

            if ($consentMethod === null || $consentMethod === '') {
                throw new \InvalidArgumentException('consentMethod is required for scope: entity records');
            }

            if ($policyMatch !== null && $policyMatch !== '') {
                throw new \InvalidArgumentException('policyMatch is only valid on scope: document records');
            }
        }

    }//end validateWrite()

    /**
     * Validate a status transition on an existing consent record
     *
     * @param array<string, mixed> $existing The existing consent record
     * @param array<string, mixed> $update   The proposed update data
     *
     * @return void
     *
     * @throws \InvalidArgumentException When a transition is blocked by policy-match rules
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
     */
    public function validateTransition(array $existing, array $update): void
    {
        $existingPolicyMatch   = $existing['policyMatch'] ?? null;
        $existingConsentStatus = $existing['consentStatus'] ?? null;
        $updateConsentStatus   = $update['consentStatus'] ?? null;

        // No policy match means no transition restriction applies.
        if ($existingPolicyMatch === null || $existingPolicyMatch === '') {
            return;
        }

        // No status change requested — always allowed.
        if ($updateConsentStatus === null || $updateConsentStatus === $existingConsentStatus) {
            return;
        }

        // Check whether the update is purely an override-up (publicationDecision=anonymize only).
        $isOverrideUp = $this->isOverrideUpRequest(update: $update);

        if ($isOverrideUp === true) {
            // Override-up is always permitted regardless of policy match.
            return;
        }

        // Policy-pre-empted records must not be transitioned to a different terminal status.
        if (in_array($updateConsentStatus, self::TERMINAL_STATUSES, true) === true) {
            throw new \InvalidArgumentException(
                'Cannot transition policy-pre-empted records to a different terminal status'
                .' (policyMatch: '.$existingPolicyMatch.')'
            );
        }

    }//end validateTransition()

    /**
     * Apply the override-up flow: set publicationDecision to anonymize
     *
     * @param array<string, mixed> $existing The existing consent record
     *
     * @return array<string, mixed> The modified consent record
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
     */
    public function applyOverrideUp(array $existing): array
    {
        $existing['publicationDecision'] = 'anonymize';
        return $existing;

    }//end applyOverrideUp()

    /**
     * Require the current user to be in the standing consent admin group
     *
     * @param IUser $user The current user
     *
     * @return void
     *
     * @throws OCSForbiddenException When user is not in the required group
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-10
     */
    public function requireStandingConsentAdminGroup(IUser $user): void
    {
        if ($this->isEntityScopeAdmin(user: $user) === false) {
            throw new OCSForbiddenException('Requires membership in docudesk-standing-consent-admins');
        }

    }//end requireStandingConsentAdminGroup()

    /**
     * Check if user is in the standing consent admin group
     *
     * @param IUser $user The user to check
     *
     * @return bool True if user is a standing consent admin
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-10
     */
    public function isEntityScopeAdmin(IUser $user): bool
    {
        return $this->groupManager->isInGroup(
            uid: $user->getUID(),
            gid: self::STANDING_CONSENT_ADMIN_GROUP
        );

    }//end isEntityScopeAdmin()

    /**
     * Determine whether an update request is purely an override-up operation
     *
     * @param array<string, mixed> $update The proposed update data
     *
     * @return bool True if the update only sets publicationDecision=anonymize
     */
    private function isOverrideUpRequest(array $update): bool
    {
        $publicationDecision = $update['publicationDecision'] ?? null;
        $hasStatusChange     = isset($update['consentStatus']);

        return $publicationDecision === 'anonymize' && $hasStatusChange === false;

    }//end isOverrideUpRequest()
}//end class
