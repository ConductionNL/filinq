---
status: reviewed
---

# PDF Generation

## Purpose

Provides a shared, reusable PDF rendering service that any co-installed Nextcloud app can call. Accepts a Twig template string and data context, renders HTML, converts to PDF via mPDF, and returns the binary content. The service is stateless — callers provide template content directly. Includes a Twig sandbox with strict security policy and an HTTP API endpoint for PDF generation.

## Requirements

### PDF Rendering

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-001 | `PdfService::renderPdf(string $templateContent, array $data, array $options): string` renders Twig template with data, converts HTML to PDF via mPDF, returns PDF binary | MUST | Implemented |
| PDF-002 | Service is stateless — does not look up templates or manage storage | MUST | Implemented |
| PDF-003 | Service is injectable via DI container at `OCA\DocuDesk\Service\PdfService::class` | MUST | Implemented |
| PDF-004 | Empty data context with static HTML template renders valid PDF | MUST | Implemented |
| PDF-005 | Invalid Twig syntax throws `\Exception` with descriptive parse error message | MUST | Implemented |

### Page Configuration Options

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-010 | `format` option: page size string, default `'A4'`, accepts mPDF format strings (A4, A3, Letter, Legal) | MUST | Implemented |
| PDF-011 | `orientation` option: `'P'` (portrait, default) or `'L'` (landscape) | MUST | Implemented |
| PDF-012 | `margin` option: array with `top`, `right`, `bottom`, `left` in mm, default 15mm all sides | MUST | Implemented |
| PDF-013 | `title` option: PDF document title metadata, default empty | MUST | Implemented |
| PDF-014 | Empty options array produces A4 portrait with 15mm margins | MUST | Implemented |

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

### API Endpoint

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-040 | `POST /api/pdf/render` authenticated endpoint (`@NoAdminRequired @NoCSRFRequired`) | MUST | Implemented |
| PDF-041 | Accepts JSON body: `template` (string, required), `data` (object), `options` (object), `filename` (string, default `document.pdf`) | MUST | Implemented |
| PDF-042 | Returns `DataDownloadResponse` with PDF binary, filename, and `application/pdf` MIME type | MUST | Implemented |
| PDF-043 | Returns 400 JSONResponse if `template` field is missing or empty | MUST | Implemented |

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

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/PdfService.php` -- core PDF rendering: `renderPdf()` with Twig sandbox, mPDF integration, page configuration options
  - `lib/Controller/PdfController.php` -- REST API: `render()` endpoint returning DataDownloadResponse
  - `appinfo/routes.php` -- route for `pdf#render` (POST `/api/pdf/render`)
  - `composer.json` -- dependencies: `mpdf/mpdf: ^8.2`, `twig/twig: ^3.18`
- **Not yet implemented**: Nothing -- all requirements (PDF-001 through PDF-051) are fully implemented
- **Used by other specs**:
  - `template-management` spec uses PdfService indirectly (templates designed for PDF rendering)
  - `document-creatie-sjablonen` spec (planned) will orchestrate PdfService for document generation

### Standards & References
- **PDF 1.4+ (ISO 32000-1)**: mPDF generates PDF 1.4 compliant output
- **PDF/A (ISO 19005)**: Not currently enforced -- mPDF can produce PDF/A but it's not configured. Should be considered for government archival use.
- **WCAG 2.1 AA**: PDF accessibility (tagged PDF, reading order, alt text) is not currently enforced by mPDF configuration
- **Twig 3.x Security**: Sandbox extension with SecurityPolicy prevents template injection attacks

### Specificity Assessment
- **Specific enough**: Yes, this spec is concise and complete. The Twig sandbox policy is exhaustively documented.
- **Missing/Ambiguous**: No mention of PDF/A compliance mode (important for Dutch government archival). No mention of font embedding or Unicode support. No maximum template size or rendering timeout specified.
- **Open questions**:
  1. Should PDF/A output mode be supported (add `PDFA: true` to mPDF config)?
  2. Should fonts be embedded for consistent rendering across systems?
  3. Is there a rendering timeout to prevent DoS via complex templates?
  4. Should the `/tmp/mpdf` temp directory be cleaned up periodically?
