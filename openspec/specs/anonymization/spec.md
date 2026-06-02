---
status: implementing
or_adoption_change: docudesk-adopt-or-abstractions
---

# Anonymization Pipeline

## Purpose

Provides a complete document anonymization pipeline: files are stored as **OR File Attachments**, text extraction and PII entity detection run via **OpenRegister's TextExtractionService**, and anonymization replaces detected entities with pseudonyms via OR's FileService. The pipeline runs 100% locally with no external cloud dependencies, ensuring GDPR/AVG compliance through privacy-by-design processing.

## OR Adoption decisions (from docudesk-adopt-or-abstractions)

- **Decision 4** — Anonymization consumes OR primitives, no replacement: OR's `TextExtractionService` and File Attachments cover the input side. The custom file-upload + entity-extraction flow is replaced by these primitives. The actual NLP/PII detection algorithms remain in docudesk (value-add). Custom plumbing is dropped.
- **Decision 3** — Anonymization-result confidence, risk-level, entity-density, and redaction-coverage are declared as `x-openregister-calculations` annotations on the file-attachment extension schema, NOT populated by ad-hoc writes in `AnonymizationService`. Service code calls `lifecycleService->transitionTo()` for state changes; OR derives calculated fields automatically.
- **Decision 2** — `anonymizationResult` objects (if stored separately) carry `x-openregister-archival.retention: P1Y` (Archiefwet cat. 1.2: operational processing logs). File attachments inherit OR's standard retention; DPO sign-off required for legal-hold override.
- **Decision 5** — Status strings on the wire stay the same (`uploaded`, `extracted`, `anonymized`). Lifecycle annotation maps these states; no renaming.

### Requirement: File Input via OR File Attachments (REQ-ANON-00)

**Priority:** Must

Users upload files and they are stored as OR File Attachments, not by docudesk-specific storage code. Virus-scan and MIME-validation hooks are inherited from OR.

#### Scenario: File persisted as OR File Attachment

- **GIVEN** a logged-in user uploads a file for anonymization
- **WHEN** `FileUploadService::upload()` stores the file
- **THEN** the file SHALL be persisted as an OR file attachment (not raw Nextcloud file API only)
- **AND** OR's MIME-validation and virus-scan hooks SHALL execute before the file is accepted
- **AND** the response includes the OR attachment `fileId` usable as a lookup key

#### Scenario: Virus-scan rejected file

- **GIVEN** a file triggers OR's virus-scan hook
- **WHEN** the hook returns `BLOCKED`
- **THEN** the upload SHALL be rejected with HTTP 422 — "File rejected by security scan"
- **AND** no docudesk record SHALL reference the rejected file

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-000 | Files persisted as OR File Attachments | MUST | Apply-phase |
| ANON-000a | MIME-validation and virus-scan hooks execute on ingest | MUST | Apply-phase |
| ANON-000b | Upload response uses OR attachment fileId as lookup key | MUST | Apply-phase |

### Requirement: Anonymization Confidence is a Calculation (REQ-ANON-CAL)

**Priority:** Must

`anonymizationConfidence`, `riskScore`, `riskLevel`, `entityDensity`, and `redactionCoverage` are declared as `x-openregister-calculations` on the file-attachment extension schema. `AnonymizationService` SHALL NOT write these fields directly; OR derives them from the calculation expression after entity detection completes.

#### Scenario: Risk level derived, not written

- **GIVEN** `x-openregister-calculations.riskLevel` is declared on the file-attachment schema
- **WHEN** entity detection completes and entity objects are persisted
- **THEN** `riskLevel` SHALL be derived by OR from the entity array
- **AND** `AnonymizationService::detectEntities()` SHALL NOT contain a `$result['riskLevel'] = ...` write

#### Scenario: Redaction coverage computed after anonymization

- **GIVEN** `x-openregister-calculations.redactionCoverage` is declared
- **WHEN** anonymization finishes and the anonymized file is stored
- **THEN** `redactionCoverage` SHALL equal `anonymizedEntityCount / totalEntityCount`
- **AND** this value SHALL be available on the file-attachment object without a service write

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-CAL-001 | `x-openregister-calculations` declared for: anonymizationConfidence, riskScore, riskLevel, entityDensity, redactionCoverage | MUST | Implementing |
| ANON-CAL-002 | AnonymizationService does not contain direct writes to calculated fields | MUST | Apply-phase |
| ANON-CAL-003 | OR PR raised if file-attachment schema is upstream and needs the extension | SHOULD | Apply-phase |
## Requirements
### Requirement: File Upload to User-Scoped Folder (REQ-ANON-01)

