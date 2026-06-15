# Design — docudesk store migration (side-by-side)

status: pr-created

## Context

`docudesk` ships **eight** hand-rolled Pinia stores under
`src/store/modules/`:

| Module                     | Pinia id              | Talks to                                                | OR-shape? |
| ---                        | ---                   | ---                                                     | ---       |
| `anonymization.js`         | `anonymization`       | `/apps/docudesk/api/anonymization/{upload,extract,…}`   | No        |
| `batchAnonymization.js`    | `batchAnonymization`  | `/apps/docudesk/api/anonymization/batch/{upload,…}`     | No        |
| `consent.js`               | `consent`             | `/apps/docudesk/api/consents`                           | No        |
| `folderAnonymization.js`   | `folderAnonymization` | `/apps/docudesk/api/anonymization/batch/folder`         | No        |
| `navigation.ts`            | `navigation`          | (UI shell — no backend)                                 | n/a       |
| `settings.js`              | `settings`            | `/apps/docudesk/api/settings`                           | No        |
| `signing.js`               | `signing`             | `/apps/docudesk/api/signing/{requests,bulk,verify,…}`   | No        |
| `template.js`              | `template`            | `/apps/docudesk/api/templates`                          | No        |

None of those eight stores match the lib's
`${baseUrl}/${register}/${schema}/${id}` URL contract, and most
expose workflow-specific surface (file queues, batch lifecycle,
signing audit trails, version history) that has no lib equivalent.
The `useObjectStore`'s base API (`fetchCollection`, `fetchObject`,
`saveObject`, `deleteObject`, sub-resource plugins) is generic
register/schema CRUD; it is _not_ a workflow engine.

At the same time, docudesk **does** declare four OR registers in
`lib/Settings/docudesk_register.json`:

| Register     | Schemas                                                      |
| ---          | ---                                                          |
| `consent`    | `publicationConsent`                                         |
| `signing`    | `signingRequest`, `signerRecord`, `signingAuditEntry`        |
| `templates`  | `template`                                                   |
| `document`   | `correspondence`, `huisstijl`                                |

So docudesk has **OR-backed object types** (seven of them) that any
future manifest page or lib component would want to fetch through
the lib store, but **today no Vue file imports `useObjectStore` at
all** — and no in-app store proxies those OR endpoints. Result: a
greenfield surface for the lib store, with the legacy
docudesk-specific workflow stores running in parallel.

## Migration patterns considered

### A. Full migration (rejected)

Mass-rewrite the eight legacy stores onto the lib's
`useObjectStore`. **Out of scope.** The legacy stores are workflow
engines (`addFiles → uploadFile → extractEntities → anonymize →
finalize`, `startFolderBatch → startPolling → fetchEntities →
anonymizeBatch`, etc.); the lib's generic CRUD API is not a
substitute. Each legacy store would need a full rewrite as a custom
plugin or facade — multi-week effort, no net win for Phase 1.

### B. Thin-wrap (rejected)

Re-export an `objectStore` shim that proxies the lib but returns
docudesk-specific data shapes. **Rejected** — there is no caller
today that uses such a shim, and adding one would create an unused
abstraction.

### C. Side-by-side (chosen)

Mirror the **zaakafhandelapp PR #190** pattern: ADD the lib store
to the app's pinia ecosystem alongside the eight legacy stores;
register the seven OR-backed object types from
`docudesk_register.json` against the lib store at boot; keep every
legacy store and every legacy Vue consumer untouched.

This is the **explicit Phase 1** of an eventual two-phase migration.
Phase 2 retires individual legacy stores per-feature once the
backend controllers either delegate to OR or are rewritten to OR
shape.

## Implementation

### `src/store/store.js`

Keep every existing legacy export verbatim. Add:

1. Imports of `useObjectStore` and `liveUpdatesPlugin` from
   `@conduction/nextcloud-vue`, plus `generateUrl` from
   `@nextcloud/router`.
