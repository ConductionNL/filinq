## MODIFIED Requirements

### Requirement: PdfService renderPdf with PDF/A support
`PdfService::renderPdf(string $templateContent, array $data, array $options): string` renders Twig template with data, converts HTML to PDF via mPDF, returns PDF binary. When `$options['pdfa']` is `true`, the output SHALL conform to PDF/A-3b (ISO 19005-3). When `pdfa` option is absent or `false`, behavior is unchanged (PDF 1.4 output).

#### Scenario: Render PDF with PDF/A mode enabled
- **WHEN** `PdfService::renderPdf()` is called with `$options['pdfa'] => true`
- **THEN** mPDF is configured with `PDFA: true` and the output is a valid PDF/A-3b document

#### Scenario: Render PDF without PDF/A mode (backward compatible)
- **WHEN** `PdfService::renderPdf()` is called without `pdfa` option or with `$options['pdfa'] => false`
- **THEN** mPDF produces standard PDF 1.4 output (existing behavior unchanged)

#### Scenario: PDF/A output includes XMP metadata
- **WHEN** PDF/A mode is enabled and a `title` option is provided
- **THEN** the PDF contains XMP metadata with the document title conforming to PDF/A requirements

### Requirement: Font embedding configuration
When PDF/A mode is enabled, `PdfService` SHALL configure mPDF to use bundled DejaVu Sans fonts (regular, bold, italic, bold-italic) from `lib/Fonts/`. The `fontDir` and `fontdata` mPDF configuration options MUST be set to include the bundled font directory. Font subsetting SHALL be enabled to minimize file size.

#### Scenario: PDF/A output uses embedded fonts
- **WHEN** PDF/A mode is enabled
- **THEN** mPDF `fontDir` includes DocuDesk's `lib/Fonts/` directory and `fontdata` includes DejaVu Sans family entries

#### Scenario: Standard PDF mode does not change font behavior
- **WHEN** PDF/A mode is not enabled
- **THEN** mPDF uses its default font configuration (no custom fontDir or fontdata)

### Requirement: Print-optimized CSS injection for PDF/A
When PDF/A mode is enabled, `PdfService` SHALL prepend print-optimized CSS to the HTML before PDF generation. The CSS MUST include `page-break-inside: avoid` for tables and figures, normalized margins, and `@page` size matching the configured format and orientation.

#### Scenario: PDF/A generation includes print CSS
- **WHEN** PDF/A mode is enabled with format "A4" and orientation "P"
- **THEN** the HTML passed to mPDF includes a `<style>` block with `@page { size: A4 portrait; }` and print optimization rules prepended

#### Scenario: Standard PDF mode does not inject print CSS
- **WHEN** PDF/A mode is not enabled
- **THEN** no additional CSS is injected into the HTML (existing behavior unchanged)

## ADDED Requirements

### Requirement: PDF/A render API endpoint
The system SHALL provide a `POST /api/pdf/render-pdfa` endpoint that behaves identically to `POST /api/pdf/render` but forces `pdfa: true` in the options. This provides a dedicated endpoint for callers that always want archival-compliant output.

#### Scenario: Render PDF/A via dedicated endpoint
- **WHEN** an authenticated user sends `POST /api/pdf/render-pdfa` with `{"template": "<h1>{{ title }}</h1>", "data": {"title": "Hello"}, "filename": "archive.pdf"}`
- **THEN** the response is a PDF/A-3b compliant PDF download with filename "archive.pdf"

#### Scenario: Dedicated endpoint ignores pdfa option in body
- **WHEN** an authenticated user sends `POST /api/pdf/render-pdfa` with `{"template": "...", "options": {"pdfa": false}}`
- **THEN** the output is still PDF/A-3b compliant (endpoint always forces PDF/A)

### Requirement: Bundled DejaVu Sans font files
The system SHALL include DejaVu Sans font files (DejaVuSans.ttf, DejaVuSans-Bold.ttf, DejaVuSans-Oblique.ttf, DejaVuSans-BoldOblique.ttf) in `lib/Fonts/`. These files MUST be LGPL-licensed and included in the app distribution.

#### Scenario: Font files exist in distribution
- **WHEN** the DocuDesk app is installed
- **THEN** the directory `lib/Fonts/` contains all four DejaVu Sans TTF files
