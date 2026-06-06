## ADDED Requirements

> @e2e exclude Pure backend batch-anonymization API — multi-file batch upload, sequential extraction, ICache-backed batch status/expiry, batch anonymization with reviewed entities, batch completion report download, and admin WOO entity-profile config. Server-orchestrated endpoints with no dedicated manifest UI page (the interactive review surface is covered by the anonymization-entity-review spec's UI tests). Verified by PHPUnit BatchAnonymizationServiceTest and the Newman docudesk-api batch collection.

### Requirement: Batch creation via multi-file upload
The system SHALL accept multiple files in a single upload request to `POST /api/anonymization/batch/upload` and return a batch ID. Each file SHALL be stored in the user's DocuDesk/ folder. Batch state SHALL be persisted in Nextcloud ICache with a 2-hour TTL. The batch SHALL track each file's processing status independently. Maximum batch size SHALL be 100 files (admin-configurable via IAppConfig key `docudesk_batch_max_files`).

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
The system SHALL provide `GET /api/anonymization/batch/{batchId}/status` returning the current state of the batch: batchId, batchStatus (uploading/extracting/review/anonymizing/completed/error), files array with per-file status, total entity count, and overall progress percentage.

#### Scenario: Query batch status during extraction
- **WHEN** a user queries `GET /api/anonymization/batch/{batchId}/status` while extraction is in progress
- **THEN** the response includes batchStatus "extracting", files array showing each file's current status, and progress as a percentage of files extracted

#### Scenario: Query expired batch
- **WHEN** a user queries a batchId whose ICache entry has expired (TTL exceeded)
- **THEN** the system returns HTTP 404 with error "Batch not found or expired"

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
