<?php

/**
 * Filinq Portal Contribution Provider
 *
 * Filinq's contribution to the shared Portaliq external portal (hydra
 * ADR-046, contribution contract v2.1, extended by v2.2 `rowActions`).
 * Portaliq — the ONE shared portal for people WITHOUT Nextcloud accounts —
 * discovers this class by convention FQCN
 * (`OCA\{Namespace}\Portal\PortalContributionProvider`) and duck-types it via
 * method_exists(), never instanceof. This class is therefore deliberately
 * PLAIN: no portaliq imports, no `implements` clause, no info.xml dependency,
 * no constructor dependencies. Without portaliq installed it is inert and
 * Filinq behaves exactly as before (amendment A1).
 *
 * It declares — for the `data-subject` (a WOO-affected entity exercising their
 * consent/objection rights) and `signer` (an external party invited to sign a
 * document) audiences — the OpenRegister collections a portal subject may read
 * and the field whitelists projected onto each. All scoping uses the STABLE
 * claim contract `claims.filinq.contactId` / `claims.filinq.signerEmail`;
 * see openspec/changes/portal-contribution/design.md. No create/endpoint
 * actions ship at the manifest top level (see design.md "Deferred actions"):
 * consent objection and document signing are UPDATE / A6 flows the
 * create-action vocabulary cannot express safely, and remain deferred.
 *
 * `portal-signing-surface` (this file, extended) adds contract-v2.2
 * `rowActions` (`sign`, `decline`) declared directly on the
 * `signerSigningRequests` collection — pure data, no I/O, no new imports.
 * `portal-signing-actions` (this file, further extended) adds the SAME three
 * acts as top-level contract-v2 A6 `endpoint` actions (`sign`, `decline`,
 * `viewDocument`, REQ-DDPSA-001) — both point at
 * `/apps/filinq/api/portal/signing/{sign,decline,viewDocument}`, now backed
 * by a real receiver: `Controller\PortalSigningReceiverController` +
 * `Portal\PortalAssertionVerifier` validate portaliq's frozen
 * `X-Portal-Subject` assertion, derive the invited signer's identity
 * server-side, and drive the honest `SigningService::sign()`/`decline()`
 * primitive via its verified-actor entrypoint. `signing-trust-rebuild`'s
 * identity-bound `v: 2` assertion MAC (`NativeSigningProvider`,
 * `SigningVerificationService`) is implemented alongside it, and the
 * portal-subject identity claims (`subjectRef`/`identityRef`/`trust`/`jti`)
 * are folded into that SAME MAC for a portal-originated signature
 * (REQ-DDPSS-004), closing the portaliq#3 forgeable-signer class for the
 * portal seam too. The one remaining apply-blocker (design.md "Open
 * questions"): portaliq's frozen A6 wire format does not yet forward a
 * resolved `signerEmail` scope claim, so the receiver fails closed (403) on
 * every act until that lands — safe to ship early by design.
 *
 * @category  Portal
 * @package   OCA\Filinq\Portal
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 * @spec openspec/specs/portal-signing-actions/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Portal;

/**
 * Declares what an external portal subject may see in Filinq.
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
class PortalContributionProvider {
	/**
	 * The OpenRegister register slug holding the consent surfaces.
	 *
	 * `filinq`, not `consent`: this app declares ONE register holding all 23
	 * schemas. The five it used to declare are retired.
	 *
	 * @var string
	 */
	private const REGISTER_CONSENT = 'filinq';

	/**
	 * The OpenRegister register slug holding the signing surfaces.
	 *
	 * `filinq`, not `signing`, for the same reason. The two constants now hold
	 * the same value and are deliberately NOT collapsed into one: they name two
	 * different portal surfaces, and folding them together would lose the
	 * record of which surface each call site is addressing the moment anything
	 * ever moves again.
	 *
	 * @var string
	 */
	private const REGISTER_SIGNING = 'filinq';

	/**
	 * The human label portaliq renders for this app's portal section.
	 *
	 * @var string
	 */
	private const LABEL = 'Filinq';

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
	private const SIGN_ENDPOINT = '/apps/filinq/api/portal/signing/sign';

	/**
	 * Instance-local relative endpoint the `decline` rowAction resolves to.
	 *
	 * Targets the `portal-signing-actions` receiver's `declineDocument` act.
	 * NOT YET implemented at HEAD — see `SIGN_ENDPOINT`.
	 *
	 * @var string
	 */
	private const DECLINE_ENDPOINT = '/apps/filinq/api/portal/signing/decline';

	/**
	 * Instance-local relative endpoint the `viewDocument` A6 action resolves to.
	 *
	 * Targets the `portal-signing-actions` receiver's `viewDocument` act
	 * (REQ-DDPSA-006) — lets the verified invited signer read the target
	 * document before signing.
	 *
	 * @var string
	 */
	private const VIEW_DOCUMENT_ENDPOINT = '/apps/filinq/api/portal/signing/viewDocument';

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
	 * The registry probes for this method first. Filinq serves WOO-affected
	 * data subjects (`data-subject`) and external document signers (`signer`).
	 *
	 * @return array<int, string> The audience identifiers.
	 *
	 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
	 */
	public function getAudiences(): array {
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
	public function getAudience(): string {
		return 'data-subject';
	}//end getAudience()

	/**
	 * Build the declarative portal manifest for one resolved subject.
	 *
	 * The subject array is server-derived by portaliq (subjectRef UUID,
	 * audience, organisation, trust level low|substantial|high). Returns null
	 * for any audience Filinq does not serve (fail-closed; the registry
	 * already filters by audience, but a provider must not rely on that). This
	 * wave declares read collections only — no create or endpoint actions.
	 *
	 * @param array<string, mixed> $subject The resolved portal subject.
	 *
	 * @return array<string, mixed>|null The manifest, or null when not contributing.
	 *
	 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
	 */
	public function getContribution(array $subject): ?array {
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
	private function dataSubjectContribution(): array {
		return [
			'label' => self::LABEL,
			'collections' => [
				[
					'id' => 'subjectConsents',
					'register' => self::REGISTER_CONSENT,
					'schema' => 'publicationConsent',
					'scopeField' => 'contactRef',
					'scopeClaim' => 'contactId',
					'label' => 'My publication consents',
					'listable' => true,
					'minTrust' => 'substantial',
					'fields' => [
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
			'actions' => [],
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
	 * target the `portal-signing-actions` receiver
	 * (`PortalSigningReceiverController`), now implemented — a row already
	 * in a terminal state (`signed`/`declined`) must not offer the actions,
	 * which is the receiver's terminal-state guard (via the honest
	 * `SigningService` status machine) to enforce, not something this
	 * pure-data manifest can express.
	 *
	 * The manifest's top-level `actions` array additionally declares the
	 * SAME three acts as contract-v2 A6 `endpoint`-type actions — `sign`,
	 * `decline`, `viewDocument` (REQ-DDPSA-001) — for the A6
	 * `POST /portal/api/actions/{appId}/{actionId}` forward mechanism, which
	 * is a distinct rendering path from the per-row `rowActions` above (both
	 * ultimately hit the SAME `PortalSigningReceiverController` endpoints).
	 * `viewDocument` (GET, REQ-DDPSA-006) has no rowAction equivalent — it
	 * lets the signer read the target document before deciding to sign or
	 * decline.
	 *
	 * @return array<string, mixed> The signer manifest.
	 *
	 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 */
	private function signerContribution(): array {
		return [
			'label' => self::LABEL,
			'collections' => [
				[
					'id' => 'signerRecords',
					'register' => self::REGISTER_SIGNING,
					'schema' => 'signerRecord',
					'scopeField' => 'email',
					'scopeClaim' => 'signerEmail',
					'label' => 'My signatures',
					'listable' => true,
					'fields' => [
						'displayName',
						'status',
						'order',
						'signedAt',
						'declineReason',
					],
				],
				[
					'id' => 'signerSigningRequests',
					'register' => self::REGISTER_SIGNING,
					'schema' => 'signingRequest',
					'scopeField' => '',
					'scopeClaim' => 'signerEmail',
					'via' => [
						'register' => self::REGISTER_SIGNING,
						'schema' => 'signerRecord',
						'scopeField' => 'email',
						'targetField' => 'signingRequestId',
					],
					'label' => 'Documents awaiting my signature',
					'listable' => true,
					'minTrust' => 'substantial',
					'fields' => [
						'documentName',
						'signatureLevel',
						'signingMode',
						'status',
						'deadline',
						'provider',
					],
					'rowActions' => [
						[
							'id' => 'sign',
							'label' => 'Sign',
							'endpoint' => self::SIGN_ENDPOINT,
							'method' => 'POST',
							'minTrust' => self::SIGNING_MIN_TRUST,
						],
						[
							'id' => 'decline',
							'label' => 'Decline to sign',
							'endpoint' => self::DECLINE_ENDPOINT,
							'method' => 'POST',
							'minTrust' => self::SIGNING_MIN_TRUST,
						],
					],
				],
			],
			// Contract-v2 A6 endpoint-forward actions (REQ-DDPSA-001): the
			// ONLY three actions on the `signer` manifest, all instance-local
			// relative endpoints under `/apps/filinq/api/portal/signing/`,
			// all gated `minTrust: substantial`. Handled by
			// `PortalSigningReceiverController` +
			// `Portal\PortalAssertionVerifier`.
			'actions' => [
				[
					'id' => 'sign',
					'label' => 'Sign',
					'endpoint' => self::SIGN_ENDPOINT,
					'method' => 'POST',
					'minTrust' => self::SIGNING_MIN_TRUST,
				],
				[
					'id' => 'decline',
					'label' => 'Decline to sign',
					'endpoint' => self::DECLINE_ENDPOINT,
					'method' => 'POST',
					'minTrust' => self::SIGNING_MIN_TRUST,
				],
				[
					'id' => 'viewDocument',
					'label' => 'View document',
					'endpoint' => self::VIEW_DOCUMENT_ENDPOINT,
					'method' => 'GET',
					'minTrust' => self::SIGNING_MIN_TRUST,
				],
			],
			'notifications' => [],
		];

	}//end signerContribution()
}//end class
