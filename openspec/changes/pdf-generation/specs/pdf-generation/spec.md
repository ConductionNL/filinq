---
status: implemented
---

# PDF Generation

## Purpose

Defines a stateless, injectable PDF rendering service (`PdfService`) for DocuDesk and co-installed Nextcloud apps. Callers supply a Twig template string and a data context; the service renders the template through a sandboxed Twig environment, converts the resulting HTML to PDF via mPDF, and returns the PDF binary. Includes a strict Twig security policy, configurable page options, automatic mPDF temp-directory management, and an authenticated HTTP endpoint for on-demand PDF generation from non-PHP callers.

## ADDED Requirements

### REQ-PDF-01: PDF Rendering Service (Priority: Must)

`PdfService::renderPdf(string $templateContent, array $data = [], array $options = []): string` MUST accept a Twig template string and data context, render HTML via a sandboxed Twig environment, convert to PDF via mPDF, and return the PDF binary as a string. The service MUST be stateless — it MUST NOT perform template lookup or storage of any kind. It MUST be injectable via the Nextcloud DI container as `OCA\DocuDesk\Service\PdfService::class`.

#### Scenario: Render PDF from template and data

- **GIVEN** a valid Twig template `<h1>{{ title }}</h1>` and data `{"title": "Hello"}`
- **WHEN** `PdfService::renderPdf()` is called
- **THEN** the HTML is rendered with the data context
- **AND** mPDF converts it to a PDF
- **AND** the PDF binary string is returned

#### Scenario: Static HTML template without data

- **GIVEN** a template with no Twig variables: `<h1>Static Report</h1>`
- **AND** an empty data context `[]`
- **WHEN** `renderPdf()` is called
- **THEN** a valid PDF is generated containing the static HTML
- **AND** no errors occur from the empty data context

#### Scenario: Invalid Twig syntax throws a descriptive exception

- **GIVEN** a template with broken syntax: `<h1>{{ unclosed`
- **WHEN** `renderPdf()` is called
- **THEN** an `Exception` is thrown
- **AND** the exception message includes "Template rendering failed"

#### Scenario: Service is injectable via DI

- **GIVEN** any Nextcloud app that declares DocuDesk as a dependency
- **WHEN** the app resolves `OCA\DocuDesk\Service\PdfService::class` from the Nextcloud DI container
- **THEN** a `PdfService` instance is returned
- **AND** the app can generate PDFs without bundling its own mPDF or Twig integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-001 | `PdfService::renderPdf()` renders Twig template with data, converts to PDF, returns binary | MUST | Implemented |
| PDF-002 | Service is stateless — no template lookup or storage | MUST | Implemented |
| PDF-003 | Injectable via Nextcloud DI container | MUST | Implemented |
| PDF-004 | Empty data array with static HTML produces a valid PDF without errors | MUST | Implemented |
| PDF-005 | Invalid Twig syntax throws `Exception` with message containing "Template rendering failed" | MUST | Implemented |

### REQ-PDF-02: Page Configuration Options (Priority: Must)

The `$options` parameter of `renderPdf()` MUST support configuring the page format, orientation, margins, and document title metadata. All options MUST have sensible defaults; passing an empty array MUST produce A4 portrait output with 15 mm margins.

#### Scenario: A4 portrait with default margins

- **GIVEN** no options are specified (empty array `[]`)
- **WHEN** `renderPdf()` is called
- **THEN** the output PDF is A4 portrait
- **AND** all four margins are 15 mm

#### Scenario: Landscape A3 with custom margins

- **GIVEN** options `{"format": "A3", "orientation": "L", "margin": {"top": 20, "right": 10, "bottom": 20, "left": 10}}`
- **WHEN** `renderPdf()` is called
- **THEN** the PDF is A3 landscape
- **AND** the margins match the specified values in mm

#### Scenario: PDF title metadata

- **GIVEN** options `{"title": "Jaarrapportage 2024"}`
- **WHEN** `renderPdf()` is called
- **THEN** the PDF document title metadata is set to "Jaarrapportage 2024"

#### Scenario: US Letter format

- **GIVEN** options `{"format": "Letter"}`
- **WHEN** `renderPdf()` is called
- **THEN** the PDF uses US Letter page size

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-010 | `format` option accepts A4 (default), A3, Letter, Legal | MUST | Implemented |
| PDF-011 | `orientation` option accepts P (portrait, default) or L (landscape) | MUST | Implemented |
| PDF-012 | `margin` option accepts top/right/bottom/left in mm, default 15 mm each | MUST | Implemented |
| PDF-013 | `title` option sets the PDF document title metadata | MUST | Implemented |
| PDF-014 | Empty options array produces A4 portrait with 15 mm margins | MUST | Implemented |

### REQ-PDF-03: Twig Sandbox Security Policy (Priority: Must)

