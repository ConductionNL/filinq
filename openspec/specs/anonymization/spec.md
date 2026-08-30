---
status: in-progress
or_adoption_change: docudesk-adopt-or-abstractions
---

# Anonymization Pipeline

**Status**: in-progress
**OpenSpec changes**:
- [image-redaction](../../changes/image-redaction/) _(active)_ — extract response gains image-origin entities with region geometry, anonymise gains the pixel-burn step for image-bearing content with a fail-flagged contract (REQ-DDIMR-007/008) (kind: code)
- [document-sanitization](../../changes/document-sanitization/) _(active)_ — anonymisation runs persist/surface OpenRegister's sanitization report and the anonymise endpoint gains the additive opt-in `sanitize` flag for the final artifact (REQ-DDSAN-006/007) (kind: code)
- [odt-anonymisation-frontend](../../changes/odt-anonymisation-frontend/) _(active)_ — ODT accepted by the upload widget (kind: code)

## Purpose

Provides a complete document anonymization pipeline: files are stored as **OR File Attachments**, text extraction and PII entity detection run via **OpenRegister's TextExtractionService**, and anonymization replaces detected entities with pseudonyms via OR's FileService. The pipeline runs 100% locally with no external cloud dependencies, ensuring GDPR/AVG compliance through privacy-by-design processing.

## OR Adoption decisions (from docudesk-adopt-or-abstractions)

- **Decision 4** — Anonymization consumes OR primitives, no replacement: OR's `TextExtractionService` and File Attachments cover the input side. The custom file-upload + entity-extraction flow is replaced by these primitives. The actual NLP/PII detection algorithms remain in filinq (value-add). Custom plumbing is dropped.
- **Decision 3** — Anonymization-result confidence, risk-level, entity-density, and redaction-coverage are declared as `x-openregister-calculations` annotations on the file-attachment extension schema, NOT populated by ad-hoc writes in `AnonymizationService`. Service code calls `lifecycleService->transitionTo()` for state changes; OR derives calculated fields automatically.
- **Decision 2** — `anonymizationResult` objects (if stored separately) carry `x-openregister-archival.retention: P1Y` (Archiefwet cat. 1.2: operational processing logs). File attachments inherit OR's standard retention; DPO sign-off required for legal-hold override.
- **Decision 5** — Status strings on the wire stay the same (`uploaded`, `extracted`, `anonymized`). Lifecycle annotation maps these states; no renaming.

### Requirement: File Input via OR File Attachments (REQ-ANON-00)

@e2e exclude Backend FileUploadService persistence as OR file attachment and OR virus-scan-hook rejection (HTTP 422) — service/contract behaviour, no UI assertion. Covered by PHPUnit (FileUploadService) and Newman (upload endpoint).

**Priority:** MUST

Uploaded files MUST be stored as OR File Attachments, not by filinq-specific storage code. Virus-scan and MIME-validation hooks are inherited from OR.

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
- **AND** no filinq record SHALL reference the rejected file

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-000 | Files persisted as OR File Attachments | MUST | Apply-phase |
| ANON-000a | MIME-validation and virus-scan hooks execute on ingest | MUST | Apply-phase |
| ANON-000b | Upload response uses OR attachment fileId as lookup key | MUST | Apply-phase |

### Requirement: Anonymization Confidence is a Calculation (REQ-ANON-CAL)

@e2e exclude Backend x-openregister-calculations derivation (riskLevel/redactionCoverage computed by OR, not written by AnonymizationService) — no browser surface. Covered by PHPUnit (calculation expressions) and OR calculation integration tests.

**Priority:** MUST

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

**Priority:** MUST

Users upload files via multipart form data, and files MUST be stored in a per-user Filinq folder within Nextcloud Files.

#### Scenario: Successful file upload
@e2e exclude multipart POST /api/anonymization/upload response shape — FileUploadService verified by PHPUnit; UI upload flow covered by complete-anonymization-workflow test
- GIVEN a logged-in user
- WHEN they upload a PDF file via `POST /api/anonymization/upload`
- THEN the file is stored in their `Filinq/` subfolder in Nextcloud Files
- AND the response includes fileId, filePath, fileName, and fileSize

