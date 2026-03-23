## Context

DocuDesk processes documents through an anonymization pipeline: upload -> extract text/entities -> anonymize. Text extraction is delegated to OpenRegister's `TextExtractionService::extractFile()`. This service handles digital-born documents (PDFs with embedded text, DOCX, etc.) but cannot extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG). Government organizations regularly handle scanned correspondence and legacy archives that need GDPR-compliant anonymization before WOO publication.

The current flow in `AnonymizationService::extractEntities()` calls `TextExtractionService::extractFile($fileId, true)` which returns extracted text and entities. For image-based files, this returns empty text, making entity detection impossible.

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

**Rationale**: This is the most popular PHP Tesseract wrapper (5M+ downloads), actively maintained, supports all Tesseract configuration options, and requires only the Tesseract binary on the system. Alternative: calling `exec('tesseract ...')` directly -- rejected because the library handles path escaping, error parsing, and configuration cleanly.

### 2. OCR as a pre-processing step in AnonymizationService

**Decision**: Add OCR detection and execution in `AnonymizationService::extractEntities()` before the existing `TextExtractionService::extractFile()` call. If OCR produces text, store it as file metadata via OpenRegister's ObjectService so TextExtractionService can access it.

**Rationale**: This keeps the pipeline transparent -- the extraction endpoint (`POST /api/anonymization/extract/{fileId}`) gains OCR capability without API changes. The existing entity detection flow remains unchanged; OCR just ensures there is text to detect entities from.

**Alternative considered**: Separate OCR endpoint -- rejected because it fragments the pipeline and requires frontend changes to call two endpoints.

### 3. New `OcrService` class

**Decision**: Create a dedicated `OCA\DocuDesk\Service\OcrService` that encapsulates all OCR logic: detection, execution, text extraction from images, and PDF page-to-image conversion.

**Rationale**: Follows the existing service-oriented pattern (AnonymizationService, ConsentService, MetadataService). Keeps OCR logic testable and isolated. The service will be injected into AnonymizationService via DI.

### 4. OCR detection strategy

**Decision**: Determine if OCR is needed based on:
1. MIME type is an image type (image/png, image/jpeg, image/tiff) -> always needs OCR
2. MIME type is application/pdf -> check if text can be extracted first; if empty, attempt OCR
3. All other types -> skip OCR

**Rationale**: Avoids running OCR on documents that already have extractable text. The fallback approach for PDFs handles mixed documents (some pages scanned, some digital).

### 5. Tesseract binary in Docker container

**Decision**: Add `tesseract-ocr` and language packs (`tesseract-ocr-nld`, `tesseract-ocr-eng`, `tesseract-ocr-deu`, `tesseract-ocr-fra`) to the Nextcloud Docker image via a Dockerfile extension or entrypoint script.

**Rationale**: Keeps processing 100% local. Language packs are small (~15MB each). The Docker approach ensures consistent environments across development and production.

### 6. Admin settings for OCR configuration

**Decision**: Add OCR settings to the existing admin settings panel:
- `ocr_enabled` (boolean, default: true) -- master toggle
- `ocr_languages` (string, default: "nld+eng") -- Tesseract language string
- `ocr_dpi` (integer, default: 300) -- DPI for PDF-to-image conversion

**Rationale**: Follows the existing pattern of metadata enrichment toggles (SET-040 through SET-043). Language selection is important because Tesseract accuracy depends heavily on the correct language model.

## Risks / Trade-offs

- **[Performance]** OCR is CPU-intensive; large multi-page scanned PDFs may take 30-60 seconds per page -> Mitigation: Process pages sequentially with progress indication; consider async processing in a future iteration
- **[Accuracy]** Tesseract accuracy varies with scan quality, language, and font -> Mitigation: Expose confidence scores in the API response so users can gauge reliability; recommend 300 DPI scans
- **[Dependency]** Tesseract binary must be installed in the container -> Mitigation: Graceful degradation -- if Tesseract is not installed, OCR is skipped with a warning log; admin settings show availability status
- **[PDF conversion]** Converting PDF pages to images for OCR requires either Imagick or Ghostscript -> Mitigation: Use PHP Imagick extension (already common in Nextcloud containers) for PDF-to-image conversion
- **[Storage]** OCR-extracted text is stored alongside the file metadata -> Mitigation: Text is stored as a string field in OpenRegister, minimal storage impact

## Migration Plan

1. Add `thiagoalessio/tesseract_ocr` to `composer.json`
2. Create `OcrService` with detection and extraction logic
3. Modify `AnonymizationService::extractEntities()` to call OcrService before TextExtractionService
4. Add OCR settings to `SettingsService` and admin settings Vue component
5. Update Docker configuration to include Tesseract
6. Add OCR status indicator to file listing frontend

**Rollback**: Disable OCR via admin toggle (`ocr_enabled = false`). No database migrations needed -- OCR-extracted text uses existing OpenRegister text fields.

## Open Questions

- Should OCR results be cached to avoid re-processing the same file? (Likely yes, via file metadata flag)
- Should there be a file size limit for OCR processing? (Suggested: 50MB default, configurable)
