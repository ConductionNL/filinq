## 1. Batch State Management (Backend)

- [x] 1.1 Create `BatchStateService` class in `lib/Service/` that manages batch state via Nextcloud ICache — create batch, get batch, update batch, delete batch, with 2-hour TTL
- [x] 1.2 Create batch data structure: batchId (UUID), userId, status (uploading/extracting/review/anonymizing/completed), files array (fileId, fileName, status, entityCount, replacementCount, error), createdAt timestamp
- [x] 1.3 Add IAppConfig key `docudesk_batch_max_files` with default 100, read by BatchStateService to enforce batch size limit

## 2. Batch Upload Endpoint (Backend)

- [x] 2.1 Create `BatchAnonymizationController` class in `lib/Controller/` with batch-specific endpoints
- [x] 2.2 Implement `POST /api/anonymization/batch/upload` — accept multiple files via multipart, store each in DocuDesk/ folder using FileUploadService, create batch state, return batchId and per-file details
- [x] 2.3 Add batch upload validation: max file count check, authentication check, file upload error handling per file
- [x] 2.4 Register batch routes in `appinfo/routes.php`

## 3. Batch Extraction Endpoint (Backend)

- [x] 3.1 Create `BatchExtractionService` in `lib/Service/` that processes the next unextracted file in a batch using AnonymizationService::extractAndDetectEntities()
- [x] 3.2 Implement `POST /api/anonymization/batch/{batchId}/extract` — extract next file, update batch state, return progress
- [x] 3.3 Handle extraction errors per-file: set file status to "error", continue batch, return error details

## 4. Batch Status Endpoint (Backend)

- [x] 4.1 Implement `GET /api/anonymization/batch/{batchId}/status` — return batch state with per-file status, progress percentage, total entity count
- [x] 4.2 Handle expired/missing batch: return 404 with descriptive error

## 5. Entity Consolidation and Review Endpoint (Backend)

- [x] 5.1 Create `EntityConsolidationService` in `lib/Service/` that deduplicates entities across files by value (case-insensitive), calculates highest confidence and file count per entity
- [x] 5.2 Implement `GET /api/anonymization/batch/{batchId}/entities` — return consolidated entity list with included flag pre-set from WOO profile, support minConfidence query parameter
- [x] 5.3 Validate batch is in "review" status before returning entities, return 409 otherwise

## 6. WOO Entity Category Profiles (Backend)

- [x] 6.1 Create `WooProfileService` in `lib/Service/` — manage WOO entity profiles via IAppConfig key `docudesk_woo_entity_profiles`, provide default profile (anonymize: PERSON/BSN/PHONE/EMAIL/IBAN/ADDRESS, keep: ORGANIZATION/LOCATION/DATE)
- [x] 6.2 Implement `GET /api/anonymization/profiles` and `PUT /api/anonymization/profiles` (admin-only) in BatchAnonymizationController
- [x] 6.3 Integrate WooProfileService with EntityConsolidationService to pre-set included flags

## 7. Batch Anonymization Endpoint (Backend)

- [x] 7.1 Create `BatchAnonymizeService` in `lib/Service/` that applies a shared entity list to all extracted files using AnonymizationService::anonymizeDocument(), with consistent UUID pseudonyms across files
- [x] 7.2 Implement `POST /api/anonymization/batch/{batchId}/anonymize` — accept entities array, anonymize all extracted files, skip error files, update batch to completed
- [x] 7.3 Handle empty entity list validation (return 400)

## 8. Batch Report Endpoint (Backend)

- [x] 8.1 Create `BatchReportService` in `lib/Service/` that generates CSV report from completed batch state using fputcsv()
- [x] 8.2 Implement `GET /api/anonymization/batch/{batchId}/report` — return CSV download with Content-Disposition header, validate batch is completed (409 if not)

## 9. Extend Single-File Endpoints (Backend)

- [x] 9.1 Add optional `excludeTypes` array parameter to `POST /api/anonymization/anonymize/{fileId}` — filter entities by type before anonymization
- [x] 9.2 Add optional `minConfidence` float parameter to anonymize endpoint — filter entities below threshold
- [x] 9.3 Add `riskLevel` field to extract endpoint response using OpenRegister RiskLevelService (graceful fallback to "unknown")

## 10. Batch Anonymization Frontend (Vue)

- [x] 10.1 Create `BatchAnonymizationView.vue` in `src/views/anonymization/` with multi-step layout: Upload -> Extract -> Review -> Anonymize -> Done
- [x] 10.2 Implement multi-file drag-and-drop upload zone with file list preview and progress per file
- [x] 10.3 Create batch Pinia store in `src/store/modules/batchAnonymization.js` — manage batch state, poll extraction progress, store consolidated entities

## 11. Entity Review Frontend (Vue)

- [x] 11.1 Create `EntityReviewTable.vue` component in `src/views/anonymization/` — sortable table with checkbox, type badge, value, confidence, file count columns
- [x] 11.2 Implement entity search (text filter by value) and type dropdown filter with combined filtering
- [x] 11.3 Implement "Select All Visible" and "Deselect All Visible" bulk actions
- [x] 11.4 Implement confidence threshold slider/input that updates entity included flags
- [x] 11.5 Add summary bar showing "X of Y entities selected for anonymization across Z files"

## 12. Batch Progress and Report Frontend (Vue)

- [x] 12.1 Implement extraction progress view with per-file status indicators and overall progress bar
- [x] 12.2 Implement batch completion view with summary statistics and CSV report download button
- [x] 12.3 Add navigation from existing AnonymizationWidget to BatchAnonymizationView (e.g., "Batch Mode" toggle or tab)

## 13. Quality and Testing

- [ ] 13.1 Run `composer check:strict` and fix all PHPCS, PHPMD, Psalm, PHPStan issues in new/modified PHP files
- [ ] 13.2 Verify all new routes are registered correctly and return expected responses
- [ ] 13.3 Test batch upload with 1, 5, and 100+ files to verify size limits and progress tracking
- [ ] 13.4 Test entity review with WOO profile pre-selection and confidence threshold
- [ ] 13.5 Verify backward compatibility: existing single-file endpoints work unchanged
