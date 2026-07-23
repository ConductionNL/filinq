# orphaned-surface-restoration Specification (delta)

---
status: proposed
---

## Purpose

Restore DocuDesk's built-but-unreachable manifest surfaces — letter
correspondence, signing authoring/verify, and publication policy
(prohibitions / standing consents) — to the manifest-V2 shell, delete the dead
legacy `src/router/index.js` that shadowed the live manifest router, and add a
reachability guard so the orphan class cannot recur. Backends for every restored
surface are already live in `appinfo/routes.php` (verified at HEAD); this change
adds no backend code. Signing trust and identity semantics remain owned by the
in-flight security wave (`signing-trust-rebuild`, `signer-identity-rails`); the
policy menu label is owned by `publication-policy-labels-and-nav`; the enriched
bulk-sign builder is owned by `bulk-signing-field-builder` — all referenced, not
modified.

## ADDED Requirements

### Requirement: The dead legacy router is deleted (REQ-DDOSR-001)

The app MUST remove `src/router/index.js`, whose module is imported nowhere at
HEAD and which shadows the manifest-derived router built in `src/main.js`. After
removal the manifest-derived router (`routesFromManifest()`) MUST remain the
app's only router and the app MUST boot and route identically to before. Any
legacy shell file whose sole importer was the deleted router MUST also be
removed or explicitly recorded on the reachability guard's allow-list.

#### Scenario: Router file is gone and nothing imports it

- GIVEN the app at HEAD where `grep -rn "router/index" src/` returns no matches
- WHEN this change is applied
- THEN `src/router/index.js` no longer exists AND the app still mounts `CnAppRoot` and routes every existing page via the manifest-derived router
- @e2e exclude static source assertion — covered by the reachability guard unit test (tests/unit/reachability.spec.js) plus the existing e2e smoke that the app boots

#### Scenario: Existing pages still route after deletion

- GIVEN the dead router is deleted
- WHEN a user navigates to Dashboard, Anonymization, Templates and Signing Requests
- THEN each page renders exactly as before the change
- @e2e tests/e2e/spec-coverage/orphaned-surface-restoration.spec.ts

### Requirement: A reachability guard prevents orphaned surfaces (REQ-DDOSR-002)

The app MUST ship a static unit test that fails when a built surface is
unreachable. The guard MUST assert that every `src/registry.js` entry resolves a
component with a recognised `kind`; that every manifest `type:"custom"` page
`component` maps to a `kind:"page"` registry entry and every `kind:"page"`
registry entry is referenced by at least one manifest page; and that every
page-level view under `src/views/**/` is either reachable through the
manifest/registry or listed on an explicit `KNOWN_HEADLESS` allow-list carrying
a reason. The guard MUST run without a live Nextcloud instance or a browser. The
allow-list MUST seed with the verified headless backends (financial-extraction,
print-queue) and with `BulkSigningPanel` (owned by `bulk-signing-field-builder`)
so those are neither flagged as regressions nor allowed to pass invisibly.

#### Scenario: A newly orphaned view fails CI

- GIVEN a page-level view added under `src/views/` that is neither registered nor allow-listed
- WHEN the reachability guard runs
- THEN the test fails naming the unreachable view
- @e2e exclude build-time guard — covered by the guard's own unit test (tests/unit/reachability.spec.js::testUnregisteredViewFails)

#### Scenario: Manifest custom page without a registry entry fails

- GIVEN a manifest `type:"custom"` page whose `component` has no `kind:"page"` registry entry
- WHEN the guard runs
- THEN the test fails naming the missing registry key
- @e2e exclude static manifest↔registry cross-check — covered by the guard unit test (tests/unit/reachability.spec.js)

#### Scenario: Known-headless backends do not trip the guard

- GIVEN the financial-extraction and print-queue views do not exist while their routes are live
- WHEN the guard runs
- THEN the guard passes because those backends are on the `KNOWN_HEADLESS` allow-list with a reason, and they remain visibly tracked
- @e2e exclude allow-list assertion — covered by the guard unit test

### Requirement: The Correspondence surface is reachable (REQ-DDOSR-003)

The app MUST register `CorrespondenceIndex` in `src/registry.js`, add a
`type:"custom"` manifest page at `/correspondence`, and add a navigation entry
so a user can open letter/correspondence generation from the menu. The restored
page MUST use the existing correspondence data path (its store /
`CorrespondenceController` routes `generate`, `generateBatch`, `jobStatus`) with
no backend change.

