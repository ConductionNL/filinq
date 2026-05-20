## Tasks

- [x] 1. **Deduplication check** — verify no existing OpenRegister or DocuDesk service already performs Tesseract OCR or image-to-text extraction; confirm `TextExtractionService` only handles digital-born PDFs/Office docs and has no OCR path; document findings: no overlap found — OCR is a new capability.

- [x] 2. **Composer dependency** — add `thiagoalessio/tesseract_ocr` to `composer.json` and run `composer update`; pin a minimum version compatible with Tesseract 4.x and 5.x; verify `composer audit` clean.

- [x] 3. **`OcrService` — core implementation** — create `lib/Service/OcrService.php` implementing:
  - `needsOcr(string $filePath, string $mimeType): bool` — returns `true` for image MIME types always; for `application/pdf` only when `TextExtractionService::extractFile()` returns empty; `false` for all other types
  - `extractText(string $filePath, string $mimeType): OcrResult` — dispatches to `extractFromImage()` or `extractFromPdf()` based on MIME type
  - `extractFromImage(string $filePath): OcrResult` — invokes `TesseractOCR($filePath)->lang($languages)->run()` and computes confidence
  - `extractFromPdf(string $filePath): OcrResult` — uses Imagick to convert each page to a PNG at the configured DPI; runs Tesseract per page; concatenates text; averages confidence
  - `getTesseractVersion(): ?string` — returns version string or `null` if binary absent; used by admin settings
  - `@spec openspec/changes/ocr-document-scanning/tasks.md#task-3` PHPDoc on class and all public methods

- [x] 4. **`OcrResult` value object** — create `lib/Service/OcrResult.php` as an immutable value object with properties `text: string`, `confidence: float`, `ocrProcessed: bool`; constructor injection only; no setters.

- [x] 5. **Graceful degradation** — in `OcrService`, wrap the Tesseract binary check in a try/catch; if `TesseractOCR::getTesseractVersion()` throws or returns `null`:
  - log `$this->logger->warning('Tesseract not available, skipping OCR for file: {fileId}', ['fileId' => $fileId])`; NEVER log file content or extracted text
  - return `OcrResult { text: '', confidence: 0.0, ocrProcessed: false }`
  - all downstream operations continue normally with empty text

- [x] 6. **OCR configuration via IAppConfig** — read `ocr_enabled` (boolean, default `true`), `ocr_languages` (string, default `nld+eng`), `ocr_dpi` (integer, default `300`) from `IAppConfig` in `OcrService`; apply to all Tesseract and Imagick invocations; if `ocr_enabled` is `false`, short-circuit `extractText()` returning `ocrProcessed: false`.

- [x] 7. **AnonymizationService integration** — modify `lib/Service/AnonymizationService.php::extractAndDetectEntities()` to:
  - inject `OcrService` via constructor DI
  - call `OcrService::needsOcr(filePath, mimeType)` before delegating to `TextExtractionService`
  - if OCR needed, call `OcrService::extractText()` and use the returned `OcrResult::text` as the extraction result
  - store `ocrProcessed` and `confidence` alongside the file record for the listing response
  - `@spec openspec/changes/ocr-document-scanning/tasks.md#task-7` PHPDoc on modified method

- [x] 8. **FileListingService extension** — extend `lib/Service/FileListingService.php` to include `ocrProcessed` (boolean) and `ocrConfidence` (float|null) in the per-file response array; `ocrProcessed: false` and `ocrConfidence: null` for files processed before this change or files that did not require OCR.

- [x] 9. **Admin settings — Tesseract status** — extend the existing admin settings endpoint (`lib/Controller/AdminController.php` or equivalent) to return `tesseractVersion: string|null` and `tesseractAvailable: bool`; extend the admin settings Vue component to display "Tesseract OCR: Installed (v5.x.x)" or "Tesseract OCR: Not installed" alongside the existing settings.

- [x] 10. **Unit tests — OcrService** — create `tests/unit/Service/OcrServiceTest.php`:
  - `needsOcr()` returns `true` for image MIME types
  - `needsOcr()` returns `true` for PDF when TextExtractionService returns empty
  - `needsOcr()` returns `false` for PDF when TextExtractionService returns non-empty
  - `needsOcr()` returns `false` for DOCX and other non-image non-PDF types
  - `extractText()` returns `ocrProcessed: false` when `ocr_enabled` is `false`
  - `extractText()` returns `ocrProcessed: false` when Tesseract binary is unavailable
  - `extractFromImage()` calls TesseractOCR with the configured language string
  - `extractFromPdf()` uses the configured DPI for Imagick resolution

- [x] 11. **Unit tests — AnonymizationService integration** — extend `tests/unit/Service/AnonymizationServiceTest.php`:
  - OCR pre-step is called when `needsOcr()` returns `true`
  - OCR result text is used as extraction input for entity detection
  - standard extraction is used when `needsOcr()` returns `false`
  - `ocrProcessed` and `confidence` are stored on the file record correctly

- [x] 12. **Integration tests — Newman** — add OCR integration tests to the Newman collection:
  - upload a scanned PNG → extract → assert `ocrProcessed: true` in file listing and non-empty entity detection result
  - upload a digital-born PDF → extract → assert `ocrProcessed: false` in file listing
  - admin settings endpoint returns `tesseractAvailable: true|false` depending on server state

- [x] 13. **Documentation** — create `docs/features/ocr-document-scanning.md` covering: supported file types, Tesseract and Imagick installation prerequisites, configurable settings (`ocr_languages`, `ocr_dpi`), confidence score interpretation, graceful degradation behaviour; add CHANGELOG entry: "Added: OCR text extraction for scanned PDFs, PNG, JPG, and TIFF files via Tesseract".

- [x] 14. **i18n** — add Dutch and English translation strings for all new admin settings UI labels (Tesseract status, language model setting, DPI setting) to `l10n/nl_NL.js` and `l10n/en.js`.

- [x] 15. **Quality + verification** — `composer check:strict` clean on all modified files; manual smoke against a live stack: upload a scanned PDF, confirm `ocrProcessed: true` and a plausible confidence score in the file listing, confirm entities are detected from the OCR text; upload a digital-born PDF, confirm `ocrProcessed: false`; navigate to admin settings, confirm Tesseract status is displayed.
