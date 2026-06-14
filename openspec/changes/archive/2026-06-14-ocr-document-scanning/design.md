---
status: pr-created
issue: 29
change-name: ocr-document-scanning
---

## Context

DocuDesk processes documents through an anonymization pipeline: upload → extract text/entities → anonymize. Text extraction is delegated to OpenRegister's `TextExtractionService::extractFile()`. This service handles digital-born documents (PDFs with embedded text, DOCX, etc.) but cannot extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG). Government organizations regularly handle scanned correspondence and legacy archives that need GDPR-compliant anonymization before WOO publication.

The current flow in `AnonymizationService::extractAndDetectEntities()` calls `TextExtractionService::extractFile($fileId, true)` which returns extracted text and entities. For image-based files, this returns empty text, making entity detection impossible.

## Goals / Non-Goals

**Goals:**
- Enable OCR text extraction from image-based documents (scanned PDFs, TIFF, PNG, JPG) using Tesseract
- Integrate OCR transparently into the existing anonymization pipeline (no new endpoints)
- Keep all processing 100% local (Tesseract runs on the server, no cloud APIs)
- Provide admin configuration for OCR languages and quality settings
- Show OCR status in the file listing so users know which files were OCR-processed

**Non-Goals:**
- Handwriting recognition (Tesseract handles printed text only)
- Real-time OCR during upload (OCR runs during the extraction step, not on upload)
- Training custom Tesseract models
- OCR for video or audio content
- Replacing OpenRegister's TextExtractionService (OCR supplements it as a pre-processing step)

## Decisions

### 1. Tesseract via `thiagoalessio/tesseract_ocr` PHP library

**Decision**: Use the `thiagoalessio/tesseract_ocr` Composer package as the PHP wrapper around the Tesseract binary.

**Rationale**: Most popular PHP Tesseract wrapper (5M+ downloads), actively maintained, supports all Tesseract configuration options, requires only the Tesseract binary. Already added to composer.json.

### 2. OCR as a pre-processing step in AnonymizationService

**Decision**: Add OCR detection and execution in `AnonymizationService::extractAndDetectEntities()` before the existing `TextExtractionService::extractFile()` call. Return OCR metadata (`ocrProcessed`, `ocrConfidence`) alongside entities.

**Rationale**: This keeps the pipeline transparent — the extraction endpoint (`POST /api/anonymization/extract/{fileId}`) gains OCR capability without API breaking changes. OCR metadata enables users to assess result quality.

### 3. New `OcrService` class

**Decision**: Dedicated `OCA\DocuDesk\Service\OcrService` encapsulates all OCR logic.

**Rationale**: Follows existing service-oriented pattern (AnonymizationService, ConsentService, MetadataService). Testable and isolated. Implemented in a prior change, retained here.

### 4. OCR detection strategy

**Decision**: Determine if OCR is needed based on:
1. MIME type is an image type (image/png, image/jpeg, image/tiff) → always needs OCR
2. MIME type is application/pdf → check if text can be extracted first; if empty, attempt OCR
3. All other types → skip OCR

### 5. Admin settings for OCR configuration

**Decision**: Add OCR settings to the existing admin settings panel:
- `ocr_enabled` (boolean, default: true) — master toggle
- `ocr_languages` (string, default: "nld+eng") — Tesseract language string
- `ocr_dpi` (integer, default: 300) — DPI for PDF-to-image conversion

## Risks / Trade-offs

- **[Performance]** OCR is CPU-intensive; large multi-page scanned PDFs may take 30-60 seconds per page
- **[Accuracy]** Tesseract accuracy varies with scan quality, language, and font
- **[Dependency]** Tesseract binary must be installed in the container; graceful degradation when not present

## Migration Plan

1. `OcrService` already created with detection and extraction logic (previous change)
2. Modify `AnonymizationService::extractAndDetectEntities()` to call OcrService before TextExtractionService
3. Add OCR settings to `SettingsService` and admin settings Vue component
4. Update Docker init script to include Tesseract installation
5. Add OCR confidence to file listing
