---
status: reviewed
---

# OCR Document Scanning

## Purpose

Provides OCR (Optical Character Recognition) text extraction from scanned and image-based documents using Tesseract OCR. Enables the anonymization pipeline to process scanned PDFs, TIFF, PNG, and JPG files by extracting text locally before entity detection. All processing runs 100% on the server with no external cloud dependencies, in compliance with DocuDesk's local processing standard.

`OcrService` integrates into `AnonymizationService::extractAndDetectEntities()` as a pre-processing step. When a file is an image type or a PDF that yields no text via standard extraction, the service invokes Tesseract. The resulting text flows into the existing entity detection and anonymization pipeline without changes to downstream consumers.

---

## REQ-OCR-01: Text Extraction from Image-Based Documents (Priority: Must)

Tesseract OCR extracts text from scanned PDFs (by converting each page to an image via Imagick) and from image files (PNG, JPG, TIFF) directly. Digital-born PDFs that already contain embedded text are not passed through OCR.

#### Scenario: Extract text from a scanned PDF

- **GIVEN** a PDF file where all pages are rasterized scans (no embedded text layer)
- **WHEN** `OcrService::extractText()` is called with MIME type `application/pdf`
- **THEN** Imagick converts each page to a PNG at the configured DPI
- **AND** Tesseract processes each page image and produces text output
- **AND** the concatenated text is returned in `OcrResult::text`
- **AND** `OcrResult::ocrProcessed` is `true`

#### Scenario: Extract text from an image file (PNG, JPG, TIFF)

- **GIVEN** a file with MIME type `image/png`, `image/jpeg`, or `image/tiff`
- **WHEN** `OcrService::extractText()` is called
- **THEN** the file is passed directly to Tesseract without Imagick conversion
- **AND** the extracted text is returned in `OcrResult::text`
- **AND** `OcrResult::ocrProcessed` is `true`

#### Scenario: Skip OCR for a digital-born PDF

- **GIVEN** a PDF file that contains embedded text (e.g. a word-processor export)
- **WHEN** `TextExtractionService::extractFile()` already returns non-empty text
- **THEN** `OcrService::needsOcr()` returns `false`
- **AND** `OcrService::extractText()` is not called for this file
- **AND** the existing text extraction result is used unchanged

#### Scenario: OCR produces text for an image-only scanned PDF page

- **GIVEN** a single-page scanned PDF containing a Dutch letter
- **WHEN** OCR runs at 300 DPI with `nld+eng` language models
- **THEN** the Dutch text on the page is returned in `OcrResult::text`
- **AND** `OcrResult::confidence` is a float between 0.0 and 100.0

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| OCR-001 | Extract text from image-based documents (scanned PDFs, TIFF, PNG, JPG) using Tesseract OCR | MUST | Implemented |
| OCR-002 | All OCR processing runs locally on the server with no external cloud service calls | MUST | Implemented |
| OCR-003 | OCR extracts text from scanned PDFs by converting each page to an image via Imagick and running Tesseract | MUST | Implemented |
| OCR-004 | OCR extracts text from image files (PNG, JPG, TIFF) directly via Tesseract without Imagick | MUST | Implemented |
| OCR-005 | OCR is skipped for digital-born PDFs that already contain embedded text | MUST | Implemented |

---

## REQ-OCR-02: Automatic OCR Detection (Priority: Must)

The system automatically detects whether a given file requires OCR based on its MIME type and the result of standard text extraction. This detection is transparent to callers: `AnonymizationService` always calls `OcrService::needsOcr()` and acts on the result without manual configuration per file.

#### Scenario: Image MIME types always trigger OCR

- **GIVEN** a file with MIME type `image/png`, `image/jpeg`, or `image/tiff`
- **WHEN** `OcrService::needsOcr(filePath, mimeType)` is evaluated
- **THEN** the result is `true` regardless of any existing file content
- **AND** the file is routed to `OcrService::extractText()`

#### Scenario: PDF falls back to OCR when standard extraction is empty

- **GIVEN** a PDF file with MIME type `application/pdf`
- **AND** `TextExtractionService::extractFile()` returns an empty string for this file
- **WHEN** `OcrService::needsOcr()` is evaluated
- **THEN** the result is `true`
- **AND** the PDF is processed through Imagick and Tesseract