**Priority:** Must

Users upload files via multipart form data, and files are stored in a per-user DocuDesk folder within Nextcloud Files.

#### Scenario: Successful file upload
@e2e exclude multipart POST /api/anonymization/upload response shape — FileUploadService verified by PHPUnit; UI upload flow covered by complete-anonymization-workflow test
- GIVEN a logged-in user
- WHEN they upload a PDF file via `POST /api/anonymization/upload`
- THEN the file is stored in their `DocuDesk/` subfolder in Nextcloud Files
- AND the response includes fileId, filePath, fileName, and fileSize

#### Scenario: Auto-create DocuDesk folder
@e2e exclude IRootFolder folder-creation side-effect — FileUploadService verified by PHPUnit; folder auto-creation not separately observable in UI
- GIVEN a logged-in user who has never used DocuDesk
- AND no `DocuDesk/` folder exists in their Nextcloud files
- WHEN they upload their first file
- THEN the `DocuDesk/` subfolder is created automatically
- AND the file is stored in the new folder

#### Scenario: Duplicate file name handling
@e2e exclude filename deduplication algorithm — FileUploadService collision logic verified by PHPUnit; not directly observable in UI without pre-existing file collision setup
- GIVEN a user's DocuDesk folder already contains `report.pdf`
- WHEN they upload another file named `report.pdf`
- THEN the file is saved as `report_1.pdf`
- AND subsequent duplicates increment the counter (`report_2.pdf`, etc.)

#### Scenario: No file uploaded
@e2e exclude backend input validation — AnonymizationController 400 response verified by PHPUnit; UI upload widget prevents empty submissions
- GIVEN a user submits the upload endpoint with no file attached
- WHEN the controller checks for uploaded file data
- THEN a 400 response is returned with "No file uploaded"

#### Scenario: PHP upload error
@e2e exclude PHP upload error code propagation — backend error handling verified by PHPUnit; not reproducible via normal UI interaction
- GIVEN a user submits a file that triggers a PHP upload error (e.g., exceeds max size)
- WHEN the controller checks `$file['error']`
- THEN a 400 response is returned with "File upload failed with error code: {code}"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-001 | Users can upload files via multipart form data to `POST /api/anonymization/upload` | MUST | Implemented |
| ANON-002 | Uploaded files are stored in the user's `DocuDesk/` subfolder in Nextcloud Files | MUST | Implemented |
| ANON-003 | The DocuDesk subfolder is created automatically if it does not exist | MUST | Implemented |
| ANON-004 | Duplicate file names are handled by appending an incrementing counter | MUST | Implemented |
| ANON-005 | Upload response includes fileId, filePath, fileName, and fileSize | MUST | Implemented |
| ANON-006 | Upload requires an authenticated user session | MUST | Implemented |

### Requirement: Text Extraction and Entity Detection (REQ-ANON-02)

Text is extracted from uploaded documents and entities are detected via the OpenRegister NER pipeline. The extraction response SHALL include a `riskLevel` field summarising the privacy risk of the detected entities.

#### Scenario: Extract with risk level
- **WHEN** extraction is performed via `POST /api/anonymization/extract/{fileId}`
- **THEN** the response includes a `riskLevel` field derived from the detected entities

### Requirement: Document Anonymization with Entity Replacement (REQ-ANON-03)

Detected entities are replaced with anonymized placeholders in the document, producing an anonymized copy. The anonymization endpoint SHALL additionally accept optional `excludeTypes` and `minConfidence` parameters so callers can narrow which detected entities are replaced.

#### Scenario: Anonymize with entity type exclusion
- **WHEN** `excludeTypes=["ORGANIZATION"]` is provided to `POST /api/anonymization/anonymize/{fileId}`
- **THEN** ORGANIZATION entities are excluded from replacement
- **AND** all other detected entity types are still anonymized

#### Scenario: Anonymize with a minimum confidence threshold
- **WHEN** `minConfidence=0.7` is provided
- **THEN** entities whose detection confidence is below 0.7 are excluded from replacement

### Requirement: Processed File Listing with Risk Assessment (REQ-ANON-04)

**Priority:** Must

List all files in the user's DocuDesk folder with entity counts, anonymization status, and risk level assessment.

#### Scenario: List files with entity counts and status
@e2e exclude FileListingService API response content — requires pre-processed files in DocuDesk/ folder; GET /api/anonymization/files verified by PHPUnit
- GIVEN a user has 5 files in their DocuDesk folder (2 extracted, 1 anonymized, 2 uploaded)
- WHEN they request `GET /api/anonymization/files`
- THEN all 5 files are returned sorted by modification time (newest first)
- AND each file includes entityCount, anonymizedCount, and status

