---
status: in-progress
---

# OCR Document Scanning

**Status**: in-progress
**Scope**: filinq
**OpenSpec changes**:
- [ocr-trigger-surface](../../changes/ocr-trigger-surface/) _(active)_ — wires the engine: OCR API route + Run-OCR UI action + anonymisation-pipeline fallback; REQ-OCR-05 corrected from the file-listing MIME heuristic to persisted per-file `ocrResult` objects (kind: code)

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

The system SHALL report OCR confidence scores and track an ocrProcessed flag per file.

#### Scenario: Report confidence score
- GIVEN OCR was performed on a file
- WHEN the file listing is queried
- THEN a confidence score (0-100) reflecting Tesseract mean confidence SHALL be reported
- AND the ocrProcessed flag SHALL be true

#### Scenario: Non-OCR files
- GIVEN a file that did not require OCR
- WHEN the file listing is queried
- THEN ocrProcessed SHALL be false
- AND no confidence score SHALL be reported

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| OCR-001 | Extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG) using Tesseract OCR | MUST | Implemented |
| OCR-002 | All OCR processing runs locally on the server with no external cloud service calls | MUST | Implemented |
| OCR-003 | OCR extracts text from scanned PDFs by converting each page to an image via Imagick and running Tesseract | MUST | Implemented |
| OCR-004 | OCR extracts text from image files (PNG, JPG, TIFF) directly via Tesseract | MUST | Implemented |
| OCR-005 | OCR is skipped for digital-born PDFs that already contain embedded text | MUST | Implemented |
| OCR-010 | Automatically detect whether a file requires OCR based on MIME type and text content | MUST | Implemented |
| OCR-011 | Image MIME types (image/png, image/jpeg, image/tiff) always trigger OCR | MUST | Implemented |
| OCR-012 | PDF files fall back to OCR when TextExtractionService returns empty text | MUST | Implemented |
| OCR-013 | Non-image non-PDF files skip OCR and use standard text extraction | MUST | Implemented |
| OCR-020 | Support configurable Tesseract language models, defaulting to Dutch and English (nld+eng) | MUST | Implemented |
| OCR-021 | Support configurable DPI for PDF-to-image conversion, defaulting to 300 | MUST | Implemented |
| OCR-022 | Custom language configuration passed to Tesseract for all OCR operations | MUST | Implemented |
| OCR-023 | Custom DPI configuration used for all PDF-to-image conversions | MUST | Implemented |
| OCR-030 | Continue to function normally when Tesseract binary is not installed | MUST | Implemented |
| OCR-031 | Skip OCR processing with a warning log when Tesseract is unavailable | MUST | Implemented |
| OCR-032 | Display Tesseract installation status and version in admin settings | MUST | Implemented |
| OCR-040 | Report OCR confidence scores (0-100) indicating Tesseract mean confidence | MUST | Implemented |
| OCR-041 | Track ocrProcessed boolean flag per file in file listing | MUST | Implemented |
| OCR-042 | Non-OCR files show ocrProcessed: false with no confidence score | MUST | Implemented |

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
