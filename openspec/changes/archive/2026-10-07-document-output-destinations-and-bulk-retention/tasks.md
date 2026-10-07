# Tasks: document-output-destinations-and-bulk-retention

## 1. Backend

- [x] 1.1 New `lib/Service/DocumentStorageService.php`: `validateTargetPath()` (relative, no `..`, no leading `/`, `[A-Za-z0-9 _.-]` charset per segment, HTTP 400 on violation) + `store(userId, targetPath, filename, content)` (segment-by-segment idempotent folder creation, `Folder::getNonExistingName()` dedupe, `newFile()` write, storage-layer failures wrapped as HTTP 507) (REQ-DDOB-002, REQ-DDOB-003)

- [x] 1.2 `DocumentService`: inject `DocumentStorageService`; add `resolveOutputMode()` (validates `options.output.mode` enum, defaults `return`), `buildOutputTargetPath()` (public — explicit targetPath wins, else template namespace default; accepts an optional pre-fetched `$template` to avoid a redundant lookup on the single-generate path), `buildOutputFilename()` (mirrors the controller's format→extension mapping) (REQ-DDOB-001, REQ-DDOB-002)

- [x] 1.3 `DocumentService::generateDocument()`: when mode is `files`/`both`, resolve target path + filename, call storage, wire `fileId`/`path`/`name`/`size` into the returned `output` sub-array; catch storage exceptions — code 400 always re-thrown, code 507 re-thrown for `files` but downgraded to a `warnings[]` entry for `both` (REQ-DDOB-001, REQ-DDOB-003)

- [x] 1.4 `DocumentService::logGeneratedDocument()`: new optional `fileId`/`filePath` parameters (additive, named-arg-safe), written onto the `generatedDocument` entry (REQ-DDOB-004)

- [x] 1.5 `DocumentService::generateBulk()`: new `validateAsyncOutputMode()` check before the sync/async branch decides — for >10 objects, `options.output.mode` must be exactly `files`; HTTP 400 otherwise, dispatch never happens (REQ-DDOB-005)

- [x] 1.6 `DocumentService::generateBulkSync()`: per-object result item shape depends on the resolved mode — `content` for `return`, `{fileId, path, name, size}` for `files`, both for `both` (REQ-DDOB-007)

- [x] 1.7 `DocumentService::dispatchBulkJob()`: compute the per-job `targetPath` once (`buildOutputTargetPath()` + `/<jobId>`) and store it in the dispatched job's `options.output.targetPath` so every per-object `generateDocument()` call in the job uses the same folder (REQ-DDOB-006)

- [x] 1.8 `BatchDocumentJob::processObjects()`: forward the per-job-computed `options` (mode/targetPath already set by `dispatchBulkJob()`) to `generateDocument()` unchanged; add `fileId`/`path` from the result's `output` sub-array to each success result item (REQ-DDOB-006)

- [x] 1.9 `DocumentController`: `parseGenerateParams()` threads `filename` into `options['filename']` (additive; not currently used by any options consumer) so the service layer can compute a storage filename; `buildDocumentResponse()` branches on `result['output']['mode'] ?? 'return'` — `files` → JSON refs, `both` → existing branch + `X-Docudesk-File-Id`/`X-Docudesk-File-Path` headers, `return` → byte-identical to before (REQ-DDOB-001, REQ-DDOB-003)

- [x] 1.10 `lib/Settings/filinq_register.json`: `generatedDocument` schema gains additive, non-required `fileId` (integer) / `filePath` (string) properties; schema + register + app description version bumps (REQ-DDOB-004)

- [x] 1.11 Explicitly no changes to `CorrespondenceService`/`CorrespondenceController` (REQ-DDOB-008) — verified by `git diff` scope check before opening the PR

## 2. Quality

- [x] 2.1 `@spec` tags on every new/changed method pointing at this change's spec
- [x] 2.2 phpcs / phpstan / psalm / phpmd clean on all changed/new files (via `nextcloud:34.0.0-apache` container — host PHP is 8.2, this repo's composer deps require >=8.3)

## 3. Tests

- [x] 3.1 `tests/unit/Service/DocumentStorageServiceTest.php` (new): path validation (leading slash, `..` traversal, disallowed charset, empty path — all rejected), folder auto-creation (missing segments created, existing segments reused, not recreated), filename dedupe via `getNonExistingName()`, storage-layer exception wrapped as code 507
- [x] 3.2 `tests/unit/Service/DocumentServiceTest.php` (extended): mode routing (`return`/`files`/`both`) end-to-end with a mocked `DocumentStorageService`, `output.mode` omitted is unchanged (byte-identical `content`/`format`/`metadata`/`warnings` shape plus the new nullable `fileId`/`filePath` audit fields), invalid mode value is HTTP 400, `files`-mode storage failure is a hard failure, `both`-mode storage failure fails open with a warning and no `output.fileId`, async-bulk-without-files-mode is HTTP 400 (both omitted and `both`), async-bulk-with-files-mode dispatches with a computed per-job `targetPath`, sync-bulk per-object mode routing (`return`/`files`/`both`)
- [x] 3.3 `tests/unit/Controller/DocumentControllerTest.php` (extended): `generate()` `files` mode returns JSON refs (200, no binary), `both` mode returns the binary with `X-Docudesk-File-Id`/`X-Docudesk-File-Path` headers, existing `return`-mode tests continue to pass unmodified (regression guard)
- [x] 3.4 `tests/unit/BackgroundJob/BatchDocumentJobTest.php` (extended): success results gain `fileId`/`path` from the service's `output` sub-array; existing tests (missing-args, all-success, partial-failure) continue to pass with the service mock updated to return an `output` sub-array
- [x] 3.5 Full `tests/unit` suite run clean in the `nextcloud:34.0.0-apache` container (only pre-existing, unrelated failures — see report)
- [x] 3.6 Live verify on the checkout served at localhost:8080 (see report for exact commands/evidence): (a) `generate` with `mode: files` for spectr-app-report app 6 → JSON `fileId` + file exists; (b) `mode: both` → binary + `X-Docudesk-File-Id` header + file exists; (c) `mode: return`/absent → byte-identical to before (regression guard for the spectr button); (d) bulk: 12 objects + `mode: files` → 202 + jobId, job executed via `occ background-job:execute`, `jobStatus` polled, per-object `fileId`/`path` present, files exist; cleanup afterward

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `options.output` omitted/`return` behaviour byte-identical to before this change (additive only)
- No new routes; existing generate/generate-bulk routes gain an optional request field
- `CorrespondenceService`/`CorrespondenceController` untouched
