---
status: proposed
source: market-intelligence
clusters: [112]
total_tenders: 39
total_requirements: 155
---

# Print Functionality

## Summary

Add print-optimized output generation and PDF/A archival compliance to DocuDesk. Government organizations need to produce print-ready documents (for physical mail dispatch, official publications, and archive copies) and PDF/A compliant output for long-term digital archiving. This extends DocuDesk's existing PDF generation with print-specific features and archival format support.

## Demand Evidence

### Cluster 112: Print functionality
- **39 tenders**, **155 requirements** (primarily Dutch government via TenderNed)
- Country distribution: TenderNed 46 reqs, Belgium 6 reqs
- High requirement count relative to tender count indicates detailed print specs per tender

### Sample Requirements from Tenders
- **Gemeente Amsterdam**: "Scannen, printen en verzenden -- omzetten printbestanden naar documenten/stukken met de minimaal benodigde metadata."
- **Gemeente Ede**: "Het afdrukken van niet voltooide printopdrachten wordt niet automatisch vervolgd nadat een storing in de keten of de apparatuur is verholpen."
- **Sovon**: "De mogelijkheid voor afdrukken in full color dient uitgeschakeld te kunnen worden."
- **Sovon**: "De dubbelzijdige afdrukmodus (kopieeren en printen) moet minimaal 1:2, 2:1 en 2:2 aan kunnen."

## What Docudesk Already Does

- **PDF Generation** (implemented): `PdfService` renders Twig templates to PDF via mPDF with configurable page format (A4/A3/Letter/Legal), orientation, margins, and title metadata
- **Template Management** (implemented): Templates with format and orientation settings
- **Document Creatie Sjablonen** (planned spec): End-to-end document generation from templates with data merging

### What Is Missing
- No PDF/A output (required for archiving per NEN 2082 / Archiefwet)
- No print-optimized output (crop marks, bleed, CMYK color space)
- No batch print generation (produce multiple documents ready for physical mail)
- No print job tracking or queue management
- No duplex/simplex configuration per template

## Scope

### In Scope
1. **PDF/A compliant output** -- generate PDF/A-1b and PDF/A-2b compliant documents for long-term archiving, meeting NEN 2082 and Archiefwet requirements
2. **Print-optimized PDF** -- output with proper crop marks, bleed area, CMYK color profile, and embedded fonts for professional print services
3. **Batch print generation** -- generate multiple print-ready documents in one operation (e.g., 500 beschikkingen for mail dispatch), packaged as a single PDF or ZIP of individual files
4. **Print configuration per template** -- configure duplex/simplex, color/grayscale, paper tray selection hints, and stapling preferences as template metadata
5. **Print queue integration** -- API endpoint for external print services (e.g., Ricoh, Canon) to retrieve print-ready documents with print instructions (JSON metadata + PDF)
6. **Archive-ready output** -- combine PDF/A generation with metadata embedding (title, author, creation date, case reference) for automated archival workflows

### Out of Scope
- Direct printer driver integration (print goes through external print services or OS print dialog)
- Physical printer management (hardware monitoring, toner levels)
- Scan-to-print copying (covered by OCR/scanning change)

## Acceptance Criteria

1. GIVEN a template, WHEN PDF/A-2b output is requested, THEN the generated PDF passes PDF/A validation (e.g., veraPDF), AND all fonts are embedded, AND no transparency or encryption is used
2. GIVEN a batch of 100 beschikkingen, WHEN batch print generation is triggered, THEN 100 individual print-ready PDFs are generated, AND a manifest file lists all documents with metadata
3. GIVEN a template configured for duplex color printing, WHEN a print job is created, THEN the print metadata includes duplex=true and color=true for the print service
4. GIVEN a generated document, WHEN archived, THEN the PDF/A contains embedded XMP metadata with title, author, creation date, and case reference
5. GIVEN an external print service, WHEN it calls the print queue API, THEN it receives the PDF binary and a JSON print instruction file with all configured print parameters
6. GIVEN a print-optimized PDF request with crop marks, WHEN generated, THEN the PDF includes 3mm bleed area and crop marks on all four corners

## Risks and Dependencies

- mPDF has limited PDF/A support; may need to switch to or supplement with another library (e.g., TCPDF, or post-process with Ghostscript)
- CMYK color profile embedding requires ICC profile files bundled with the app
- Batch generation of hundreds of documents needs background job processing
- Print queue API needs authentication for external print services
- PDF/A validation should be part of CI to prevent regressions
