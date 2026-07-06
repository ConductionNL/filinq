<?php

/**
 * DocuDesk Portal Contribution Provider
 *
 * DocuDesk's contribution to the shared Portaliq external portal (hydra
 * ADR-046, contribution contract v2.1). Portaliq — the ONE shared portal for
 * people WITHOUT Nextcloud accounts — discovers this class by convention FQCN
 * (`OCA\{Namespace}\Portal\PortalContributionProvider`) and duck-types it via
 * method_exists(), never instanceof. This class is therefore deliberately
 * PLAIN: no portaliq imports, no `implements` clause, no info.xml dependency,
 * no constructor dependencies. Without portaliq installed it is inert and
 * DocuDesk behaves exactly as before (amendment A1).
 *
 * It declares — for the `data-subject` (a WOO-affected entity exercising their
 * consent/objection rights) and `signer` (an external party invited to sign a
 * document) audiences — the OpenRegister collections a portal subject may read
 * and the field whitelists projected onto each. All scoping uses the STABLE
 * claim contract `claims.docudesk.contactId` / `claims.docudesk.signerEmail`;
 * see openspec/changes/portal-contribution/design.md. No create/endpoint
 * actions ship in this wave (see design.md "Deferred actions"): consent
 * objection and document signing are UPDATE / A6 flows the create-action
 * vocabulary cannot express safely, and are deferred to a follow-up.
 *
 * @category  Portal
 * @package   OCA\DocuDesk\Portal
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Portal;

/**
 * Declares what an external portal subject may see in DocuDesk.
 *
 * The contribution is a declarative manifest (pure data — no I/O, no
 * callbacks). All subject identity (subjectRef, audience, organisation, trust)
 * is derived server-side by portaliq's auth edge and MUST never be trusted
 * from the client (ADR-005). Scoping uses server-managed portalAccount claims
 * (a contact-record reference for the data subject, the invited signer email
 * for the signer) — never Nextcloud user ids, because externals have no
 * Nextcloud account by premise (amendment A4).
 *
 * Every read collection ships an explicit `fields` whitelist so portaliq (which
 * whitelist-projects rows AFTER per-row verification — identifiers always
 * survive, a malformed declaration degrades to identifiers-only) never hands a
 * subject a staff-only or other-party column. The `signerSigningRequests`
 * collection uses the contract-v2.1 one-hop `via` join (A5): it routes through
 * the subject's own `signerRecord` rows to reach the parent `signingRequest`,
 * because `signingRequest` carries no direct subject-scope property. Rationale +
 * whitelist tables: openspec/changes/portal-contribution/design.md.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */
class PortalContributionProvider
{
    /**
     * The OpenRegister register slug holding the consent surfaces.
     *
     * @var string
     */
    private const REGISTER_CONSENT = 'consent';

    /**
     * The OpenRegister register slug holding the signing surfaces.
     *
     * @var string
     */
    private const REGISTER_SIGNING = 'signing';

    /**
     * The human label portaliq renders for this app's portal section.
     *
     * @var string
     */
    private const LABEL = 'DocuDesk';

    /**
     * The audiences this provider contributes to (contract v2, preferred).
     *
     * The registry probes for this method first. DocuDesk serves WOO-affected
     * data subjects (`data-subject`) and external document signers (`signer`).
     *
     * @return array<int, string> The audience identifiers.
     *
     * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
     */
    public function getAudiences(): array
    {
        return ['data-subject', 'signer'];

    }//end getAudiences()

    /**
     * The primary audience this provider contributes to (contract v1 fallback).
     *
     * Kept alongside getAudiences() so the provider also works against a v1
     * registry that predates multi-audience support.
     *
     * @return string The primary audience identifier.
     *
     * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
     */
    public function getAudience(): string
    {
        return 'data-subject';

    }//end getAudience()

    /**
     * Build the declarative portal manifest for one resolved subject.
     *
     * The subject array is server-derived by portaliq (subjectRef UUID,
     * audience, organisation, trust level low|substantial|high). Returns null
     * for any audience DocuDesk does not serve (fail-closed; the registry
     * already filters by audience, but a provider must not rely on that). This
     * wave declares read collections only — no create or endpoint actions.
     *
     * @param array<string, mixed> $subject The resolved portal subject.
     *
     * @return array<string, mixed>|null The manifest, or null when not contributing.
     *
     * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
     */
    public function getContribution(array $subject): ?array
    {
        $audience = ($subject['audience'] ?? '');

        if ($audience === 'data-subject') {
            return $this->dataSubjectContribution();
        }

        if ($audience === 'signer') {
            return $this->signerContribution();
        }

        return null;

    }//end getContribution()

