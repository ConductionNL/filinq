## MODIFIED Requirements

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