#### Scenario: Correspondence opens from the menu

- GIVEN the restored Correspondence menu entry
- WHEN a user clicks it
- THEN the `CorrespondenceIndex` page renders and can list/generate correspondence via the existing backend routes
- @e2e tests/e2e/spec-coverage/orphaned-surface-restoration.spec.ts

#### Scenario: Correspondence is registered and guard-clean

- GIVEN the restored registration
- WHEN the reachability guard runs
- THEN `CorrespondenceIndex` is a reachable `kind:"page"` entry referenced by the `/correspondence` manifest page
- @e2e exclude static registration assertion — covered by the reachability guard unit test

### Requirement: Signing authoring and verify are reachable with trust actions gated (REQ-DDOSR-004)

The app MUST register `SigningRequestForm` (signer-chain create) and
`SignatureVerification` (verify) as reachable manifest pages linked from the
existing Signing index and request-detail surfaces. Reachability MUST NOT
introduce a new trust surface: actions that assert or mutate signing trust
(send for signature, provider binding, signer-identity assertion, presenting a
verification verdict as authoritative) MUST remain governed by the in-flight
security wave — the restored create form MUST expose only non-trust-bearing
composition until `signing-trust-rebuild` / `signer-identity-rails` land, and
the verify page MUST render the `SigningController::verify()` result verbatim
with an attribution to the signing engine rather than minting its own trust
claim. `BulkSigningPanel` MUST NOT be registered by this change; its reachability
is owned by `bulk-signing-field-builder`.

#### Scenario: Signer-chain create form opens from the Signing index

- GIVEN the restored `SigningRequestForm` page
- WHEN a user clicks "New" on the Signing Requests index
- THEN the signer-chain create form opens (multi-signer / field / provider composition) instead of the generic bare-object Add
- @e2e tests/e2e/spec-coverage/orphaned-surface-restoration.spec.ts

#### Scenario: Verify page renders the backend verdict verbatim

- GIVEN a signed file and the restored verify page at `/signing/verify/:fileId`
- WHEN a user opens it from a request detail
- THEN it shows the `signing#verify/{fileId}` result attributed to the signing engine and does not compute or assert its own validity
- @e2e tests/e2e/spec-coverage/orphaned-surface-restoration.spec.ts

#### Scenario: Trust actions defer to the security wave

- GIVEN `signing-trust-rebuild` / `signer-identity-rails` have not yet landed
- WHEN a user reaches the restored create form
- THEN only non-trust-bearing composition (draft the request) is available and no send/provider/identity assertion re-opens the forgeable-signer path
- @e2e exclude cross-change gating state — covered by component tests asserting trust controls are disabled/deferred when the dependency capability is absent

### Requirement: Policy surfaces are reachable, menu ownership deferred (REQ-DDOSR-005)

The app MUST register `ProhibitionIndex` and `StandingConsentIndex` (as pages)
and their `ProhibitionFormModal` / `StandingConsentFormModal` (as modals) in
`src/registry.js`, and add deep-link-reachable `type:"custom"` manifest pages at
`/policy/prohibitions` and `/policy/standing-consents` backed by the live
`PolicyController` routes. This change MUST NOT add a policy navigation entry or
decide policy labels — the menu placement and the "Publish always / never"
labelling are owned by `publication-policy-labels-and-nav`; this change only
makes the pages resolve and be reachable.

#### Scenario: Policy pages are deep-link reachable

- GIVEN the restored policy registrations
- WHEN a user navigates to `/policy/prohibitions` or `/policy/standing-consents`
- THEN the prohibition list and standing-consent list render against the live `PolicyController` routes, with their form modals functional
- @e2e tests/e2e/spec-coverage/orphaned-surface-restoration.spec.ts

#### Scenario: No policy menu label is introduced here

- GIVEN this change is applied without `publication-policy-labels-and-nav`
- WHEN the navigation menu is inspected
- THEN no policy menu entry is added by this change (menu ownership belongs to the labels/nav change), while the pages remain deep-link reachable and guard-clean
- @e2e exclude negative navigation assertion (absence of a menu entry) — covered by a manifest unit assertion in the reachability guard test
