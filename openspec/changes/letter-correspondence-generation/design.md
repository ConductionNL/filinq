## Context

DocuDesk provides `TemplateService` (template CRUD backed by OpenRegister), `PdfService` (stateless mPDF rendering via Twig sandbox), and — as a planned spec — `DataResolverService` from `document-creatie-sjablonen` (nested OpenRegister data resolution up to 3 levels). The `correspondence` and `huisstijl` schemas are already present in `docudesk_register.json` and registered on boot. What is missing is the orchestration layer that chains data resolution → template render → huisstijl apply → format output → audit log into a single correspondence workflow.

## Goals / Non-Goals

**Goals:**

- Implement `CorrespondenceService::generate()` that orchestrates the full correspondence flow in one call.
- Implement `CorrespondenceService::generateBatch()` with synchronous handling for ≤ 10 recipients and background job dispatch for > 10.
- Expose `POST /api/correspondence/generate`, `POST /api/correspondence/generate/batch`, and `GET /api/correspondence/jobs/{jobId}` as authenticated endpoints.
- Apply huisstijl configuration (logo, header/footer HTML, margins) automatically from an OpenRegister `huisstijl` object when available.
- Log every generation attempt (success or failure) as a `correspondence` object in the document register.
- Support output formats: PDF (default), DOCX (LibreOffice conversion), HTML (preview), and email (inline-styled HTML).

**Non-Goals:**

- Email sending (handled by n8n or Nextcloud Notifications).
- Physical mail dispatch or print queue integration.
- Template creation/editing UI (covered by `advanced-template-management` change).
- Template versioning (covered by `advanced-template-management` change).
- OpenConnector/external BRP data resolution — `DataResolverService` handles this; `CorrespondenceService` delegates without custom adapter logic.

## Decisions

### D1. `CorrespondenceService` is the single orchestration entry point

**Decision:** A new `CorrespondenceService` wraps all sub-services and is the only class callers inject. It does not replace `TemplateService`, `PdfService`, or `DataResolverService` — it delegates to them.

**Rationale:** Callers (controllers, background jobs, future workflow triggers) need one place to call. Keeping orchestration separate from rendering and data-resolution services maintains single-responsibility and allows the sub-services to remain reusable in other contexts (e.g., `PdfService` still used by the direct PDF endpoint).

### D2. Batch threshold: synchronous ≤ 10, async > 10

**Decision:** Batches of up to 10 recipients are processed synchronously in the same HTTP request; batches larger than 10 are dispatched as a Nextcloud `IJob` (`BatchCorrespondenceJob`) and a 202 response with `jobId` is returned.

**Rationale:** The 10-item threshold keeps synchronous response times under ~5 seconds (mPDF render ~0.3–0.5 s per letter). Beyond 10, processing in the request thread risks PHP-FPM timeout. The threshold is not user-configurable in v1 — it is a hard server-side rule, keeping the API surface simple.

**Trade-off:** A caller expecting synchronous results must check the batch size before calling. The response contract differs (200 array vs 202 with jobId). This asymmetry is surfaced clearly in the API contract and documented.

### D3. Per-recipient failure isolation in batch

**Decision:** A failure for one recipient (e.g., missing object data, render error) MUST NOT abort the batch. Each result in the array carries `status: "generated"` or `status: "error"` plus an optional `error` field. The batch overall returns HTTP 200 (synchronous) or completes the background job with partial success.

**Rationale:** A batch of 50 citizen letters should not fail entirely because one citizen's address object is missing. Partial success is vastly more useful for government operators who can re-run failures individually.

### D4. Huisstijl applied via `ObjectService::findAll()` by slug

**Decision:** `CorrespondenceService` resolves huisstijl via `ObjectService::searchObjects({schema: "huisstijl", register: "document"})`. If the request supplies a `huisstijlId`, it fetches that object; otherwise it takes the first result as the default. No fallback chain beyond this — if none found, default PdfService margins (15 mm all sides) are used and no header/footer is applied.

**Rationale:** Huisstijl is organisation-wide configuration; most deployments have exactly one. Querying by slug/ID is lightweight. Keeping the fallback simple avoids implicit config hierarchies.

### D5. DOCX output via LibreOffice headless; graceful degradation on absence

**Decision:** DOCX conversion uses `soffice --headless --convert-to docx` invoked via PHP `exec()` (wrapped in a `LibreOfficeConverter` helper for testability). If `soffice` is not found or exits non-zero, the endpoint returns HTTP 503 with `{"message": "DOCX conversion service unavailable"}`.

**Rationale:** LibreOffice is an optional server dependency. Failing hard is correct — silently returning a PDF when DOCX was requested would be worse than a clear error. The 503 code signals a server-side capability gap, not a client error.

### D6. Email format returns clean HTML without `@page` rules

