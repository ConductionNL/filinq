# ocr-trigger-surface Specification (delta)

---
status: proposed
---

## Purpose

The invocation surface for Filinq's existing local Tesseract OCR engine
(`OcrService`): an API route, a "Run OCR" UI action on scanned/image
documents, an automatic OCR fallback inside the anonymisation extract
pipeline (so scans get entity detection instead of silently skipping —
GDPR Art. 5(1)(f) integrity: undetected PII in scans is unprotected PII),
honest persisted per-file OCR status, and end-to-end enforcement of the
existing admin settings (enable toggle, languages, DPI). OpenRegister
remains the single owner of chunking and entity detection and performs no
OCR; Filinq's Tesseract path is the local OCR provider. All OCR runs
100% on the server (no external services).

## ADDED Requirements

### Requirement: OCR API route (REQ-DDOCR-001)

The system MUST provide `POST /api/ocr/{fileId}` running OCR on the given
file via the existing `OcrService::processFile()`, and
`GET /api/ocr/{fileId}` returning the file's persisted OCR status. The
POST response MUST include `ocrProcessed`, `confidence`, `textLength`,
and the `languages`/`dpi` used, and MUST NOT include the extracted text
itself. Error contract: HTTP 409 when OCR is disabled by the admin
toggle; HTTP 503 when OCR is enabled but Tesseract is not installed;
HTTP 404 for an unknown/inaccessible file; HTTP 400 for a MIME type that
is not an OCR candidate (`needsOcr()` semantics). Both routes MUST
declare their auth posture and MUST resolve the file through the
requesting user's folder (no cross-user file access).

#### Scenario: Manual OCR of a scanned PDF succeeds

- GIVEN OCR enabled, Tesseract installed, and a scanned PDF in the user's files
- WHEN `POST /api/ocr/{fileId}` is called
- THEN the response reports `ocrProcessed: true` with a confidence score and text length
- AND the response contains no extracted text body
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

#### Scenario: Disabled toggle blocks the route

- GIVEN the admin has set `ocr_enabled` to off
- WHEN `POST /api/ocr/{fileId}` is called
- THEN the system returns HTTP 409 stating OCR is disabled
- @e2e exclude admin-config API error contract; covered by PHPUnit (tests/unit/Controller/OcrControllerTest.php)

#### Scenario: Missing Tesseract yields 503

- GIVEN OCR enabled but no Tesseract binary on the host
- WHEN `POST /api/ocr/{fileId}` is called
- THEN the system returns HTTP 503 naming the missing capability
- @e2e exclude host-capability error contract; covered by PHPUnit (tests/unit/Controller/OcrControllerTest.php)

#### Scenario: Non-candidate MIME is rejected

- GIVEN a `.docx` file (not an OCR-candidate MIME)
- WHEN `POST /api/ocr/{fileId}` is called
- THEN the system returns HTTP 400
- @e2e exclude API validation contract; covered by PHPUnit (tests/unit/Controller/OcrControllerTest.php)

### Requirement: "Run OCR" UI action (REQ-DDOCR-002)

The system MUST offer a "Run OCR" action on OCR-candidate documents
(image MIME types and PDFs) in the MyDocuments file listing and the
in-app file viewer. The action MUST be rendered only when OCR is enabled
and Tesseract is available (server-verified again on invocation — UI
visibility is not authorization). While running, the action MUST show a
busy state; on completion the file's OCR badge (status + confidence) MUST
update without a page reload; on failure the error reason MUST be shown
to the user.

#### Scenario: User runs OCR from MyDocuments

- GIVEN a scanned PDF listed in MyDocuments with `ocrProcessed: false`
- WHEN the user triggers "Run OCR" on the file
- THEN a busy state is shown while OCR runs
- AND on completion the file row shows an OCR badge with the confidence score
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

#### Scenario: Action hidden when OCR is disabled

- GIVEN the admin has disabled OCR
- WHEN the user opens MyDocuments
- THEN no "Run OCR" action is offered on any file
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

### Requirement: Automatic OCR fallback in the anonymisation extract pipeline (REQ-DDOCR-003)

`AnonymizationService::extractAndDetectEntities()` MUST, after
OpenRegister text extraction completes, evaluate
`OcrService::needsOcr(mimeType, extractedText)` for the file; when it
returns true (image MIME, or PDF whose extraction yielded empty text) and
OCR is enabled and available, the pipeline MUST run
`OcrService::processFile()` and hand the recovered text to OpenRegister
for chunking and entity detection (REQ-DDOCR-004), so that
OCR-recovered entities flow through the SAME post-processing as native
extractions (grondslag proposals, prohibition/standing-consent matching,
risk level). Born-digital files whose extraction yielded text MUST NOT
trigger OCR (no behaviour change). A candidate file for which OCR cannot
run (disabled, unavailable, or zero text recovered) MUST be explicitly
flagged in the extract response (`ocrSkipped` with a reason) — never
silently reported as "no entities".

#### Scenario: Scanned PDF gets entity detection via fallback

