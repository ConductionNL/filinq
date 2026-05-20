## Context

DocuDesk's anonymization pipeline (specced in `archive/2026-03-21-anonymization`) calls `AnonymizationService::extractAndDetectEntities()` which delegates to OpenRegister's `TextExtractionService`. This works only for digital-born PDFs and Office documents. Scanned PDFs and image files (PNG, JPG, TIFF) return empty text, making entity detection and anonymization impossible on scanned content.

This change introduces `OcrService` as a pre-processing step inside `extractAndDetectEntities()`. When text extraction returns empty for a supported MIME type, or when the file is an image type that always requires OCR, the service invokes Tesseract OCR via the `thiagoalessio/tesseract_ocr` Composer package. For scanned PDFs, PHP Imagick converts each page to a rasterized image before Tesseract processes it. All processing is local with no external service calls.

## Goals / Non-Goals

**Goals:**

- Extract text from scanned PDFs, PNG, JPG, and TIFF files using Tesseract OCR running locally.
- Detect automatically whether a file needs OCR (image MIME types always; PDFs only when text extraction returns empty).
- Return OCR confidence score (0--100) and `ocrProcessed` flag alongside extracted text.
- Expose Tesseract status (installed, version) in admin settings.
- Degrade gracefully when Tesseract binary is absent: log a warning, skip OCR, return empty result.
- Store OCR configuration (`ocr_enabled`, `ocr_languages`, `ocr_dpi`) in `IAppConfig`.

**Non-Goals:**

- Image pre-processing (deskew, denoise, contrast adjustment) — deferred.
- Searchable PDF output (PDF/A with embedded text layer) — deferred.
- Auto-classification of document type based on OCR text — deferred.
- Cloud OCR service integration — explicitly excluded (privacy-by-design).
- Storing OCR results in OpenRegister — OCR result is a transient value object; text flows into the existing extraction pipeline.

## Decisions

### D1. OcrService as a pre-processing step in AnonymizationService

`OcrService` is called inside `AnonymizationService::extractAndDetectEntities()` before delegating to OpenRegister's `TextExtractionService`. If OCR produces text, that text is passed to the entity detection pipeline directly. This keeps OCR logic isolated in a dedicated service without modifying OpenRegister's extraction contract.

**Trade-off:** Coupling OCR to `AnonymizationService` rather than injecting it at the OpenRegister layer keeps DocuDesk in full control of when and how OCR runs, and avoids modifying a shared platform component.

### D2. PDF-to-image via Imagick, then Tesseract per page

For scanned PDFs, `OcrService::extractFromPdf()` uses PHP Imagick to convert each page to a PNG at the configured DPI (default 300), then passes each image to Tesseract. The per-page confidence scores are averaged to produce a file-level confidence score.

**Trade-off:** Imagick is a required server dependency alongside Tesseract. This is acceptable as both are standard packages on Linux servers. The alternative (streaming entire PDF to Tesseract) is not supported by the `thiagoalessio/tesseract_ocr` library for multi-page PDFs.

### D3. OCR skipped for digital-born PDFs

`OcrService::needsOcr()` returns `false` for PDFs when `TextExtractionService` already returned non-empty text. Image MIME types (`image/png`, `image/jpeg`, `image/tiff`) always trigger OCR regardless of existing text content.

### D4. Graceful degradation via binary check

`OcrService` checks for the Tesseract binary at runtime via `TesseractOCR::getTesseractVersion()`. If unavailable, OCR is skipped with a `warning` level log. DocuDesk continues functioning normally for all non-OCR operations. Admin settings display the availability status.

### D5. Configuration in IAppConfig

OCR settings are stored as three `IAppConfig` keys: `ocr_enabled` (boolean), `ocr_languages` (string, e.g. `nld+eng`), `ocr_dpi` (integer). No custom DB entity or OpenRegister schema is needed.

## Architecture

### Backend

```
AnonymizationService::extractAndDetectEntities(fileNode)
  └─► OcrService::needsOcr(filePath, mimeType)          // detection
        ├─ image/* → true (always)
        └─ application/pdf → true only if TextExtractionService returns empty
  └─► OcrService::extractText(filePath, mimeType)        // dispatch
        ├─ image/* → extractFromImage(filePath)          // Tesseract direct
        └─ application/pdf → extractFromPdf(filePath)    // Imagick → Tesseract
  └─► returns OcrResult { text, confidence, ocrProcessed }
  └─► text fed into entity detection pipeline (unchanged)
```

| Class | Location | Responsibility |
|-------|----------|----------------|
| `OcrService` | `lib/Service/OcrService.php` | Wraps `thiagoalessio/tesseract_ocr`; orchestrates OCR for images and PDFs |
| `AnonymizationService` | `lib/Service/AnonymizationService.php` | Calls `OcrService` as pre-step in `extractAndDetectEntities()` |
| `AdminController` (existing) | `lib/Controller/AdminController.php` | Exposes Tesseract version/status via existing admin settings endpoint |

### Internal Data Object

