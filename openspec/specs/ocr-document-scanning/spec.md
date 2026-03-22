---
status: reviewed
---

# OCR Document Scanning

## Purpose

Provides OCR (Optical Character Recognition) text extraction from scanned and image-based documents using Tesseract OCR. Enables the anonymization pipeline to process scanned PDFs, TIFF, PNG, and JPG files by extracting text locally before entity detection. All processing runs 100% on the server with no external cloud dependencies, in compliance with DocuDesk's local processing standard.

## Requirements

### OCR Text Extraction

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| OCR-001 | Extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG) using Tesseract OCR | MUST | Implemented |
| OCR-002 | All OCR processing runs locally on the server with no external cloud service calls | MUST | Implemented |
| OCR-003 | OCR extracts text from scanned PDFs by converting each page to an image via Imagick and running Tesseract | MUST | Implemented |
| OCR-004 | OCR extracts text from image files (PNG, JPG, TIFF) directly via Tesseract | MUST | Implemented |
| OCR-005 | OCR is skipped for digital-born PDFs that already contain embedded text | MUST | Implemented |

### OCR Detection

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| OCR-010 | Automatically detect whether a file requires OCR based on MIME type and text content | MUST | Implemented |
| OCR-011 | Image MIME types (image/png, image/jpeg, image/tiff) always trigger OCR | MUST | Implemented |
| OCR-012 | PDF files fall back to OCR when TextExtractionService returns empty text | MUST | Implemented |
| OCR-013 | Non-image non-PDF files skip OCR and use standard text extraction | MUST | Implemented |

### OCR Configuration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| OCR-020 | Support configurable Tesseract language models, defaulting to Dutch and English (nld+eng) | MUST | Implemented |
| OCR-021 | Support configurable DPI for PDF-to-image conversion, defaulting to 300 | MUST | Implemented |
| OCR-022 | Custom language configuration passed to Tesseract for all OCR operations | MUST | Implemented |
| OCR-023 | Custom DPI configuration used for all PDF-to-image conversions | MUST | Implemented |

### Graceful Degradation

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| OCR-030 | Continue to function normally when Tesseract binary is not installed | MUST | Implemented |
| OCR-031 | Skip OCR processing with a warning log when Tesseract is unavailable | MUST | Implemented |
| OCR-032 | Display Tesseract installation status and version in admin settings | MUST | Implemented |

### OCR Metadata

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
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

- **Service**: `OCA\DocuDesk\Service\OcrService` wraps Tesseract OCR via `thiagoalessio/tesseract_ocr`
- **Integration**: Called by `AnonymizationService::extractAndDetectEntities()` as a pre-processing step before OpenRegister's TextExtractionService
- **Config**: OCR settings stored in IAppConfig (`ocr_enabled`, `ocr_languages`, `ocr_dpi`)
- **Dependencies**: Tesseract binary on system, PHP Imagick extension for PDF conversion
