# Tasks: document-intake-inbox

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Schema

- [x] 1.1 Add `intakeDocument` to the `document` register in `lib/Settings/filinq_register.json` with the D1 properties and the `received`, `assigned`, `rejected` lifecycle (REQ-DII-01)
  - `hardValidation: true`; register version bumped so `importFromApp()` picks it up.
- [x] 1.2 Seed two `received` intake documents (one `scan`, one `mail`) so the inbox is non-empty on a fresh install

## 2. Service and event

- [x] 2.1 Add `lib/Event/IntakeDocumentReceivedEvent.php` and `lib/Service/IntakeService.php` with `receive`, `assign`, `reject` (REQ-DII-02, REQ-DII-03)
  - Server-side write checks on the intake register and on the assign target.
- [ ] 2.2 Point the OCR folder watch at `IntakeService::receive()` with channel `scan`
  - Replaced by `scan-intake-from-a-watched-folder` (integriq's watcher hands files over), which waits on integriq's `WatchedFileArrivedEvent` (not on integriq `development`, checked 2026-10-09).
  - The event and its listener are in place, so a feeder is one dispatch away. The folder watch itself is wired in `scan-intake-with-separator-sheets`, which owns paper intake.

## 3. Page and leaf

- [x] 3.1 Add the `intake` manifest page with preview, assign dialog and reject dialog (REQ-DII-04)
- [x] 3.2 Register the `filinq-document-intake` leaf: `RegisterLeafProvidersEvent` listener plus `registerIntegration()` with tab and widget (REQ-DII-05)
  - Built 2026-10-09 on the leaf groundwork filinq now has: `lib/EventListener/RegisterDocumentIntakeLeafListener.php` (render surface, `detail-page` and `single-entity`, wired in `IntegrationLeafRegistrar`) and `src/integrations/registerDocumentIntakeLeaf.js` mounting `CnFilinqDocumentIntakeWidget.vue` in both bundles, under the same id. The widget lists the waiting documents, those whose `sourceRef` names the record first, and files one on the host record through filinq's own assign route; it calls nothing in the host app. Tests: tests/unit/EventListener/RegisterDocumentIntakeLeafListenerTest.php (both halves agree, registrar wires it), tests/vitest/documentIntakeLeaf.spec.js.

## 4. Quality

- [x] 4.1 PHPUnit for `IntakeService` (assign, reject, refused assign) inside the container; 75% on new code (ADR-009)
- [x] 4.2 Playwright e2e `tests/e2e/intake-inbox.spec.ts` for the inbox page and both actions
- [ ] 4.3 Dutch and English strings (ADR-005); docs in `docs/features/document-intake.md` with screenshots (ADR-010)
  - Strings: done in all six locales. Docs with screenshots: (not run: screenshots need the live instance).
- [x] 4.4 Tell dossiq the leaf id and props so it can place the leaf (dossiq change `consume-filinq-intake-leaf`)
  - Filed 2026-10-09 for the coordinator as ask 2 in `~/memcap-work/build-all/for-ruben/filinq-sibling-asks.md`: id, surfaces, props and the assign route.
