# Tasks: scan-intake-with-separator-sheets

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Schema and settings

- [ ] 1.1 Add `scanBatch` to the `document` register in `lib/Settings/filinq_register.json` with the D1 properties and the `received`, `split`, `failed` lifecycle (REQ-SCI-01)
  - `hardValidation: true`; register version bumped so `importFromApp()` picks it up.
- [ ] 1.2 Add `scanProfiles[]` to the admin settings with one watched folder per profile (D2)

## 2. Separator sheets

- [ ] 2.1 Add `lib/Service/SeparatorSheetService.php` rendering the sheets through `PdfService` with a locally drawn QR code (REQ-SCI-02)
- [ ] 2.2 Add the print action and dialog on the intake page (REQ-SCI-02)

## 3. Batch split

- [ ] 3.1 Add `lib/Service/ScanBatchService.php` with `receive` and `split` over FPDI, QR and blank-page detection, and the intake event per segment (REQ-SCI-03)
- [ ] 3.2 Point the watched-folder job at `ScanBatchService::receive()` for profile folders; the OCR watch keeps its own folders (D2)

## 4. Inbox

- [ ] 4.1 Pre-fill the assign picker from `sourceRef` on the intake page and the leaf (REQ-SCI-04)

## 5. Quality

- [ ] 5.1 PHPUnit for `ScanBatchService` with fixture batches (two separators, none, one undecodable page) inside the container; 75% on new code (ADR-009)
- [ ] 5.2 Playwright `tests/e2e/scan-separators.spec.ts` and the pre-fill case in `tests/e2e/intake-inbox.spec.ts`; Dutch and English strings; docs in `docs/features/scan-intake.md` with screenshots
