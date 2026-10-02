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
  - The event and its listener are in place, so a feeder is one dispatch away. The folder watch itself is wired in `scan-intake-with-separator-sheets`, which owns paper intake.

## 3. Page and leaf

- [x] 3.1 Add the `intake` manifest page with preview, assign dialog and reject dialog (REQ-DII-04)
- [ ] 3.2 Register the `filinq-document-intake` leaf: `RegisterLeafProvidersEvent` listener plus `registerIntegration()` with tab and widget (REQ-DII-05)
  - BLOCKED, MEASURED 2026-09-18: filinq consumes no `RegisterLeafProvidersEvent`
    and `webpack.config.js` declares no `leaves` entry. A leaf registered without
    that entry is DARK: the host renders nothing while the registration reports
    success. Both leaf tasks in this repo wait on the same groundwork.

## 4. Quality

- [x] 4.1 PHPUnit for `IntakeService` (assign, reject, refused assign) inside the container; 75% on new code (ADR-009)
- [x] 4.2 Playwright e2e `tests/e2e/intake-inbox.spec.ts` for the inbox page and both actions
- [ ] 4.3 Dutch and English strings (ADR-005); docs in `docs/features/document-intake.md` with screenshots (ADR-010)
- [ ] 4.4 Tell dossiq the leaf id and props so it can place the leaf (dossiq change `consume-filinq-intake-leaf`)
