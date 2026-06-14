# Proposal — docudesk adopts `@conduction/nextcloud-vue` `useObjectStore`

## Why

Project memory rule **"Store pattern guidance — Do not use custom
stores; use Options API with `createObjectStore`"** flags every
Conduction app that ships hand-rolled pinia stores instead of using
the lib's `useObjectStore`/`createObjectStore`. The canonical failure
case is **decidesk #162**: when an app rolls a parallel object store,
the lib's sub-resource plugins (`liveUpdatesPlugin`,
`auditTrailsPlugin`, `filesPlugin`, `relationsPlugin`) cannot activate
against it. The runtime consequence is silent feature loss:
notify_push live updates never propagate, the audit-trail tab loads
empty, file widgets fail to attach.

Three companion apps in the fleet just merged this exact migration:

* **decidesk PR #163** — full thin-wrap migration (the canonical
  reference).
* **softwarecatalog PR #219** — spec-only follow-up that documented
  the already-completed plugin-based migration.
* **procest PR #321** — call-site migration of phantom
  `getObjects` / `getAll` etc. to the lib's API.
* **zaakafhandelapp PR #190** — Phase 1 side-by-side (lib store added
  next to thirteen legacy controller-backed stores).

`docudesk` is the last app in the fleet that has not adopted the lib
store. Its current `src/store/` has **no generic OR-CRUD store at
all** — it ships seven docudesk-specific Pinia stores
(`anonymization`, `batchAnonymization`, `consent`,
`folderAnonymization`, `settings`, `signing`, `template`,
`navigation`) that each call docudesk-specific REST controllers
(`/apps/docudesk/api/{anonymization,consents,signing,templates,settings}`).
None of those endpoints match the OR canonical shape
`/apps/openregister/api/objects/{register}/{schema}/{id}`. So docudesk
cannot collapse onto a single OR-backed store today, but it _can_
adopt the lib store **side-by-side** so manifest pages, lib
sub-resource plugins, and any future OR-backed code path bind to a
known-shape store — which is exactly what zaakafhandelapp PR #190
established as the Phase 1 pattern.

## What Changes

* **ADD** the lib's `useObjectStore` to docudesk's pinia ecosystem
  via `createObjectStore('docudesk-objects')` re-exported from
  `src/store/store.js`. Pinia store id `'docudesk-objects'` is
  distinct from the lib's default (`'conduction-objects'`) and from
  every docudesk legacy store id, so no collision. **Lib gap:**
  `liveUpdatesPlugin` is not yet exported by
  `@conduction/nextcloud-vue@0.1.0-beta.8` (the version pinned in
  docudesk's lockfile); it will be wired in via a follow-up once the
  next lib release exposes the plugin.
* **ADD** an `initializeStores()` upgrade in `src/store/store.js` that:
  * still calls `useSettingsStore.fetchSettings()` (preserves the
    docudesk admin-settings boot fetch),
  * calls `objectStore.configure({ baseUrl: generateUrl('/apps/openregister/api/objects') })`,
  * calls `objectStore.registerObjectType(slug, schema, register)`
    for every OR-backed docudesk type declared by
    `lib/Settings/docudesk_register.json`:
    * `consent` → register `consent`, schema `publicationConsent`
    * `signing-request` → register `signing`, schema `signingRequest`
    * `signer-record` → register `signing`, schema `signerRecord`
    * `signing-audit-entry` → register `signing`, schema
      `signingAuditEntry`
    * `template` → register `templates`, schema `template`
    * `correspondence` → register `document`, schema
      `correspondence`
    * `huisstijl` → register `document`, schema `huisstijl`
* **WIRE** `initializeStores()` from `src/main.js` (it isn't called
  there today; the loose `App.vue` boot path keeps it idle until a
  consumer touches a store, which means the lib store would not be
  pre-configured for the manifest renderer or sub-resource plugins).
* **KEEP** every legacy docudesk store unchanged. The docudesk
  feature stores
  (`anonymization`, `batchAnonymization`, `consent`,
  `folderAnonymization`, `signing`, `template`) talk to
  docudesk-specific controllers and follow internal workflow
  semantics (queue processing, batch lifecycle, signing audit
  trails) that have no lib equivalent.
* **KEEP** `useSettingsStore` exactly as decidesk PR #163 preserved
  its app-specific settings store. Docudesk's settings store talks
  to `/apps/docudesk/api/settings`, exposes `hasOpenRegisters` and
  `isAdmin`, and gates admin-only UI; replacing it with a generic
  lib store would conflate two different concerns.
* **DOCUMENT** the side-by-side pattern + Phase 2 cutover triggers
  (any docudesk legacy controller rewritten to OR shape; any new
  feature requiring lib sub-resource plugins on a legacy store
  path).

This change is **purely additive** — zero existing imports change,
zero existing Vue files modified, zero behavioural regression for
the seven legacy stores.

## Impact

### Affected specs

* **NEW** `specs/docudesk-store-migration/spec.md` — declares the
  store-adoption requirements: lib-store presence,
  `liveUpdatesPlugin` enablement, registered object types, base URL,
  re-export shape, side-by-side invariant, Phase 2 follow-up
  triggers.

### Affected code

* `src/store/store.js` — additive: lib re-export + boot-helper
  upgrade.
* `src/main.js` — single new call to `initializeStores()` before
  `$mount`.
* No legacy store, no Vue consumer, no `apiEndpoint` constant
  modified.

### Affected behaviour

* **Manifest pages and lib components resolve a known-shape store.**
  Any consumer that imports `useObjectStore` from
  `'../store/store.js'` gets a fully-configured `'docudesk-objects'`
  pinia store with the seven OR types pre-registered.
* **`liveUpdatesPlugin` activation deferred** to the lib-release
  follow-up (the plugin is not exported by beta.8). Once shipped,
  consumers can call `subscribe(type, id?)` / `unsubscribe(handle)`
  without `try/catch` polling fallbacks.
* **Decidesk #162 class of bug cannot occur** for new docudesk code
  paths that opt onto the lib store.
* **Legacy stores keep working.** Zero behavioural regression for
  any of the 11 Vue files importing `anonymizationStore`,
  `consentStore`, `signingStore`, `templateStore`, etc. from
  `./store/store.js`.

### Citations

* Project memory: **"Store pattern guidance — Do not use custom
  stores; use Options API with `createObjectStore`"** (file
  `feedback_store-pattern.md`).
* Decidesk **issue #162** — canonical "live-updates plugin can't
  activate; `fetchObject` is missing" example for an app-rolled
  object store.
* Decidesk **PR #163** — reference thin-wrap migration.
* zaakafhandelapp **PR #190** — reference side-by-side migration
  (the closer template for docudesk).
* procest **PR #321** — companion call-site migration.
* softwarecatalog **PR #219** — spec-only follow-up.
* OR registers/schemas: `lib/Settings/docudesk_register.json`
  (registers: `consent`, `signing`, `templates`, `document`).
* Lib API: `@conduction/nextcloud-vue@0.1.0-beta.8`
  (exports `useObjectStore`, `createObjectStore`,
  `liveUpdatesPlugin`).
