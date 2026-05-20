---
status: reviewed
source: market-intelligence
clusters: [144, 110]
total_tenders: 61
total_requirements: 98
---

# OCR / Document Scanning

## Summary

Add OCR (Optical Character Recognition) capabilities to DocuDesk for extracting text from scanned documents and images. Many government organizations still receive physical documents that are scanned into the system; these scanned PDFs and images need text extraction for searchability, anonymization, and document classification. This integrates with DocuDesk's existing text extraction and anonymization pipelines. All processing runs 100% on the server with no external cloud dependencies, in compliance with DocuDesk's local processing standard.

## Demand Evidence

### Cluster 144: OCR / document scanning
- **19 tenders**, **32 requirements** (primarily Dutch government via TenderNed)
- Country distribution: TenderNed 277 reqs, Belgium 39 reqs

### Cluster 110: Document scanning
- **42 tenders**, **66 requirements**
- Country distribution: TenderNed 40 reqs, Belgium 2 reqs

### Sample Requirements from Tenders
- **Gemeente Gulpen-Wittem**: "De output van het scannen wordt geautomatiseerd aangeboden aan de Oplossing. De Oplossing biedt de mogelijkheid om deze output toe te voegen aan een registratie."
- **Gemeente Noordenveld**: "De oplossing moet aansluiten op de centrale scanstraat, bediend door een centrale DIV-afdeling waar binnenkomende post wordt gescand en eventuele bulk wordt verwerkt."
- **Gemeente Amsterdam**: "Scannen, printen en verzenden -- omzetten printbestanden PPS naar documenten/stukken met de minimaal benodigde metadata. Beoordelingsproces scan..."
- **Gemeente Geertruidenberg**: "U vraagt een voorziening uit om post in te scannen (scanoplossing met scansoftware)."

## What DocuDesk Already Does

- **Text Extraction** (implemented): `DocumentTextExtractor` service extracts text from documents -- but only from text-based PDFs (not scanned/image PDFs)
- **Entity Detection** (implemented): `EntityDetectionService` processes extracted text for NER -- this already works once text is available
- **Anonymization Pipeline** (implemented): Full pipeline from upload to anonymization -- OCR feeds into this pipeline
- **Metadata Enrichment** (implemented): `MetadataService` enriches documents with extracted metadata

### What Was Missing (now addressed)
- No OCR engine integration (could not extract text from images or scanned PDFs)
- No automatic scanned PDF detection (image-based vs. text-based)
- No OCR confidence scoring per document
- No graceful degradation when Tesseract is not installed
- No admin visibility into OCR availability and version

## Scope

### In Scope (Implemented)
1. **OCR engine integration** -- Tesseract OCR (`thiagoalessio/tesseract_ocr`) for text extraction from scanned PDFs and image files (JPEG, PNG, TIFF); runs fully locally
2. **Scanned PDF detection** -- automatically detect whether a PDF is text-based or image-based, routing image-based PDFs through OCR
3. **PDF-to-image conversion** -- use PHP Imagick to convert each PDF page to an image before passing to Tesseract
4. **OCR confidence scoring** -- report Tesseract mean confidence score (0--100) per file; exposed in file listing
5. **OCR configuration** -- configurable language models (default `nld+eng`) and DPI (default 300) via admin settings stored in `IAppConfig`
6. **Graceful degradation** -- continue operating normally when Tesseract binary is absent; warn in logs and display status in admin settings
7. **Admin visibility** -- Tesseract installation status and version shown in admin settings panel
8. **Integration with anonymization pipeline** -- OCR output feeds directly into entity detection and anonymization

### Out of Scope
- Physical scanner hardware integration (documents must already be digitized)
- Handwriting recognition (ICR)
- Form field extraction (structured data from forms)
- Cloud-based OCR services (local processing only, consistent with privacy-by-design)
- Image pre-processing (deskew, denoise, contrast adjustment) -- deferred to a future change
- Searchable PDF output (PDF/A with embedded text layer) -- deferred to a future change
- Auto-classification of document type based on OCR text -- deferred to a future change

## Acceptance Criteria

1. GIVEN a scanned PDF (image-only, no text layer), WHEN uploaded to DocuDesk, THEN OCR extracts the text content using Tesseract, AND the text is available for entity detection and anonymization
2. GIVEN a digital-born PDF with embedded text, WHEN processed, THEN OCR is skipped and the existing text extraction path is used
3. GIVEN an image file (PNG, JPG, TIFF), WHEN processed, THEN OCR runs directly via Tesseract without Imagick conversion
4. GIVEN Tesseract is not installed on the server, WHEN any document is processed, THEN OCR is skipped with a warning log, AND all other DocuDesk functionality continues normally
5. GIVEN an OCR-processed document, WHEN the file listing is requested, THEN `ocrProcessed: true` and a confidence score (0--100) are returned per file
6. GIVEN an admin navigates to the DocuDesk settings page, WHEN viewing the settings, THEN Tesseract installation status and version are displayed
7. GIVEN an OCR-processed document, WHEN the anonymization pipeline is triggered, THEN entities are detected from the OCR-extracted text, AND the document can be anonymized

## Risks and Dependencies

- Tesseract binary must be installed on the server; graceful degradation handles absence
- Imagick PHP extension required for PDF-to-image conversion
- OCR is CPU-intensive for large documents; background job processing is recommended for batches
- Dutch language OCR model (`nld`) needed for accuracy; configured via `ocr_languages` setting
- Local-only processing maintains privacy-by-design principle at the cost of potentially slower throughput vs. cloud OCR
