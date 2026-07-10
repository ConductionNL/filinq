# Tasks: docudesk-adopt-buildmanifest-pipeline

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.

## [docudesk] ADR-037 fragment-pipeline prerequisite

### F-1. Split the monolithic manifest into fragments (M)

- [ ] F-1.1 Create `src/manifest.d/` and split `src/manifest.json`'s `pages[]`,
  `menu[]`, and `dependencies[]` into per-concern fragment files (mirroring the
  fragment layout used by a peer app already on ADR-037, e.g. pipelinq or
  procest).
- [ ] F-1.2 Wire a `require.context('./manifest.d', false, /\.json$/)`
  collector (the one permitted app-local step per ADR-044 Rule 1) and export
  the collected fragment array.
- [ ] F-1.3 Confirm `npm run check:manifest` (ADR-024 validation gate) still
  passes against the reassembled manifest.

## [docudesk] ADR-044 buildManifest adoption

### B-1. Replace the inline pipeline with the shared util (M)

- [ ] B-1.1 Import `buildManifest` from `@conduction/nextcloud-vue` in
  `src/main.js`.
- [ ] B-1.2 Delete `mergeMenuItems` (`src/main.js:81`), `applyMenuRelocations`
  (`:121`), `applyMenuSections` (`:169`), `applyMenuRemovals` (`:191`),
  `applySettingsSection` (`:218`), and `applyMenuLayout` (`:245`).
- [ ] B-1.3 Replace the `const manifest = applyMenuLayout(bundledManifest)`
  call site with `const manifest = buildManifest(bundledManifest, fragments,
  menuLayout)` (or the library's actual call signature — confirm against
  `@conduction/nextcloud-vue`'s current `buildManifest` docs/tests before
  wiring).
  - **Acceptance:** `src/main.js` contains zero re-implementations of the five
    forbidden functions; `grep -n "function mergeMenuItems\|function
    applyMenuRelocations\|function applyMenuRemovals\|function
    applySettingsSection" src/main.js` returns nothing.

### B-2. No-functionality-loss verification (ADR-044 Rule 5) (S)

- [ ] B-2.1 Before/after route inventory: list every route resolvable from
  `bundledManifest` pre-migration vs. the `buildManifest` output
  post-migration; diff MUST be empty (menu-layout.json's relocations /
  sections / removals / settingsSection are all currently empty, so output
  should be byte-identical modulo the pipeline implementation).
- [ ] B-2.2 Manual or Playwright smoke test: navigate all 13 manifest pages
  via the in-app nav and confirm each resolves (no 404/blank page).

## [docudesk] Dead navigation code removal

### D-1. Remove orphaned pre-manifest nav components (S)

- [ ] D-1.1 Delete `src/navigation/MainMenu.vue` — confirmed unreferenced
  outside its own file definition (repo-wide grep for `MainMenu\b` in `src/`
  returns only the file itself).
- [ ] D-1.2 Delete `src/navigation/FolderFilesNavigation.vue` — confirmed
  unreferenced as a component (repo-wide grep for `FolderFilesNavigation\b`
  in `src/` returns only the file itself plus two code-comment mentions in
  `src/store/modules/anonymization.js` and
  `src/views/anonymization/AnonymizationWidget.vue` that describe the concept,
  not an import).
  - **Acceptance:** `npm run build` succeeds with both files removed; no
    unresolved-import errors.
- [ ] D-1.3 If `src/navigation/` is now empty, remove the directory.
