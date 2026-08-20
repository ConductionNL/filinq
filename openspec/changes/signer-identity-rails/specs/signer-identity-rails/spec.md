# signer-identity-rails Specification (delta)

---
status: proposed
---

## Purpose

NL identity rails for signers: a pluggable signer-authentication provider seam
(OIDC-broker style, separate from the artifact-producing signing-provider
seam) supporting DigiD/eHerkenning/iDIN means mapped to eIDAS assurance levels
(low/substantial/high) and signature levels (SES/AdES/QES); per-request
assurance gating enforced fail-closed; identity evidence recorded on the
signing flow with strict data minimisation; broker credential custody per
ADR-064; EUDI-wallet QES (mandatory member-state wallets Dec 2026) declared as
a provider-plugin target proven by a conformance contract. Depends on
`signing-trust-rebuild` (the v2 assertion MAC makes recorded identity
tamper-evident).

## ADDED Requirements

### Requirement: Pluggable signer-authentication provider seam (REQ-DDSIR-001)

The app MUST define `SignerAuthenticationProviderInterface` — distinct from
`SigningProviderInterface` — with `getIdentifier()`, `getSupportedMeans()`,
`getSupportedAssurance()`, `initiateAuthentication()` and
`completeAuthentication()`. Two providers MUST ship: `nextcloud-session`
(current behaviour, assurance `low`, the default) and `oidc-broker` (generic
OIDC against a configured broker exposing DigiD/eHerkenning/iDIN means, with
server-side code exchange and `state`/`nonce`/`aud`/`iss`/`exp` validation).
Provider resolution MUST be strict: an unknown configured provider MUST fail
loudly, never fall back silently. An abstract provider contract test suite
MUST exist that every provider (shipped or future plugin) passes.

#### Scenario: Both shipped providers pass the provider contract

- GIVEN the abstract SignerAuthProvider contract test suite
- WHEN it is run against `nextcloud-session` and `oidc-broker`
- THEN both providers pass every contract case (identifier, means, assurance bounds, fail-closed completeAuthentication on invalid input)
- @e2e exclude backend seam contract — covered by PHPUnit (tests/unit/Service/SignerAuth/SignerAuthProviderContractTest.php)

#### Scenario: Unknown provider configuration fails loudly

- GIVEN app config naming a signer-auth provider that is not registered
- WHEN a signing act requiring authentication resolves the provider
- THEN resolution throws an error naming the missing provider
- AND no fallback provider is silently substituted
- @e2e exclude configuration fault injection — covered by PHPUnit (tests/unit/Service/SignerAuth/SignerAuthProviderFactoryTest.php)

### Requirement: Assurance levels map means to signature levels (REQ-DDSIR-002)

The app MUST model eIDAS assurance as the enum `low | substantial | high`.
The `oidc-broker` provider MUST map broker `acr` values to assurance via a
configurable mapping whose documented defaults cover DigiD
(Midden/Substantieel → substantial, Hoog → high), eHerkenning (EH3 →
substantial, EH4 → high) and iDIN (substantial); an unknown or unmappable
`acr` MUST map to `low` (fail-closed to the weakest). Signature levels MUST
carry assurance floors — SES → low, AdES → substantial, QES → high — and a
signing request's `requiredAssurance` MUST default to its level's floor and
MUST NOT be settable below it.

#### Scenario: Unknown acr degrades to low, not up

- GIVEN an OIDC callback whose validated ID token carries an acr absent from the mapping
- WHEN the identity evidence is derived
- THEN its assurance is `low`
- AND a subsequent sign attempt on a `substantial` request is refused
- @e2e exclude mapping-table unit behaviour — covered by PHPUnit (tests/unit/Service/SignerAuth/OidcBrokerProviderTest.php)

#### Scenario: Request cannot undercut its level floor

