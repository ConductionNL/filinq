# Tasks: portal-signing-surface

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

<!--
APPLY-TIME STATUS (2026-07-23): T01's dependencies are NOT met — neither
sibling change is implemented at HEAD (both 0/N tasks checked, no
controller/route/verifier/v2-MAC code in this repo at apply time). Per the
apply brief's hard rule ("if a dependency change is NOT yet implemented at
HEAD, implement only what your change specifies... do not silently absorb the
other changes' scope"), only T02/T07 (the self-contained declarative
rowActions + their pinning tests) and the eIDAS posture documentation are
implemented this pass. T03/T04 extend a `portal-signing-actions` receiver that
does not exist; T05/T06/T08/T09 extend/verify a `signing-trust-rebuild` v2 MAC
that does not exist — implementing them now would mean either re-implementing
the sibling changes' own scope, or shipping fields that LOOK identity-bound but
aren't actually covered by any MAC (the exact forgeable-signer shape this
surface exists to close). They are left unchecked and blocked below.
-->

## Prerequisites (apply-blockers, shared with the sibling changes)

- [ ] T01 — Confirm `portal-signing-actions` (receiver, `PortalAssertionVerifier`, invited-signer IDOR guard, verified-actor `SigningService` entrypoint) and `signing-trust-rebuild` (`v: 2` identity-bound MAC + honest verification) are applied first; confirm the A6 assertion carries the resolved `signerEmail` scope claim + the portal subject claims (`subjectRef`/`identityRef`, trust, `jti`) server-side (design.md Open Q1/Q2).
  **BLOCKED (not met, verified 2026-07-23)**: `portal-signing-actions` 0/13 tasks checked, no `PortalSigningReceiverController`/`PortalAssertionVerifier`/route in this repo. `signing-trust-rebuild` 0/16 tasks checked; `NativeSigningProvider::produceSignedArtifact()` still writes the pre-fix `v: 1`-shaped assertion (`mac = HMAC(secret, contentHash)`, no signer/portal fields covered at all) and `SigningVerificationService::verifyAssertion()` still verifies only that flat form.

## Implementation

- [x] T02 — Extend `lib/Portal/PortalContributionProvider.php` — signer `rowActions` (REQ-DDPSS-001). Reference exactly `sign` + `decline` as `rowActions` on the `signerSigningRequests` collection, each `minTrust: substantial`, instance-local relative endpoints; keep `signerRecords` + `data-subject` write-action-free; class stays plain/dependency-free. EUPL-1.2/SPDX docblock + `@spec` tags.

- [ ] T03 — Extend the `portal-signing-actions` receiver to accept `signDocument` (REQ-DDPSS-002). Record the `consent` confirmation + optional drawn-signature payload into `signerRecord.signatureData`; re-verify invited-signer ownership; drive `SigningService::sign()` via the verified-actor entrypoint; transition `status → signed`; preserve the terminal-state machine and uniform not-authorised result.
  **BLOCKED**: the receiver this task extends does not exist (`portal-signing-actions` unimplemented). Out of this change's scope per proposal "Out of Scope" — not built.

- [ ] T04 — Extend the receiver to accept `declineDocument` (REQ-DDPSS-003). Record the client `reason` into `signerRecord.declineReason`; drive `SigningService::decline()`; same invited-signer scope + terminal-state guards.
  **BLOCKED**: same receiver dependency as T03 — not built.

- [ ] T05 — Bind the portal subject identity into the signature evidence MAC (REQ-DDPSS-004). Add the verified assertion's `subjectRef`/`identityRef`, trust and `jti` to the `v: 2` assertion's canonical JSON BEFORE the MAC is computed (coordinate the exact key names with `signing-trust-rebuild`'s writer); source them ONLY from the verified assertion. No new verifier — the existing `hash_equals()` MAC recompute now covers the portal identity.
  **BLOCKED**: there is no `v: 2` assertion/MAC to extend — `signing-trust-rebuild` is unimplemented. Adding the four portal-identity fields to the CURRENT flat assertion would not be covered by the existing MAC (`hash_hmac('sha256', $contentHash, $secret)` covers only the content hash, no assertion field at all today), so it would decorate the evidence with unenforced fields — the exact bug class (portaliq#3) this requirement exists to close. Not built; documented instead (design note added to `PortalContributionProvider.php`'s docblock).

- [ ] T06 — Enforce SES/AES-only assurance (REQ-DDPSS-005). Record/expose an assurance level that never exceeds the session trust; never represent a portal signature as QES.
  **PARTIALLY DONE (declarative only)**: the `SIGNING_MIN_TRUST = 'substantial'` constant + docblock posture notes on `PortalContributionProvider.php` state the SES/AES-only, no-QES posture and gate the rowActions at eIDAS-substantial. Runtime enforcement (recording/exposing an actual assurance level on a signature) depends on the unbuilt receiver/MAC (T03-T05) and is not implemented.

## Testing & quality

- [x] T07 — Unit tests `tests/unit/Portal/PortalContributionProviderTest.php` (extend): pin the `sign`/`decline` rowActions on `signerSigningRequests` (ids, `minTrust: substantial`, relative endpoints); assert `signerRecords` + `data-subject` carry no write action.

- [ ] T08 — Unit tests for the receiver acts (REQ-DDPSS-002/003): happy-path `signDocument` (consent + drawn signature recorded, `SigningService::sign()` called, status → signed); `declineDocument` (reason recorded, `decline()` called); terminal-state rejection unchanged; non-invited signer → uniform not-authorised, `SigningService` never called; body-supplied identity ignored.
  **BLOCKED**: no receiver exists to test (T03/T04 not built).

- [ ] T09 — Portaliq#3 regression + eIDAS tests (REQ-DDPSS-004/005): a stored portal-signature evidence record with a rewritten `subjectRef` (original MAC kept) verifies as `invalid`; evidence omitting the portal signer identity is rejected, never `verified`; a substantial-session signature records at most AES and never claims QES.
  **BLOCKED**: no MAC binding exists to regression-test (T05 not built).

- [x] T10 — Quality gates (scoped to what this pass touched): `php -l`, `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan run in the `nextcloud:34.0.0-apache` container) and the full unit suite (939 tests) pass with zero new violations; `openspec validate portal-signing-surface --strict` (both `--type change` and `--type spec`) exits 0. Hydra gate script itself was not run (not present/invoked in this workflow); route-auth/route-reachability are not applicable — no route was added.

## Quality checklist

- Every MUST in the spec has a unit test; the portaliq#3 identity-binding regression (rewritten portal signer identity ⇒ `invalid`) and the no-write-action-on-read-collections invariant are explicitly asserted.
- No receiver/verifier/entrypoint is re-implemented — this change consumes `portal-signing-actions` + `signing-trust-rebuild`; the boundary is stated in the proposal Out of Scope.
- Manifest labels ship in English source (i18n policy); portaliq owns portal-side translation.
- No register JSON change — `signerRecord.signatureData/declineReason`, `signingRequest.status` verified against HEAD; the portal identity keys live inside the existing `v: 2` evidence JSON.
- QES is explicitly out of scope; the surface claims SES/AES only.
- No DocuDesk UI ships (portaliq owns the SPA) — no Playwright; covered by PHPUnit.
