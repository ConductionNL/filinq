---
status: draft
---

# Correspondence Generation

Provides a dedicated correspondence generation workflow for government users to generate letters, beschikkingen, and other correspondence from templates with merge fields populated from case/citizen data. Supports batch generation for multiple recipients, multiple output formats (PDF, DOCX, HTML, email), huisstijl enforcement, and correspondence audit logging. Orchestrates `TemplateService`, `DataResolverService`, `TemplateRenderer`, and `PdfService` into a single `CorrespondenceService` entry point.

## REQ-COR-01: Single Correspondence Generation (Priority: Must)

`CorrespondenceService::generate(string $templateId, array $dataRefs, array $options): array` SHALL orchestrate the full generation flow: resolve the template by UUID, resolve recipient data from OpenRegister object references (up to 3 levels deep), merge data into the template, apply any configured huisstijl, produce the output document in the requested format, and write an audit record to the document register. The method SHALL return an array containing `documentBinary` (string), `filename` (string), `mimeType` (string), `warnings` (array), and `correspondenceId` (UUID of the created audit record).

#### Scenario: Generate a single letter from template and case data

- **GIVEN** a template with UUID `a1b2c3d4-0001-0000-0000-000000000001` exists in the `template` schema
- **AND** a `dataRefs` array contains `{register: "brp", schema: "ingeschreven-persoon", id: "<uuid>"}`
- **WHEN** `CorrespondenceService::generate()` is called with these arguments
- **THEN** `TemplateService::getTemplate()` is called with the template UUID
- **AND** `DataResolverService::resolve()` is called with the `dataRefs` array
- **AND** nested references are resolved up to 3 levels deep (e.g., persoon → adres)
- **AND** `TemplateRenderer::render()` is called with the merged data context
- **AND** `PdfService::renderPdf()` is called with the rendered HTML (format defaults to PDF)
- **AND** an audit record is written to the `correspondence` schema with `status: "generated"`
- **AND** the method returns an array containing `documentBinary`, `filename`, `mimeType`, `warnings`, and `correspondenceId`

#### Scenario: Missing template returns 404 exception

- **GIVEN** `CorrespondenceService::generate()` is called with a non-existent template UUID
- **WHEN** `TemplateService::getTemplate()` throws a not-found exception
- **THEN** a `\Exception` with code 404 and message `"Template not found"` is thrown
- **AND** no `DataResolverService` call is made
- **AND** a `correspondence` audit record with `status: "failed"` and an `errorMessage` is written to the document register

#### Scenario: Missing required merge fields produce warnings, not errors

- **GIVEN** the template references `{{ recipient.naam }}` in its content
- **AND** the resolved data context has no `naam` field
- **WHEN** `TemplateRenderer::render()` is called
- **THEN** the field is rendered as empty in the output document
- **AND** the `generate()` return array includes a `warnings` entry describing the missing field (e.g., `"Missing field: recipient.naam"`)
- **AND** the document is still generated and returned

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-001 | `CorrespondenceService::generate()` orchestrates template lookup, data resolution, rendering, format output, and audit logging | MUST | Planned |
| COR-002 | Nested data resolution delegated to `DataResolverService`, max 3 levels deep | MUST | Planned |
| COR-003 | Non-existent template throws exception with code 404 | MUST | Planned |
| COR-004 | Missing merge fields produce per-field warnings; document still generated | SHOULD | Planned |
| COR-005 | Audit record written to `correspondence` schema on both success and failure | MUST | Planned |

## REQ-COR-02: Correspondence REST Endpoint (Priority: Must)

`POST /api/correspondence/generate` SHALL be an authenticated endpoint (`@NoAdminRequired @NoCSRFRequired`). The JSON request body SHALL accept `templateId` (string, required), `dataRefs` (array of object references, required), `options` (object, optional), and `filename` (string, optional, default `"correspondence.pdf"`). On success the endpoint SHALL return a `DataDownloadResponse` with the generated document binary and appropriate `Content-Type` header. On validation failure it SHALL return a 400 JSON response with a `message` field.

#### Scenario: Successful correspondence generation via API

- **GIVEN** an authenticated Nextcloud user
- **WHEN** `POST /api/correspondence/generate` is called with `{"templateId": "<uuid>", "dataRefs": [{"register": "brp", "schema": "ingeschreven-persoon", "id": "<uuid>"}]}`
- **THEN** the response status is 200
- **AND** the `Content-Type` header is `application/pdf`
- **AND** the response body is the binary PDF content

#### Scenario: Missing templateId returns 400

- **GIVEN** an authenticated Nextcloud user
- **WHEN** `POST /api/correspondence/generate` is called with a request body that has no `templateId` field
- **THEN** the response status is 400
- **AND** the response body is `{"message": "templateId is required"}`

