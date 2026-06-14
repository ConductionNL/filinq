# Specification — `docudesk-store-migration` (Phase 1, side-by-side)

@e2e exclude Frontend store-wiring internals: pinia store id/exports, useObjectStore barrel adoption, type registration, configure() base URL, boot-fetch and no-collision invariants — module-level concerns with no browser-rendered surface. Covered by Vitest unit tests on the store module.

## Purpose

Phase 1 (side-by-side) adoption of the shared `@conduction/nextcloud-vue` `useObjectStore` as docudesk's canonical OR-CRUD store, registering OR-backed object types at boot while preserving every legacy store and the docudesk-specific settings store. The cutover to the shared store (Phase 2) is documented but deferred.

## Requirements

### Requirement: REQ-DSM-1 lib `useObjectStore` is the canonical OR-CRUD store

Docudesk SHALL adopt `@conduction/nextcloud-vue`'s `useObjectStore`
as the canonical generic object store for any code path needing
register-and-schema-based CRUD, sub-resource plugins (live updates,
audit trails, files, relations), or a future manifest-driven page
renderer.

#### Scenario: lib store is exported from the app's store barrel

- **GIVEN** a Vue file in `src/`
- **WHEN** it imports `useObjectStore` from `'../store/store.js'`
  (or the equivalent relative path)
- **THEN** the import SHALL resolve to a `useObjectStore` produced
  by `createObjectStore('docudesk-objects')` at module load
- **AND** the returned pinia store SHALL have id
  `'docudesk-objects'`
