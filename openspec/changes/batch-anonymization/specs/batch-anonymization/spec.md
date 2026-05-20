---
status: draft
---

# Batch Anonymization

## Purpose

Provides a stateful multi-file anonymisation pipeline for WOO operators who must process batches of 10–100 documents before publication. The pipeline accepts multiple files in a single upload, extracts entities from each file sequentially via OpenRegister, presents a consolidated entity review step, anonymises all files with consistent UUID pseudonyms across the batch, and produces a GDPR-compliant CSV audit report. A companion WOO entity category profiles feature pre-selects the correct entity types at review time, reducing operator effort and the chance of missed categories.

## Requirements

### REQ-BATCH-01: Multi-file batch upload (Priority: Must)

The system accepts multiple files in a single `POST /api/anonymization/batch/upload` request, stores each file in the authenticated user's `DocuDesk/` folder, creates a batch state object in ICache with a 2-hour TTL, and returns a unique `batchId`. Each file tracks its processing status independently. The maximum number of files per batch is admin-configurable via `IAppConfig` key `docudesk_batch_max_files` (default: 100).

#### Scenario: Upload multiple files as a batch

- GIVEN an authenticated user
- WHEN they upload 5 PDF files to `POST /api/anonymization/batch/upload`
- THEN the system creates a batch with a unique `batchId`
- AND all 5 files are stored in the user's `DocuDesk/` folder
- AND the response includes `batchId`, `fileCount`, and a `files` array with per-file `fileId`, `fileName`, and `status: "uploaded"`
- AND batch state is stored in ICache with a 2-hour TTL

#### Scenario: Exceed maximum batch size

- GIVEN a default maximum batch size of 100
- WHEN a user uploads 101 files to `POST /api/anonymization/batch/upload`
- THEN the system returns HTTP 400 with error message "Batch size exceeds maximum of 100 files"
- AND no files are stored
- AND no batch is created in ICache

#### Scenario: Batch upload requires authentication

- GIVEN an unauthenticated request (no valid Nextcloud session)
- WHEN the request hits `POST /api/anonymization/batch/upload`
- THEN the system returns HTTP 401
- AND no files are stored

#### Scenario: Upload with admin-configured maximum

- GIVEN an admin has set `docudesk_batch_max_files` to 50
- WHEN a user uploads 51 files
- THEN the system returns HTTP 400 with error message "Batch size exceeds maximum of 50 files"

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BATCH-001 | `POST /api/anonymization/batch/upload` accepts multiple files in a single multipart request | MUST | Draft |
| BATCH-002 | Each uploaded file is stored in the authenticated user's `DocuDesk/` folder via IRootFolder | MUST | Draft |
| BATCH-003 | Batch state is persisted in ICache with a 2-hour TTL using key `docudesk_batch_{batchId}` | MUST | Draft |
| BATCH-004 | Response includes `batchId`, `fileCount`, and per-file `fileId`, `fileName`, `status` | MUST | Draft |
| BATCH-005 | Maximum batch size enforced via `IAppConfig` key `docudesk_batch_max_files` (default: 100) | MUST | Draft |
| BATCH-006 | Exceeding the maximum returns HTTP 400 with a descriptive error message | MUST | Draft |
| BATCH-007 | Endpoint requires an authenticated Nextcloud session; unauthenticated requests return HTTP 401 | MUST | Draft |

### REQ-BATCH-02: Sequential batch extraction (Priority: Must)

The system extracts text and detects entities for each file in a batch sequentially. Each call to `POST /api/anonymization/batch/{batchId}/extract` processes the next file with status `uploaded`, using OpenRegister's `TextExtractionService::extractFile()` and `EntityRelationMapper::findEntitiesForFile()`. After extraction the file's status updates to `extracted` in ICache. When all files have been processed (status `extracted` or `error`), the batch `batchStatus` changes to `review`. A single extraction failure sets that file to `error` and does not halt the batch.

#### Scenario: Extract next file in batch