Templates MUST be rendered inside a Twig `SandboxExtension` configured with `sandboxed: true`. The sandbox MUST enforce a strict `SecurityPolicy` whitelist. Calls to forbidden functions (e.g. `system`, `exec`) MUST throw a Twig `SecurityError`. Object method and property access MUST be blocked; only plain array data is permitted in templates.

#### Scenario: Allowed filter applied successfully

- **GIVEN** a template using `{{ naam | upper }}`
- **WHEN** the template is rendered
- **THEN** the `upper` filter is applied (value returned in uppercase)
- **AND** no `SecurityError` is thrown

#### Scenario: Forbidden function blocked by sandbox

- **GIVEN** a template containing `{{ system('ls') }}`
- **WHEN** `renderPdf()` is called
- **THEN** the Twig sandbox throws a `SecurityError`
- **AND** PDF generation fails with a descriptive error
- **AND** no system command is executed

#### Scenario: Object method call blocked

- **GIVEN** a template containing `{{ user.getPassword() }}`
- **WHEN** `renderPdf()` is called
- **THEN** the sandbox blocks the method call (zero allowed methods)
- **AND** an exception is raised before any PDF is produced

#### Scenario: Conditional and loop rendering with allowed tags

- **GIVEN** a template `{% if items %}{% for item in items %}{{ item }}{% endfor %}{% endif %}`
- **AND** data `{"items": ["a", "b", "c"]}`
- **WHEN** the template is rendered
- **THEN** the output contains "abc"
- **AND** no `SecurityError` is thrown (if/for are in the allowed tags list)

#### Scenario: Complete allowed filter set is enforced

- **GIVEN** the sandbox `SecurityPolicy`
- **WHEN** the allowed filters are inspected
- **THEN** exactly the following 23 filters are permitted: `escape`, `e`, `upper`, `lower`, `trim`, `nl2br`, `date`, `number_format`, `join`, `split`, `first`, `last`, `length`, `default`, `raw`, `sort`, `reverse`, `keys`, `values`, `merge`, `slice`, `batch`, `column`, `round`, `abs`

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-020 | Twig environment uses `SandboxExtension` with strict `SecurityPolicy` | MUST | Implemented |
| PDF-021 | 23 allowed filters including `escape`, `date`, `number_format`, `upper` | MUST | Implemented |
| PDF-022 | 5 allowed functions: `range`, `cycle`, `date`, `max`, `min` | MUST | Implemented |
| PDF-023 | 10 allowed tags: `if`, `for`, `set`, `block`, `extends`, `include`, `macro`, `spaceless`, `apply`, `autoescape` | MUST | Implemented |
| PDF-024 | Zero allowed methods and properties on objects; only array access permitted | MUST | Implemented |
| PDF-025 | Forbidden functions (system, exec, etc.) blocked with `SecurityError` | MUST | Implemented |

### REQ-PDF-04: mPDF Temp Directory Management (Priority: Must)

The service MUST ensure a writable temp directory exists at `/tmp/mpdf` before creating the mPDF instance. The directory MUST be created with `0777` permissions if absent, and its permissions MUST be verified on every invocation. mPDF failures MUST be caught, logged, and re-thrown as an `Exception` with HTTP status code 500.

#### Scenario: Temp directory created on first use

- **GIVEN** `/tmp/mpdf` does not exist
- **WHEN** the first PDF generation occurs
- **THEN** the directory is created with permissions `0777`
- **AND** mPDF uses it as its `tempDir`

#### Scenario: Temp directory already exists

- **GIVEN** `/tmp/mpdf` already exists
- **WHEN** a PDF is generated
- **THEN** permissions are set to `0777` (ensured idempotently)
- **AND** mPDF proceeds without error

#### Scenario: mPDF generation failure

- **GIVEN** mPDF throws an `MpdfException` during PDF generation
- **WHEN** the exception is caught
- **THEN** the error is logged with message "mPDF generation failed"
- **AND** an `Exception` with code 500 is thrown to the caller

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-030 | Temp directory at `/tmp/mpdf` with `0777` permissions | MUST | Implemented |
| PDF-031 | Temp directory created if absent before mPDF instantiation | MUST | Implemented |
| PDF-032 | `MpdfException` caught, logged with "mPDF generation failed", and re-thrown as `Exception` with code 500 | MUST | Implemented |

### REQ-PDF-05: PDF Rendering API Endpoint (Priority: Must)

An authenticated HTTP endpoint MUST allow callers to generate PDFs on demand without PHP code. The endpoint MUST validate that a `template` field is present in the request body and return HTTP 400 if it is missing. PDF output MUST be returned as a binary download response.

#### Scenario: Successful render via API

- **GIVEN** an authenticated Nextcloud user
- **WHEN** `POST /apps/docudesk/api/pdf/render` is called with body `{"template": "<h1>{{ titel }}</h1>", "data": {"titel": "Hallo"}, "filename": "rapport.pdf"}`
- **THEN** the response status is HTTP 200
- **AND** the `Content-Type` header is `application/pdf`
- **AND** the `Content-Disposition` header references `rapport.pdf`
- **AND** the body is the PDF binary

