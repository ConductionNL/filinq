---
id: orphaned-surface-restoration
title: Orphaned Surface Restoration
sidebar_label: Orphaned Surface Restoration
sidebar_position: 13
description: Restoring built-but-unreachable manifest surfaces (correspondence, signing authoring/verify, publication policy) and the reachability guard that prevents the orphan class recurring
keywords:
  - manifest
  - reachability
  - registry
  - correspondence
  - signing
  - publication policy
  - technical debt
---

# Orphaned Surface Restoration

Filinq mounts the manifest-V2 shell (`CnAppRoot` + a router built *from the
manifest* in `src/main.js` via `routesFromManifest()`). A legacy
`src/router/index.js` used to list a larger set of views but was imported
nowhere — the app never routed through it. Any view whose only wiring was
that dead router was **green-but-dead**: the backend ran, the component
compiled, but the route 404'd through the real (manifest-derived) router.

This change (`openspec/changes/orphaned-surface-restoration`) does two
things:

1. Restores reachability for three built-but-unreachable surface families —
   letter correspondence, signing authoring/verify, and publication policy
   (prohibitions / standing consents).
2. Deletes the dead router and its exclusive legacy shell files, and adds a
   **reachability guard** — a static unit test that fails CI when a built
   view becomes unreachable again, so the orphan class cannot recur
   silently.

## Restored surfaces

### Correspondence

`CorrespondenceIndex` (`src/views/correspondence/CorrespondenceIndex.vue`) —
letter/correspondence generation from templates with merge data, single or
batch — is now a `type:"custom"` manifest page at `/correspondence`, with a
menu entry ("Brieven & correspondentie") between Templates and Signing
Requests. No backend change: it drives the existing
`CorrespondenceController` routes (`generate`, `generateBatch`,
`jobStatus`) via `correspondenceStore`.

### Signing authoring + verify (gated)

- `SigningRequestForm` (`/signing/new`) — a signer-chain create form,
  reachable from the Signing Requests index via a `primaryAction`
  ("New signing request") rendered above the navigation list. The index's
  own inline "Add" button is disabled (`config.actionToggles.showAdd:
  false`) so there is exactly one create path — previously the generic Add
  wrote a bare `signingRequest` object with no signer chain, fields, or
  provider.
- `SignatureVerification` (`/signing/verify/:fileId`) — a read-only verify
  page over `signing#verify/{fileId}`, linked from a signing request
  detail's new "Verify" button (shown when the request has a
  `documentFileId`).

**Trust gating.** Reachability is not a new trust surface. Neither
`signing-trust-rebuild` nor `signer-identity-rails` has landed at the time
of this change, so:

- The restored create form composes a **draft** signing request only
  (`signing#createRequest` — a record create, not a send-for-signature or
  provider-binding action) and carries an in-app notice saying so.
- The verify page renders the `SigningController::verify()` result
  **verbatim**, with an explicit "Verification provided by the signing
  engine" attribution — it does not compute or assert its own validity.
- `BulkSigningPanel.vue` is deliberately **not** registered by this change.
  Its enriched bulk-sign + field-placement experience is owned by
  `bulk-signing-field-builder`; registering it here would spec a surface
  another active change is actively rebuilding.

### Publication policy (menu deferred)

`ProhibitionIndex` (`/policy/prohibitions`, "Publish never") and
`StandingConsentIndex` (`/policy/standing-consents`, "Publish always") are
now deep-link-reachable `type:"custom"` pages, backed by the ten live
`PolicyController` routes. Their form modals (`ProhibitionFormModal`,
`StandingConsentFormModal`) are registered `kind:"modal"`.

No menu entry is added here — the "Publish always / never" label and menu
placement are owned by `publication-policy-labels-and-nav`. This change
only makes the pages resolve and be reachable; that change decides how
they surface in navigation.

As part of restoring `StandingConsentIndex`, its previous **inline**
`NcDialog` (a hydra `modal-isolation` violation — the modal lived inside
the index component instead of its own file) was replaced with the
already-existing, previously-unused `StandingConsentFormModal.vue`,
mirroring the pattern `ProhibitionIndex.vue` already used.

## Pre-existing debt found and fixed

While auditing `src/registry.js` against `src/manifest.json`, two registry
entries were found to be **dangling** — registered under `kind:"page"` but
referenced by no manifest page: `TemplateIndex` and `SigningRequestList`.
Both were superseded by an earlier "Phase 8" decomposition to generic
`type:"index"` pages (see the `Templates` / `SigningRequests` page `_note`
fields in `src/manifest.json`) but the registry entries were never removed
— a second, distinct violation of `registry.js`'s own documented contract
("keys must match a manifest `component` string").

