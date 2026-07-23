# Tasks: portal-signing-surface

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

<!--
STATUS UPDATE (2026-07-23, same-PR resume): the sibling changes' dependency
was resolved WITHIN THIS SAME PULL REQUEST — `portal-signing-actions`
(receiver, verifier, invited-signer guard, verified-actor entrypoint) and
`signing-trust-rebuild` (`v: 2` identity-bound MAC) both now have real code
and unit tests in this repo. T03/T04/T05/T08/T09 below were re-checked against
that code and are now genuinely implemented — none of it re-implements the
sibling changes' own scope; this change only THREADS the portal identity
through the seams those changes expose (the verified-actor `sign()`/`decline()`
parameters, the `produceSignedArtifact()` `$context` array). T06 remains
partial: the never-QES/never-exceeds-trust invariant holds structurally (the
native provider only ever produces SES), but no dedicated per-signature
assurance-level field is recorded/exposed beyond the general
`SigningConcludedEvent.assuranceLevel` completion payload added by
`signing-trust-rebuild` REQ-DDSTR-010.
-->

## Prerequisites (apply-blockers, shared with the sibling changes)

- [x] T01 — Confirm `portal-signing-actions` (receiver, `PortalAssertionVerifier`, invited-signer IDOR guard, verified-actor `SigningService` entrypoint) and `signing-trust-rebuild` (`v: 2` identity-bound MAC + honest verification) are applied first; confirm the A6 assertion carries the resolved `signerEmail` scope claim + the portal subject claims (`subjectRef`/`identityRef`, trust, `jti`) server-side (design.md Open Q1/Q2).
  **MET (2026-07-23)**: both sibling changes now have implemented, unit-tested code in this repo (`lib/Controller/PortalSigningReceiverController.php`, `lib/Portal/PortalAssertionVerifier.php`, `lib/Service/Signing/NativeSigningProvider.php` v2 writer). The portal subject claims flow: `PortalSigningReceiverController::authoriseAct()` derives `subjectRef`/`identityRef`/`trust`/`jti` from the verified assertion → `SigningService::sign()`/`decline()` verified-actor param → `produceAndStoreSignedArtifact()` context → `NativeSigningProvider::produceSignedArtifact()` folds them into the `v: 2` MAC. The `signerEmail` scope claim itself is still NOT forwarded by portaliq's live A6 wire format (go-live blocker on `portal-signing-actions`, not an authoring blocker here).

## Implementation

- [x] T02 — Extend `lib/Portal/PortalContributionProvider.php` — signer `rowActions` (REQ-DDPSS-001). Reference exactly `sign` + `decline` as `rowActions` on the `signerSigningRequests` collection, each `minTrust: substantial`, instance-local relative endpoints; keep `signerRecords` + `data-subject` write-action-free; class stays plain/dependency-free. EUPL-1.2/SPDX docblock + `@spec` tags.

- [x] T03 — Extend the `portal-signing-actions` receiver to accept `signDocument` (REQ-DDPSS-002). `PortalSigningReceiverController::signDocument()` records the `consent` confirmation + optional drawn-signature payload into `signerRecord.signatureData` via `SigningService::sign()`'s `$signatureData` param; re-verifies invited-signer ownership via `authoriseAct()`; drives `SigningService::sign()` via the verified-actor entrypoint; the existing `SigningService` status machine transitions `status → SIGNED`; terminal-state machine and uniform not-authorised result preserved unchanged.

- [x] T04 — Extend the receiver to accept `declineDocument` (REQ-DDPSS-003). `PortalSigningReceiverController::declineDocument()` records the client `reason` into `signerRecord.declineReason` via `SigningService::decline()`'s `$reason` param; same invited-signer scope + terminal-state guards as T03.

- [x] T05 — Bind the portal subject identity into the signature evidence MAC (REQ-DDPSS-004). The verified assertion's `subjectRef`/`identityRef`, `trust` and `jti` are threaded (`portalSubjectRef`/`portalIdentityRef`/`portalTrust`/`portalJti`) into `NativeSigningProvider::produceSignedArtifact()`'s `$context`, folded into the assertion object BEFORE the `v: 2` MAC is computed — sourced ONLY from the verified assertion (never the request body). No new verifier — the existing `hash_equals()` MAC recompute now covers the portal identity. Regression-tested in `tests/unit/Service/Signing/NativeSigningProviderTest.php::testProduceSignedArtifactBindsPortalIdentityIntoMac` (rewritten `portalSubjectRef`, original MAC kept, verifies `invalid`).

