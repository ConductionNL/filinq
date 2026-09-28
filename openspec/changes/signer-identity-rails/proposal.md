---
kind: code
depends_on: [signing-trust-rebuild]
---

# Proposal: signer-identity-rails

## Why

NL signer identity rails are a hard blocker for government signing
(intelligence insight, verified): **ValidSign, Zynyo and Signhost (Entrust,
ex-Evidos) monopolize DigiD/eHerkenning/iDIN/BankID signer authentication —
effectively 100% of NL government signing** (evidence:
validsign.eu/branches/overheid, zynyo.com, ROC tender 408784; intelligence
rows: ValidSign "DigiD/eHerkenning/iDIN/BankID signer identity", Signhost
"Remote qualified signing service — Entrust RSS; iDIN/DigiD/eHerkenning/
biometrics/national eIDs"). Filinq today has only Native SES + a stubbed
ValidSign provider and **no identity-rail integration of any kind**: a signer
is authenticated purely by their Nextcloud session, and there is no way to
express "this signature requires DigiD at substantial" on a request.

The window is now: **eIDAS 2.0 (Reg. 2024/1183) makes EUDI wallets mandatory
in every member state by end of December 2026**, with wallet-based QES from a
phone and mandatory acceptance (incl. public services) by late 2027
(intelligence insights "eIDAS 2.0: EUDI wallets mandatory end-2026" and "EUDI
wallet QES: once-in-a-decade opening for an open-source signer — the first
OSS Nextcloud signer with wallet-based QES wins the reset identity landscape").
Market-gap feature `mg2026-signer-identity-rails`: "DigiD/eHerkenning/iDIN
signer authentication via broker + EUDI wallet QES readiness (Dec 2026)".

This change deliberately builds **rails, not a vendor integration**: a
pluggable signer-authentication provider seam (OIDC-broker style, the same
duck-typed plugin philosophy as `SigningProviderInterface`) with eIDAS
assurance-level mapping, identity evidence persisted on the signing request,
ADR-064 credential custody, and the EUDI wallet declared as a provider-plugin
target — so any broker (Signicat-style, municipal OIDC, or a future wallet
verifier) can plug in without Filinq re-architecture.

Depends on `signing-trust-rebuild`: identity evidence is only worth recording
once the artifact MAC binds identity fields (REQ-DDSTR-001) and the pipeline
is honest (REQ-DDSTR-002/003).

### Amendment, 2026-09-27: signers under the age of consent (decision D11)

Learniq round 1 planned a separate filinq change, `filinq-esignature-opp-consent`
(change-plan row: code, MUST, size L, feeds learniq findings 8.3 and L-new-4).
Ruben decided on 27 September (D11 in
`market-intelligence/learniq/_round1/compare/decisions.md`) that e-signature
with parental consent folds into this change instead: a guardian is one more
signer whose identity the rails must establish, so the consent rules are
requirements of the rails, not a second signing path.

The evidence sits in `market-intelligence/learniq/_round1/compare/findings.md`.
Row 8.3 (rung 1, tier A, MUST): "OPP: create within 6 weeks, evaluate, parent
signature". ParnasSys takes an OPP from concept to akkoord through
"Handelingsdeel > Ondertekenen", and Somtoday ships "Digitaal ondertekenen" of
OPPs. Every Dutch incumbent in the round signs an OPP digitally. Learniq's MBO
dataset review found the same gap on the praktijkovereenkomst: `PokSignature`
has no role for the parent who co-signs a minor's POK
(learniq `openspec/changes/segment-example-datasets-mbo/design.md`, finding 4).

Filinq has no guardian concept today. Its signer entry is `{userId,
displayName, email, order}`, with no role and no on-behalf-of link, and no age
rule exists anywhere in filinq, learniq or portaliq. The dedupe on 27 September
found three neighbouring requirements, none of which gates a signature on age:
learniq "Guardian consent gates a minor's subject-choice submission"
(school-structure), learniq "Parent co-signs are verified against the
learner's profile found on ncUserId" (learner-lookup-and-learnerrefs-fixes),
and portaliq "An activity MUST be able to require a guardian's consent,
recorded on the sign-up" (activity-parental-consent). The word "consent
record" already names Woo `publicationConsent` in filinq, so this amendment
speaks of a guardian's consent act instead.

## What Changes

- **`SignerAuthenticationProviderInterface`** — a new pluggable seam, distinct
  from the signing-provider seam: providers assert *who the signer is* (and at
  what assurance), signing providers produce *the artifact*. Ships with a
  built-in `nextcloud-session` provider (the current behaviour, assurance
  `low`) and a generic `oidc-broker` provider configurable against any
  OIDC-compliant identity broker exposing DigiD/eHerkenning/iDIN means.
- **Assurance model**: identity means map to eIDAS assurance
  (`low`/`substantial`/`high`), and a signing request declares
  `requiredAssurance` alongside `signatureLevel` (SES/AdES/QES). A signer
  authenticated below the required assurance cannot sign — fail-closed.
- **Identity evidence on the signing flow**: the authenticated identity
  (provider id, means, assurance, pairwise pseudonym, auth timestamp) is
  persisted on the signer record, carried into the OR audit entry context,
  and included in the artifact assertion fields covered by the v2 MAC.
  **Never BSN or other national identifiers in cleartext** — pairwise
  pseudonym only (AVG data minimisation).
- **Credential custody per ADR-064**: broker client secrets resolve via
  `credentialRef` at call time; NEVER stored in a register schema or app
  config value.
- **EUDI wallet readiness (Dec 2026)**: wallet-based QES is declared and
  documented as a provider-plugin target on the same seam (capability
  discovery via `getSupportedMeans()`/`getSupportedAssurance()`); no wallet
  code ships in this change, and the readiness claim is anchored to a
  conformance test suite a wallet plugin must pass — avoiding the
  orphaned-capability trap by proving the seam with the two shipped providers.
- **Guardian consent for signers under the age of consent (D11)**: a signer
  whose birth date puts them under the guardian consent age (admin setting,
  default 16, UAVG article 5) signs only with a guardian on the same request,
  who co-signs or consents. A request can raise the age for its own signers
  (18 on a POK) and never lower it. The guardian is a signer record with
  `role: guardian`, acts through the same `sign()` path and identity
  resolution as every signer, and cannot be the minor or a minor. The guard
  refuses the minor's act when no guardian is named, and refuses completion
  when no guardian acted. The completed request and the signed artifact (inside
  the v2 MAC) carry a `consentBasis` naming both signers, the age, the basis
  and the guardian's identity tuple, and never the birth date. The consumer
  contract names learniq's OPP and POK and portaliq's toestemmingsformulieren.

## Capabilities

### New Capabilities

- `signer-identity-rails`: pluggable signer-authentication provider seam with
  DigiD/eHerkenning/iDIN assurance mapping to eIDAS SES/AdES/QES, identity
  evidence on the signing request, ADR-064 credential custody, and EUDI-wallet
  QES declared as a provider plugin target; plus guardian consent for signers
  under the age of consent (REQ-DDSIR-008 to 011).

### Modified Capabilities

<!-- none — document-signing request/artifact contracts gain fields via this
     change's own requirements; the existing document-signing requirements are
     unchanged. signing-via-or-approval-with-provider-plugins is untouched:
     identity providers are a SEPARATE seam from signing providers. -->

## Out of Scope

- Any direct DigiD/eHerkenning/iDIN aansluiting (PKIoverheid certificates,
  Logius onboarding) — that is the broker's job; Filinq speaks OIDC to a
  broker. A concrete broker credential/config is deploy-time, not code.
- Shipping an EUDI wallet verifier — declared plugin target only, with the
  conformance suite that a future `eudi-wallet` provider must pass.
- External (accountless) signer end-to-end UX — the portal surface for
  external signers is `portal-contribution` (signer audience); this change
  defines the identity/assurance contract that flow must satisfy, and the
  portal's magic-link/eIDAS trust levels map onto the same assurance model.
- Qualified-certificate artifact production (QES artifact itself) — remains
  the signing provider's job (`document-signing`); identity rails gate WHO may
  trigger it, not how the artifact is made.
- A standing guardian consent given outside the request (a toestemming given
  once at enrolment and reused for later documents). Decision D1 puts consent
  state in learniq and portaliq; a consumer holding such a consent raises the
  request with the guardian as a `consent` signer. Filinq cannot verify how a
  guardian was identified in another app's record, so it accepts only a
  guardian act made through its own rails.
- Switching learniq's OPP and POK flows onto the delegated signing event.
  They write their own `Signature` and `PokSignature` records today; the switch
  is a learniq change.
- Per-child parental authority (gezag). Filinq checks that a guardian acted,
  not that the guardian holds gezag over this child; the consumer decides who
  it names as guardian, the way learniq already checks `parentIds`.

## Success Criteria

- `openspec validate signer-identity-rails --strict` exits 0.
- With the `oidc-broker` provider configured against a test OIDC IdP, a
  request requiring `substantial` refuses a signer whose session carries only
  `low`, and accepts after step-up authentication — live-verified.
- The signer record and OR audit entry for a broker-authenticated signature
  carry provider id, means, assurance, pairwise pseudonym and auth timestamp;
  no BSN-like value appears anywhere (grep + test assertion).
- The broker client secret exists only behind a `credentialRef`; a reviewer
  grep of schemas, config dumps and logs finds no secret material (ADR-064).
- A third provider can be added by implementing the interface + registering
  it — proven by the test-fixture provider used in the conformance suite.
- A signer 14 years old cannot sign a request that names no guardian (403,
  nothing mutated); with a guardian who co-signs, both acts succeed and the
  completed request and artifact carry one `consentBasis` entry naming both
  signer records, with no birth date in it. Proven by tests written red first
  (`tests/unit/Service/Signing/GuardianConsentGuardTest.php`,
  `tests/unit/Service/SigningServiceGuardianConsentTest.php`).
