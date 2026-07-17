# document-register Specification (delta)

---
status: proposed
---

## Purpose delta

The document register's record schemas participate in Archiefwet retention
via OpenRegister's records-management stack: schema-level `archive`
configuration replaces the auto-delete annotation on record classes, while
operational-log schemas keep annotation-driven cleanup in the shape OR HEAD
validates.

## ADDED Requirements

### Requirement: Document-register record schemas carry Archiefwet archive configuration (REQ-DDARE-020)

The document register MUST declare, on its record schemas
`correspondence`, `generatedDocument` and `publicationRecord`, an `archive`
configuration block
(`enabled: true`, a placeholder selectielijst `classificatie` pending
selectielijst-manager confirmation, and an `afleidingswijze` with its
trigger field: `closureField: generatedAt` for correspondence and
generatedDocument, `bronEigenschap: publicatiedatum` for
publicationRecord) so that OpenRegister stamps `retention` archival
metadata at object creation. The `archive` key MUST survive
`ConfigurationService::importFromApp()` — a unit test MUST pin that the
imported schema exposes the configured `archive` block, and if the import
path drops the key an OpenRegister issue MUST be filed (the degradation
path is manual configuration through OR's schema UI, never a DocuDesk-side
retention implementation).

#### Scenario: Imported record schemas expose archive configuration

- GIVEN a fresh install after `ConfigurationService::importFromApp()` runs
- WHEN the `correspondence` schema is loaded from OpenRegister
- THEN its `archive` configuration carries `enabled: true`, the placeholder `classificatie` and `closureField: generatedAt`
- @e2e exclude boot-time register import with no UI surface — covered by PHPUnit import-pin tests (tests/unit/Settings/)

#### Scenario: New correspondence records are stamped

- GIVEN the imported `correspondence` schema with archive configuration
- WHEN a correspondence record is created
- THEN OpenRegister populates its `retention` block from the selectielijst entry the `classificatie` resolves to
- @e2e exclude backend stamping performed by OpenRegister — covered by PHPUnit on created objects

## MODIFIED Requirements

### Requirement: Correspondence Schema — Full JSON Schema with Archival (REQ-DREG-01)

**Priority:** MUST

The `correspondence` schema tracks individual generated documents. It
declares full JSON Schema validation. Its 7-year Archiefwet retention
(selectielijst zakelijke correspondentie — category placeholder pending
selectielijst-manager confirmation) is managed by OpenRegister's
records-management stack via the schema's `archive` configuration
(REQ-DDARE-020): the retention term lives in the referenced selectielijst
entry and destruction happens exclusively through an approved
vernietigingslijst (REQ-DDARE-004). The schema MUST NOT declare the
`x-openregister-archival` auto-delete annotation, because that sweep
destroys rows without vernietigingslijst approval (REQ-DDARE-008).

#### Scenario: Correspondence record validates strictly

- **GIVEN** the correspondence schema has `hardValidation: true`
- **WHEN** a controller writes a record with an unknown field (e.g., `foo: "bar"`)
- **THEN** OR's validator SHALL reject the write with a validation error
- **AND** no record SHALL be persisted
- @e2e exclude backend validation behaviour — covered by PHPUnit (schema validation tests)

#### Scenario: Correspondence destruction requires an approved vernietigingslijst

- **GIVEN** correspondence records older than their 7-year retention term
- **WHEN** OpenRegister's destruction check runs
- **THEN** the expired records appear on a vernietigingslijst with status `in_review` instead of being deleted
- **AND** deletion happens only after archivist approval, producing a verklaring van vernietiging
- @e2e exclude destruction pipeline is OR-side — covered by PHPUnit fixture tests on the Archiefbeheer surface; archivist flow covered in tests/e2e/workflows/archiefwet-retention.spec.ts

#### Scenario: Generated correspondence lifecycle

- **GIVEN** a correspondence record is created with status `generated`
- **WHEN** the record is queried
- **THEN** the status field SHALL equal `generated` (terminal success state)
- **AND** no further status transitions are expected for individual correspondence records
- @e2e exclude backend data-model property — covered by PHPUnit

### Requirement: Batch Correspondence Job Schema — Lifecycle-Backed Status (REQ-DREG-02)

**Priority:** MUST

The `batchCorrespondenceJob` schema replaces IAppConfig-based batch-job
tracking. Each batch dispatch creates an OR object; the job lifecycle
(pending → processing → success|error → completed) is declared via
`x-openregister-lifecycle`. As an operational log (not an
Archiefwet-controlled record class) it keeps annotation-driven cleanup,
declared in the object shape OR HEAD validates:
`"x-openregister-archival": {"retention": {"default": "P1Y"}}` — never the
legacy bare-string shape, which fails OR's `ArchivalAnnotationValidator`
with a 422 on schema save.

#### Scenario: BatchCorrespondenceJob creates an OR object on dispatch

- **GIVEN** `CorrespondenceService::dispatchBatchJob()` is invoked with > 10 recipients
- **WHEN** the job is queued
- **THEN** a `batchCorrespondenceJob` object SHALL be created in the `document` register with status `pending`
- **AND** the OR object UUID SHALL replace the current `$jobId` IAppConfig key
- @e2e exclude backend job dispatch — covered by PHPUnit

#### Scenario: Job lifecycle transitions replace inline status writes

- **GIVEN** `BatchCorrespondenceJob::run()` begins processing
- **WHEN** the job previously wrote `'status' => 'processing'` to IAppConfig
- **THEN** it SHALL invoke the lifecycle transition to `processing` instead
- **AND** the resulting object on the wire SHALL serialize `"status": "processing"` unchanged
- @e2e exclude backend lifecycle behaviour — covered by PHPUnit

#### Scenario: Batch job auto-cleanup after 1 year uses the validated annotation shape

- **GIVEN** the `batchCorrespondenceJob` schema declares `x-openregister-archival` as `{"retention": {"default": "P1Y"}}`
- **WHEN** the schema is saved and OR's archival sweep runs
- **THEN** the annotation passes OR's `ArchivalAnnotationValidator`
- **AND** job records older than 1 year are deleted by the sweep with an audit-trail entry
- @e2e exclude backend annotation + sweep behaviour — covered by PHPUnit register-lint and OR's own sweep suite

#### Scenario: Batch completed notification fires on lifecycle transition

- **GIVEN** `x-openregister-notifications.batchCompleted` is keyed on `complete` transition
- **WHEN** the job transitions to `completed`
- **THEN** the notification SHALL fire automatically to the `initiatedBy` user
- **AND** no direct `notificationManager->notify()` call SHALL exist in `BatchCorrespondenceJob`
- @e2e exclude backend notification dialect — covered by PHPUnit

#### Scenario: Error count is a calculation, not an ad-hoc write

- **GIVEN** `batchCorrespondenceJob` carries `x-openregister-calculations.errorRate`
- **WHEN** the job finishes
- **THEN** the `errorRate` derived field SHALL be computed from `errorCount / recipientCount`
- **AND** service code SHALL NOT compute this value directly
- @e2e exclude backend calculation dialect — covered by PHPUnit
