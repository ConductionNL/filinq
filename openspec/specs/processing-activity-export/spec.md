---
status: done
---

# processing-activity-export Specification

## Purpose
DocuDesk's contribution to the platform AVG Art. 30 verwerkingsregister owned by
OpenRegister (`openregister/processing-activity-register`, OR-PA-1..9): the
document-processing activity catalogue (anonymisation, OCR, metadata-enrichment,
signing) declared as `x-openregister-processing` annotations, the grondslag/
retention mapping, the per-access read-logging opt-in, and the admin UI window
onto the platform register. The aggregate export engine, output formats, the
no-literal-PII contract, and access gating are OpenRegister requirements and are
deliberately NOT restated here (ADR-022).

## Requirements
### Requirement: Docudesk MUST declare its processing activities as catalogue annotations

Docudesk SHALL declare its four processing activities — `anonymisation`, `ocr`, `metadata-enrichment`, `signing` — as `x-openregister-processing` annotations in its register configuration (OR-PA-2), each carrying purpose, data-subject/data categories expressed as OR NER entity types, the configured backend identifier, and retention references taken from the schemas' existing `x-openregister-archival` annotations ("not declared" when absent). Docudesk SHALL NOT implement an aggregation service, export controller, or register template of its own.

#### Scenario: Register import seeds the four activities as drafts

<!-- @e2e exclude verified by ProcessingActivityCatalogueTest + OR-side import; OR's full catalogue seeder (OR-PA-2.2) is deferred so this is backend, not UI -->

- **GIVEN** docudesk's register configuration is imported into OpenRegister
- **WHEN** the import completes
- **THEN** the four activities MUST exist in the OR processing-activity register as drafts with their categories, backend identifier, and retention references

#### Scenario: Missing archival annotation stays visible

<!-- @e2e exclude catalogue-content contract verified by ProcessingActivityCatalogueTest::testRetentionReferencesMirrorArchivalOrStayVisible; rendering is OR's -->

- **GIVEN** a schema in scope without an `x-openregister-archival` annotation
- **WHEN** the catalogue entry is rendered in OR's register or export
- **THEN** its retention reference MUST read "not declared" rather than being omitted

#### Scenario: No docudesk export engine exists

<!-- @e2e exclude negative backend guard verified by ProcessingActivityCatalogueTest::testNoDocudeskProcessingExportEndpointExists; no UI surface to drive -->

- **WHEN** docudesk's route table is inspected
- **THEN** no docudesk endpoint MUST exist that aggregates or exports processing activities — the export is OR-PA-7's

### Requirement: Grondslag gaps MUST surface through the platform's unclassified bucket

The legal-bases dimension of docudesk's processing record sources from `EntityRelation.bases[]`; relations whose `bases` is null or absent (historical runs, or OR's `entity-relation-grondslagen` not yet landed) MUST appear in OR's explicit unclassified / no-grondslag-recorded bucket (OR-PA-4) and MUST NOT be dropped from totals.

#### Scenario: Mixed recorded and unrecorded bases

<!-- @e2e exclude OR-side aggregation (OR-PA-4); asserted in OpenRegister's suite, not duplicated here; no docudesk UI -->

- **GIVEN** 30 in-range relations with `bases: ["avg-6-1-e"]` and 12 with `bases` null
- **WHEN** OR's export for docudesk's slice is generated
- **THEN** it MUST report 30 under `avg-6-1-e` and 12 under the unclassified bucket, totalling 42

### Requirement: The admin UI MUST surface the platform register scoped to docudesk

Docudesk's admin settings SHALL gain a compliance section that (a) shows the OR-maintained controller-identity record (OR-PA-1) with a configure prompt when unset, and (b) provides the Art. 30 export entry point delegating to OR-PA-7 scoped to docudesk's registers. Access follows OR-PA-8; strings use English i18n source keys.

#### Scenario: Admin exports the register from docudesk

- **GIVEN** an admin on the docudesk compliance settings section
- **WHEN** they trigger the Art. 30 export for a quarter
- **THEN** the export MUST be produced by OpenRegister scoped to docudesk's registers, carrying the OR controller-identity header

#### Scenario: Unconfigured identity prompts, not blocks

- **GIVEN** the OR controller-identity record has never been filled
- **WHEN** the admin opens the compliance section
- **THEN** a configure prompt MUST be shown and the export MUST still succeed with identity fields rendered as "not configured" (OR-PA-7 behaviour)

#### Scenario: Non-admin is rejected

<!-- @e2e exclude access gating is OR-PA-8 (ProcessingLogController fails closed for non-admin/non-FG); asserted in OpenRegister's ProcessingLogControllerTest, not a docudesk UI surface -->

- **GIVEN** an authenticated non-admin user without FG delegation
- **WHEN** they attempt the compliance section's delegated endpoints
- **THEN** access MUST be denied per OR-PA-8