#### Scenario: Missing template field returns 400

- **GIVEN** an authenticated Nextcloud user
- **WHEN** `POST /apps/docudesk/api/pdf/render` is called with body `{"data": {"titel": "Hallo"}}`
- **THEN** the response status is HTTP 400
- **AND** the response body contains `"template is required"`

#### Scenario: Default filename applied when omitted

- **GIVEN** an authenticated Nextcloud user
- **AND** no `filename` field in the request body
- **WHEN** `POST /apps/docudesk/api/pdf/render` is called with a valid template
- **THEN** the `Content-Disposition` header references `document.pdf`

#### Scenario: Custom page options via API

- **GIVEN** an authenticated Nextcloud user
- **AND** request body includes `"options": {"format": "A3", "orientation": "L"}`
- **WHEN** `POST /apps/docudesk/api/pdf/render` is called
- **THEN** the generated PDF uses A3 landscape format

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-040 | `POST /apps/docudesk/api/pdf/render` is an authenticated endpoint | MUST | Implemented |
| PDF-041 | Accepts JSON body with `template` (required), `data` (optional object), `options` (optional object), `filename` (optional string) | MUST | Implemented |
| PDF-042 | Returns `DataDownloadResponse` with PDF binary and `application/pdf` MIME type | MUST | Implemented |
| PDF-043 | Returns HTTP 400 with `"template is required"` if `template` field is absent or empty | MUST | Implemented |

### REQ-PDF-06: Composer Dependencies (Priority: Must)

The `composer.json` MUST declare minimum version constraints for both mPDF and Twig. The `TemplateRenderer` class MUST be kept separate from `PdfService` to maintain separation of concerns.

#### Scenario: mPDF declared in composer.json

- **GIVEN** DocuDesk's `composer.json`
- **WHEN** the `require` block is inspected
- **THEN** `mpdf/mpdf: ^8.2` is present

#### Scenario: Twig declared in composer.json

- **GIVEN** DocuDesk's `composer.json`
- **WHEN** the `require` block is inspected
- **THEN** `twig/twig: ^3.18` is present

#### Scenario: TemplateRenderer is a separate class

- **GIVEN** `PdfService` needs to render a Twig template
- **WHEN** `PdfService::renderPdf()` executes
- **THEN** it delegates to `TemplateRenderer::renderTemplate()`
- **AND** the mPDF lifecycle in `PdfService` has no direct dependency on Twig internals

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-050 | `mpdf/mpdf: ^8.2` declared in `composer.json` | MUST | Implemented |
| PDF-051 | `twig/twig: ^3.18` declared in `composer.json` | MUST | Implemented |
| PDF-052 | `TemplateRenderer` extracted from `PdfService` for separation of concerns | MUST | Implemented |

### REQ-PDF-07: Twig Sandbox Configuration Details (Priority: Must)

The sandbox configuration MUST be centralised in `TemplateRenderer`. The `SandboxExtension` MUST be instantiated with `sandboxed: true` so the sandbox is always active and cannot be bypassed per-template. The `Environment` MUST use `strict_variables: false`. Template content MUST be loaded via an `ArrayLoader` with the fixed key `"document"`.

#### Scenario: Sandbox is always active

- **GIVEN** `TemplateRenderer` creates a Twig `Environment`
- **WHEN** the `SandboxExtension` is added
- **THEN** it is instantiated with `sandboxed: true`
- **AND** the sandbox cannot be disabled by passing a template-level directive

#### Scenario: strict_variables is disabled

- **GIVEN** the Twig `Environment` is configured
- **WHEN** `strict_variables` is inspected
- **THEN** it is `false`
- **AND** a template referencing an undefined key renders an empty string instead of throwing a `RuntimeError`

#### Scenario: ArrayLoader with key "document"

- **GIVEN** template content is provided as a string
- **WHEN** `TemplateRenderer::renderTemplate()` processes it
- **THEN** an `ArrayLoader` is created with the template content stored under key `"document"`
- **AND** the template is rendered via `$twig->render('document', $data)`

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PDF-060 | `SandboxExtension` instantiated with `sandboxed: true` (always-on) | MUST | Implemented |
| PDF-061 | `strict_variables: false` — undefined template variables render as empty string | MUST | Implemented |
| PDF-062 | `ArrayLoader` used with fixed template key `"document"` | MUST | Implemented |

## API Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/apps/docudesk/api/pdf/render` | Nextcloud session | Generate PDF from Twig template and data context |

## Implementation Files

| File | Role |
|------|------|
| `lib/Service/PdfService.php` | Core rendering service — mPDF lifecycle, options, error handling |
| `lib/Service/TemplateRenderer.php` | Twig sandbox configuration and template rendering |
| `lib/Controller/PdfController.php` | REST endpoint adapter |
| `appinfo/routes.php` | Route registration for `pdf#render` |
| `composer.json` | `mpdf/mpdf ^8.2` and `twig/twig ^3.18` dependency declarations |
