# Proposal: docudesk-adopt-buildmanifest-pipeline

kind: code

## Why

ADR-044 ("Menu architecture — shared buildManifest pipeline, settings-foldout,
and cards-collapse") requires: *"Apps MUST build their effective manifest via
`@conduction/nextcloud-vue` `buildManifest(base, fragments, menuLayout)`. No
app may re-implement `mergeMenuItems` / `applyMenuRelocations` /
`applyMenuRemovals` / `applySettingsSection` inline."*

DocuDesk currently ships exactly the forbidden bespoke copy. `src/main.js`
defines, inline:

- `mergeMenuItems` (`src/main.js:81`)
- `applyMenuRelocations` (`src/main.js:121`)
- `applyMenuSections` (`src/main.js:169`)
- `applyMenuRemovals` (`src/main.js:191`)
- `applySettingsSection` (`src/main.js:218`)
- `applyMenuLayout` (`src/main.js:245`) which drives all five over
  `src/menu-layout.json` and the monolithic `src/manifest.json`

This is a near-verbatim reproduction of the pipeline ADR-044 says drifted
between apps before the shared `buildManifest` util existed — the exact
duplication this ADR was written to retire. DocuDesk's own comment in
`applyMenuLayout`'s docblock (`src/main.js:236`) even says *"DocuDesk ships a
monolithic manifest (no manifest.d fragments)"*, confirming it also has not
taken the ADR-037 fragment-pipeline prerequisite (ADR-044 Decision Rule 6:
*"An app whose manifest is a single monolithic `src/manifest.json`... MUST
first adopt the ADR-037 fragment pipeline... before it can adopt
`buildManifest`"*).

Separately, two navigation Vue components are dead code left over from
DocuDesk's pre-manifest navigation, superseded by the manifest-driven
`CnAppNav` that `App.vue` now uses:

- `src/navigation/MainMenu.vue` — not imported by any other file in `src/`
  (confirmed via repo-wide grep; the only match for `MainMenu` in `src/` is
  the file's own definition).
- `src/navigation/FolderFilesNavigation.vue` — likewise never imported; the
  only other hits for `FolderFilesNavigation` in the repo are code comments
  (`src/store/modules/anonymization.js:133,540`,
  `src/views/anonymization/AnonymizationWidget.vue:351`) describing the
  *concept*, not an import of the component.

Both components also hardcode NC theme variables to fixed hex values instead
of deriving from the active theme (`MainMenu.vue:176` `--color-background-hover:
#efefef`; `:182-183` `--color-primary-element: #fff` /
`--color-primary-element-hover: #fff`; `FolderFilesNavigation.vue:136,140-141`
identical pattern) — which would additionally violate ADR-010 (NL Design /
CSS vars, no hardcoded colors) if either were ever wired back in. Removing
them alongside the pipeline migration avoids resurrecting theme-broken dead
code.

## What Changes

- Adopt the ADR-037 `manifest.d/` fragment pipeline: split `src/manifest.json`
  into `src/manifest.d/*.json` fragments collected via `require.context`
  (the one permitted app-local step).
- Replace the six inline functions in `src/main.js`
  (`mergeMenuItems`, `applyMenuRelocations`, `applyMenuSections`,
  `applyMenuRemovals`, `applySettingsSection`, `applyMenuLayout`) with a call
  to `@conduction/nextcloud-vue`'s `buildManifest(base, fragments,
  menuLayout)`.
- Keep `src/menu-layout.json` as the layout-data file (already correctly
  separated from the manifest itself, per ADR-044 Decision Rule 2) — only its
  consumer changes, not its shape.
- Delete `src/navigation/MainMenu.vue` and
  `src/navigation/FolderFilesNavigation.vue` (confirmed dead, unreferenced
  outside comments) as part of the same cleanup.
- **No functionality loss** (ADR-044 Rule 5): every page/route currently
  reachable through the bespoke pipeline's output MUST remain reachable
  through `buildManifest`'s output — this is a mechanical swap of the
  pipeline implementation, not an IA change (`menu-layout.json`'s
  `relocations` / `sections` / `removals` / `settingsSection` are all
  currently empty, so behaviour is expected to be identical pre/post).
- Not BREAKING: no route or menu entry is added, removed, or relocated by
  this change; it is purely an implementation-conformance migration.

## Out of Scope

- Any actual navigation-IA change (populating `menu-layout.json`'s
  relocations/sections/removals/settingsSection) — DocuDesk's own note in
  `menu-layout.json#_settingsSectionNote` already explains why these are
  intentionally empty today; this change does not revisit that decision.
- ADR-042 (first-time-setup wizard) adoption — tracked separately if pursued.

## Success Criteria

- `src/main.js` no longer defines `mergeMenuItems` / `applyMenuRelocations` /
  `applyMenuSections` / `applyMenuRemovals` / `applySettingsSection` /
  `applyMenuLayout`; it imports and calls `buildManifest` from
  `@conduction/nextcloud-vue` instead.
- `src/manifest.d/` exists with fragment files; `src/manifest.json` (the
  monolithic file) is removed or reduced to the base-manifest fragment.
- Every route reachable before the migration remains reachable after
  (Playwright / manual nav smoke test across all 13 manifest pages).
- `src/navigation/MainMenu.vue` and `src/navigation/FolderFilesNavigation.vue`
  no longer exist in the repo.
