# Tasks: extraction-receipt-photo-and-invoice-agent

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Register

- [x] 1.1 Add `unattendedVerdict`, `verdictReasons`, `verdictThreshold` and `capturedFileId` to `financialExtraction` in `lib/Settings/filinq_register.json` (to 1.1.0) and bump the register version (REQ-ERP-001, REQ-ERP-003). Verify: `occ maintenance:repair` imports it and an extraction saves the four fields.

Built in this pass: the register moves to 8.52.0 with `financialExtraction` 1.1.0 and its four fields (`occ maintenance:repair` not run here, no instance). `UnattendedVerdict` judges every extraction in `extractFinancial()` before the save with the reason codes of D3; `ExtractionDuplicateLookup` finds an earlier extraction with the same supplier identifier and invoice number and counts a lookup that cannot run as a duplicate. `ExtractionVerdictEvent` is dispatched after the completed event when `callbackEvent` is true, and a failure is logged. The threshold is read from `extraction_unattended_threshold` (default 0.9, a value outside 0.6 to 1.0 reads as the default); writing it from the admin settings with the refusal message is task 3.3, still open.

## 2. Capture

- [x] 2.1 Add `lib/Service/Extraction/ReceiptCaptureService.php`: type check through `UploadPolicyService::check()`, 20 MB limit, EXIF orientation, HEIC to JPEG with Imagick or 415, store under `Receipts/<yyyy>/<mm>/`, then `extractFinancial()` (D1, D2) (REQ-ERP-001, REQ-ERP-002). Verify: PHPUnit with a rotated JPEG, a HEIC fixture with and without HEIC support, and a ZIP named `.jpg`.
  `ReceiptCaptureService` with `PhotoNormaliser` (EXIF orientation with GD, HEIC through Imagick or 415); `capturedFileId` is stored on the extraction. PHPUnit: `ReceiptCaptureServiceTest`, `PhotoNormaliserTest`.
- [x] 2.2 Add `POST api/extraction/capture` to `ExtractionController` with `#[NoAdminRequired]`, storing only in the session user's Files (REQ-ERP-001). Verify: Newman multipart request answers 201 and the file exists for that user only.
  `ExtractionController::capture()`, documented in `openapi.json`; PHPUnit in `ExtractionControllerTest`. The Newman request needs a live instance and is not run.

## 3. Verdict

- [x] 3.1 Add `lib/Service/Extraction/UnattendedVerdict.php` with the checks and reason codes of D3, and call it from `extractFinancial()` before the save (REQ-ERP-003). Verify: PHPUnit per reason code, the clean pass, and a verdict unchanged after a threshold change.
- [x] 3.2 Add `lib/Event/ExtractionVerdictEvent.php` and dispatch it after the completed event, logging and continuing on failure (D4) (REQ-ERP-004). Verify: PHPUnit snapshot of both payloads; the completed payload is byte-identical to before.
- [x] 3.3 Add the threshold setting `extraction_unattended_threshold` with its bounds to the settings service and `Settings.vue` (D5) (REQ-ERP-005). Verify: Playwright `tests/e2e/extraction-verdict-settings.spec.ts`.
  Saved through `SettingsService` (refused outside 0.6-1.0 by `UnattendedThresholdSetting`) and on the admin page next to inbound classification. The Playwright spec `tests/e2e/extraction-verdict-settings.spec.ts` is written (0.95 kept over a reload, 0.5 refused on the page and by the server, the original value restored); not run, it needs a live instance.

## 4. Cross-app, strings, tests and docs

- [x] 4.1 File the shillinq issues for the camera entry on its receipts screen and for acting on the verdict event (proposal, cross-app dependencies). Verify: both issue links are in the PR body.
  - Filed as ConductionNL/shillinq#3243 and ConductionNL/shillinq#3244 (2026-11-24); linked in `proposal.md` and the PR body.
- [x] 4.2 Dutch and English strings (ADR-005) and `@spec` tags on every new method. Verify: `npm run lint`, `npm run check:l10n` and `composer check:strict` pass.
  English and Dutch in `l10n/en.json` and `l10n/nl.json` (rebuilt `.js`); `@spec` on every new method.
- [x] 4.3 PHPUnit at 75% or more on the new classes (ADR-009). Verify: the coverage report for the new files.
  - Coverage measured 2026-11-20 with pcov over the full unit suite (8398 tests, 0 failures), statements of the lib files carrying this change's `@spec` tag: 531 of 564, 94.1% over 10 files. `PhotoNormaliser` went from 24/37 to 26/37 (70.3%) with two new tests (a turned photo whose image cannot be decoded stays as it came; an undecodable HEIC is refused with 415). The 11 statements left are its Imagick branches, which run only where Imagick is installed.
- [ ] 4.4 A section in `docs/features/` on the capture route and the verdict, with a screenshot of the threshold setting (ADR-010). Verify: the docs page renders with the screenshot.