#### Scenario: Auto-create Filinq folder
@e2e exclude IRootFolder folder-creation side-effect — FileUploadService verified by PHPUnit; folder auto-creation not separately observable in UI
- GIVEN a logged-in user who has never used Filinq
- AND no `Filinq/` folder exists in their Nextcloud files
- WHEN they upload their first file
- THEN the `Filinq/` subfolder is created automatically
- AND the file is stored in the new folder

#### Scenario: Duplicate file name handling
@e2e exclude filename deduplication algorithm — FileUploadService collision logic verified by PHPUnit; not directly observable in UI without pre-existing file collision setup
- GIVEN a user's Filinq folder already contains `report.pdf`
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
| ANON-002 | Uploaded files are stored in the user's `Filinq/` subfolder in Nextcloud Files | MUST | Implemented |
| ANON-003 | The Filinq subfolder is created automatically if it does not exist | MUST | Implemented |
| ANON-004 | Duplicate file names are handled by appending an incrementing counter | MUST | Implemented |
| ANON-005 | Upload response includes fileId, filePath, fileName, and fileSize | MUST | Implemented |
| ANON-006 | Upload requires an authenticated user session | MUST | Implemented |

### Requirement: Text Extraction and Entity Detection (REQ-ANON-02)

Text is extracted from uploaded documents and entities are detected via the OpenRegister NER pipeline. The extraction response SHALL include a `riskLevel` field summarising the privacy risk of the detected entities.

@e2e exclude Backend OR NER extraction via POST /api/anonymization/extract/{fileId} with derived riskLevel — service/API contract, no browser surface. Covered by Newman (extract endpoint) and PHPUnit (AnonymizationService).

#### Scenario: Extract with risk level
- **WHEN** extraction is performed via `POST /api/anonymization/extract/{fileId}`
- **THEN** the response includes a `riskLevel` field derived from the detected entities

### Requirement: Document Anonymization with Entity Replacement (REQ-ANON-03)

Detected entities are replaced with anonymized placeholders in the document, producing an anonymized copy. The anonymization endpoint SHALL additionally accept optional `excludeTypes` and `minConfidence` parameters so callers can narrow which detected entities are replaced.

@e2e exclude Backend anonymize endpoint excludeTypes/minConfidence parameter handling (POST /api/anonymization/anonymize/{fileId}) — entity-replacement service logic, no browser surface. Covered by Newman (anonymize endpoint params) and PHPUnit (AnonymizationService).

#### Scenario: Anonymize with entity type exclusion
- **WHEN** `excludeTypes=["ORGANIZATION"]` is provided to `POST /api/anonymization/anonymize/{fileId}`
- **THEN** ORGANIZATION entities are excluded from replacement
- **AND** all other detected entity types are still anonymized

#### Scenario: Anonymize with a minimum confidence threshold
- **WHEN** `minConfidence=0.7` is provided
- **THEN** entities whose detection confidence is below 0.7 are excluded from replacement

### Requirement: Processed File Listing with Risk Assessment (REQ-ANON-04)

**Priority:** MUST

The system MUST list all files in the user's Filinq folder with entity counts, anonymization status, and risk level assessment.

#### Scenario: List files with entity counts and status
@e2e exclude FileListingService API response content — requires pre-processed files in Filinq/ folder; GET /api/anonymization/files verified by PHPUnit
- GIVEN a user has 5 files in their Filinq folder (2 extracted, 1 anonymized, 2 uploaded)
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
| ANON-030 | List all files in the user's Filinq folder via `GET /api/anonymization/files` | MUST | Implemented |
| ANON-031 | Each file includes entityCount, anonymizedCount, and status (uploaded/extracted/anonymized) | MUST | Implemented |
| ANON-032 | Each file includes riskLevel from OpenRegister's RiskLevelService | MUST | Implemented |
| ANON-033 | Files are sorted by modification time descending (newest first) | MUST | Implemented |
| ANON-034 | File listing includes fileId, fileName, filePath, fileSize, and mimeType | MUST | Implemented |
| ANON-035 | Only actual files are listed (directories are skipped) | MUST | Implemented |

### Requirement: Lazy OpenRegister Service Resolution (REQ-ANON-05)

