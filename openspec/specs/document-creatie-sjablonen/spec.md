---
status: in-progress
---

# document-creatie-sjablonen Specification

**Status**: in-progress
**OpenSpec changes**:
- [guided-document-wizard](../../changes/guided-document-wizard/) _(active)_ — wizard-driven generations validate `options.wizardContext` server-side and record the interview context (wizard id + version + answers) on the `generatedDocument` audit object (REQ-DDGDW-008) (kind: code)
- [multi-format-output](../../changes/multi-format-output/) _(active)_ — `options.formats` produces multiple outputs from a single render pass with a JSON manifest; `docx` becomes a first-class editable generation format; every produced output is recorded on `generatedDocument.outputs` (REQ-DDMFO-001/003/006) (kind: code)

## Purpose
Generates documents from templates by merging resolved data into a sandboxed Twig template. Merge data is resolved from OpenRegister objects by register, schema, and object UUID with nested resolution up to three levels deep, optional external data via OpenConnector, and ad-hoc JSON context, while rendering supports conditional sections, iteration, and per-field warnings for missing values. This enables automated creation of formal documents such as beschikkingen from structured case data.
## Requirements
### Requirement: REQ-DCS-01 Data Resolution from OpenRegister (Priority: Must)

The system MUST resolve merge data from OpenRegister objects by register, schema, and object UUID, with support for nested resolution and ad-hoc context data.

#### Scenario: Resolve data from a single zaak object
- GIVEN a zaak object exists in OpenRegister with UUID "abc-123"
- AND the object contains fields: aanvrager, status, datum
- WHEN DocumentService resolves data for register "zaken", schema "zaak", object "abc-123"
- THEN all object fields are available as template variables

#### Scenario: Nested data resolution
- GIVEN a zaak object references a persoon (aanvrager), which references an adres
- WHEN data resolution runs with recursive resolution enabled
- THEN the persoon is resolved from its register/schema
- AND the adres is resolved from the persoon reference
- AND resolution stops at 3 levels deep (zaak -> persoon -> adres)

#### Scenario: Data resolution failure
- GIVEN a data reference points to a non-existent object
- WHEN data resolution is attempted
- THEN a descriptive error is returned per field (not a generic 500)
- AND other fields that resolved successfully are still available

#### Scenario: Ad-hoc data context
- GIVEN a template needs both OpenRegister data and user-supplied context
- WHEN the API is called with both object references and a JSON data object
- THEN the ad-hoc data is merged with the resolved OpenRegister data
- AND ad-hoc values take precedence over resolved values

#### Scenario: External data via OpenConnector
- GIVEN a template needs BRP citizen data
- WHEN the data reference specifies an OpenConnector source (e.g., BRP API)
- THEN data is resolved via OpenConnector
- AND the result is available as template variables

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-001 | Resolve merge data from OpenRegister objects by register + schema + object UUID | MUST | Planned |
| DCS-002 | Resolve merge data from external API sources via OpenConnector | SHOULD | Planned |
| DCS-003 | Support nested data resolution up to 3 levels deep | MUST | Planned |
| DCS-004 | Data resolution failures return descriptive errors per field | MUST | Planned |
| DCS-005 | Accept ad-hoc JSON data context alongside or instead of object references | MUST | Planned |

### Requirement: REQ-DCS-02 Template Merge Execution (Priority: Must)

The system MUST render templates by merging resolved data context using the existing Twig sandbox, with support for conditional sections and iteration.

#### Scenario: Generate a beschikking from a zaak
- GIVEN a template "Beschikking Omgevingsvergunning" exists with namespace "procest"
- AND a zaak object is resolved with aanvrager, activiteiten, and besluit data
- WHEN DocumentService::generateDocument() is called
- THEN the Twig template is rendered with the merged data
- AND conditional sections show/hide based on zaaktype
- AND document metadata is stored in the document register

#### Scenario: Conditional sections in template
- GIVEN a template has `{% if zaaktype == 'omgevingsvergunning' %}` blocks
- WHEN the zaak has type "omgevingsvergunning"
- THEN the conditional block is rendered
- AND other conditional blocks for different types are hidden

#### Scenario: Iteration over collections
- GIVEN a template has `{% for activiteit in activiteiten %}` loops
- WHEN the zaak has 3 activiteiten
- THEN the loop renders 3 times with each activiteit's data