#### Scenario: Risk level assessment per file
@e2e exclude RiskLevelService integration — requires real OR RiskLevelService and processed files; verified by PHPUnit integration tests
- GIVEN a file with 7 detected entities including 5 PERSON entities
- WHEN the file listing is generated
- THEN the file includes a riskLevel from OpenRegister's RiskLevelService
- AND the risk level reflects the number and type of detected PII

#### Scenario: File listing with unavailable OpenRegister services
@e2e exclude graceful degradation catch-block — RuntimeException fallback verified by PHPUnit; not reproducible in env with OR installed
- GIVEN OpenRegister's EntityRelationMapper or RiskLevelService is unavailable
- WHEN the file listing is generated
- THEN the listing continues with default values (entityCount: 0, riskLevel: "unknown")
- AND a RuntimeException is caught and logged but does not crash the listing

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-030 | List all files in the user's DocuDesk folder via `GET /api/anonymization/files` | MUST | Implemented |
| ANON-031 | Each file includes entityCount, anonymizedCount, and status (uploaded/extracted/anonymized) | MUST | Implemented |
| ANON-032 | Each file includes riskLevel from OpenRegister's RiskLevelService | MUST | Implemented |
| ANON-033 | Files are sorted by modification time descending (newest first) | MUST | Implemented |
| ANON-034 | File listing includes fileId, fileName, filePath, fileSize, and mimeType | MUST | Implemented |
| ANON-035 | Only actual files are listed (directories are skipped) | MUST | Implemented |

### Requirement: Lazy OpenRegister Service Resolution (REQ-ANON-05)

**Priority:** Must

AnonymizationService lazily resolves OpenRegister services at call time to gracefully handle the case where OpenRegister is not installed.

#### Scenario: Service resolution when OpenRegister is installed
@e2e exclude lazy DI resolution pattern — AnonymizationService container->get() pattern verified by PHPUnit; not directly observable in UI
- GIVEN OpenRegister is installed and enabled
- WHEN `extractAndDetectEntities()` is called
- THEN TextExtractionService is lazily resolved via `container->get()`
- AND EntityRelationMapper is lazily resolved via `container->get()`

#### Scenario: Service resolution when OpenRegister is not installed
@e2e exclude RuntimeException on missing OR — not reproducible in env with OR installed; verified by PHPUnit
- GIVEN OpenRegister is not installed
- WHEN any anonymization operation is attempted
- THEN `getOpenRegisterService()` throws RuntimeException
- AND the exception message identifies which service is unavailable

#### Scenario: Graceful degradation in file listing
@e2e exclude EntityRelationMapper failure catch-block — not reproducible in env with OR installed; verified by PHPUnit
- GIVEN OpenRegister is installed but EntityRelationMapper fails
- WHEN file listing is requested
- THEN the RuntimeException is caught
- AND files are listed with default entity counts (0)
- AND the listing remains functional

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-040 | Service getters use `getInstalledApps()` to check OpenRegister availability before resolving | MUST | Implemented |
| ANON-041 | Service getters throw `\RuntimeException` when OpenRegister is unavailable | MUST | Implemented |
| ANON-042 | Service resolution is lazy (per-call), not eagerly loaded at construction time | MUST | Implemented |
| ANON-043 | `listProcessedFiles()` gracefully handles unavailable services by catching RuntimeException | MUST | Implemented |

### Requirement: UUID v4 Generation for Anonymization Keys (REQ-ANON-06)

**Priority:** Must

Each anonymized entity is assigned a cryptographically secure UUID v4 key as its replacement identifier.

#### Scenario: UUID key generation
@e2e exclude cryptographic UUID generation — random_bytes(16) UUID generation verified by PHPUnit unit tests
- GIVEN 5 entities are being mapped for anonymization
- WHEN UUIDs are generated for each entity
- THEN each entity receives a unique UUID v4 string
- AND the UUID conforms to RFC 4122 version 4 format
- AND the UUIDs are generated using `random_bytes(16)` for cryptographic security

#### Scenario: UUID uniqueness
@e2e exclude UUID collision probability — statistical property verified by PHPUnit; not UI-observable
- GIVEN multiple entities are processed in a single anonymization request
- WHEN UUIDs are generated
- THEN each UUID is unique (collision probability is negligible with 122 bits of entropy)