**Priority:** MUST

AnonymizationService MUST lazily resolve OpenRegister services at call time to gracefully handle the case where OpenRegister is not installed.

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

**Priority:** MUST

Each anonymized entity MUST be assigned a cryptographically secure UUID v4 key as its replacement identifier.

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

**Priority:** MUST

File operations MUST require an authenticated user session to scope files to the correct user's folder.

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

**Priority:** MUST

The frontend MUST provide a step-by-step UI for the complete anonymization workflow with drag-and-drop upload, progress tracking, and result display.

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

**Priority:** MUST

Two distinct EntityRelationMapper methods MUST serve their different purposes in the pipeline: entity detail retrieval vs. relation counting.

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

**Priority:** MUST

The Pinia store MUST manage a sequential file processing queue with status tracking through the pipeline stages.

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

### Requirement: The anonymise endpoint MUST accept an optional `outputFormat` field

The anonymise endpoint payload MUST accept an optional top-level `outputFormat` field with allowed values `"pdf"` and `"preserve"`. When omitted, the endpoint MUST use the tenant default (`filinq.anonymisation.default_output_format`, default `pdf`). Any other value MUST be rejected with HTTP 400.

#### Scenario: Default behaviour produces PDF

- **GIVEN** an anonymise request with no `outputFormat` specified
- **AND** the tenant default is `pdf`
- **WHEN** the endpoint processes the request
- **THEN** the resulting file written to Nextcloud Files is a PDF/A-3b
- **AND** the response indicates success with the file's metadata

#### Scenario: Explicit `outputFormat: "preserve"` keeps native format

- **GIVEN** an anonymise request with `outputFormat: "preserve"`
- **AND** an input DOCX file
- **WHEN** the endpoint processes the request
- **THEN** the resulting file written to Nextcloud Files is a DOCX (the native format)
- **AND** no conversion is attempted

#### Scenario: Invalid value is rejected

- **GIVEN** an anonymise request with `outputFormat: "rtf"`
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 400
- **AND** the body cites the allowed values: `"pdf"`, `"preserve"`

#### Scenario: Tenant default `preserve` reverses the default

- **GIVEN** the tenant default is `preserve` (admin override)
- **AND** an anonymise request without `outputFormat`
- **WHEN** the endpoint processes the request
- **THEN** the resulting file is in the native input format

### Requirement: When `outputFormat: "pdf"`, the endpoint MUST invoke `PdfConversionService` after OpenRegister returns

The anonymise pipeline MUST follow this order when the resolved `outputFormat` is `pdf`:

1. Forward the anonymise request to OpenRegister (existing behaviour).
2. Receive the anonymised file in its native format.
3. Pass the file to `PdfConversionService::convertToPdf()`.
4. On success, replace the native-format file in Nextcloud Files with the converted PDF/A-3b file (atomic — the operator never sees both).
5. On failure (the service throws `ConversionFailedException`), see the next requirement.

#### Scenario: Successful conversion replaces the native file with the PDF

- **GIVEN** an anonymise request with `outputFormat: "pdf"` and a DOCX input
- **AND** at least one conversion backend is available and capable
- **WHEN** the request completes
- **THEN** the file at the input's original NC path is replaced with the converted PDF/A-3b
- **AND** the file extension is updated to `.pdf`
- **AND** no DOCX intermediate is left behind

#### Scenario: Native file is not written when conversion is requested but fails

- **GIVEN** an anonymise request with `outputFormat: "pdf"`
- **AND** the conversion will fail (no backend handles the input)
- **WHEN** the request is processed
- **THEN** the native-format intermediate is NOT left in Nextcloud Files
- **AND** the original (pre-anonymisation) file is unchanged
- **AND** the response is HTTP 422 (per the next requirement)

### Requirement: Conversion failure MUST return HTTP 422 with structured body

When `PdfConversionService::convertToPdf()` throws, the anonymise endpoint MUST:

1. Roll back any intermediate state — delete the un-converted anonymised file from Nextcloud Files if it was written.
2. Return HTTP 422 with a JSON body of the documented shape (see scenarios).
3. NOT silently fall back to native-format output (the operator must explicitly opt in via `outputFormat: "preserve"`).

The 422 body MUST include:

```json
{
  "error": "<localised string>",
  "conversionAttempts": [
    {"backend": "<name>", "available": <bool>, "supports": <bool>, "reason": "<string>"}
  ],
  "outputFormat": "pdf",
  "fallback": "<localised hint mentioning outputFormat: 'preserve'>"
}
```

#### Scenario: 422 lists every backend that was tried

- **GIVEN** an anonymise request with `outputFormat: "pdf"` and an input no backend can handle
- **WHEN** the request is processed
- **THEN** the response is HTTP 422
- **AND** `conversionAttempts` lists each backend in cascade order with `{backend, available, supports, reason}`
- **AND** `fallback` mentions `outputFormat: "preserve"` as the explicit escape hatch

#### Scenario: 422 does not happen for `preserve`

- **GIVEN** an anonymise request with `outputFormat: "preserve"`
- **WHEN** the request completes
- **THEN** the response is the existing pre-change shape (HTTP 200, file metadata)
- **AND** no conversion failure can fire (no conversion is attempted)

### Requirement: The change MUST be additive and non-breaking for callers that supply `outputFormat: "preserve"`

Pre-change callers that begin sending `outputFormat: "preserve"` MUST see behaviour identical to the pre-change anonymise endpoint. Existing pre-change clients that do NOT send `outputFormat` MUST observe the new PDF default — this is a deliberate behaviour change documented in the CHANGELOG.

#### Scenario: `preserve` callers see pre-change behaviour

- **GIVEN** a pre-change client that sends `outputFormat: "preserve"` on every call
- **WHEN** the client interacts with the anonymise endpoint
- **THEN** behaviour is identical to before this change
- **AND** the response shape is unchanged

#### Scenario: Pre-change client without `outputFormat` sees PDF default

- **GIVEN** a pre-change client that sends payloads without `outputFormat` and a DOCX input
- **WHEN** the client receives the response
- **THEN** the file written is now PDF (behaviour change)
- **AND** the response remains HTTP 200 with file metadata in the existing shape (the file extension and MIME on the metadata reflect the new PDF type)

### Requirement: Batch anonymise MUST honour `outputFormat` per request

The batch anonymise endpoint (`POST /api/anonymization/batch/{batchId}/anonymize`) MUST accept the same top-level `outputFormat` field. When `pdf`, every file in the batch is converted; if any single file's conversion fails, the operator gets a 422 listing the failed file(s) but the batch's already-converted files remain in NC.

#### Scenario: Batch with mixed-format inputs all converted to PDF

- **GIVEN** a batch with DOCX, PDF, and TXT inputs
- **AND** `outputFormat: "pdf"` (or default)
- **WHEN** the batch anonymise endpoint processes the request
- **THEN** all three files are anonymised AND converted to PDF/A-3b
- **AND** the response indicates per-file outcomes

#### Scenario: Partial-failure batch returns per-file status

- **GIVEN** a batch where one file's conversion fails (e.g. an unsupported XLSX with no Office app installed)
- **WHEN** the batch endpoint processes the request
- **THEN** the response is HTTP 422 (or HTTP 207 multi-status) with per-file outcomes
- **AND** files that converted successfully remain in NC as PDFs
- **AND** the failed file is NOT written to NC in any format

### Requirement: The anonymise endpoint MUST accept an optional `appendBasisSummary` flag

The endpoint payload MUST accept an optional top-level boolean field `appendBasisSummary`. When omitted or `false`, behaviour matches pre-change exactly. When `true`, the endpoint MUST invoke the summary-append flow (per the `anonymisation-grondslagen-summary` capability) after the anonymised file has been written to Nextcloud Files.

The flag MUST be honoured for both the per-document anonymise endpoint and the batch anonymise endpoint. In the batch case, the flag applies to every file in the batch.

#### Scenario: appendBasisSummary omitted preserves pre-change behaviour

- **GIVEN** an anonymise request with no `appendBasisSummary` field (or `appendBasisSummary: false`)
- **WHEN** the endpoint processes the request
- **THEN** no summary is rendered
- **AND** no summary PDF is appended or saved
- **AND** the response shape is identical to pre-change

#### Scenario: appendBasisSummary true with PDF output appends a summary page