#### Scenario: Missing dataRefs returns 400

- **GIVEN** an authenticated Nextcloud user
- **WHEN** `POST /api/correspondence/generate` is called with `{"templateId": "<uuid>"}` and no `dataRefs`
- **THEN** the response status is 400
- **AND** the response body is `{"message": "dataRefs is required"}`

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-010 | `POST /api/correspondence/generate` endpoint exists and requires authentication | MUST | Planned |
| COR-011 | Endpoint accepts `templateId` (required), `dataRefs` (required), `options` (optional), `filename` (optional) | MUST | Planned |
| COR-012 | Successful response is `DataDownloadResponse` with correct `Content-Type` | MUST | Planned |
| COR-013 | Missing `templateId` returns 400 with descriptive message | MUST | Planned |
| COR-014 | Missing `dataRefs` returns 400 with descriptive message | MUST | Planned |

## REQ-COR-03: Batch Correspondence Generation (Priority: Must)

`CorrespondenceService::generateBatch(string $templateId, array $recipientIds, array $options): array` SHALL generate one letter per recipient. For batches of **10 or fewer** recipients, generation SHALL be synchronous and return an array of per-recipient result objects. For batches **larger than 10** recipients, generation SHALL be dispatched as a Nextcloud background job via `IJobList::add()` and return an array with `jobId` and `status: "queued"`. Each recipient failure SHALL NOT abort the batch; the result SHALL include per-recipient `status` and `error` fields.

#### Scenario: Synchronous batch for small recipient list

- **GIVEN** `CorrespondenceService::generateBatch()` is called with 5 recipient object UUIDs
- **WHEN** the method executes
- **THEN** 5 individual letters are generated synchronously
- **AND** an array of 5 result objects is returned, each containing `recipientId`, `status` (`"generated"` or `"error"`), and either `documentBinary` or `error`
- **AND** 5 `correspondence` audit records are written to the document register

#### Scenario: Asynchronous batch for large recipient list

- **GIVEN** `CorrespondenceService::generateBatch()` is called with 50 recipient object UUIDs
- **WHEN** the method executes
- **THEN** `IJobList::add(BatchCorrespondenceJob::class, ...)` is called once
- **AND** the method returns `{"jobId": "<uuid>", "status": "queued", "totalRecipients": 50}`
- **AND** no letters are generated synchronously within the method call

#### Scenario: Individual failure does not abort batch

- **GIVEN** `CorrespondenceService::generateBatch()` is called with 10 recipient UUIDs
- **AND** the object for recipient 3 does not exist in OpenRegister
- **WHEN** the batch is processed
- **THEN** recipients 1, 2, 4–10 receive successfully generated letters (`status: "generated"`)
- **AND** recipient 3 has `status: "error"` with a descriptive `error` field
- **AND** the overall batch call returns HTTP 200 (synchronous) with the partial results array

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-020 | `generateBatch()` processes ≤ 10 recipients synchronously | MUST | Planned |
| COR-021 | `generateBatch()` dispatches a background job for > 10 recipients | MUST | Planned |
| COR-022 | Per-recipient failure does not abort the batch | MUST | Planned |
| COR-023 | Each result contains `recipientId`, `status`, and either `documentBinary` or `error` | MUST | Planned |
| COR-024 | Background job dispatch returns `jobId` and `status: "queued"` | MUST | Planned |

## REQ-COR-04: Batch REST Endpoints (Priority: Must)

`POST /api/correspondence/generate/batch` SHALL accept the same `templateId`, `dataRefs` array (one entry per recipient), and `options` as the single endpoint. For batches ≤ 10, it SHALL return 200 with a results array. For batches > 10, it SHALL return 202 with `{"jobId": "<uuid>", "status": "queued", "totalRecipients": N}`. `GET /api/correspondence/jobs/{jobId}` SHALL return the job status and available partial results. Both endpoints SHALL require authentication (`@NoAdminRequired @NoCSRFRequired`).

#### Scenario: Batch endpoint returns job ID for large batch

- **GIVEN** an authenticated Nextcloud user
- **WHEN** `POST /api/correspondence/generate/batch` is called with 50 recipient references
- **THEN** the response status is 202
- **AND** the response body is `{"jobId": "<uuid>", "status": "queued", "totalRecipients": 50}`

#### Scenario: Job status endpoint returns in-progress status

- **GIVEN** a batch job was queued with `jobId` `"abc-123"`
- **AND** the background job has processed 25 of 50 recipients
- **WHEN** `GET /api/correspondence/jobs/abc-123` is called
- **THEN** the response status is 200
- **AND** the response body contains `{"jobId": "abc-123", "status": "processing", "completed": 25, "total": 50, "errors": []}`

#### Scenario: Job status endpoint returns completed status

