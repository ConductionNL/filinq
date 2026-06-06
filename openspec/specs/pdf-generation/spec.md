---
status: implemented
---

# PDF Generation

## Purpose

Provides a shared, reusable PDF rendering service that any co-installed Nextcloud app can call. Accepts a Twig template string and data context, renders HTML, converts to PDF via mPDF, and returns the binary content. The service is stateless -- callers provide template content directly. Includes a Twig sandbox with strict security policy and an HTTP API endpoint for PDF generation.

> @e2e exclude Pure backend PDF rendering service — Twig-to-HTML-to-mPDF conversion, page configuration, Twig sandbox security policy, mPDF temp-dir management, dependency wiring, and the render REST endpoint (returns binary PDF). No browser-driven UI surface. Verified by PHPUnit TemplateRendererTest/PdfServiceTest and the Newman docudesk-api pdf-render collection.

## Requirements

### REQ-PDF-01: PDF Rendering Service (Priority: Must)

PdfService accepts a Twig template string and data context, renders HTML via a sandboxed Twig environment, and converts to PDF via mPDF.

#### Scenario: Render PDF from template and data
- GIVEN a valid Twig template `<h1>{{ title }}</h1>` and data `{"title": "Hello"}`
- WHEN `PdfService::renderPdf()` is called
- THEN the HTML is rendered with the data context
- AND mPDF converts it to a PDF
- AND the PDF binary string is returned

#### Scenario: Static HTML template without data
- GIVEN a template with no Twig variables: `<h1>Static Report</h1>`
- AND an empty data context
- WHEN renderPdf() is called
- THEN a valid PDF is generated with the static HTML
- AND no errors occur from the empty data

#### Scenario: Invalid Twig syntax
- GIVEN a template with broken syntax: `<h1>{{ unclosed`
- WHEN renderPdf() is called
- THEN an Exception is thrown with a descriptive parse error message
- AND the error message includes "Template rendering failed"

#### Scenario: Service is injectable via DI
- GIVEN any Nextcloud app has DocuDesk as a dependency
- WHEN the app resolves `OCA\DocuDesk\Service\PdfService::class` from the container
- THEN the PdfService instance is provided
- AND the app can generate PDFs without own mPDF/Twig integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-001 | `PdfService::renderPdf()` renders Twig, converts to PDF, returns binary | MUST | Implemented |
| PDF-002 | Service is stateless -- no template lookup or storage | MUST | Implemented |
| PDF-003 | Injectable via DI container | MUST | Implemented |
| PDF-004 | Empty data with static HTML produces valid PDF | MUST | Implemented |
| PDF-005 | Invalid Twig syntax throws Exception with descriptive message | MUST | Implemented |

### REQ-PDF-02: Page Configuration Options (Priority: Must)

PDF output can be configured with page format, orientation, margins, and document title metadata.

#### Scenario: A4 portrait with default margins
- GIVEN no options are specified (empty array)
- WHEN renderPdf() is called
- THEN the output is A4 portrait with 15mm margins on all sides

#### Scenario: Landscape A3 with custom margins
- GIVEN options: `{"format": "A3", "orientation": "L", "margin": {"top": 20, "right": 10, "bottom": 20, "left": 10}}`
- WHEN renderPdf() is called
- THEN the PDF is A3 landscape with the specified margins

#### Scenario: PDF title metadata
- GIVEN options: `{"title": "Annual Report 2024"}`
- WHEN renderPdf() is called
- THEN the PDF document title metadata is set to "Annual Report 2024"

#### Scenario: Letter format
- GIVEN options: `{"format": "Letter"}`
- WHEN renderPdf() is called
- THEN the PDF uses US Letter page size

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-010 | `format` option: A4 (default), A3, Letter, Legal | MUST | Implemented |
| PDF-011 | `orientation` option: P (portrait, default) or L (landscape) | MUST | Implemented |
| PDF-012 | `margin` option: top/right/bottom/left in mm, default 15mm all | MUST | Implemented |
| PDF-013 | `title` option: PDF document title metadata | MUST | Implemented |
| PDF-014 | Empty options = A4 portrait with 15mm margins | MUST | Implemented |

