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

A signer under the guardian consent age (an admin setting, default 16, the
Dutch age of consent) signs only with a guardian beside them: the guardian
co-signs or consents on the same request, acts through the same identity
rails as every signer, and the signed document records both signers and the
consent basis (decision D11 of learniq round 1, which folded the round 1
item `filinq-esignature-opp-consent` into this change).

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
- @e2e exclude backend seam contract — covered by PHPUnit (tests/unit/Service/SignerAuth/SignerAuthProviderContractTestCase.php)

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
- WHEN the signer record, the `filinq.signing.SIGNED` audit entry and the produced artifact assertion are inspected
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
OpenRegister credential broker (`CredentialBrokerService::resolveInjectable()`,
the ADR-064 path for a self-hosted host the broker cannot proxy). The app MUST
NOT build its own custody store. A settings value that is not a credential
reference MUST be refused, so a secret pasted into the field is never written.
Secret material MUST NOT appear in any register schema, app-config value, log
line, or frontend response (ADR-064).

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
- THEN the suite exercises initiate/complete/fail-closed cases without any Filinq core change
- @e2e exclude plugin-seam conformance — covered by PHPUnit (tests/unit/Service/SignerAuth/SignerAuthProviderContractTestCase.php)

### Requirement: Resolved assurance is surfaced to downstream consumers (REQ-DDSIR-007)

A completed signing act's resolved eIDAS assurance level MUST be surfaced to
downstream consumers (`low | substantial | high`) without ever exposing a
BSN, other national identifier, or the raw ID token. Specifically: (a) the
completion payload of the `filinq-signing` delegation seam
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
- @e2e exclude cross-app assurance propagation — covered by PHPUnit (tests/unit/Service/SigningServiceTest.php) + the filinq-signing seam Newman contract

### Requirement: A signer under the guardian consent age signs only with a guardian (REQ-DDSIR-008)

A signer whose `birthDate` puts them under the guardian consent age at the
moment of their own signing act MUST have a guardian on the same signing
request before their signature counts: a guardian who co-signs, or a guardian
who consents (REQ-DDSIR-009). The guardian consent age MUST be the admin
setting `signing_guardian_consent_age`, default 16, the age of consent in
Dutch law (UAVG article 5 on AVG article 8). An unset, non-numeric or
non-positive setting MUST fall back to 16. A signing request MAY raise the age
for its own signers through `guardianConsentAge` (for example 18 on a
praktijkovereenkomst, because civil-law minority runs to 18) and MUST NOT
lower it: a lower value is raised to the setting, and the persisted request
states the age that applies. The age MUST be evaluated at the moment of the
signer's own act, not at the request's creation.

The app MUST refuse, with a 403 and without mutating any signer or request
object, a signing act by a signer under the age when no guardian record on the
request points at them. The app MUST reject, with a 400 and before anything is
persisted, the creation of a request that names a signer under the age at
creation time without a guardian for them. A request MUST NOT complete, and no
signed artifact may be produced, while a signer who signed under the age has no
guardian who acted. A signer without a `birthDate` is outside this rule, so a
consumer that knows a signer may be a minor MUST send the birth date. Through
the portal receiver (portal-signing-actions) a refusal MUST answer 403
`signing_refused`, not the 502 of a downstream failure, and MUST NOT count as a
rejected assertion, because the assertion itself was valid.

#### Scenario: A 14-year-old cannot sign an OPP that names no guardian

- GIVEN a PENDING signing request whose only signer record carries a `birthDate` 14 years before today, and no guardian record points at that signer
- WHEN the signer signs
- THEN the act is refused with a 403 that names the guardian requirement
- AND the signer record and the request are unchanged
- @e2e exclude backend guard with no screen of its own, covered by PHPUnit (tests/unit/Service/Signing/GuardianConsentGuardTest.php, tests/unit/Service/SigningServiceGuardianConsentTest.php)

#### Scenario: The age is a setting, and a request can only raise it

- GIVEN the setting `signing_guardian_consent_age` is unset
- WHEN a request is created with `guardianConsentAge: 12`, and another with `guardianConsentAge: 18`
- THEN the first persists `guardianConsentAge: 16` and the second persists `guardianConsentAge: 18`
- @e2e exclude creation-time normalisation, covered by PHPUnit (tests/unit/Service/Signing/GuardianConsentGuardTest.php)

#### Scenario: A signer who has reached the age signs alone

