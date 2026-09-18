# Tasks: merge-documents-to-pdf

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Schema

- [x] 1.1 Add `mergeJob` to the `document` register in `lib/Settings/filinq_register.json` with the D1 properties and the `queued`, `running`, `done`, `failed` lifecycle (REQ-DMG-01)
  - `hardValidation: true`; register version bumped so `importFromApp()` picks it up.

## 2. Service, job and endpoint

- [x] 2.1 Add `lib/Service/DocumentMergeService.php`: convert through `PdfConversionService`, concatenate with FPDI, cover page through `PdfService`, bookmarks, PDF/A-3b result (REQ-DMG-02)
  - Server-side read check per input and write check on the target folder.
- [x] 2.2 Add `lib/BackgroundJob/MergeDocumentsJob.php` and the `mergeSyncThresholdPages` setting (REQ-DMG-03)
- [x] 2.3 Add `lib/Controller/MergeController.php` with `POST /api/merge` and `GET /api/merge/{id}`; routes registered in `appinfo/routes.php` before the catch-all (REQ-DMG-03)

## 3. Leaf

- [ ] 3.1 Register `filinq-merge-to-pdf`: `RegisterLeafProvidersEvent` listener plus `registerIntegration()` with `bulkAction` and `widget` (REQ-DMG-04)
  - Filinq ships no leaf infrastructure yet: no `leaves` webpack entry, no `LeafDescriptor`, no `registerIntegration`. The leaf is a change of its own rather than a webpack entry smuggled in here.
- [ ] 3.2 Build the action dialog: drag order, cover template picker, bookmarks toggle, result name (REQ-DMG-04)
  - Follows the leaf. The endpoint already takes the order, the cover template, the bookmarks toggle and the result name.

## 4. Quality

- [x] 4.1 PHPUnit for `DocumentMergeService` (order, bookmarks, failed conversion, refused read) inside the container; 75% on new code (ADR-009)
- [x] 4.2 Playwright `tests/e2e/workflows/merge-to-pdf.spec.ts` covers the merge, the refusal, the empty request and the queued job; the strings and the feature docs are still open
- [ ] 4.3 Tell dossiq the leaf id and props so `documents-on-the-case` places the bulk action
  - Follows the leaf: there is no leaf id to tell dossiq about yet.
