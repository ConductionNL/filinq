---
status: done
---

# Letter/Correspondence Generation

## Purpose

@e2e exclude backend service not yet surfaced in the Filinq UI — correspondence generation is API/DI-only; covered by PHPUnit service tests

Provides a dedicated correspondence generation workflow for government users to generate letters, beschikkingen, and other correspondence from templates with merge fields populated from case/citizen data. Supports batch generation for multiple recipients, multiple output formats (PDF, DOCX, HTML, email), huisstijl enforcement, and correspondence audit logging. Builds on the existing template-management and pdf-generation capabilities.

## Requirements

### Requirement: Correspondence generation API

The system SHALL provide a dedicated `CorrespondenceService` at `OCA\Filinq\Service\CorrespondenceService` that orchestrates the end-to-end correspondence generation workflow: resolve recipient data from OpenRegister objects, merge into a template, apply huisstijl defaults (margins, header, footer, logo), and produce the output document. The service SHALL delegate template lookup to `TemplateService`, data resolution to `DataResolverService`, template rendering to `TemplateRenderer`, and PDF output to `PdfService`.

#### Scenario: Generate a single letter from template and case data
- **WHEN** `CorrespondenceService::generate(string $templateId, array $dataRefs, array $options)` is called with a valid template UUID and an object reference `{register: "brp", schema: "ingeschreven-persoon", id: "<uuid>"}`
- **THEN** the template is fetched via `TemplateService::getTemplate()`
- **AND** the object data is resolved from OpenRegister via `ObjectService::find()`
- **AND** nested references are resolved up to 3 levels deep (e.g., persoon -> adres)
- **AND** the template content is rendered with the merged data context
- **AND** a PDF binary is returned with huisstijl defaults applied

#### Scenario: Missing template returns 404
- **WHEN** `CorrespondenceService::generate()` is called with a non-existent template UUID
- **THEN** a `\Exception` with code 404 and message "Template not found" is thrown

#### Scenario: Missing required merge fields produce warnings
- **WHEN** the template references `{{ recipient.naam }}` but the resolved data has no `naam` field
- **THEN** the response includes a `warnings` array with an entry describing the missing field
- **AND** the document is still generated with the field rendered as empty

### Requirement: Correspondence REST endpoint

The system SHALL expose `POST /api/correspondence/generate` as an authenticated endpoint (`@NoAdminRequired @NoCSRFRequired`). The endpoint SHALL accept a JSON body with `templateId` (string, required), `dataRefs` (array of object references, required), `options` (object, optional), and `filename` (string, optional, default "correspondence.pdf"). The endpoint SHALL return a `DataDownloadResponse` with the generated document binary.

#### Scenario: Successful correspondence generation via API
- **WHEN** `POST /api/correspondence/generate` is called with `{"templateId": "<uuid>", "dataRefs": [{"register": "brp", "schema": "ingeschreven-persoon", "id": "<uuid>"}]}`
- **THEN** the response is a PDF file download with status 200
- **AND** the `Content-Type` header is `application/pdf`

#### Scenario: Missing templateId returns 400
- **WHEN** `POST /api/correspondence/generate` is called without a `templateId` field
- **THEN** a 400 JSON response is returned with message "templateId is required"

### Requirement: Batch correspondence generation

The system SHALL provide `CorrespondenceService::generateBatch(string $templateId, array $recipientIds, array $options): array` that generates one letter per recipient. For batches of 10 or fewer, generation SHALL be synchronous and return an array of results. For batches larger than 10, generation SHALL be dispatched as a Nextcloud background job and return a job ID. Each recipient failure SHALL NOT abort the batch; partial results with per-recipient error details SHALL be returned.

#### Scenario: Synchronous batch for small recipient list
- **WHEN** `generateBatch()` is called with 5 recipient object UUIDs
- **THEN** 5 individual letters are generated synchronously
- **AND** an array of 5 result objects is returned, each containing `recipientId`, `status`, and `documentBinary` or `error`

#### Scenario: Asynchronous batch for large recipient list
- **WHEN** `generateBatch()` is called with 50 recipient object UUIDs
- **THEN** a Nextcloud background job is created via `IJobList::add()`
- **AND** a JSON response with `jobId` and `status: "queued"` is returned
- **AND** `GET /api/correspondence/jobs/{jobId}` returns progress and partial results

#### Scenario: Individual failure does not abort batch
- **WHEN** a batch of 10 recipients is processed and recipient 3 has missing data
- **THEN** recipients 1, 2, 4-10 receive successfully generated letters
- **AND** recipient 3 has `status: "error"` with a descriptive error message

