# Tasks: ocr-trigger-surface

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `ocrResult` schema (`fileId` idempotency key, `confidence`, `languages`, `dpi`, `textLength`, `ocrProcessedAt`, `triggeredBy`, `engineVersion`) to `lib/Settings/filinq_register.json` — additive, union-merge only; never stores OCR text
- [ ] 1.2 Seed one scanned-PDF sample (tests/sample-documents) with its `ocrResult` so badge/status render on a clean install

## 2. Backend

- [ ] 2.1 Add `OcrController` with `POST /api/ocr/{fileId}` and `GET /api/ocr/{fileId}` wrapping `OcrService::processFile()`, persisting `ocrResult`, with the 409/503/404/400 error contract (REQ-DDOCR-001)
  - Auth attributes on every method (route-auth gate); file resolved via the requesting user's folder only (no-admin-idor gate); response carries `textLength`, never the text.
- [ ] 2.2 File an OpenRegister issue + PR for the provided-text ingestion seam (`extractFromProvidedText(int $fileId, string $text)`) that chunks, persists and runs entity recognition (REQ-DDOCR-004)
  - Cross-app dependency; verify against OR HEAD at apply time — do not assume it landed.
- [ ] 2.3 Add the OCR fallback branch to `AnonymizationService::extractAndDetectEntities()`: `needsOcr()` after OR extraction → `processFile()` → ingest via the OR seam; fail-flagged degradation (`ocrSkipped` reasons, `ocrDetectionPending` when the seam is absent) (REQ-DDOCR-003, REQ-DDOCR-004)
  - OCR-recovered entities flow through the existing proposals/policy-match/risk post-processing unchanged.
- [ ] 2.4 Replace the `FileListingService` MIME heuristic with `ocrResult`-derived `ocrProcessed`/`ocrConfidence` + `ocrAvailable` (REQ-DDOCR-005)
  - `FileEntitiesDashboardWidget` keeps working (field names/types unchanged).

## 3. Frontend

- [ ] 3.1 "Run OCR" action in MyDocuments and the file viewer with busy state, badge update without reload, and error surfacing; hidden when disabled/unavailable but always server-re-checked (REQ-DDOCR-002, REQ-DDOCR-006)
- [ ] 3.2 Review-flow warnings for `ocrSkipped` and `ocrDetectionPending` documents so scans are never silently "clean" (REQ-DDOCR-003, REQ-DDOCR-004)

## 4. Quality

- [ ] 4.1 PHPUnit for controller error contract, fallback branching, degradation flags, `ocrResult` persistence and listing derivation — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with OpenRegister + Tesseract in the container: upload scan → fallback OCR → entities in review; and the manual Run-OCR path.
- [ ] 4.2 Playwright spec `tests/e2e/spec-coverage/ocr-trigger.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 4.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 4.4 Docs: `docs/features/ocr.md` with Playwright screenshots (Run OCR action, badge, review warnings, admin settings) and the Filinq↔OpenRegister OCR division of labour (ADR-010)
- [ ] 4.5 Validate: `openspec validate ocr-trigger-surface --strict` passes; hydra gates green (orphaned-write gate should now pass for OcrService)
