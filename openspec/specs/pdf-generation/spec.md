---
status: reviewed
---

# PDF Generation

## Purpose

Provides a shared, reusable PDF rendering service that any co-installed Nextcloud app can call. Accepts a Twig template string and data context, renders HTML, converts to PDF via mPDF, and returns the binary content. The service is stateless — callers provide template content directly. Includes a Twig sandbox with strict security policy, an HTTP API endpoint for PDF generation, and PDF/A-3b archival compliance mode with font embedding and print-optimized CSS.

## Requirements

### PDF Rendering

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-001 | `PdfService::renderPdf(string $templateContent, array $data, array $options): string` renders Twig template with data, converts HTML to PDF via mPDF, returns PDF binary. When `$options['pdfa']` is `true`, output conforms to PDF/A-3b (ISO 19005-3). When `pdfa` is absent or `false`, behavior is standard PDF 1.4 | MUST | Implemented |
| PDF-002 | Service is stateless — does not look up templates or manage storage | MUST | Implemented |
| PDF-003 | Service is injectable via DI container at `OCA\DocuDesk\Service\PdfService::class` | MUST | Implemented |
| PDF-004 | Empty data context with static HTML template renders valid PDF | MUST | Implemented |
| PDF-005 | Invalid Twig syntax throws `\Exception` with descriptive parse error message | MUST | Implemented |
| PDF-006 | `PdfService::renderHtmlPreview(string $templateContent, array $data, array $options): string` renders Twig template with data, injects print-optimized CSS, returns HTML string for browser print preview | MUST | Implemented |

### Page Configuration Options

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-010 | `format` option: page size string, default `'A4'`, accepts mPDF format strings (A4, A3, Letter, Legal) | MUST | Implemented |
| PDF-011 | `orientation` option: `'P'` (portrait, default) or `'L'` (landscape) | MUST | Implemented |
| PDF-012 | `margin` option: array with `top`, `right`, `bottom`, `left` in mm, default 15mm all sides | MUST | Implemented |
| PDF-013 | `title` option: PDF document title metadata, default empty | MUST | Implemented |
| PDF-014 | Empty options array produces A4 portrait with 15mm margins | MUST | Implemented |
| PDF-015 | `pdfa` option: boolean, default `false`. When `true`, enables PDF/A-3b compliance mode with `PDFA: true` and `PDFAauto: true` in mPDF config | MUST | Implemented |

### PDF/A Compliance

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-060 | When PDF/A mode is enabled, mPDF is configured with `PDFA: true` and `PDFAauto: true` | MUST | Implemented |
| PDF-061 | When PDF/A mode is enabled, bundled DejaVu Sans fonts (regular, bold, oblique, bold-oblique) from `lib/Fonts/` are configured via `fontDir` and `fontdata` | MUST | Implemented |
| PDF-062 | When PDF/A mode is enabled, `default_font` is set to `dejavusans` | MUST | Implemented |
| PDF-063 | When PDF/A mode is enabled, print-optimized CSS is prepended to HTML with `@page` size matching format/orientation, `page-break-inside: avoid` for tables/figures, and margin normalization | MUST | Implemented |
| PDF-064 | When PDF/A mode is enabled, XMP metadata is set via `SetAuthor('DocuDesk')` and `SetCreator('DocuDesk PDF/A Generator')` | MUST | Implemented |
| PDF-065 | When PDF/A mode is not enabled, font configuration, print CSS injection, and XMP metadata are not applied (backward compatible) | MUST | Implemented |

### Font Files

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-070 | `lib/Fonts/DejaVuSans.ttf` bundled in app distribution | MUST | Implemented |
| PDF-071 | `lib/Fonts/DejaVuSans-Bold.ttf` bundled in app distribution | MUST | Implemented |
| PDF-072 | `lib/Fonts/DejaVuSans-Oblique.ttf` bundled in app distribution | MUST | Implemented |
| PDF-073 | `lib/Fonts/DejaVuSans-BoldOblique.ttf` bundled in app distribution | MUST | Implemented |
| PDF-074 | `lib/Fonts/LICENSE` (LGPL) bundled with font files | MUST | Implemented |

### Twig Sandbox Security

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-020 | Twig configured with `SandboxExtension` using strict `SecurityPolicy` | MUST | Implemented |
| PDF-021 | Allowed filters: `escape`, `e`, `upper`, `lower`, `trim`, `nl2br`, `date`, `number_format`, `join`, `split`, `first`, `last`, `length`, `default`, `raw`, `sort`, `reverse`, `keys`, `values`, `merge`, `slice`, `batch`, `column`, `round`, `abs` | MUST | Implemented |
| PDF-022 | Allowed functions: `range`, `cycle`, `date`, `max`, `min` | MUST | Implemented |
| PDF-023 | Allowed tags: `if`, `for`, `set`, `block`, `extends`, `include`, `macro`, `spaceless`, `apply`, `autoescape` | MUST | Implemented |
| PDF-024 | Zero allowed methods and properties on objects (data passed as arrays only) | MUST | Implemented |
| PDF-025 | Forbidden functions (`system`, `exec`, `passthru`, `shell_exec`, file operations) blocked by sandbox | MUST | Implemented |

### mPDF Configuration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-030 | Temp directory at `/tmp/mpdf` with 0777 permissions, created if not exists | MUST | Implemented |
| PDF-031 | mPDF `tempDir` config option set to `/tmp/mpdf` | MUST | Implemented |

