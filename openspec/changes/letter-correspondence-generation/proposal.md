---
status: proposed
source: market-intelligence
clusters: [45, 55]
total_tenders: 245
total_requirements: 555
---

# Letter/Correspondence Generation

## Why

DocuDesk already has template CRUD (`TemplateService`), PDF rendering (`PdfService`), and a data-resolution layer (`DataResolverService` from `document-creatie-sjablonen`). These building blocks exist but there is no end-to-end correspondence workflow: no single service that orchestrates recipient data resolution → template merge → huisstijl application → output formatting → register logging in one cohesive call. Government users generating a beschikking, a citizen notification letter, or a batch of permit decisions must stitch together multiple capabilities manually. Market intelligence confirms 245 tenders and 555 requirements demand exactly this — letter generation from case data with merge fields, batch generation for multiple recipients, and email template support with huisstijl enforcement.

Sample tender requirements:
- **Gemeente Zuidplas**: "De Oplossing beschikt over documentcreatiefunctionaliteit om documenten en e-mails op basis van sjablonen te creeren."
- **Gemeente Molenlanden**: "Als de Oplossing een eigen documentcreatiefunctionaliteit heeft, is het mogelijk om centraal emailsjablonen inclusief voet- en kopteksten te configureren."
- **Ministerie van VWS**: "Als gebruiker, wil ik emailtemplates kunnen creeren conform CIBG-huisstijl."

## What Changes

- **NEW capability:** `correspondence-generation` — `CorrespondenceService` at `OCA\DocuDesk\Service\CorrespondenceService` orchestrates the full end-to-end correspondence flow: resolve recipient data from OpenRegister objects (delegating to `DataResolverService`), merge into a template (delegating to `TemplateService` + `TemplateRenderer`), apply huisstijl defaults (logo, header/footer HTML, margins), produce output in the requested format (PDF, DOCX, HTML, or email), and write an audit record to the `correspondence` schema in the document register.
- **NEW:** `POST /api/correspondence/generate` — authenticated single-letter generation endpoint returning a `DataDownloadResponse`.
- **NEW:** `CorrespondenceService::generateBatch()` — synchronous for ≤ 10 recipients; dispatches a Nextcloud background job for > 10 recipients, returning a `jobId`.
- **NEW:** `POST /api/correspondence/generate/batch` — batch generation endpoint; returns 202 with job ID for large batches.
- **NEW:** `GET /api/correspondence/jobs/{jobId}` — async job status polling endpoint.
- **MODIFIED:** `docudesk_register.json` — `correspondence` and `huisstijl` schemas already present; seed data objects added (3–5 per schema) to support dev/test via the existing `importFromApp()` pipeline.
- **MODIFIED:** `appinfo/routes.php` — three new API routes registered.

## Capabilities

### New Capabilities

- `correspondence-generation`

## Cross-app Dependencies

- **Hard** — `docudesk:template-management` — `TemplateService::getTemplate()` used for template lookup; namespace filtering used for email templates.
- **Hard** — `docudesk:pdf-generation` — `PdfService::renderPdf()` used for PDF output.
- **Hard** — `docudesk:document-creatie-sjablonen` — `DataResolverService` used for nested OpenRegister data resolution (up to 3 levels deep).
- **Soft** — LibreOffice headless (`soffice --headless --convert-to docx`) — required only for DOCX output; graceful 503 degradation when unavailable.

## Impact

- **Code (docudesk):** `lib/Service/CorrespondenceService.php` (new), `lib/Controller/CorrespondenceController.php` (new), `lib/Job/BatchCorrespondenceJob.php` (new), `appinfo/routes.php` (extended), `lib/Settings/docudesk_register.json` (seed data added).
- **API contract:** Three new endpoints; no existing endpoints modified. Fully additive and non-breaking.
- **Migration:** None — `correspondence` and `huisstijl` schemas are already registered; only seed data is added via idempotent `importFromApp()` with `force: false`.