- GIVEN a batch with at least one file with status `uploaded`
- WHEN `POST /api/anonymization/batch/{batchId}/extract` is called
- THEN the system selects the next file with status `uploaded` (lowest array index)
- AND extracts text via OpenRegister `TextExtractionService::extractFile()`
- AND detects entities via OpenRegister `EntityRelationMapper::findEntitiesForFile()`
- AND updates the file's status to `extracted` with its `entityCount` in ICache
- AND the response includes `fileId`, `fileName`, `entityCount`, and batch progress as `filesExtracted` / `totalFiles`

#### Scenario: All files already extracted

- GIVEN all files in the batch have status `extracted` or `error`
- WHEN `POST /api/anonymization/batch/{batchId}/extract` is called
- THEN the system returns HTTP 200 with `batchStatus: "review"` and `message: "All files extracted"`
- AND no further processing is performed

#### Scenario: Extraction error on a single file

- GIVEN a batch with multiple files, one of which causes an extraction failure
- WHEN `POST /api/anonymization/batch/{batchId}/extract` is called and extraction fails for that file
- THEN the file's status changes to `error` in ICache with the error message stored
- AND the response includes `fileId`, `fileName`, `status: "error"`, and the `errorMessage`
- AND the batch `batchStatus` remains `extracting`
- AND subsequent extract calls continue with remaining `uploaded` files

#### Scenario: Extraction on an unknown or expired batch

- GIVEN a `batchId` that does not exist in ICache (unknown or TTL expired)
- WHEN `POST /api/anonymization/batch/{batchId}/extract` is called
- THEN the system returns HTTP 404 with error "Batch not found or expired"

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BATCH-010 | Each call to `POST /api/anonymization/batch/{batchId}/extract` processes exactly one `uploaded` file | MUST | Draft |
| BATCH-011 | Extraction uses OpenRegister `TextExtractionService::extractFile()` | MUST | Draft |
| BATCH-012 | Entity detection uses OpenRegister `EntityRelationMapper::findEntitiesForFile()` | MUST | Draft |
| BATCH-013 | Successful extraction sets file status to `extracted` with `entityCount` in ICache | MUST | Draft |
| BATCH-014 | Response includes `fileId`, `fileName`, `entityCount`, `filesExtracted`, and `totalFiles` | MUST | Draft |
| BATCH-015 | When all files are `extracted` or `error`, batch `batchStatus` changes to `review` | MUST | Draft |
| BATCH-016 | A single file extraction failure sets that file to `error`; the batch continues | MUST | Draft |
| BATCH-017 | ICache TTL is extended on each successful extraction call | SHOULD | Draft |

### REQ-BATCH-03: Batch status endpoint (Priority: Must)

The system provides `GET /api/anonymization/batch/{batchId}/status` returning the complete current state of the batch: `batchId`, `batchStatus` (one of: `uploading`, `extracting`, `review`, `anonymizing`, `completed`, `error`), a `files` array with per-file status and entity count, total entity count across all files, and overall progress as a percentage of files extracted.

#### Scenario: Query batch status during extraction

- GIVEN a batch where 3 out of 5 files have status `extracted` and 2 remain `uploaded`
- WHEN a user queries `GET /api/anonymization/batch/{batchId}/status`
- THEN the response includes `batchStatus: "extracting"`
- AND the `files` array shows the current status of each file
- AND `progressPercent` reflects 60 (3 of 5 files extracted)

#### Scenario: Query expired or unknown batch

- GIVEN a `batchId` whose ICache entry has expired or never existed
- WHEN a user queries `GET /api/anonymization/batch/{batchId}/status`
- THEN the system returns HTTP 404 with error "Batch not found or expired"

#### Scenario: Query completed batch status