The dangling registrations were removed. The two `.vue` files themselves
were **not** deleted:

- `TemplateIndex.vue` is claimed by the still-open
  `office-template-authoring` change, which plans further edits to this
  exact file.
- `SigningRequestList.vue` has no current owner but is left in place
  (minimal-touch restoration, not a cleanup pass).

Both are recorded on the reachability guard's `KNOWN_HEADLESS` allow-list
with a reason, so the guard neither false-positives on them nor lets the
condition pass invisibly.

A second, unrelated duplicate was found:
`src/views/consent/StandingConsentIndex.vue` — a second, incompatible
`StandingConsentIndex` implementation (built against `consentStore` /
`scope: 'entity'`, with its own exclusive, otherwise-unused
`src/modals/CreateStandingConsentModal.vue`). It predates the
`PolicyController`-backed `src/views/policy/StandingConsentIndex.vue` this
change registers, and was orphaned even under the old dead router (never
referenced there either). It is left in place and allow-listed, not
deleted or registered — registering it would collide with the canonical
`policy/` page of the same component name.

## The reachability guard

`tests/unit/reachability.spec.js` (run via `npm run test:unit` — Vitest)
is a purely static, file-based unit suite: it reads `src/registry.js`,
`src/manifest.json`, and `appinfo/routes.php` as text/JSON, and walks
`src/views/**` and the app's three webpack entry points
(`main.js`/`settings.js`/`dashboard.js`) as a relative-import graph. It
needs no live Nextcloud instance, no browser, and no Vue SFC compiler.

It asserts, against the current tree:

1. **Registry integrity** — every `registry.js` entry resolves an actual
   component file and carries a recognised `kind`
   (`page|modal|widget|form-field|cell-renderer`).
2. **Manifest ↔ registry cross-check** — every manifest `type:"custom"`
   page's `component` has a matching `kind:"page"` registry entry, and
   every `kind:"page"` registry entry is referenced by at least one
   manifest page (no dangling registrations — the exact class of bug
   described above).
3. **No hidden orphans** — every `.vue` file under `src/views/**` is either
   transitively reachable from an app entry point, or explicitly listed on
   the `KNOWN_HEADLESS` allow-list with a one-line reason.
4. **Known-headless backends stay visibly tracked** — `financial-extraction`
   and `print-queue` have live backend routes
   (`extraction#*`/`glAccountSuggestion#*`, `printJob#*`) but no Vue
   component at all (a distinct "headless backend" class from a
   router-orphan — building UI for them is net-new work, out of scope
   here). The guard confirms their routes are still live in
   `appinfo/routes.php`, so a stale allow-list entry would itself fail.

It also carries a small set of **detector self-tests** against synthetic
(non-repo) inputs, proving the checks fail the way the canonical spec
scenarios require — e.g. `testUnregisteredViewFails` constructs a fake
unreachable, non-allow-listed view and asserts the detector reports it by
name.

`npm test` (Jest) does **not** run this file — it is excluded via
`jest.config.js`'s `testPathIgnorePatterns`, the same way `tests/e2e/` is
excluded, because it imports from `vitest` and needs no DOM/`.vue`
transform. `vitest.config.js`'s `include` was extended with the file's
exact path alongside the existing `tests/vitest/**` glob.

## Scope boundary: financial extraction and print queue

The market baseline that motivated this change also lists
**financial-extraction** and the **print-job queue** as orphans. Verified
at HEAD, they are a different class: their backends are live
(`extraction#financial`/`#corrections`, `glAccountSuggestion#suggestAccount`,
`printJob#create`…`#updateStatus`) but **no Vue component exists** for
either. There is nothing to *restore* — giving them a surface is net-new
UI, not re-registration. They stay out of scope here and are recorded on
the reachability guard's `KNOWN_HEADLESS` allow-list so the gap remains
visible rather than silently passing.

## Related changes (referenced, not modified)

- `signing-trust-rebuild` — owns cryptographic trust/identity binding for
  signing; the gating above defers to it.
- `signer-identity-rails` — owns signer-identity binding on request
  creation.
- `bulk-signing-field-builder` — owns `BulkSigningPanel`'s enriched
  bulk-sign + field-placement surface.
- `publication-policy-labels-and-nav` — owns the "Publish always / never"
  labels and policy menu placement.
- `office-template-authoring` — plans further work on `TemplateIndex.vue`.