- **AND** the store SHALL expose `fetchCollection`, `fetchObject`,
  `saveObject`, `deleteObject`, `getCollection`, `getObject`,
  `getCachedObject`, `isLoading`, `getError`, `getPagination`,
  `getSchema`, `getRegister`, `getFacets`, `setSearchTerm`,
  `clearError`, `resolveReferences`, `registerObjectType`,
  `unregisterObjectType`, `configure`, `createObjectTypeSlug`
  (the lib's documented base API).

### Requirement: REQ-DSM-2 Live updates plugin enablement deferred

Phase 1 MUST ship without the live-updates plugin: the shared store SHOULD be created with `liveUpdatesPlugin()` once the plugin ships in `@conduction/nextcloud-vue`, but Beta.8 does not export it. A follow-up change SHALL bump the lib version and add the plugin to the
`createObjectStore` options at the line marked by the
file-level comment in `src/store/store.js`.

#### Scenario: Plugin not yet wired (current state)

- **GIVEN** the installed `@conduction/nextcloud-vue@0.1.0-beta.8`
- **WHEN** a consumer inspects the lib store's plugin list
- **THEN** `liveUpdatesPlugin` SHALL NOT be present (deferred to
  the lib-version follow-up)
- **AND** the file-level comment in `src/store/store.js` SHALL
  reference this gap and the exact line to update on bump.

#### Scenario: Subscribe is a real method (post-bump)

- **GIVEN** a future change has bumped the lib to a release that
  exports `liveUpdatesPlugin` and updated the
  `createObjectStore` call accordingly
- **WHEN** a Vue component obtains the store via `useObjectStore()`
  and calls `store.subscribe('consent', consentId)`
- **THEN** the call SHALL NOT throw `TypeError: ... is not a function`
- **AND** the returned handle SHALL be acceptable to
  `store.unsubscribe(handle)`.

### Requirement: REQ-DSM-3 OR-backed object types registered at boot

`initializeStores()` SHALL register every OR-backed docudesk
object type declared in `lib/Settings/docudesk_register.json`
against the lib store, populating slug / schema / register triples
verbatim from that file.

The required minimum set is seven types:

| Slug                  | Schema                | Register     |
| ---                   | ---                   | ---          |
| `consent`             | `publicationConsent`  | `consent`    |
| `signing-request`     | `signingRequest`      | `signing`    |
| `signer-record`       | `signerRecord`        | `signing`    |
| `signing-audit-entry` | `signingAuditEntry`   | `signing`    |
| `template`            | `template`            | `templates`  |
| `correspondence`      | `correspondence`      | `document`   |
| `huisstijl`           | `huisstijl`           | `document`   |

#### Scenario: Type registration covers all OR registers

- **GIVEN** `initializeStores()` has resolved
- **WHEN** `objectStore.objectTypes` is read
- **THEN** the array SHALL contain all seven slugs above.

#### Scenario: Configure call uses canonical OR base URL

- **GIVEN** `initializeStores()` has resolved
- **WHEN** the lib store's internal `baseUrl` is inspected
- **THEN** it SHALL equal
  `generateUrl('/apps/openregister/api/objects')`.

#### Scenario: Idempotent

- **GIVEN** `initializeStores()` has run once
- **WHEN** it is invoked a second time within the same session
- **THEN** it SHALL short-circuit without throwing
- **AND** SHALL NOT re-register the seven object types
- **AND** SHALL NOT re-fetch settings.

### Requirement: REQ-DSM-4 Settings store preserved

The docudesk-specific `useSettingsStore` (`src/store/modules/settings.js`) SHALL remain in place, since it talks to `/apps/docudesk/api/settings` and exposes `{ config, openRegisters, isAdmin }` used to gate admin-only UI.
`initializeStores()` SHALL still `await useSettingsStore(pinia)
.fetchSettings()` before the lib-store wiring runs.

#### Scenario: Settings store still importable

- **GIVEN** `src/store/store.js`
- **WHEN** a component imports
  `{ useSettingsStore } from '../store/store.js'`
- **THEN** the import SHALL resolve and return the same Pinia store
  as `import { useSettingsStore } from '../store/modules/settings.js'`.

#### Scenario: Settings fetch still happens at boot

- **GIVEN** `src/main.js` has called `initializeStores()`
- **WHEN** the returned promise resolves
- **THEN** `useSettingsStore().initialized` SHALL be `true`
- **AND** `useSettingsStore().config` SHALL contain the docudesk
  settings response payload.

### Requirement: REQ-DSM-5 Legacy stores preserved side-by-side

Phase 1 SHALL be additive. Every legacy store currently exported
from `src/store/store.js` SHALL continue to be exported unchanged.
Every Vue file that imports a legacy store SHALL continue to
function identically.

#### Scenario: Legacy exports unchanged

- **GIVEN** the post-migration `src/store/store.js`
- **WHEN** a consumer imports any of
  `navigationStore`, `consentStore`, `anonymizationStore`,
  `batchAnonymizationStore`, `folderAnonymizationStore`, or
  `useSettingsStore`
- **THEN** the import SHALL resolve to the same Pinia store the
  consumer received before this change
- **AND** the legacy stores' actions and state SHALL remain
  unchanged.

#### Scenario: No Pinia store-id collision

- **GIVEN** the post-migration store ecosystem
- **WHEN** any consumer instantiates the lib store and any legacy
  store within the same pinia
- **THEN** there SHALL be no Pinia store-id collision (the lib
  uses `'docudesk-objects'`; the legacy stores use IDs
  `'anonymization'`, `'batchAnonymization'`, `'consent'`,
  `'folderAnonymization'`, `'navigation'`, `'settings'`,
  `'signing'`, `'template'`).

### Requirement: REQ-DSM-6 Boot order preserved

`src/main.js` SHALL call `initializeStores()` after
`Vue.use(PiniaVuePlugin)` and before `new Vue(...).$mount(...)`,
using a fire-and-forget try/catch pattern that logs failures
without blocking the mount.

#### Scenario: Mount is not blocked

- **GIVEN** `initializeStores()` is awaited inside `main.js`
- **WHEN** the awaited fetch fails
- **THEN** `new Vue(...).$mount(...)` SHALL still execute
- **AND** the failure SHALL be logged to the browser console as a
  warning.

### Requirement: REQ-DSM-7 Phase 2 cutover triggers documented

The change SHALL document the explicit triggers for Phase 2 (the
follow-up that retires individual legacy stores per-feature). Phase
2 SHALL NOT be attempted in this change.

#### Scenario: Phase 2 trigger list is normative

- **GIVEN** the design.md of the
  `docudesk-store-migration` change
- **WHEN** a future agent decides whether to file the Phase 2
  follow-up for a specific feature
- **THEN** the design.md SHALL list the trigger conditions:
  (a) the docudesk REST controller behind the feature delegates to
  OR,
  (b) a new feature requires lib sub-resource plugins (live
  updates, audit, files, relations) on a legacy store path,
  (c) a docudesk manifest renderer ships and the manifest page
  binds to the lib store
- **AND** matching any one trigger SHALL be sufficient grounds to
  schedule Phase 2 for that feature.
