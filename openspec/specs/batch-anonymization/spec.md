---
status: in-progress
or_adoption_change: docudesk-adopt-or-abstractions
---

# Batch Anonymization

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [docudesk-adopt-or-abstractions](../../changes/archive/2026-06-14-docudesk-adopt-or-abstractions/) _(implementing)_ — REQ-BANON-00: ICache batch state replaced by `batchAnonymizationJob` OR objects with per-file lifecycle children, scheduled via OR Background Jobs (kind: code)
- [redaction-at-scale](../../changes/redaction-at-scale/) _(active)_ — batch anonymize becomes a background operation above the synchronous cap (HTTP 202 + work-unit processing, cancel/resume, throughput report, sampling QA) (kind: code)

## Purpose

@e2e exclude batch anonymization API not yet exposed in the DocuDesk UI — batch upload/extraction/review flow is API-only; covered by PHPUnit and API contract tests

Provides batch anonymization of multiple files in a single operation. Per-file state is tracked as **child OR objects** with `x-openregister-lifecycle` annotations (`pending → processing → success | error`). The previous `ICache`-backed status tracking is replaced by OR Background Jobs and child-object lifecycle. Maximum batch size is admin-configurable via `IAppConfig` key `docudesk.batch.max_files_per_run` (default: 100).

## OR Adoption decisions (from docudesk-adopt-or-abstractions)

- **Task 8** — `ICache`-backed per-file status tracking is replaced by per-file child OR objects. Each file in a batch is a child object of the `batchAnonymizationJob` schema with lifecycle states `pending → processing → success | error`. OR Background Jobs schedule and execute the batch; no custom cache TTL machinery is needed.
- **Task 11** — `BatchStateService::CACHE_TTL` (7200) and `DEFAULT_MAX_FILES` (100) are promoted to admin-config keys `docudesk.batch.cache_ttl_seconds` and `docudesk.batch.max_files_per_run`. `CACHE_PREFIX` is dropped after the ICache state machine is removed. Default values are preserved.
- **Decision 5** — Status strings on the wire stay the same (`pending`, `processing`, `success`, `error`). Lifecycle annotation maps these values; no renaming.

## Requirements

### Requirement: Batch Job Schema Backed by OR Lifecycle (REQ-BANON-00)

**Priority:** MUST

A `batchAnonymizationJob` schema MUST be declared with `x-openregister-lifecycle` (states: `pending → extracting → review → anonymizing → completed | error`). Each batch job is an OR object in the `dossier` register; per-file child objects carry their own lifecycle.

#### Scenario: Batch job creates an OR object on upload

- **GIVEN** a user uploads 5 files to `POST /api/anonymization/batch/upload`
- **WHEN** the batch is created
- **THEN** a `batchAnonymizationJob` OR object SHALL be created with status `pending`
- **AND** each file SHALL produce a child `batchAnonymizationFile` OR object with status `pending`
- **AND** no batch state SHALL be stored in `ICache`

#### Scenario: Per-file lifecycle transitions replace cache writes

- **GIVEN** `BatchStateService::updateFileStatus()` previously wrote to ICache
- **WHEN** a file is extracted
- **THEN** the child object's lifecycle SHALL transition to `extracted` via `lifecycleService->transitionTo()`
- **AND** `BatchStateService::CACHE_PREFIX` SHALL no longer be read or written

#### Scenario: Batch status endpoint reads from OR

- **GIVEN** `GET /api/anonymization/batch/{batchId}/status` is called
- **WHEN** the batch job OR object is fetched
- **THEN** the response includes `batchStatus` and per-file `status` from the OR child objects
- **AND** no ICache lookup is performed

#### Scenario: Max batch size from admin-config

- **GIVEN** admin has set `docudesk.batch.max_files_per_run = 50`
- **WHEN** a user uploads 51 files
- **THEN** the system returns HTTP 400 "Batch size exceeds maximum of 50 files"
- **AND** the limit is read from `IAppConfig`, not from `BatchStateService::DEFAULT_MAX_FILES`

#### Scenario: Batch scheduling via OR Background Jobs

