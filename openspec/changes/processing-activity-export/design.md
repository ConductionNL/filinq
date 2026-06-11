## Context

GDPR Art. 30 requires the controller to maintain a record of processing activities: name/contact of controller and DPO, purposes, categories of data subjects and personal data, categories of recipients, transfers, retention periods, and a general description of security measures. DocuDesk's processing footprint is fully observable from existing persisted data:

- **Categories of personal data** → entity types on `Entity`/`EntityRelation` rows (PERSON, BSN, IBAN, EMAIL, …) keyed by file ID in the OR NER pipeline.
- **Legal bases** → `EntityRelation.bases[]` once `entity-relation-grondslagen` (OR) lands; the DocuDesk-side `base` schema (via `add-dossier-schema`) carries the catalogue.
- **Activities + volumes** → anonymisation reports/results, batch reports, OCR runs, enrichment events, signing audit entries.
- **Retention** → `x-openregister-archival` annotations already declared on DocuDesk schemas (P10Y signing, P7Y correspondence, etc. per `docudesk-adopt-or-abstractions` task 5).
- **Processors/measures** → the configured anonymiser backend (Presidio/OpenAnonymiser, local processing — "no cloud") and OR storage.

The grondslagen cluster renders per-document and per-dossier views. The FG needs the orthogonal view: organisation-level, per-activity-category, over a reporting period, exportable.

## Goals / Non-Goals

**Goals:**

- Aggregate Art. 30 view per activity category (anonymisation, OCR, metadata enrichment, signing) over `[from, to]`: run counts, document counts, entity-type breakdown, legal-bases breakdown, backend identifier, retention references.
- Export as JSON (machine), CSV (spreadsheet), PDF (formal register document, Twig + `PdfService`).
- Controller-identity admin settings embedded in the export header.
- Hard no-literal-PII contract on every output format.
- Admin-only access in v1.

**Non-Goals:**

- New persistent schema for activities (computed on demand).
- DPIA / breach register / data-subject request handling.
- Cross-app instance-wide processing inventory.
- Scheduled/periodic export generation (operators export on demand; cron can follow if asked).

## Decisions

### D1. Computed on demand — no activity register schema

An Art. 30 record derived from live data cannot drift from reality; a persisted copy can. The service aggregates per request from OR mappers/object queries. To bound cost: the range may not exceed `docudesk.processing_activity.max_range_days` (default 366) → 422 beyond; counting queries use mapper-level aggregation, never hydrating full objects. If deployments later need point-in-time snapshots ("the register as filed in 2026"), the exported PDF/CSV itself — stored by the operator in NC Files — is the snapshot, which is exactly how paper-era verwerkingsregisters work.

### D2. Aggregate-only output is a hard contract, not a rendering choice

Every output format is constrained at the data-model level: the service's aggregate DTO contains only counts, category identifiers (entity types), base identifiers + display names from the `base` catalogue, backend identifiers, schema/retention identifiers, and the configured controller-identity strings. There is no field in the DTO that could carry an entity value, document name, or document text — making "no literal PII in the export" structurally true rather than filter-enforced. File names were deliberately excluded too: government file names routinely embed citizen names (`bezwaar-jansen.pdf`).

### D3. Activity categories are fixed in v1

`anonymisation`, `ocr`, `metadata-enrichment`, `signing` — the four processing operations DocuDesk actually performs on personal data. Each maps to a documented source (reports, OCR results, enrichment events, signing audit). A registry of pluggable categories was rejected: Art. 30 rows change when the app gains capabilities, which is a spec change anyway.

### D4. Legal-bases breakdown degrades to "no grondslag recorded"

Mirrors `anonymisation-grondslagen-summary-rendering`: rows whose `EntityRelation.bases` is null/absent (historical runs, OR grondslagen change not yet landed) aggregate under an explicit `no-grondslag-recorded` bucket rather than being dropped — the FG must see the gap, not a prettier lie.

### D5. PDF via Twig + PdfService, NL-only v1

Same rendering stack and language posture as the grondslagen summaries: `lib/Resources/templates/processing/verwerkingsregister.twig`, rendered by the existing `PdfService`; EN template follows `register-i18n`. Reuse before reinvention (ADR-011).

### D6. Admin-gated in v1

Both endpoints require admin (no `#[NoAdminRequired]`; NC SecurityMiddleware's admin-only default posture). The Art. 30 export is organisation-level compliance material, not operator tooling. Delegation to a compliance-officer group via `IDelegatedSettings`/group checks is a deliberate follow-up once a deployment asks — building role plumbing speculatively contradicts the fleet's RBAC-from-OR direction.

## Risks / Trade-offs

- **Aggregation cost on large registers** → bounded range (D1), mapper-level counting; if still hot, an apply-phase index review on the relevant OR tables (coordinate with OR, don't fork).
- **Incomplete picture pre-grondslagen** → explicit `no-grondslag-recorded` bucket (D4) keeps the export honest.
- **Retention references depend on schema annotations being current** → the export reads annotations from `docudesk_register.json` at request time; a missing annotation renders as "not declared", which is itself a useful compliance finding.
- **Legal sufficiency** — Art. 30 has fields DocuDesk cannot know (e.g. transfers to third countries: none, local processing). The template states these as fixed declarations ("processing is local; no third-country transfer by DocuDesk") reviewed with the proposal; the FG remains owner of the final register.

## Migration Plan

1. Land `ProcessingActivityService` aggregate DTO + unit tests.
2. Land controller + routes + admin settings (controller identity).
3. Land CSV serialisation + Twig/PDF template.
4. Land admin UI entry (settings section + export form) + e2e.

No data migration. Historical data aggregates with the `no-grondslag-recorded` degradation.