- **GIVEN** the background job for `jobId` `"abc-123"` has finished
- **WHEN** `GET /api/correspondence/jobs/abc-123` is called
- **THEN** the response body contains `{"jobId": "abc-123", "status": "completed", "completed": 50, "total": 50, "errors": [...]}`

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-030 | `POST /api/correspondence/generate/batch` exists and requires authentication | MUST | Planned |
| COR-031 | Batch endpoint returns 202 + jobId for > 10 recipients | MUST | Planned |
| COR-032 | Batch endpoint returns 200 + results array for ≤ 10 recipients | MUST | Planned |
| COR-033 | `GET /api/correspondence/jobs/{jobId}` returns job progress and partial results | MUST | Planned |
| COR-034 | Job status response includes `status`, `completed`, `total`, `errors` | MUST | Planned |

## REQ-COR-05: Output Format Selection (Priority: Must)

`CorrespondenceService::generate()` and the REST endpoint SHALL support output formats selectable via `options.format`: `pdf` (default), `docx`, `html`, and `email`. Each format has distinct output behaviour and MIME type. DOCX conversion requires LibreOffice headless; if unavailable, the endpoint SHALL return 503.

#### Scenario: PDF output (default)

- **GIVEN** `generate()` is called without specifying `options.format` (or with `options.format = "pdf"`)
- **WHEN** the correspondence is generated
- **THEN** `PdfService::renderPdf()` is called with the rendered HTML and huisstijl options
- **AND** the returned binary is a valid PDF document
- **AND** the `Content-Type` response header is `application/pdf`

#### Scenario: DOCX output

- **GIVEN** `generate()` is called with `options.format = "docx"`
- **AND** LibreOffice headless (`soffice`) is available on the server
- **WHEN** the correspondence is generated
- **THEN** `TemplateRenderer::render()` produces HTML
- **AND** `LibreOfficeConverter::convert()` converts the HTML to DOCX using `soffice --headless --convert-to docx`
- **AND** the returned binary is a valid DOCX file
- **AND** the `Content-Type` response header is `application/vnd.openxmlformats-officedocument.wordprocessingml.document`

#### Scenario: DOCX output when LibreOffice unavailable

- **GIVEN** `generate()` is called with `options.format = "docx"`
- **AND** `soffice` is not available on the server
- **WHEN** `LibreOfficeConverter::convert()` is called
- **THEN** it throws a `RuntimeException`
- **AND** the endpoint returns HTTP 503 with `{"message": "DOCX conversion service unavailable"}`

#### Scenario: HTML preview output

- **GIVEN** `generate()` is called with `options.format = "html"`
- **WHEN** the correspondence is generated
- **THEN** the rendered HTML string is returned directly without PDF or DOCX conversion
- **AND** the `Content-Type` response header is `text/html`

#### Scenario: Email body output

- **GIVEN** `generate()` is called with `options.format = "email"`
- **WHEN** the correspondence is generated
- **THEN** the rendered HTML is post-processed to strip `@page` CSS rules and convert block styles to inline attributes
- **AND** the result is clean HTML suitable for email body embedding
- **AND** the `Content-Type` response header is `text/html`

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-040 | PDF output via `PdfService::renderPdf()` is the default format | MUST | Planned |
| COR-041 | DOCX output via LibreOffice headless conversion | MUST | Planned |
| COR-042 | DOCX unavailability returns HTTP 503 with descriptive message | MUST | Planned |
| COR-043 | HTML output returns raw rendered HTML without conversion | SHOULD | Planned |
| COR-044 | Email output returns HTML with `@page` rules stripped and inline styles | MUST | Planned |

## REQ-COR-06: Huisstijl Default Configuration (Priority: Must)

When generating correspondence, `CorrespondenceService` SHALL look up a `huisstijl` configuration object from the document register and apply its `logo`, `primaryColor`, `headerHtml`, `footerHtml`, and `defaultMargins` to the output. If a `huisstijlId` is provided in `options`, that specific object is used; otherwise the first available `huisstijl` object is used as the organisational default. If no `huisstijl` object exists, default PdfService margins (15 mm all sides) are applied with no header or footer.

#### Scenario: Huisstijl applied to letter

- **GIVEN** a `huisstijl` object exists in the document register with `primaryColor: "#154273"`, `headerHtml`, `footerHtml`, and `defaultMargins: {top:25, right:20, bottom:20, left:20}`
- **AND** no `huisstijlId` is specified in `options`
- **WHEN** a correspondence is generated
- **THEN** `ObjectService::searchObjects({schema: "huisstijl", register: "document"})` returns the huisstijl object
- **AND** `PdfService::renderPdf()` is called with `margins: {top:25, right:20, bottom:20, left:20}`, `header: "<headerHtml rendered HTML>"`, and `footer: "<footerHtml rendered HTML>"`

