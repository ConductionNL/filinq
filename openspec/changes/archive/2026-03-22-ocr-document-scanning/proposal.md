## Why

DocuDesk's anonymization pipeline currently relies on OpenRegister's TextExtractionService which extracts text from digital-born documents (PDFs with embedded text, Word files, etc.). Scanned documents -- image-based PDFs, TIFF files, and photographs of documents -- contain no extractable text, causing the entity detection and anonymization steps to silently produce empty results. Government organizations frequently receive scanned correspondence, legacy archives, and photographed documents that require GDPR-compliant anonymization before publication under the Wet Open Overheid (WOO). Adding Tesseract OCR as a pre-processing step enables text extraction from these image-based documents, keeping all processing 100% local.

## What Changes

- Add a new `OcrService` that wraps Tesseract OCR (via `thiagoalessio/tesseract_ocr` PHP library) for extracting text from image-based documents
- Detect whether a file requires OCR (image MIME types, PDFs without embedded text) and run OCR automatically before entity extraction
- Add admin settings for OCR: enable/disable toggle, language selection (nld, eng, deu, fra), and DPI configuration
- Integrate OCR into the existing anonymization pipeline so scanned documents flow through: upload -> OCR -> extract entities -> anonymize
- Add a Dockerfile/docker-compose update to install `tesseract-ocr` and language packs in the Nextcloud container
- Display OCR status and extracted text confidence in the file listing UI

## Capabilities

### New Capabilities
- `ocr-document-scanning`: OCR text extraction from scanned/image-based documents using Tesseract, integrated into the anonymization pipeline with admin-configurable language support

### Modified Capabilities
- `anonymization`: The extraction step must detect image-based documents and run OCR before entity extraction, adding an OCR pre-processing stage to the pipeline
- `admin-settings`: New OCR configuration section (enable/disable, languages, DPI) in admin settings

## Impact

- **Backend**: New `OcrService` class, modifications to `AnonymizationService` extraction flow
- **Frontend**: OCR status indicators in file listing, new admin settings fields
- **Dependencies**: New Composer dependency `thiagoalessio/tesseract_ocr`; Tesseract binary must be installed in the container
- **Docker**: `openregister/docker-compose.yml` Nextcloud image needs `tesseract-ocr` + language packs
- **APIs**: No new endpoints; existing extraction endpoint gains OCR capability transparently
- **Data**: OCR-extracted text stored in the same OpenRegister text fields as digitally-extracted text