### Requirement: Batch correspondence REST endpoints

The system SHALL expose `POST /api/correspondence/generate/batch` for batch generation and `GET /api/correspondence/jobs/{jobId}` for job status queries. Both endpoints SHALL require authentication (`@NoAdminRequired @NoCSRFRequired`).

#### Scenario: Batch endpoint returns job ID for large batch
- **WHEN** `POST /api/correspondence/generate/batch` is called with 50 recipient IDs
- **THEN** a 202 JSON response is returned with `{"jobId": "<uuid>", "status": "queued", "totalRecipients": 50}`

#### Scenario: Job status endpoint returns progress
- **WHEN** `GET /api/correspondence/jobs/{jobId}` is called for an in-progress job
- **THEN** a 200 JSON response is returned with `{"jobId": "<uuid>", "status": "processing", "completed": 25, "total": 50, "errors": []}`

### Requirement: Output format selection

The system SHALL support multiple output formats for correspondence: PDF (default), DOCX (via LibreOffice server-side conversion), HTML (for preview), and email (clean HTML). The format SHALL be selectable per request via the `options.format` field accepting values `pdf`, `docx`, `html`, or `email`.

#### Scenario: PDF output (default)
- **WHEN** `generate()` is called without specifying a format
- **THEN** a PDF document is produced via `PdfService::renderPdf()`

#### Scenario: DOCX output
- **WHEN** `generate()` is called with `options.format = "docx"`
- **THEN** the HTML is first rendered via Twig, then converted to DOCX using LibreOffice headless (`soffice --headless --convert-to docx`)
- **AND** if LibreOffice is not available, a 503 error is returned with message "DOCX conversion service unavailable"

#### Scenario: HTML preview output
- **WHEN** `generate()` is called with `options.format = "html"`
- **THEN** the rendered HTML string is returned directly without PDF conversion

### Requirement: Huisstijl default configuration

The system SHALL support a huisstijl configuration object stored in OpenRegister (schema: `huisstijl`, register: `filinq`) containing `logo` (base64 or file reference), `primaryColor` (CSS color), `headerHtml` (Twig template for page header), `footerHtml` (Twig template for page footer), and `defaultMargins` (object with top/right/bottom/left in mm). When generating correspondence, if the template references a huisstijl ID or a default huisstijl is configured, these settings SHALL be applied automatically to the output.

#### Scenario: Huisstijl applied to letter
- **WHEN** a correspondence is generated and a huisstijl configuration exists
- **THEN** the organization logo is included in the header
- **AND** the configured header/footer HTML is rendered on every page
- **AND** the default margins from the huisstijl configuration are used (unless overridden by the request)

#### Scenario: No huisstijl configured
- **WHEN** no huisstijl configuration exists in OpenRegister
- **THEN** the letter is generated with default PdfService margins (15mm all sides)
- **AND** no header/footer is applied

### Requirement: Correspondence register logging

The system SHALL log every generated correspondence as an object in the `filinq` register (schema: `correspondence`, register: `filinq`). Each entry SHALL contain: `templateId` (UUID of template used), `templateName` (human-readable), `recipientId` (UUID of recipient object), `recipientType` (e.g., PERSON, ORGANIZATION), `caseReference` (optional UUID linking to source zaak/case), `generatedAt` (ISO 8601 datetime), `format` (pdf/docx/html), `status` (generated/failed), and `generatedBy` (Nextcloud user ID). The schema SHALL be added to `filinq_register.json`.

#### Scenario: Successful generation creates register entry
- **WHEN** a letter is successfully generated
- **THEN** a correspondence object is created in the `filinq` register
- **AND** the object contains all required metadata fields
- **AND** `status` is set to "generated"

#### Scenario: Failed generation creates register entry with error
- **WHEN** a letter generation fails
- **THEN** a correspondence object is still created in the `filinq` register
- **AND** `status` is set to "failed"
- **AND** an `errorMessage` field contains the failure reason

### Requirement: Email body generation

The system SHALL support generating email body content from templates using the same merge logic as letter generation. When `options.format = "email"`, the system SHALL return rendered HTML suitable for email body inclusion (no PDF wrapper, no page-specific styling). Email templates SHALL use the namespace `email` in the template management system.

