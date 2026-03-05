---
status: reviewed
---

# Anonymization Pipeline

## Purpose

Provides a complete document anonymization pipeline: upload files to a user-scoped DocuDesk folder, extract text and detect personally identifiable entities (PII) using OpenRegister's TextExtractionService, and anonymize the document by replacing detected entities with placeholders via OpenRegister's FileService. The pipeline runs 100% locally with no external cloud dependencies.

## Requirements

### File Upload

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-001 | Users can upload files via multipart form data to `POST /api/anonymization/upload` | MUST | Implemented |
| ANON-002 | Uploaded files are stored in the user's `DocuDesk/` subfolder in Nextcloud Files | MUST | Implemented |
| ANON-003 | The DocuDesk subfolder is created automatically if it does not exist | MUST | Implemented |
| ANON-004 | Duplicate file names are handled by appending an incrementing counter (e.g., `file_1.pdf`, `file_2.pdf`) | MUST | Implemented |
| ANON-005 | Upload response includes fileId, filePath, fileName, and fileSize | MUST | Implemented |
| ANON-006 | Upload requires an authenticated user session | MUST | Implemented |

### Entity Extraction

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-010 | Text extraction is performed via OpenRegister's `TextExtractionService::extractFile()` | MUST | Implemented |
| ANON-011 | Entity recognition runs during text extraction using the method configured in OpenRegister file settings (Presidio, OpenAnonymiser, or hybrid) | MUST | Implemented |
| ANON-012 | Full entity details are retrieved via `EntityRelationMapper::findEntitiesForFile()` | MUST | Implemented |
| ANON-013 | Entities are normalized to a consistent format: `type`, `value`, `confidence` | MUST | Implemented |
| ANON-014 | Extraction endpoint is `POST /api/anonymization/extract/{fileId}` | MUST | Implemented |
| ANON-015 | Response includes `entities` array and `entityCount` | MUST | Implemented |

### Document Anonymization

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-020 | Anonymization replaces detected entities with anonymized placeholders via `FileService::anonymizeDocument()` | MUST | Implemented |
| ANON-021 | Entity values shorter than 3 characters are skipped | MUST | Implemented |
| ANON-022 | Purely numeric entity values are skipped (prevents PHP array key type coercion) | MUST | Implemented |
| ANON-023 | Duplicate entity values are deduplicated before anonymization | MUST | Implemented |
| ANON-024 | Each entity is assigned a unique UUID key for the anonymization mapping | MUST | Implemented |
| ANON-025 | Anonymization endpoint is `POST /api/anonymization/anonymize/{fileId}` with entities array in request body | MUST | Implemented |
| ANON-026 | Response includes anonymizedFileId, anonymizedFileName, anonymizedFilePath, and replacementCount | MUST | Implemented |

### Processed File Listing

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-030 | List all files in the user's DocuDesk folder via `GET /api/anonymization/files` | MUST | Implemented |
| ANON-031 | Each file includes entityCount, anonymizedCount, and status (uploaded/extracted/anonymized) | MUST | Implemented |
| ANON-032 | Each file includes riskLevel from OpenRegister's RiskLevelService | MUST | Implemented |
| ANON-033 | Files are sorted by modification time descending (newest first) | MUST | Implemented |
| ANON-034 | File listing includes fileId, fileName, filePath, fileSize, and mimeType | MUST | Implemented |
| ANON-035 | Only actual files are listed (directories are skipped) | MUST | Implemented |

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

## User Interface

### Anonymization View (`AnonymizationWidget.vue`)

- **Step indicator bar**: 4 steps (Upload, Analyze, Anonymize, Done) with visual progress
- **Upload state**: Drag-and-drop zone with file browser fallback, progress bar during upload
- **Extracting state**: Loading spinner with "Analyzing document for personal data..." message
- **Anonymizing state**: Loading spinner with entity count display
- **Completed state**: Success note card with replacement count, anonymized file info, entity table (type/value/confidence), and "Anonymize Another" button
- **Error state**: Error note card with "Try Again" button

### Frontend Store (`anonymization.js`)

- Pinia store managing a file processing queue
- Each file tracks status through: `queued` -> `uploading` -> `extracting` -> `anonymizing` -> `completed` (or `error`)
- Sequential processing of queued files
- Getters: `hasFiles`, `hasCompleted`, `allDone`, `isProcessing`

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/anonymization/files` | List processed files |
| POST | `/api/anonymization/upload` | Upload file (multipart/form-data) |
| POST | `/api/anonymization/extract/{fileId}` | Extract text and detect entities |
| POST | `/api/anonymization/anonymize/{fileId}` | Anonymize document |

## Scenarios

### Upload and Anonymize a Document

```
GIVEN a logged-in user
WHEN they upload a PDF file via the anonymization interface
THEN the file is stored in their DocuDesk/ folder
AND a fileId is returned

