## ADDED Requirements

### Requirement: Bulk mode processing

The system SHALL support redacting multiple documents in a single operation by queuing per-document jobs. When a user starts a bulk job on a folder or matter selection containing N documents with a chosen profile, the system MUST queue one `RedactionJob` per document, track progress per-document, process at least 50 pages per minute on the standard worker tier, and produce an aggregate summary report.

#### Scenario: Bulk job queues per-document jobs
- **GIVEN** a folder containing 10 documents with a total of 150 pages
- **WHEN** the user starts a bulk redaction with a profile
- **THEN** the system creates 10 independent `RedactionJob` records (one per document)
- **AND** each job has `sourceDocumentId`, `mode`, `profileId`, and initial `status: "queued"`
- **AND** a parent bulk-job record tracks overall progress

#### Scenario: Parallelism across documents
- **WHEN** 10 documents are queued on a worker pool with 4 workers
- **THEN** up to 4 documents are processed in parallel
- **AND** remaining documents transition from queued → running → completed as workers become available
- **AND** total processing time is <4 hours (150 pages / 50 pages-per-minute throughput)

#### Scenario: Per-document failure isolation
- **GIVEN** documents A, B, C queued in a bulk job
- **WHEN** document B's export fails due to PDF corruption
- **THEN** documents A and C complete normally with `status: "completed"`
- **AND** document B is marked `status: "failed"` with an error message
- **AND** the bulk job overall status is `"partially_completed"`

#### Scenario: Aggregate progress UI
- **WHEN** a bulk job is in progress
- **THEN** the UI shows:
  - Total documents: 10
  - Completed: 3 (30%)
  - Running: 2
  - Queued: 5
  - Failed: 0
  - Progress bar reflecting overall completion

#### Scenario: Bulk summary report
- **WHEN** a bulk job completes (all documents processed or max failures reached)
- **THEN** the system generates a summary report with:
  - Total documents processed: N
  - Successfully redacted: X
  - Failed: Y
  - Partially completed: Z (completed with warnings)
  - Aggregate annotation counts by category (total BSN: 45, total email: 120, total PERSON: 89, etc.)
  - Per-document summary (document name, annotation count, status, error if applicable)
  - Time taken, average pages per minute, throughput metrics

#### Scenario: Document-level summary in bulk report
- **GIVEN** a completed bulk job
- **WHEN** the user opens the bulk summary
- **THEN** a table shows each document with:
  - Document name
  - Page count
  - Annotations detected
  - Annotations applied
  - Status (completed, failed, etc.)
  - Link to view the job detail (if user has permission)

### Requirement: Bulk job folder/matter selection

The system SHALL support selecting documents by folder (all documents recursively) or matter selection (explicit list of documents, e.g., from a search result or filter).

#### Scenario: Bulk redaction on a folder
- **WHEN** a user selects a folder in DocuDesk and clicks "Redact"
- **THEN** all documents in that folder (and subfolders, recursively) are queued
- **AND** the bulk job summary shows the folder name and document count

#### Scenario: Bulk redaction on a matter selection
- **WHEN** a user performs a search (e.g., "documents from 2024 Q2") and selects 50 results
- **THEN** clicking "Redact Selection" queues those 50 documents
- **AND** the bulk job references the selection (not folder-based)

### Requirement: Pausable and resumable bulk jobs

A bulk job MAY be paused (stops queuing new documents, in-flight jobs complete), resumed (resumes queuing), or cancelled (stops all jobs, marks incomplete).

#### Scenario: Pausing a bulk job
- **GIVEN** a bulk job with 20 documents queued, 5 running, 3 completed
- **WHEN** the user clicks "Pause"
- **THEN** no new documents transition from queued → running
- **AND** running jobs continue to completion
- **AND** the job status is `"paused"`

#### Scenario: Resuming a paused bulk job
- **WHEN** the user clicks "Resume" on a paused job
- **THEN** queued documents resume transitioning to running
- **AND** the job status returns to `"running"`

#### Scenario: Cancelling a bulk job
- **WHEN** the user clicks "Cancel"
- **THEN** all queued jobs are marked `"cancelled"`
- **AND** running jobs are allowed to complete or are forcefully terminated (implementation choice, document)
- **AND** the bulk job status is `"cancelled"`
- **AND** a summary is available showing which documents completed, which were cancelled
