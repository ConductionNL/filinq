---
status: implementing
or_adoption_change: docudesk-adopt-or-abstractions
---

# Document Register

## Purpose

Defines the data model for the `document` register used by DocuDesk to store correspondence audit logs and huisstijl configuration. The `report`, `template`, and `entity` schemas originally present in `document_register.json` have been migrated to their authoritative homes: report objects are now OR File Attachments enriched with `x-openregister-calculations` annotations; template management lives in the `templates` register. Three schemas remain active in the document register: `correspondence`, `huisstijl`, and `batchCorrespondenceJob`.

## OR Adoption decisions (from docudesk-adopt-or-abstractions)

- **Decision 3**: Schema validation is now mandatory. All schemas in the document register declare full `required`, `properties`, and `hardValidation: true`. The previous `properties: []` / `hardValidation: false` shape is removed.
- **Decision 2**: Archival annotation per schema. `correspondence` carries `x-openregister-archival.retention: P7Y` (Archiefwet selectielijst cat. 3.2). `batchCorrespondenceJob` carries `P1Y` (operational log, cat. 1.2).
- **Decision 1**: Lifecycle annotation backs all status fields. `batchCorrespondenceJob` declares `x-openregister-lifecycle` replacing the IAppConfig-backed status writes in `BatchCorrespondenceJob.php`. The wire status values (pending/processing/success/error/completed) are unchanged (Decision 5).

## Requirements

### Requirement: Correspondence Schema — Full JSON Schema with Archival (REQ-DREG-01)

**Priority:** Must

The `correspondence` schema tracks individual generated documents. It declares full JSON Schema validation and a P7Y archival retention.

#### Scenario: Correspondence record validates strictly

- **GIVEN** the correspondence schema has `hardValidation: true`
- **WHEN** a controller writes a record with an unknown field (e.g., `foo: "bar"`)
- **THEN** OR's validator SHALL reject the write with a validation error
- **AND** no record SHALL be persisted

#### Scenario: Correspondence archival after 7 years

- **GIVEN** `x-openregister-archival.retention: P7Y` is declared on the correspondence schema
- **WHEN** OR's archival background job runs
- **THEN** correspondence records older than 7 years SHALL be eligible for archival
- **AND** this traces to Archiefwet 1995 selectielijst cat. 3.2 (zakelijke correspondentie)

#### Scenario: Generated correspondence lifecycle

- **GIVEN** a correspondence record is created with status `generated`
- **WHEN** the record is queried
- **THEN** the status field SHALL equal `generated` (terminal success state)
- **AND** no further status transitions are expected for individual correspondence records

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-001 | Correspondence schema declares `required` + `properties` + `hardValidation: true` | MUST | Implementing |
| DREG-002 | Correspondence schema carries `x-openregister-archival.retention: P7Y` | MUST | Implementing |
| DREG-003 | Status field has enum `[generated, failed]` — preserved per Decision 5 | MUST | Implementing |
| DREG-004 | x-openregister-notifications: `correspondenceFailed` keyed on creation event | MUST | Implemented |

### Requirement: Batch Correspondence Job Schema — Lifecycle-Backed Status (REQ-DREG-02)

**Priority:** Must

The `batchCorrespondenceJob` schema replaces IAppConfig-based batch-job tracking. Each batch dispatch creates an OR object; the job lifecycle (pending → processing → success|error → completed) is declared via `x-openregister-lifecycle`.

#### Scenario: BatchCorrespondenceJob creates an OR object on dispatch

- **GIVEN** `CorrespondenceService::dispatchBatchJob()` is invoked with > 10 recipients
- **WHEN** the job is queued
- **THEN** a `batchCorrespondenceJob` object SHALL be created in the `document` register with status `pending`
- **AND** the OR object UUID SHALL replace the current `$jobId` IAppConfig key

#### Scenario: Job lifecycle transitions replace inline status writes

- **GIVEN** `BatchCorrespondenceJob::run()` begins processing
- **WHEN** the job previously wrote `'status' => 'processing'` to IAppConfig (line 113)
- **THEN** it SHALL invoke `lifecycleService->transitionTo($batchJobObj, 'processing')` instead
- **AND** the resulting object on the wire SHALL serialize `"status": "processing"` unchanged (Decision 5)

#### Scenario: Batch completed notification fires on lifecycle transition

- **GIVEN** `x-openregister-notifications.batchCompleted` is keyed on `complete` transition
- **WHEN** the job transitions to `completed`
- **THEN** the notification SHALL fire automatically to the `initiatedBy` user
- **AND** no direct `notificationManager->notify()` call SHALL exist in `BatchCorrespondenceJob`

#### Scenario: Batch job archival after 1 year

- **GIVEN** `x-openregister-archival.retention: P1Y` is declared on batchCorrespondenceJob
- **WHEN** OR's archival job runs
- **THEN** batch job records older than 1 year SHALL be eligible for destruction
- **AND** this traces to Archiefwet cat. 1.2 (operationele verwerkingslogboeken)

#### Scenario: Error count is a calculation, not an ad-hoc write

- **GIVEN** `batchCorrespondenceJob` carries `x-openregister-calculations.errorRate`
- **WHEN** the job finishes
- **THEN** the `errorRate` derived field SHALL be computed from `errorCount / recipientCount`
- **AND** service code SHALL NOT compute this value directly

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-010 | `batchCorrespondenceJob` schema exists in `document` register | MUST | Implementing |
| DREG-011 | Schema declares `x-openregister-lifecycle` with states: pending/processing/success/error/completed | MUST | Implementing |
| DREG-012 | All five lifecycle transition writes in `BatchCorrespondenceJob.php` (lines 113/162/168/186/199) route through lifecycle API | MUST | Apply-phase |
| DREG-013 | Schema carries `x-openregister-archival.retention: P1Y` | MUST | Implementing |
| DREG-014 | `x-openregister-notifications` keyed on `complete` and `fail` transitions | MUST | Implementing |
| DREG-015 | `initiatedBy` field enables recipient resolution for notifications | MUST | Implementing |

