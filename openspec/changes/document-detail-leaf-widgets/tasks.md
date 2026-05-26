# Tasks: document-detail-leaf-widgets

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.
NO apply in this change — implementation runs through Hydra later.

## [docudesk] Surface integration leaves on the document detail record

### D-1. Render contacts / activity / shares leaf tabs via the registry (M)

- [ ] D-1.1 On the document/report detail page (ADR-001), mount the integration registry's
  enabled leaf tabs/widgets for the document object — contacts (role-grouped person chips),
  activity (stream), shares (current NC shares) — sourced from
  `IntegrationRegistry::getEnabled()` via the shared `@conduction/nextcloud-vue` registry tab
  host. Do NOT author a parallel per-document tab/widget system.
  - **Acceptance:** With Contacts + activity + sharing available, the three leaf tabs render on
    the document detail page; with an app absent, its tab is hidden and the page renders without
    error. App-owned `Anonimisatie` / `Redactie` / `Handtekeningen` tabs remain present.

### D-2. i18n + tests (S)

- [ ] D-2.1 Provide nl + en translations for any new UI strings (tab labels) per ADR-007 /
  ADR-025.
  - **Acceptance:** Both `l10n/en.json` and `l10n/nl.json` carry the new keys.
- [ ] D-2.2 Component/integration test asserting the registry tabs render on the document detail
  page when their leaves are enabled and are hidden when absent.
  - **Acceptance:** Tests pass; no duplicate sidebar-tab system introduced.
