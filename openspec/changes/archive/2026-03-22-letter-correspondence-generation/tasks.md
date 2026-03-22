## 1. Data Resolution Service

- [x] 1.1 Create `lib/Service/DataResolverService.php` with `resolve(array $dataRefs, array $adHocData = []): array` method that fetches objects from OpenRegister by register + schema + UUID
- [x] 1.2 Implement nested reference resolution in DataResolverService with depth limit of 3 levels and per-request object cache
- [x] 1.3 Implement ad-hoc data merging: merge adHocData on top of resolved data, ad-hoc overrides resolved values on key conflict
- [x] 1.4 Implement error handling: resolution failures return descriptive errors per reference without aborting other resolutions

## 2. Correspondence Schema and Register

- [x] 2.1 Add `correspondence` schema to `lib/Settings/docudesk_register.json` with fields: templateId, templateName, recipientId, recipientType, caseReference, generatedAt, format, status, generatedBy, errorMessage
- [x] 2.2 Add `huisstijl` schema to `lib/Settings/docudesk_register.json` with fields: logo, primaryColor, headerHtml, footerHtml, defaultMargins
- [x] 2.3 Ensure register is imported via `ConfigurationService::importFromApp()` on `Application::boot()` — version bumped to 4.0.0 triggers re-import

## 3. Correspondence Service

- [x] 3.1 Create `lib/Service/CorrespondenceService.php` with constructor injecting TemplateService, DataResolverService, TemplateRenderer, PdfService, ObjectService, IJobList, LoggerInterface
- [x] 3.2 Implement `generate(string $templateId, array $dataRefs, array $options = []): array` — fetch template, resolve data, render, produce output, log to register
- [x] 3.3 Implement huisstijl application: look up huisstijl config from OpenRegister, apply logo/header/footer/margins to rendered output
- [x] 3.4 Implement output format selection: PDF (default via PdfService), HTML (direct render), DOCX (LibreOffice headless conversion with 503 fallback), email (clean HTML without page styling)
- [x] 3.5 Implement correspondence register logging: create correspondence object in document register on both success and failure

## 4. Batch Correspondence

- [x] 4.1 Implement `generateBatch(string $templateId, array $recipientIds, array $options = []): array` in CorrespondenceService — synchronous for <=10 recipients
- [x] 4.2 Create `lib/BackgroundJob/BatchCorrespondenceJob.php` extending `\OCP\BackgroundJob\QueuedJob` for async batch processing (>10 recipients)
- [x] 4.3 Implement per-recipient error isolation: individual failures produce error entries without aborting the batch
- [x] 4.4 Implement job status storage: store progress (completed/total/errors) in app config or OpenRegister for status queries

## 5. REST API Endpoints

- [x] 5.1 Create `lib/Controller/CorrespondenceController.php` with `generate()`, `generateBatch()`, and `jobStatus()` methods
- [x] 5.2 Add routes to `appinfo/routes.php`: `POST /api/correspondence/generate`, `POST /api/correspondence/generate/batch`, `GET /api/correspondence/jobs/{jobId}`
- [x] 5.3 Implement request validation: templateId required, dataRefs required for single generation, recipientIds required for batch
- [x] 5.4 Implement response formats: DataDownloadResponse for single PDF/DOCX, JSONResponse for batch job ID, JSONResponse for job status

## 6. Quality and Testing

- [x] 6.1 Add PHPDoc blocks to all new classes and methods following existing DocuDesk conventions (PHPCS/PHPMD compliant)
- [x] 6.2 Run `composer check:strict` and fix any PHPCS, PHPMD, Psalm, or PHPStan violations
- [x] 6.3 Write unit tests for DataResolverService: object resolution, nested resolution, ad-hoc merging, error handling
- [x] 6.4 Write unit tests for CorrespondenceService: single generation, format selection, register logging
- [x] 6.5 Write unit tests for BatchCorrespondenceJob: batch processing, error isolation, progress tracking (covered in CorrespondenceServiceTest batch tests)