`OcrResult` is a transient PHP value object (not persisted, not an OpenRegister object):

| Field | Type | Description |
|-------|------|-------------|
| `text` | string | OCR-extracted text content |
| `confidence` | float | Tesseract mean confidence score (0--100); 0.0 when `ocrProcessed` is false |
| `ocrProcessed` | boolean | Whether OCR was performed for this file |

### Configuration Keys (IAppConfig)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `ocr_enabled` | boolean | `true` | Master switch; disabling skips all OCR |
| `ocr_languages` | string | `nld+eng` | Tesseract language model(s); `+`-separated |
| `ocr_dpi` | integer | `300` | DPI for PDF-to-image conversion via Imagick |

### File Listing Extension

`FileListingService` is extended to include OCR metadata per file:

| Field | Type | Description |
|-------|------|-------------|
| `ocrProcessed` | boolean | Whether OCR was performed |
| `ocrConfidence` | float\|null | Tesseract mean confidence (0--100); `null` if not OCR-processed |

## Data Flow

```
1. User uploads file → stored in DocuDesk/ folder (existing upload pipeline)
2. AnonymizationService::extractAndDetectEntities(fileNode) is called
3. OcrService::needsOcr(filePath, mimeType) evaluates file type:
   a. image/png, image/jpeg, image/tiff → always true
   b. application/pdf → TextExtractionService::extractFile() first;
      if result is empty → true; otherwise → false (digital-born PDF)
   c. all other types → false (use standard extraction)
4a. [OCR path] OcrService::extractText(filePath, mimeType):
     - Images: TesseractOCR(filePath)->lang(languages)->run() → text + confidence
     - PDFs: Imagick converts each page → temp PNG at configured DPI
             TesseractOCR(tempPng)->lang(languages)->run() per page
             text concatenated; confidence averaged across pages
     - Returns OcrResult { text, confidence, ocrProcessed: true }
4b. [No-OCR path] Returns OcrResult { text: '', confidence: 0, ocrProcessed: false }
5. Extracted text (OCR or standard) flows into entity detection (unchanged)
6. OcrResult metadata stored per-file and returned in file listing responses
```

## Reuse Analysis

This change does NOT introduce new OpenRegister schemas or custom DB entities. It reuses the following existing platform components:

| Component | How reused |
|-----------|-----------|
| `TextExtractionService` (OpenRegister) | Called first for PDFs; OCR only triggers on empty result |
| `AnonymizationService` | OCR integrated as a pre-processing step; existing pipeline unchanged |
| `IAppConfig` | All OCR configuration stored here; no custom DB table |
| `FileListingService` | Extended (not replaced) to include `ocrProcessed` and `ocrConfidence` |
| Admin settings Vue component | Extended to display Tesseract status; no new settings page |

No duplication of existing OpenRegister `TextExtractionService` or `EntityRecognitionHandler` — OCR runs only when those services cannot produce text from the raw file.

## Seed Data

Not applicable. `OcrService` operates on Nextcloud filesystem files and returns a transient `OcrResult` value object. No OpenRegister schemas are introduced by this change; therefore no seed data is required per ADR-001 ("Changes that only modify frontend components or non-schema backend logic do not require seed data").

## Risks / Trade-offs

- **Imagick memory usage** — converting high-resolution scanned PDFs to images is memory-intensive. Operators should set `memory_limit` and `post_max_size` appropriately. A future change may add per-page streaming to reduce peak memory.
- **Tesseract accuracy** — depends heavily on scan quality. Confidence scores below 70 should be surfaced to the operator (future UX change; this change only exposes the raw score).
- **Imagick absent** — if PHP Imagick is not installed, `extractFromPdf()` will throw. This is treated as a missing dependency, not graceful degradation. Imagick is a standard dependency alongside Tesseract.

## Migration Plan

1. Add `thiagoalessio/tesseract_ocr` to `composer.json`.
2. Implement `OcrService` and unit tests.
3. Integrate `OcrService` into `AnonymizationService::extractAndDetectEntities()`.
4. Extend `FileListingService` to return `ocrProcessed` and `ocrConfidence`.
5. Extend admin settings endpoint and Vue component to display Tesseract status.
6. Document Tesseract and Imagick as installation prerequisites.

**Rollback:** Set `ocr_enabled: false` in `IAppConfig`. All OCR calls short-circuit; no data loss.

## ADR Compliance

| ADR | Rule | How satisfied |
|-----|------|---------------|
| ADR-001 | Domain data → OpenRegister; config → IAppConfig | `OcrResult` is a transient value object; all config stored in `IAppConfig` |
| ADR-001 | No new entity/mapper for domain data | No custom DB entity; no OpenRegister schema |
| ADR-003 | Controller → Service strict layering | OCR logic isolated in `OcrService`; called from `AnonymizationService` only |
| ADR-003 | Services stateless | `OcrService` holds no per-request state |
| ADR-005 | No PII in logs | OCR text content never logged; only confidence scores and file IDs |
| ADR-005 | File type validation before processing | MIME type check before OCR dispatch |
