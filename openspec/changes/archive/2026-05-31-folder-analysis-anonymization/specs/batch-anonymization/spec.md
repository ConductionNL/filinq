## MODIFIED Requirements

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

## ADDED Requirements

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
