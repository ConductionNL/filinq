## Context

Filinq already has a mature document-generation stack: `TemplateService` (CRUD by UUID + `namespace`), `DocumentRenderPipeline` (huisstijl load/apply, PDF options), `DocumentService::generateDocument()`/`generateBulk()` (Twig render -> mPDF, optional Files storage via `options.output.mode`), and `Pdfa3ConversionService` for archival-grade PDF/A-3 output. None of that is duplicated here (ADR-011). What's missing is purely the external-caller surface: learniq's `ReportCardPdfDelegationService` already POSTs a fixed `templateSlug` to a contract that does not exist yet, and no template can be looked up by a human-readable slug — only by UUID.

See proposal.md - Why / What Changes for motivation and scope. See specs/report-render-api/spec.md and specs/template-management/spec.md for the requirements.

## Goals / Non-Goals

**Goals:**
- Let a template be addressed by a stable slug instead of a UUID, with per-tenant override and namespace-wide fallback.
- Give external callers (learniq today, any future consumer) one small, stable REST contract that accepts inline data and returns a document reference, without them needing to know filinq's register/schema model.
- Reuse every existing rendering, huisstijl, storage, and PDF/A-3 primitive as-is.

**Non-Goals:**
- No new rendering engine, no new PDF library, no new huisstijl model.
- No change to `ReportCardPdfDelegationService` itself (learniq-side; out of scope for this filinq change).
- No UI for managing slugs or tenants in this change — CRUD stays API/DI-only, matching template-management's existing "no dedicated UI" status.
- No multi-tenant data isolation model beyond the `tenantId` string match already implied by the corpus row (real per-tenant RBAC, if ever needed beyond this lookup convenience, is a separate change).

## Decisions

**Slug + tenantId as plain string fields on the existing `template` schema, not a new schema.** A separate "template alias" schema would need its own CRUD, its own register wiring, and its own RBAC — for two optional lookup fields. Adding `slug` (string, optional) and `tenantId` (string, optional) to the existing `template` schema is the smaller change and keeps one source of truth. Alternative considered: a join table mapping `(namespace, tenantId, slug) -> templateId`; rejected as premature — nothing today needs a slug to point at more than one template concurrently.

**Resolution order is tenant-specific-first, namespace-wide-fallback, implemented as two `searchObjectsPaginated` calls (or one query with tenant-priority sort) in `TemplateService`, not a new resolver class.** This mirrors how `DocumentRenderPipeline::loadHuisstijl()` already does simple ID-based OpenRegister lookups — no new architectural pattern.

**The render endpoint is a thin new controller (`ReportRenderController`) + service (`ReportRenderService`) that composes `TemplateService` (slug resolution) with the existing `DocumentService`/`DocumentRenderPipeline` render path, rather than extending `DocumentController::generate()`.** `generate()`'s contract (`templateId` + `dataRefs: [{register,schema,id}]`) is a different shape from what external callers like learniq have (they already hold the data, not a register/schema/uuid pointer to it). Bolting an inline-data branch onto `generate()` would make one endpoint serve two incompatible request shapes; a separate small endpoint is clearer to document and to gate. It still calls into `DocumentRenderPipeline` and `DocumentService`'s internal render helpers so there is exactly one render code path.

**Authentication is a static shared-secret bearer token, checked in the controller against an admin-configured `IAppConfig` value (`filinq.report_render_api_token`), not an interactive Nextcloud session.** `ReportCardPdfDelegationService` already sends `Authorization: Bearer <learniq.docudesk_api_token>` with no NC login involved — this is a server-to-server call, so the endpoint is `#[PublicPage]` + `#[NoCSRFRequired]` with `hash_equals()` token comparison (401 on missing/mismatched token), the same shape `PortalSigningReceiverController` uses for its own server-to-server contract, simplified from HS256-signed assertions to a static shared secret because there is exactly one caller identity here (whichever app fleet-wide is configured with the token), not a per-signer claim to verify. Storage therefore also cannot come from `IUserSession::getUser()` (there is no session): the request carries an explicit `userId` (whose Files area receives the stored output), falling back to an admin-configured `filinq.report_render_storage_user` when omitted, and 400s if neither is present — mirroring `DocumentController::generate()`'s existing `options.userId` requirement for `output.mode=files`, just sourced from the request instead of the session.

**`documentRef` is the stored file's identifier from the existing `output.mode = 'files'` storage path** (the same one `DocumentController::generate()` already uses to return `X-Docudesk-File-Id`), not a new document-registry concept. This keeps "what a documentRef is" consistent fleet-wide.

