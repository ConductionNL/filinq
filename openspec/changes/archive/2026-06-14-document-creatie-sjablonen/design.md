# Design: document-creatie-sjablonen

status: pr-created

## Architecture Overview

Extends the existing `template-management` and `pdf-generation` building blocks with a
higher-level document generation workflow for formal government documents:

1. **Data resolution**: `DataResolverService` (existing) fetches OpenRegister objects by
   register/schema/UUID with nested resolution up to 3 levels deep.
2. **Template rendering**: `TemplateRenderer` (existing) executes the Twig sandbox.
3. **Huisstijl enforcement**: `DocumentService.renderWithHuisstijl()` wraps rendered HTML
   with header/footer from a huisstijl config stored in OpenRegister.
4. **Output formats**: PDF via `PdfService` (existing mPDF), ODF via LibreOffice headless,
   HTML for browser preview.
5. **Audit trail**: `DocumentService.logGeneratedDocument()` stores template UUID + version
   (DCS-051) and generation metadata in the `generatedDocument` schema (DCS-072).
6. **Bulk generation**: synchronous for ≤10 objects, async `BatchDocumentJob` for larger
   batches with job status queryable via `GET /api/documents/jobs/{jobId}`.

## New Components

| File | Purpose |
|---|---|
| `lib/Service/DocumentService.php` | Orchestrates document generation end-to-end |
| `lib/BackgroundJob/BatchDocumentJob.php` | Async queued job for bulk batches |
| `lib/Controller/DocumentController.php` | REST API controller for 4 endpoints |
| `lib/Settings/docudesk_register.json` | Added `generatedDocument` schema to document register |
| `appinfo/routes.php` | 4 new document generation routes |

## API Endpoints

| Method | URL | Purpose |
|---|---|---|
| POST | `/api/documents/generate` | Single document (pdf/odf/html) |
| POST | `/api/documents/generate/preview` | HTML preview without audit log |
| POST | `/api/documents/generate/bulk` | Bulk generation (sync ≤10, async >10) |
| GET | `/api/documents/jobs/{jobId}` | Async job status |

## Declarative-vs-imperative decision

`DocumentService` is a legitimate service (not declarative) because it performs:
- External conversion via LibreOffice (`convertToOdf`)
- PDF rendering via mPDF (delegated to `PdfService`)
- Multi-step orchestration of data resolution + template rendering + output conversion
- Audit log writes to OpenRegister

None of these map to the seven `x-openregister-*` schema extension types in ADR-031.

## Test Infrastructure Fixes

Fixed pre-existing stub collision between `NextcloudStubs.php` and `OpenRegisterStubs.php`
(duplicate `OCP\IRequest`, `OCP\IL10N`, `JSONResponse`, `DataDownloadResponse`, `Controller`
declarations). Added 15+ missing type stubs. Fixed 6 pre-existing test assertion bugs.
All 339 unit tests now pass.
