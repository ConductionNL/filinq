## Tasks

### Deduplication Check

- [ ] 1. **Deduplication check** — verify no overlap with existing DocuDesk services: confirm `CorrespondenceService` is a new orchestration layer distinct from `TemplateService` (template CRUD), `PdfService` (stateless rendering), and `DataResolverService` (data resolution); confirm `CorrespondenceController` endpoints (`/api/correspondence/...`) do not duplicate any existing routes in `appinfo/routes.php`; document findings in a comment block in `CorrespondenceService.php` header docblock.

### Backend — Service Layer

- [ ] 2. **`CorrespondenceService::generate()`** — implement `lib/Service/CorrespondenceService.php` with `generate(string $templateId, array $dataRefs, array $options = []): array`; inject `TemplateService`, `DataResolverService`, `TemplateRenderer`, `PdfService`, `ObjectService`, `IUserSession`; call `TemplateService::getTemplate()`, delegate data resolution to `DataResolverService::resolve()`, call `TemplateRenderer::render()`, route to the appropriate output handler by `$options['format']` (default `pdf`), call `ObjectService::saveObject()` to write the `correspondence` audit record on both success and failure; return `['documentBinary' => ..., 'filename' => ..., 'mimeType' => ..., 'warnings' => [], 'correspondenceId' => ...]`.

- [ ] 3. **`CorrespondenceService::generateBatch()`** — implement `generateBatch(string $templateId, array $recipientIds, array $options = []): array`; for `count($recipientIds) <= 10` iterate synchronously calling `generate()` per recipient, catching per-recipient exceptions and recording `status: "error"` without aborting the loop; for `count($recipientIds) > 10` call `IJobList::add(BatchCorrespondenceJob::class, ['templateId' => ..., 'recipientIds' => ..., 'options' => ...])` and return `['jobId' => <uuid>, 'status' => 'queued', 'totalRecipients' => N]`.

- [ ] 4. **Huisstijl resolution** — within `generate()`, after template lookup, call `ObjectService::searchObjects(['schema' => 'huisstijl', 'register' => 'document', '_limit' => 1])` (or `ObjectService::find($options['huisstijlId'])` when `huisstijlId` is provided in options); map the result to `PdfService` options: `margins`, `header`, `footer`; fall back to `['margins' => ['top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15]]` when no huisstijl object is found.

- [ ] 5. **`LibreOfficeConverter`** — implement `lib/Service/LibreOfficeConverter.php` with `convert(string $htmlContent, string $targetFormat = 'docx'): string`; write HTML to a temp file, invoke `soffice --headless --convert-to {format} --outdir /tmp {file}`, read and return the output binary, clean up temp files; throw `\RuntimeException("DOCX conversion service unavailable")` if `soffice` is not found (`exec('which soffice')` fails) or exits non-zero.

- [ ] 6. **Email HTML post-processing** — within `generate()` for `format = "email"`, after `TemplateRenderer::render()`, strip all `@page` CSS rules (regex on `<style>` blocks), convert block-level `style` attributes to inline styles using a lightweight CSS inliner (or a simple regex pass for common properties), strip `<html>/<head>/<body>` wrapper tags and return only the inner body fragment.

- [ ] 7. **`BatchCorrespondenceJob`** — implement `lib/Job/BatchCorrespondenceJob.php` extending Nextcloud `QueuedJob`; in `run(array $argument)`, iterate over `$argument['recipientIds']`, call `CorrespondenceService::generate()` for each, store per-recipient results (keyed by `jobId`) in an OpenRegister object (register: `document`, schema: `batchJob` — or use IAppConfig if schema is out of scope); update a progress counter after each recipient.

### Backend — Controller and Routes

- [ ] 8. **`CorrespondenceController`** — implement `lib/Controller/CorrespondenceController.php` with three methods:
  - `generate()`: read `templateId`, `dataRefs`, `options`, `filename` from JSON body; validate `templateId` (required, return 400 if absent) and `dataRefs` (required, return 400 if absent); call `CorrespondenceService::generate()`; return `DataDownloadResponse` with the binary and correct `Content-Type`; on `\Exception` with code 503 return 503 JSON; on `\Exception` with code 404 return 404 JSON; on other exceptions return 500 JSON.
  - `generateBatch()`: read `templateId`, `dataRefs`, `options` from JSON body; validate; call `CorrespondenceService::generateBatch()`; return 200 JSON (array results, ≤ 10) or 202 JSON (`jobId`, > 10).
  - `jobStatus(string $jobId)`: read job progress from OpenRegister/IAppConfig; return 200 JSON with `jobId`, `status`, `completed`, `total`, `errors`.

