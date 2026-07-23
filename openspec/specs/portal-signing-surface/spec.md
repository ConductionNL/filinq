---
capability: portal-signing-surface
status: in-progress
built_by: openspec/changes/portal-signing-surface
---

# portal-signing-surface Specification

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [portal-signing-surface](../../changes/portal-signing-surface/) _(active)_ — contract-v2.2 `rowActions` on the signer manifest + portal-subject evidence binding (kind: code)

## Purpose

DocuDesk gives an external, accountless **signer** a real signing surface
through **portaliq** (hydra ADR-046, contribution contract v2.2): `sign` and
`decline` rendered as per-document `rowActions` on the documents-awaiting-me
collection, at eIDAS-aligned substantial trust. Closing the forgeable-signer
class of bug filed as portaliq#3, a portal-originated signature's evidence
cryptographically binds the PORTAL signer's identity so it can never be
re-attributed. This capability consumes the receiver, verifier, invited-signer
guard and verified-actor entrypoint from `portal-signing-actions` and the
`v: 2` identity-bound MAC from `signing-trust-rebuild`; it does not
re-implement them.

**Status note (2026-07-23)**: both sibling changes (`portal-signing-actions`
receiver/verifier/entrypoint, `signing-trust-rebuild`'s `v: 2` MAC) now have
code and unit tests in this repo, so REQ-DDPSS-001 through REQ-DDPSS-004 below
are implemented and tested. REQ-DDPSS-005 (SES/AES-only, never QES) holds
structurally — the native provider only ever produces SES-level artifacts
regardless of portal trust, so a portal signature can never be recorded above
SES/`low` assurance — but no DEDICATED per-signature assurance-level field is
recorded/exposed yet beyond the general `SigningConcludedEvent.assuranceLevel`
completion payload (signing-trust-rebuild REQ-DDSTR-010); a labelled
portal-signature assurance surface remains a follow-up. Newman/Playwright
coverage for this capability has not been authored; PHPUnit is the sole test
evidence today.

## Requirements

### Requirement: Signer manifest declares sign and decline as substantial-gated rowActions (REQ-DDPSS-001)

`OCA\DocuDesk\Portal\PortalContributionProvider`'s `signer` manifest MUST
reference exactly two contract-v2.2 `rowActions` — `sign` and `decline` — on the
`signerSigningRequests` collection (the documents-awaiting-me rows), each gated
at `minTrust: substantial` (eIDAS-aligned: an advanced-electronic-signature act
requires a substantial-assurance portal session). Each referenced action MUST
resolve to an instance-local RELATIVE receiver endpoint (leading slash, no
scheme, no host, no `..`). The `signerRecords` collection and the entire
`data-subject` manifest MUST carry no write action. The provider MUST stay a
plain, dependency-free class (no portaliq import, no `implements`, no
constructor) — it only adds pure-data rowAction declarations.

#### Scenario: The signer collection carries the sign and decline rowActions
- **GIVEN** a constructed `PortalContributionProvider` and a subject with `audience: 'signer'`
- **WHEN** `getContribution($subject)` is called
- **THEN** the `signerSigningRequests` collection references exactly `sign` and `decline` as `rowActions`, each `minTrust: substantial`, each pointing at an instance-local relative receiver endpoint
- **AND** the `signerRecords` collection and the `data-subject` manifest carry no write action
- @e2e exclude backend-only manifest rendered inside portaliq, not DocuDesk — covered by PHPUnit (tests/unit/Portal/PortalContributionProviderTest.php)

### Requirement: signDocument records consent plus optional drawn signature and transitions to signed (REQ-DDPSS-002)

The `signDocument` receiver act MUST, for a verified invited signer, record the
signer's explicit consent confirmation (evidence of intent) and an OPTIONAL
drawn-signature payload into the existing `signerRecord.signatureData` field
(schema `visible:false`, never projected to the read manifest), then drive the
honest `OCA\DocuDesk\Service\SigningService::sign()` primitive to transition the
request `status → signed`. Server-side ownership MUST be re-verified via the
`portal-signing-actions` invited-signer scope (`signerRecord.email == verified
assertion signerEmail AND signingRequestId == target`) before any act; the
`SigningService` terminal-state status machine MUST still reject a sign on an
already-terminal request. The consent flag and drawn signature MUST NOT be
trusted for identity — identity is the server-derived assertion subject, never
the request body.

#### Scenario: A verified invited signer signs with consent
- **GIVEN** a valid `signer` assertion resolving to an invited signer on a still-pending request, a body carrying an affirmative `consent` and an optional drawn-signature payload
- **WHEN** the receiver processes `signDocument`
- **THEN** it records the consent + drawn signature into `signerRecord.signatureData`, calls `SigningService::sign()` acting as the resolved signer, and the request transitions `status → signed`
- **AND** a `signDocument` on an already-terminal request is rejected by the status machine unchanged, and a signer not invited on the target gets the uniform not-authorised result with `SigningService` never called
- @e2e exclude backend receiver act with no DocuDesk UI surface — covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: declineDocument records a reason and transitions through the honest primitive (REQ-DDPSS-003)

The `declineDocument` receiver act MUST, for a verified invited signer, record
the client-supplied `reason` into `signerRecord.declineReason` and drive
`SigningService::decline()` to transition the request, preserving the same
invited-signer scope check and terminal-state machine as `signDocument`. A
missing reason MUST be handled per the `SigningService` contract (not silently
dropped). Ownership and identity MUST be server-derived exactly as for
`signDocument`.

#### Scenario: A verified invited signer declines with a reason
- **GIVEN** a valid `signer` assertion resolving to an invited signer on a still-pending request and a body carrying a decline `reason`
- **WHEN** the receiver processes `declineDocument`
- **THEN** it records the `reason` and calls `SigningService::decline()`, transitioning the request out of pending through the status machine
- **AND** a decline on an already-terminal request is rejected unchanged, and a non-invited signer gets the uniform not-authorised result
- @e2e exclude backend receiver act with no DocuDesk UI surface — covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: Portal signature evidence cryptographically binds the portal signer identity (REQ-DDPSS-004)

For a portal-originated signature the recorded signature evidence MUST
cryptographically bind the PORTAL signer's identity — the verified assertion's
`subjectRef` / `identityRef`, eIDAS trust level, and act `jti` — together with
the document hash and the signing timestamp, into the `v: 2` assertion MAC
(`signing-trust-rebuild` `REQ-DDSTR-001`). Those portal-identity claims MUST be
included in the canonical JSON that the MAC is computed over BEFORE the MAC is
taken, and MUST be sourced ONLY from the verified assertion, never the request
body. An evidence record that validates while OMITTING the portal signer
identity for a portal-originated signature is a spec violation. Rewriting any
bound portal-identity field (e.g. swapping the `subjectRef` to a different
subject) while keeping the original MAC MUST cause verification to report
`invalid`. This is the portal half of the forgeable-signer fix (portaliq#3); the
in-app signer binding remains owned by `REQ-DDSTR-001`.

#### Scenario: A rewritten portal signer identity fails evidence validation
- **GIVEN** a portal-originated signature whose `v: 2` evidence binds the assertion `subjectRef`/`identityRef`, trust and `jti` plus the document hash and timestamp into the MAC
- **WHEN** the stored evidence is rewritten to name a DIFFERENT portal subject while keeping the original MAC, and the artifact is verified
- **THEN** verification reports status `invalid` (the recomputed MAC no longer matches)
- **AND** a portal signature whose evidence omits the portal signer identity entirely is treated as a spec violation, never `verified`
- **AND** the bound identity fields are taken only from the verified assertion; a body-supplied `subjectRef`/`identityRef` never changes the evidence
- @e2e exclude cryptographic mutation test not browser-drivable — covered by PHPUnit (tests/unit/Service/Signing/NativeSigningProviderTest.php::testProduceSignedArtifactBindsPortalIdentityIntoMac)

### Requirement: The surface claims SES/AES only and refuses QES over-claim (REQ-DDPSS-005)

This surface MUST expose only simple / advanced electronic signature (SES / AES)
assurance and MUST NOT claim qualified electronic signature (QES) assurance for a
portal signature. The assurance level recorded and exposed on a portal signature
MUST NOT exceed the portal session's trust level (a `substantial` session yields
at most AES-grade evidence). Qualified signatures via an external eIDAS QTSP
(Article 3(12)), PAdES-LTV and certificate rails are delegated to an external
provider and MUST NOT be represented as delivered by this surface.

#### Scenario: A substantial-trust portal signature is AES, never QES
- **GIVEN** a portal signer acting on a `minTrust: substantial` session
- **WHEN** the signature evidence records the assurance level
- **THEN** the recorded/exposed assurance is at most AES and never claims QES
- **AND** the surface does not represent a QES as delivered — QES is delegated to an external QTSP
- @e2e exclude structural guarantee (native provider only ever produces SES/`low`) — no dedicated per-signature assurance field yet; not independently unit-tested beyond `SigningConcludedEventTest.php`'s general assurance-resolution coverage
