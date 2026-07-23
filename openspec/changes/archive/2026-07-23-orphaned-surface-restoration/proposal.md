---
kind: code
---

# Proposal: orphaned-surface-restoration

## Why

DocuDesk ships more working software than a user can reach. The app mounts the
manifest-V2 shell (`CnAppRoot` + a router built *from the manifest* in
`src/main.js`, verified HEAD), yet a legacy `src/router/index.js` still lists a
larger set of views — and that file is **imported nowhere** (verified:
`grep -rn "router/index" src/` returns nothing; `src/main.js` builds its own
routes via `routesFromManifest()`). Every view whose only wiring was that dead
router is therefore green-but-dead: the backend runs, the component compiles,
the route 404s.

Verified orphans at HEAD (component present under `src/views/`, backend routes
live in `appinfo/routes.php`, but absent from `src/registry.js` and
`src/manifest.json`):

- **Correspondence** — `src/views/correspondence/CorrespondenceIndex.vue`
  exists; `CorrespondenceService` (873 LOC) + `CorrespondenceController` +
  `BatchCorrespondenceJob` are live behind routes `correspondence#generate`,
  `#generateBatch`, `#jobStatus` (routes.php L121–123). Zero reachable UI
  (R1-code-inventory.md §4.2). Letter/correspondence generation is a shipped,
  archived capability (`letter-correspondence-generation` canonical spec) with
  no way in.
- **Bespoke signing authoring** — `SigningRequestForm.vue` (signer-chain
  create), `BulkSigningPanel.vue` (route `signing#bulkSign`, routes.php L147),
  `SignatureVerification.vue` (route `signing#verify/{fileId}`, L148) all exist
  and are unregistered. The only reachable "create" is the generic index Add,
  which writes a bare `signingRequest` object with no signer chain, fields or
  provider (R1 §2, §4.3). The multi-signer authoring, bulk-sign and verify
  experiences are unreachable.
- **Policy (prohibitions / standing consents)** — `ProhibitionIndex.vue`,
  `StandingConsentIndex.vue` (+ their `*FormModal.vue`) exist; `PolicyController`
  exposes 10 live routes (`policy#indexProhibitions` … `#deleteStandingConsent`,
  routes.php L82–93) backed by `PolicyMatchService`/`PolicyRetroactiveService`.
  Unreachable (R1 §4.4).