- GIVEN a signer whose `birthDate` is 17 years before today and an applied age of 16
- WHEN the signer signs a request that names no guardian
- THEN the signature is accepted
- AND the completed request carries no `consentBasis`
- @e2e exclude backend guard, covered by PHPUnit (tests/unit/Service/Signing/GuardianConsentGuardTest.php)

#### Scenario: No artifact without a guardian who acted

- GIVEN every signer record on a request is SIGNED, one of them signed under the age, and no SIGNED guardian record points at that signer
- WHEN the request would complete
- THEN no signed artifact is produced and the request does not become COMPLETED
- @e2e exclude completion-time defence in depth that no screen can reach, covered by PHPUnit (tests/unit/Service/SigningServiceGuardianConsentTest.php)

#### Scenario: A portal refusal is a refused act, not a downstream failure

- GIVEN a verified portal signer whose act the guardian rule refuses
- WHEN the portal receiver relays the refusal
- THEN it answers 403 with the body `{"error": "signing_refused"}` and no exception text
- AND the brute-force counter for rejected assertions is not incremented
- @e2e exclude receiver error mapping, covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: The guardian acts through the same identity rails (REQ-DDSIR-009)

A guardian MUST be a signer record on the same request with `role: guardian`
and `guardianForSignerId` naming the minor's signer record. The guardian MUST
act through the same `sign()` path as every signer: the same acting-identity
resolution (Nextcloud session, or the verified portal assertion of
portal-signing-actions REQ-DDPSA-005), the same signer ownership check, the
same mandate and status gates, and the assurance gate of REQ-DDSIR-003 at the
request's `requiredAssurance`. A guardian's act is `co-sign` (the guardian
signs the document as a party) or `consent` (the guardian consents to the
minor signing, the toestemming of article 1:234 BW, against the
`consentStatement` the consumer supplied). The consumer declares the act on
the guardian's entry (`guardianAct`, default `co-sign`); a `consent` entry
without a statement MUST be rejected at creation with a 400.

At creation, `guardianFor` MUST resolve to another signer on the same request,
by `userId` or by email, or the request is rejected with a 400. The app MUST
refuse, with a 403 and without mutation, a guardian act when the guardian is
under the guardian consent age, or when the guardian's identity (Nextcloud uid
or email) is the minor's own. Every act of a minor or a guardian MUST record on
the acting signer's record the identity the rails resolved, as `provider`
(`nextcloud-session`, `portaliq`, or the REQ-DDSIR-001 provider id once
identity evidence exists), `assurance` (`low | substantial | high`; an unknown
portal trust maps to `low`) and `authenticatedAt`, and nothing more
identifying: no BSN, no pseudonym, no raw token. When REQ-DDSIR-004
`identityEvidence` exists on the record, the tuple MUST be taken from it.

#### Scenario: A parent co-signs the OPP of a 14-year-old

- GIVEN a request with a learner signer (`birthDate` 14 years ago) and a guardian signer (`role: guardian`, `guardianForSignerId` the learner, `guardianAct: co-sign`)
- WHEN the learner signs and then the guardian signs, each in their own Nextcloud session
- THEN both acts are accepted
- AND each record carries `provider: nextcloud-session`, `assurance: low` and the moment of the act
- @e2e exclude backend guard, covered by PHPUnit (tests/unit/Service/SigningServiceGuardianConsentTest.php)

#### Scenario: A guardian cannot be the minor, or a minor

- GIVEN a guardian record whose `userId` equals the learner's, or whose `birthDate` is 15 years ago
- WHEN the guardian signs
- THEN the act is refused with a 403 and nothing is mutated
- @e2e exclude backend guard, covered by PHPUnit (tests/unit/Service/Signing/GuardianConsentGuardTest.php)

#### Scenario: A consent act needs the statement the guardian agrees to

- GIVEN a creation payload whose guardian entry declares `guardianAct: consent` and no `consentStatement`, or whose `guardianFor` names nobody on the request
- WHEN the request is created
- THEN it is rejected with a 400 before any object is persisted
- @e2e exclude creation-time validation, covered by PHPUnit (tests/unit/Service/Signing/GuardianConsentGuardTest.php)

#### Scenario: A portal guardian's trust lands as assurance

- GIVEN a guardian who signs through the portal receiver with a verified assertion of trust `substantial`
- WHEN the act is recorded
- THEN the guardian's record says `provider: portaliq` and `assurance: substantial`
- AND it holds no portal subject reference
- @e2e exclude portal-assertion path, covered by PHPUnit (tests/unit/Service/Signing/GuardianConsentGuardTest.php)

