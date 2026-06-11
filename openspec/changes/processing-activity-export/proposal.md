## Why

DocuDesk performs systematic, large-scale processing of personal data: NER extraction, anonymisation, OCR, and metadata enrichment over documents full of PII. Under GDPR/AVG Article 30, the controller must maintain a record of processing activities (verwerkingsregister) covering purposes, categories of data subjects and personal data, recipients, retention, and technical/organisational measures. Today a Functionaris Gegevensbescherming (FG/DPO) using DocuDesk has to assemble that record by hand: the grondslagen cluster (`anonymisation-bases-passthrough`, `anonymisation-grondslagen-summary-rendering`) records legal bases per entity and renders per-document/per-dossier summaries, but there is no aggregate, exportable, organisation-level view of what DocuDesk processed in a period. Every best-in-class GDPR-compliance suite ships this; for an app whose pitch is "GDPR-compliant document processing", its absence is the most conspicuous compliance gap left after the grondslagen work.

All the raw material already exists in OpenRegister — `Entity`/`EntityRelation` rows (entity types = categories of personal data, `bases[]` = legal bases), anonymisation reports, dossiers, consents, signing audit, and `x-openregister-archival` retention annotations. This change aggregates it; it stores nothing new about documents and never exports literal PII.

## What Changes

- **NEW capability:** `processing-activity-export`. The aggregate Art. 30 model, the export endpoint (JSON/CSV/PDF), the controller-identity admin settings, and the no-literal-PII guarantee.
- **NEW service `lib/Service/ProcessingActivityService.php`:** computes the aggregate per activity category (anonymisation, OCR, metadata enrichment, signing) over a date range from existing OR data — counts, entity-type breakdown, legal-bases breakdown, NER backend used, retention references from schema archival annotations.
- **NEW controller `lib/Controller/ProcessingActivityController.php`** + routes: `GET /api/processing-activities` (aggregate JSON) and `GET /api/processing-activities/export?from&to&format=json|csv|pdf`. Admin-only in v1.
- **NEW Twig template** `lib/Resources/templates/processing/verwerkingsregister.twig` rendered via the existing `PdfService` (same pattern as `GrondslagenSummaryService`). NL-only v1; EN follows `register-i18n`.
- **NEW admin settings:** controller-identity fields (verwerkingsverantwoordelijke name, contact, FG contact) embedded in the export header — Art. 30(1)(a).
- **NO new register schemas for activities** — the record is computed on demand from data already persisted; only the controller-identity settings are stored (app config).

### Out of scope

- Per-document / per-dossier grondslagen summaries — `anonymisation-grondslagen-summary-rendering` owns those; this change aggregates across them.
- Data Protection Impact Assessments (DPIA), breach registers, or consent-of-data-subject management — different Art. obligations, different changes if ever wanted.
- Processing performed by *other* apps on the instance — scope is DocuDesk's own processing activities.
- A compliance-officer (non-admin) role — v1 is admin-gated; delegation can follow via NC `IDelegatedSettings` when a concrete need appears.
- Back-filling legal bases onto historical `EntityRelation` rows — pre-grondslagen runs aggregate under "no grondslag recorded" (same precedent as the grondslagen summary).

## Capabilities

### New Capabilities

- `processing-activity-export`

## Cross-app Dependencies

- **Soft** — `openregister:entity-relation-grondslagen` — provides `EntityRelation.bases` for the legal-bases breakdown; absent, the breakdown shows "no grondslag recorded".
- **Soft** — `docudesk:add-dossier-schema` — dossier metadata enriches the purposes column when present.
- **Soft** — `docudesk:register-i18n` — EN template rendering follows it; NL-only v1.

## Impact

- **Code (docudesk):** `lib/Service/ProcessingActivityService.php` (NEW), `lib/Controller/ProcessingActivityController.php` (NEW), `lib/Resources/templates/processing/verwerkingsregister.twig` (NEW), admin settings section, `appinfo/routes.php`.
- **API contract:** two new admin-only endpoints. No changes to existing endpoints.
- **Privacy/compliance:** delivers the Art. 30 record; the export is aggregate-only by contract — counts, entity-type categories, bases, backend identifiers — and MUST NOT contain detected entity values, document text, or document names.
- **Performance:** aggregation queries run over OR mappers per request; ranges are bounded (max range app-configurable) to keep request budgets sane.
- **Migration:** none.