#### Scenario: Specific huisstijl by ID

- **GIVEN** two `huisstijl` objects exist
- **AND** `options.huisstijlId` is set to the UUID of the second one
- **WHEN** a correspondence is generated
- **THEN** `ObjectService::find()` is called with the specified UUID
- **AND** the second huisstijl's settings are applied

#### Scenario: No huisstijl configured

- **GIVEN** no `huisstijl` object exists in the document register
- **WHEN** a correspondence is generated
- **THEN** `PdfService::renderPdf()` is called with default margins (15 mm all sides)
- **AND** no header or footer is applied to the output

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-050 | Huisstijl configuration resolved from document register at generation time | MUST | Planned |
| COR-051 | `options.huisstijlId` selects a specific huisstijl; without it, the first available is used | MUST | Planned |
| COR-052 | Absent huisstijl falls back to PdfService default margins, no header/footer | MUST | Planned |
| COR-053 | `headerHtml` and `footerHtml` are rendered through `TemplateRenderer` before passing to PdfService | SHOULD | Planned |

## REQ-COR-07: Correspondence Register Logging (Priority: Must)

Every correspondence generation attempt (success or failure) SHALL create an object in the document register (register: `document`, schema: `correspondence`) containing all fields defined in the `correspondence` schema: `templateId`, `templateName`, `recipientId`, `recipientType`, `caseReference` (optional), `generatedAt`, `format`, `status` (`"generated"` or `"failed"`), `generatedBy`, and `errorMessage` (on failure only).

#### Scenario: Successful generation creates register entry

- **GIVEN** a letter is successfully generated for recipient UUID `f0e1d2c3-...`
- **WHEN** `CorrespondenceService::generate()` completes
- **THEN** `ObjectService::saveObject()` is called to create a `correspondence` object
- **AND** the object contains `templateId`, `templateName`, `recipientId`, `generatedAt` (ISO 8601), `format`, `status: "generated"`, `generatedBy` (current Nextcloud user ID)

#### Scenario: Failed generation creates register entry with error

- **GIVEN** a letter generation fails due to a missing object reference
- **WHEN** `CorrespondenceService::generate()` catches the exception
- **THEN** a `correspondence` object is still created in the document register
- **AND** `status` is `"failed"`
- **AND** `errorMessage` contains the failure description

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-060 | Every generation attempt (success or failure) writes a `correspondence` object | MUST | Planned |
| COR-061 | `status: "generated"` on success; `status: "failed"` + `errorMessage` on failure | MUST | Planned |
| COR-062 | `generatedBy` is populated from the current Nextcloud user session | MUST | Planned |
| COR-063 | `generatedAt` is a valid ISO 8601 datetime in UTC | MUST | Planned |

## REQ-COR-08: Email Body Generation (Priority: Must)

When `options.format = "email"`, `CorrespondenceService` SHALL return rendered HTML suitable for embedding in an email body. The HTML SHALL have `@page` CSS rules stripped, block-level styles converted to inline `style` attributes, and no PDF page-dimension wrappers. Email templates use namespace `email` in the template management system and are filterable via `GET /api/templates?namespace=email`.

#### Scenario: Email body generation returns inline-styled HTML

- **GIVEN** `generate()` is called with `options.format = "email"` and a template with `namespace: "email"`
- **WHEN** the correspondence is generated
- **THEN** `TemplateRenderer::render()` produces the base HTML
- **AND** a post-processing step strips all `@page` CSS rules
- **AND** block-level styles are inlined (e.g., `<p style="...">`)
- **AND** the result contains no `<html>`, `<head>`, `<body>` tags (body content fragment only)

#### Scenario: Email templates listed by namespace

- **GIVEN** templates exist with `namespace: "email"` and `namespace: "letter"`
- **WHEN** `GET /api/templates?namespace=email` is called
- **THEN** only templates with `namespace: "email"` are returned
- **AND** templates with other namespaces are excluded

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| COR-070 | `format = "email"` returns HTML with `@page` rules stripped and inline styles | MUST | Planned |
| COR-071 | Email output is a body content fragment (no `<html>`/`<head>`/`<body>` wrapper) | MUST | Planned |
| COR-072 | Email templates use namespace `email`; filterable via `GET /api/templates?namespace=email` | MUST | Planned |

## Dependencies

- **template-management** spec: `TemplateService::getTemplate()`, namespace filtering
- **pdf-generation** spec: `PdfService::renderPdf()`, `TemplateRenderer::render()`
- **document-creatie-sjablonen** spec: `DataResolverService::resolve()` for OpenRegister data resolution
- **OpenRegister ObjectService**: `huisstijl` and `correspondence` object access
- **LibreOffice headless**: Server-side DOCX conversion (optional, graceful degradation)
