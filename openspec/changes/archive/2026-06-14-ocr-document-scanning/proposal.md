---
status: proposed
source: market-intelligence
clusters: [144, 110]
total_tenders: 61
total_requirements: 98
---

# OCR / Document Scanning

## Summary

Add OCR (Optical Character Recognition) capabilities to DocuDesk for extracting text from scanned documents and images. Many government organizations still receive physical documents that are scanned into the system; these scanned PDFs and images need text extraction for searchability, anonymization, and document classification. This integrates with DocuDesk's existing text extraction and anonymization pipelines.

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

## What Docudesk Already Does

- **Text Extraction** (implemented): `DocumentTextExtractor` service extracts text from documents -- but only from text-based PDFs (not scanned/image PDFs)
- **Entity Detection** (implemented): `EntityDetectionService` processes extracted text for NER -- this already works once text is available
- **Anonymization Pipeline** (implemented): Full pipeline from upload to anonymization -- OCR would feed into this pipeline
- **Metadata Enrichment** (implemented/spec): `MetadataService` enriches documents with extracted metadata

### What Is Missing
- No OCR engine integration (cannot extract text from images or scanned PDFs)
- No image pre-processing (deskew, denoise, contrast adjustment)
- No auto-classification of scanned documents based on content
- No scan quality assessment

## Scope

### In Scope
1. **OCR engine integration** -- integrate Tesseract OCR (open source, runs locally) for text extraction from scanned PDFs and images (JPEG, PNG, TIFF)
2. **Image pre-processing** -- automatic deskew, denoise, and contrast adjustment to improve OCR accuracy
3. **Scanned PDF detection** -- automatically detect whether a PDF is text-based or image-based, and route image-based PDFs through OCR
4. **Searchable PDF output** -- produce PDF/A with embedded OCR text layer (invisible text behind the scanned image) for searchability
5. **Auto-classification** -- classify scanned documents based on extracted text content (e.g., "brief", "factuur", "beschikking") using keyword matching or ML classification
6. **OCR confidence scoring** -- report per-page and per-word confidence scores; flag low-confidence results for manual review
7. **Integration with anonymization pipeline** -- OCR output feeds directly into entity detection and anonymization

### Out of Scope
- Physical scanner hardware integration (documents must already be digitized)
- Handwriting recognition (ICR)
- Form field extraction (structured data from forms)
- Cloud-based OCR services (local processing only, consistent with privacy-by-design)

## Acceptance Criteria

1. GIVEN a scanned PDF (image-only, no text layer), WHEN uploaded to DocuDesk, THEN OCR extracts the text content, AND the text is searchable
2. GIVEN a mixed PDF with both text pages and scanned pages, WHEN processed, THEN only the scanned pages go through OCR, AND text pages retain their original text
3. GIVEN a scanned document with slight skew, WHEN pre-processing runs, THEN the image is deskewed before OCR, AND OCR accuracy is improved
4. GIVEN OCR output with confidence scores, WHEN a page has average confidence below 70%, THEN it is flagged for manual review
5. GIVEN a scanned beschikking, WHEN auto-classification runs on the OCR text, THEN the document is classified as "beschikking" based on content keywords
6. GIVEN an OCR-processed document, WHEN the anonymization pipeline is triggered, THEN entities are detected from the OCR text, AND the document can be anonymized
7. GIVEN a scanned PDF, WHEN searchable PDF output is requested, THEN a PDF/A is produced with an invisible text layer behind the original scan images

## Risks and Dependencies

- Tesseract OCR quality varies significantly with scan quality; pre-processing is critical
- OCR is CPU-intensive; batch processing needs background jobs and may need resource limits
- Dutch language OCR model (Tesseract `nld`) needed; accuracy for handwritten text is poor
- PDF/A output with embedded text layer requires careful PDF manipulation (e.g., via PyMuPDF or mPDF)
- Local-only processing may be slower than cloud OCR but maintains privacy-by-design principle
