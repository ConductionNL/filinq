---
status: in-progress
---

# document-creatie-sjablonen Specification

**Status**: in-progress
**OpenSpec changes**:
- [guided-document-wizard](../../changes/guided-document-wizard/) _(active)_ — wizard-driven generations validate `options.wizardContext` server-side and record the interview context (wizard id + version + answers) on the `generatedDocument` audit object (REQ-DDGDW-008) (kind: code)
- [multi-format-output](../../changes/multi-format-output/) _(active)_ — `options.formats` produces multiple outputs from a single render pass with a JSON manifest; `docx` becomes a first-class editable generation format; every produced output is recorded on `generatedDocument.outputs` (REQ-DDMFO-001/003/006) (kind: code)
- [document-generation-list-refs](../../changes/archive/2026-09-28-document-generation-list-refs/) _(archived 2026-09-28)_ — `options.listRefs` resolves an array of OpenRegister objects (via the slug-aware search path, including external DBAL registers) into the Twig context under a named key, alongside single-object `dataRefs`; scoped to generate/preview only (REQ-DDLR-001/002/003/004) (kind: code)

## Purpose
Generates documents from templates by merging resolved data into a sandboxed Twig template. Merge data is resolved from OpenRegister objects by register, schema, and object UUID with nested resolution up to three levels deep, optional external data via OpenConnector, and ad-hoc JSON context, while rendering supports conditional sections, iteration, and per-field warnings for missing values. This enables automated creation of formal documents such as beschikkingen from structured case data.

@e2e exclude API-only generation surface (POST /api/documents/generate, /generate/preview, /generate/bulk, GET /api/documents/jobs/{jobId}) reached by no Vue component; asserted below HTTP by PHPUnit — tests/unit/Controller/DocumentControllerTest.php, tests/unit/Service/DocumentServiceTest.php, tests/unit/BackgroundJob/BatchDocumentJobTest.php

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
- AND document metadata is stored in the `filinq` register

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
- AND document metadata is stored in the `filinq` register

#### Scenario: Workflow-triggered generation
- GIVEN an n8n workflow monitors zaak status changes
- WHEN a zaak status changes to "besluit genomen"
- THEN the workflow triggers document generation via the API
- AND the generated beschikking is attached to the zaak

#### Scenario: Audit trail
- GIVEN a document is generated
- WHEN the metadata is stored in the `filinq` register
- THEN it includes: template ID, version, data sources, generation timestamp, generating user

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-070 | Generated documents can be attached to zaak in Procest | SHOULD | Planned |
| DCS-071 | Document generation triggerable from n8n workflows | SHOULD | Planned |
| DCS-072 | Generated document metadata stored in the `filinq` register | MUST | Planned |

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

### Requirement: listRefs resolve collections into the Twig context (REQ-DDLR-001)

`DataResolverService::resolve()` MUST accept an optional `listRefs`
parameter: an array of `{register, schema, filter?, limit?, order?, as?}`
entries. Each entry MUST resolve via
`OCA\OpenRegister\Service\ObjectService::setRegister()` /
`::setSchema()` (both slug-aware) followed by `::searchObjectsPaginated()`
— the register/schema-context pattern that also reaches a schema's
`x-openregister-object-source` provider when one is configured (unlike
the sibling `searchObjects()`/`searchObjectsBySlug()` methods, which never
consult the object-source and return nothing for a DBAL-backed schema) —
to an array of the matching objects (each serialized via `jsonSerialize()`
where available), placed in the merged data context under the key `as`.
When `as` is omitted it MUST default to the schema slug converted to a
legal Twig identifier (every character outside `[a-zA-Z0-9_]` replaced
with `_`, a leading digit prefixed with `_`) with `_list` appended — e.g.
schema `v-app-competitors` defaults to `v_app_competitors_list`. `filter`
entries (if present) MUST be passed through as top-level search filter
keys; `limit` MUST be forwarded as the search's `_limit`; `order` (if an
array) MUST be forwarded as `_order`.

#### Scenario: Resolve a DBAL-backed collection with an explicit `as` key

- GIVEN a virtual register `spectr-live` with schema `v-app-competitors`, reachable via OpenRegister's DBAL search path
- WHEN a `listRefs` entry `{register: "spectr-live", schema: "v-app-competitors", filter: {app_id: 6}, limit: 5, as: "competitors"}` is resolved
- THEN the Twig context contains a `competitors` key
- AND it is an array of at most 5 objects, each matching `app_id: 6`
- @e2e tests/e2e/spec-coverage/document-generation-list-refs.spec.ts

#### Scenario: Default `as` key sanitises a hyphenated schema slug

- GIVEN a `listRefs` entry with `schema: "v-app-competitors"` and no `as`
- WHEN the listRef is resolved
- THEN the resolved array appears under the context key `v_app_competitors_list`
- AND `{% for c in v_app_competitors_list %}` is valid, renderable Twig
- @e2e tests/e2e/spec-coverage/document-generation-list-refs.spec.ts