### REQ-PDF-03: Twig Sandbox Security Policy (Priority: Must)

Twig templates are rendered in a sandboxed environment with strict security policy to prevent template injection attacks.

#### Scenario: Allowed filter usage
- GIVEN a template using `{{ name | upper }}`
- WHEN the template is rendered
- THEN the filter is applied successfully (SandboxExtension allows it)

#### Scenario: Forbidden function blocked
- GIVEN a template containing `{{ system('ls') }}`
- WHEN renderTemplate() is called
- THEN the Twig sandbox throws a SecurityError
- AND PDF generation fails with a descriptive error

#### Scenario: Object method call blocked
- GIVEN a template trying `{{ user.getPassword() }}`
- WHEN renderTemplate() is called
- THEN the sandbox blocks the method call (zero allowed methods/properties)
- AND only array access is permitted

#### Scenario: Conditional and loop rendering
- GIVEN a template with `{% if items %}{% for item in items %}{{ item }}{% endfor %}{% endif %}`
- WHEN rendered with `{"items": ["a", "b", "c"]}`
- THEN the output contains "abc"
- AND the if/for tags are in the allowed tags list

#### Scenario: Complete allowed filter list
- GIVEN the sandbox security policy
- WHEN the allowed filters are inspected
- THEN the following 23 filters are permitted: escape, e, upper, lower, trim, nl2br, date, number_format, join, split, first, last, length, default, raw, sort, reverse, keys, values, merge, slice, batch, column, round, abs

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-020 | Twig uses SandboxExtension with strict SecurityPolicy | MUST | Implemented |
| PDF-021 | 23 allowed filters including escape, date, number_format | MUST | Implemented |
| PDF-022 | 5 allowed functions: range, cycle, date, max, min | MUST | Implemented |
| PDF-023 | 10 allowed tags: if, for, set, block, extends, include, macro, spaceless, apply, autoescape | MUST | Implemented |
| PDF-024 | Zero allowed methods and properties on objects | MUST | Implemented |
| PDF-025 | Forbidden functions (system, exec, etc.) blocked by sandbox | MUST | Implemented |

### REQ-PDF-04: mPDF Temp Directory Management (Priority: Must)

mPDF requires a writable temp directory, which is created and configured automatically.

#### Scenario: Temp directory creation
- GIVEN `/tmp/mpdf` does not exist
- WHEN the first PDF generation occurs
- THEN the directory is created with permissions 0777
- AND mPDF uses it as its tempDir

#### Scenario: Temp directory already exists
- GIVEN `/tmp/mpdf` already exists
- WHEN a PDF is generated
- THEN the permissions are set to 0777 (ensured)
- AND mPDF proceeds without error

#### Scenario: mPDF generation failure
- GIVEN mPDF throws an MpdfException during PDF generation
- WHEN the error is caught
- THEN the error is logged with "mPDF generation failed" message
- AND an Exception with code 500 is thrown to the caller

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-030 | Temp directory at `/tmp/mpdf` with 0777 permissions | MUST | Implemented |
| PDF-031 | Temp directory created if not exists | MUST | Implemented |
| PDF-032 | MpdfException caught, logged, and re-thrown as Exception with code 500 | MUST | Implemented |

### REQ-PDF-05: PDF Rendering API Endpoint (Priority: Must)

An HTTP endpoint allows authenticated users to generate PDFs on demand.

#### Scenario: API render with template and data
- GIVEN an authenticated user
- WHEN POST /api/pdf/render is called with `{"template": "<h1>{{ title }}</h1>", "data": {"title": "Hello"}, "filename": "report.pdf"}`
- THEN the response is a PDF download with filename "report.pdf"
- AND the content type is "application/pdf"

#### Scenario: Missing template field
- GIVEN an authenticated user
- WHEN POST /api/pdf/render is called with `{"data": {"title": "Hello"}}`
- THEN a 400 JSONResponse is returned with "template is required"

