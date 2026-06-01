# Tasks: document-creatie-sjablonen

## Task 1: Core Implementation
- [x] Implement service classes
  - [x] `lib/Service/DocumentService.php` — orchestrates data resolution, template rendering, PDF/ODF/HTML output, huisstijl, bulk, job dispatch
  - [x] `lib/BackgroundJob/BatchDocumentJob.php` — async queued job for bulk generation >10 objects
- [x] Add API endpoints
  - [x] `lib/Controller/DocumentController.php` — POST /api/documents/generate, /preview, /generate/bulk; GET /api/documents/jobs/{jobId}
  - [x] `appinfo/routes.php` — four new document routes registered
- [x] Add configuration settings
  - [x] `lib/Settings/docudesk_register.json` — added `generatedDocument` schema to document register (DCS-051, DCS-072)

## Task 2: Testing
- [x] Unit tests
  - [x] `tests/unit/Service/DocumentServiceTest.php` — generateDocument, generatePreview, generateBulk, partial failure, template versioning, huisstijl, warnings
  - [x] `tests/unit/Controller/DocumentControllerTest.php` — all endpoints, auth, format routing, error cases
  - [x] `tests/unit/BackgroundJob/BatchDocumentJobTest.php` — missing args, partial failure, completed status
- [ ] Integration tests

## Task 3: Documentation
- [x] API documentation
  - [x] README.md updated with new document generation endpoints
- [ ] Admin guide