#### Scenario: Missing required fields produce warnings
- GIVEN a template references `{{ aanvrager.naam }}` but aanvrager.naam is null
- WHEN the template is rendered
- THEN the field is rendered as empty (Twig strict_variables is false)
- AND a warning is included in the response indicating the missing field

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-010 | `DocumentService::generateDocument()` resolves data, renders template, returns document metadata + binary | MUST | Planned |
| DCS-011 | Merge uses the existing Twig sandbox from pdf-generation (same security policy) | MUST | Planned |
| DCS-012 | Support conditional sections in templates | MUST | Planned |
| DCS-013 | Support iteration over collections | MUST | Planned |
| DCS-014 | Missing required fields produce warnings, not silent empty values | SHOULD | Planned |

### Requirement: REQ-DCS-03 Output Format Support (Priority: Must)

The system MUST support PDF, ODF, and HTML output formats, selectable per request.

#### Scenario: PDF output (default)
- GIVEN a template is rendered with data
- WHEN the format is "pdf" (or not specified)
- THEN the output is generated via PdfService using mPDF
- AND the PDF binary is returned

#### Scenario: ODF output
- GIVEN a template is rendered with data
- WHEN the format is "odf"
- THEN an ODF (.odt) file is produced via server-side conversion
- AND the file conforms to ODF 1.2 specification (ISO/IEC 26300:2015)

#### Scenario: HTML preview
- GIVEN a template needs to be previewed before final generation
- WHEN the format is "html"
- THEN the rendered HTML is returned without PDF/ODF conversion
- AND the preview can be displayed in the browser

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-020 | PDF output via existing PdfService (default) | MUST | Planned |
| DCS-021 | ODF output (.odt) via server-side conversion | MUST | Planned |
| DCS-022 | HTML output for browser preview | SHOULD | Planned |
| DCS-023 | Output format selectable per request via `format` option | MUST | Planned |

### Requirement: REQ-DCS-04 Huisstijl Enforcement (Priority: Must)

Templates MUST be able to reference a corporate identity (huisstijl) configuration for consistent branding.

#### Scenario: Automatic huisstijl application
- GIVEN a template references a huisstijl configuration stored in OpenRegister
- WHEN the document is rendered
- THEN the logo, colors, fonts, and header/footer are applied automatically
- AND the template author does not need to hardcode brand elements

#### Scenario: NL Design System token integration
- GIVEN a template uses CSS variables for styling
- WHEN NL Design System tokens are available
- THEN the tokens can be used as CSS variables in the template
- AND the output matches the municipality's design system

#### Scenario: No huisstijl configured
- GIVEN a template does not reference a huisstijl configuration
- WHEN the document is rendered
- THEN default styling is applied
- AND the document is still valid and readable

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-030 | Templates can reference a huisstijl configuration stored in OpenRegister | MUST | Planned |
| DCS-031 | Huisstijl applied automatically during rendering | SHOULD | Planned |
| DCS-032 | NL Design System tokens can be used as CSS variables | SHOULD | Planned |

### Requirement: REQ-DCS-05 Bulk Document Generation (Priority: Must)

The system MUST generate documents for multiple objects in a single request, with async processing for large batches and partial failure handling.

#### Scenario: Bulk generate citizen letters
- GIVEN a template "Kennisgeving Bestemmingsplan" exists
- AND 150 persoon objects are selected
- WHEN POST /api/documents/generate/bulk is called
- THEN a job ID is returned (>10 objects = async)
- AND each letter is generated with the individual citizen's data
- AND GET /api/documents/jobs/{jobId} returns progress

#### Scenario: Partial failure in bulk generation
- GIVEN 50 objects are being processed in bulk
- AND 3 objects have missing required data
- WHEN bulk generation runs
- THEN 47 documents are generated successfully
- AND 3 failures are returned with per-item error details
- AND the batch is not aborted

#### Scenario: Merged PDF output
- GIVEN a bulk generation request with mergedOutput option
- WHEN all documents are generated
- THEN all documents are concatenated into a single PDF with page breaks
- AND the merged PDF is returned as a single download

#### Scenario: Small batch synchronous processing
- GIVEN 5 objects are submitted for bulk generation
- WHEN the batch size is <= 10
- THEN processing is synchronous
- AND all results are returned in the response

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-040 | `DocumentService::generateBulk()` generates one document per object | MUST | Planned |
| DCS-041 | Async processing for >10 objects with job ID and status query | SHOULD | Planned |
| DCS-042 | Merged output: all documents concatenated into single PDF | SHOULD | Planned |
| DCS-043 | Partial failures do not abort the batch | MUST | Planned |

