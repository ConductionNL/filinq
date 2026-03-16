---
status: proposed
---

# Document Creatie Sjablonen

## Purpose

Provides document creation from templates by merging zaak/object data into pre-defined templates, producing ODF and PDF output. Extends the existing `template-management` (CRUD for Twig/HTML templates) and `pdf-generation` (stateless PDF rendering) specs with a higher-level workflow: resolve data from OpenRegister objects or external APIs, merge into templates, enforce huisstijl, and produce output documents. Supports bulk generation (e.g., letters to multiple citizens) and template versioning. Key tender requirement: 39% of government tenders demand document creation from templates.

## Relation to Existing Specs

- **template-management**: Provides the underlying CRUD for templates. This spec adds data-resolution, merge execution, bulk generation, and ODF output on top.
- **pdf-generation**: Provides the low-level PDF rendering via mPDF. This spec orchestrates the end-to-end flow: data in, document out.
- **document-register**: Stores generated document metadata as report objects for audit trail.

## Requirements

### Data Resolution

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-001 | Resolve merge data from OpenRegister objects by register + schema + object UUID | MUST | Planned |
| DCS-002 | Resolve merge data from external API sources via OpenConnector (e.g., BRP, KVK, BAG) | SHOULD | Planned |
| DCS-003 | Support nested data resolution: a zaak object references a persoon, which references an adres — all resolved recursively up to 3 levels deep | MUST | Planned |
| DCS-004 | Data resolution failures return descriptive errors per field, not a generic 500 | MUST | Planned |
| DCS-005 | Accept ad-hoc data context (JSON object) alongside or instead of object references, merged with resolved data | MUST | Planned |

### Template Merge Execution

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-010 | `DocumentService::generateDocument(string $templateId, array $dataRefs, array $options): array` — resolve data, render template, return document metadata + binary | MUST | Planned |
| DCS-011 | Merge uses the existing Twig sandbox from pdf-generation (same security policy) | MUST | Planned |
| DCS-012 | Support conditional sections in templates (e.g., show/hide blocks based on zaaktype or status) | MUST | Planned |
| DCS-013 | Support iteration over collections (e.g., list of activiteiten, list of documenten in a zaak) | MUST | Planned |
| DCS-014 | Merge result is validated: missing required fields produce warnings in the response, not silent empty values | SHOULD | Planned |

### Output Formats

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-020 | PDF output via existing PdfService (default) | MUST | Planned |
| DCS-021 | ODF output (.odt) via server-side conversion — hard requirement in Dutch government tenders | MUST | Planned |
| DCS-022 | HTML output for preview in browser before final generation | SHOULD | Planned |
| DCS-023 | Output format selectable per request via `format` option: `pdf`, `odf`, `html` | MUST | Planned |

### Huisstijl Enforcement

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-030 | Templates can reference a huisstijl configuration (logo, colors, fonts, header/footer) stored as an OpenRegister object | MUST | Planned |
| DCS-031 | Huisstijl is applied automatically during rendering — template authors do not need to hardcode brand elements | SHOULD | Planned |
| DCS-032 | NL Design System tokens can be used as CSS variables in template styling | SHOULD | Planned |

### Bulk Generation

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-040 | `DocumentService::generateBulk(string $templateId, array $objectIds, array $options): array` — generate one document per object, return array of results | MUST | Planned |
| DCS-041 | Bulk generation is asynchronous for >10 objects — returns a job ID, status queryable via `GET /api/documents/jobs/{jobId}` | SHOULD | Planned |
| DCS-042 | Bulk generation supports merged output: all documents concatenated into a single PDF with page breaks | SHOULD | Planned |
| DCS-043 | Individual failures in bulk do not abort the entire batch — partial results are returned with per-item error details | MUST | Planned |

### Template Versioning

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-050 | Templates support versioning: each update creates a new version, previous versions are retained | MUST | Planned |
| DCS-051 | Generated documents reference the specific template version used (template UUID + version number) | MUST | Planned |
| DCS-052 | Previous template versions can be retrieved and used for re-generation | SHOULD | Planned |

### API Endpoints

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-060 | `POST /api/documents/generate` — generate a single document from template + data references | MUST | Planned |
| DCS-061 | `POST /api/documents/generate/bulk` — bulk generate documents for multiple objects | MUST | Planned |
| DCS-062 | `POST /api/documents/generate/preview` — HTML preview without producing final output | SHOULD | Planned |
| DCS-063 | `GET /api/documents/jobs/{jobId}` — query bulk generation job status | SHOULD | Planned |
| DCS-064 | All endpoints require authentication (`@NoAdminRequired @NoCSRFRequired`) | MUST | Planned |

### Zaaksysteem Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DCS-070 | Generated documents can be automatically attached to the source zaak in Procest | SHOULD | Planned |
| DCS-071 | Document generation can be triggered from n8n workflows (e.g., on zaak status change) | SHOULD | Planned |
| DCS-072 | Generated document metadata is stored in the document register for audit trail | MUST | Planned |

## Scenarios

### Generate a beschikking from a zaak

```
GIVEN a template "Beschikking Omgevingsvergunning" exists with namespace "procest"
AND a zaak object exists in OpenRegister with aanvrager, activiteiten, and besluit data
WHEN POST /api/documents/generate with templateId and zaak object reference
THEN data is resolved from the zaak (including nested aanvrager/adres)
AND the template is rendered with merged data
AND a PDF is returned with huisstijl applied
AND document metadata is stored in the document register
```

### Bulk generate citizen letters

```
GIVEN a template "Kennisgeving Bestemmingsplan" exists
AND 150 persoon objects are selected from OpenRegister
WHEN POST /api/documents/generate/bulk with templateId and object IDs
THEN a job ID is returned (>10 objects = async)
AND each letter is generated with the individual citizen's data
AND failures for individual citizens do not abort the batch
AND GET /api/documents/jobs/{jobId} returns progress and partial results
```

### ODF output for archiving

```
GIVEN a template exists and data is available
WHEN POST /api/documents/generate with format: "odf"
THEN an ODF (.odt) file is produced
AND the file is valid according to the ODF 1.2 specification
```

### Template version tracking

```
GIVEN template "Vergunningbrief" version 3 was used to generate a document last month
AND the template has since been updated to version 4
WHEN the user requests re-generation of the same document
THEN they can choose to use version 3 (original) or version 4 (current)
```

## Dependencies

- **template-management** spec: Template CRUD and namespace scoping
- **pdf-generation** spec: PDF rendering via mPDF + Twig sandbox
- **OpenRegister ObjectService**: Data resolution from register objects
- **OpenConnector**: External data resolution (BRP, KVK, BAG)
- **NL Design System**: Huisstijl CSS variable support
- **LibreOffice/unoconv** (or equivalent): Server-side ODF conversion
