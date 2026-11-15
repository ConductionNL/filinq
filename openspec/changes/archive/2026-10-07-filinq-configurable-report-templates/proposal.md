---
kind: code
---

## Why

`ReportCardPdfDelegationService` in learniq (`lib/Service/ReportCardPdfDelegationService.php`) already POSTs a report card's `subjectGrades`, `mentorComment`, `attendanceSummary` and a `templateSlug` to a proposed, not-yet-verified filinq contract (`POST /api/v1/documents/render`) — no such endpoint exists in filinq today (confirmed: `grep -rn "documents/render" appinfo/routes.php` returns nothing). The slug is hard-coded to one fixed value (`report-card`), so every school gets the same layout even though filinq already lets a school configure its own huisstijl (logo, colours, header/footer) for letters and formal documents via `DocumentRenderPipeline::loadHuisstijl()`. `change-plan.md` (learniq round 1, row `filinq-configurable-report-templates`) and `M3-integrations.md` row I13 both name this the gap: report-card templates should be configurable per school, and the render call should be a real, callable endpoint, not a proposed one. Two competitor rows sharpen the shape: Easyrapport ships "elk rapport... in de huisstijl en visie van uw school" (R-new-2, house-style templates) and "geschikt voor... professioneel drukwerk" (R-new-12, print-shop quality PDF), and Easyrapport also lets teachers "elke periode een ZIP-bestand van hun klas downloaden" (R-new-10, bulk ZIP per class).

## What Changes

- Add a `slug` field to the `template` schema (unique per `namespace`, optionally scoped by a new `tenantId` field) so a template can be resolved by a stable human-readable identifier instead of only by UUID. Falls back from a tenant-specific slug to a namespace-wide default when no tenant override exists.
- Add `POST /api/v1/documents/render`: a stable external-caller contract that resolves a template by `templateSlug` (+ optional `tenantId`), accepts an ad-hoc JSON data payload directly (no `dataRefs`/register lookup required — the caller, e.g. learniq, already has the data), renders it through the existing `DocumentRenderPipeline`/huisstijl pipeline, stores the output, and returns `{documentRef, templateSlug, renderedAt}` — matching the shape `ReportCardPdfDelegationService::callDocudeskRender()` already expects (`documentRef` on success).
- Add an `outputQuality` render option (`screen` default, `print`) that, when `print`, routes the rendered PDF through the existing `Pdfa3ConversionService` for archival/print-shop-grade output instead of building a new PDF pipeline.
- Add `POST /api/v1/documents/render/batch`: renders a list of payloads against the same `templateSlug`, zips the resulting PDFs with `ZipArchive`, stores the ZIP, and returns `{documentRef, count}` — reusing the same per-item render path as the single-document endpoint rather than a second rendering pipeline.
- Tests: unit coverage for slug resolution (tenant override + namespace fallback + not-found), the render endpoint (happy path, missing slug, malformed payload), the batch/ZIP endpoint, and the `outputQuality: print` branch.

## Capabilities

### New Capabilities
- `report-render-api`: the external, slug-addressed render contract (`/api/v1/documents/render` and `/render/batch`) that other Conduction apps call to turn ad-hoc data into a stored, school-styled PDF without needing to know filinq's internal register/schema model.

### Modified Capabilities
- `template-management`: adds `slug` (+ optional `tenantId`) to the template data model and a slug-resolution lookup path (REQ-TMPL-13), alongside the existing UUID/namespace lookup.

## Impact

- `lib/Settings/filinq_register.json`: `template` schema gains `slug` and `tenantId` properties.
- `lib/Service/TemplateService.php` (or a small new resolver): slug + tenant-fallback lookup.
- New `lib/Controller/ReportRenderController.php` (or an addition to `DocumentController`), a new `ReportRenderService` composing `TemplateService` slug lookup + existing `DocumentRenderPipeline`/`DocumentService` render/store path, and a small ZIP-batch helper.
- `appinfo/routes.php`: two new routes.
- No changes to `Pdfa3ConversionService`, `DocumentRenderPipeline`, huisstijl loading, or the Twig sandbox — all reused as-is per ADR-011.
- learniq's `ReportCardPdfDelegationService` is unaffected by this change (it already POSTs the exact contract this proposal implements); wiring it to stop treating the endpoint as "proposed, not-yet-verified" is a learniq-side follow-up, out of scope here.