2. A `createObjectStore('docudesk-objects')` call at module
   top-level so the Pinia id `'docudesk-objects'` is registered
   exactly once. Re-export `useObjectStore` (now re-bound to that
   custom id).
   **Lib gap (deferred):** `liveUpdatesPlugin()` is not exported by
   `@conduction/nextcloud-vue@0.1.0-beta.8` (only
   `auditTrailsPlugin`, `relationsPlugin`, `filesPlugin`,
   `lifecyclePlugin`, `selectionPlugin`, `searchPlugin` ship in
   beta.8). The plugin exists on the lib's `beta` branch but has
   not been released. Wiring is deferred to a follow-up once the
   next lib release exposes it; the file-level comment in
   `src/store/store.js` documents the exact line that needs
   updating.
3. Upgrade `initializeStores()` to:
   * `await useSettingsStore.fetchSettings()` (preserves existing
     boot behaviour),
   * `objectStore.configure({ baseUrl: generateUrl('/apps/openregister/api/objects') })`,
   * iterate over the seven OR object-type registrations:

     ```js
     objectStore.registerObjectType('consent',              'publicationConsent', 'consent')
     objectStore.registerObjectType('signing-request',      'signingRequest',     'signing')
     objectStore.registerObjectType('signer-record',        'signerRecord',       'signing')
     objectStore.registerObjectType('signing-audit-entry',  'signingAuditEntry',  'signing')
     objectStore.registerObjectType('template',             'template',           'templates')
     objectStore.registerObjectType('correspondence',       'correspondence',     'document')
     objectStore.registerObjectType('huisstijl',            'huisstijl',          'document')
     ```

     The first argument is the docudesk consumer-facing slug; the
     second is the schema slug from `docudesk_register.json`; the
     third is the register slug.
   * Be idempotent — guard with a module-scoped `initialized`
     boolean so calling twice is harmless. (Mirrors zaakafhandelapp
     PR #190 pattern.)

### `src/main.js`

Add a `try`/`catch` `initializeStores()` invocation before
`new Vue(...).$mount(...)`, mirroring the zaakafhandelapp pattern:
the store call is fire-and-forget; the boot mount stays unblocked;
synchronous and async failures both log a warning instead of
crashing the bundle.

### Why no manifest changes

Docudesk does NOT yet have a `src/manifest.json` page renderer (no
`zaakafhandelapp-manifest-v1` equivalent has shipped). The
side-by-side adoption purely makes the lib store **available**; it
does not bind the manifest renderer. That's a Phase 2 concern that
will land jointly with a future docudesk-manifest change.

## Boundary lines

* **In scope (Phase 1)**:
  * `src/store/store.js` — additive: lib re-export + boot-helper
    upgrade.
  * `src/main.js` — one new call.
  * `openspec/changes/docudesk-store-migration/` — change docs.

* **Out of scope (deferred)**:
  * Migrating any of the 8 legacy Pinia stores.
  * Replacing any docudesk REST controller with an OR-backed route.
  * Wiring the lib store into a manifest renderer (no manifest yet).
  * Modifying any of the 11 Vue files that import legacy stores.

## Risks & mitigations

1. **Pinia store-id collision.** The lib's default id is
   `'conduction-objects'`. Docudesk uses
   `'anonymization'`, `'batchAnonymization'`, `'consent'`,
   `'folderAnonymization'`, `'navigation'`, `'settings'`, `'signing'`,
   `'template'`. The lib store gets the **distinct** id
   `'docudesk-objects'` (mirrors decidesk's `'decidesk-objects'`
   precedent in PR #163), so no collision can occur with either the
   legacy stores or with a future embedded openregister sidebar.
2. **Bundle-size delta.** The lib's `useObjectStore` is already
   pulled in transitively via `CnVersionInfoCard`, `CnDetailPage`,
   `CnIndexPage`, `CnDashboardPage`, `CnStatsBlock`, `CnStatusBadge`
   (all imported from `@conduction/nextcloud-vue` by the existing
   docudesk views). Adding a direct import in `store.js` does not
   pull new code into the bundle.
3. **Boot ordering.** `initializeStores()` MUST run after
   `Vue.use(PiniaVuePlugin)` is registered and `pinia` is
   constructed. The existing `src/main.js` order is preserved; the
   new `initializeStores()` call is placed **after** the `Vue.use`
   line and **before** `new Vue({pinia, ...}).$mount`.
4. **Settings-store fetch latency.** `useSettingsStore.fetchSettings()`
   is awaited inside `initializeStores()` today; that behaviour is
   preserved. The new `objectStore.configure` and
   `registerObjectType` calls are synchronous and add no extra
   network round-trips.
5. **`liveUpdatesPlugin` deferred.** The plugin is not exported by
   `@conduction/nextcloud-vue@0.1.0-beta.8`; consumers calling
   `store.subscribe(...)` against the Phase 1 store would get a
   `TypeError`. The follow-up that bumps the lib version + adds the
   plugin is gated on the next lib release. Until then, any
   docudesk feature needing notify_push wiring keeps using its own
   polling/SSE path.

## Phase 2 cutover triggers

Phase 2 should land when **any** of these become true for a
specific docudesk feature:

1. The docudesk REST controller behind one of the 7 OR types is
   rewritten to delegate to OR (e.g. `ConsentCrudService` switches
   to OR's `/apps/openregister/api/objects/consent/publicationConsent`).
2. A new feature requires the lib's sub-resource plugins
   (live updates, audit, files, relations) on a legacy store path.
3. A docudesk manifest renderer ships and the manifest page binds
   to the lib store.

When Phase 2 lands, the legacy store + Vue files for that one
feature retire; the Phase 1 boot wiring stays intact — only the
specific legacy store and its REST controller(s) retire.

## Per-store classification

| Store                                   | Phase 1 fate | Phase 2 candidate? |
| ---                                     | ---          | ---                |
| `anonymization` (queue / pipeline)      | KEEP         | No — workflow engine, no lib equivalent |
| `batchAnonymization` (batch lifecycle)  | KEEP         | No — workflow engine |
| `consent` (consent CRUD)                | KEEP         | **Yes** — once `ConsentCrudService` delegates to OR |
| `folderAnonymization` (folder pipeline) | KEEP         | No — workflow engine |
| `navigation` (UI shell)                 | KEEP         | No — UI state, not OR data |
| `settings` (admin settings)             | KEEP        | No — talks to docudesk-specific `/api/settings` |
| `signing` (signing requests CRUD + sign / decline / bulk / verify / audit) | KEEP | **Yes** for the CRUD slice — once the controller delegates to OR |
| `template` (template CRUD + versioning + lock) | KEEP | **Yes** for the CRUD slice — once the controller delegates to OR |

The three "Yes for CRUD slice" candidates retain their legacy
workflow surface (sign, decline, bulk, verify, restore-version,
acquire-lock) even after CRUD migrates — those are
docudesk-specific actions with no lib equivalent.

## Citations

* Project memory: `feedback_store-pattern.md` — "Do not use custom
  stores; use Options API with `createObjectStore`".
* Decidesk **issue #162** — canonical missing-`fetchObject` bug.
* Decidesk **PR #163** — full thin-wrap migration; `'docudesk-objects'`
  store id mirrors `'decidesk-objects'`.
* zaakafhandelapp **PR #190** — Phase 1 side-by-side pattern.
* procest **PR #321** — call-site migration of phantom store calls.
* softwarecatalog **PR #219** — spec-only follow-up.
* OR registers: `lib/Settings/docudesk_register.json`.
* Lib: `@conduction/nextcloud-vue@0.1.0-beta.8`
  `src/store/useObjectStore.js`,
  `src/store/plugins/liveUpdates.js`.