#### Scenario: A listRef search failure is a soft, per-item error

- GIVEN two `listRefs` entries, one referencing an unresolvable schema slug
- WHEN `resolve()` runs
- THEN the listRef with the valid slug still resolves its array under its `as` key
- AND the failing listRef's `as` key is present with an empty array
- AND the failure is reported in `errors` (mirroring how `dataRefs` failures are reported), not thrown
- @e2e exclude fault-injection (an unresolvable slug at request time) is not browser-drivable — covered by PHPUnit (tests/unit/Service/ListReferenceResolverTest.php::testSearchFailureIsSoftError)

### Requirement: listRefs precedence and resolution order (REQ-DDLR-002)

`resolve()` MUST resolve `listRefs` AFTER `dataRefs` and BEFORE `adHocData`.
`adHocData` MUST take precedence over a `listRefs`-resolved key on conflict,
consistent with `adHocData`'s existing precedence over `dataRefs`
(REQ-DCS-01). The combined precedence order is: `dataRefs` < `listRefs` <
`adHocData`.

#### Scenario: adHocData overrides a listRef's key

- GIVEN a `listRefs` entry resolves under the key `competitors`
- AND `options.adHocData` also supplies a `competitors` key
- WHEN `resolve()` runs
- THEN the Twig context's `competitors` value is the `adHocData` value, not the listRef's resolved array
- @e2e exclude precedence-ordering pin; covered by PHPUnit (tests/unit/Service/DataResolverServiceTest.php::testAdHocDataOverridesListRef)

### Requirement: listRefs guardrails reject malformed requests with HTTP 400 (REQ-DDLR-003)

Guardrail violations MUST be validated for every `listRefs` entry BEFORE any
OpenRegister search runs for the request (fail-fast: a malformed entry
aborts the whole request, not just itself), and MUST surface as HTTP 400
through `DocumentController`'s existing exception-to-status mapping
(`Exception` code 400). The guardrails are: (a) at most 10 `listRefs`
entries per request; (b) each entry's `filter` values MUST be scalars (or
null) — an array/object filter value is rejected, not silently coerced or
dropped; (c) each entry's `limit`, if present, MUST be an integer between 1
and 500 inclusive; (d) each entry's resolved `as` key MUST match
`/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/`; (e) no two resolved `as` keys within one
request — across `dataRefs` schema keys and all `listRefs` — may collide.

#### Scenario: More than 10 listRefs is rejected

- GIVEN a request with 11 `listRefs` entries
- WHEN `POST /api/documents/generate/preview` is called
- THEN the response is HTTP 400
- AND no OpenRegister search runs for any of the 11 entries
- @e2e exclude guardrail boundary; covered by PHPUnit (tests/unit/Service/ListReferenceResolverTest.php::testTooManyListRefsRejected, ::testGuardrailViolationAbortsBeforeAnySearch)

#### Scenario: A non-scalar filter value is rejected

- GIVEN a `listRefs` entry with `filter: {nested: {not: "scalar"}}`
- WHEN the request is validated
- THEN the response is HTTP 400 and identifies the offending filter key
- @e2e exclude guardrail boundary; covered by PHPUnit (tests/unit/Service/ListReferenceResolverTest.php::testNonScalarFilterValueRejected)

#### Scenario: `as` colliding with a dataRefs key is rejected

- GIVEN a `dataRefs` entry resolves under the schema key `persoon`
- AND a `listRefs` entry explicitly sets `as: "persoon"`
- WHEN the request is validated
- THEN the response is HTTP 400
- @e2e exclude guardrail boundary; covered by PHPUnit (tests/unit/Service/DataResolverServiceTest.php::testListRefAsKeyCollidesWithDataRefKey, tests/unit/Service/ListReferenceResolverTest.php::testAsKeyCollisionWithReservedKeyRejected, ::testAsKeyCollisionBetweenTwoListRefsRejected)

### Requirement: listRefs are not wired into bulk generation (REQ-DDLR-004)

`DocumentService::generateBulk()` MUST NOT accept or resolve
`options.listRefs`, on either the synchronous or the async
`BatchDocumentJob` path. This is an explicit scope exclusion, not an
oversight: the async job
persists per-object job status/progress but no per-object rendered output
artifact, so a per-object-resolved list would have nowhere durable to
attach to on the majority (>10 objects) branch, and silently doing nothing
is worse than not offering it.

#### Scenario: listRefs on a bulk request has no effect

- GIVEN a `POST /api/documents/generate/bulk` request includes `options.listRefs`
- WHEN bulk generation runs (sync or async)
- THEN `options.listRefs` is not read or resolved by `generateBulk()`
- AND this is documented on `DocumentService::generateBulk()`'s docblock, not silently dropped without explanation
- @e2e exclude scope-exclusion pin, not a behaviour to browser-test; covered by code review of DocumentService::generateBulk() docblock
