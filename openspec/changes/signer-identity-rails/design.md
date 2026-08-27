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
credential broker (same interim `ICrypto` local-custody mode behind the same
interface as `document-waarmerk-certification` task 2.1 — reuse that resolver,
ADR-011). Secret never in a register schema, never logged, never echoed to the
frontend.

### D5 — EUDI readiness = a conformance contract, not a stub

The orphaned-capability trap (fleet lesson: implemented + spec'd + green but
nothing invokes it) is avoided by: (a) the seam ships with TWO live providers
(`nextcloud-session` default; `oidc-broker` exercised e2e against a test OIDC
IdP in CI), so every interface method has a real caller; (b) EUDI readiness is
expressed as a documented conformance suite (`SignerAuthProviderContractTest`,
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
