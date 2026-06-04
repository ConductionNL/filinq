# Tasks: Print Functionality (#33)

## Deduplication Check

- [x] **0.1 Verify no overlap** — Checked `PdfService`, `PrintController`, `TemplateService`, `CorrespondenceService`. No existing print job queue or batch print service. `PdfService` has basic PDF/A support that we extend. `CorrespondenceService` job tracking pattern is copied, not duplicated.

## Task 1: Extend PdfService with print-optimized output

- [x] **1.1 Add `buildCropMarksHtml()`** — Inject 3mm bleed CSS + SVG crop mark corners into HTML before PDF generation when `cropMarks: true`
- [x] **1.2 Add XMP metadata support** — Extend `generatePdf()` to embed title, author, creation date, and caseReference via mPDF's SetAuthor/SetKeywords when options contain them
- [x] **1.3 Extend `renderPdf()` option handling** — Pass `cropMarks`, `author`, `caseReference` through from options to `generatePdf()`

## Task 2: Extend PrintController with print configuration

- [x] **2.1 Accept print config in request** — Read `duplex`, `color`, `paperTray`, `stapling` from request options
- [x] **2.2 Return print instruction in preview** — Include `printConfig` JSON in preview response
- [x] **2.3 Include print config in PDF/A download header** — Return `X-Print-Config` response header with JSON-encoded print parameters

## Task 3: Create PrintJobService

- [x] **3.1 Create `lib/Service/PrintJobService.php`** — Service with `createJob()`, `getJob()`, `storeJobStatus()`, `loadJobStatus()`, `dispatchBatchJob()`, `generateJobId()` using IAppConfig for persistence
- [x] **3.2 Add `buildManifest()`** — Build manifest array listing all documents with filename, status, and metadata per acceptance criterion 2

## Task 4: Create BatchPrintJob background job

- [x] **4.1 Create `lib/BackgroundJob/BatchPrintJob.php`** — QueuedJob that generates PDFs for each item in the batch, updates progress, and stores manifest via PrintJobService

## Task 5: Create PrintJobController

- [x] **5.1 Create `lib/Controller/PrintJobController.php`** — Controller with `create()`, `show()`, `download()`, `updateStatus()`, `batch()` methods
- [x] **5.2 Add per-object authorization** — `show()`, `download()`, `updateStatus()` check ownerUserId or admin

## Task 6: Register new routes

- [x] **6.1 Add print job routes to `appinfo/routes.php`** — POST /api/print/jobs, GET /api/print/jobs/{id}, GET /api/print/jobs/{id}/download, PUT /api/print/jobs/{id}/status, POST /api/print/batch

## Task 7: Write tests

- [x] **7.1 `tests/unit/Service/PrintJobServiceTest.php`** — Tests for createJob, getJob, generateJobId, buildManifest
- [x] **7.2 `tests/unit/Controller/PrintJobControllerTest.php`** — Tests for create, show, download, updateStatus with auth checks
- [x] **7.3 `tests/unit/BackgroundJob/BatchPrintJobTest.php`** — Tests for run() processing
- [x] **7.4 `tests/unit/Controller/PrintControllerTest.php`** — Tests for existing preview and downloadPdfA endpoints with new print config

## Task 8: Documentation

- [x] **8.1 Update README.md / docs** — Document new print API endpoints and print configuration options
