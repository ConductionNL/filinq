---
status: in-progress
---

# OCR Document Scanning

**Status**: in-progress
**Scope**: filinq
**OpenSpec changes**:
- [ocr-trigger-surface](../../changes/archive/2026-09-29-ocr-trigger-surface/) _(archived 2026-09-29)_ — wires the engine: OCR API route + Run-OCR UI action + anonymisation-pipeline fallback; REQ-OCR-05 corrected from the file-listing MIME heuristic to persisted per-file `ocrResult` objects (kind: code)

## Purpose

Provides OCR (Optical Character Recognition) text extraction from scanned and image-based documents using Tesseract OCR. Enables the anonymization pipeline to process scanned PDFs, TIFF, PNG, and JPG files by extracting text locally before entity detection. All processing runs 100% on the server with no external cloud dependencies, in compliance with Filinq's local processing standard.

@e2e exclude pure backend OCR service (OcrService wraps Tesseract) — no dedicated UI surface; extraction, detection, configuration and degradation behavior verified by PHPUnit.

## Requirements

### Requirement: OCR Text Extraction (REQ-OCR-01)

**Priority:** MUST

The system SHALL extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG) using Tesseract OCR, running 100% locally on the server with no external cloud service calls.

#### Scenario: Extract text from a scanned PDF
- GIVEN a scanned PDF containing no embedded text
- WHEN OcrService processes the file
- THEN each page SHALL be converted to an image via Imagick and run through Tesseract
- AND the extracted text SHALL be returned

#### Scenario: Extract text from an image file
- GIVEN an image file (PNG, JPG, or TIFF)
- WHEN OcrService processes the file
- THEN Tesseract SHALL extract the text directly from the image
- AND the extracted text SHALL be returned

#### Scenario: Skip OCR for digital-born PDFs
- GIVEN a digital-born PDF that already contains embedded text
- WHEN the file is evaluated for OCR
- THEN OCR SHALL be skipped
- AND the embedded text SHALL be used directly

### Requirement: OCR Detection (REQ-OCR-02)

**Priority:** MUST

The system SHALL automatically detect whether a file requires OCR based on MIME type and text content.

#### Scenario: Image MIME types trigger OCR
- GIVEN a file with an image MIME type (image/png, image/jpeg, image/tiff)
- WHEN OCR detection runs
- THEN OCR SHALL always be triggered

#### Scenario: PDF with no text falls back to OCR
- GIVEN a PDF file
- WHEN TextExtractionService returns empty text
- THEN the file SHALL fall back to OCR

#### Scenario: Non-image non-PDF files skip OCR
- GIVEN a file that is neither an image nor a PDF
- WHEN OCR detection runs
- THEN OCR SHALL be skipped
- AND standard text extraction SHALL be used

### Requirement: OCR Configuration (REQ-OCR-03)

**Priority:** MUST

The system SHALL support configurable Tesseract language models and DPI, defaulting to Dutch and English (nld+eng) and 300 DPI respectively.

#### Scenario: Configurable language models
- GIVEN an admin configures Tesseract language models
- WHEN an OCR operation runs
- THEN the custom language configuration SHALL be passed to Tesseract for all OCR operations
- AND the default SHALL be nld+eng when unset

#### Scenario: Configurable DPI
- GIVEN an admin configures the PDF-to-image DPI
- WHEN a PDF-to-image conversion runs
- THEN the custom DPI configuration SHALL be used for all conversions
- AND the default SHALL be 300 when unset

### Requirement: Graceful Degradation (REQ-OCR-04)

**Priority:** MUST

The system SHALL continue to function normally when the Tesseract binary is not installed.

#### Scenario: Tesseract unavailable
- GIVEN the Tesseract binary is not installed
- WHEN a file that would require OCR is processed
- THEN OCR processing SHALL be skipped with a warning log
- AND the app SHALL continue to function normally

#### Scenario: Installation status in admin settings
- GIVEN the admin settings page is displayed
- WHEN Tesseract status is rendered
- THEN the Tesseract installation status and version SHALL be displayed

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

## Data Model

### OCR Result (Internal)

| Field | Type | Description |
|-------|------|-------------|
| text | string | OCR-extracted text content |
| confidence | float | Tesseract mean confidence score (0-100) |
| ocrProcessed | boolean | Whether OCR was performed |

## Architecture

- **Service**: `OCA\Filinq\Service\OcrService` wraps Tesseract OCR via `thiagoalessio/tesseract_ocr`
- **Integration**: Called by `AnonymizationService::extractAndDetectEntities()` as a pre-processing step before OpenRegister's TextExtractionService
- **Config**: OCR settings stored in IAppConfig (`ocr_enabled`, `ocr_languages`, `ocr_dpi`)
- **Dependencies**: Tesseract binary on system, PHP Imagick extension for PDF conversion
