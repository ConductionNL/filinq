---
kind: code
depends_on: [portal-contribution, portal-signing-actions, signing-trust-rebuild]
---

# Proposal: portal-signing-surface

## Why

DocuDesk's `portal-contribution` gives an external **signer** (a person WITHOUT
a Nextcloud account) a READ-ONLY window into their signing work through
**portaliq**, the shared external portal (hydra ADR-046, contribution contract
v2.2). Its `signer` manifest ships `actions: []` (`REQ-DDPORT-006`): it exposes
`signerRecords` and the `signerSigningRequests` via-join, but the subject can
look, not act. `onderteken*` is the single highest demand signal in the tender
corpus — **358 tenders** — so "let the invited counterparty actually sign from
the portal" is the headline follow-up.

Two sibling changes already land the plumbing this surface stands on, hence the
`depends_on`:

- `portal-signing-actions` (`REQ-DDPSA-*`) adds the bearer-guarded DocuDesk
  receiver, the `PortalAssertionVerifier`, the server-derived invited-signer
  IDOR guard, and the verified-actor entrypoint that drives the honest
  `SigningService::sign()` / `decline()` primitive from a `#[PublicPage]`
  endpoint. It declares `sign` / `decline` / `viewDocument` at the manifest's
  top-level `actions[]`.
- `signing-trust-rebuild` (`REQ-DDSTR-001`) makes the native signed-artifact
  assertion bind the SIGNER identity into a `v: 2` MAC
  (`HMAC-SHA256(secret, sha256(canonical-document) . "\n" .
  canonical-JSON(assertion-without-mac))`) so a rewritten signer name fails
  verification — the in-app half of the forgeable-signer fix.

This change closes the two gaps those leave, and both are grounded in the
now-richer contract:

1. **The contribution SURFACE the subject sees.** The contract now expresses
   per-collection `rowActions` (contract v2.2), `type: update` actions, and
   `set` server-stamping. `portal-signing-actions` wired the receiver but left
   the actions as a bare top-level `actions[]` list; this change wires **`sign`
   and `decline` as `rowActions` on the `signerSigningRequests` collection**, at
   `minTrust: substantial`, so portaliq renders a per-document sign / decline
   control on exactly the rows awaiting the subject — the eIDAS-aligned signing
   surface a signer actually uses.
