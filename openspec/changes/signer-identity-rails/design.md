# Design: signer-identity-rails

## Context

Verified at HEAD: signer authentication is implicit — `SigningService::sign()`
accepts any authenticated NC session whose uid matches `signer.userId`
(the #282 fix from `signing-trust-rebuild`'s baseline). There is no notion of
HOW the session was established or at what assurance. The register `signing`
schemas carry no identity-evidence fields (`signerRecord`: signingRequestId,
userId, displayName, email, order, status, signedAt, declineReason, ipAddress,
signatureData; `signingRequest`: no `requiredAssurance`). The provider seam
that exists (`SigningProviderInterface`) is about artifact production, not
identity. Nextcloud's own login can front DigiD via `user_oidc` against a
broker, but that authenticates the *account*, not the *signature act*, and
carries no per-request assurance gating.

NL reality (evidence in proposal): government signing requires per-signature
identity rails — DigiD (citizens), eHerkenning (organisations), iDIN (bank
verification) — normally consumed through an OIDC broker (Signicat-style)
that normalises them to OIDC with `acr`/`amr` claims. eIDAS assurance maps:
DigiD Midden/Substantieel/Hoog → substantial/high; eHerkenning EH3/EH4 →
substantial/high; iDIN → substantial (bank-verified). eIDAS 2.0 adds EUDI
wallets (mandatory Dec 2026) with wallet-based QES.

Related work this change aligns with (not duplicates):
- `signing-trust-rebuild` (dependency): assertion v2 MAC covers identity
  fields; identity evidence recorded here becomes tamper-evident there.
- `portal-contribution` (shipped wave): external `signer` audience with
  portaliq trust levels (`low`/`substantial`) — the same eIDAS vocabulary;
  portaliq's auth edge is effectively a signer-authentication provider for
  accountless externals.
- `document-waarmerk-certification` (wave 1): ADR-064 credentialRef custody
  pattern for the org certificate — same custody model reused for broker
  secrets.
- `multi-tenant-hardening` (wave 2 sibling): per-organisation configuration
  model; broker config should be organisation-scopable once that lands
  (Open Questions).

## Goals / Non-Goals

**Goals**

1. A pluggable signer-authentication seam that any OIDC broker (and later a
   wallet verifier) can implement — vendor-neutral rails.
2. Per-request assurance gating: `requiredAssurance` declared at creation,
   enforced fail-closed at every sign/decline act.
3. Identity evidence persisted (signer record + OR audit + MAC-covered
   assertion) with strict data minimisation (pairwise pseudonym, never BSN).
4. ADR-064 custody for broker secrets.
5. EUDI-wallet readiness that is provable, not aspirational.

**Non-Goals**

- Direct Logius/PKIoverheid aansluitingen; QES artifact production; wallet
  verifier implementation; external-signer portal UX (see proposal Out of
  Scope).

## Decisions

### D1 — Separate seam: identity provider ≠ signing provider

`SignerAuthenticationProviderInterface` (new, `lib/Service/SignerAuth/`):

```php
getIdentifier(): string                       // 'nextcloud-session' | 'oidc-broker' | future 'eudi-wallet'
getSupportedMeans(): array                    // e.g. ['digid','eherkenning','idin'] or ['nc-session']
getSupportedAssurance(): array                // subset of ['low','substantial','high']
initiateAuthentication(SignerAuthContext): AuthChallenge   // e.g. OIDC authorize redirect URL, state bound to signerId+requestId
completeAuthentication(callbackData): IdentityEvidence     // validates OIDC code/ID-token (nonce, aud, iss, exp), maps acr→assurance
```

Rejected alternative: overloading `SigningProviderInterface` with auth
methods — mixes two lifecycles (a native-SES artifact with DigiD-substantial
identity is a legitimate combination; ValidSign bundles both, but the seam
must not force bundling). The two seams compose: identity gates the actor,
signing produces the artifact. Mirrors the existing factory pattern
(`SignerAuthProviderFactory`, strict resolution — no silent fallback, the
REQ-DDSTR-002 lesson applied from day one).

### D2 — Assurance model and mapping

Canonical enum `low | substantial | high` (eIDAS LoA). Mapping table
(maintained as provider config, defaults documented):

| Means | acr example | Assurance |
|---|---|---|
| NC session | — | low |
| DigiD Midden | `urn:...:digid:midden` | substantial |
| DigiD Substantieel | `urn:...:digid:substantieel` | substantial |
| DigiD Hoog | `urn:...:digid:hoog` | high |
| eHerkenning EH3 | `urn:etoegang:core:assurance-class:loa3` | substantial |
| eHerkenning EH4 | `urn:etoegang:core:assurance-class:loa4` | high |
| iDIN | broker-specific | substantial |
| EUDI wallet (target) | wallet PID/QES | high |

Signature-level policy (documented default, admin-overridable per level, never
downward past the floor): SES → `low` floor, AdES → `substantial` floor,
QES → `high` floor. `requiredAssurance` on a request may exceed the floor,
never go below it. An unknown/unmappable `acr` → assurance `low` (fail-closed
to the weakest, which then fails any substantial/high gate).

### D3 — Identity evidence: shape and minimisation

New object properties (register version bump, additive):

- `signingRequest.requiredAssurance` (enum, default = level floor).
- `signerRecord.identityEvidence` (object): `provider`, `means`, `assurance`,
  `subjectPseudonym` (the broker's pairwise/sector pseudonym or `sub` claim —
  NEVER BSN/KvK-embedded identifiers; providers MUST hash any non-pairwise
  subject with a per-instance salt), `authenticatedAt` (ISO 8601),
  `evidenceHash` (sha256 of the raw validated ID-token/assertion, the token
  itself is NOT stored).

The same tuple goes into the OR audit `changed` context (extends the existing
`signing-audit-via-or` context contract additively) and into the artifact
assertion (covered by the v2 MAC per REQ-DDSTR-001, making identity claims
tamper-evident end to end). Raw ID tokens are never persisted: the evidence
hash allows later dispute resolution against broker logs without Filinq
holding token PII.

### D4 — ADR-064 custody for broker secrets

Broker config in admin settings: issuer URL, client id, redirect URI, acr
mapping — all non-secret, stored via IAppConfig. The client secret is stored
ONLY as a `credentialRef` resolved at token-exchange time through the
credential broker. Secret never in a register schema, never logged, never
echoed to the frontend.

Amended at apply time (2026-09-28). The `document-waarmerk-certification`
resolver this decision first pointed at is unbuilt, and ADR-064 forbids an app
its own broker. `BrokerCredentialResolver` therefore calls OpenRegister's
`CredentialBrokerService::resolveInjectable($credentialRef, 'filinq')`,
looked up by class name so filinq still boots without it. An identity broker is
an arbitrary self-hosted host that the broker's host-locked proxy cannot serve,
which is exactly ADR-064's documented injection exception: the admin mints the
secret in OpenRegister on an `inject_only` provider with `filinq` allowed, and
enters the credential's UUID in filinq. The settings refuse anything that is
not a UUID. The secret is used by `OidcTokenExchange` for the one token request
and kept nowhere.

**OIDC mechanics as built.** State and nonce live in the signer's own
server-side session (`OidcPendingAuthentications`), bound to request, signer
and user, single use, ten minutes. The callback carries only `code` and
`state`, so the provider exposes `boundAct(state)` for the callback to learn
which act to complete. The ID token comes from a direct server-side TLS call to
an https token endpoint, so its claims are validated (`iss`, `aud`/`azp`,
`exp`, `iat`, `nonce`, `sub`) and its signature is not: OpenID Connect Core
section 3.1.3.7 allows TLS server validation in place of the signature check
for exactly this case. `sub` is always hashed with a key derived from the
instance secret (`SubjectPseudonymiser`), because filinq cannot tell a pairwise
`sub` from one that embeds a BSN. The authorize request sends `prompt=login`
and `acr_values` limited to the values that meet the required assurance; an old
`auth_time` makes the evidence stale at the gate. DigiD's defaults are the
Logius AuthnContextClassRefs, eHerkenning's the eToegang assurance classes;
iDIN has no standard acr, so its default key is a placeholder the admin
replaces with their broker's value.

### D5 — EUDI readiness = a conformance contract, not a stub

The orphaned-capability trap (fleet lesson: implemented + spec'd + green but
nothing invokes it) is avoided by: (a) the seam ships with TWO live providers
(`nextcloud-session` default; `oidc-broker` exercised e2e against a test OIDC
IdP in CI), so every interface method has a real caller; (b) EUDI readiness is
expressed as a documented conformance suite (`SignerAuthProviderContractTestCase`,
an abstract PHPUnit contract any provider must extend) + a readiness statement
in docs naming the Dec 2026 timeline; (c) NO `eudi-wallet` class ships — a
stub provider would be dead code. A future wallet plugin passes the contract
suite and registers; nothing else changes.

### D6 — Enforcement point

The assurance gate lives in `SigningService::sign()`/`decline()` (server-side,
after the #282 ownership check): the acting signer's current
`IdentityEvidence` must exist, be issued by a registered provider, be fresher
than a configurable max age (default 15 minutes for the signing act), and meet
`requiredAssurance`. UI drives step-up: the sign dialog calls
`initiateAuthentication` when the gate reports insufficient assurance. The
gate is fail-closed: absent/expired/insufficient evidence → 403 with a
step-up hint, nothing mutates.

### D7: Guardian consent for signers under the age of consent (amendment, D11)

Learniq round 1 decision D11 folds e-signature with parental consent into this
change. The design choices, in the order a reviewer will question them:

**Where the age comes from.** Filinq holds no person record. The consumer
knows the learner's birth date (`LearnerProfile.birthDate` in learniq) and
sends it on the signer entry. Filinq evaluates the age itself, at the moment of
the signer's own act, against the admin setting `signing_guardian_consent_age`
(default 16, UAVG article 5; unset, non-numeric or non-positive falls back to
16). The birth date is stored on the signer record with `visible: false`,
because the age must be evaluated at signing time and the request can sit open
for weeks. It never leaves that record: the consent basis carries the applied
age and the moment of evaluation, not the date. Rejected alternative: a
consumer-computed `minor: true` flag. It minimises one field but moves the age
rule, and the setting, out of filinq, so the configured age would be a lie.

**Per-request age.** `guardianConsentAge` on the request may raise the age and
never lower it, the same floor rule REQ-DDSIR-002 applies to
`requiredAssurance`. A POK needs 18, because a minor under civil law is anyone
under 18. The applied value is persisted on every request, so the record says
which age governed it.

**Who the guardian is.** A signer record with `role: guardian` and
`guardianForSignerId`. The consumer names it on the entry with `guardianFor`,
the `userId` or email of another entry in the same list; `createRequest()`
resolves that to the minor's signer record id after saving the records. A
guardian therefore goes through `SigningActorResolver` and
`loadAuthorisedSigner()` like every signer, and gets the REQ-DDSIR-003 gate for
free once task 3.1 lands. Rejected alternative: a separate guardian endpoint.
It would be a second identity path, which is exactly what "the same identity
rails" forbids.

**Co-sign or consent.** `guardianAct: co-sign` (default) makes the guardian a
party to the document, which is what an OPP asks of parents. `guardianAct:
consent` records the toestemming of article 1:234 BW: the guardian consents to
the minor's act against a `consentStatement` the consumer supplies, and is not
a party. Both acts run through the same `sign()` call; only the label in the
record differs. A standing consent given outside the request is out of scope
(proposal), because filinq cannot verify how another app identified the
guardian.

**Enforcement points**, in `lib/Service/Signing/GuardianConsentGuard.php`:

1. `createRequest()`: validate entries before anything is persisted. A
   `guardianFor` that resolves to nobody or to the guardian itself, a `consent`
   act without a statement, a malformed birth date, and a signer under the age
   at creation time with no guardian all throw with code 400.
2. `sign()`: after `loadAuthorisedSigner()` and the PENDING check, before any
   mutation. A minor with no guardian record pointing at them, a guardian under
   the age, and a guardian whose uid or email is the minor's all throw with
   code 403. The controller already honours the exception code.
3. `updateRequestStatus()`: before `SignedArtifactProducer::produce()`. The
   guard builds the consent basis from the loaded signer records and throws
   when a signer who signed under the age has no guardian who acted. The
   request then stays IN_PROGRESS, the same honest-completion rule the
   artifact gate already follows.

The guard is a required constructor dependency of `SigningService`, not a
nullable seam like `SigningMandateService`. A safety guard that silently does
nothing when unwired is the failure this fleet keeps finding, so an unwired
guard fails construction instead.

**The identity tuple.** At the act of a minor or a guardian the guard records
`actingIdentity: {provider, assurance, authenticatedAt}` on the signer's own
record. In-app: `nextcloud-session`, `low` (REQ-DDSIR-002 table). Portal:
`portaliq`, the verified assertion's `trust`, where an unknown trust becomes
`low`. When REQ-DDSIR-004 `identityEvidence` is on the record, the tuple is
copied from it. The portal subject reference is deliberately left out; the
artifact already binds it for the completing actor, and the consent basis does
not need a pseudonym to say who acted.

**The record.** `signingRequest.consentBasis` (array, written at completion)
and the same array in the native artifact assertion, before the MAC. The
verifier recomputes over the assertion minus `mac`, so a new field stays
verifiable with no verifier change. A request with no signer under the age
writes no `consentBasis` and its assertion gains no field.

**Schema additions** (additive, `signerRecord` 1.2.0 to 1.3.0,
`signingRequest` 1.4.0 to 1.5.0, register 8.16.0 to 8.17.0): on
`signerRecord`, `role`, `guardianForSignerId`, `guardianAct`,
`consentStatement`, `guardianRef`, `birthDate` (hidden) and `actingIdentity`;
on `signingRequest`, `guardianConsentAge` and `consentBasis`. The
`signingRequest` schema is deprecated in favour of OR task sequences
(`migrate-signing-to-or-tasks`); these fields are request data and travel with
it when that migration lands, the same as `requiredAssurance`.

## OpenRegister usage (ADR-001)

All persistence via OR ObjectService on the existing `signing` register:
additive properties `signingRequest.requiredAssurance`,
`signerRecord.identityEvidence` in `lib/Settings/filinq_register.json`
(register version bump for boot import; additive union — never drop existing
properties, diff against merge base per the union-merge lesson). Audit context
extension rides the existing `SigningAuditService::logEvent()` metadata
pass-through. No new registers; no Filinq-local tables.

## Seed Data

- Demo `signingRequest` `00000000-0000-0000-0000-00000000e001` (Demostad,
  SES, `requiredAssurance: substantial`) + `signerRecord` `…e002` carrying a
  fixture `identityEvidence` (`provider: oidc-broker`, `means: digid`,
  `assurance: substantial`, `subjectPseudonym:
  'demo-pseudonym-not-a-bsn-0001'`, evidenceHash of the string `fixture`).
- CI e2e uses a throwaway OIDC IdP container (e.g. mock-oidc) with acr values
  from the D2 table; client secret injected as a runtime credentialRef — no
  secret fixture committed.

## Security Considerations

- Fail-closed assurance gate (absent/expired/unmapped ⇒ refuse); no silent
  provider fallback (strict factory resolution).
- OIDC hygiene: `state` bound to signerId+requestId (CSRF), `nonce` verified,
  `aud`/`iss`/`exp` validated, token exchange server-side only.
- Data minimisation (AVG Art. 5(1)(c)): pairwise pseudonym only; raw tokens
  never persisted; BSN never stored or logged — test-asserted.
- ADR-064: secret custody via credentialRef; reviewer grep for secret
  material in schemas/config/logs.
- Identity evidence is tamper-evident end to end only in combination with
  REQ-DDSTR-001 (v2 MAC) — hence the hard `depends_on`.

## Risks / Trade-offs

- **Broker variance**: acr URIs differ per broker; mitigated by config-driven
  mapping with fail-closed default (`low`).
- **Step-up UX friction**: a 15-min evidence window forces re-auth on slow
  flows; configurable, default chosen to match the signing act's legal weight.
- **Schema additivity**: `identityEvidence` as one object property keeps the
  register diff small but makes per-field OR filtering harder; accepted —
  evidence is read whole, never queried by sub-field.
- **Dec 2026 wallet timeline slip**: readiness is a seam + contract, so slip
  costs nothing; being early costs only the documented conformance suite.

## Migration Plan

1. Register version bump with the two additive properties; boot import.
2. Existing requests without `requiredAssurance` default to the level floor
   (SES→low) at read time — no data migration.
3. Existing signer records without `identityEvidence`: signing acts after
   deploy create evidence; historical records stay evidence-less (readable,
   flagged in UI as "pre-rails signature").
4. `nextcloud-session` provider is the default → zero behaviour change until
   an admin raises assurance floors or configures the broker.

## Open Questions

- Per-organisation broker config (nine-gemeente shared instances will want
  per-tenant brokers): defer to `multi-tenant-hardening`'s per-organisation
  settings surface; the provider factory is written organisation-aware-ready
  (config lookup keyed by org once available).
- Should the portal (`portal-contribution` signer audience) step-up reuse the
  same `oidc-broker` provider server-side? portaliq owns the portal auth edge
  (ADR-046); alignment conversation filed with the portaliq team at apply
  time.
- Should a guardian be held to a higher assurance than the minor? Learniq
  already asks `substantial` of a parent signing an OPP
  (`LearningPlanSignatureGuard`). Today both acts meet the request's single
  `requiredAssurance`; a per-role floor would be a later amendment once task
  3.1 exists.
- Standing consents (proposal, out of scope): if learniq or portaliq later
  record a guardian's consent through filinq's rails, a reference to that act
  could satisfy REQ-DDSIR-008 for later requests.
