## Why

DocuDesk generates PDF documents via mPDF, but the output is standard PDF 1.4 without archival compliance. Dutch government tenders (97K requirements across 39K tenders) consistently require PDF/A (ISO 19005) output for long-term archival. The current `PdfService` has no PDF/A mode, no font embedding guarantees, and no print-optimized CSS handling. Adding print functionality with PDF/A compliance addresses a critical gap for government document workflows and enables browser-based print preview before PDF generation.

## What Changes

- Add PDF/A-3b compliance mode to `PdfService` via mPDF's `PDFA` configuration option
- Embed fonts (DejaVu Sans family) to guarantee consistent rendering across systems
- Add a print-preview API endpoint that returns rendered HTML suitable for browser `window.print()`
- Add print-optimized CSS media queries to template rendering pipeline
- Add a Vue print-preview component with PDF/A download action
- Expose PDF/A as a toggle option on existing PDF generation endpoints

## Capabilities

### New Capabilities
- `print-preview`: Browser-based print preview with rendered HTML output, print-optimized CSS, and PDF/A download action from the preview screen

### Modified Capabilities
- `pdf-generation`: Add PDF/A-3b compliance mode (`pdfa` option), font embedding configuration, and print-optimized CSS injection to existing `PdfService::renderPdf()`

## Impact

- **Backend**: `PdfService.php` gains PDF/A config path and font embedding; new `PrintController.php` for preview endpoint
- **Frontend**: New `PrintPreview.vue` component with print dialog integration
- **Routes**: New `GET /api/print/preview` and `POST /api/print/pdf-a` endpoints
- **Dependencies**: No new composer dependencies (mPDF already supports PDF/A natively); DejaVu Sans fonts bundled or referenced from system
- **Standards**: ISO 19005-3 (PDF/A-3b), WCAG 2.1 AA for print preview accessibility
- **All processing remains 100% local** — no external services involved