- **GIVEN** a batch job object is created with status `pending`
- **WHEN** OR's Background Jobs scheduler runs
- **THEN** OR SHALL dispatch the batch anonymization job without docudesk managing its own scheduling
- **AND** the job progress SHALL update the child object lifecycles

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| BANON-000 | `batchAnonymizationJob` schema declared with `x-openregister-lifecycle` | MUST | Implementing |
| BANON-001 | Per-file child objects carry `pending → processing → success | error` lifecycle | MUST | Implementing |
| BANON-002 | `BatchStateService` ICache reads/writes replaced by OR child-object lifecycle | MUST | Apply-phase |
| BANON-003 | `CACHE_PREFIX` constant removed after apply phase | MUST | Apply-phase |
| BANON-004 | `max_files_per_run` read from `IAppConfig docudesk.batch.max_files_per_run` (default: 100) | MUST | Apply-phase |
| BANON-005 | OR Background Jobs schedule batch execution; docudesk does not manage its own scheduler | MUST | Apply-phase |

### Requirement: Batch creation via multi-file upload
The system SHALL accept multiple files in a single upload request to `POST /api/anonymization/batch/upload` and return a batch ID. Each file SHALL be stored as an OR File Attachment. Batch state SHALL be persisted as OR child objects. The batch SHALL track each file's processing status via per-file lifecycle. Maximum batch size is admin-configurable via `docudesk.batch.max_files_per_run` (default: 100).

#### Scenario: Upload multiple files as a batch
- **WHEN** an authenticated user uploads 5 PDF files to `POST /api/anonymization/batch/upload`
- **THEN** the system creates a batch with a unique batchId
- **AND** all 5 files are stored in the user's DocuDesk/ folder
- **AND** the response includes batchId, fileCount, and per-file details (fileId, fileName, status: "uploaded")
- **AND** batch state is stored in ICache with 2-hour TTL

#### Scenario: Exceed maximum batch size
- **WHEN** a user uploads 101 files (exceeding default limit of 100)
- **THEN** the system returns HTTP 400 with error "Batch size exceeds maximum of 100 files"
- **AND** no files are stored

#### Scenario: Batch upload requires authentication
- **WHEN** an unauthenticated request hits `POST /api/anonymization/batch/upload`
- **THEN** the system returns HTTP 401

### Requirement: Sequential batch extraction
The system SHALL extract text and detect entities for each file in a batch sequentially via `POST /api/anonymization/batch/{batchId}/extract`. Extraction SHALL process one file per API call (the next unprocessed file) using OpenRegister's TextExtractionService. After each file is extracted, its status SHALL update to "extracted" in the batch state. When all files are extracted, the batch status SHALL change to "review".

#### Scenario: Extract next file in batch
- **WHEN** `POST /api/anonymization/batch/{batchId}/extract` is called
- **THEN** the system extracts the next file with status "uploaded"
- **AND** entities are detected via OpenRegister EntityRelationMapper
- **AND** the file's status updates to "extracted" with its entity count
- **AND** the response includes fileId, fileName, entityCount, and overall batch progress (filesExtracted/totalFiles)

#### Scenario: All files already extracted
- **WHEN** `POST /api/anonymization/batch/{batchId}/extract` is called but all files have status "extracted"
- **THEN** the system returns HTTP 200 with batchStatus "review" and message "All files extracted"

#### Scenario: Extraction error on single file
- **WHEN** extraction fails for one file in the batch
- **THEN** that file's status changes to "error" with the error message
- **AND** the batch continues — remaining files can still be extracted
- **AND** the response includes the error details for the failed file

### Requirement: Batch status endpoint
The system SHALL provide `GET /api/anonymization/batch/{batchId}/status` returning the current state of the batch: batchId, batchStatus (uploading/extracting/review/anonymizing/completed/error), files array with per-file status, total entity count, and overall progress percentage. Reading batch status SHALL reset the cache TTL, keeping the batch alive as long as it is being actively polled.

#### Scenario: Query batch status during extraction
- **WHEN** a user queries `GET /api/anonymization/batch/{batchId}/status` while extraction is in progress
- **THEN** the response includes batchStatus "extracting", files array showing each file's current status, and progress as a percentage of files extracted

#### Scenario: Query expired batch
- **WHEN** a user queries a batchId whose ICache entry has expired (TTL exceeded)
- **THEN** the system returns HTTP 404 with error "Batch not found or expired"

#### Scenario: TTL is reset on status poll
- **WHEN** a user polls batch status at time T (within the 2-hour TTL window)
- **THEN** the cache TTL is reset to 2 hours from time T
- **AND** the batch remains accessible for another 2 hours of inactivity

### Requirement: Batch anonymization
The system SHALL anonymize all extracted files in a batch via `POST /api/anonymization/batch/{batchId}/anonymize`. The request body SHALL include an `entities` array of entity values/types to anonymize (from the review step). The system SHALL apply the entity list to each file using OpenRegister's FileService::anonymizeDocument(). Per GDPR Article 4(5), entity replacements SHALL use unique UUID pseudonyms per entity value (consistent across all files in the batch).