### Requirement: Huisstijl Schema — Validation Enabled (REQ-DREG-03)

**Priority:** Must

The `huisstijl` schema stores organisation house-style configuration. Validation is enabled to prevent malformed logo data or colour codes from reaching PDF generation.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-020 | Huisstijl schema has `hardValidation: true` | MUST | Implementing |
| DREG-021 | No archival annotation — huisstijl is configuration, not a record | MUST | Implementing |

### Requirement: Report Schema Migrated to OR File Attachments (REQ-DREG-04)

**Priority:** Must

The original `report` schema (previously in `document_register.json`) is replaced by OR File Attachment metadata. Calculated fields (anonymization-confidence, OCR-confidence, risk-score, entity-density, redaction-coverage) are declared via `x-openregister-calculations` on the file-attachment extension in `docudesk_register.json`.

#### Scenario: Report data lives on OR file attachment

- **GIVEN** a document has been analysed
- **WHEN** analysis results are persisted
- **THEN** the results SHALL be stored as properties on the OR file attachment object
- **AND** the ad-hoc `properties: []` report schema SHALL no longer exist

#### Scenario: Risk level is a calculation, not an ad-hoc write

- **GIVEN** `x-openregister-calculations.riskLevel` is declared on the file-attachment extension
- **WHEN** entity detection completes
- **THEN** `riskLevel` SHALL be derived from the calculation expression (`entityCount → riskScore → riskLevel`)
- **AND** `AnonymizationService` SHALL NOT write `riskLevel` directly

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-030 | Report data migrated to OR File Attachment properties | MUST | Apply-phase |
| DREG-031 | `x-openregister-calculations` declares `riskScore`, `riskLevel`, `anonymizationConfidence`, `entityDensity`, `redactionCoverage` | MUST | Implementing |
| DREG-032 | `document_register.json` with `properties: []` schemas removed after migration | MUST | Apply-phase |
| DREG-033 | OR file-attachment extension PR raised if schema is upstream | SHOULD | Apply-phase |

### Requirement: Multi-tenancy and i18n (P2) (REQ-DREG-05)

**Priority:** Should (Phase 2 — gated on nc-vue shipping multi-tenancy-context + OR shipping i18n-source-of-truth)

#### Scenario: Tenant scope from composable

- **GIVEN** nc-vue `multi-tenancy-context` composable is available
- **WHEN** a docudesk frontend store needs the current tenant
- **THEN** it SHALL read from `useTenantContext()`, not from user/route state
- **AND** document-register reads SHALL be scoped to the current tenant

#### Scenario: i18n-aware reads on correspondence

- **GIVEN** a client sends `Accept-Language: nl-NL`
- **WHEN** the response includes a translatable field declared in i18n-source-of-truth
- **THEN** the field SHALL return the Dutch translation

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-040 | Tenant-scoped reads via `useTenantContext()` | SHOULD | P2-gated |
| DREG-041 | i18n-aware API respects `Accept-Language` header | SHOULD | P2-gated |

## Data Model

### batchCorrespondenceJob Schema Fields

| Field | Type | Required | Description | Lifecycle role |
|-------|------|----------|-------------|----------------|
| templateId | string (UUID) | Yes | Template used for generation | — |
| templateName | string | No | Template name (denormalised) | — |
| recipientCount | integer | Yes | Total recipients | — |
| completedCount | integer | No | Successfully generated | — |
| errorCount | integer | No | Failed generations | — |
| status | string (enum) | Yes | pending / processing / success / error / completed | `x-openregister-lifecycle` field |
| initiatedBy | string | Yes | Nextcloud user ID | Notification recipient |
| startedAt | datetime | No | When processing began | — |
| completedAt | datetime | No | When job finished | — |
| errorMessage | string | No | Fatal error if failed | — |

### correspondence Schema Fields (unchanged from current)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| templateId | string (UUID) | Yes | Template used |
| templateName | string | No | Template name |
| recipientId | string (UUID) | Yes | Recipient object UUID |
| recipientType | string (enum) | No | PERSON / ORGANIZATION |
| caseReference | string (UUID) | No | Source case |
| generatedAt | datetime | Yes | Generation timestamp |
| format | string (enum) | Yes | pdf / docx / html / email |
| status | string (enum) | Yes | generated / failed |
| generatedBy | string | Yes | Nextcloud user ID |
| errorMessage | string | No | Error if failed |

## Dependencies

- **OpenRegister ObjectService**: CRUD on register objects
- **OpenRegister LifecycleService**: Transition API for batchCorrespondenceJob
- **docudesk_register.json**: Source of truth for register/schema structure
- **BatchCorrespondenceJob.php**: Lifecycle transitions replace IAppConfig writes (apply phase)
- **CorrespondenceService.php**: Dispatch creates batchCorrespondenceJob OR object (apply phase)

## Migration path

1. This change adds the `batchCorrespondenceJob` schema to `docudesk_register.json` and annotates `correspondence` with archival.
2. The apply phase wires `BatchCorrespondenceJob.php` and `CorrespondenceService.php` to create/transition OR objects instead of reading/writing IAppConfig.
3. The `document_register.json` file with its `properties: []` schemas is removed after the apply phase migrates report data to OR file attachments.
