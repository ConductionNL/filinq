## 1. Dependencies and Infrastructure

- [x] 1.1 Add `thiagoalessio/tesseract_ocr` to composer.json (already done in prior change)
- [x] 1.2 Update Docker init script to install `tesseract-ocr`, `tesseract-ocr-nld`, `tesseract-ocr-eng`, `tesseract-ocr-deu`, `tesseract-ocr-fra` packages

## 2. OcrService Core Implementation

- [x] 2.1 OcrService exists with `isTesseractAvailable()` and `getTesseractVersion()` (prior change)
- [x] 2.2 `needsOcr(string $mimeType, ?string $existingText): bool` implemented (prior change)
- [x] 2.3 `extractTextFromImage()` implemented (prior change)
- [x] 2.4 `extractTextFromPdf()` implemented (prior change)
- [x] 2.5 `processFile(int $fileId): array` implemented (prior change)

## 3. AnonymizationService Integration

- [x] 3.1 OcrService is auto-wired by Nextcloud DI (no manual registration needed)
- [x] 3.2 Inject OcrService into AnonymizationService constructor
- [x] 3.3 Modify `AnonymizationService::extractAndDetectEntities()` to call OcrService before TextExtractionService
- [x] 3.4 Add `ocrProcessed` and `ocrConfidence` fields to the extraction response array
- [x] 3.5 Add `ocrConfidence` field to file listing response in `FileListingService`

## 4. Admin Settings Backend

- [x] 4.1 Add OCR config keys to `SettingsService::loadFeatureToggles()`: `ocr_enabled`, `ocr_languages`, `ocr_dpi`
- [x] 4.2 Add `getOcrStatus()` method to SettingsService that returns Tesseract availability and version via OcrService
- [x] 4.3 Include OCR settings and Tesseract status in `getAllSettings()` response
- [x] 4.4 Add OCR keys to `WRITABLE_KEYS` allowlist in SettingsService

## 5. Admin Settings Frontend

- [x] 5.1 OCR settings section already in `Settings.vue` (prior change)
- [x] 5.2 Language selection checkboxes already implemented (prior change)
- [x] 5.3 DPI numeric input already implemented (prior change)
- [x] 5.4 Tesseract availability status indicator already implemented (prior change)

## 6. Testing and Quality

- [x] 6.1 Unit tests for `OcrService::needsOcr()` already exist (prior change)
- [x] 6.2 Add unit tests for AnonymizationService OCR integration
- [x] 6.3 Run `composer check:strict` and fix any PHPCS, PHPMD, Psalm, or PHPStan issues