- GIVEN a signing request created at level "QES" with `requiredAssurance: "low"` in the payload
- WHEN the creation is processed
- THEN the API rejects it (or normalises upward per the documented behaviour) so the persisted `requiredAssurance` is `high`
- AND the response makes the applied floor explicit
- @e2e tests/e2e/spec-coverage/signer-identity-rails.spec.ts

### Requirement: Assurance gate on every signing act (REQ-DDSIR-003)

`sign()` and `decline()` MUST enforce, server-side and after the existing
ownership check, that the acting signer holds identity evidence that (a) was
issued by a registered provider, (b) meets the request's `requiredAssurance`,
and (c) is fresher than the configured maximum evidence age (default 15
minutes). A missing, expired, or insufficient evidence MUST yield a 403
carrying a step-up indication, and no signer or request object may be mutated.
The `nextcloud-session` provider satisfies only `low`, so a `substantial`/
`high` request is unsignable until the signer completes broker step-up.

#### Scenario: Substantial request refuses a session-only signer

- GIVEN a signing request with `requiredAssurance: "substantial"` and a PENDING signer authenticated only by their Nextcloud session
- WHEN the signer attempts to sign
- THEN the API responds 403 with a step-up indication
- AND the signer record and request status are unchanged
- @e2e tests/e2e/spec-coverage/signer-identity-rails.spec.ts

#### Scenario: Step-up unlocks the signature

- GIVEN the same request and signer
- WHEN the signer completes `oidc-broker` authentication at DigiD-substantial and signs within the evidence window
- THEN the signature is accepted and the signer record becomes SIGNED
- @e2e tests/e2e/spec-coverage/signer-identity-rails.spec.ts

#### Scenario: Stale evidence does not carry over

- GIVEN identity evidence older than the configured maximum age
- WHEN the signer attempts to sign a `substantial` request
- THEN the attempt is refused with the step-up indication
- @e2e exclude clock-dependent expiry — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php)

### Requirement: Identity evidence is recorded with data minimisation (REQ-DDSIR-004)

A signing act performed under broker authentication MUST persist an
`identityEvidence` object on the signer record — `provider`, `means`,
`assurance`, `subjectPseudonym`, `authenticatedAt`, `evidenceHash` (sha256 of
the validated raw token; the raw token itself MUST NOT be persisted) — carry
the same tuple in the OR audit entry context, and include it in the artifact
assertion fields covered by the v2 MAC (REQ-DDSTR-001), making the identity
claim tamper-evident end to end. `subjectPseudonym` MUST be a pairwise/sector
pseudonym; BSN or other national identifiers MUST NOT be stored or logged
anywhere (AVG Art. 5(1)(c) data minimisation), and providers MUST hash any
non-pairwise subject identifier with a per-instance salt before persisting.
`signingRequest.requiredAssurance` and `signerRecord.identityEvidence` MUST be
declared additively in the register JSON with a register version bump.

#### Scenario: Evidence lands on record, audit and artifact together

- GIVEN a signer who signed after DigiD-substantial step-up
- WHEN the signer record, the `docudesk.signing.SIGNED` audit entry and the produced artifact assertion are inspected
- THEN all three carry provider `oidc-broker`, means `digid`, assurance `substantial`, the pairwise pseudonym and the auth timestamp
- AND the artifact's identity fields are covered by the v2 MAC
- @e2e tests/e2e/spec-coverage/signer-identity-rails.spec.ts

#### Scenario: No BSN and no raw token anywhere

- GIVEN a completed broker-authenticated signing flow whose test IdP subject deliberately embeds a BSN-like value
- WHEN stored objects, audit entries, logs and the artifact are scanned
- THEN no BSN-like value and no raw ID token appear; only the pseudonym and the evidence hash
- @e2e exclude negative data-leak scan — covered by PHPUnit assertions over store/audit/log fixtures (tests/unit/Service/SignerAuth/EvidenceMinimisationTest.php)