GIVEN an uploaded file with fileId
WHEN entity extraction is triggered
THEN text is extracted using OpenRegister TextExtractionService
AND entities are detected and returned with type, value, and confidence

GIVEN extracted entities for a file
WHEN anonymization is triggered with the entity list
THEN an anonymized copy of the document is created
AND entity mentions are replaced with placeholders
AND the anonymized file details are returned
```

### List Processed Files

```
GIVEN a user with files in their DocuDesk/ folder
WHEN they request the file listing
THEN all files are returned with entity counts and anonymization status
AND files are sorted by most recently modified first
AND each file includes its risk level assessment
```

### Handle File Without Entities

```
GIVEN an uploaded file with no detectable entities
WHEN entity extraction completes
THEN the response shows entityCount of 0
AND the file status is set to completed without anonymization
```

### Upload Validation Errors (Gap 14)

```
GIVEN a user submits the upload endpoint with no file attached
WHEN the controller checks for uploaded file data
THEN a 400 response is returned with "No file uploaded"

GIVEN a user submits a file that triggers a PHP upload error
WHEN the controller checks $file['error']
THEN a 400 response is returned with "File upload failed with error code: {code}"
AND the raw PHP upload error constant (1-8) is included

GIVEN a file upload where the temp file cannot be read
WHEN file_get_contents($file['tmp_name']) returns false
THEN a 500 response is returned with "Failed to read uploaded file"
```

### Empty Entities Validation (Gap 15)

```
GIVEN a user calls the anonymize endpoint
WHEN the entities parameter is empty or not an array
THEN a 400 response is returned with "No entities provided for anonymization"
AND no anonymization processing occurs
```

### No User Session (Gap 13)

```
GIVEN a request hits an anonymization upload or files endpoint without an authenticated user
WHEN getCurrentUserId() is called
THEN an Exception is thrown with "No user is currently logged in." and code 401
AND the controller correctly returns a 401 status code (for files/upload endpoints)
NOTE: extract/anonymize endpoints do not call getCurrentUserId() directly
```

## Internal Implementation Details

### OpenRegister Service Getters (Gap 11)

AnonymizationService uses 4 private getter methods to lazily resolve OpenRegister services at call time rather than via constructor injection. This is necessary because OpenRegister may not be installed.

| Getter | Returns | Purpose |
|--------|---------|---------|
| `getTextExtractionService()` | `OCA\OpenRegister\Service\TextExtractionService` | Text extraction from files |
| `getFileService()` | `OCA\OpenRegister\Service\FileService` | File access and anonymization |
| `getEntityRelationMapper()` | `OCA\OpenRegister\Db\EntityRelationMapper` | Entity relation queries |
| `getRiskLevelService()` | `OCA\OpenRegister\Service\RiskLevelService` | Risk level assessment |

**Pattern**: Each getter checks `in_array('openregister', $this->appManager->getInstalledApps(), true)` to verify OpenRegister is installed, then resolves the service via `$this->container->get(FQCN)`. If OpenRegister is not installed, a `\RuntimeException` is thrown with a descriptive message (e.g., "OpenRegister TextExtractionService is not available.").

**Design rationale**: Lazy resolution via `getInstalledApps()` + `container->get()` avoids constructor-time failures when OpenRegister is not installed. The app can still load and show a "not available" state rather than crashing.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-040 | Service getters use `getInstalledApps()` to check OpenRegister availability before resolving | MUST | Implemented |
| ANON-041 | Service getters throw `\RuntimeException` when OpenRegister is unavailable | MUST | Implemented |
| ANON-042 | Service resolution is lazy (per-call), not eagerly loaded at construction time | MUST | Implemented |
| ANON-043 | `listProcessedFiles()` gracefully handles unavailable EntityRelationMapper and RiskLevelService by catching RuntimeException and continuing with defaults | MUST | Implemented |

### UUID v4 Generation (Gap 12)

The `generateUuid()` private method creates UUID v4 strings used as anonymization replacement keys.

**Implementation**:
1. Generates 16 random bytes via `random_bytes(16)`
2. Sets the version nibble: `$data[6] = chr(ord($data[6]) & 0x0f | 0x40)` (version 4)
3. Sets the variant bits: `$data[8] = chr(ord($data[8]) & 0x3f | 0x80)` (RFC 4122 variant)
4. Formats via `vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4))`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-044 | UUID keys are generated using cryptographically secure random bytes (`random_bytes`) | MUST | Implemented |
| ANON-045 | Generated UUIDs conform to RFC 4122 version 4 format | MUST | Implemented |
| ANON-046 | Each entity gets a unique UUID key in the anonymization mapping | MUST | Implemented |

### User Session Error Handling (Gap 13)

The `getCurrentUserId()` private method throws `Exception("No user is currently logged in.", 401)` when `$this->userSession->getUser()` returns null. The controller methods (`files()`, `upload()`) check the exception code via `($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500`, which correctly returns a 401 status code for this exception. The `extract()` and `anonymize()` controller methods do not use this pattern and would return 500.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-047 | `getCurrentUserId()` throws `Exception` with code 401 when no user session exists | MUST | Implemented |
| ANON-048 | `files()` and `upload()` controller methods correctly propagate the 401 status code; `extract()` and `anonymize()` always return 500 on any exception | MUST | Partial |

**Note**: The `files()` and `upload()` endpoints handle the 401 code correctly. The `extract()` and `anonymize()` endpoints do not use the exception-code-aware pattern and would return 500 for auth failures, but these endpoints accept a `fileId` parameter and do not call `getCurrentUserId()` directly.

### Upload Validation Error Responses (Gap 14)

The `AnonymizationController::upload()` method validates the uploaded file in three stages with distinct error responses:

| Condition | HTTP Status | Error Message | Notes |
|-----------|-------------|---------------|-------|
| No file uploaded (`$file === null` or no `tmp_name`) | 400 | "No file uploaded" | Checked first |
| PHP upload error (`$file['error'] !== UPLOAD_ERR_OK`) | 400 | "File upload failed with error code: {code}" | Returns raw PHP upload error code (1-8) |
| Cannot read temp file (`file_get_contents()` returns false) | 500 | "Failed to read uploaded file" | Server-side I/O failure |

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-050 | Return 400 with "No file uploaded" when request contains no file | MUST | Implemented |
| ANON-051 | Return 400 with PHP error code when file upload has a PHP error | MUST | Implemented |
| ANON-052 | Return 500 with "Failed to read uploaded file" when temp file cannot be read | MUST | Implemented |

### Empty Entities Validation (Gap 15)

The `AnonymizationController::anonymize()` method validates that the entities array is non-empty before proceeding:

```php
if (is_array($entities) === false || empty($entities) === true) {
    return new JSONResponse(['error' => 'No entities provided for anonymization'], 400);
}
```

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-053 | Return 400 with "No entities provided for anonymization" when entities array is empty or not an array | MUST | Implemented |

### EntityRelationMapper Method Differences (Gap 24)

AnonymizationService uses two distinct EntityRelationMapper methods in different contexts:

| Method | Used In | Purpose | Returns |
|--------|---------|---------|---------|
| `findEntitiesForFile($fileId)` | `extractAndDetectEntities()` | Retrieves full entity details (type, value, confidence) after text extraction + entity recognition | Entity objects with `entity_type`, `entity_value`, `confidence` |
| `findByFileId($fileId)` | `listProcessedFiles()` | Retrieves entity relations for counting and anonymization status checking | Relation objects with `getAnonymized()` method |

**Key difference**: `findEntitiesForFile()` returns rich entity data (the detected entities themselves), while `findByFileId()` returns relation records that link files to entities and track whether each entity has been anonymized. The former is used during the extraction pipeline; the latter during file listing to compute counts and status.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-054 | Entity extraction uses `findEntitiesForFile()` to get entity details (type, value, confidence) | MUST | Implemented |
| ANON-055 | File listing uses `findByFileId()` to get entity counts and anonymization status | MUST | Implemented |
| ANON-056 | `findByFileId()` relations expose `getAnonymized()` for per-entity anonymization tracking | MUST | Implemented |

## Dependencies

- **OpenRegister TextExtractionService**: Text extraction and entity recognition
- **OpenRegister FileService**: File node access and document anonymization
- **OpenRegister EntityRelationMapper**: Entity relation queries per file (`findEntitiesForFile` for details, `findByFileId` for relations)
- **OpenRegister RiskLevelService**: Risk level assessment per file
- **Nextcloud IRootFolder**: File storage in user folders
- **Nextcloud IUserSession**: Current user identification
- **Nextcloud IAppManager**: Checking OpenRegister installation status
- **PSR ContainerInterface**: Lazy service resolution
