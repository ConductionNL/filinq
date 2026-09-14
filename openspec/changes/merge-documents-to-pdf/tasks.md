# Tasks: merge-documents-to-pdf

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Schema

- [ ] 1.1 Add `mergeJob` to the `document` register in `lib/Settings/filinq_register.json` with the D1 properties and the `queued`, `running`, `done`, `failed` lifecycle (REQ-DMG-01)
  - `hardValidation: true`; register version bumped so `importFromApp()` picks it up.

## 2. Service, job and endpoint

- [ ] 2.1 Add `lib/Service/DocumentMergeService.php`: convert through `PdfConversionService`, concatenate with FPDI, cover page through `PdfService`, bookmarks, PDF/A-3b result (REQ-DMG-02)
  - Server-side read check per input and write check on the target folder.
- [ ] 2.2 Add `lib/BackgroundJob/MergeDocumentsJob.php` and the `mergeSyncThresholdPages` setting (REQ-DMG-03)
- [ ] 2.3 Add `lib/Controller/MergeController.php` with `POST /api/merge` and `GET /api/merge/{id}`; routes registered in `appinfo/routes.php` before the catch-all (REQ-DMG-03)

## 3. Leaf

- [ ] 3.1 Register `filinq-merge-to-pdf`: `RegisterLeafProvidersEvent` listener plus `registerIntegration()` with `bulkAction` and `widget` (REQ-DMG-04)
- [ ] 3.2 Build the action dialog: drag order, cover template picker, bookmarks toggle, result name (REQ-DMG-04)

## 4. Quality

- [ ] 4.1 PHPUnit for `DocumentMergeService` (order, bookmarks, failed conversion, refused read) inside the container; 75% on new code (ADR-009)
- [ ] 4.2 Playwright `tests/e2e/merge-to-pdf.spec.ts`; Dutch and English strings; docs in `docs/features/merge-to-pdf.md` with screenshots
- [ ] 4.3 Tell dossiq the leaf id and props so `documents-on-the-case` places the bulk action