This is a market-visible honesty gap, not a cosmetic one. The buyer research
ranks a **human-in-the-loop redaction/authoring workbench** as NL table-stakes
(R2-competitors.md §D, 4 competitors; dd-coverage-baseline "Human-review
redaction UI is NL-market table stakes and DocuDesk lacks it"), and DocuDesk's
own tracker requirements #45–64 describe exactly the review/authoring surfaces
that already exist in code but cannot be opened (R3-user-wishes.md §A). Shipping
the reachability that already-built code deserves is the cheapest possible
capability delta.

The root cause — a dead router shadowing the live manifest router — is a
**regression trap**: any future view added only to the wrong file silently
becomes an orphan. So the durable deliverable is not just re-registering three
surfaces; it is a **reachability guard** that fails CI when a built top-level
view is neither reachable through the manifest nor explicitly allow-listed as
headless.

### HEAD-verification surprise (scope boundary)

The market baseline and brief also list **financial-extraction** and the
**print-job queue** as orphans. Verified at HEAD they are a *different* class:
their backends are live (`extraction#financial`/`#corrections`,
`glAccountSuggestion#suggestAccount`, routes.php L152–156; `printJob#create` …
`#updateStatus`, L108–112) but **no Vue component exists** for either
(`find src -iname "*financ*" -o -iname "*print*"` yields only the already-
registered `PrintPreview.vue`, which is unrelated). There is nothing to
*restore*: giving them a surface is net-new UI, not re-registration. They are
therefore **out of scope** here and recorded by the reachability guard's
`known-headless` allow-list so the gap stays visible rather than silently
passing. Building those surfaces is a separate change.

## What Changes

- **Delete the dead router**: remove `src/router/index.js`; the manifest-derived
  router in `src/main.js` remains the only router.
- **Reachability guard** (durable core): a build/CI unit test asserting that
  every `src/registry.js` component imports successfully, every manifest
  `type:"custom"` page `component` has a matching registry entry, and every
  top-level view under `src/views/**/` is either reachable through the
  manifest/registry or listed on an explicit `known-headless` allow-list with a
  reason. A new orphan fails CI.
- **Restore Correspondence**: register `CorrespondenceIndex` in `registry.js`,
  add a `type:"custom"` manifest page and a Templates-adjacent menu entry.
- **Restore signing authoring + verify**: register `SigningRequestForm`
  (signer-chain create) and `SignatureVerification` (verify) as reachable pages
  linked from the existing Signing surfaces; **mutation and identity actions are
  gated behind the existing signing security posture** — the create form may
  compose a request but its trust-bearing actions (send, provider binding,
  signer-identity assertion) remain governed by `signing-trust-rebuild` /
  `signer-identity-rails`, and `BulkSigningPanel` reachability is coordinated
  with (not duplicated by) `bulk-signing-field-builder`.
- **Restore policy reachability**: register `ProhibitionIndex`,
  `StandingConsentIndex` and their modals so they resolve and are deep-link
  reachable; **the user-facing menu label and placement are owned by
  `publication-policy-labels-and-nav`** (declared dependency), not re-specced
  here.

## Capabilities

### New Capabilities

- `orphaned-surface-restoration`: the app's built-but-unreachable manifest
  surfaces (correspondence, signing authoring/verify, policy) are reachable
  through the manifest-V2 shell, the dead legacy router is removed, and a
  reachability guard prevents the orphan class from recurring.

### Modified Capabilities

<!-- none. This change only registers existing views and deletes dead code. It
     does NOT modify signing security (signing-trust-rebuild / signer-identity-
     rails own that), the bulk field builder (bulk-signing-field-builder), or
     policy nav labels (publication-policy-labels-and-nav) — those are declared
     dependencies below, referenced not edited. -->

## Impact

- `src/router/index.js`: **deleted**.
- `src/registry.js`: `+ CorrespondenceIndex, SigningRequestForm,
  SignatureVerification, ProhibitionIndex, StandingConsentIndex` (kind:page) and
  the two policy `*FormModal` entries (kind:modal).
- `src/manifest.json`: new `type:"custom"` pages for the above; one new menu
  entry for Correspondence (signing/policy pages are deep-link/linked, no new
  top-level menu here — signing has a menu already; policy menu is owned by
  `publication-policy-labels-and-nav`).
- New `tests/unit/*` (JS): the reachability guard (Jest/vitest) — pure static
  assertion over `registry.js` + `manifest.json` + a `src/views/**` scan; runs
  without a live NC instance (the required unit seam).
- Consumes unchanged (presence of backend routes verified at HEAD):
  `CorrespondenceController`, `SigningController` (`bulkSign`/`verify`),
  `PolicyController` (10 routes). No backend behaviour changes.
- **Declared dependencies (referenced, not modified)**:
  - `signing-trust-rebuild` — trust/verification honesty; the restored create
    form and verify page must not present or mutate signatures in ways that
    contradict the security wave. Restoration lands reachability; that change
    lands the honest trust semantics.
  - `signer-identity-rails` — identity binding on request creation.
  - `bulk-signing-field-builder` — the enriched bulk-sign + field-placement
    surface; `BulkSigningPanel` reachability is coordinated with it (see
    design.md D4).
  - `publication-policy-labels-and-nav` — owns the "Publish always / never"
    labels and policy menu placement; this change supplies the reachable pages
    it will label.
- Evidence: R1-code-inventory.md §2 (dead-ended flows), §4 (verified orphans),
  Appendix (file-path anchors); R2 §D (review/authoring table-stakes); R3 §A
  (#45–64 authoring/review wishes); HEAD greps in this proposal.
