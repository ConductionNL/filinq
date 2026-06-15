## Context

DocuDesk already provides template management (CRUD for Twig/HTML templates via OpenRegister) and stateless PDF rendering (mPDF + Twig sandbox). The document-creatie-sjablonen spec defines a planned DocumentService for merging data into templates, but it is not yet implemented.

Government tenders (245 tenders, 555 requirements) demand a higher-level correspondence workflow: generate letters/beschikkingen from templates with recipient data from case systems, support batch generation for multiple recipients, log all correspondence for audit trails, and support multiple output formats.

This change adds a correspondence-specific layer on top of the existing building blocks, introducing data resolution from OpenRegister, batch generation with background jobs, huisstijl enforcement, and a correspondence audit register.

## Goals / Non-Goals

**Goals:**
- Provide a dedicated correspondence generation API that resolves recipient data and produces letters
- Support batch generation for multiple recipients with per-item error handling
- Log all generated correspondence in the document register for audit trails
- Support PDF (default), DOCX, HTML, and email output formats
- Apply organization huisstijl (logo, header, footer, margins) automatically
- Extract data resolution into a reusable DataResolverService

**Non-Goals:**
- Actual email sending (handled by n8n or notification service)
- Physical mail dispatch (handled by external print/mail services)
- Template creation/editing UI (covered by advanced-template-management change)
- Full DocumentService implementation (only the data resolution and correspondence portions)

## Decisions

### 1. Separate CorrespondenceService rather than extending PdfService
**Decision:** Create a new `CorrespondenceService` that orchestrates the workflow rather than adding correspondence logic to PdfService.
**Rationale:** PdfService is stateless by design (PDF-002) -- it receives template content and data, returns PDF binary. Correspondence generation involves data resolution, register logging, and format selection, which are higher-level concerns. Keeping PdfService stateless preserves its reusability for other consumers.
**Alternative considered:** Adding correspondence methods to PdfService -- rejected because it would violate single responsibility and the existing spec.

### 2. Reusable DataResolverService for OpenRegister data resolution
**Decision:** Extract data resolution into `DataResolverService` rather than embedding it in CorrespondenceService.
**Rationale:** Data resolution from OpenRegister objects (by register + schema + UUID, with nested reference support) is needed by both the correspondence workflow and the future DocumentService from document-creatie-sjablonen. A shared service avoids duplication.
**Alternative considered:** Inline resolution in CorrespondenceService -- rejected because DocumentService would need the same logic.

### 3. Nextcloud background jobs for async batch generation
**Decision:** Use Nextcloud's `IJobList` and `TimedJob`/`QueuedJob` for async batch processing (>10 recipients).
**Rationale:** Nextcloud already provides a job queue via cron.php. This avoids introducing external dependencies (Redis, RabbitMQ) and works with existing Nextcloud deployment patterns. n8n could also trigger batch generation but the core mechanism should be Nextcloud-native.
**Alternative considered:** n8n workflows for batching -- rejected as a dependency for core functionality; n8n integration is optional via DCS-071.

### 4. LibreOffice headless for DOCX conversion
**Decision:** Use `soffice --headless --convert-to docx` for DOCX output, with graceful degradation if LibreOffice is not installed.
**Rationale:** LibreOffice headless is the standard approach in government environments and is already commonly available in Docker-based deployments. If unavailable, a 503 error is returned rather than silently failing.
**Alternative considered:** PHP-native DOCX libraries (PhpWord) -- rejected because HTML-to-DOCX fidelity is poor; Collabora CODE -- rejected as heavyweight dependency.

### 5. Correspondence schema in document_register.json
**Decision:** Add a `correspondence` schema to the existing `document_register.json` rather than creating a separate register.
**Rationale:** The document register already contains `report`, `template`, and `entity` schemas. Correspondence metadata is document metadata and belongs in the same register. The register is already defined but not loaded on boot -- this change will also ensure it is loaded.

### 6. Huisstijl as OpenRegister object
**Decision:** Store huisstijl configuration as an OpenRegister object in the document register (schema: `huisstijl`) rather than as Nextcloud app config.
**Rationale:** Huisstijl configuration is structured data (logo, colors, header/footer templates, margins) that benefits from OpenRegister's object model. Multiple organizations in a multi-tenant setup can have different huisstijl configurations. App config would be limited to key-value pairs.

## Architecture

### New Files
```
lib/Service/CorrespondenceService.php    -- Orchestrates correspondence generation
lib/Service/DataResolverService.php      -- Resolves data from OpenRegister objects
lib/Controller/CorrespondenceController.php -- REST API endpoints
lib/BackgroundJob/BatchCorrespondenceJob.php -- Async batch processing
```

### Modified Files
```
appinfo/routes.php                       -- Add correspondence routes
lib/Settings/document_register.json      -- Add correspondence + huisstijl schemas
lib/AppInfo/Application.php              -- Ensure document_register.json is loaded on boot
```

### Service Dependency Graph
```
CorrespondenceController
  -> CorrespondenceService
       -> TemplateService (fetch template)
       -> DataResolverService (resolve recipient data)
       -> TemplateRenderer (render Twig)
       -> PdfService (PDF output)
       -> ObjectService (log to correspondence register)
       -> IJobList (async batch dispatch)
```

### Data Resolution Flow
```
1. Receive dataRefs: [{register, schema, id}, ...]
2. For each ref: ObjectService::find(id, register, schema)
3. For nested refs: inspect resolved object for UUID-like values referencing other objects
4. Recursion depth limit: 3 levels
5. Merge ad-hoc data on top of resolved data
6. Return merged context keyed by schema name
```

## Risks / Trade-offs

**[LibreOffice dependency]** DOCX output requires LibreOffice installed on the server. Not all deployments will have it.
-> Mitigation: Graceful 503 error when unavailable. Document installation requirements. PDF remains the default.

**[Background job reliability]** Nextcloud cron jobs depend on system cron being configured correctly.
-> Mitigation: Synchronous fallback for small batches (<=10). Admin documentation for cron setup. Job status endpoint for monitoring.

**[document_register.json not loaded on boot]** The existing document register is defined but not imported during Application::boot().
-> Mitigation: Add the import call to Application.php alongside the existing docudesk_register.json import.

**[Nested resolution performance]** Resolving nested references (3 levels) could result in many ObjectService::find() calls for complex object graphs.
-> Mitigation: Limit to 3 levels. Cache resolved objects within a single generation request to avoid duplicate lookups.

## Open Questions

1. Should huisstijl configurations be manageable via the admin UI, or is API-only sufficient for the first iteration?
2. What is the maximum batch size to support? (Current design: no hard limit, but >10 triggers async)
3. Should the correspondence register entries include a reference to the generated file in Nextcloud storage, or only the metadata?