- GIVEN a batch where all files have status `anonymized` or `error`
- WHEN a user queries `GET /api/anonymization/batch/{batchId}/status`
- THEN the response includes `batchStatus: "completed"`
- AND `progressPercent` is 100

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BATCH-020 | `GET /api/anonymization/batch/{batchId}/status` returns `batchId`, `batchStatus`, `files`, `totalEntityCount`, and `progressPercent` | MUST | Draft |
| BATCH-021 | `batchStatus` is one of: `uploading`, `extracting`, `review`, `anonymizing`, `completed`, `error` | MUST | Draft |
| BATCH-022 | `files` array includes per-file `fileId`, `fileName`, `status`, and `entityCount` | MUST | Draft |
| BATCH-023 | `progressPercent` is calculated as (files with status `extracted` or `anonymized` or `error`) / totalFiles × 100 | MUST | Draft |
| BATCH-024 | Unknown or expired `batchId` returns HTTP 404 with error "Batch not found or expired" | MUST | Draft |

### REQ-BATCH-04: Batch anonymisation with consistent pseudonyms (Priority: Must)

The system anonymises all `extracted` files in a batch via `POST /api/anonymization/batch/{batchId}/anonymize`. The request body includes an `entities` array of entity values and types selected during the review step. The system builds a single entity-to-UUID map (deduplicated by value) and applies it to each `extracted` file via OpenRegister `FileService::anonymizeDocument()`. Per GDPR Article 4(5), the same entity value receives the same UUID pseudonym across all files in the batch. Files with status `error` are skipped and listed in the `skippedFiles` response array.

#### Scenario: Anonymise batch with reviewed entities

- GIVEN a batch in `review` state with 3 `extracted` files and 1 `error` file
- AND the request body contains an `entities` array of 10 selected entities
- WHEN `POST /api/anonymization/batch/{batchId}/anonymize` is called
- THEN a single entity-to-UUID map is built from the deduplicated entity list
- AND each of the 3 `extracted` files is anonymised using `FileService::anonymizeDocument()` with the shared entity map
- AND each file's status updates to `anonymized` in ICache with its `replacementCount`
- AND the same entity value receives the same UUID pseudonym across all 3 files
- AND the 1 `error` file is skipped and listed in `skippedFiles`
- AND batch `batchStatus` changes to `completed`
- AND the response includes the `files` array and `skippedFiles` array

#### Scenario: Anonymise with empty entity list

- GIVEN a batch in `review` state
- WHEN `POST /api/anonymization/batch/{batchId}/anonymize` is called with an empty `entities` array
- THEN the system returns HTTP 400 with error "No entities provided for anonymization"
- AND no files are modified

#### Scenario: Skip error files during batch anonymisation

- GIVEN a batch containing 2 `extracted` files and 1 `error` file
- WHEN `POST /api/anonymization/batch/{batchId}/anonymize` is called
- THEN the 2 `extracted` files are anonymised
- AND the 1 `error` file is skipped
- AND the response `skippedFiles` array includes the skipped file's `fileId`, `fileName`, and `reason`

#### Scenario: Consistent UUID pseudonymisation across files

- GIVEN a batch where "Petra de Vries" appears in both file A and file B
- AND the entity list includes PERSON entity value "Petra de Vries"
- WHEN the batch is anonymised
- THEN "Petra de Vries" is replaced with the same UUID pseudonym in both file A and file B
- AND no two different entity values share the same UUID

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BATCH-030 | `POST /api/anonymization/batch/{batchId}/anonymize` accepts an `entities` array in the request body | MUST | Draft |
| BATCH-031 | A single entity-to-UUID map is built once from the deduplicated entity list before processing any file | MUST | Draft |
| BATCH-032 | The shared entity map is applied to every `extracted` file via `FileService::anonymizeDocument()` | MUST | Draft |
| BATCH-033 | Per GDPR Art. 4(5), the same entity value receives the same UUID pseudonym across all files in the batch | MUST | Draft |
| BATCH-034 | UUID pseudonyms are generated using `random_bytes(16)` (RFC 4122 v4, cryptographically secure) | MUST | Draft |
| BATCH-035 | Files with status `error` are skipped; their `fileId`, `fileName`, and `reason` appear in `skippedFiles` | MUST | Draft |
| BATCH-036 | Each successfully anonymised file status changes to `anonymized` with `replacementCount` in ICache | MUST | Draft |
| BATCH-037 | Empty `entities` array returns HTTP 400 with "No entities provided for anonymization" | MUST | Draft |
| BATCH-038 | Batch `batchStatus` changes to `completed` after all eligible files are processed | MUST | Draft |

