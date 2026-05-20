# Tasks: Anonymization Pipeline

## Task 1: File Upload Endpoint

- [x] 1.1 Register `POST /api/anonymization/upload` route in `appinfo/routes.php`
- [x] 1.2 Implement `FileUploadService::uploadFile()` — read multipart file from `$_FILES`, write to `IRootFolder`
- [x] 1.3 Implement `getOrCreateDocuDeskFolder()` — create `DocuDesk/` subfolder if absent using `IUserSession` UID
- [x] 1.4 Implement duplicate-name resolution: check for existing file, append `_1`, `_2`, … counter suffix
- [x] 1.5 Return `{fileId, fileName, filePath, fileSize}` JSON response
- [x] 1.6 Return 400 with `"No file uploaded"` when `$_FILES` is empty
- [x] 1.7 Return 400 with `"File upload failed with error code: {code}"` on PHP upload error
- [x] 1.8 Return 500 with `"Failed to read uploaded file"` when temp file cannot be read
- [x] 1.9 Throw `Exception` with code 401 in `getCurrentUserId()` when no user session exists

## Task 2: Text Extraction and Entity Detection

- [x] 2.1 Register `POST /api/anonymization/extract/{fileId}` route in `appinfo/routes.php`
- [x] 2.2 Implement `AnonymizationService::extractAndDetectEntities()` — call `TextExtractionService::extractFile()`
- [x] 2.3 Retrieve entity details via `EntityRelationMapper::findEntitiesForFile()` after extraction
- [x] 2.4 Implement `EntityDetectionService::normalizeEntities()` — handle `entity_type`/`entityType` and `entity_value`/`entityValue` field name variants
- [x] 2.5 Return `{entities[], entityCount}` JSON response
- [x] 2.6 Return 500 with descriptive message when OpenRegister is unavailable

## Task 3: Document Anonymization with Entity Replacement

- [x] 3.1 Register `POST /api/anonymization/anonymize/{fileId}` route in `appinfo/routes.php`
- [x] 3.2 Implement `EntityDetectionService::buildAnonymizationMapping()` — filter entities, assign UUID v4 keys
- [x] 3.3 Skip entity values shorter than 3 characters
- [x] 3.4 Skip purely numeric entity values
- [x] 3.5 Deduplicate entity values using a seen-set before building the mapping
- [x] 3.6 Generate UUID v4 keys using `random_bytes(16)` conforming to RFC 4122
- [x] 3.7 Call `FileService::anonymizeDocument()` with the entity mapping
- [x] 3.8 Return `{anonymizedFileId, anonymizedFileName, anonymizedFilePath, replacementCount}` JSON response
- [x] 3.9 Return 400 with `"No entities provided for anonymization"` when entities array is empty

## Task 4: Processed File Listing with Risk Assessment

- [x] 4.1 Register `GET /api/anonymization/files` route in `appinfo/routes.php`
- [x] 4.2 Implement `FileListingService::listProcessedFiles()` — iterate files in user's `DocuDesk/` folder
- [x] 4.3 Skip directory nodes (list files only)
- [x] 4.4 Enrich each file with `entityCount` and `anonymizedCount` via `EntityRelationMapper::findByFileId()`
- [x] 4.5 Derive `status` from entity counts: `uploaded` (0 entities), `extracted` (entities present, none anonymized), `anonymized` (all anonymized)
- [x] 4.6 Enrich each file with `riskLevel` via `OpenRegister::RiskLevelService`
- [x] 4.7 Sort files by modification time descending (newest first)
- [x] 4.8 Return 401 when no user session exists (propagated from `getCurrentUserId()`)
- [x] 4.9 Catch `RuntimeException` from OpenRegister services and default to `entityCount: 0`, `riskLevel: "unknown"`

## Task 5: Lazy OpenRegister Service Resolution

- [x] 5.1 Implement `getOpenRegisterService($serviceName)` in `AnonymizationService` — check `IAppManager::getInstalledApps()` first
- [x] 5.2 Throw `\RuntimeException` with the service name in the message when OpenRegister is not installed
- [x] 5.3 Resolve all OpenRegister services lazily via `ContainerInterface::get()` (not constructor injection)
- [x] 5.4 Verify `listProcessedFiles()` catches `RuntimeException` and continues with defaults

## Task 6: Anonymization Pipeline UI

- [x] 6.1 Create `src/views/anonymization/AnonymizationWidget.vue` with drag-and-drop upload zone (SPDX header)
- [x] 6.2 Implement 4-step progress indicator: Upload → Analyze → Anonymize → Done
- [x] 6.3 Show detected entities in a table (type, value, confidence columns)
- [x] 6.4 Display result: replacement count, anonymized file name and path
- [x] 6.5 Show `NcNoteCard` with error message and "Try again" button on pipeline failure
- [x] 6.6 Implement "Anonymize another" button that resets state back to upload step
- [x] 6.7 All UI strings via `this.t('docudesk', '...')` — no hardcoded Dutch or English strings in templates

## Task 7: Pinia Store — File Processing Queue

- [x] 7.1 Create `src/store/modules/anonymization.js` with file queue array (SPDX header)
- [x] 7.2 Implement `addFiles()` action — push files with initial status `queued`
- [x] 7.3 Implement `processQueue()` action — process files sequentially, one at a time
- [x] 7.4 Transition each file through: `queued → uploading → extracting → anonymizing → completed`
- [x] 7.5 On error: set file status to `error`, advance queue to next file
- [x] 7.6 Implement getters: `hasFiles`, `hasCompleted`, `allDone`, `isProcessing`
- [x] 7.7 Use `@nextcloud/axios` for all API calls (no raw `fetch()`)

## Task 8: ADR Compliance and @spec Tags

- [x] 8.1 Add `@spec openspec/changes/anonymization/tasks.md#task-1` PHPDoc to `AnonymizationController.php`
- [x] 8.2 Add `@spec` tags to all service files referencing their corresponding task number
- [x] 8.3 Add EUPL-1.2 `@license` PHPDoc to all new PHP files (ADR-014)
- [x] 8.4 Add `<!-- SPDX-License-Identifier: EUPL-1.2 -->` to all new Vue files (ADR-014)
- [x] 8.5 Add `// SPDX-License-Identifier: EUPL-1.2` to all new JS files (ADR-014)
- [x] 8.6 Import all Vue components from `@conduction/nextcloud-vue` (not `@nextcloud/vue`) (ADR-015)
- [x] 8.7 Verify every `<NcFoo>` / `<CnFoo>` in templates has a matching import and `components: {}` entry

## Task 9: Translations (ADR-007)

- [x] 9.1 Add Dutch translations for all anonymization UI strings to `l10n/nl.json`
- [x] 9.2 Add English translations (identity-mapped) to `l10n/en.json`
- [x] 9.3 Verify both files have exactly the same keys (zero gaps)
- [x] 9.4 Verify all translation keys are in English sentence case (no Dutch keys, no title case)

## Task 10: Tests

- [x] 10.1 Unit test `FileUploadService` — error handling (no file, PHP error, read failure)
- [x] 10.2 Unit test `EntityDetectionService` — normalization, filtering (short/numeric/duplicate), UUID mapping
- [x] 10.3 Unit test `AnonymizationResultParser` — output parsing
- [x] 10.4 Unit test `AnonymizationService` — lazy service resolution, graceful degradation
- [x] 10.5 Unit test `FileListingService` — file listing with default fallbacks when OpenRegister is absent
