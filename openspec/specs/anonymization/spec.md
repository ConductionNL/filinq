---
status: in-progress
---

# Anonymization Pipeline

<!-- OpenSpec changes: odt-anonymisation-frontend (ODT accepted by the upload widget) -->


## Purpose

Provides a complete document anonymization pipeline: upload files to a user-scoped DocuDesk folder, extract text and detect personally identifiable entities (PII) using OpenRegister's TextExtractionService, and anonymize the document by replacing detected entities with placeholders via OpenRegister's FileService. The pipeline runs 100% locally with no external cloud dependencies, ensuring GDPR/AVG compliance through privacy-by-design processing.

## Requirements

### REQ-ANON-01: File Upload to User-Scoped Folder (Priority: Must)

Users upload files via multipart form data, and files are stored in a per-user DocuDesk folder within Nextcloud Files.

#### Scenario: Successful file upload
- GIVEN a logged-in user
- WHEN they upload a PDF file via `POST /api/anonymization/upload`
- THEN the file is stored in their `DocuDesk/` subfolder in Nextcloud Files
- AND the response includes fileId, filePath, fileName, and fileSize

#### Scenario: Auto-create DocuDesk folder
- GIVEN a logged-in user who has never used DocuDesk
- AND no `DocuDesk/` folder exists in their Nextcloud files
- WHEN they upload their first file
- THEN the `DocuDesk/` subfolder is created automatically
- AND the file is stored in the new folder

#### Scenario: Duplicate file name handling
- GIVEN a user's DocuDesk folder already contains `report.pdf`
- WHEN they upload another file named `report.pdf`
- THEN the file is saved as `report_1.pdf`
- AND subsequent duplicates increment the counter (`report_2.pdf`, etc.)

#### Scenario: No file uploaded
- GIVEN a user submits the upload endpoint with no file attached
- WHEN the controller checks for uploaded file data
- THEN a 400 response is returned with "No file uploaded"

#### Scenario: PHP upload error
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

### REQ-ANON-02: Text Extraction and Entity Detection (Priority: Must)

Text is extracted from uploaded documents and entities (persons, organizations, emails, phone numbers) are detected using OpenRegister's NER capabilities.

#### Scenario: Extract entities from a document with PII
- GIVEN an uploaded PDF containing names, email addresses, and organization names
- WHEN entity extraction is triggered via `POST /api/anonymization/extract/{fileId}`
- THEN text is extracted using OpenRegister's `TextExtractionService::extractFile()`
- AND entities are detected with type (PERSON, ORGANIZATION, EMAIL, etc.)
- AND each entity includes a confidence score (0.0 - 1.0)
- AND the response includes the entities array and entityCount

#### Scenario: Extract entities from a clean document
- GIVEN an uploaded document containing no personally identifiable information
- WHEN entity extraction is triggered
- THEN the response shows entityCount of 0
- AND the entities array is empty

#### Scenario: Entity normalization
- GIVEN OpenRegister returns entities with varying field names (entity_type/entityType, entity_value/entityValue)
- WHEN entities are normalized by EntityDetectionService
- THEN all entities have consistent format: `type`, `value`, `confidence`

#### Scenario: OpenRegister unavailable during extraction
- GIVEN OpenRegister is not installed
- WHEN entity extraction is triggered
- THEN a RuntimeException is thrown
- AND the controller returns a 500 error with descriptive message

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-010 | Text extraction is performed via OpenRegister's `TextExtractionService::extractFile()` | MUST | Implemented |
| ANON-011 | Entity recognition runs during text extraction using the method configured in OpenRegister | MUST | Implemented |
| ANON-012 | Full entity details are retrieved via `EntityRelationMapper::findEntitiesForFile()` | MUST | Implemented |
| ANON-013 | Entities are normalized to a consistent format: `type`, `value`, `confidence` | MUST | Implemented |
| ANON-014 | Extraction endpoint is `POST /api/anonymization/extract/{fileId}` | MUST | Implemented |
| ANON-015 | Response includes `entities` array and `entityCount` | MUST | Implemented |

### REQ-ANON-03: Document Anonymization with Entity Replacement (Priority: Must)

