## Why

DocuDesk generates documents (letters, reports, export summaries) that must be delivered as PDFs. Until this change, every app that needed a PDF had to bundle its own mPDF + Twig wiring — duplicating dependencies, duplicating sandbox configuration, and getting the security policy wrong in subtle ways (e.g. allowing `system()` in templates). DocuDesk already ships Twig and mPDF transitively; the gap was the absence of a shared, injectable service that other co-installed apps could call without re-implementing the wheel.

A second gap: there was no HTTP endpoint for on-demand PDF generation, so callers without PHP access (frontend, external services via the REST API) had no path to produce PDFs from templates at runtime.

## What Changes

- **NEW service `lib/Service/PdfService.php`** — the primary public API. Accepts a Twig template string + data array + options array, renders HTML via a sandboxed Twig environment, converts to PDF via mPDF, and returns the PDF binary. Stateless: callers supply template content directly; no template lookup or storage.
- **NEW service `lib/Service/TemplateRenderer.php`** — isolated Twig rendering layer extracted from `PdfService`. Configures a sandboxed Twig `Environment` with an `ArrayLoader`, an explicit `SecurityPolicy` whitelist (23 filters, 5 functions, 10 tags, zero allowed methods/properties), and `strict_variables: false`.
- **NEW controller `lib/Controller/PdfController.php`** — thin REST adapter. Exposes `POST /api/pdf/render` (authenticated). Reads `template`, `data`, `options`, `filename` from the JSON body; delegates to `PdfService`; returns a `DataDownloadResponse` with `application/pdf` MIME.
- **NEW route** in `appinfo/routes.php` — maps `pdf#render` to the controller.
- **NEW composer dependencies** — `mpdf/mpdf: ^8.2` and `twig/twig: ^3.18` declared in `composer.json`.

Not in scope (deliberate follow-ups):
- PDF/A conformance (ISO 19005) — mPDF supports it but requires font embedding and colour-profile configuration; tracked separately.
- Tagged PDF / WCAG 2.1 AA accessibility — deferred until an accessibility requirement surfaces.
- Persistent template library — callers continue to own and pass their templates; no DocuDesk-side template storage.
- Rate limiting or quota on the render endpoint.

## Capabilities

### New Capabilities

- `pdf-generation`: Stateless, injectable PDF rendering service for DocuDesk and co-installed apps. Twig template + data → sandboxed HTML render → mPDF conversion → PDF binary. Includes an authenticated HTTP endpoint (`POST /api/pdf/render`) for on-demand generation from external callers. Covers page configuration options (format, orientation, margins, title), mPDF temp-directory lifecycle, and a strict Twig sandbox security policy.

## Cross-app Dependencies

None. `PdfService` is self-contained. Other apps that declare DocuDesk as a Nextcloud app dependency can resolve it via the DI container without additional coupling.

## Impact

**Affected code (DocuDesk):**
- `lib/Service/PdfService.php` — new
- `lib/Service/TemplateRenderer.php` — new
- `lib/Controller/PdfController.php` — new
- `appinfo/routes.php` — new route entry
- `composer.json` — two new dependencies

**API contract:** `POST /index.php/apps/docudesk/api/pdf/render` (authenticated, JSON body). No existing endpoints change.

**Privacy / compliance:** No PII stored. Service is stateless. Rendered PDFs are returned in the HTTP response; callers decide where to persist them. The Twig sandbox blocks code execution paths that could leak server data.

**Migration:** None. The new composer dependencies are resolved by `composer install`; no DB changes, no repair steps.

**Architectural alignment:**
- ADR-001 (Data Layer): no domain data stored; no OpenRegister schemas; service is stateless.
- ADR-003 (Backend): strict Controller → Service layering; `PdfController` delegates entirely to `PdfService`; DI constructor injection throughout.
- ADR-002 (API): endpoint follows `/apps/{app}/api/{resource}` pattern; returns appropriate HTTP status codes.
- ADR-005 (Security): Twig sandbox prevents template injection; endpoint requires Nextcloud authentication; no PII in error responses.
- ADR-008 (Testing): PHPUnit tests for service and controller; Newman integration test for the render endpoint.
