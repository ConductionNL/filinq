# Tasks: document-detail-leaf-widgets

All tasks are `[filinq]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.
NO apply in this change — implementation runs through Hydra later.

## [filinq] Surface integration leaves on the document detail record

### D-1. Render contacts / activity / shares leaf tabs via the registry (M)

- [x] D-1.1 On the document/report detail page (ADR-001), mount the integration registry's
  enabled leaf tabs/widgets for the document object — contacts (role-grouped person chips),
  activity (stream), shares (current NC shares) — sourced from
  `IntegrationRegistry::getEnabled()` via the shared `@conduction/nextcloud-vue` registry tab
  host. Do NOT author a parallel per-document tab/widget system.
  - **Acceptance:** With Contacts + activity + sharing available, the three leaf tabs render on
    the document detail page; with an app absent, its tab is hidden and the page renders without
    error. App-owned `Anonimisatie` / `Redactie` / `Handtekeningen` tabs remain present.
  - 🔴 THE RECORDED BLOCK WAS FALSE, AND IT HELD THESE THREE TASKS FOR MONTHS. It said the
    registry tab host and the `IntegrationRegistry` API were "not yet shipped in
    `@conduction/nextcloud-vue`". They were shipped, and they were shipped in the very version
    this app installs: `useIntegrationRegistry`, `CnIntegrationTab`, `CnObjectSidebar`,
    `registerLeafIntegrations` and `VALID_SURFACES` are all exported from the package root at
    **2.39.0**, which is what `package.json` pins and what `package-lock.json` resolves. Checked
    in `node_modules`, not remembered. The lesson is the one a stale block always teaches: it
    stops anyone looking, so nobody finds out it stopped being true.
  - WHAT WAS ACTUALLY DIFFERENT, recorded so the next reader is not surprised: this change's
    design assumes the document detail surface is an OR record in a `document` register under a
    `report` schema. Filinq ships neither — the register is `filinq` and the detail surface is
    FILE-backed (the viewer opens a Nextcloud `fileId`). The per-document OR record Filinq does
    keep is `filinq/anonymizationLink`, so that is what the leaf tabs bind to, and a document
    with no link record has no object for a leaf to link against and renders no tabs at all.
  - Built as `src/components/DocumentLeafTabs.vue` (renders through `resolveTab(id)`, falling
    back to the library's `CnIntegrationTab`) plus `src/services/documentLeafTabs.js` for the
    selection rule, mounted at the foot of `src/sidebars/FileViewerSidebar.vue` BELOW the
    app-owned review surface, which is untouched.

### D-2. i18n + tests (S)

- [x] D-2.1 Provide nl + en translations for any new UI strings (tab labels) per ADR-007 /
  ADR-025.
  - **Acceptance:** Both `l10n/en.json` and `l10n/nl.json` carry the new keys.
  - The deferral reason resolved itself the moment the host was read rather than assumed: the
    TAB LABELS ARE NOT OURS TO TRANSLATE. Each label comes off the registry descriptor, already
    translated by the leaf that registered it, so hard-coding one here would override an app's
    own name for its surface. The three strings this change does own are the section heading and
    the two empty/unavailable states; those are in `l10n/en.json` + `l10n/nl.json`, with
    `l10n/*.js` rebuilt via `npm run l10n:build`.
- [x] D-2.2 Component/integration test asserting the registry tabs render on the document detail
  page when their leaves are enabled and are hidden when absent.
  - **Acceptance:** Tests pass; no duplicate sidebar-tab system introduced.
  - `tests/vitest/documentLeafTabs.spec.js`, 10 tests, asserts the selection: all three render
    when enabled, an absent app's leaf is dropped and the rest still render, an unknown
    availability is NOT treated as absent, a document with no record renders nothing, and the
    twenty-odd other integrations in the registry are not rendered. Mutation-checked three ways —
    folding unknown availability into absent, matching the anonymised file id as well as the
    source, and rendering with no record — each reddening the assertion that names it, not a
    setup line.
  - 🔑 NO COMPONENT-MOUNT HARNESS EXISTS IN THIS REPO (`@vue/test-utils` is not a dependency),
    which is why the decision lives in a module a test can call and the RENDER is asserted by
    `tests/e2e/workflows/document-detail-leaf-widgets.spec.ts` instead. Saying so here rather
    than leaving a reader to wonder why the "component test" is not one.