#### Scenario: PDF with embedded text skips OCR

- **GIVEN** a PDF file where `TextExtractionService::extractFile()` returns non-empty text
- **WHEN** `OcrService::needsOcr()` is evaluated
- **THEN** the result is `false`
- **AND** standard extracted text is used without invoking Tesseract

#### Scenario: Non-image non-PDF files skip OCR

- **GIVEN** a file with MIME type `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (DOCX) or similar Office type
- **WHEN** `OcrService::needsOcr()` is evaluated
- **THEN** the result is `false`
- **AND** standard text extraction handles the file

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| OCR-010 | Automatically detect whether a file requires OCR based on MIME type and text content | MUST | Implemented |
| OCR-011 | Image MIME types (image/png, image/jpeg, image/tiff) always trigger OCR | MUST | Implemented |
| OCR-012 | PDF files fall back to OCR when TextExtractionService returns empty text | MUST | Implemented |
| OCR-013 | Non-image non-PDF files skip OCR and use standard text extraction | MUST | Implemented |

---

## REQ-OCR-03: OCR Configuration (Priority: Must)

OCR behaviour is configurable via `IAppConfig` keys. Operators can tune language models and image resolution for their deployment without changing code. Configuration is applied to every OCR operation at call time; changes take effect immediately without restart.

#### Scenario: Default language models apply when no custom value is set

- **GIVEN** no `ocr_languages` key is configured in `IAppConfig`
- **WHEN** `OcrService` constructs a Tesseract invocation
- **THEN** the language model string `nld+eng` is used
- **AND** Tesseract is invoked with `--lang nld+eng`

#### Scenario: Custom language configuration is applied

- **GIVEN** `ocr_languages` is set to `deu+eng` in admin settings
- **WHEN** OCR is triggered for any file
- **THEN** Tesseract is invoked with `--lang deu+eng`
- **AND** the resulting text reflects the configured language model

#### Scenario: Default DPI applies to PDF-to-image conversion

- **GIVEN** no `ocr_dpi` key is configured in `IAppConfig`
- **WHEN** `OcrService::extractFromPdf()` converts a scanned PDF page to an image
- **THEN** Imagick renders the page at 300 DPI
- **AND** the resulting image is passed to Tesseract at that resolution

#### Scenario: Custom DPI is applied to all PDF conversions

- **GIVEN** `ocr_dpi` is set to `150` in admin settings
- **WHEN** a scanned PDF is processed
- **THEN** Imagick renders each page at 150 DPI
- **AND** the lower-resolution images are passed to Tesseract

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| OCR-020 | Support configurable Tesseract language models, defaulting to Dutch and English (nld+eng) | MUST | Implemented |
| OCR-021 | Support configurable DPI for PDF-to-image conversion, defaulting to 300 | MUST | Implemented |
| OCR-022 | Custom language configuration passed to Tesseract for all OCR operations | MUST | Implemented |
| OCR-023 | Custom DPI configuration used for all PDF-to-image conversions | MUST | Implemented |

---

## REQ-OCR-04: Graceful Degradation When Tesseract Is Unavailable (Priority: Must)

DocuDesk MUST continue to function normally when the Tesseract binary is not installed on the server. OCR is skipped silently with a warning log entry. No error is surfaced to the user through normal document operations. Administrators can diagnose the missing dependency through the admin settings panel.

#### Scenario: Tesseract binary absent — document processing continues

- **GIVEN** Tesseract is not installed on the server
- **WHEN** a scanned PDF or image file is uploaded and processed
- **THEN** `OcrService` detects the missing binary via `TesseractOCR::getTesseractVersion()`
- **AND** OCR is skipped for this file
- **AND** a `warning` level log entry is written (no PII in log message)
- **AND** `OcrResult::ocrProcessed` is `false`
- **AND** the anonymization pipeline continues with empty text (entity detection finds nothing)
- **AND** no exception is surfaced to the end user

#### Scenario: Tesseract absent — admin settings show unavailable

- **GIVEN** Tesseract is not installed
- **WHEN** an admin views the DocuDesk settings page
- **THEN** the Tesseract status indicator shows "Not installed" or equivalent
- **AND** no version string is displayed

#### Scenario: Tesseract installed — admin settings show version

- **GIVEN** Tesseract is installed (e.g., version 5.3.1)
- **WHEN** an admin views the DocuDesk settings page
- **THEN** the Tesseract status indicator shows "Installed"
- **AND** the version string is displayed alongside the status

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| OCR-030 | Continue to function normally when Tesseract binary is not installed | MUST | Implemented |
| OCR-031 | Skip OCR processing with a warning log when Tesseract is unavailable | MUST | Implemented |
| OCR-032 | Display Tesseract installation status and version in admin settings | MUST | Implemented |

---

## REQ-OCR-05: OCR Metadata in File Listing (Priority: Must)

The file listing response (`GET /api/anonymization/files`) is extended to include per-file OCR metadata. This allows the frontend and API consumers to distinguish OCR-processed files from digitally-born files, and to assess OCR output quality via the confidence score.

#### Scenario: OCR-processed file appears in listing with confidence score

- **GIVEN** a scanned PDF that was processed through OCR with an average confidence of 82.5
- **WHEN** the file listing is requested via `GET /api/anonymization/files`
- **THEN** the file entry includes `ocrProcessed: true`
- **AND** the file entry includes `ocrConfidence: 82.5`

#### Scenario: Non-OCR file appears in listing with ocrProcessed false

- **GIVEN** a digital-born PDF that was processed via standard text extraction
- **WHEN** the file listing is requested
- **THEN** the file entry includes `ocrProcessed: false`
- **AND** `ocrConfidence` is `null` or absent from the response

#### Scenario: OCR confidence range is 0–100

- **GIVEN** Tesseract returns per-character confidence values
- **WHEN** `OcrService` computes the mean confidence for a file
- **THEN** the result is a float in the range [0.0, 100.0]
- **AND** a value of 0.0 indicates no recognizable text; 100.0 indicates perfect confidence

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| OCR-040 | Report OCR confidence scores (0–100) indicating Tesseract mean confidence | MUST | Implemented |
| OCR-041 | Track ocrProcessed boolean flag per file in file listing | MUST | Implemented |
| OCR-042 | Non-OCR files show ocrProcessed: false with no confidence score | MUST | Implemented |

---

## Data Model

### OcrResult (Internal Value Object)

`OcrResult` is a transient PHP value object returned by `OcrService::extractText()`. It is NOT stored in OpenRegister or any database. The `text` field feeds into the entity detection pipeline; the metadata fields are stored alongside the file listing entry.

| Field | Type | Description |
|-------|------|-------------|
| `text` | string | OCR-extracted text content; empty string if OCR was skipped |
| `confidence` | float | Tesseract mean confidence score (0–100); 0.0 when `ocrProcessed` is false |
| `ocrProcessed` | boolean | Whether OCR was performed for this file |

## API Impact

No new API endpoints are introduced. The existing file listing endpoint is extended:

| Field added | Endpoint | Type | Description |
|-------------|----------|------|-------------|
| `ocrProcessed` | `GET /api/anonymization/files` | boolean | Whether OCR ran for this file |
| `ocrConfidence` | `GET /api/anonymization/files` | float\|null | Tesseract mean confidence (0–100); null if not OCR-processed |

## Dependencies

- **Tesseract binary**: Must be installed on the server (`apt install tesseract-ocr tesseract-ocr-nld`)
- **PHP Imagick extension**: Required for PDF-to-image conversion (`apt install php-imagick`)
- **`thiagoalessio/tesseract_ocr`** Composer package: PHP wrapper around the Tesseract CLI
- **`TextExtractionService`** (OpenRegister): Used for digital-born PDFs before OCR fallback decision
- **`IAppConfig`** (Nextcloud): Stores `ocr_enabled`, `ocr_languages`, `ocr_dpi`

## Standards & References

- **AVG/GDPR Article 5(1)(f)**: Integrity and confidentiality — all OCR processing is local; no data leaves the server
- **WOO (Wet open overheid)**: OCR enables anonymization of scanned correspondence prior to publication
- **ISO/IEC 19005 (PDF/A)**: Future searchable PDF output target format (deferred scope)
- **Tesseract OCR**: Apache 2.0 licensed, maintained by Google; `nld` language model from tessdata project
