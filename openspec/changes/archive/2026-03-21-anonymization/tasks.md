# Tasks: anonymization

## Task 1: File Upload Endpoint
- [x] Implement `POST /api/anonymization/upload` with multipart handling
- [x] Auto-create DocuDesk/ folder per user
- [x] Handle duplicate file names with counter suffix

## Task 2: Text Extraction and Entity Detection
- [x] Implement `POST /api/anonymization/extract/{fileId}`
- [x] Delegate to OpenRegister TextExtractionService
- [x] Normalize detected entities via EntityDetectionService

## Task 3: Document Anonymization
- [x] Implement `POST /api/anonymization/anonymize/{fileId}`
- [x] Map entities for anonymization via EntityDetectionService
- [x] Delegate to OpenRegister FileService::anonymizeDocument()

## Task 4: File Listing
- [x] Implement `GET /api/anonymization/files`
- [x] Return files with entity counts, risk levels, and status

## Task 5: Frontend Anonymization Wizard
- [x] Create 4-step wizard: Upload, Analyze, Anonymize, Done
- [x] Add drag-and-drop file upload
- [x] Show detected entities with type, value, confidence

## Task 6: Unit Tests (ADR-009)
- [x] Test file upload error handling
- [x] Test entity detection delegation
- [x] Test anonymization result parsing

## Task 7: Documentation + Screenshots (ADR-010)
- [x] Take screenshot of anonymization page
- [x] Write feature documentation at `docs/features/anonymization.md`

## Task 8: i18n (ADR-005)
- [x] Add Dutch translations for anonymization UI strings
- [x] Add English translations for anonymization UI strings
