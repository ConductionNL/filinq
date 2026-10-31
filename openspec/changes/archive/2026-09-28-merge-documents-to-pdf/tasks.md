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

- [x] 3.1 Register `filinq-merge-to-pdf`: `RegisterMergeToPdfLeafListener` through `IntegrationLeafRegistrar`, and `registerMergeToPdfLeaf()` in `main.js` and the `filinq-leaves` bundle (REQ-DMG-04)
  - Built 2026-09-28 as a render surface on `detail-page` and `single-entity`, not a `bulkAction`: the shared registry has no bulk-action slot (design D3, corrected). Test: `tests/unit/EventListener/RegisterMergeToPdfLeafListenerTest.php` (descriptor, both halves agree, registrar wires it).
- [x] 3.2 Build the action dialog: choose and order the documents, cover template picker, bookmarks toggle, result name (REQ-DMG-04)
  - `src/integrations/CnFilinqMergeToPdfWidget.vue` over `src/services/mergeSelection.js`; move up and move down replace drag, so the order can be set with a keyboard. Test: `tests/vitest/mergeSelection.spec.js`.

## 4. Quality

- [x] 4.1 PHPUnit for `DocumentMergeService` (order, bookmarks, failed conversion, refused read) inside the container; 75% on new code (ADR-009)
- [x] 4.2 Playwright `tests/e2e/workflows/merge-to-pdf.spec.ts` covers the merge, the refusal, the empty request and the queued job; the leaf strings (six locales) and `docs/features/merge-to-pdf.md` landed with the leaf on 2026-09-28
- [x] 4.3 Tell dossiq the leaf id and props so `documents-on-the-case` places the leaf
  - Told in ConductionNL/dossiq#3185 (id, props, surfaces, and that it is a case-page leaf, not a Files-tab bulk action).