#### Scenario: Email body generation
- **WHEN** `generate()` is called with `options.format = "email"` and a template with namespace `email`
- **THEN** clean HTML is returned without page-specific CSS (no @page rules, no fixed dimensions)
- **AND** the HTML uses inline styles suitable for email clients

#### Scenario: Email templates listed by namespace
- **WHEN** `GET /api/templates?namespace=email` is called
- **THEN** only templates with namespace `email` are returned

### Requirement: A composed letter can be sent by email (REQ-LCS-01)

Filinq SHALL offer `POST /api/correspondence/send` with a template id, a recipient id and an optional case reference. It SHALL compose the letter with the email body generation of this spec and send it through Nextcloud's mailer to the email address on the recipient object. The subject SHALL be the template's subject line, or its name when it has none. When the caller asks for it, the PDF of the same letter SHALL be attached. The caller SHALL be able to read the recipient object, or the send is refused with 404. The send SHALL be logged as a correspondence object with `status` `sent`, `sentAt`, `sentTo` and `sentBy`.

#### Scenario: An officer sends a letter to a citizen

- **GIVEN** an approved template "Uitnodiging gesprek" and a recipient with email address `j.jansen@example.nl`
- **WHEN** the officer sends the letter by email
- **THEN** one message goes to `j.jansen@example.nl` with the composed body
- **AND** the correspondence object has status `sent` with the address, the time and the officer

#### Scenario: The PDF goes along

- **GIVEN** the same template and recipient
- **WHEN** the officer sends the letter with the PDF attached
- **THEN** the message carries the PDF of the same letter

### Requirement: A send that cannot happen says why and records nothing as sent (REQ-LCS-02)

Filinq SHALL refuse the send with 422 and a message naming the cause when the recipient has no email address or the instance has no working mail server. A mailer error during the send SHALL record the correspondence object with `status` `failed` and the error, and the answer SHALL say the letter was not sent. The answer SHALL NOT contain server internals.

#### Scenario: The recipient has no email address

- **GIVEN** a recipient without an email address
- **WHEN** the officer sends a letter by email
- **THEN** the answer is 422 with "Deze ontvanger heeft geen e-mailadres" and no message is sent

#### Scenario: The mail server fails

- **GIVEN** a mail server that refuses the message
- **WHEN** the officer sends a letter by email
- **THEN** the correspondence object has status `failed` with the reason and the page says the letter was not sent

### Requirement: filinq keeps a house style per thematiq token set (REQ-HSP-001)

When thematiq is installed, filinq MUST keep one `huisstijl` per thematiq
token set, marked as managed by thematiq, holding the profile's logo, primary
colour, fonts, cover image and footer lines, and MUST refresh it when the
profile changes and only then. Without thematiq filinq MUST behave as before.

#### Scenario: the organisation changes its logo in Theming
- GIVEN a managed house style for the default token set
- WHEN the administrator uploads a new document logo in Theming and a letter is generated
- THEN the managed house style carries the new logo and the letter shows it
- @e2e exclude cross-app sync; covered by PHPUnit with a stub profile and a local run with thematiq

### Requirement: A document without a named house style uses the user's own (REQ-HSP-002)

When a generation request names no `huisstijlId`, filinq MUST apply the
managed house style of the requesting user's token set. A named
`huisstijlId` MUST take precedence.

#### Scenario: two municipalities, two letterheads
- GIVEN the BUCH municipalities with one token set per municipality in thematiq
- WHEN a clerk of Bergen and a clerk of Uitgeest each generate the same letter without naming a house style
- THEN each letter carries its own municipality's logo, colours and footer
- @e2e exclude generation across two apps; covered by PHPUnit and a local run noted in the PR

### Requirement: A managed house style is changed only in thematiq (REQ-HSP-003)

filinq MUST refuse an update of a house style managed by thematiq, with a
message that names Theming as the place to change it. A hand-made house
style MUST stay editable.

#### Scenario: an editor tries to change the managed footer
- GIVEN a template editor and a house style managed by thematiq
- WHEN the editor saves a new footer through filinq
- THEN the save is refused with "This house style follows thematiq; change it in Theming"
- @e2e exclude API refusal; covered by Newman

## Dependencies

- **template-management** spec: Template CRUD and namespace scoping
- **pdf-generation** spec: PDF rendering via mPDF + Twig sandbox
- **document-creatie-sjablonen** spec: Data resolution layer (DataResolverService)
- **OpenRegister ObjectService**: Data resolution from register objects
- **LibreOffice headless**: Server-side DOCX conversion (optional, graceful degradation)