- **GIVEN** `outputFormat: "pdf"` (or default) and `appendBasisSummary: true`
- **WHEN** the request completes successfully
- **THEN** the resulting file's last page is the rendered summary
- **AND** the file is PDF/A-3b

#### Scenario: appendBasisSummary true with preserve mode produces a separate PDF

- **GIVEN** `outputFormat: "preserve"` and `appendBasisSummary: true` and a non-PDF input
- **WHEN** the request completes
- **THEN** the anonymised native-format file is written normally (per Change A's preserve path)
- **AND** a separate `<original-base>_anonymized_grondslagen.pdf` is written alongside in the same folder
- **AND** the response indicates both files (file metadata for the anonymised file and a `summaryFileId` / `summaryFilePath` reference for the separate summary)

#### Scenario: Batch anonymise honours flag per batch

- **GIVEN** a batch anonymise request with `appendBasisSummary: true`
- **WHEN** the batch completes
- **THEN** every file's anonymised output (or its accompanying summary PDF in preserve mode) carries the rendered summary
- **AND** files in the batch that have no `EntityRelation.bases` data still get a summary page that lists their anonymised entities with the `⟨geen grondslag vastgelegd⟩` placeholder

### Requirement: Summary append failure MUST NOT discard the anonymised file

If the summary rendering or append step throws (e.g. mPDF import error, base resolution timeout), the anonymised file itself MUST be preserved as-is in Nextcloud Files. The endpoint MUST return HTTP 200 with the anonymised file metadata AND a structured warning indicating the summary failed. The operator can re-attempt summary generation later (no API surface for this in v1; it requires re-running the anonymise call or — once available in a follow-up — a standalone summary-render endpoint).

#### Scenario: Append failure surfaces as a warning, not an error

- **GIVEN** an anonymise request with `appendBasisSummary: true`
- **AND** the summary rendering fails internally
- **WHEN** the response is returned
- **THEN** the response is HTTP 200
- **AND** the anonymised file is in Nextcloud Files at its expected path
- **AND** the response body contains a `warning` field describing the summary failure (with a stable error code suitable for the frontend to display)
- **AND** no partial summary PDF is left in NC

### Requirement: The change MUST be additive and non-breaking

Pre-change callers that don't set `appendBasisSummary` MUST see behaviour identical to the pre-change anonymise endpoint. The response shape adds the `warning` field only when a summary failure occurs; pre-change clients that don't read it are unaffected.

#### Scenario: Pre-change client unaffected

- **GIVEN** a pre-change client that doesn't send `appendBasisSummary`
- **WHEN** the client performs an anonymise call
- **THEN** the response shape is unchanged
- **AND** no summary work runs

### Requirement: The Filinq anonymise endpoint MUST NOT carry `bases` per entity in its payload

The endpoint payload's `entities[]` array MUST NOT introduce a `bases` field on entries. Callers that wish to attach legal bases to a detected entity occurrence MUST do so via OpenRegister's `PATCH /api/entity-relations/{id}` endpoint (or the equivalent DI mapper method `EntityRelationMapper::updateDecisionMetadata`) BEFORE invoking Filinq's anonymise endpoint.

Filinq MUST ignore any `bases` field that erroneously appears on incoming payload entries — silently drop it (do NOT 400). This preserves backwards-compatibility with any caller still on the old contract; the field becomes a no-op rather than a hard failure.

Filinq MUST NOT persist bases locally. Single source of truth: the `EntityRelation` row, written via OR's audited PATCH endpoint.

#### Scenario: Anonymise request without bases works exactly as before

- **GIVEN** an anonymise request payload with entities that have no `bases` field
- **WHEN** Filinq's controller processes it
- **THEN** the call MUST succeed
- **AND** behaviour MUST match the pre-change `anonymization` capability exactly

#### Scenario: Stray `bases` field on a payload entry is silently ignored

- **GIVEN** a caller still using the old contract sends `entities: [{text: "Jan Janssen", entityType: "PERSON", key: "x", bases: ["uuid-a"]}]`
- **WHEN** Filinq's controller processes it
- **THEN** the call MUST succeed
- **AND** no `bases` value MUST be written to any EntityRelation row by Filinq's code path (bases-set is via OR's PATCH, which the caller has not invoked)
- **AND** no error MUST be raised

