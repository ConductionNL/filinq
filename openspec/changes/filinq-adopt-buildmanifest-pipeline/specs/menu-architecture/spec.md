# menu-architecture Specification (delta)

---
status: proposed
---

## Purpose

Bring Filinq's navigation-manifest assembly into conformance with ADR-044
(shared `buildManifest` pipeline) and ADR-037 (fragment-based manifest), and
remove the pre-manifest navigation components it superseded.

## ADDED Requirements

### Requirement: Navigation Manifest Assembled via Shared buildManifest

Filinq SHALL assemble its effective navigation manifest by calling
`@conduction/nextcloud-vue`'s `buildManifest(base, fragments, menuLayout)`.
Filinq MUST NOT define its own `mergeMenuItems`, `applyMenuRelocations`,
`applyMenuSections`, `applyMenuRemovals`, or `applySettingsSection`
implementations.

#### Scenario: main.js contains no re-implemented pipeline functions

- GIVEN `src/main.js` after this change
- WHEN the file is searched for `function mergeMenuItems`,
  `function applyMenuRelocations`, `function applyMenuRemovals`, or
  `function applySettingsSection`
- THEN none of these definitions SHALL be present
- AND `src/main.js` SHALL import `buildManifest` from
  `@conduction/nextcloud-vue`

#### Scenario: No route or menu entry is lost by the migration

- GIVEN the set of routes reachable via the bundled manifest before this
  change
- WHEN the manifest is rebuilt via `buildManifest` after this change
- THEN every previously-reachable route SHALL remain reachable
- AND `src/menu-layout.json`'s `relocations`, `sections`, `removals`, and
  `settingsSection` continue to behave identically (all currently empty)

### Requirement: Manifest Assembled from ADR-037 Fragments

Filinq SHALL define its manifest as `src/manifest.d/*.json` fragments
collected via `require.context`, per ADR-037 — the prerequisite for adopting
`buildManifest` (ADR-044 Decision Rule 6).

#### Scenario: Fragments replace the monolithic manifest.json

- GIVEN the manifest content that previously lived entirely in
  `src/manifest.json`
- WHEN this change ships
- THEN the same pages/menu/dependencies content SHALL be split across
  `src/manifest.d/*.json` fragment files
- AND `npm run check:manifest` SHALL still validate the reassembled manifest
  against the canonical `app-manifest.schema.json`

## MODIFIED Requirements

### Requirement: In-App Navigation Contains No Dead Components

The in-app navigation surface SHALL be composed solely of components that are
actually mounted by the manifest-driven `CnAppNav` / `CnAppRoot` shell.
Pre-manifest navigation components superseded by the manifest system MUST be
removed rather than left as dead code.

#### Scenario: Orphaned pre-manifest nav components are removed

- GIVEN `src/navigation/MainMenu.vue` and
  `src/navigation/FolderFilesNavigation.vue`, neither of which is imported by
  any other file in `src/` (confirmed by repo-wide grep before this change)
- WHEN this change ships
- THEN both files SHALL be deleted from the repository
- AND `npm run build` SHALL succeed without unresolved-import errors
