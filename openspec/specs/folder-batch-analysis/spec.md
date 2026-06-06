# folder-batch-analysis Specification

## Purpose
TBD - created by archiving change folder-analysis-anonymization. Update Purpose after archive.

@e2e exclude Backend folder-batch API + FolderExtractionJob (QueuedJob) + EntityConsolidationService + file-system output placement; every scenario asserts an HTTP contract or background-job/service behaviour, not UI rendering. Covered by Newman (/api/anonymization/batch/* contracts) and PHPUnit (job + consolidation).

## Requirements
### Requirement: Folder batch initiation from existing Nextcloud folder
The system SHALL accept a folder path via `POST /api/anonymization/batch/folder` and create a batch from the files already present in that Nextcloud folder. The system SHALL enumerate only direct children of the folder (flat scan, no recursion). Only file nodes SHALL be included; subdirectories SHALL be skipped. The batch SHALL be created with the same state structure as upload-based batches, with each file starting at status "uploaded". The endpoint SHALL return the batchId, file count, and file list. Maximum batch size limits (admin-configurable via `docudesk_batch_max_files`) SHALL apply.

#### Scenario: Initiate folder analysis on a folder with 5 documents
- **WHEN** an authenticated user calls `POST /api/anonymization/batch/folder` with `{ "folderPath": "/Documents/WOB-2024" }`
- **THEN** the system resolves the folder via Nextcloud's file system API
- **AND** enumerates all direct file children (skipping subdirectories)
- **AND** creates a batch with each file's fileId, fileName, and status "uploaded"
- **AND** returns `{ batchId, fileCount: 5, files: [...] }`

#### Scenario: Folder path does not exist
- **WHEN** a user provides a folderPath that does not exist in their Nextcloud files
- **THEN** the system returns HTTP 404 with error "Folder not found"

#### Scenario: Path points to a file, not a folder
- **WHEN** a user provides a path that resolves to a file node instead of a folder
- **THEN** the system returns HTTP 400 with error "Path is not a folder"

#### Scenario: Folder is empty
- **WHEN** a user provides a path to an empty folder (no files, only subdirectories or nothing)
- **THEN** the system returns HTTP 400 with error "No files found in folder"

#### Scenario: Folder exceeds maximum batch size
- **WHEN** a folder contains more files than the configured maximum (default 100)
- **THEN** the system returns HTTP 400 with error "Folder contains too many files (found: N, maximum: M)"

#### Scenario: Folder analysis requires authentication
- **WHEN** an unauthenticated request hits `POST /api/anonymization/batch/folder`
- **THEN** the system returns HTTP 401

### Requirement: Background extraction via QueuedJob
The system SHALL queue a `FolderExtractionJob` (extending `OCP\BackgroundJob\QueuedJob`) when a folder batch is created. The job SHALL process files sequentially, calling `AnonymizationService::extractAndDetectEntities()` for each file. After each file is processed, the job SHALL update the batch state (file status to "extracted" or "error"). When all files are processed, the batch status SHALL change to "review". Individual file failures SHALL NOT abort the batch; processing SHALL continue with the remaining files.

#### Scenario: Background job processes 3 files sequentially
- **WHEN** a `FolderExtractionJob` runs for a batch with 3 files
- **THEN** file 1 is extracted and its status updated to "extracted" before file 2 begins
- **AND** file 2 is extracted and its status updated before file 3 begins
- **AND** after all 3 files are processed, the batch status changes to "review"

#### Scenario: One file fails during background extraction
- **WHEN** extraction fails for file 2 of 3
- **THEN** file 2's status is set to "error" with the error message
- **AND** file 3 is still processed normally
- **AND** the batch status still transitions to "review" when all files have been attempted

#### Scenario: Batch state reflects progress during extraction
- **WHEN** the background job has processed 2 of 5 files
- **THEN** a poll of `GET /api/anonymization/batch/{batchId}/status` shows batchStatus "extracting", 2 files with status "extracted", and 3 files with status "uploaded"
- **AND** progress is reported as 40%

### Requirement: Progressive entity consolidation during extraction
The system SHALL allow polling `GET /api/anonymization/batch/{batchId}/entities` while the batch status is "extracting" (not only "review"). The response SHALL include a `complete` boolean flag (`false` during extraction, `true` when status is "review") and a `filesProcessed` count. The consolidated entity list SHALL reflect all entities from files extracted so far, using the existing `EntityConsolidationService::consolidateEntities()` which already iterates only files with status "extracted". Entity deduplication across files SHALL use exact case-insensitive matching (existing behavior).

#### Scenario: Poll entities after 2 of 5 files extracted
- **WHEN** a user calls `GET /api/anonymization/batch/{batchId}/entities` while extraction is in progress (2 of 5 files done)
- **THEN** the response includes entities consolidated from the 2 extracted files
- **AND** `complete: false` and `filesProcessed: 2`

#### Scenario: Same entity found in multiple files
- **WHEN** "Jan Jansen" is detected in file 1 (confidence 0.85) and file 2 (confidence 0.92)
- **THEN** the consolidated entity list shows one entry for "Jan Jansen" with `fileCount: 2` and `highestConfidence: 0.92`

#### Scenario: Poll entities after all files extracted
- **WHEN** all files have been extracted and batch status is "review"
- **THEN** the response includes the full consolidated entity list with `complete: true`

### Requirement: Anonymized output in source folder
The system SHALL save anonymized files in the same folder as the source files, with the filename suffix `_anonymized` before the file extension (e.g., `report.docx` → `report_anonymized.docx`). This is already the behavior of OpenRegister's `DocumentProcessingHandler::anonymizeDocument()` which saves the output in the parent folder of the source node. No additional logic is needed for output placement when processing files from their original location.

#### Scenario: Anonymize a file in its source folder
- **WHEN** a file `/Documents/WOB-2024/report.docx` is anonymized
- **THEN** the anonymized copy is saved as `/Documents/WOB-2024/report_anonymized.docx`
- **AND** the original file is not modified

#### Scenario: Anonymize batch of 3 files in source folder
- **WHEN** a folder batch containing `a.docx`, `b.pdf`, `c.docx` is anonymized
- **THEN** the folder contains the 3 originals plus `a_anonymized.docx`, `b_anonymized.pdf`, `c_anonymized.docx`

