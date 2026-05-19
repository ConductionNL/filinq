## 1. Dependencies and Infrastructure

- [x] 1.1 Add `thiagoalessio/tesseract_ocr` to composer.json and run composer install
- [x] 1.2 Update Docker entrypoint/Dockerfile to install `tesseract-ocr`, `tesseract-ocr-nld`, `tesseract-ocr-eng`, `tesseract-ocr-deu`, `tesseract-ocr-fra` packages

## 2. OcrService Core Implementation

- [x] 2.1 Create `lib/Service/OcrService.php` with Tesseract availability detection (`isTesseractAvailable()`, `getTesseractVersion()`)
- [x] 2.2 Implement `needsOcr(string $mimeType, ?string $existingText): bool` method to determine OCR processing path based on MIME type and existing text content
- [x] 2.3 Implement `extractTextFromImage(string $filePath, string $languages, int $dpi): array` method returning `['text' => string, 'confidence' => float]`
- [x] 2.4 Implement `extractTextFromPdf(string $filePath, string $languages, int $dpi): array` method that converts PDF pages to images via Imagick and runs OCR on each page
- [x] 2.5 Implement `processFile(int $fileId): array` main entry point that reads OCR settings, gets the file from Nextcloud filesystem, determines the processing path, and returns OCR results

## 3. AnonymizationService Integration

- [x] 3.1 Register OcrService in Nextcloud DI container (Application.php or auto-wiring)
- [x] 3.2 Inject OcrService into AnonymizationService constructor
- [x] 3.3 Modify `AnonymizationService::extractEntities()` to call OcrService before TextExtractionService when OCR is needed
- [x] 3.4 Add `ocrProcessed` and `ocrConfidence` fields to the extraction response array
- [x] 3.5 Add `ocrProcessed` flag to file listing response in `FileListingService`

## 4. Admin Settings Backend

- [x] 4.1 Add OCR config keys to `SettingsService`: `ocr_enabled` (default "1"), `ocr_languages` (default "nld+eng"), `ocr_dpi` (default "300")
- [x] 4.2 Add `getOcrStatus()` method to SettingsService that returns Tesseract availability and version via OcrService
- [x] 4.3 Include OCR settings and Tesseract status in `GET /api/settings` response

## 5. Admin Settings Frontend

- [x] 5.1 Add OCR settings section to `src/views/settings/Settings.vue` with NcCheckboxRadioSwitch for enable/disable toggle
- [x] 5.2 Add language selection checkboxes (nld, eng, deu, fra) that compose the Tesseract language string
- [x] 5.3 Add DPI numeric input with validation (72-600 range)
- [x] 5.4 Display Tesseract availability status indicator (success with version or warning NcNoteCard)

## 6. File Listing Frontend Updates

- [x] 6.1 Add OCR status badge/icon to file listing items for files processed with OCR
- [x] 6.2 Display OCR confidence score for OCR-processed files

## 7. Testing and Quality

- [x] 7.1 Add unit tests for `OcrService::needsOcr()` covering all MIME type scenarios
- [x] 7.2 Add unit tests for `OcrService::extractTextFromImage()` with mock Tesseract wrapper
- [x] 7.3 Add integration test for the full OCR pipeline: upload scanned PDF -> extract -> verify entities detected
- [x] 7.4 Run `composer check:strict` and fix any PHPCS, PHPMD, Psalm, or PHPStan issues
- [x] 7.5 Test with nldesign theme enabled to verify admin settings accessibility compliance