### Requirement: The signed document records both signers and the consent basis (REQ-DDSIR-010)

When a request with a signer under the age completes, the app MUST write a
`consentBasis` list onto the signing request and fold the same list into the
signed artifact's assertion before the v2 MAC is computed (REQ-DDSTR-001), so
the basis is as tamper-evident as the signer fields. Each entry MUST name the
minor's signer record id and display name, the applied age, the moment the age
was evaluated (the minor's `signedAt`), a `basis` of `guardian-co-signature`
(at least one guardian co-signed) or `guardian-consent`, and every guardian who
acted for them: signer record id, display name, act, acted-at, the identity
tuple of REQ-DDSIR-009, the consent statement for a `consent` act, and the
consumer's `guardianRef` when one was supplied. An entry MUST NOT carry the
birth date. The `SIGNED` audit entries of the minor and the guardian MUST carry
the guardian link in their metadata. A request without a signer under the age
MUST carry no `consentBasis`, and its artifact assertion MUST carry no
`consentBasis` field.

#### Scenario: The artifact names the learner, the parent and the basis

- GIVEN the co-signed OPP of REQ-DDSIR-009 completes
- WHEN the signing request and the native artifact assertion are read
- THEN both carry one `consentBasis` entry naming the learner, the guardian, basis `guardian-co-signature` and age 16
- AND the entry carries no birth date
- AND the entry sits inside the assertion the MAC covers
- @e2e exclude artifact and record contents, covered by PHPUnit (tests/unit/Service/SigningServiceGuardianConsentTest.php, tests/unit/Service/Signing/NativeSigningProviderTest.php)

#### Scenario: A request between adults is untouched

- GIVEN a request whose signers carry no `birthDate` under the age
- WHEN it completes
- THEN neither the request nor the artifact assertion carries `consentBasis`
- @e2e exclude artifact and record contents, covered by PHPUnit (tests/unit/Service/SigningServiceGuardianConsentTest.php)

### Requirement: Learner-facing consumers of the guardian contract (REQ-DDSIR-011)

The guardian contract MUST be reachable through the delegated
`DocumentSigningRequestedEvent` (one-signing-rail-for-the-fleet REQ-DDSRC-001)
without a change to the event: each `signers[]` entry MAY carry `birthDate`
(ISO 8601 date), `role` (`signer | guardian`), `guardianFor` (the `userId` or
email of another entry in the same list), `guardianAct` (`co-sign | consent`),
`consentStatement` and `guardianRef` (a reference to the consumer's own
guardian record, never a copy of it), and the request MAY carry
`guardianConsentAge`. The contract names these consumers:

- learniq ontwikkelingsperspectief (OPP, a `LearningPlan` of kind `opp`): the learner entry carries `LearnerProfile.birthDate`; each parent in `LearnerProfile.parentIds` is a `guardian` entry with `guardianAct: co-sign` and a `guardianRef` from `LearnerProfile.guardianRefs`.
- learniq praktijkovereenkomst (POK, `Praktijkovereenkomst`): the request carries `guardianConsentAge: 18`, and the student's legal representative is a `guardian` entry that co-signs or consents.
- portaliq toestemmingsformulieren: a guardian consents through the portal signer surface (portal-signing-actions) as a `guardian` entry with `guardianAct: consent`; portaliq's `{statement, grantedByRef, grantedAt}` consent shape (change activity-parental-consent) maps onto `consentStatement`, `guardianRef` and the recorded acted-at.

Learniq's `assuranceLevel` vocabulary maps onto this one as `basic` to `low`;
`none` is not a signing act. Learniq's OPP and POK flows write their own
`Signature` and `PokSignature` records today. They reach this contract once
they raise the request through the delegated event, which is a learniq change
this requirement does not deliver.

#### Scenario: A delegated OPP request carries the guardian link into the signer records

- GIVEN learniq raises a signing request with a learner entry carrying `birthDate` and a parent entry carrying `role: guardian`, `guardianFor` the learner's `userId` and a `guardianRef`
- WHEN filinq creates the request
- THEN the guardian's signer record carries `role: guardian`, `guardianForSignerId` the learner's signer record id, `guardianAct: co-sign` and the `guardianRef`
- AND the learner's signer record carries the `birthDate` in a property hidden from list views
- @e2e exclude cross-app event contract with no screen of its own, covered by PHPUnit (tests/unit/EventListener/DocumentSigningRequestedListenerTest.php, tests/unit/Service/SigningServiceGuardianConsentTest.php)
