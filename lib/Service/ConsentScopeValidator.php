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
 */
final class ConsentScopeValidator
{


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
    public function assertValid(array $consent): void
    {
        $scope = (string) ($consent['scope'] ?? 'document');

        if ($scope !== 'document' && $scope !== 'entity') {
            throw new InvalidArgumentException(
                'publicationConsent.scope must be "document" or "entity" (got "'.$scope.'")'
            );
        }

        if ($scope === 'document') {
            $this->assertDocumentScope($consent);
            return;
        }

        $this->assertEntityScope($consent);

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
    private function assertDocumentScope(array $consent): void
    {
        $documentId = (string) ($consent['documentId'] ?? '');
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
    private function assertEntityScope(array $consent): void
    {
        $documentId = $consent['documentId'] ?? null;
        if ($documentId !== null && $documentId !== '') {
            throw new InvalidArgumentException(
                'scope=entity publicationConsent records MUST NOT carry a documentId'
            );
        }

        $matchRules = $consent['matchRules'] ?? null;
        if (is_array($matchRules) === false || count($matchRules) === 0) {
            throw new InvalidArgumentException(
                'scope=entity publicationConsent records require at least one matchRule'
            );
        }

        foreach ($matchRules as $idx => $rule) {
            if (is_array($rule) === false
                || isset($rule['type'], $rule['value']) === false
            ) {
                throw new InvalidArgumentException(
                    'scope=entity publicationConsent matchRules['.(string) $idx.'] must be a {type, value} object'
                );
            }
            $type = (string) $rule['type'];
            if (in_array($type, ['exact', 'normalized', 'bsn', 'kvk'], true) === false) {
                throw new InvalidArgumentException(
                    'scope=entity publicationConsent matchRules['.(string) $idx.'].type must be one of exact|normalized|bsn|kvk (got "'.$type.'")'
                );
            }
        }

        $consentMethod = (string) ($consent['consentMethod'] ?? '');
        if (in_array(
            $consentMethod,
            ['paper', 'digital_signature', 'verbal_recorded', 'opt_in_form'],
            true
        ) === false) {
            throw new InvalidArgumentException(
                'scope=entity publicationConsent records require consentMethod ∈ {paper, digital_signature, verbal_recorded, opt_in_form}'
            );
        }

        // Standing-consent rows are never themselves matched — they are
        // referenced from scope=document rows via policyMatch.
        if (isset($consent['policyMatch']) === true
            && $consent['policyMatch'] !== null
            && $consent['policyMatch'] !== ''
        ) {
            throw new InvalidArgumentException(
                'scope=entity publicationConsent records MUST NOT carry policyMatch (only scope=document records are policy-matched)'
            );
        }

    }//end assertEntityScope()


}//end class