#### Scenario: UUID format verification
@e2e exclude UUID RFC 4122 format — regex verification in PHPUnit; not UI-observable
- GIVEN a generated UUID
- THEN it matches the pattern `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx` where y is 8, 9, a, or b

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-044 | UUID keys are generated using cryptographically secure random bytes (`random_bytes`) | MUST | Implemented |
| ANON-045 | Generated UUIDs conform to RFC 4122 version 4 format | MUST | Implemented |
| ANON-046 | Each entity gets a unique UUID key in the anonymization mapping | MUST | Implemented |

### Requirement: User Session Authentication (REQ-ANON-07)

**Priority:** Must

File operations require an authenticated user session to scope files to the correct user's folder.

#### Scenario: No user session on upload
@e2e exclude Nextcloud session authentication gate — AnonymizationController 401 path verified by PHPUnit; NC itself handles unauthenticated requests
- GIVEN a request hits the upload endpoint without an authenticated user
- WHEN `getCurrentUserId()` is called
- THEN an Exception is thrown with message "No user is currently logged in." and code 401
- AND the controller returns a 401 status code

#### Scenario: No user session on file listing
@e2e exclude Nextcloud session authentication gate — 401 path verified by PHPUnit; NC itself handles unauthenticated requests
- GIVEN a request hits the files endpoint without an authenticated user
- WHEN `getCurrentUserId()` is called
- THEN a 401 response is returned

#### Scenario: Extract/anonymize endpoints do not check user session
@e2e exclude absence of getCurrentUserId() call in extract/anonymize — code structure assertion verified by PHPUnit/code inspection
- GIVEN the extract and anonymize endpoints accept a fileId parameter
- WHEN these endpoints are called
- THEN they do not call `getCurrentUserId()` directly
- AND any exception results in a 500 status code (no HTTP status code propagation)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-047 | `getCurrentUserId()` throws `Exception` with code 401 when no user session exists | MUST | Implemented |
| ANON-048 | `files()` and `upload()` correctly propagate 401; `extract()` and `anonymize()` return 500 on exception | MUST | Partial |

### Requirement: Anonymization Pipeline UI (REQ-ANON-08)

**Priority:** Must

The frontend provides a step-by-step UI for the complete anonymization workflow with drag-and-drop upload, progress tracking, and result display.

#### Scenario: Complete anonymization workflow in UI
- GIVEN a logged-in user navigates to the Anonymization view
- WHEN they drag a PDF file onto the upload zone
- THEN a step indicator shows progress through Upload, Analyze, Anonymize, Done
- AND the file is uploaded, extracted, and anonymized sequentially
- AND the result displays replacement count, anonymized file info, and entity table

#### Scenario: Error during anonymization
- GIVEN a file is being processed in the anonymization pipeline
- WHEN text extraction fails with an error
- THEN an error NcNoteCard is displayed with the error message
- AND a "Try Again" button is shown

#### Scenario: Anonymize another document
- GIVEN the previous anonymization completed successfully
- WHEN the user clicks "Anonymize Another"
- THEN the interface resets to the upload state
- AND the previous result is cleared

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-050 | Return 400 with "No file uploaded" when request contains no file | MUST | Implemented |
| ANON-051 | Return 400 with PHP error code when file upload has a PHP error | MUST | Implemented |
| ANON-052 | Return 500 with "Failed to read uploaded file" when temp file cannot be read | MUST | Implemented |
| ANON-053 | Return 400 with "No entities provided for anonymization" when entities array is empty | MUST | Implemented |

### Requirement: EntityRelationMapper Method Usage (REQ-ANON-09)

**Priority:** Must

Two distinct EntityRelationMapper methods serve different purposes in the pipeline: entity detail retrieval vs. relation counting.

#### Scenario: Entity details during extraction
@e2e exclude EntityRelationMapper::findEntitiesForFile() method selection — internal OR mapper API usage verified by PHPUnit
- GIVEN a file has been text-extracted with entity recognition
- WHEN `extractAndDetectEntities()` retrieves entity details
- THEN `findEntitiesForFile()` returns rich entity data (type, value, confidence)
- AND these are the detected entities themselves

#### Scenario: Entity counts during file listing
@e2e exclude EntityRelationMapper::findByFileId() method selection — internal OR mapper API usage verified by PHPUnit
- GIVEN a user requests the file listing
- WHEN entity counts are computed for each file
- THEN `findByFileId()` returns relation records linking files to entities
- AND each relation exposes `getAnonymized()` for per-entity anonymization tracking
- AND entityCount and anonymizedCount are derived from these relations

