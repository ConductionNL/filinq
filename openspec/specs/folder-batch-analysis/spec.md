---
status: done
---

# folder-batch-analysis Specification

## Purpose
Creates anonymisation batches from the files already present in a Nextcloud folder, accepting either a folder ID or a folder path and enumerating the folder's direct file children. The batch stores both the rename-proof folder ID and a human-readable path snapshot, resolves multi-mount folder IDs by preferring a writable node, and processes the files through a background extraction job and entity-consolidation service subject to admin-configurable size limits. This lets operators run analysis and anonymisation over an existing document folder in one batch operation.

@e2e exclude Backend folder-batch API + FolderExtractionJob (QueuedJob) + EntityConsolidationService + file-system output placement; every scenario asserts an HTTP contract or background-job/service behaviour, not UI rendering. Covered by Newman (/api/anonymization/batch/* contracts) and PHPUnit (job + consolidation).
## Requirements
### Requirement: Folder batch initiation from existing Nextcloud folder
The system SHALL accept either a folder ID (`folderId`, integer) or a folder path (`folderPath`, string) via `POST /api/anonymization/batch/folder` and create a batch from the files already present in that Nextcloud folder. The request MUST provide exactly one of `folderId` or `folderPath` — requests providing neither or both SHALL be rejected with HTTP 400. When `folderId` is provided, the system SHALL resolve the node via `IRootFolder::getUserFolder($userId)->getById($folderId)` and, when multiple nodes are returned (same file ID surfacing through multiple mounts in the user's tree), prefer a node with write permission, falling back to the first readable node. When `folderPath` is provided, the system SHALL resolve the node via `$userFolder->get($folderPath)` (existing behavior). The system SHALL enumerate only direct children of the resolved folder (flat scan, no recursion). Only file nodes SHALL be included; subdirectories SHALL be skipped. The batch SHALL be created with the same state structure as upload-based batches, with each file starting at status "uploaded". The batch SHALL store both `folderId` (canonical, rename-proof) and `folderPath` (human-readable snapshot resolved at batch creation time) regardless of which input was provided. The endpoint SHALL return `batchId`, `folderId`, `folderPath`, `fileCount`, and `files` regardless of which input was provided. Maximum batch size limits (admin-configurable via `docudesk_batch_max_files`) SHALL apply.

#### Scenario: Initiate folder analysis by folder path (existing behavior)
- **WHEN** an authenticated user calls `POST /api/anonymization/batch/folder` with `{ "folderPath": "/Documents/WOB-2024" }`
- **THEN** the system resolves the folder via `$userFolder->get("/Documents/WOB-2024")`
- **AND** enumerates all direct file children (skipping subdirectories)
- **AND** creates a batch with each file's fileId, fileName, and status "uploaded"
- **AND** stores both `folderId` (from the resolved node) and `folderPath: "/Documents/WOB-2024"` on the batch
- **AND** returns `{ batchId, folderId, folderPath: "/Documents/WOB-2024", fileCount: 5, files: [...] }`

#### Scenario: Initiate folder analysis by folder ID
- **WHEN** an authenticated user calls `POST /api/anonymization/batch/folder` with `{ "folderId": 12345 }`
- **THEN** the system resolves the folder via `$userFolder->getById(12345)`
- **AND** enumerates all direct file children (skipping subdirectories)
- **AND** creates a batch with each file's fileId, fileName, and status "uploaded"
- **AND** stores both `folderId: 12345` and `folderPath` (resolved snapshot, e.g. "/Documents/WOB-2024") on the batch
- **AND** returns `{ batchId, folderId: 12345, folderPath: "/Documents/WOB-2024", fileCount: 5, files: [...] }`

#### Scenario: Folder ID resolves to multiple mounts — prefer writable
- **WHEN** a user provides `{ "folderId": 12345 }` and the ID resolves to two nodes: a read-only group folder mount and a writable share mount
- **THEN** the system selects the writable share mount
- **AND** the batch is created successfully
- **AND** the stored `folderPath` reflects the path of the writable mount

#### Scenario: Folder ID resolves to a single read-only mount
- **WHEN** a user provides `{ "folderId": 12345 }` and the ID resolves to one read-only node
- **THEN** the system selects that node (falling back to readable when no writable node exists)
- **AND** the batch is created successfully
- **AND** the stored `folderPath` reflects the read-only mount's path

#### Scenario: Neither folderId nor folderPath provided
- **WHEN** a user calls `POST /api/anonymization/batch/folder` with an empty body or neither param
- **THEN** the system returns HTTP 400 with error "Either folderId or folderPath must be provided"

#### Scenario: Both folderId and folderPath provided
- **WHEN** a user calls `POST /api/anonymization/batch/folder` with `{ "folderId": 12345, "folderPath": "/Documents/WOB-2024" }`
- **THEN** the system returns HTTP 400 with error "Provide only one of folderId or folderPath"
- **AND** no batch is created

#### Scenario: Folder ID does not exist or is not accessible
- **WHEN** a user provides a `folderId` that does not resolve to any node in the user's tree (unknown ID, or ID belongs to another user's unshared folder)
- **THEN** the system returns HTTP 404 with error "Folder not found"

#### Scenario: Folder path does not exist
- **WHEN** a user provides a `folderPath` that does not exist in their Nextcloud files
- **THEN** the system returns HTTP 404 with error "Folder not found"

#### Scenario: ID points to a file, not a folder
- **WHEN** a user provides a `folderId` that resolves to a file node instead of a folder
- **THEN** the system returns HTTP 400 with error "Path is not a folder"

#### Scenario: Path points to a file, not a folder
- **WHEN** a user provides a `folderPath` that resolves to a file node instead of a folder
- **THEN** the system returns HTTP 400 with error "Path is not a folder"

#### Scenario: Folder is empty (by ID)
- **WHEN** a user provides a `folderId` pointing to an empty folder (no files, only subdirectories or nothing)
- **THEN** the system returns HTTP 400 with error "No files found in folder"

#### Scenario: Folder is empty (by path)
- **WHEN** a user provides a `folderPath` pointing to an empty folder (no files, only subdirectories or nothing)
- **THEN** the system returns HTTP 400 with error "No files found in folder"

#### Scenario: Folder exceeds maximum batch size (by ID)
- **WHEN** a folder resolved by `folderId` contains more files than the configured maximum (default 100)
- **THEN** the system returns HTTP 400 with error "Folder contains too many files (found: N, maximum: M)"

#### Scenario: Folder exceeds maximum batch size (by path)
- **WHEN** a folder resolved by `folderPath` contains more files than the configured maximum (default 100)
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

