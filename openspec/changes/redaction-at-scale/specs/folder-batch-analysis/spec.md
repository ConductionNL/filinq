# folder-batch-analysis Specification (delta)

---
status: proposed
---

## Purpose

Scales folder batches for municipal dossiers: the single-run
`FolderExtractionJob` (which processes every file of the batch in one
QueuedJob execution) is replaced by the chunked, cancellable work-unit
processing of `redaction-at-scale` REQ-DDRAS-002, and folder enumeration
gains an optional recursive mode for dossier trees.

## MODIFIED Requirements

### Requirement: Background extraction via QueuedJob
The system SHALL process folder-batch extraction in background work units under the `redaction-at-scale` coordinator (REQ-DDRAS-002): per run, at most `filinq.batch.files_per_tick` files and at most `filinq.batch.seconds_per_tick` wall seconds, processing files sequentially and calling `AnonymizationService::extractAndDetectEntities()` for each file. After each file is processed, the per-file OR child object SHALL be updated (status "extracted" or "error") before the next file begins. Between files the worker SHALL honour a pending cancellation (`redaction-at-scale` REQ-DDRAS-004). When all files have been attempted, the batch status SHALL change to "review". Individual file failures SHALL NOT abort the batch; processing SHALL continue with the remaining files.

#### Scenario: Background job processes 3 files sequentially
- **WHEN** a folder-batch work unit runs for a batch with 3 files
- **THEN** file 1 is extracted and its status updated to "extracted" before file 2 begins
- **AND** file 2 is extracted and its status updated before file 3 begins
- **AND** after all 3 files are processed, the batch status changes to "review"

#### Scenario: One file fails during background extraction
- **WHEN** extraction fails for file 2 of 3
- **THEN** file 2's status is set to "error" with the error message
- **AND** file 3 is still processed normally
- **AND** the batch status still transitions to "review" when all files have been attempted

#### Scenario: Batch state reflects progress during extraction
- **WHEN** the background processing has completed 2 of 5 files
- **THEN** a poll of `GET /api/anonymization/batch/{batchId}/status` shows batchStatus "extracting", 2 files with status "extracted", and 3 files with status "uploaded"
- **AND** progress is reported as 40%

#### Scenario: Extraction spanning multiple work units (REQ-DDRAS-010)
- **GIVEN** a folder batch of 60 files and `files_per_tick = 25`
- **WHEN** the coordinator runs until the batch is done
- **THEN** extraction completes across three work units with per-file state persisted at each file boundary
- AND a cancellation requested between units stops the batch before the next unit starts
- @e2e exclude cron work-unit scheduling not drivable from UI; covered by PHPUnit (tests/unit/BackgroundJob/BatchJobCoordinatorTest.php)

## ADDED Requirements

### Requirement: Optional recursive folder enumeration (REQ-DDRAS-011)

`POST /api/anonymization/batch/folder` MUST accept an optional boolean
`recursive` parameter (default `false`, preserving the existing flat-scan
behaviour). When `recursive: true`, the system MUST enumerate file nodes
depth-first through all subfolders of the resolved folder, still skipping
non-file nodes, and MUST record each file's path relative to the batch
root so the report and review views can display dossier structure. The
applicable batch size cap (synchronous or background, per
`redaction-at-scale` REQ-DDRAS-008) MUST be enforced against the total
recursive file count before any processing starts.

#### Scenario: Recursive dossier folder becomes one batch

- GIVEN a dossier folder `/Dossiers/Woo-2025-014` with 3 subfolders containing 480 files in total
- WHEN a folder batch is created with `recursive: true`
- THEN the batch contains all 480 files with their relative paths recorded
- AND the batch is routed to the background path (above the synchronous cap)
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: Default remains a flat scan

- GIVEN a folder with 5 direct files and a subfolder containing 10 more
- WHEN a folder batch is created without the `recursive` parameter
- THEN the batch contains only the 5 direct files (existing behaviour)
- @e2e exclude backwards-compatible enumeration contract; covered by PHPUnit (tests/unit/Service/FolderBatchServiceTest.php)

#### Scenario: Recursive count above the background cap is refused

- GIVEN a recursive enumeration totalling 1200 files and a background cap of 1000
- WHEN batch creation is attempted
- THEN the system returns HTTP 400 naming the configured maximum
- AND no batch is created
- @e2e exclude API limit contract; covered by PHPUnit + Newman batch contracts