Detected entities are replaced with anonymized placeholders in the document, producing an anonymized copy.

#### Scenario: Anonymize a document with detected entities
- GIVEN extracted entities for a file (3 PERSON entities, 1 ORGANIZATION entity)
- WHEN anonymization is triggered via `POST /api/anonymization/anonymize/{fileId}` with entities array
- THEN an anonymized copy of the document is created
- AND each entity mention is replaced with a placeholder (e.g., [PERSON: a1b2c3d4])
- AND the response includes anonymizedFileId, anonymizedFileName, anonymizedFilePath, and replacementCount

#### Scenario: Short entity values are skipped
- GIVEN entity list includes a value "AB" (2 characters)
- WHEN entities are mapped for anonymization
- THEN the short value is skipped (minimum 3 characters required)
- AND only entities with 3+ character values are processed

#### Scenario: Numeric entity values are skipped
- GIVEN entity list includes a purely numeric value "12345"
- WHEN entities are mapped for anonymization
- THEN the numeric value is skipped to prevent PHP array key type coercion issues
- AND only string entity values are processed

#### Scenario: Duplicate entities are deduplicated
- GIVEN the entity list contains "Ruben van der Linde" twice
- WHEN entities are mapped for anonymization
- THEN only one replacement mapping is created for that value
- AND the deduplication uses a seen-set to track processed values

#### Scenario: Empty entities validation
- GIVEN a user calls the anonymize endpoint with an empty entities array
- WHEN the controller validates the request
- THEN a 400 response is returned with "No entities provided for anonymization"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-020 | Anonymization replaces detected entities with placeholders via `FileService::anonymizeDocument()` | MUST | Implemented |
| ANON-021 | Entity values shorter than 3 characters are skipped | MUST | Implemented |
| ANON-022 | Purely numeric entity values are skipped | MUST | Implemented |
| ANON-023 | Duplicate entity values are deduplicated before anonymization | MUST | Implemented |
| ANON-024 | Each entity is assigned a unique UUID v4 key for the anonymization mapping | MUST | Implemented |
| ANON-025 | Anonymization endpoint is `POST /api/anonymization/anonymize/{fileId}` with entities array in request body | MUST | Implemented |
| ANON-026 | Response includes anonymizedFileId, anonymizedFileName, anonymizedFilePath, and replacementCount | MUST | Implemented |

### REQ-ANON-04: Processed File Listing with Risk Assessment (Priority: Must)

List all files in the user's DocuDesk folder with entity counts, anonymization status, and risk level assessment.

#### Scenario: List files with entity counts and status
- GIVEN a user has 5 files in their DocuDesk folder (2 extracted, 1 anonymized, 2 uploaded)
- WHEN they request `GET /api/anonymization/files`
- THEN all 5 files are returned sorted by modification time (newest first)
- AND each file includes entityCount, anonymizedCount, and status

#### Scenario: Risk level assessment per file
- GIVEN a file with 7 detected entities including 5 PERSON entities
- WHEN the file listing is generated
- THEN the file includes a riskLevel from OpenRegister's RiskLevelService
- AND the risk level reflects the number and type of detected PII

#### Scenario: File listing with unavailable OpenRegister services
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

### REQ-ANON-05: Lazy OpenRegister Service Resolution (Priority: Must)

AnonymizationService lazily resolves OpenRegister services at call time to gracefully handle the case where OpenRegister is not installed.

#### Scenario: Service resolution when OpenRegister is installed
- GIVEN OpenRegister is installed and enabled
- WHEN `extractAndDetectEntities()` is called
- THEN TextExtractionService is lazily resolved via `container->get()`
- AND EntityRelationMapper is lazily resolved via `container->get()`

#### Scenario: Service resolution when OpenRegister is not installed
- GIVEN OpenRegister is not installed
- WHEN any anonymization operation is attempted
- THEN `getOpenRegisterService()` throws RuntimeException
- AND the exception message identifies which service is unavailable

#### Scenario: Graceful degradation in file listing
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

### REQ-ANON-06: UUID v4 Generation for Anonymization Keys (Priority: Must)

Each anonymized entity is assigned a cryptographically secure UUID v4 key as its replacement identifier.