### Requirement: Broker credentials resolve via credentialRef (REQ-DDSIR-005)

Broker configuration (issuer, client id, redirect URI, acr mapping) MUST live
in admin settings as non-secret values; the OIDC client secret MUST be stored
only as a `credentialRef` and resolved at token-exchange time through the
credential-custody resolver (reusing the `document-waarmerk-certification`
resolver seam per ADR-011). Secret material MUST NOT appear in any register
schema, app-config value, log line, or frontend response (ADR-064).

#### Scenario: Secret is only a reference at rest

- GIVEN a configured `oidc-broker` provider
- WHEN the register JSON, app-config dump and admin-settings API response are inspected
- THEN only the `credentialRef` appears; the client secret value appears nowhere
- AND the token exchange still succeeds by resolving the reference at call time
- @e2e exclude custody grep + resolver round-trip — covered by PHPUnit (tests/unit/Service/SignerAuth/CredentialCustodyTest.php); no browser surface exposes secrets

### Requirement: EUDI-wallet QES is a proven plugin target (REQ-DDSIR-006)

The provider seam MUST be documented as the integration point for EUDI-wallet
based QES (eIDAS 2.0, member-state wallets mandatory December 2026): the docs
MUST name the timeline and the plugin path, and the abstract provider contract
suite MUST be the acceptance bar a future `eudi-wallet` provider passes
(supported means `eudi-wallet`, assurance `high`). No wallet provider class
ships in this change — a stub would be dead code (orphaned-capability trap) —
and the readiness claim MUST NOT be presented in feature documentation as a
shipped wallet integration.

#### Scenario: Readiness is documented honestly

- GIVEN the signer-identity documentation page
- WHEN it is read
- THEN it states DigiD/eHerkenning/iDIN via broker as available (when configured), and EUDI-wallet QES as a plugin target with the Dec 2026 wallet timeline
- AND it does not claim a shipped wallet integration
- @e2e exclude docs-content accuracy — not a navigable app surface; checked in review + docs lint

#### Scenario: The contract suite is the wallet acceptance bar

- GIVEN a fixture provider declaring means `eudi-wallet` and assurance `high`
- WHEN it is run through the abstract provider contract suite
- THEN the suite exercises initiate/complete/fail-closed cases without any DocuDesk core change
- @e2e exclude plugin-seam conformance — covered by PHPUnit (tests/unit/Service/SignerAuth/SignerAuthProviderContractTest.php)

### Requirement: Resolved assurance is surfaced to downstream consumers (REQ-DDSIR-007)

A completed signing act's resolved eIDAS assurance level MUST be surfaced to
downstream consumers (`low | substantial | high`) without ever exposing a
BSN, other national identifier, or the raw ID token. Specifically: (a) the
completion payload of the `docudesk-signing` delegation seam
(`signing-trust-rebuild` REQ-DDSTR-010) MUST carry the resolved assurance so
decidesk's `QesGuard` can gate resolution adoption on it; and (b) the same
assurance MUST be readable by the `portal-signing-actions` `minTrust` gate so an
external portal signer's act is admitted only when its assurance meets the
request's `requiredAssurance`. The surfaced value MUST be exactly the assurance
recorded in `identityEvidence` (REQ-DDSIR-004) — never re-derived from a
client-supplied value — and only the pairwise `subjectPseudonym`, never a BSN,
may accompany it.

#### Scenario: QesGuard and portal gate read the same recorded assurance

- GIVEN a signing act completed after DigiD-substantial step-up (recorded `identityEvidence.assurance` = `substantial`)
- WHEN the delegation-seam completion payload and the `portal-signing-actions` `minTrust` gate read the assurance
- THEN both observe `substantial` — the exact value from `identityEvidence`, not a re-derived or client-supplied one
- AND neither receives a BSN nor the raw ID token
- @e2e exclude cross-app assurance propagation — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php) + the docudesk-signing seam Newman contract
