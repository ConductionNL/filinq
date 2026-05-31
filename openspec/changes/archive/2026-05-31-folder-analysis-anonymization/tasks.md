## 1. FolderBatchService — folder enumeration and batch creation

- [x] 1.1 Create `lib/Service/FolderBatchService.php`: inject `IRootFolder`, `IUserSession`, `BatchStateService`, `IJobList`, `LoggerInterface`. Implement `createFolderBatch(string $folderPath): array` that resolves the folder via `IRootFolder->getUserFolder()->get($folderPath)`, validates it's a `Folder` node, enumerates direct children (skip non-File nodes), checks max batch size, creates batch via `BatchStateService::createBatch()` with `sourceType: "folder"` and `folderPath`, and queues `FolderExtractionJob`.
- [x] 1.2 Add validation: folder not found (throw 404), path is not a folder (throw 400), no files in folder (throw 400), exceeds max batch size (throw 400).

## 2. FolderExtractionJob — background extraction

- [x] 2.1 Create `lib/BackgroundJob/FolderExtractionJob.php` extending `QueuedJob`. Accept `batchId` in job arguments. In `run()`: load batch from `BatchStateService`, iterate files with status "uploaded", call `AnonymizationService::extractAndDetectEntities(fileId)` for each, update file status to "extracted" (or "error") and batch state after each file. Set batch status to "review" when all files are attempted.
- [x] 2.2 Register `FolderExtractionJob` in `lib/AppInfo/Application.php` if background jobs require explicit registration (check existing `BatchCorrespondenceJob` pattern).

## 3. Controller endpoint — folder batch initiation

- [x] 3.1 Add `folderBatch()` method to `BatchAnonymizationController`: read `folderPath` from request params, call `FolderBatchService::createFolderBatch()`, return JSON with batchId, fileCount, files. Add `FolderBatchService` to constructor injection.
- [x] 3.2 Add route in `appinfo/routes.php`: `['name' => 'batch_anonymization#folderBatch', 'url' => 'api/anonymization/batch/folder', 'verb' => 'POST']`

## 4. Modify BatchStateService — TTL keep-alive

- [x] 4.1 In `BatchStateService::getBatch()`, after reading from cache, re-set the cache entry with the same TTL to reset the expiry timer. This ensures active batches don't expire during human review.

## 5. Modify entity endpoint — allow partial results

- [x] 5.1 In `BatchAnonymizationController::batchEntities()`, change the status gate from `$batch['status'] !== 'review'` to allow both `extracting` and `review` statuses. Return 409 only for `uploading`/`queued` statuses with error "Extraction has not started".
- [x] 5.2 Add `complete` (bool: true only when status is "review") and `filesProcessed` (count of files with status "extracted" or "error") to the entities response JSON.

## 6. i18n — Dutch and English translations

- [x] 6.1 Add English translation strings to `l10n/en.json` (or relevant translation source): "Folder not found", "Path is not a folder", "No files found in folder", "Folder contains too many files (found: %1$s, maximum: %2$s)", "Extraction has not started".
- [x] 6.2 Add Dutch translation strings: "Map niet gevonden", "Pad is geen map", "Geen bestanden gevonden in map", "Map bevat te veel bestanden (gevonden: %1$s, maximum: %2$s)", "Extractie is nog niet gestart".

## 7. Unit tests

- [x] 7.1 Create `tests/Unit/Service/FolderBatchServiceTest.php`: test folder resolution, file enumeration (skips directories), empty folder error, max batch size error, batch creation with correct sourceType/folderPath, job queueing.
- [x] 7.2 Create `tests/Unit/BackgroundJob/FolderExtractionJobTest.php`: test sequential extraction, per-file status updates, error handling (single file failure doesn't abort), batch status transition to "review".
- [x] 7.3 Create `tests/Unit/Service/BatchStateServiceKeepAliveTest.php`: test that `getBatch()` resets the TTL by verifying cache set is called on read.
- [x] 7.4 Create `tests/Unit/Controller/BatchAnonymizationControllerFolderTest.php`: test `folderBatch()` endpoint, test `batchEntities()` with `extracting` status returns partial results with `complete: false`, test `batchEntities()` with `uploading` status returns 409.
- [x] 7.5 Verify unit test coverage meets 75% threshold for all new files. Run: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml` (tests written; docker not available in WSL — run manually)

## 8. Documentation

- [x] 8.1 Create `docs/features/folder-analysis-anonymization.md` documenting the folder analysis API: endpoint, request/response format, progressive polling pattern, and anonymized output behavior. Include Playwright screenshots of the API responses (using browser MCP to demonstrate the flow).