#### Scenario: UUID key generation
- GIVEN 5 entities are being mapped for anonymization
- WHEN UUIDs are generated for each entity
- THEN each entity receives a unique UUID v4 string
- AND the UUID conforms to RFC 4122 version 4 format
- AND the UUIDs are generated using `random_bytes(16)` for cryptographic security

#### Scenario: UUID uniqueness
- GIVEN multiple entities are processed in a single anonymization request
- WHEN UUIDs are generated
- THEN each UUID is unique (collision probability is negligible with 122 bits of entropy)

#### Scenario: UUID format verification
- GIVEN a generated UUID
- THEN it matches the pattern `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx` where y is 8, 9, a, or b

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-044 | UUID keys are generated using cryptographically secure random bytes (`random_bytes`) | MUST | Implemented |
| ANON-045 | Generated UUIDs conform to RFC 4122 version 4 format | MUST | Implemented |
| ANON-046 | Each entity gets a unique UUID key in the anonymization mapping | MUST | Implemented |

### REQ-ANON-07: User Session Authentication (Priority: Must)

File operations require an authenticated user session to scope files to the correct user's folder.

#### Scenario: No user session on upload
- GIVEN a request hits the upload endpoint without an authenticated user
- WHEN `getCurrentUserId()` is called
- THEN an Exception is thrown with message "No user is currently logged in." and code 401
- AND the controller returns a 401 status code

#### Scenario: No user session on file listing
- GIVEN a request hits the files endpoint without an authenticated user
- WHEN `getCurrentUserId()` is called
- THEN a 401 response is returned

#### Scenario: Extract/anonymize endpoints do not check user session
- GIVEN the extract and anonymize endpoints accept a fileId parameter
- WHEN these endpoints are called
- THEN they do not call `getCurrentUserId()` directly
- AND any exception results in a 500 status code (no HTTP status code propagation)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-047 | `getCurrentUserId()` throws `Exception` with code 401 when no user session exists | MUST | Implemented |
| ANON-048 | `files()` and `upload()` correctly propagate 401; `extract()` and `anonymize()` return 500 on exception | MUST | Partial |

### REQ-ANON-08: Anonymization Pipeline UI (Priority: Must)

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

### REQ-ANON-09: EntityRelationMapper Method Usage (Priority: Must)

Two distinct EntityRelationMapper methods serve different purposes in the pipeline: entity detail retrieval vs. relation counting.

#### Scenario: Entity details during extraction
- GIVEN a file has been text-extracted with entity recognition
- WHEN `extractAndDetectEntities()` retrieves entity details
- THEN `findEntitiesForFile()` returns rich entity data (type, value, confidence)
- AND these are the detected entities themselves

#### Scenario: Entity counts during file listing
- GIVEN a user requests the file listing
- WHEN entity counts are computed for each file
- THEN `findByFileId()` returns relation records linking files to entities
- AND each relation exposes `getAnonymized()` for per-entity anonymization tracking
- AND entityCount and anonymizedCount are derived from these relations

#### Scenario: Different return types for different contexts
- GIVEN both mapper methods are available
- WHEN extraction pipeline needs entity details it uses `findEntitiesForFile()`
- AND when file listing needs counts it uses `findByFileId()`
- THEN the appropriate method is used for each context

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-054 | Entity extraction uses `findEntitiesForFile()` for entity details | MUST | Implemented |
| ANON-055 | File listing uses `findByFileId()` for entity counts and status | MUST | Implemented |
| ANON-056 | `findByFileId()` relations expose `getAnonymized()` for per-entity tracking | MUST | Implemented |

### REQ-ANON-10: Frontend File Processing Queue (Priority: Must)

The Pinia store manages a sequential file processing queue with status tracking through the pipeline stages.

#### Scenario: Sequential file processing
- GIVEN 3 files are queued for anonymization
- WHEN processing begins
- THEN files are processed sequentially (one at a time)
- AND each file transitions through: queued -> uploading -> extracting -> anonymizing -> completed

#### Scenario: Error state in queue
- GIVEN a file fails during the extracting stage
- WHEN the error is caught
- THEN the file status is set to `error`
- AND the next file in the queue begins processing

#### Scenario: Queue state getters
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