**Print quality is a boolean fork to the existing `Pdfa3ConversionService`, not a new PDF backend.** `outputQuality: 'print'` runs the already-rendered PDF through that service before storage; `screen` (default) skips it. Alternatives considered: a higher-DPI mPDF config — rejected, PDF/A-3 already exists specifically for "this must survive professional printing/archiving" use cases and is exercised elsewhere in this repo (`pdfa3-conversion`, `verapdf-validation` specs).

**Batch/ZIP reuses the single-item render path in a loop, with `ZipArchive` (PHP core extension, already used elsewhere in this repo for docx packaging), not a new job/queue system.** `DocumentService::generateBulk()`'s async-job pattern (`BackgroundJob` + polling `GET /jobs/{jobId}`) is heavier than what a "download this class's reports" caller needs; a synchronous request that renders N small report cards and streams back one ZIP is simpler and matches Easyrapport's documented UX (one ZIP per class per period, not a background job the teacher must poll). If report-card classes grow large enough that synchronous rendering times out, a follow-up can add an async path — flagged in Open Questions, not solved here.

**Partial-failure handling in the batch endpoint returns per-item errors alongside the ZIP** (spec REQ-RRA-03, "one item's render failure does not abort the batch") rather than failing the whole request, because a single malformed learner record should not block twenty-nine other reports from being generated.

## Declarative-vs-imperative decision (ADR-031)

None of this change's behaviour is a lifecycle, aggregation, derived field, notification, declarative relation, or dashboard widget — it is document rendering and storage triggered synchronously by an inbound HTTP request, which is the "external-system bridge" exception ADR-031 already names (and which `ReportCardPdfDelegationService`'s own docblock cites for the learniq side of this same integration). All new behaviour is imperative PHP (a controller + a service), matching the existing `DocumentController`/`DocumentService` pattern it extends.

## Seed Data (ADR-001)

Two representative `template` objects for local/demo seeding, added to `_registers.json` under the existing `template` schema:

```json
{
  "name": "Report Card - Gemeente Voorbeeld Basisschool",
  "namespace": "learniq",
  "tenantId": "school-demo-basis",
  "slug": "report-card",
  "format": "A4",
  "orientation": "P",
  "content": "<h1>{{ leerling.naam }}</h1><p>{{ mentorComment }}</p>{% for grade in subjectGrades %}<div>{{ grade.subject }}: {{ grade.score }}</div>{% endfor %}"
},
{
  "name": "Report Card - Default",
  "namespace": "learniq",
  "tenantId": null,
  "slug": "report-card",
  "format": "A4",
  "orientation": "P",
  "content": "<h1>{{ leerling.naam }}</h1>{% for grade in subjectGrades %}<div>{{ grade.subject }}: {{ grade.score }}</div>{% endfor %}"
}
```

The first models a school (Gemeente Voorbeeld Basisschool, a fictitious municipality-run primary school used elsewhere in this repo's seed data conventions) with its own huisstijl-backed template; the second is the namespace-wide fallback exercised when a school has not configured its own.

## Risks / Trade-offs

- [Risk] A caller reuses the same `(namespace, tenantId, slug)` by accident and gets a 400 on create → Mitigation: the 400 names the conflicting slug so the fix (pick a different slug, or update the existing template) is immediate; duplication is a create-time check, not a runtime surprise.
- [Risk] Synchronous batch rendering of a very large class (60+ reports) could be slow or time out → Mitigation: flagged as an Open Question below rather than silently building an async path nobody asked for yet; the corpus evidence names "per class" batches, which are small (tens, not hundreds).
- [Risk] `tenantId` as a free-text string has no referential integrity to an actual tenant/school register → Mitigation: matches the existing `huisstijlId` and `namespace` conventions (plain OpenRegister object references / strings), not a new integrity model; out of scope per Non-Goals.

## Migration Plan

Additive only: new optional schema fields (`slug`, `tenantId` default null on `template`), two new routes, no changes to existing endpoints' request/response shapes. No data migration needed — existing templates without a `slug` remain resolvable by UUID exactly as before. Rollback is a plain revert (no destructive schema change to undo).

## Open Questions

- Should batch rendering move to the existing async job pattern (`BackgroundJob` + `GET /jobs/{jobId}`) once a real class size is observed to be slow? Deferred until there's a measured timeout, per Non-Goals.
