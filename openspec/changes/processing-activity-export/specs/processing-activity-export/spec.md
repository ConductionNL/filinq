---
status: draft
---

# Processing Activity Export

## Purpose

Defines the GDPR/AVG Article 30 record of processing activities (verwerkingsregister) for DocuDesk's own processing: an organisation-level, per-activity-category aggregate over a reporting period, computed on demand from data already persisted in OpenRegister (NER `Entity`/`EntityRelation` rows, anonymisation/batch reports, OCR results, enrichment events, signing audit, `x-openregister-archival` annotations) and exportable as JSON, CSV, and PDF. Complements — and never duplicates — the per-document/per-dossier grondslagen summaries (`anonymisation-grondslagen-summary-rendering`): those answer "what happened to this document", this capability answers "what did DocuDesk process this period". The export is aggregate-only by structural contract: no detected entity value, document text, or file name can appear in any output. Admin-only in v1.

## ADDED Requirements

### Requirement: The aggregate MUST cover DocuDesk's four processing activity categories over a bounded period

`ProcessingActivityService` MUST compute, for a requested `[from, to]` range, one aggregate block per activity category — `anonymisation`, `ocr`, `metadata-enrichment`, `signing` — each containing: run count, distinct-document count, entity-type breakdown (counts per entity type from the OR NER pipeline rows in scope), legal-bases breakdown, the configured backend identifier (e.g. the anonymiser backend), and retention references. The range MUST NOT exceed app config `docudesk.processing_activity.max_range_days` (default 366); exceeding it yields HTTP 422. OR services MUST be resolved lazily; when OpenRegister is unavailable the endpoint MUST fail with a clear 503-style error, not a partial silent result.

#### Scenario: Aggregate over a quarter

- **GIVEN** anonymisation runs, OCR runs, and signing events exist within Q1
- **WHEN** the aggregate is requested for `from=2026-01-01&to=2026-03-31`
- **THEN** the response contains one block per activity category
- **AND** the `anonymisation` block's counts cover exactly the runs in the range

#### Scenario: Entity-type breakdown reflects NER rows

- **GIVEN** anonymisation runs in range whose `EntityRelation` rows cover 40 PERSON, 10 BSN, and 5 IBAN entities
- **WHEN** the aggregate is computed
- **THEN** the `anonymisation` block's entity-type breakdown reports those counts per type

#### Scenario: Over-long range is rejected

- **GIVEN** `max_range_days` is 366
- **WHEN** a range of 500 days is requested
- **THEN** the response is HTTP 422 naming the configured maximum

#### Scenario: OpenRegister unavailable fails loudly

- **GIVEN** OR services cannot be resolved
- **WHEN** the aggregate is requested
- **THEN** the response is an explicit error
- **AND** no partial aggregate is returned

### Requirement: The legal-bases breakdown MUST include an explicit no-grondslag-recorded bucket

The legal-bases breakdown aggregates `EntityRelation.bases[]` per base identifier (display names resolved from the `base` catalogue when `add-dossier-schema` is present). Relations whose `bases` is null or absent — historical runs, or OR's `entity-relation-grondslagen` not yet landed — MUST be counted under an explicit `no-grondslag-recorded` bucket. They MUST NOT be dropped from the totals.

#### Scenario: Mixed recorded and unrecorded bases

- **GIVEN** 30 in-range relations with `bases: ["avg-6-1-e"]` and 12 with `bases` null
- **WHEN** the breakdown is computed
- **THEN** it reports 30 under `avg-6-1-e` and 12 under `no-grondslag-recorded`
- **AND** the category totals include all 42

#### Scenario: Grondslagen dependency not landed

- **GIVEN** OR's `entity-relation-grondslagen` has not landed and no relation carries bases
- **WHEN** the breakdown is computed
- **THEN** all relations aggregate under `no-grondslag-recorded`
- **AND** the export renders that bucket visibly (no empty/hidden section)

### Requirement: Retention references MUST be read from schema archival annotations at request time

Each activity category's retention reference MUST be resolved from the `x-openregister-archival` annotations declared on the relevant schemas in `docudesk_register.json` at request time (e.g. signing → P10Y). A schema without an archival annotation MUST render as "not declared" rather than being omitted or guessed.

#### Scenario: Declared retention is reported

- **GIVEN** the signing-audit schema declares `x-openregister-archival.retention: P10Y`
- **WHEN** the aggregate is computed
- **THEN** the `signing` block's retention reference reports P10Y