    /**
     * Manifest for the `data-subject` audience (a WOO-affected entity).
     *
     * Scoped by `publicationConsent.contactRef` — the linkage pointer to the
     * canonical Nextcloud Contact record — via the `contactId` claim. The
     * PII-in-clear `contactEmail` is deliberately NOT used as the scope: a
     * cleartext email is a weaker identity binding than the contact reference
     * (design.md "Claim-names contract"). `minTrust: substantial` because a
     * consent/objection case file carries the subject's own GDPR/WOO rights
     * data (mirrors the Wave-1 avgVerzoek gating). The field whitelist projects
     * only subject-safe transparency + objection-rights columns; every internal
     * detection key, notification-delivery internal, staff note, matching rule
     * and the polymorphic prohibition linkage is dropped (design.md exclusions).
     *
     * @return array<string, mixed> The data-subject manifest.
     *
     * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
     */
    private function dataSubjectContribution(): array
    {
        return [
            'label'         => self::LABEL,
            'collections'   => [
                [
                    'id'         => 'subjectConsents',
                    'register'   => self::REGISTER_CONSENT,
                    'schema'     => 'publicationConsent',
                    'scopeField' => 'contactRef',
                    'scopeClaim' => 'contactId',
                    'label'      => 'My publication consents',
                    'listable'   => true,
                    'minTrust'   => 'substantial',
                    'fields'     => [
                        'scope',
                        'consentStatus',
                        'objectionDeadline',
                        'objectionReceivedAt',
                        'objectionReason',
                        'publicationDecision',
                        'legalBasis',
                        'validFrom',
                        'validUntil',
                        'consentScope',
                        'consentMethod',
                        'active',
                    ],
                ],
            ],
            'actions'       => [],
            'notifications' => [],
        ];

    }//end dataSubjectContribution()

    /**
     * Manifest for the `signer` audience (an external document signer).
     *
     * `signerRecord` is scoped directly by the invited `email` via the
     * `signerEmail` claim: the record carries no external-contact UUID, and its
     * `userId` is a Nextcloud account id — unusable for accountless externals
     * (amendment A4) — so the verified invitation email is the only stable
     * subject key (design.md documents this PII-in-clear scope choice). Its
     * whitelist exposes only the signer's own participation facts; the base64
     * `signatureData` (schema `visible:false`), captured `ipAddress`, internal
     * `userId` and parent linkage are dropped.
     *
     * `signerSigningRequests` reaches the parent `signingRequest` through the
     * one-hop `via` join over the subject's own `signerRecord` rows
     * (targetField `signingRequestId`); it is gated at `substantial` because it
     * reveals which binding documents await the subject's signature, and its
     * whitelist drops the initiator's identity (`initiatorUserId`), the full
     * co-signer roster (`signerIds`) and the internal Nextcloud `documentFileId`
     * — every other-party and system-internal column.
     *
     * @return array<string, mixed> The signer manifest.
     *
     * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
     */
    private function signerContribution(): array
    {
        return [
            'label'         => self::LABEL,
            'collections'   => [
                [
                    'id'         => 'signerRecords',
                    'register'   => self::REGISTER_SIGNING,
                    'schema'     => 'signerRecord',
                    'scopeField' => 'email',
                    'scopeClaim' => 'signerEmail',
                    'label'      => 'My signatures',
                    'listable'   => true,
                    'fields'     => [
                        'displayName',
                        'status',
                        'order',
                        'signedAt',
                        'declineReason',
                    ],
                ],
                [
                    'id'         => 'signerSigningRequests',
                    'register'   => self::REGISTER_SIGNING,
                    'schema'     => 'signingRequest',
                    'scopeField' => '',
                    'scopeClaim' => 'signerEmail',
                    'via'        => [
                        'register'    => self::REGISTER_SIGNING,
                        'schema'      => 'signerRecord',
                        'scopeField'  => 'email',
                        'targetField' => 'signingRequestId',
                    ],
                    'label'      => 'Documents awaiting my signature',
                    'listable'   => true,
                    'minTrust'   => 'substantial',
                    'fields'     => [
                        'documentName',
                        'signatureLevel',
                        'signingMode',
                        'status',
                        'deadline',
                        'provider',
                    ],
                ],
            ],
            'actions'       => [],
            'notifications' => [],
        ];

    }//end signerContribution()
}//end class