- [ ] T06 — Enforce SES/AES-only assurance (REQ-DDPSS-005). Record/expose an assurance level that never exceeds the session trust; never represent a portal signature as QES.
  **PARTIALLY DONE**: the `SIGNING_MIN_TRUST = 'substantial'` constant + docblock posture notes on `PortalContributionProvider.php` state the SES/AES-only, no-QES posture. Structurally enforced: the native provider only ever produces SES (`supportsLevel()` rejects anything else at both creation and production time), so a portal signature can never be evidenced above SES regardless of portal trust. NOT done: a dedicated, labelled per-signature assurance field — the only assurance signal recorded today is the general `SigningConcludedEvent.assuranceLevel` completion payload (signing-trust-rebuild REQ-DDSTR-010), which is request-scoped, not portal-act-scoped.

## Testing & quality

- [x] T07 — Unit tests `tests/unit/Portal/PortalContributionProviderTest.php` (extend): pin the `sign`/`decline` rowActions on `signerSigningRequests` (ids, `minTrust: substantial`, relative endpoints); assert `signerRecords` + `data-subject` carry no write action.

- [x] T08 — Unit tests for the receiver acts (REQ-DDPSS-002/003): `tests/unit/Controller/PortalSigningReceiverControllerTest.php` covers happy-path `signDocument` (consent + drawn signature recorded, `SigningService::sign()` called with the exact positional args, status returned) and `declineDocument` (reason recorded, `decline()` called); non-invited/cross-signer → uniform not-authorised 403, `SigningService` never called; body-supplied identity ignored (identity asserted comes from the mint, never the request params). Terminal-state rejection unchanged is covered at the `SigningService` layer (`tests/unit/Service/SigningServiceTest.php::testDeclineRejectedOnCompletedRequest`), not re-tested at the receiver layer (the receiver has no terminal-state logic of its own — it delegates entirely to `SigningService`).

- [x] T09 — Portaliq#3 regression + eIDAS tests (REQ-DDPSS-004/005): the rewritten-`subjectRef`-verifies-`invalid` regression is covered (`NativeSigningProviderTest::testProduceSignedArtifactBindsPortalIdentityIntoMac`). "Evidence omitting the portal signer identity is rejected, never verified" holds by construction — omitted portal fields simply produce an ordinary (non-portal) v2 assertion, covered by the existing v2 round-trip tests. "A substantial-session signature records at most AES and never claims QES" is a structural guarantee (see T06), not an independently labelled unit test.

- [x] T10 — Quality gates (scoped to what this pass touched): `php -l` clean; `composer check:strict` (PHPCS, PHPStan, Psalm run in the `nextcloud:34.0.0-apache` container) — see PR body for the full result; the full unit suite is green (1016/1016, up from the 939 baseline) with zero new violations; `openspec validate portal-signing-surface --strict` — see PR body. Hydra gate script itself was not run (not present/invoked in this workflow); route-auth/route-reachability were verified for the new `PortalSigningReceiverController` routes (owned by `portal-signing-actions`, not re-verified here).

## Quality checklist

- Every MUST in the spec has a unit test; the portaliq#3 identity-binding regression (rewritten portal signer identity ⇒ `invalid`) and the no-write-action-on-read-collections invariant are explicitly asserted.
- No receiver/verifier/entrypoint is re-implemented — this change consumes `portal-signing-actions` + `signing-trust-rebuild`; the boundary is stated in the proposal Out of Scope.
- Manifest labels ship in English source (i18n policy); portaliq owns portal-side translation.
- No register JSON change — `signerRecord.signatureData/declineReason`, `signingRequest.status` verified against HEAD; the portal identity keys live inside the existing `v: 2` evidence JSON.
- QES is explicitly out of scope; the surface claims SES/AES only.
- No DocuDesk UI ships (portaliq owns the SPA) — no Playwright; covered by PHPUnit.
