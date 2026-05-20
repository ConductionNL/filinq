## Why

DocuDesk needs a complete, self-contained document anonymization pipeline that operators and civil servants can use to redact PII from sensitive documents before WOO publication or archival — entirely on-premises with no external cloud calls. Without this pipeline, users must rely on manual redaction tools or cloud-dependent third-party services, which undermines GDPR/AVG compliance and privacy-by-design requirements.

## What Changes

- **NEW endpoint** `POST /api/anonymization/upload`: accepts multipart file upload, stores the file in the user's `DocuDesk/` Nextcloud folder (auto-created on first use), handles duplicate names with a counter suffix, and returns fileId + path metadata.
- **NEW endpoint** `POST /api/anonymization/extract/{fileId}`: delegates to OpenRegister `TextExtractionService::extractFile()` and returns detected PII entities (PERSON, ORGANIZATION, EMAIL, PHONE) normalized to a consistent `{type, value, confidence}` schema.
- **NEW endpoint** `POST /api/anonymization/anonymize/{fileId}`: maps detected entities to UUID v4 replacement keys, skips short/numeric values, deduplicates, then delegates to OpenRegister `FileService::anonymizeDocument()`. Returns the anonymized file path and replacement count.
- **NEW endpoint** `GET /api/anonymization/files`: lists all files in the user's `DocuDesk/` folder with per-file entityCount, anonymizedCount, status (`uploaded`/`extracted`/`anonymized`), and riskLevel from OpenRegister `RiskLevelService`.
- **NEW service layer**: `AnonymizationService`, `EntityDetectionService`, `AnonymizationResultParser`, `FileListingService`, `FileUploadService` — each with a single responsibility, lazily resolving OpenRegister services so the app degrades gracefully when OpenRegister is not installed.
- **NEW frontend** `AnonymizationWidget.vue` and Pinia store (`anonymization.js`): drag-and-drop 4-step wizard (Upload → Analyze → Anonymize → Done) with sequential file queue, per-file status tracking, and NcNoteCard error display.

## Capabilities

### New Capabilities

- `anonymization-upload`: multipart file upload to per-user `DocuDesk/` folder with auto-folder creation and duplicate name resolution.
- `anonymization-extract`: text extraction + NER entity detection via OpenRegister, returning normalized entity list.
- `anonymization-anonymize`: entity-to-UUID mapping + document anonymization producing a redacted copy with placeholder tokens.
- `anonymization-file-listing`: file list with entity counts, anonymization status, and risk level assessment.
- `anonymization-ui`: step-by-step wizard UI with drag-and-drop upload, progress indicators, entity review table, and queue management.

## Cross-app Dependencies

- **Hard** — `openregister`: `TextExtractionService`, `FileService`, `EntityRelationMapper`, `RiskLevelService`. All resolved lazily; absence raises `RuntimeException` caught by the service layer.

## Impact

- `lib/Controller/AnonymizationController.php`: 4 REST endpoints with auth guard and thin delegation.
- `lib/Service/AnonymizationService.php`: pipeline orchestration with lazy OpenRegister service resolution.
- `lib/Service/EntityDetectionService.php`: entity normalization and UUID mapping.
- `lib/Service/AnonymizationResultParser.php`: anonymization output parsing.
- `lib/Service/FileListingService.php`: file listing with per-file entity counts and risk assessment.
- `lib/Service/FileUploadService.php`: multipart upload with duplicate-name handling.
- `src/views/anonymization/AnonymizationWidget.vue`: drag-and-drop upload UI widget.
- `src/store/modules/anonymization.js`: Pinia store with file processing queue.
- `appinfo/routes.php`: 4 new API routes.

## Standards Compliance

- **GDPR/AVG Article 4(5)**: pseudonymization; Article 89 + Recital 26: anonymization. Pipeline runs 100% locally.
- **WOO (Wet open overheid)**: supports document redaction before publication.
- **RFC 4122**: UUID v4 keys for anonymization replacement tokens.
- **NEN-ISO/IEC 27001**: data minimization and anonymization controls.