#### Scenario: Bases-attached entities are redacted under their bases when those were set via OR's PATCH first

- **GIVEN** an authorized caller PATCHes OR with `{bases: ["uuid-a"]}` for an EntityRelation row R
- **AND** subsequently calls Filinq's anonymise endpoint without any `bases` field
- **WHEN** the call processes
- **THEN** R's `bases` value MUST remain `["uuid-a"]` (set via OR's PATCH; not overwritten by the anonymise call)
- **AND** R MUST be redacted (no `skipAnonymization=true`)

### Requirement: The extract endpoint response MUST include a `prohibitionMatch` field per detected entity

The extract endpoint's response (currently returns `entities[]` per detected entity with `text`, `entityType`, `score`) MUST include a new field `prohibitionMatch` per entity. The field is either:

- `null` — no `publicationProhibition` rule matches this entity, OR
- An object `{ ruleId, ruleName, highConfidence }` where:
  - `ruleId` is the matched rule's UUID,
  - `ruleName` is the rule's `primaryName`,
  - `highConfidence` is `true` when the entity's `score` ≥ the configured high-confidence threshold (default 0.85), `false` otherwise.

The matcher used MUST be the same `PolicyMatchService` consulted by the prohibition gate (see `anonymisation-prohibition-gate` capability). The matcher invocation at extract time is read-only and MUST NOT modify any state.

#### Scenario: No prohibition matches — field is null

- **GIVEN** a file whose detected entities match no `publicationProhibition` rule
- **WHEN** the extract endpoint returns the entity list
- **THEN** every entry has `prohibitionMatch: null`

#### Scenario: High-confidence prohibition match is flagged

- **GIVEN** a detected entity at confidence 0.96 matching prohibition rule `R-X` whose `primaryName` is "Beschermde Getuige A"
- **WHEN** the extract endpoint returns the entity
- **THEN** the entry's `prohibitionMatch` is `{ruleId: "R-X", ruleName: "Beschermde Getuige A", highConfidence: true}`

#### Scenario: Low-confidence prohibition match is flagged with highConfidence false

- **GIVEN** a detected entity at confidence 0.62 matching prohibition rule `R-Y`
- **AND** the configured high-confidence threshold is 0.85
- **WHEN** the extract endpoint returns the entity
- **THEN** the entry's `prohibitionMatch.highConfidence` is `false`

#### Scenario: Same threshold is applied at extract and at the gate

- **GIVEN** the threshold is configured at 0.85
- **AND** an entity is detected at confidence 0.85 exactly
- **WHEN** the extract endpoint returns the entity
- **THEN** `highConfidence: true` (the threshold is inclusive — ≥ 0.85)
- **AND** the gate (per `anonymisation-prohibition-gate`) also treats this match as high-confidence

### Requirement: The change MUST be additive and non-breaking for existing consumers

Pre-change clients that don't send `bases` and don't read `prohibitionMatch` MUST continue to work without modification. No existing field is removed, renamed, or repurposed.

#### Scenario: Pre-change client continues to work

- **GIVEN** a pre-change client constructing payloads without `bases` and reading responses without `prohibitionMatch`
- **WHEN** the client sends an extract request followed by an anonymise request
- **THEN** both succeed with behaviour identical to before this change
- **AND** the response shape is a strict superset of the pre-change shape (new fields added, none removed)

### Requirement: Admin Warning When No Anonymiser Backend Is Available

Filinq MUST surface a non-blocking admin warning when entity recognition is operating in regex-only mode AND the admin viewing the page has not dismissed the warning.

#### Scenario: Admin opens Filinq admin settings with no backend configured
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the current user is in the admin group
- **AND** the admin has not previously dismissed the warning
- **WHEN** the admin loads the Filinq admin settings page
- **THEN** a warning banner is shown at the top of the settings section
- **AND** the banner contains a deep link to the App Store entry for `openanonymiser_light`
- **AND** the banner contains a deep link to the App Store entry for `openanonymiser`
- **AND** the banner contains a link to OpenRegister settings for configuring a custom endpoint
- **AND** the banner contains a "Dismiss" action