- GIVEN OCR enabled and a scanned PDF containing a name and a BSN, uploaded to the anonymisation flow
- WHEN extraction runs
- THEN OR extraction yields empty text, the OCR fallback runs, and the recovered text is ingested
- AND the review shows the detected PERSON and BSN entities with proposals and policy matches applied
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

#### Scenario: Born-digital PDF skips OCR

- GIVEN a born-digital PDF with embedded text
- WHEN extraction runs
- THEN no OCR is performed and extraction behaves exactly as before this change
- @e2e exclude no-op regression path; covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

#### Scenario: Scan with OCR unavailable is flagged, not silent

- GIVEN Tesseract not installed and a scanned PDF in the anonymisation flow
- WHEN extraction runs
- THEN the extract response carries `ocrSkipped` with reason `tesseract_unavailable`
- AND the review UI shows a warning that the document could not be scanned for entities
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

### Requirement: OCR text enters OpenRegister detection via the ingestion seam (REQ-DDOCR-004)

Recovered OCR text MUST be handed to OpenRegister through a provided-text
ingestion seam (an `extractFromProvidedText(int $fileId, string $text)`
style API on OR's `TextExtractionService`) that chunks, persists and runs
entity recognition for the file, keeping OpenRegister the single owner of
chunk storage and entity detection. Filinq MUST NOT write OR chunk
persistence directly and MUST NOT run its own entity detection over OCR
text. Until the OR seam is available at runtime, the pipeline MUST
degrade fail-flagged: the extract response and review UI mark the file
`ocrDetectionPending` (OCR ran; detection could not consume the text),
and the file MUST NOT be reported as reviewed-clean.

#### Scenario: Ingested OCR text produces OR-detected entities

- GIVEN the OR provided-text seam available and a scanned PDF whose OCR recovered text
- WHEN the fallback ingests the text
- THEN OR persists chunks for the file and `EntityRelationMapper::findEntitiesForFile()` returns the detected entities
- @e2e exclude cross-app seam integration; covered by PHPUnit integration tests with OpenRegister installed (tests/integration)

#### Scenario: Seam absent degrades fail-flagged

- GIVEN an OpenRegister version without the provided-text seam
- WHEN the fallback runs OCR on a scan
- THEN the extract response carries `ocrDetectionPending: true`
- AND the review UI shows the pending-detection warning for the document
- @e2e exclude version-skew degradation branch; covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: Honest persisted OCR status per file (REQ-DDOCR-005)

Every completed OCR run (manual or fallback) MUST persist an `ocrResult`
OpenRegister object keyed by `fileId` (re-runs update it) recording
`confidence`, `languages`, `dpi`, `textLength`, `ocrProcessedAt`,
`triggeredBy` (`manual` | `fallback`) and `engineVersion`. The object
MUST NOT store the OCR text itself (data minimisation — the text lives in
OR chunks like all extracted text). `FileListingService` MUST derive
`ocrProcessed` and `ocrConfidence` from the `ocrResult` object — the
MIME-heuristic fabrication of these fields MUST be removed. Files that
are OCR candidates without an `ocrResult` MUST report
`ocrProcessed: false` plus `ocrAvailable: true` so the UI can offer the
action.

#### Scenario: File listing reflects a real OCR run

- GIVEN a scanned PDF that was OCR'd at confidence 91.4
- WHEN the file listing is queried
- THEN the file reports `ocrProcessed: true` and `ocrConfidence: 91.4`
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

#### Scenario: Candidate without a run is no longer faked

- GIVEN an extracted (non-OCR'd) born-digital PDF
- WHEN the file listing is queried
- THEN the file reports `ocrProcessed: false` and `ocrConfidence: null`
- AND `ocrAvailable: true`
- @e2e exclude listing-shape regression contract; covered by PHPUnit (tests/unit/Service/FileListingServiceTest.php)

### Requirement: Admin settings drive every OCR run (REQ-DDOCR-006)

The system MUST apply the existing admin settings to every OCR invocation
on every surface (route, UI action, pipeline fallback) via the existing
`OcrService` reads: the enable toggle (`ocr_enabled`), the language
models (`ocr.default_languages`, legacy `ocr_languages` fallback, default
`nld+eng`) and the DPI (`ocr.default_dpi`, legacy `ocr_dpi` fallback,
default 300). Changed settings MUST take effect on the next run without
restart. The persisted `ocrResult` MUST record the languages and DPI
actually used.

#### Scenario: Custom languages are applied and recorded

- GIVEN the admin sets the OCR languages to `nld+deu`
- WHEN a user runs OCR on a scan
- THEN Tesseract runs with `nld+deu`
- AND the file's `ocrResult` records `languages: "nld+deu"`
- @e2e exclude settings pass-through; covered by PHPUnit (tests/unit/Service/OcrServiceTest.php, OcrControllerTest.php)

#### Scenario: Toggle change takes effect immediately

- GIVEN OCR was enabled and a user has MyDocuments open
- WHEN the admin disables OCR and the user triggers a (stale) Run OCR action
- THEN the server returns HTTP 409 and the UI surfaces the disabled state
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts
