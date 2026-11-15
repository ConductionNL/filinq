# Tasks: intake-ocr-on-arrival

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 7. -->

## 1. Backend

- [x] 1.1 `OcrService::processFile()` sends an image to `extractTextFromImage()` only (D5) (REQ-IOA-004). Verify: PHPUnit with an image MIME type asserts the PDF path is not called.
- [x] 1.2 Add `lib/BackgroundJob/IntakeOcrJob.php`: `reading`, `processFile()`, then `read` with `contentText` or `failed` with a reason, all through `IntakeReadingProgress::progressFor()` (D2) (REQ-IOA-002, REQ-IOA-003). Verify: PHPUnit with the real `IntakeReadingProgress` for each outcome.
- [x] 1.3 `IntakeService::receive()` queues the job for a new document whose file needs OCR, and sets `readingState` to `queued`; not for a repeat delivery, not when `ocr_on_arrival` is off (D1, D4) (REQ-IOA-001). Verify: PHPUnit on `receive()` with the real `IJobList` method names.
- [x] 1.4 Add `ocr_on_arrival` to `SettingsService` (default on) and its toggle on the admin settings page (D4) (REQ-IOA-001). Verify: PHPUnit on the settings round trip.

## 2. Register and screens

- [x] 2.1 Add `contentText` to `intakeDocument`, a seed value, and a register version bump; validate a job payload against the real schema (D3). Verify: a register test validates the written payload.
- [x] 2.2 Show the reading state per row on `IntakeIndex.vue` (REQ-IOA-003). Verify: unit test on the label mapping.

## 3. Strings and docs

- [x] 3.1 Dutch and English strings for the new labels and reasons, `@spec` tags on the new methods, and a section in `docs/features/`. Verify: `npm run lint`, `composer check:strict`, `npm run test:l10n`.
