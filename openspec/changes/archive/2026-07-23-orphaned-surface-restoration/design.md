# Design: orphaned-surface-restoration

## Context

Verified at HEAD (DocuDesk `spec/market-gap-wave3-2026-07`):

- The app boots the manifest-V2 shell. `src/main.js` builds the vue-router from
  `manifest.pages` (`routesFromManifest()`, `mode:'history'`, base
  `/apps/docudesk`) and mounts `App` with `{manifest, customComponents,
  pageTypes, registry}`. `customComponents` is derived from the `kind:'page'`
  entries of `src/registry.js`.
- `src/router/index.js` (54 lines) declares Dashboard, Anonymization, Consent,
  Templates, MyDocuments, Print, **Policy (Prohibitions/StandingConsents)**,
  Signing (list/detail/**form/bulk/verify**), **Correspondence**, Comparison,
  Gallery. It imports `vue-router` and exports a `new Router(...)`. **Nothing
  imports it** — confirmed by grep. It is dead code that lists the orphans.
- `src/registry.js` registers 14 page components. It does **not** include
  `CorrespondenceIndex`, `SigningRequestForm`, `BulkSigningPanel`,
  `SignatureVerification`, `ProhibitionIndex`, `StandingConsentIndex`.
- `src/manifest.json` `pages[]` has no correspondence/policy/signing-authoring
  pages; `menu[]` has no correspondence/policy entries.
- Backend routes for all three families are live in `appinfo/routes.php`
  (correspondence L121–123; `signing#bulkSign`/`#verify` L147–148; policy
  L82–93). Components exist on disk (`ls src/views/{correspondence,policy,
  signing}`).
- Financial extraction (`extraction#*`, `glAccountSuggestion#*`, L152–156) and
  print-job queue (`printJob#*`, L108–112) have live routes but **no Vue
  component** — a distinct "headless backend" class, not a router-orphan.

## Goals / Non-Goals

**Goals:**

- Make the three built-UI orphan families reachable through the manifest shell.
- Delete the dead router so it can no longer shadow the manifest router.
- Add a reachability guard that turns "a built view is unreachable" from a
  silent runtime 404 into a CI failure.
- Keep signing trust semantics owned by the open security wave — restore
  *reachability*, gate *mutation/trust* behind existing posture.

**Non-Goals:**

- No backend changes; no new controllers, services or routes.
- No signing crypto / trust / identity work (owned by `signing-trust-rebuild`,
  `signer-identity-rails`).
- No bulk-sign field-placement builder (owned by `bulk-signing-field-builder`).
- No policy menu labels/placement (owned by `publication-policy-labels-and-nav`).
- No net-new financial-extraction or print-queue UI (headless class; out of
  scope — allow-listed by the guard).

## Decisions

### D1 — Delete the dead router, keep the manifest router

`src/router/index.js` is removed outright. `src/main.js` already owns routing
(`routesFromManifest`), so deletion is inert at runtime and removes the shadow
that made these views look "wired" while being unreachable. The reachability
guard (D2) then enforces that no view depends on a re-introduced side router.

### D2 — Reachability guard (durable core, the unit seam)

A JS unit test (`tests/unit/reachability.spec.js`, Jest/vitest — the test
runner already present for the shell) asserts, statically, against source files
(no live NC, no browser):

1. **Registry integrity**: every `src/registry.js` entry has a defined
   `component` (import resolves) and a recognised `kind`
   (`page|modal|widget|form-field|cell-renderer`).
2. **Manifest ↔ registry**: every manifest page with `type:"custom"` has a
   `component` string present as a `kind:"page"` registry entry; every
   `kind:"page"` registry entry is referenced by at least one manifest page
   (no dangling registrations).
3. **No hidden orphans**: every top-level view file under `src/views/**/` whose
   default export is a page-level view is EITHER reachable (referenced by a
   manifest page through the registry) OR listed in an explicit
   `KNOWN_HEADLESS` allow-list constant with a one-line reason.
4. **`known-headless` allow-list** seeds with the two verified headless
   backends (financial-extraction, print-queue) — documented as "backend live,
   no built UI, tracked separately", so the guard neither false-positives on
   them nor lets them pass invisibly.

Rationale: the guard is what stops the orphan class recurring. It is
file-static, so it runs in CI in milliseconds and is the change's testable
seam. Legacy shell scaffolding (`src/views/Views.vue`,
`src/navigation/MainMenu.vue`, `FileNavigation.vue`) that only ever imported the
now-deleted router is removed alongside the router or allow-listed as
`legacy-shell-unused` — the guard forces that decision to be explicit.

### D3 — Correspondence restoration

- `registry.js`: `CorrespondenceIndex: { kind:'page', component: CorrespondenceIndex }`.
- `manifest.json` page: `{ id:"Correspondence", route:"/correspondence",
  type:"custom", title:"Correspondence", component:"CorrespondenceIndex" }`.
- `menu[]`: `{ id:"Correspondence", label:"Correspondence", icon:"icon-mail",
  route:"Correspondence", order:55 }` (between Templates and Signing). Menu
  `route` is the page id per the manifest contract (verified: existing menu
  entries reference `route` = page id, e.g. `SigningRequests`).

No new data path: `CorrespondenceIndex.vue` already calls the correspondence
store/controller. This is pure re-wiring.

### D4 — Signing authoring + verify restoration (gated)

- `registry.js`: `SigningRequestForm` and `SignatureVerification` as
  `kind:'page'`.
- `manifest.json` pages (no new top-level menu — the Signing menu already
  exists; these are reached from the Signing index/detail):
  - `{ id:"SigningRequestForm", route:"/signing/new", type:"custom",
    component:"SigningRequestForm" }` — the signer-chain create form, launched
    from the Signing index "New" action.
  - `{ id:"SignatureVerification", route:"/signing/verify/:fileId",
    type:"custom", component:"SignatureVerification" }` — read-only verify page
    over the live `signing#verify/{fileId}` route, linked from a request detail.
- **Gating (touch discipline):** reachability ≠ new trust surface. The restored
  form composes a request and the verify page displays what
  `SigningController::verify()` already returns. Any action that *asserts or
  mutates trust* — sending for signature, binding a provider, asserting signer
  identity, presenting a verification verdict as authoritative — remains
  governed by the in-flight security wave. Concretely: the create form's
  send/provider/identity controls defer their semantics to
  `signing-trust-rebuild` + `signer-identity-rails`; where those changes have
  not yet landed, the restored form exposes only non-trust-bearing composition
  (draft the request) and the verify page renders the backend verdict verbatim
  with a "verification provided by the signing engine" attribution rather than
  minting a new trust claim. This keeps restoration from silently re-opening the
  forgeable-signer surface the security wave exists to close.
- **`BulkSigningPanel`**: coordinated with `bulk-signing-field-builder`, which
  owns the enriched bulk-sign + field-placement experience. To avoid speccing a
  surface another active change is actively rebuilding, `BulkSigningPanel` is
  **not** registered by this change; it stays on the guard's allow-list as
  `owned-by:bulk-signing-field-builder` until that change registers its
  successor. (Reachability of bulk-sign is delivered there, not duplicated
  here.)

### D5 — Policy reachability (labels deferred)

- `registry.js`: `ProhibitionIndex`, `StandingConsentIndex` (kind:page); their
  `ProhibitionFormModal`, `StandingConsentFormModal` (kind:modal).
- `manifest.json` pages (deep-link reachable, **no menu entry here**):
  - `{ id:"Prohibitions", route:"/policy/prohibitions", type:"custom",
    component:"ProhibitionIndex" }`
  - `{ id:"StandingConsents", route:"/policy/standing-consents", type:"custom",
    component:"StandingConsentIndex" }`
- The menu label/placement is **owned by `publication-policy-labels-and-nav`**
  ("Publish always / never"). This change makes the pages resolvable and
  deep-link reachable (fixing the orphan / satisfying the guard); that change
  decides how they surface in navigation. Registering the pages without a menu
  entry is a clean seam: the guard passes (components resolve, pages exist), and
  no naming decision is pre-empted.

## Registry / manifest contract (ADR-036)

Registry keys equal the manifest `component` strings. Manifest schema refs (in
`type:"index"` pages) use SLUG form — not touched here (all restored pages are
`type:"custom"`, driven by existing stores/controllers). Modals live in their
own files under `src/views/policy/` already and are imported by their index
views; registering them as `kind:"modal"` follows the registry's documented
modal-resolution path.

## Declarative vs imperative

- **Declarative**: registry entries + manifest pages/menu (the reachability is
  data).
- **Imperative (test)**: the reachability guard (static assertion over source).
- No imperative backend logic is added.

## Security Considerations

- No new routes, so no new auth surface. All restored pages call controllers
  that already enforce their own auth (`PolicyController`, `SigningController`,
  `CorrespondenceController`).
- Signing trust actions are gated (D4) so restoration cannot re-expose the
  forgeable-signer path the security wave is closing.
- The verify page renders the backend verdict verbatim; it does not compute or
  assert its own signature validity.

## Risks / Trade-offs

- [Restoring the signing create form before `signing-trust-rebuild` lands could
  imply trust the wave hasn't yet secured] → mitigated by D4: only non-trust
  composition is enabled until the wave lands; trust controls defer to it.
- [Policy pages reachable without a menu could look "half-wired"] → accepted:
  the menu is owned by `publication-policy-labels-and-nav`; deep-link
  reachability is the correct minimal fix and unblocks that change.
- [The guard's `src/views/**` scan could false-positive on helper components] →
  the guard only treats default-exported page-level views as candidates and
  supports the explicit allow-list; helpers/sub-components are excluded.

## Migration Plan

Additive + one deletion. Delete `src/router/index.js` (+ any legacy shell files
whose only importer was it); add registry/manifest entries; add the guard test.
No data, no schema, no route changes. Rollback = restore the file and revert
registry/manifest (the guard test would then fail, which is the point).

## Open Questions

- Financial-extraction and print-queue surfaces (headless) — net-new UI, a
  separate change; allow-listed here so the gap is visible, not built.
- Whether legacy shell files (`Views.vue`, `MainMenu.vue`,
  `FolderFilesNavigation.vue`, `FileNavigation.vue`) should be deleted now or
  allow-listed as `legacy-shell-unused` — provisional decision: delete the ones
  whose sole importer was the dead router; allow-list any with a lingering
  import until a follow-up removes it. The guard forces the choice to be
  explicit either way.