#### Scenario: Anonymize batch with reviewed entities
- **WHEN** `POST /api/anonymization/batch/{batchId}/anonymize` is called with 10 selected entities
- **THEN** each extracted file in the batch is anonymized using the provided entity list
- **AND** each file's status updates to "anonymized" with replacementCount
- **AND** the same entity value receives the same UUID pseudonym across all files
- **AND** the batch status changes to "completed"

#### Scenario: Anonymize batch with empty entity list
- **WHEN** the entities array is empty
- **THEN** the system returns HTTP 400 with error "No entities provided for anonymization"

#### Scenario: Skip error files during batch anonymization
- **WHEN** a batch contains files with status "error" from extraction
- **THEN** those files are skipped during anonymization
- **AND** the response includes a skippedFiles array listing the skipped file IDs and reasons

### Requirement: Batch completion report
The system SHALL generate a CSV audit report via `GET /api/anonymization/batch/{batchId}/report` after batch completion. The report SHALL include: fileName, originalFileId, anonymizedFileId, entityCount, replacementCount, status, and timestamp. Entity values SHALL NOT be included in the report (GDPR data minimization, Recital 26). The response SHALL have Content-Type `text/csv` with a Content-Disposition header for download.

#### Scenario: Download batch report
- **WHEN** a user requests `GET /api/anonymization/batch/{batchId}/report` for a completed batch
- **THEN** the system returns a CSV file with headers: fileName, originalFileId, anonymizedFileId, entityCount, replacementCount, status, timestamp
- **AND** the Content-Disposition header suggests filename "anonymization-report-{batchId}.csv"

#### Scenario: Report for incomplete batch
- **WHEN** a user requests the report for a batch that is not yet completed
- **THEN** the system returns HTTP 409 with error "Batch is not yet completed"

### Requirement: WOO entity category profiles
The system SHALL support pre-configured entity category profiles stored in IAppConfig key `docudesk_woo_entity_profiles`. The default WOO profile SHALL anonymize: PERSON, BSN, PHONE, EMAIL, IBAN, ADDRESS. The default WOO profile SHALL keep visible: ORGANIZATION, LOCATION, DATE. Profiles SHALL be retrievable via `GET /api/anonymization/profiles` and configurable by admins via `PUT /api/anonymization/profiles`. The entity review step SHALL pre-select entities based on the active profile.

#### Scenario: Retrieve default WOO profile
- **WHEN** `GET /api/anonymization/profiles` is called and no custom profile exists
- **THEN** the system returns the default WOO profile with anonymize=[PERSON, BSN, PHONE, EMAIL, IBAN, ADDRESS] and keep=[ORGANIZATION, LOCATION, DATE]

#### Scenario: Admin updates entity profile
- **WHEN** an admin calls `PUT /api/anonymization/profiles` with a modified profile
- **THEN** the profile is saved to IAppConfig
- **AND** subsequent batch reviews use the updated profile for pre-selection

#### Scenario: Non-admin cannot update profiles
- **WHEN** a non-admin user calls `PUT /api/anonymization/profiles`
- **THEN** the system returns HTTP 403

### Requirement: Batch entity consolidation with partial results
The system SHALL provide `GET /api/anonymization/batch/{batchId}/entities` returning consolidated entities. The endpoint SHALL be accessible when batch status is "extracting" OR "review" (previously only "review"). The response SHALL include a `complete` boolean (true only when batch status is "review") and `filesProcessed` count alongside the existing `entities` array and `entityCount`. The `minConfidence` query parameter SHALL continue to work as before.

#### Scenario: Poll entities during extraction (partial results)
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called while batch status is "extracting"
- **THEN** the response includes entities consolidated from all files with status "extracted"
- **AND** `complete: false`
- **AND** `filesProcessed` reflects the number of extracted files

#### Scenario: Poll entities after extraction complete
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called when batch status is "review"
- **THEN** the response includes the full consolidated entity list
- **AND** `complete: true`
- **AND** `filesProcessed` equals the total file count (minus error files)

#### Scenario: Poll entities before extraction starts
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called when batch status is "uploading" or "queued"
- **THEN** the system returns HTTP 409 with error "Extraction has not started"

#### Scenario: Apply minimum confidence filter on partial results
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities?minConfidence=0.7` is called during extraction
- **THEN** only entities with highestConfidence >= 0.7 are included
- **AND** the `complete` and `filesProcessed` fields are still present