#### Scenario: Different return types for different contexts
@e2e exclude dual-mapper-method architectural decision — internal service design verified by PHPUnit; not UI-observable
- GIVEN both mapper methods are available
- WHEN extraction pipeline needs entity details it uses `findEntitiesForFile()`
- AND when file listing needs counts it uses `findByFileId()`
- THEN the appropriate method is used for each context

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-054 | Entity extraction uses `findEntitiesForFile()` for entity details | MUST | Implemented |
| ANON-055 | File listing uses `findByFileId()` for entity counts and status | MUST | Implemented |
| ANON-056 | `findByFileId()` relations expose `getAnonymized()` for per-entity tracking | MUST | Implemented |

### Requirement: Frontend File Processing Queue (REQ-ANON-10)

**Priority:** Must

The Pinia store manages a sequential file processing queue with status tracking through the pipeline stages.

#### Scenario: Sequential file processing
@e2e exclude multi-file queue sequencing — requires multiple simultaneous uploads; single-file flow covered by complete-anonymization-workflow test; store queue logic unit-testable
- GIVEN 3 files are queued for anonymization
- WHEN processing begins
- THEN files are processed sequentially (one at a time)
- AND each file transitions through: queued -> uploading -> extracting -> anonymizing -> completed

#### Scenario: Error state in queue
@e2e exclude Pinia queue error-state transition — unit-testable store behavior; not reliably injectable via UI without backend failure injection
- GIVEN a file fails during the extracting stage
- WHEN the error is caught
- THEN the file status is set to `error`
- AND the next file in the queue begins processing

#### Scenario: Queue state getters
@e2e exclude Pinia store getter shape — unit-testable; UI observes rendered state (covered by complete-anonymization-workflow test)
- GIVEN the anonymization store has files in various states
- WHEN the UI queries the store
- THEN `hasFiles`, `hasCompleted`, `allDone`, and `isProcessing` getters provide accurate state

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-057 | Pinia store manages file processing queue with sequential processing | MUST | Implemented |
| ANON-058 | Each file tracks status: queued, uploading, extracting, anonymizing, completed, or error | MUST | Implemented |
| ANON-059 | Store provides getters: hasFiles, hasCompleted, allDone, isProcessing | MUST | Implemented |

## Data Model

### File Entry (API response)

| Field | Type | Description |
|-------|------|-------------|
| fileId | integer | Nextcloud file ID |
| fileName | string | File name |
| filePath | string | Full path in Nextcloud |
| fileSize | integer | File size in bytes |
| mimeType | string | MIME type |
| entityCount | integer | Number of detected entities |
| anonymizedCount | integer | Number of anonymized entities |
| status | string | `uploaded`, `extracted`, or `anonymized` |
| riskLevel | string | Risk level from RiskLevelService |
| modified | integer | File modification timestamp |

### Detected Entity (normalized)

| Field | Type | Description |
|-------|------|-------------|
| type | string | Entity type (e.g., PERSON, ORGANIZATION, EMAIL, PHONE) |
| value | string | Detected entity text |
| confidence | float | Detection confidence score (0.0 - 1.0) |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/anonymization/files` | List processed files |
| POST | `/api/anonymization/upload` | Upload file (multipart/form-data) |
| POST | `/api/anonymization/extract/{fileId}` | Extract text and detect entities |
| POST | `/api/anonymization/anonymize/{fileId}` | Anonymize document |

## Dependencies

- **OpenRegister TextExtractionService**: Text extraction and entity recognition
- **OpenRegister FileService**: File node access and document anonymization
- **OpenRegister EntityRelationMapper**: Entity relation queries per file
- **OpenRegister RiskLevelService**: Risk level assessment per file
- **Nextcloud IRootFolder**: File storage in user folders
- **Nextcloud IUserSession**: Current user identification
- **Nextcloud IAppManager**: Checking OpenRegister installation status
- **PSR ContainerInterface**: Lazy service resolution

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/AnonymizationService.php` -- core pipeline orchestration
  - `lib/Service/EntityDetectionService.php` -- entity normalization and mapping
  - `lib/Service/AnonymizationResultParser.php` -- result parsing
  - `lib/Service/FileListingService.php` -- file listing with entity counts
  - `lib/Service/FileUploadService.php` -- file upload handling
  - `lib/Controller/AnonymizationController.php` -- REST API endpoints
  - `src/views/anonymization/AnonymizationWidget.vue` -- drag-and-drop upload UI
  - `src/store/modules/anonymization.js` -- Pinia store with file queue

### Standards & References
- **GDPR/AVG Article 4(5)**: Pseudonymization definition; Article 89 and Recital 26 on anonymization
- **RFC 4122**: UUID v4 generation for anonymization replacement keys
- **WOO (Wet open overheid)**: Anonymization pipeline supports WOO document redaction
- **NEN-ISO/IEC 27001**: Data minimization and anonymization controls