#### Scenario: Admin opens Filinq dashboard with no backend configured
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the current user is in the admin group
- **AND** the admin has not previously dismissed the warning
- **WHEN** the admin loads the Filinq dashboard
- **THEN** the warning banner is shown at the top of the dashboard

#### Scenario: Non-admin user opens Filinq dashboard with no backend configured
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the current user is NOT in the admin group
- **WHEN** the user loads the Filinq dashboard
- **THEN** the warning banner is NOT shown

#### Scenario: Admin dismisses the warning banner
- **WHEN** the admin clicks "Dismiss" on the warning banner
- **THEN** the dismissal is persisted as a per-admin `IAppConfig` user value
- **AND** the banner is not shown again to this admin on subsequent page loads

#### Scenario: Admin re-enables the warning
- **GIVEN** the admin has previously dismissed the warning
- **WHEN** the admin enables "Show anonymiser backend warning" in Filinq admin settings
- **THEN** the dismissal record is cleared
- **AND** the banner is shown again on the next page load

#### Scenario: Backend becomes available
- **GIVEN** the warning was previously visible
- **WHEN** OpenRegister reports backend state changes to any non-`regex` method (e.g. `openanonymiser`, `presidio`, custom URL)
- **AND** the admin loads a Filinq admin page
- **THEN** the warning banner is NOT shown
- **AND** dismissal state remains intact (re-shown if backend later disappears)

#### Scenario: AppAPI is not installed
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the `app_api` Nextcloud app is not installed or not enabled
- **WHEN** the admin loads the Filinq admin settings page
- **THEN** the warning banner additionally indicates that AppAPI must be installed first
- **AND** the deep-link CTAs to the ExApp entries remain visible

### Requirement: Deep Links Target App Store Entries

The warning banner's CTAs MUST link to Nextcloud App Store entries by app id, not to AppAPI internal admin pages.

#### Scenario: Click on "Install OpenAnonymiser Light"
- **WHEN** the admin clicks the OpenAnonymiser Light CTA
- **THEN** the browser navigates to `/settings/apps/discover/openanonymiser_light` on the current Nextcloud instance
- **AND** the Nextcloud App Store sidebar auto-opens with the app details and the "Download and enable" action
- **AND** no install action is triggered automatically — the admin must confirm install from the sidebar

### Requirement: Detection State Source

Filinq MUST NOT query AppAPI, `IAppManager`, or HTTP health endpoints directly to determine backend availability. All detection MUST be delegated to OpenRegister via the `AnonymisationBackendService::getState()` PHP service.

#### Scenario: Filinq delegates state lookup to OpenRegister
- **WHEN** Filinq needs to determine whether to show the warning
- **THEN** it calls `OCA\OpenRegister\Service\AnonymisationBackendService::getState()`
- **AND** it does not directly call `IAppManager::isEnabledForUser('openanonymiser_light')` or any AppAPI service
- **AND** if OpenRegister is not installed, Filinq treats this as a fatal install-time error consistent with its existing OpenRegister dependency

### Requirement: The anonymise endpoint MUST reject calls when any skip-marked relation has a blocking consent record

Before delegating to OpenRegister's `anonymizeDocument`, the anonymise endpoint MUST verify that every `EntityRelation` row for the target file with `skipAnonymization: true` has either no corresponding `publicationConsent` record OR a record in a non-blocking state. The classification:

| consentStatus | publicationDecision | objectionDeadline | Blocking? |
|---|---|---|---|
| `consent_given` | (any) | (any) | No |
| `anonymized` | (any) | (any) | No |
| `pending` | (any) | past | No |
| `pending` | (any) | future | **YES** |
| `objection_received` | `anonymize` | (any) | No |
| `objection_received` | `publish_with_consent` | (any) | No |
| `objection_received` | `pending` | (any) | **YES** |
| `no_response` | (any) | (any) | No |

When at least one blocking record is found, the request MUST return HTTP 422 with a structured body listing every blocking consent. No file mutation MUST occur. No EntityRelation row MUST be modified.

The 422 body shape MUST be:

```json
{
  "error": "<localised string>",
  "blockingConsents": [
    {
      "consentId": "<uuid>",
      "entityText": "<string>",
      "consentStatus": "<enum>",
      "objectionDeadline": "<ISO-8601 timestamp or null>",
      "reason": "<one of: objection_window_open | objection_under_review>"
    }
  ]
}
```

#### Scenario: File with no skip-marked relations passes the check

- **GIVEN** a file whose EntityRelations all have `skipAnonymization: false`
- **WHEN** the anonymise endpoint is called
- **THEN** the check passes
- **AND** anonymisation proceeds as before

#### Scenario: Skip-marked relation with an auto-resolved consent passes the check

- **GIVEN** a file with a `skipAnonymization: true` relation
- **AND** a publicationConsent record for the entity has `consentStatus: "consent_given"` (standing-consent match)
- **WHEN** the anonymise endpoint is called
- **THEN** the check passes
- **AND** anonymisation proceeds

#### Scenario: Skip-marked relation with a pending consent in window blocks the call

- **GIVEN** a file with a `skipAnonymization: true` relation for entity "Anneke Jansen"
- **AND** a publicationConsent record for that entity has `consentStatus: "pending"` and `objectionDeadline` 10 days in the future
- **WHEN** the anonymise endpoint is called
- **THEN** the response is HTTP 422
- **AND** `blockingConsents[]` lists exactly one entry referencing the consent record with `reason: "objection_window_open"`
- **AND** no file mutation occurs

#### Scenario: Skip-marked relation with a pending consent past window passes

- **GIVEN** a file with a `skipAnonymization: true` relation
- **AND** the publicationConsent's `objectionDeadline` has already passed
- **WHEN** the anonymise endpoint is called
- **THEN** the check passes (window closed; "no objection received" is the operator's go-ahead)
- **AND** anonymisation proceeds

#### Scenario: Skip-marked relation with objection received, decision pending, blocks

- **GIVEN** a publicationConsent record with `consentStatus: "objection_received"` and `publicationDecision: "pending"`
- **WHEN** the anonymise endpoint is called for the associated file
- **THEN** the response is HTTP 422 with `reason: "objection_under_review"`

#### Scenario: Skip-marked relation with objection received and decision = anonymize passes

- **GIVEN** a publicationConsent record with `consentStatus: "objection_received"` and `publicationDecision: "anonymize"`
- **WHEN** the anonymise endpoint is called for the associated file
- **THEN** the check passes (operator decided to anonymise despite the skip flag — the decision overrides)

#### Scenario: Skip-marked relation with no consent record proceeds with a warning

- **GIVEN** a `skipAnonymization: true` relation whose corresponding publicationConsent record is missing (likely listener failure)
- **WHEN** the anonymise endpoint is called
- **THEN** the check logs a warning identifying the relation
- **AND** the relation is treated as not-blocking
- **AND** anonymisation proceeds (the operator's skip decision stands; the missing consent record is a system bug to investigate separately, not a reason to block the user)

#### Scenario: Multiple skip-marked relations, mixed states

- **GIVEN** three `skipAnonymization: true` relations:
  - Relation A → consent `consent_given`
  - Relation B → consent `pending` in window (blocking)
  - Relation C → consent `pending` past window (not blocking)
- **WHEN** the anonymise endpoint is called
- **THEN** the response is HTTP 422
- **AND** `blockingConsents[]` lists only relation B's consent
- **AND** no file mutation occurs

### Requirement: The anonymise endpoint's success-path shape MUST be unchanged

This delta MUST NOT modify the anonymise endpoint's request payload or its successful (HTTP 200) response shape. Existing callers that pass the same payload they pass today MUST receive the same response they receive today.

#### Scenario: Pre-change client is unaffected on the happy path

- **GIVEN** a pre-change client that sends an anonymise request with the existing payload (no `skipAnonymization: true` relations on the target file)
- **WHEN** the request succeeds
- **THEN** the response body matches the pre-change shape exactly (no new fields)

#### Scenario: HTTP 422 is the only new failure response

- **WHEN** any other anonymise error condition arises (file not found, permission denied, OR rejection, etc.)
- **THEN** the response code and shape remain whatever the pre-change behaviour produced
- **AND** the new 422 path applies ONLY to the blocking-consent case described above

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