### API Endpoints

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-040 | `POST /api/pdf/render` authenticated endpoint (`@NoAdminRequired @NoCSRFRequired`) | MUST | Implemented |
| PDF-041 | Accepts JSON body: `template` (string, required), `data` (object), `options` (object), `filename` (string, default `document.pdf`) | MUST | Implemented |
| PDF-042 | Returns `DataDownloadResponse` with PDF binary, filename, and `application/pdf` MIME type | MUST | Implemented |
| PDF-043 | Returns 400 JSONResponse if `template` field is missing or empty | MUST | Implemented |
| PDF-044 | `POST /api/pdf/render-pdfa` authenticated endpoint that behaves identically to `/api/pdf/render` but forces `pdfa: true`. The `pdfa` option in the request body is ignored | MUST | Implemented |

### Print Preview and Download Endpoints

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-080 | `POST /api/print/preview` authenticated endpoint returns JSON `{"html": "<rendered>", "title": "<name>"}` with print-optimized CSS injected | MUST | Implemented |
| PDF-081 | Preview accepts `templateId` (loads stored template via TemplateService) or `template` (inline content) with `data` context | MUST | Implemented |
| PDF-082 | Preview returns 400 if neither `templateId` nor `template` provided | MUST | Implemented |
| PDF-083 | `POST /api/print/pdf-a` authenticated endpoint returns PDF/A-3b `DataDownloadResponse`. Accepts `templateId` or `template` with `data` and optional `filename` | MUST | Implemented |

### Dependencies

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-050 | `mpdf/mpdf: ^8.2` in DocuDesk's `composer.json` | MUST | Implemented |
| PDF-051 | `twig/twig: ^3.18` in DocuDesk's `composer.json` | MUST | Implemented |

## Scenarios

### Render PDF from template and data

```
GIVEN a valid Twig template "<h1>{{ title }}</h1>" and data {"title": "Hello"}
WHEN PdfService::renderPdf() is called
THEN the HTML is rendered with the data context
AND mPDF converts it to a PDF
AND the PDF binary string is returned
```

### API render with template and data

```
GIVEN an authenticated user
WHEN POST /api/pdf/render with {"template": "<h1>{{ title }}</h1>", "data": {"title": "Hello"}, "filename": "report.pdf"}
THEN the response is a PDF download with filename "report.pdf"
AND the PDF contains rendered HTML "<h1>Hello</h1>"
```

### Forbidden Twig function blocked

```
GIVEN a template containing {{ system('ls') }}
WHEN renderPdf() is called
THEN the Twig sandbox throws a SecurityError
AND PDF generation fails with an exception
```

### Render PDF/A with compliance mode

```
GIVEN a valid Twig template and options with pdfa: true
WHEN PdfService::renderPdf() is called
THEN mPDF is configured with PDFA: true and PDFAauto: true
AND DejaVu Sans fonts are embedded via fontDir and fontdata
AND print-optimized CSS is prepended to the HTML
AND XMP metadata (author, creator) is set
AND the output is a PDF/A-3b compliant document
```

### Backward compatible rendering without PDF/A

```
GIVEN a valid Twig template without pdfa option
WHEN PdfService::renderPdf() is called
THEN mPDF produces standard PDF 1.4 output
AND no custom fonts, print CSS, or XMP metadata are added
```

### Print preview returns HTML with CSS

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"template": "<h1>{{ title }}</h1>", "data": {"title": "Hello"}}
THEN the response contains {"html": "<style>@media print{...}</style><h1>Hello</h1>", "title": "document"}
```

### PDF/A download via print controller

```
GIVEN an authenticated user
WHEN POST /api/print/pdf-a with {"templateId": "<uuid>", "data": {"title": "Report"}, "filename": "report.pdf"}
THEN the template is loaded from TemplateService
AND the response is a PDF/A-3b compliant PDF download with filename "report.pdf"
```

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/PdfService.php` -- core PDF rendering: `renderPdf()` with Twig sandbox, mPDF integration, page configuration options, PDF/A-3b compliance mode, `renderHtmlPreview()` for print preview, `buildPrintCss()` for print-optimized CSS
  - `lib/Controller/PdfController.php` -- REST API: `render()` and `renderPdfA()` endpoints
  - `lib/Controller/PrintController.php` -- REST API: `preview()` for HTML print preview, `downloadPdfA()` for PDF/A download, template resolution (templateId or inline)
  - `lib/Fonts/` -- bundled DejaVu Sans TTF files (regular, bold, oblique, bold-oblique) with LGPL license
  - `src/components/PrintPreview.vue` -- Vue component with iframe preview, Print button, and Download PDF/A button
  - `appinfo/routes.php` -- routes for `pdf#render`, `pdf#renderPdfA`, `print#preview`, `print#downloadPdfA`
  - `composer.json` -- dependencies: `mpdf/mpdf: ^8.2`, `twig/twig: ^3.18`
- **Used by other specs**:
  - `template-management` spec uses PdfService indirectly (templates designed for PDF rendering)
  - `document-creatie-sjablonen` spec (planned) will orchestrate PdfService for document generation

### Standards & References
- **PDF 1.4+ (ISO 32000-1)**: mPDF generates PDF 1.4 compliant output (default mode)
- **PDF/A-3b (ISO 19005-3)**: Enabled via `pdfa: true` option, with embedded fonts and XMP metadata
- **WCAG 2.1 AA**: PDF accessibility (tagged PDF, reading order, alt text) is not currently enforced by mPDF configuration
- **Twig 3.x Security**: Sandbox extension with SecurityPolicy prevents template injection attacks
