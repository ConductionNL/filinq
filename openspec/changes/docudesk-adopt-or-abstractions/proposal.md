# docudesk: adopt OpenRegister abstractions

## Why

The OR-abstraction audit (2026-05-03) flagged docudesk as a Tier 2 backend-heavy app that
duplicates several abstractions that now live in OpenRegister and shared platform packages:

- Status-string state machines hand-rolled in `BatchCorrespondenceJob`, `BatchStateService`,
  and the signing providers — five literal `'status' => '…'` writes in a single job, one
  classic state machine encoded in inline strings. Should be a `x-openregister-lifecycle`
  annotation.
- Three custom schemas (report, template, entity) declared in
  `openspec/specs/document-register/spec.md` with `properties: []` and ad-hoc fields. Should
  be canonical OR schemas with `x-openregister-archival` (Archiefwet 1995, 10-year retention)
  and validated via OR's schema validator.
- Anonymization and batch-anonymization specs describe custom file ingest, entity
  extraction, and per-file status tracking. OR now ships `TextExtractionService`, file
  attachments, and background-job state — these specs should consume those instead of
  re-implementing.
- A dozen hardcoded magic numbers (OCR languages, DPI, lock timeout, cache TTL, max files,
  batch limit, max depth, version floor) live as `const` values in service classes; they
  belong in admin-config so tenants can tune without code changes.
- No app manifest declaring tier or dependency on `openregister`. Hydra coordination cannot
  pin docudesk on the right OR baseline without it.

This change adopts the OR-side specs (`register-resolver-service`,
`pluggable-integration-registry`, `i18n-source-of-truth`, `i18n-api-language-negotiation`),
the nc-vue `multi-tenancy-context` spec, and the Hydra `adopt-app-manifest` change. It also
migrates docudesk's three core schemas onto OR annotations
(`x-openregister-lifecycle`, `x-openregister-archival`, `x-openregister-calculations`).

The audit references this proposal must respect:

- `.claude/audit-2026-05-03/01-code-cleanup.md` (stream 1: ObjectService de-duplication)
- `.claude/audit-2026-05-03/02-spec-rewrite.md` (stream 2: spec rewrites)
- `.claude/audit-2026-05-03/04-hardcoded.md` (stream 4: magic-number cleanup)
- `hydra/openspec/architecture/ADR-022.md` (lifecycle annotation)
- `hydra/openspec/architecture/ADR-024.md` (archival annotation)
- `hydra/openspec/architecture/ADR-025.md` (notification annotation)

## What Changes

### Spec rewrites

1. `openspec/specs/document-register/spec.md` — rewrite the three schemas (report, template,
   entity) as full JSON-schema objects with `x-openregister-archival` annotations declaring
   Archiefwet retention. Drop the ad-hoc `properties: []` shape; require schema validation
   through OR's validator.
2. `openspec/specs/anonymization/spec.md` — replace the custom file-upload + entity-extraction
   pipeline with consumption of OR File Attachments and `TextExtractionService`. Anonymization
   becomes a `x-openregister-calculations` annotation on the report schema.
3. `openspec/specs/batch-anonymization/spec.md` — replace `ICache`-backed per-file status
   tracking with OR Background Jobs + `x-openregister-lifecycle` annotation. Per-file state is
   recorded as a child object with its own lifecycle, not in a cache key.
4. `openspec/specs/metadata-enrichment/spec.md` (P2) — declare enrichment as a
   `x-openregister-calculations` annotation rather than a custom service.

### Code-side migrations (deferred to apply phase, NOT done in this change)

This change is spec-only. It captures the migration as task lines so the apply phase has
file-level hints. The 17 hardcoded paths from `04-hardcoded.md` are listed verbatim in
`tasks.md` Phase 7.

### Manifest + multi-tenancy + i18n adoption

- Add `openspec/manifest.yaml` declaring tier 2, dependency on `openregister`, and the
  shared specs consumed.
- Adopt nc-vue `multi-tenancy-context` for the document-register frontend (consume; do not
  re-implement).
- Adopt OR `i18n-source-of-truth` and `i18n-api-language-negotiation` for all
  user-facing content fields on the report/template/entity schemas.

## Impact

- Affected specs: `document-register`, `anonymization`, `batch-anonymization`,
  `metadata-enrichment` (rewrites); new `docudesk-or-adoption` spec captures the adoption
  contract itself.
- Affected code (apply-phase hints, NOT changed here): `lib/Service/OcrService.php`,
  `lib/Service/TemplateService.php`, `lib/Service/BatchStateService.php`,
  `lib/Service/CorrespondenceService.php`, `lib/Service/DataResolverService.php`,
  `lib/Service/SettingsService.php`, `lib/Service/Signing/NativeSigningProvider.php`,
  `lib/Service/Signing/ValidSignProvider.php`, `lib/BackgroundJob/BatchCorrespondenceJob.php`,
  `lib/Service/FileEntityStatsService.php`, `lib/Service/BatchUploadService.php`,
  `lib/Service/SigningAuditService.php`.
- Breaking changes: schema migration for report/template/entity — existing rows must be
  re-validated against the new strict JSON-schema. Migration script needed in apply phase.
- Dependencies: openregister must ship `register-resolver-service`,
  `pluggable-integration-registry`, `i18n-source-of-truth`, `i18n-api-language-negotiation`
  before the apply phase. nc-vue must ship `multi-tenancy-context`. Hydra must ship
  `adopt-app-manifest`.