### REQ-BATCH-05: Batch completion report (Priority: Must)

The system generates a CSV audit report via `GET /api/anonymization/batch/{batchId}/report` after batch completion. The report includes per-file rows: `fileName`, `originalFileId`, `anonymizedFileId`, `entityCount`, `replacementCount`, `status`, and `timestamp`. Entity values are NOT included per GDPR Recital 26 (data minimisation). The response has `Content-Type: text/csv` and a `Content-Disposition` header for download. The endpoint returns HTTP 409 if the batch is not yet in `completed` status.

#### Scenario: Download batch report for a completed batch

- GIVEN a completed batch with 2 anonymised files and 1 error file
- WHEN a user requests `GET /api/anonymization/batch/{batchId}/report`
- THEN the system returns HTTP 200 with `Content-Type: text/csv`
- AND the `Content-Disposition` header is `attachment; filename="anonymization-report-{batchId}.csv"`
- AND the CSV body contains a header row: `fileName,originalFileId,anonymizedFileId,entityCount,replacementCount,status,timestamp`
- AND the CSV contains one data row per file in the batch
- AND entity values do NOT appear anywhere in the CSV

#### Scenario: Report for an incomplete batch

- GIVEN a batch with `batchStatus: "extracting"`
- WHEN a user requests `GET /api/anonymization/batch/{batchId}/report`
- THEN the system returns HTTP 409 with error "Batch is not yet completed"

#### Scenario: Report for an expired or unknown batch

- GIVEN a `batchId` whose ICache entry has expired
- WHEN a user requests `GET /api/anonymization/batch/{batchId}/report`
- THEN the system returns HTTP 404 with error "Batch not found or expired"

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BATCH-040 | `GET /api/anonymization/batch/{batchId}/report` is available only when `batchStatus === "completed"` | MUST | Draft |
| BATCH-041 | Response `Content-Type` is `text/csv` | MUST | Draft |
| BATCH-042 | `Content-Disposition` header is `attachment; filename="anonymization-report-{batchId}.csv"` | MUST | Draft |
| BATCH-043 | CSV header row: `fileName,originalFileId,anonymizedFileId,entityCount,replacementCount,status,timestamp` | MUST | Draft |
| BATCH-044 | Entity values are NOT included in any CSV column (GDPR Recital 26 data minimisation) | MUST | Draft |
| BATCH-045 | Incomplete batch returns HTTP 409 with "Batch is not yet completed" | MUST | Draft |
| BATCH-046 | Unknown or expired batch returns HTTP 404 with "Batch not found or expired" | MUST | Draft |

### REQ-BATCH-06: WOO entity category profiles (Priority: Must)

The system supports pre-configured entity category profiles stored in `IAppConfig` key `docudesk_woo_entity_profiles`. The default WOO profile ships with the app (hardcoded fallback) with `anonymize: [PERSON, BSN, PHONE, EMAIL, IBAN, ADDRESS]` and `keep: [ORGANIZATION, LOCATION, DATE]`. Profiles are retrievable by any authenticated user via `GET /api/anonymization/profiles` and configurable by admins only via `PUT /api/anonymization/profiles`. The entity review step pre-selects entities matching the `anonymize` list of the active profile.

#### Scenario: Retrieve default WOO profile

- GIVEN no custom profile has been saved to IAppConfig
- WHEN any authenticated user calls `GET /api/anonymization/profiles`
- THEN the system returns the hardcoded default WOO profile
- AND the response includes `anonymize: ["PERSON", "BSN", "PHONE", "EMAIL", "IBAN", "ADDRESS"]`
- AND `keep: ["ORGANIZATION", "LOCATION", "DATE"]`