#### Scenario: Default filename
- GIVEN no filename is specified in the request
- WHEN POST /api/pdf/render is called
- THEN the default filename "document.pdf" is used

#### Scenario: Custom page options via API
- GIVEN options: `{"format": "A3", "orientation": "L"}`
- WHEN POST /api/pdf/render is called with these options
- THEN the generated PDF uses A3 landscape format

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-040 | `POST /api/pdf/render` authenticated endpoint | MUST | Implemented |
| PDF-041 | Accepts: template (required), data (optional), options (optional), filename (optional) | MUST | Implemented |
| PDF-042 | Returns DataDownloadResponse with PDF binary and application/pdf MIME | MUST | Implemented |
| PDF-043 | Returns 400 if template field is missing or empty | MUST | Implemented |

### REQ-PDF-06: Dependencies (Priority: Must)

PdfService requires mPDF and Twig libraries at specific minimum versions.

#### Scenario: mPDF dependency
- GIVEN DocuDesk's composer.json
- WHEN dependencies are inspected
- THEN `mpdf/mpdf: ^8.2` is declared

#### Scenario: Twig dependency
- GIVEN DocuDesk's composer.json
- WHEN dependencies are inspected
- THEN `twig/twig: ^3.18` is declared

#### Scenario: TemplateRenderer separation
- GIVEN PdfService and TemplateRenderer are separate classes
- WHEN PdfService needs to render a template
- THEN it delegates to TemplateRenderer::renderTemplate()
- AND separation of concerns is maintained

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-050 | `mpdf/mpdf: ^8.2` in composer.json | MUST | Implemented |
| PDF-051 | `twig/twig: ^3.18` in composer.json | MUST | Implemented |
| PDF-052 | TemplateRenderer extracted from PdfService for separation of concerns | MUST | Implemented |

### REQ-PDF-07: Twig Sandbox Configuration Details (Priority: Must)

The sandbox configuration is centralized in TemplateRenderer with explicit whitelists.

#### Scenario: Sandbox is always enabled
- GIVEN TemplateRenderer creates a Twig Environment
- WHEN the SandboxExtension is added
- THEN it is created with `sandboxed: true` (always enforced)
- AND the sandbox cannot be bypassed

#### Scenario: strict_variables disabled
- GIVEN the Twig Environment is configured
- WHEN `strict_variables` is inspected
- THEN it is set to `false`
- AND undefined variables render as empty (no error thrown)

#### Scenario: ArrayLoader for template content
- GIVEN template content is passed as a string
- WHEN TemplateRenderer processes it
- THEN an ArrayLoader is used with key "document"
- AND the template is rendered via `$twig->render('document', $data)`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PDF-060 | SandboxExtension created with `sandboxed: true` (always enforced) | MUST | Implemented |
| PDF-061 | `strict_variables: false` -- undefined variables render as empty | MUST | Implemented |
| PDF-062 | ArrayLoader used with template key "document" | MUST | Implemented |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/pdf/render` | Generate PDF from template and data |

## Dependencies

- **mpdf/mpdf ^8.2**: HTML-to-PDF conversion
- **twig/twig ^3.18**: Template rendering with sandbox
- **TemplateRenderer**: Twig rendering (extracted from PdfService)
- **PdfController**: REST API endpoint

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/PdfService.php` -- PDF rendering with mPDF
  - `lib/Service/TemplateRenderer.php` -- Twig sandboxed rendering
  - `lib/Controller/PdfController.php` -- REST API endpoint
  - `composer.json` -- mpdf and twig dependencies
  - `appinfo/routes.php` -- route for pdf#render

### Standards & References
- **PDF 1.4+ (ISO 32000-1)**: mPDF generates PDF 1.4 compliant output
- **PDF/A (ISO 19005)**: Not currently configured; should be considered for archival
- **Twig 3.x Security**: Sandbox prevents template injection
- **WCAG 2.1 AA**: PDF accessibility (tagged PDF) not currently enforced