### Requirement: REQ-DCS-06 Template Versioning (Priority: Must)

Templates MUST support versioning so that generated documents can reference the exact template version used.

#### Scenario: Template version on update
- GIVEN template "Vergunningbrief" version 1 exists
- WHEN the template is updated with new content
- THEN a new version 2 is created
- AND version 1 is retained and retrievable

#### Scenario: Document references template version
- GIVEN a document is generated from template version 3
- WHEN the document metadata is stored
- THEN it includes the template UUID and version number 3

#### Scenario: Re-generate with previous version
- GIVEN a document was originally generated from version 3
- AND the template is now at version 4
- WHEN the user requests re-generation
- THEN they can choose version 3 (original) or version 4 (current)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-050 | Each template update creates a new version; previous versions retained | MUST | Planned |
| DCS-051 | Generated documents reference template UUID + version number | MUST | Planned |
| DCS-052 | Previous versions retrievable for re-generation | SHOULD | Planned |

### Requirement: REQ-DCS-07 Document Generation API (Priority: Must)

The system MUST expose REST API endpoints for single and bulk document generation, preview, and job status.

#### Scenario: Single document generation
- GIVEN an authenticated user
- WHEN POST /api/documents/generate is called with templateId and data references
- THEN the document is generated and returned with metadata

#### Scenario: Bulk document generation
- GIVEN an authenticated user with 20 object IDs
- WHEN POST /api/documents/generate/bulk is called
- THEN a job ID is returned for async processing
- AND the job can be queried via GET /api/documents/jobs/{jobId}

#### Scenario: HTML preview
- GIVEN an authenticated user
- WHEN POST /api/documents/generate/preview is called
- THEN the rendered HTML is returned without producing final output
- AND the preview can be displayed inline

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-060 | `POST /api/documents/generate` for single document generation | MUST | Planned |
| DCS-061 | `POST /api/documents/generate/bulk` for bulk generation | MUST | Planned |
| DCS-062 | `POST /api/documents/generate/preview` for HTML preview | SHOULD | Planned |
| DCS-063 | `GET /api/documents/jobs/{jobId}` for async job status | SHOULD | Planned |
| DCS-064 | All endpoints require authentication | MUST | Planned |

### Requirement: REQ-DCS-08 Zaaksysteem Integration (Priority: Should)

Generated documents MUST be linkable to cases in Procest and triggerable from workflows.

#### Scenario: Attach document to case
- GIVEN a document is generated from a zaak's data
- WHEN the generation completes
- THEN the document can be automatically attached to the source zaak in Procest
- AND document metadata is stored in the document register

#### Scenario: Workflow-triggered generation
- GIVEN an n8n workflow monitors zaak status changes
- WHEN a zaak status changes to "besluit genomen"
- THEN the workflow triggers document generation via the API
- AND the generated beschikking is attached to the zaak

#### Scenario: Audit trail
- GIVEN a document is generated
- WHEN the metadata is stored in the document register
- THEN it includes: template ID, version, data sources, generation timestamp, generating user

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-070 | Generated documents can be attached to zaak in Procest | SHOULD | Planned |
| DCS-071 | Document generation triggerable from n8n workflows | SHOULD | Planned |
| DCS-072 | Generated document metadata stored in document register | MUST | Planned |

### Requirement: REQ-DCS-09 Mock Register Test Data (Priority: Should)

Mock registers MUST provide realistic test data for template merge testing during development.

#### Scenario: BRP data merge test
- GIVEN BRP mock register is loaded with 35 person records
- WHEN a template is merged with BSN 999993653 (Suzanne Moulin)
- THEN person fields (naam, adres, geboortedatum) are available as template variables

#### Scenario: Bulk generation test
- GIVEN BRP mock register has 35 person records
- WHEN bulk generation is tested
- THEN 35 letters are generated with individual citizen data

#### Scenario: Nested resolution test
- GIVEN BAG mock register has nummeraanduiding records
- WHEN nested resolution is tested (zaak -> persoon -> adres)
- THEN address data is resolved from BAG through the reference chain

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-080 | BRP mock register (35 persons) for person data merge testing | SHOULD | Planned |
| DCS-081 | KVK mock register (16 businesses) for business data merge testing | SHOULD | Planned |
| DCS-082 | BAG mock register (32 addresses) for nested address resolution testing | SHOULD | Planned |