#### Scenario: Missing annotation is surfaced as not declared

- **GIVEN** a schema in scope without an `x-openregister-archival` annotation
- **WHEN** the aggregate is computed
- **THEN** the corresponding retention reference is "not declared"

### Requirement: Exports MUST be available as JSON, CSV, and PDF with the controller identity header

`GET /api/processing-activities/export?from&to&format=json|csv|pdf` MUST serialise the same aggregate in three formats. Every format MUST carry a header with: controller (verwerkingsverantwoordelijke) name and contact, FG/DPO contact (all from admin settings), the reporting period, and the generation timestamp. CSV is one row per (activity category × entity type × base), UTF-8 with BOM. PDF is rendered from `lib/Resources/templates/processing/verwerkingsregister.twig` via the existing `PdfService` (NL-only v1; EN follows `register-i18n`) and includes the fixed declarations that processing is local and DocuDesk performs no third-country transfers. An unknown `format` yields HTTP 400.

#### Scenario: PDF export carries the Art. 30 header

- **GIVEN** admin settings with controller "Gemeente Voorbeeld" and FG contact "fg@voorbeeld.nl"
- **WHEN** a PDF export is requested
- **THEN** the document header shows the controller name, FG contact, period, and generation timestamp
- **AND** the fixed local-processing / no-transfer declarations are present

#### Scenario: CSV shape

- **GIVEN** an aggregate with two entity types under `anonymisation`
- **WHEN** a CSV export is requested
- **THEN** the file contains one row per (category × entity type × base) combination
- **AND** the file begins with a UTF-8 BOM

#### Scenario: Unknown format

- **WHEN** `format=docx` is requested
- **THEN** the response is HTTP 400

#### Scenario: Unconfigured controller identity prompts, not blocks

- **GIVEN** the controller-identity settings have never been filled
- **WHEN** an export is requested
- **THEN** the export succeeds with the identity fields rendered as "not configured"
- **AND** the admin UI shows a prompt to configure them

### Requirement: No output MUST contain literal personal data

The aggregate data model MUST be structurally incapable of carrying detected entity values, document text, or file names: its fields are counts, entity-type identifiers, base identifiers + catalogue display names, backend identifiers, schema/retention identifiers, and the configured controller-identity strings. This applies identically to the JSON, CSV, and PDF outputs and to application logs of export requests.

#### Scenario: Seeded entity value never appears in exports

- **GIVEN** an anonymisation run in range whose document contained the entity value "Pieter Jansen"
- **WHEN** each of the JSON, CSV, and PDF exports is generated
- **THEN** the byte stream of each export does not contain "Pieter Jansen"

#### Scenario: File names are excluded

- **GIVEN** in-range processing of a file named `bezwaar-jansen.pdf`
- **WHEN** any export is generated
- **THEN** no output contains the file name

### Requirement: Access MUST be admin-only in v1

Both endpoints (`GET /api/processing-activities`, `GET /api/processing-activities/export`) MUST require an admin user — neither declares `#[NoAdminRequired]`, relying on NC SecurityMiddleware's admin-only default — and MUST be registered in `appinfo/routes.php` with auth posture declared per gate-5.

#### Scenario: Non-admin is rejected

- **GIVEN** an authenticated non-admin user
- **WHEN** either endpoint is called
- **THEN** the response is an NC admin-required rejection (no aggregate data leaks)

#### Scenario: Admin succeeds

- **GIVEN** an admin user
- **WHEN** the aggregate endpoint is called with a valid range
- **THEN** the response is HTTP 200 with the aggregate

### Requirement: The admin UI MUST provide settings and an export surface

The DocuDesk admin settings MUST gain a compliance section with: the controller-identity fields (controller name, contact, FG/DPO contact) and an export form (date-range picker, format selector, download action). All strings use English i18n source keys with NL translations.

#### Scenario: Admin configures identity and exports

- **GIVEN** an admin on the DocuDesk compliance settings section
- **WHEN** they fill the controller-identity fields, pick a quarter and PDF, and trigger the export
- **THEN** a PDF downloads containing the configured identity in its header

#### Scenario: Export form validates the range

- **GIVEN** the admin selects a range exceeding the configured maximum
- **WHEN** they trigger the export
- **THEN** the UI surfaces the 422 reason without a silent failure