**Decision:** When `options.format = "email"`, `TemplateRenderer::render()` is called directly and the result is post-processed to strip `@page` CSS rules and convert block-level styles to inline attributes. No PDF or DOCX conversion runs.

**Rationale:** Email clients do not support `@page` CSS, fixed page dimensions, or external stylesheets. Inline styles maximise compatibility. The caller (e.g., n8n workflow) receives ready-to-embed HTML.

### D7. Correspondence audit log written on both success and failure

**Decision:** `CorrespondenceService::generate()` always writes a `correspondence` object in the document register (register: `document`, schema: `correspondence`) regardless of outcome. On failure, `status` is `"failed"` and `errorMessage` is populated.

**Rationale:** Audit completeness for government GDPR/Archiefwet compliance. A failed generation attempt is still an event that must be traceable.

## Component Map

### Backend (PHP)

| Component | File | Purpose |
|-----------|------|---------|
| CorrespondenceService (new) | `lib/Service/CorrespondenceService.php` | Orchestrates generate() and generateBatch(); delegates to TemplateService, DataResolverService, TemplateRenderer, PdfService, ObjectService |
| CorrespondenceController (new) | `lib/Controller/CorrespondenceController.php` | Thin REST layer for /api/correspondence/generate and batch endpoints; delegates to CorrespondenceService |
| BatchCorrespondenceJob (new) | `lib/Job/BatchCorrespondenceJob.php` | Nextcloud IJob implementation for async batch generation; reads job params, calls CorrespondenceService::generate() per recipient |
| LibreOfficeConverter (new) | `lib/Service/LibreOfficeConverter.php` | Wraps soffice exec call; throws RuntimeException on absence/failure (testable wrapper) |
| docudesk_register.json (modified) | `lib/Settings/docudesk_register.json` | Seed data objects added for `correspondence` and `huisstijl` schemas |
| routes.php (modified) | `appinfo/routes.php` | Three new routes: POST correspondence/generate, POST correspondence/generate/batch, GET correspondence/jobs/{jobId} |

### Data Flow

```
POST /api/correspondence/generate
  └── CorrespondenceController::generate()
        └── CorrespondenceService::generate(templateId, dataRefs, options)
              ├── TemplateService::getTemplate(templateId)            → template object
              ├── DataResolverService::resolve(dataRefs)              → merged data context
              ├── [huisstijl] ObjectService::searchObjects(...)       → huisstijl config
              ├── TemplateRenderer::render(content, context)          → rendered HTML
              ├── PdfService::renderPdf(html, options) [if format=pdf]
              │   OR LibreOfficeConverter::convert(html, 'docx')      [if format=docx]
              │   OR return rendered HTML                             [if format=html|email]
              └── ObjectService::saveObject(correspondence, ...)      → audit log entry
```

## Reuse Analysis

This change deliberately reuses existing DocuDesk services and the platform-provided OpenRegister layer:

| What | Where | How used |
|------|-------|----------|
| `TemplateService::getTemplate()` | `lib/Service/TemplateService.php` | Template lookup by UUID; namespace filtering for email templates |
| `DataResolverService::resolve()` | `lib/Service/DataResolverService.php` (from document-creatie-sjablonen) | Nested OpenRegister data resolution, up to 3 levels deep |
| `TemplateRenderer::render()` | `lib/Service/TemplateRenderer.php` | Twig sandboxed rendering of template content with merged context |
| `PdfService::renderPdf()` | `lib/Service/PdfService.php` | HTML-to-PDF via mPDF; huisstijl margins/header/footer passed as options |
| `ObjectService` (OpenRegister) | Platform | Huisstijl lookup, correspondence audit record creation, recipient data fetch |
| `IJobList::add()` (Nextcloud) | Platform | Background job dispatch for large batches |
| `AuditTrailService` (OpenRegister) | Platform | Automatic audit trail on correspondence object writes — no custom audit logging needed |

No custom search endpoints, file upload components, or pagination logic are introduced. All list/detail views for correspondence log objects are provided by the platform's `CnIndexPage` + `CnDetailPage` components.

## Seed Data

Seed data is required because `correspondence` and `huisstijl` schemas are present but the app ships with no objects, which blocks QA testing. Objects are added to `lib/Settings/docudesk_register.json` under `components.objects[]` using the `@self` envelope.

### huisstijl — 3 seed objects

```json
{
  "@self": { "register": "document", "schema": "huisstijl", "slug": "huisstijl-gemeente-westerbork" },
  "name": "Huisstijl Gemeente Westerbork",
  "primaryColor": "#154273",
  "headerHtml": "<div style='color:#154273;font-size:11pt;'>Gemeente Westerbork — Afdeling Vergunningen</div>",
  "footerHtml": "<div style='font-size:8pt;'>Pagina {{ page }} van {{ pages }} | Gemeente Westerbork, Hoofdstraat 1, 9431 AA Westerbork</div>",
  "defaultMargins": { "top": 25, "right": 20, "bottom": 20, "left": 20 }
}
```

