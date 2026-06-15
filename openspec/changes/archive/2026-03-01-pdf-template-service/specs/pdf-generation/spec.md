## Requirements

### Requirement: PDF rendering from Twig template and data context
The system SHALL provide a `PdfService` that accepts a Twig template string and a data context array, renders the template with the data, converts the resulting HTML to PDF via mPDF, and returns the PDF binary content as a string.

The service SHALL be stateless — it does not look up templates or manage storage. Callers provide the template content directly.

The service SHALL be injectable via Nextcloud's DI container at `OCA\DocuDesk\Service\PdfService::class`, enabling any co-installed Nextcloud app to use it without HTTP overhead.

#### Scenario: Render PDF from template and data
- **WHEN** a caller invokes `PdfService::renderPdf(string $templateContent, array $data, array $options)` with valid Twig template content and a data array
- **THEN** the service renders the Twig template with the data context, feeds the HTML to mPDF, and returns the PDF binary as a string

#### Scenario: Render PDF with empty data context
- **WHEN** a caller invokes `renderPdf()` with a template containing no Twig variables and an empty data array
- **THEN** the service renders the static HTML to PDF and returns the binary content

#### Scenario: Twig syntax error in template
- **WHEN** a caller invokes `renderPdf()` with a template containing invalid Twig syntax (e.g., unclosed `{{ }}`)
- **THEN** the service SHALL throw an `\Exception` with a descriptive message about the Twig parse error

### Requirement: PDF page configuration options
The system SHALL accept an optional `$options` array in `renderPdf()` supporting the following keys:
- `format`: Page size string (default: `'A4'`). Accepts mPDF format strings (A4, A3, Letter, Legal).
- `orientation`: `'P'` for portrait (default) or `'L'` for landscape.
- `margin`: Array with keys `top`, `right`, `bottom`, `left` in millimeters (default: 15mm all sides).
- `title`: PDF document title metadata string (default: empty).

#### Scenario: Generate landscape A3 PDF
- **WHEN** a caller invokes `renderPdf()` with `['format' => 'A3', 'orientation' => 'L']`
- **THEN** the resulting PDF has A3 landscape page dimensions

#### Scenario: Default options produce A4 portrait
- **WHEN** a caller invokes `renderPdf()` with an empty options array
- **THEN** the resulting PDF uses A4 portrait format with 15mm margins

### Requirement: Twig sandbox security policy
The system SHALL configure Twig with `SandboxExtension` using a strict security policy. The policy SHALL:
- Allow safe filters: `escape`, `e`, `upper`, `lower`, `trim`, `nl2br`, `date`, `number_format`, `join`, `split`, `first`, `last`, `length`, `default`, `raw`, `sort`, `reverse`, `keys`, `values`, `merge`, `slice`, `batch`, `column`, `round`, `abs`
- Allow safe functions: `range`, `cycle`, `date`, `max`, `min`
- Allow safe tags: `if`, `for`, `set`, `block`, `extends`, `include`, `macro`, `spaceless`
- Block all methods and properties on objects (data is passed as arrays)
- Disallow `system`, `exec`, `passthru`, `shell_exec`, and file operations

#### Scenario: Template attempts forbidden function call
- **WHEN** a template contains `{{ system('ls') }}` or `{{ '/etc/passwd'|file_excerpt }}`
- **THEN** the Twig sandbox SHALL throw a `SecurityError` and PDF generation SHALL fail with an exception

#### Scenario: Template uses allowed filter
- **WHEN** a template contains `{{ name|upper }}` with data `{'name': 'test'}`
- **THEN** the template renders `TEST` successfully

### Requirement: mPDF temporary directory management
The system SHALL create the mPDF temp directory at `/tmp/mpdf` with 0777 permissions if it does not exist. The system SHALL configure mPDF to use this directory via the `tempDir` config option.

#### Scenario: First PDF generation on fresh server
- **WHEN** `renderPdf()` is called and `/tmp/mpdf` does not exist
- **THEN** the directory is created with 0777 permissions before mPDF initialization

### Requirement: PDF render API endpoint
The system SHALL expose `POST /api/pdf/render` as an authenticated endpoint (`@NoCSRFRequired`) that accepts a JSON body with:
- `template` (string, required): Twig template content
- `data` (object, optional): Data context for template rendering
- `options` (object, optional): PDF configuration options
- `filename` (string, optional): Suggested filename for the download (default: `document.pdf`)

The endpoint SHALL return a `DataDownloadResponse` with the PDF binary, the filename, and MIME type `application/pdf`.

#### Scenario: API render with template and data
- **WHEN** an authenticated user POSTs to `/api/pdf/render` with `{"template": "<h1>{{ title }}</h1>", "data": {"title": "Hello"}, "filename": "report.pdf"}`
- **THEN** the response is a PDF download with filename `report.pdf` containing rendered HTML `<h1>Hello</h1>`

#### Scenario: API render without template
- **WHEN** an authenticated user POSTs to `/api/pdf/render` without a `template` field
- **THEN** the response is a 400 JSONResponse with `{"error": "Template content is required"}`

#### Scenario: Unauthenticated request
- **WHEN** an unauthenticated user POSTs to `/api/pdf/render`
- **THEN** the response is a 401 error (Nextcloud handles this via middleware)

### Requirement: Composer dependencies
The system SHALL add `mpdf/mpdf: ^8.2` and `twig/twig: ^3.18` to DocuDesk's `composer.json` require section.

#### Scenario: Dependencies installed
- **WHEN** `composer install` is run in the DocuDesk directory
- **THEN** mPDF and Twig are available in the autoloader and can be instantiated