- [ ] 9. **Routes** — add to `appinfo/routes.php`:
  ```php
  ['name' => 'Correspondence#generate',      'url' => '/api/correspondence/generate',       'verb' => 'POST'],
  ['name' => 'Correspondence#generateBatch', 'url' => '/api/correspondence/generate/batch', 'verb' => 'POST'],
  ['name' => 'Correspondence#jobStatus',     'url' => '/api/correspondence/jobs/{jobId}',   'verb' => 'GET'],
  ```
  Ensure these routes are registered **before** any wildcard `{slug}` catch-all routes.

### Schema and Seed Data

- [ ] 10. **Seed data — `huisstijl`** — add 3 seed objects to `lib/Settings/docudesk_register.json` under `components.objects[]` using the `@self` envelope (register: `document`, schema: `huisstijl`); use realistic Dutch municipality/ministry brand data (see design.md Seed Data section); slugs: `huisstijl-gemeente-westerbork`, `huisstijl-gemeente-hoogeveen`, `huisstijl-ministerie-vws`.

- [ ] 11. **Seed data — `correspondence`** — add 4 seed audit-log objects to `lib/Settings/docudesk_register.json` (register: `document`, schema: `correspondence`); include at least one with `status: "failed"` and a populated `errorMessage`; see design.md Seed Data section for values. Verify idempotency: `importFromApp()` with `force: false` must not create duplicates on re-import.

### Tests

- [ ] 12. **Unit tests — `CorrespondenceService`** — create `tests/unit/Service/CorrespondenceServiceTest.php`; mock `TemplateService`, `DataResolverService`, `TemplateRenderer`, `PdfService`, `ObjectService`, `IJobList`; assert: (a) generate() calls all sub-services in correct order; (b) missing template propagates 404 exception; (c) missing merge fields return warnings array; (d) generateBatch() iterates synchronously for ≤ 10 recipients; (e) generateBatch() calls `IJobList::add()` for > 10 recipients; (f) per-recipient exception in synchronous batch does not abort loop; (g) `correspondence` audit record is saved on both success and failure.

- [ ] 13. **Unit tests — `CorrespondenceController`** — create `tests/unit/Controller/CorrespondenceControllerTest.php`; assert: (a) missing `templateId` returns 400; (b) missing `dataRefs` returns 400; (c) successful generate returns `DataDownloadResponse`; (d) 503 from LibreOfficeConverter surfaces as HTTP 503; (e) generateBatch() returns 202 for > 10 recipients; (f) generateBatch() returns 200 array for ≤ 10 recipients.

- [ ] 14. **Unit tests — `LibreOfficeConverter`** — create `tests/unit/Service/LibreOfficeConverterTest.php`; mock `exec()`; assert: (a) unavailable soffice throws `RuntimeException`; (b) non-zero exit code throws `RuntimeException`; (c) successful conversion returns binary string.

- [ ] 15. **Integration tests — Newman** — add Newman collection entries for: (a) `POST /api/correspondence/generate` with valid template + BRP dataRef → assert 200, `Content-Type: application/pdf`; (b) `POST /api/correspondence/generate` missing `templateId` → assert 400 + message; (c) `POST /api/correspondence/generate/batch` with 50 dataRefs → assert 202 + `jobId`; (d) `GET /api/correspondence/jobs/{jobId}` → assert 200 + `status`/`completed`/`total` fields; (e) `POST /api/correspondence/generate` with `options.format = "email"` → assert 200, `Content-Type: text/html`, no `@page` rules in response.

### Quality and Verification

- [ ] 16. **`@spec` PHPDoc tags** — add `@spec openspec/changes/letter-correspondence-generation/tasks.md#task-2` (and matching task numbers) to all new classes and public methods: `CorrespondenceService`, `CorrespondenceController`, `BatchCorrespondenceJob`, `LibreOfficeConverter`; add file-level `@spec` tag in the header docblock of each new file.

- [ ] 17. **`composer check:strict` clean** — run `composer check:strict` and resolve any issues in the touched files; do not suppress existing pre-change issues in files not modified by this change.

- [ ] 18. **Manual smoke test** — against a live Nextcloud stack: generate a single PDF letter via `POST /api/correspondence/generate` with a real template and a BRP seed object; verify the PDF contains the merged data; test `format = "email"` and confirm no `@page` rules in the output; test batch with 12 recipients (> 10 threshold) and confirm 202 + jobId response; poll `GET /api/correspondence/jobs/{jobId}` until `status: "completed"`.

- [ ] 19. **`openspec validate letter-correspondence-generation`** — run once the tool is available; confirm all requirements in `specs/correspondence-generation/spec.md` are referenced by tasks and the change validates cleanly.