```json
{
  "@self": { "register": "document", "schema": "huisstijl", "slug": "huisstijl-gemeente-hoogeveen" },
  "name": "Huisstijl Gemeente Hoogeveen",
  "primaryColor": "#E84B20",
  "headerHtml": "<div style='color:#E84B20;font-size:11pt;'>Gemeente Hoogeveen</div>",
  "footerHtml": "<div style='font-size:8pt;'>Pagina {{ page }} van {{ pages }} | Gemeente Hoogeveen, Raadhuisplein 1, 7902 AA Hoogeveen</div>",
  "defaultMargins": { "top": 20, "right": 20, "bottom": 20, "left": 25 }
}
```

```json
{
  "@self": { "register": "document", "schema": "huisstijl", "slug": "huisstijl-ministerie-vws" },
  "name": "Huisstijl Ministerie van VWS",
  "primaryColor": "#01689B",
  "headerHtml": "<div style='color:#01689B;font-size:11pt;'>Ministerie van Volksgezondheid, Welzijn en Sport</div>",
  "footerHtml": "<div style='font-size:8pt;'>Pagina {{ page }} van {{ pages }} | Postbus 20350, 2500 EJ Den Haag | www.rijksoverheid.nl</div>",
  "defaultMargins": { "top": 30, "right": 25, "bottom": 25, "left": 25 }
}
```

### correspondence — 4 seed objects (audit log examples)

```json
{
  "@self": { "register": "document", "schema": "correspondence", "slug": "correspondence-beschikking-jansen-2026" },
  "templateId": "a1b2c3d4-0001-0000-0000-000000000001",
  "templateName": "Beschikking Omgevingsvergunning",
  "recipientId": "f0e1d2c3-0001-0000-0000-000000000001",
  "recipientType": "PERSON",
  "caseReference": "b2c3d4e5-0001-0000-0000-000000000001",
  "generatedAt": "2026-05-15T09:30:00+02:00",
  "format": "pdf",
  "status": "generated",
  "generatedBy": "medewerker.vergunningen"
}
```

```json
{
  "@self": { "register": "document", "schema": "correspondence", "slug": "correspondence-kennisgeving-de-vries-2026" },
  "templateId": "a1b2c3d4-0002-0000-0000-000000000002",
  "templateName": "Kennisgeving Bestemmingsplanwijziging",
  "recipientId": "f0e1d2c3-0002-0000-0000-000000000002",
  "recipientType": "PERSON",
  "caseReference": "b2c3d4e5-0002-0000-0000-000000000002",
  "generatedAt": "2026-05-16T11:00:00+02:00",
  "format": "pdf",
  "status": "generated",
  "generatedBy": "medewerker.ruimtelijke.ordening"
}
```

```json
{
  "@self": { "register": "document", "schema": "correspondence", "slug": "correspondence-herinnering-bakker-bv-2026" },
  "templateId": "a1b2c3d4-0003-0000-0000-000000000003",
  "templateName": "Betalingsherinnering",
  "recipientId": "f0e1d2c3-0003-0000-0000-000000000003",
  "recipientType": "ORGANIZATION",
  "caseReference": null,
  "generatedAt": "2026-05-17T14:45:00+02:00",
  "format": "email",
  "status": "generated",
  "generatedBy": "financien.beheer"
}
```

```json
{
  "@self": { "register": "document", "schema": "correspondence", "slug": "correspondence-failed-missing-data-2026" },
  "templateId": "a1b2c3d4-0004-0000-0000-000000000004",
  "templateName": "Vergunningsbrief Sloop",
  "recipientId": "f0e1d2c3-0004-0000-0000-000000000004",
  "recipientType": "PERSON",
  "caseReference": "b2c3d4e5-0004-0000-0000-000000000004",
  "generatedAt": "2026-05-18T10:15:00+02:00",
  "format": "pdf",
  "status": "failed",
  "generatedBy": "medewerker.vergunningen",
  "errorMessage": "DataResolverService: object not found in register 'brp', schema 'ingeschreven-persoon', id 'f0e1d2c3-0004-0000-0000-000000000004'"
}
```

## Open Questions

- Should `generateBatch()` store partial results in OpenRegister as the background job progresses (for live progress polling), or keep results only in the job's argument store? Provisional: store per-recipient results in a temporary OpenRegister object keyed by jobId; clean up after 24 h via a separate cleanup job.
- Does the DOCX conversion via LibreOffice need a conversion server (LibreOffice Online / Collabora) or is headless `soffice` on the same machine sufficient? Provisional: headless on same machine; conversion server is a future option if performance demands it.
