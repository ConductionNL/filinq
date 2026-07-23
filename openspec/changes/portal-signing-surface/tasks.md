# Tasks: portal-signing-surface

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## Prerequisites (apply-blockers, shared with the sibling changes)

- [ ] T01 — Confirm `portal-signing-actions` (receiver, `PortalAssertionVerifier`, invited-signer IDOR guard, verified-actor `SigningService` entrypoint) and `signing-trust-rebuild` (`v: 2` identity-bound MAC + honest verification) are applied first; confirm the A6 assertion carries the resolved `signerEmail` scope claim + the portal subject claims (`subjectRef`/`identityRef`, trust, `jti`) server-side (design.md Open Q1/Q2).

## Implementation

- [ ] T02 — Extend `lib/Portal/PortalContributionProvider.php` — signer `rowActions` (REQ-DDPSS-001). Reference exactly `sign` + `decline` as `rowActions` on the `signerSigningRequests` collection, each `minTrust: substantial`, instance-local relative endpoints; keep `signerRecords` + `data-subject` write-action-free; class stays plain/dependency-free. EUPL-1.2/SPDX docblock + `@spec` tags.

- [ ] T03 — Extend the `portal-signing-actions` receiver to accept `signDocument` (REQ-DDPSS-002). Record the `consent` confirmation + optional drawn-signature payload into `signerRecord.signatureData`; re-verify invited-signer ownership; drive `SigningService::sign()` via the verified-actor entrypoint; transition `status → signed`; preserve the terminal-state machine and uniform not-authorised result.

- [ ] T04 — Extend the receiver to accept `declineDocument` (REQ-DDPSS-003). Record the client `reason` into `signerRecord.declineReason`; drive `SigningService::decline()`; same invited-signer scope + terminal-state guards.

- [ ] T05 — Bind the portal subject identity into the signature evidence MAC (REQ-DDPSS-004). Add the verified assertion's `subjectRef`/`identityRef`, trust and `jti` to the `v: 2` assertion's canonical JSON BEFORE the MAC is computed (coordinate the exact key names with `signing-trust-rebuild`'s writer); source them ONLY from the verified assertion. No new verifier — the existing `hash_equals()` MAC recompute now covers the portal identity.

- [ ] T06 — Enforce SES/AES-only assurance (REQ-DDPSS-005). Record/expose an assurance level that never exceeds the session trust; never represent a portal signature as QES.

## Testing & quality

- [ ] T07 — Unit tests `tests/unit/Portal/PortalContributionProviderTest.php` (extend): pin the `sign`/`decline` rowActions on `signerSigningRequests` (ids, `minTrust: substantial`, relative endpoints); assert `signerRecords` + `data-subject` carry no write action.

- [ ] T08 — Unit tests for the receiver acts (REQ-DDPSS-002/003): happy-path `signDocument` (consent + drawn signature recorded, `SigningService::sign()` called, status → signed); `declineDocument` (reason recorded, `decline()` called); terminal-state rejection unchanged; non-invited signer → uniform not-authorised, `SigningService` never called; body-supplied identity ignored.

- [ ] T09 — Portaliq#3 regression + eIDAS tests (REQ-DDPSS-004/005): a stored portal-signature evidence record with a rewritten `subjectRef` (original MAC kept) verifies as `invalid`; evidence omitting the portal signer identity is rejected, never `verified`; a substantial-session signature records at most AES and never claims QES.

- [ ] T10 — Quality gates: `php -l`, `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan) and the unit suite pass with zero new violations; relevant Hydra gates (route-auth, route-reachability, security-change-has-tests, spec-coverage) green; `openspec validate portal-signing-surface --strict` exits 0.

## Quality checklist

- Every MUST in the spec has a unit test; the portaliq#3 identity-binding regression (rewritten portal signer identity ⇒ `invalid`) and the no-write-action-on-read-collections invariant are explicitly asserted.
- No receiver/verifier/entrypoint is re-implemented — this change consumes `portal-signing-actions` + `signing-trust-rebuild`; the boundary is stated in the proposal Out of Scope.
- Manifest labels ship in English source (i18n policy); portaliq owns portal-side translation.
- No register JSON change — `signerRecord.signatureData/declineReason`, `signingRequest.status` verified against HEAD; the portal identity keys live inside the existing `v: 2` evidence JSON.
- QES is explicitly out of scope; the surface claims SES/AES only.
- No DocuDesk UI ships (portaliq owns the SPA) — no Playwright; covered by PHPUnit.
