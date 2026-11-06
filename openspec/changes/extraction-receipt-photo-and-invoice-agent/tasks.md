# Tasks: extraction-receipt-photo-and-invoice-agent

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Register

- [ ] 1.1 Add `unattendedVerdict`, `verdictReasons`, `verdictThreshold` and `capturedFileId` to `financialExtraction` in `lib/Settings/filinq_register.json` (to 1.1.0) and bump the register version (REQ-ERP-001, REQ-ERP-003). Verify: `occ maintenance:repair` imports it and an extraction saves the four fields.

## 2. Capture

- [ ] 2.1 Add `lib/Service/Extraction/ReceiptCaptureService.php`: type check through `UploadPolicyService::check()`, 20 MB limit, EXIF orientation, HEIC to JPEG with Imagick or 415, store under `Receipts/<yyyy>/<mm>/`, then `extractFinancial()` (D1, D2) (REQ-ERP-001, REQ-ERP-002). Verify: PHPUnit with a rotated JPEG, a HEIC fixture with and without HEIC support, and a ZIP named `.jpg`.
- [ ] 2.2 Add `POST api/extraction/capture` to `ExtractionController` with `#[NoAdminRequired]`, storing only in the session user's Files (REQ-ERP-001). Verify: Newman multipart request answers 201 and the file exists for that user only.

## 3. Verdict

- [ ] 3.1 Add `lib/Service/Extraction/UnattendedVerdict.php` with the checks and reason codes of D3, and call it from `extractFinancial()` before the save (REQ-ERP-003). Verify: PHPUnit per reason code, the clean pass, and a verdict unchanged after a threshold change.
- [ ] 3.2 Add `lib/Event/ExtractionVerdictEvent.php` and dispatch it after the completed event, logging and continuing on failure (D4) (REQ-ERP-004). Verify: PHPUnit snapshot of both payloads; the completed payload is byte-identical to before.
- [ ] 3.3 Add the threshold setting `extraction_unattended_threshold` with its bounds to the settings service and `Settings.vue` (D5) (REQ-ERP-005). Verify: Playwright `tests/e2e/extraction-verdict-settings.spec.ts`.

## 4. Cross-app, strings, tests and docs

- [ ] 4.1 File the shillinq issues for the camera entry on its receipts screen and for acting on the verdict event (proposal, cross-app dependencies). Verify: both issue links are in the PR body.
- [ ] 4.2 Dutch and English strings (ADR-005) and `@spec` tags on every new method. Verify: `npm run lint`, `npm run check:l10n` and `composer check:strict` pass.
- [ ] 4.3 PHPUnit at 75% or more on the new classes (ADR-009). Verify: the coverage report for the new files.
- [ ] 4.4 A section in `docs/features/` on the capture route and the verdict, with a screenshot of the threshold setting (ADR-010). Verify: the docs page renders with the screenshot.