2. **The forgeable-signer fix extended to the PORTAL identity chain
   (portaliq#3).** `REQ-DDSTR-001` binds the in-app signer's *name/level/
   timestamp* into the MAC, and `portal-signing-actions` records the portal
   assertion `jti` in the AUDIT — but neither binds the PORTAL SUBJECT's claims
   (`subjectRef`/`identityRef`, trust level, `jti`) into the cryptographic
   signature EVIDENCE. A portal-originated signature whose evidence validates
   without the portal signer's identity is exactly the class of bug filed as
   portaliq#3 ("signing MAC excludes identity ⇒ forgeable signer"). This change
   makes the evidence for a portal signature cryptographically bind the portal
   subject identity + document hash + timestamp, so the recorded signer cannot
   be swapped after the fact.

## What Changes

- **`sign` + `decline` become rowActions on `signerSigningRequests`.** Extend
  `lib/Portal/PortalContributionProvider.php` (still plain, dependency-free) so
  the `signer` manifest references contract-v2.2 `rowActions`
  `[sign, decline]` on the `signerSigningRequests` collection, each `minTrust:
  substantial` (eIDAS-aligned), pointing at the instance-local relative
  receiver endpoints. The `data-subject` manifest stays read-only.
- **`signDocument` records consent + optional drawn signature and transitions to
  `signed`.** The receiver body carries a consent confirmation and an optional
  drawn-signature payload; the server re-verifies ownership via the
  `signerRecord` scope (the `portal-signing-actions` invited-signer guard),
  records the signature evidence, and drives the honest
  `SigningService::sign()` primitive to transition the request `status → signed`.
- **`declineDocument` records a reason** and drives `SigningService::decline()`.
- **Portal signature evidence binds the portal subject identity (portaliq#3).**
  For a portal-originated signature, the evidence assertion MUST additionally
  carry and MAC-cover the portal subject claims (`subjectRef`/`identityRef`,
  trust level, `jti`) alongside the document hash and timestamp; verification
  MUST fail if any bound identity field is altered.
- **eIDAS posture is explicit.** This delivers a simple / advanced electronic
  signature (SES / AES) surface; qualified electronic signatures (QES) are
  delegated to external QTSPs and are out of scope.

## Capabilities

### Added Capabilities

- `portal-signing-surface`: an external, accountless signer signs or declines a
  document from portaliq's SPA through `rowActions` on the documents-awaiting-me
  collection, at eIDAS-aligned substantial trust, and the recorded signature
  evidence cryptographically binds the portal signer's identity so it cannot be
  forged or re-attributed.

## Affected Projects

- [x] Project: `docudesk` — extend `lib/Portal/PortalContributionProvider.php` (signer `rowActions`); extend the `portal-signing-actions` receiver + verified-actor `SigningService` entrypoint to record consent + optional drawn signature and to bind the portal subject claims into the signature evidence MAC; unit tests under `tests/unit/Portal/` + `tests/unit/Service/`; this OpenSpec change.
- Depends on: `portal-contribution` (signer manifest), `portal-signing-actions` (receiver, `PortalAssertionVerifier`, invited-signer IDOR guard, verified-actor entrypoint), `signing-trust-rebuild` (`v: 2` identity-bound MAC + honest verification).
- Contract: `apps-extra/portaliq` — the contract-v2.2 `rowActions` + A6 endpoint-forward + frozen assertion requirements this surface is templated against; runtime consumer that renders the rowActions and forwards the acts.
- Reference: `hydra` ADR-046 (portaliq external portal); portaliq#3 (forgeable-signer class of bug).

## Out of Scope

- The receiver, `PortalAssertionVerifier`, invited-signer IDOR/SSRF guard and
  the verified-actor `SigningService` entrypoint — owned by
  `portal-signing-actions`; this change consumes them and adds the rowAction
  surface + the portal-subject evidence binding on top.
- The in-app signer identity MAC and honest verification states — owned by
  `signing-trust-rebuild` (`REQ-DDSTR-001`); this change extends the SAME
  assertion to additionally bind the portal subject claims for portal-originated
  signatures, not re-specify the in-app binding.
- **Qualified electronic signatures (QES).** Certificate-backed QES via an
  external QTSP (eIDAS Article 3(12)), PAdES-LTV, and NL identity rails are out
  of scope; this surface delivers SES / AES only.
- Any portal UI, session, auth edge or rendering — portaliq owns the entire
  external surface (ADR-046); DocuDesk ships zero portal frontend.
- The `data-subject` objection-intake write path — still deferred
  (`portal-contribution` design.md); this change touches the `signer` audience
  only.

## Success Criteria

- `openspec validate portal-signing-surface --strict` exits 0.
- The `signer` manifest references exactly `sign` + `decline` as `rowActions` on
  the `signerSigningRequests` collection at `minTrust: substantial`; the
  `signerRecords` and `data-subject` collections carry no write action.
- A verified invited signer's `signDocument` records the consent confirmation +
  optional drawn signature, transitions the request `status → signed` through
  the honest `SigningService::sign()` primitive, and preserves every
  `portal-signing-actions` guard (assertion validity, invited-signer scope,
  terminal-state machine).
- The signature evidence for a portal signature cryptographically binds the
  portal subject identity (`subjectRef`/`identityRef`, trust, `jti`) + document
  hash + timestamp; an evidence record whose bound signer identity is altered
  fails validation (portaliq#3 regression test).
- QES is explicitly refused / delegated; only SES / AES assurance is claimed.
- `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan) and the unit suite pass
  on the new files with zero new violations.
