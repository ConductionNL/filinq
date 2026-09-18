# Tasks: scan-intake-with-separator-sheets

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Schema and settings

- [x] 1.1 Add `scanBatch` to the `document` register in `lib/Settings/filinq_register.json` with the D1 properties and the `received`, `split`, `failed` lifecycle (REQ-SCI-01)
  - `hardValidation: true`; register version bumped so `importFromApp()` picks it up.
- [x] 1.2 Add `scanProfiles[]` to the admin settings with one watched folder per profile (D2)

## 2. Separator sheets

- [x] 2.1 Add `lib/Service/SeparatorSheetService.php` rendering the sheets through `PdfService` with a locally drawn QR code (REQ-SCI-02)
- [ ] 2.2 Add the print action and dialog on the intake page (REQ-SCI-02)

## 3. Batch split

- [x] 3.1 Add `lib/Service/ScanBatchService.php` with `receive` and `split` over FPDI, QR and blank-page detection, and the intake event per segment (REQ-SCI-03)
- [ ] 3.2 Point the watched-folder job at `ScanBatchService::receive()` for profile folders; the OCR watch keeps its own folders (D2)

## 4. Inbox

- [ ] 4.1 Pre-fill the assign picker from `sourceRef` on the intake page and the leaf (REQ-SCI-04)

## 5. Quality

- [~] 5.1 PHPUnit for `ScanBatchService` with fixture batches (two separators, none, one undecodable page) inside the container; 75% on new code (ADR-009)
- [ ] 5.2 Playwright `tests/e2e/scan-separators.spec.ts` and the pre-fill case in `tests/e2e/intake-inbox.spec.ts`; Dutch and English strings; docs in `docs/features/scan-intake.md` with screenshots

## Status, 2026-09-18

**Where this came from.** The services and the controller were already written
on `feat/scan-intake-separator-sheets`, pushed at 09:10 and never opened as a
PR — a lane's work left on the branch when the lane stopped. Every checkbox
above was unticked and most of them were done. This PR lands that work and adds
the one thing it had none of: tests.

**Measured, not assumed.** `scanBatch` is in the register descriptor with the
D1 properties and the three-state lifecycle; `scanProfiles` is the admin
settings key `ScanProfileService` reads; `SeparatorSheetService` renders the
sheets and reads their payload; `ScanBatchService` receives, cuts and delivers.

**The cut is now tested, and the tests are about the failure that hides.** A
batch cut in the wrong place delivers half of one person's file appended to
somebody else's, into a case that reads as complete — nobody notices, because
every segment that comes out looks like a document. Eleven cases: two
separators, none at all, a run of them, a trailing one, the separator belonging
to neither side, the case number reaching exactly one document, an undecodable
page, and blank mode needing both no ink and no words.

**The undecodable page is the direction that matters.** It is treated as an
ORDINARY page. Reading it as a separator would cut a document in half at a
random sheet and deliver both halves as whole documents; leaving it in makes
one document too long, which a person notices immediately.

**Still open, and none of it is in this PR:**

- **3.2**, pointing the watched-folder job at `ScanBatchService::receive()`.
  There is no watched-folder job in `lib/BackgroundJob/` to point, so this is a
  job plus its registration, not a redirect. Until then a batch arrives through
  the controller only.
- **2.2 and 4.1**, both frontend: the print action and dialog on the intake
  page, and pre-filling the assign picker from `sourceRef`. The branch touched
  `src/icons.js` and nothing else under `src/`.
- **5.1 is partial**: `segmentsOf()` is covered, `receive()`, `split()` and
  `deliver()` are not. Those three write files and dispatch events, so they
  want fixture PDFs and a filesystem, which is 5.1's "inside the container"
  half.
- **5.2**, the Playwright specs, the Dutch and English strings, and
  `docs/features/scan-intake.md`.
