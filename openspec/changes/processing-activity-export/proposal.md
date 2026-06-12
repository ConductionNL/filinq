## Why

> **Reworked 2026-06-11 (abstraction decision):** the Art. 30 verwerkingsregister surfaced near-identically in docudesk, procest, and scholiq on the same day — proof the requirement is abstract. The aggregate/export engine, JSON/CSV/PDF generation, no-literal-PII contract, controller-identity record, and access gating are now owned by OpenRegister (`openregister/openspec/changes/processing-activity-register`, OR-PA-1..9; docudesk D1–D7 are mapped in its design.md supersession table). This change is docudesk's THIN CONSUMER: it contributes the document-processing activity catalogue, category/grondslag mapping, retention references, and the admin UI surfacing — nothing more.
>
> **Depends on:** `openregister/processing-activity-register` — BLOCKED_EXTERNAL until that change lands.

DocuDesk performs systematic, large-scale processing of personal data (NER extraction, anonymisation, OCR, metadata enrichment, signing). Under AVG Art. 30 the controller must maintain a record of processing activities. All the raw material already exists in OpenRegister (`Entity`/`EntityRelation` rows, anonymisation/batch reports, signing audit, `x-openregister-archival` annotations); what is docudesk-specific is the *domain knowledge*: which activity categories docudesk's processing falls into, where its grondslagen live, and which retention annotations apply.

## What Changes

- **Activity catalogue (consumes OR-PA-2):** docudesk's four processing activities — `anonymisation`, `ocr`, `metadata-enrichment`, `signing` — declared as `x-openregister-processing` annotations in `docudesk_register.json`, each carrying purpose, legal-basis source, data-subject/data categories (entity types), backend identifier (e.g. the configured anonymiser), and retention references read from the existing `x-openregister-archival` annotations.
- **Grondslag mapping (feeds OR-PA-4):** the legal-bases breakdown sources from `EntityRelation.bases[]`; relations without bases land in OR's explicit `no-grondslag-recorded` / unclassified bucket — never dropped from totals.
- **Admin UI surfacing (consumes OR-PA-1/7/8):** a compliance section in docudesk admin settings that deep-links to OR's Art. 30 export scoped to docudesk's registers (period + format selection rendered by OR), and surfaces the controller-identity record maintained in OR (OR-PA-1) with a configure prompt when unset.

## Superseded by OpenRegister (removed from this change)

`ProcessingActivityService` aggregation engine, `ProcessingActivityController` + export endpoints, the verwerkingsregister Twig/PDF template, CSV/JSON serialisation, range guards, the no-literal-PII output contract, controller-identity storage, and admin-only gating — see OR-PA-4/7/8 and the supersession table (docudesk D1–D7).

### Out of scope

- Per-document / per-dossier grondslagen summaries — `anonymisation-grondslagen-summary-rendering` owns those.
- DPIA, breach registers, consent management.
- Processing performed by other apps — each contributes its own catalogue to OR.

## Capabilities

### Modified Capabilities

- `processing-activity-export` (thinned to catalogue + UI surfacing)

## Cross-app Dependencies

- **Hard** — `openregister:processing-activity-register` (OR-PA-1..9) — BLOCKED_EXTERNAL.
- **Soft** — `openregister:entity-relation-grondslagen` — provides `EntityRelation.bases`; absent, everything buckets under no-grondslag-recorded.
- **Soft** — `docudesk:register-i18n` — EN rendering of catalogue strings.

## Impact

- **Code (docudesk):** catalogue annotations in `lib/Settings/docudesk_register.json`; one admin-settings compliance section (Vue). No new services, controllers, routes, or templates.
- **Privacy/compliance:** Art. 30 delivery via the platform capability; docudesk's slice scoped per OR-PA-8.
- **Migration:** none.
