# ocr-document-scanning Specification (delta)

---
status: proposed
---

## Purpose

Corrects the OCR metadata requirement to match reality and this change's
wiring: at HEAD, `FileListingService` fabricates `ocrProcessed` from a
MIME heuristic with `ocrConfidence` hardcoded null, and the capability's
claimed integration with `AnonymizationService::extractAndDetectEntities()`
does not exist (OcrService's only caller is the scan-en-herken financial
pipeline). With `ocr-trigger-surface`, per-file OCR metadata becomes a
persisted record and the anonymisation integration becomes real
(REQ-DDOCR-003/004/005).

## MODIFIED Requirements

### Requirement: OCR Metadata (REQ-OCR-05)

**Priority:** MUST

The system SHALL report OCR confidence scores and track an ocrProcessed flag per file, derived from a persisted per-file `ocrResult` OpenRegister object written by every completed OCR run (manual trigger or anonymisation-pipeline fallback — see `ocr-trigger-surface` REQ-DDOCR-005). The file listing SHALL NOT infer OCR status from MIME type or processing status: `ocrProcessed` SHALL be true only when an `ocrResult` exists for the file, and `ocrConfidence` SHALL be the recorded Tesseract mean confidence (0-100) from that object. OCR-candidate files without an `ocrResult` SHALL report `ocrProcessed: false` with no confidence score and `ocrAvailable: true`.

#### Scenario: Report confidence score
- GIVEN OCR was performed on a file
- WHEN the file listing is queried
- THEN a confidence score (0-100) reflecting Tesseract mean confidence SHALL be reported from the file's persisted `ocrResult`
- AND the ocrProcessed flag SHALL be true
- @e2e tests/e2e/spec-coverage/ocr-trigger.spec.ts

#### Scenario: Non-OCR files
- GIVEN a file that did not require OCR
- WHEN the file listing is queried
- THEN ocrProcessed SHALL be false
- AND no confidence score SHALL be reported
- @e2e exclude listing-shape contract; covered by PHPUnit (tests/unit/Service/FileListingServiceTest.php)

#### Scenario: OCR candidate not yet processed is not faked
- GIVEN a scanned PDF that has been uploaded but never OCR'd
- WHEN the file listing is queried
- THEN ocrProcessed SHALL be false and ocrAvailable SHALL be true
- AND no confidence score SHALL be reported
- @e2e exclude listing-shape contract; covered by PHPUnit (tests/unit/Service/FileListingServiceTest.php)