#### Scenario: Admin updates entity profile

- GIVEN an admin user
- WHEN the admin calls `PUT /api/anonymization/profiles` with a modified profile (e.g. adding `VEHICLE` to `anonymize`)
- THEN the updated profile is saved to `IAppConfig` key `docudesk_woo_entity_profiles`
- AND subsequent calls to `GET /api/anonymization/profiles` return the updated profile
- AND subsequent batch entity review steps pre-select using the updated profile

#### Scenario: Non-admin user attempts to update profiles

- GIVEN a non-admin authenticated user
- WHEN they call `PUT /api/anonymization/profiles`
- THEN the system returns HTTP 403
- AND the profile in IAppConfig is unchanged

#### Scenario: Entity review step pre-selects from active profile

- GIVEN an active WOO profile with `anonymize: ["PERSON", "BSN", "EMAIL"]`
- AND a batch in `review` state where extracted entities include PERSON, BSN, EMAIL, and ORGANIZATION entities
- WHEN the operator opens the entity review step
- THEN PERSON, BSN, and EMAIL entities are pre-selected for anonymisation
- AND ORGANIZATION entities are not pre-selected

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BATCH-050 | `GET /api/anonymization/profiles` returns the active profile; falls back to hardcoded default if no custom profile exists | MUST | Draft |
| BATCH-051 | Default profile: `anonymize: [PERSON, BSN, PHONE, EMAIL, IBAN, ADDRESS]`, `keep: [ORGANIZATION, LOCATION, DATE]` | MUST | Draft |
| BATCH-052 | `PUT /api/anonymization/profiles` saves to `IAppConfig` key `docudesk_woo_entity_profiles` | MUST | Draft |
| BATCH-053 | `PUT /api/anonymization/profiles` requires admin role; non-admin returns HTTP 403 | MUST | Draft |
| BATCH-054 | `PUT /api/anonymization/profiles` annotated `#[AuthorizedAdminSetting(Application::APP_ID)]` (ADR-005) | MUST | Draft |
| BATCH-055 | Entity review step pre-selects entities whose type appears in the active profile's `anonymize` list | MUST | Draft |
| BATCH-056 | `GET /api/anonymization/profiles` is accessible to any authenticated user (`#[NoAdminRequired]`) | MUST | Draft |

## API Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/anonymization/batch/upload` | Upload multiple files; create batch in ICache | `#[NoAdminRequired]` + session |
| POST | `/api/anonymization/batch/{batchId}/extract` | Extract next `uploaded` file in batch | `#[NoAdminRequired]` + session |
| GET | `/api/anonymization/batch/{batchId}/status` | Return current batch state and progress | `#[NoAdminRequired]` + session |
| POST | `/api/anonymization/batch/{batchId}/anonymize` | Anonymise all `extracted` files with entity list | `#[NoAdminRequired]` + session |
| GET | `/api/anonymization/batch/{batchId}/report` | Download CSV audit report (completed batches only) | `#[NoAdminRequired]` + session |
| GET | `/api/anonymization/profiles` | Retrieve active WOO entity profile | `#[NoAdminRequired]` |
| PUT | `/api/anonymization/profiles` | Update WOO entity profile (admin only) | `#[AuthorizedAdminSetting]` |

## Dependencies

- **OpenRegister TextExtractionService**: Text extraction per file (`extractFile()`)
- **OpenRegister EntityRelationMapper**: Entity detection (`findEntitiesForFile()`)
- **OpenRegister FileService**: Document anonymisation (`anonymizeDocument()`)
- **Nextcloud ICache**: Batch state storage with TTL
- **Nextcloud IAppConfig**: WOO profile storage and `docudesk_batch_max_files` config
- **Nextcloud IRootFolder**: File storage in user `DocuDesk/` folder
- **Nextcloud IUserSession**: Current user identification; user identity derived server-side only (ADR-005)
- **Nextcloud IGroupManager**: Admin check for `PUT /api/anonymization/profiles`
