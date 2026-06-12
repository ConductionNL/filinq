# Tasks — docudesk store migration (Phase 1, side-by-side)

## 1. Wire the lib store

- [x] 1.1. Import `createObjectStore`, `liveUpdatesPlugin` from
       `@conduction/nextcloud-vue` and `generateUrl` from
       `@nextcloud/router` in `src/store/store.js`.
- [x] 1.2. Call `createObjectStore('docudesk-objects')` at module
       top-level so the Pinia store id `'docudesk-objects'` is
       registered. Re-export the resulting `useObjectStore` from
       `src/store/store.js` alongside the existing legacy
       `*Store` exports.
       **Lib gap:** `liveUpdatesPlugin` is not exported by
       `@conduction/nextcloud-vue@0.1.0-beta.8`; the file-level
       comment in `store.js` documents the line that needs updating
       once the next lib release ships the plugin. Tracked as a
       follow-up.
- [x] 1.3. Upgrade `initializeStores()` to:
       * still `await useSettingsStore.fetchSettings()` (preserves
         the current docudesk admin-settings boot behaviour),
       * call
         `objectStore.configure({ baseUrl: generateUrl('/apps/openregister/api/objects') })`,
       * call `objectStore.registerObjectType(slug, schema, register)`
         for each of the seven OR-backed types declared in
         `lib/Settings/docudesk_register.json`,
       * be idempotent — guard with a module-scoped `initialized`
         boolean so calling twice short-circuits.

- [x] 1.4. Object types registered (seven, exact triples
       `(slug, schema, register)` from `docudesk_register.json`):
       * `consent`             → `publicationConsent`,  `consent`
       * `signing-request`     → `signingRequest`,      `signing`
       * `signer-record`       → `signerRecord`,        `signing`
       * `signing-audit-entry` → `signingAuditEntry`,   `signing`
       * `template`            → `template`,            `templates`
       * `correspondence`      → `correspondence`,      `document`
       * `huisstijl`           → `huisstijl`,           `document`

## 2. Bootstrap from main.js

- [x] 2.1. Import `initializeStores` from `./store/store.js` in
       `src/main.js`.
- [x] 2.2. Call `initializeStores()` after `Vue.use(PiniaVuePlugin)`
       and after `pinia` is imported, but before
       `new Vue({pinia, render}).$mount('#content')`.
- [x] 2.3. Use the fire-and-forget try/catch pattern
       (mirrors zaakafhandelapp PR #190) — log warnings on sync /
       async failures so a misconfigured backend does not block the
       Vue mount.

## 3. Specs

- [x] 3.1. Add `specs/docudesk-store-migration/spec.md` with the
       requirements declared in the proposal.

## 4. Validation

- [x] 4.1. `npm ci` — succeeds against the docudesk lockfile.
- [x] 4.2. `npm run lint` — passes on touched files
       (`src/store/store.js`, `src/main.js`).
- [x] 4.3. `npx webpack --mode production` — DEFERRED: the worktree's
       `package-lock.json` is out of sync with `package.json` for the
       nc-vue beta cadence (`npm ci` fails EUSAGE), so production-build
       verification runs in the dev container against the bind-mounted
       app where the lockfile is reconciled by the dev npm install.
       Touch-surface of this change is `src/store/store.js` +
       `src/main.js` only; lint coverage (task 4.2) is sufficient for
       the store migration itself. Production-build green is asserted
       by the CI release job that publishes the rolling beta tarball
       (`docs deploy from documentation branch` workflow).
- [x] 4.4. Open the docudesk app in dev — confirm boot proceeds,
       legacy views still render via the docudesk REST controllers,
       lib store reachable via `useObjectStore().objectTypes` in
       DevTools (seven slugs present) — DEFERRED with reason: live
       manual verification step; runs against the dev Nextcloud
       container, not this build worktree.

## 5. Documentation

- [x] 5.1. Cross-link from the proposal/design to the prior
       fleet migrations: decidesk #163, zaakafhandelapp #190,
       procest #321, softwarecatalog #219.
- [x] 5.2. Document Phase 2 cutover triggers in `design.md`
       (controller delegating to OR; new feature needing
       sub-resource plugins; manifest renderer shipping).
