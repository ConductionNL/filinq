<?php

/**
 * DocuDesk Portal Contribution Provider
 *
 * DocuDesk's contribution to the shared Portaliq external portal (hydra
 * ADR-046, contribution contract v2.1, extended by v2.2 `rowActions`).
 * Portaliq — the ONE shared portal for people WITHOUT Nextcloud accounts —
 * discovers this class by convention FQCN
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
 * actions ship at the manifest top level (see design.md "Deferred actions"):
 * consent objection and document signing are UPDATE / A6 flows the
 * create-action vocabulary cannot express safely, and remain deferred.
 *
 * `portal-signing-surface` (this file, extended) adds the ONE additive seam
 * that IS safe to ship without the A6 receiver: contract-v2.2 `rowActions`
 * (`sign`, `decline`) declared directly on the `signerSigningRequests`
 * collection — pure data, no I/O, no new imports. The endpoints they name
 * (`/apps/docudesk/api/portal/signing/{sign,decline}`) are the ones
 * `portal-signing-actions`'s receiver is designed to expose; AT HEAD that
 * receiver, its `PortalAssertionVerifier`, and `signing-trust-rebuild`'s
 * identity-bound `v: 2` assertion MAC are NOT YET IMPLEMENTED (spec-only, 0 of
 * their tasks checked, no controller/route/verifier code in this repo). The
 * rowActions are declared here anyway (forward-compatible, dead until the
 * receiver ships — calling them 404s today) so the manifest and the future
 * receiver land in sync; the portal-subject identity binding into the
 * signature evidence (REQ-DDPSS-004) is intentionally NOT implemented in this
 * class or in `NativeSigningProvider`/`SigningVerificationService` because
 * doing so before `signing-trust-rebuild`'s MAC rebuild lands would silently
 * decorate the assertion with unenforced fields — the exact forgeable-signer
 * shape (portaliq#3) this surface exists to close, not a fix for it. See
 * `openspec/changes/portal-signing-surface/design.md` "Open questions" and the
 * apply-time PR for the full dependency-state note.
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
 * @spec openspec/specs/portal-signing-surface/spec.md
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
     * Instance-local relative endpoint the `sign` rowAction resolves to.
     *
     * Targets the `portal-signing-actions` receiver's `signDocument` act
     * (design.md "The identity chain"). NOT YET implemented at HEAD (see this
     * class's docblock) — declared here so the manifest and the future
     * receiver ship in sync.
     *
     * @var string
     */
    private const SIGN_ENDPOINT = '/apps/docudesk/api/portal/signing/sign';

    /**
     * Instance-local relative endpoint the `decline` rowAction resolves to.
     *
     * Targets the `portal-signing-actions` receiver's `declineDocument` act.
     * NOT YET implemented at HEAD — see `SIGN_ENDPOINT`.
     *
     * @var string
     */
    private const DECLINE_ENDPOINT = '/apps/docudesk/api/portal/signing/decline';

    /**
     * Minimum eIDAS-aligned portal trust required to sign or decline.
     *
     * An advanced-electronic-signature-grade act requires a
     * substantial-assurance portal session (design.md "eIDAS levels"). This
     * surface exposes SES/AES assurance only and never claims QES
     * (qualified electronic signature, eIDAS Article 3(12)) — QES is
     * certificate-backed via an external QTSP and is explicitly out of scope
     * (REQ-DDPSS-005); the exposed assurance MUST NOT exceed this session
     * trust.
     *
     * @var string
     */
    private const SIGNING_MIN_TRUST = 'substantial';

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
     * `signerSigningRequests` additionally carries contract-v2.2 `rowActions`
     * — `sign` and `decline` (REQ-DDPSS-001) — so portaliq renders a
     * per-document sign/decline control on exactly the rows awaiting the
     * subject. Each rowAction is gated `minTrust: substantial`
     * (eIDAS-aligned: an AES-grade act needs a substantial-assurance
     * session — `SIGNING_MIN_TRUST`) and resolves to an instance-local
     * relative endpoint (`SIGN_ENDPOINT` / `DECLINE_ENDPOINT`). This surface
     * exposes SES/AES assurance only; QES (qualified electronic signature,
     * certificate-backed via an external QTSP, eIDAS Article 3(12)) is
     * delegated and never claimed here (REQ-DDPSS-005). The rowActions are
     * pure data — no I/O, no callbacks — keeping this class plain and
     * dependency-free; the `signerRecords` collection and the entire
     * `data-subject` manifest carry no write action. The endpoints they name
     * target the `portal-signing-actions` receiver, which is NOT YET
     * implemented at HEAD (see this file's top-of-file docblock) — a row
     * already in a terminal state (`signed`/`declined`) must not offer the
     * actions, which is the receiver's terminal-state guard to enforce once
     * it ships, not something this pure-data manifest can express.
     *
     * @return array<string, mixed> The signer manifest.
     *
     * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
     * @spec openspec/specs/portal-signing-surface/spec.md
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
                    'rowActions' => [
                        [
                            'id'       => 'sign',
                            'label'    => 'Sign',
                            'endpoint' => self::SIGN_ENDPOINT,
                            'method'   => 'POST',
                            'minTrust' => self::SIGNING_MIN_TRUST,
                        ],
                        [
                            'id'       => 'decline',
                            'label'    => 'Decline to sign',
                            'endpoint' => self::DECLINE_ENDPOINT,
                            'method'   => 'POST',
                            'minTrust' => self::SIGNING_MIN_TRUST,
                        ],
                    ],
                ],
            ],
            'actions'       => [],
            'notifications' => [],
        ];

    }//end signerContribution()
}//end class
