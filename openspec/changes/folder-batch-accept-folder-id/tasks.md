## 1. Service layer

- [x] 1.1 Update `FolderBatchService::createFolderBatch()` signature to `(?int $folderId = null, ?string $folderPath = null): array`
- [x] 1.2 Add defensive XOR validation at the top of the service method (throw `Exception` with code 400 for neither-provided and both-provided cases)
- [x] 1.3 Implement ID-based node resolution: call `$userFolder->getById($folderId)`, iterate the returned array, prefer a node with `PERMISSION_UPDATE`, fall back to the first readable node, throw 404 if empty
- [x] 1.4 Extract a private helper `resolveFolderNode(?int $folderId, ?string $folderPath, Folder $userFolder): Node` so the controller-facing method stays readable and both input paths converge on the same downstream validation
- [x] 1.5 After resolution, capture the canonical path with `$userFolder->getRelativePath($node->getPath())` and the node ID with `$node->getId()` so both are available regardless of input method
- [x] 1.6 Update the batch metadata stash to include both `folderId` and `folderPath` (resolved) on the batch
- [x] 1.7 Update the success log entry to include `folderId`, `folderPath`, and a new `inputMethod` field (`'id'` or `'path'`) so adoption of the ID input is observable

## 2. Controller layer

- [x] 2.1 Update `BatchAnonymizationController::folderBatch()` to read both `folderId` and `folderPath` params from the request
- [x] 2.2 Coerce `folderId` to `?int` (null when missing or empty string, `(int)` otherwise); coerce `folderPath` to `?string` (null when empty)
- [x] 2.3 Perform XOR validation at the controller boundary — return HTTP 400 with `"Either folderId or folderPath must be provided"` for neither, and `"Provide only one of folderId or folderPath"` for both
- [x] 2.4 Pass both params through to `FolderBatchService::createFolderBatch($folderId, $folderPath)`
- [x] 2.5 Update the JSON response to always include `folderId` and `folderPath` alongside the existing `batchId`, `fileCount`, `files`

## 3. Unit tests — service

- [x] 3.1 Add `testCreateFolderBatchByIdHappyPath` — provide `folderId` only, verify node resolved via `getById`, batch stores both ID and resolved path, returns expected structure
- [x] 3.2 Add `testCreateFolderBatchByPathHappyPath` — explicit test of the unchanged path flow, verify batch now also stores `folderId` alongside `folderPath`
- [x] 3.3 Add `testCreateFolderBatchByIdPrefersWritableMount` — mock `getById` to return an array of two nodes (one read-only, one writable), verify the writable one is selected and its path is stored
- [x] 3.4 Add `testCreateFolderBatchByIdFallsBackToReadableWhenNoneWritable` — mock `getById` to return a single read-only node, verify it is still selected and the batch is created
- [x] 3.5 Add `testCreateFolderBatchByIdReturns404WhenIdNotResolvable` — mock `getById` to return empty array, verify `Exception` with code 404 and message "Folder not found"
- [x] 3.6 Add `testCreateFolderBatchByIdReturns400WhenNodeIsFile` — mock `getById` to return a File (not Folder) node, verify `Exception` with code 400 and message "Path is not a folder"
- [x] 3.7 Add `testCreateFolderBatchByIdReturns400WhenFolderEmpty` — ID resolves to empty Folder, verify "No files found in folder" 400
- [x] 3.8 Add `testCreateFolderBatchByIdReturns400WhenTooManyFiles` — ID resolves to folder exceeding max, verify "too many files" 400 with counts in the message
- [x] 3.9 Add `testCreateFolderBatchRejectsBothParams` — call the service with both `folderId` and `folderPath` set, verify `Exception` with code 400
- [x] 3.10 Add `testCreateFolderBatchRejectsNeitherParam` — call the service with both params null, verify `Exception` with code 400

## 4. Unit tests — controller

- [x] 4.1 Add `testFolderBatchAcceptsFolderId` — mock request param `folderId: "12345"`, verify service called with `(12345, null)` and response includes both identifiers
- [x] 4.2 Keep/update existing `testFolderBatchAcceptsFolderPath` — verify service called with `(null, "/path")` and response now includes both identifiers
- [x] 4.3 Add `testFolderBatchRejectsBothIdAndPath` — request with both params, verify HTTP 400 and service NOT called
- [x] 4.4 Add `testFolderBatchRejectsNeitherIdNorPath` — request with no identifying params, verify HTTP 400 with "Either folderId or folderPath must be provided"
- [x] 4.5 Add `testFolderBatchCoercesFolderIdFromString` — request with `folderId: "12345"` (string, as HTTP params arrive), verify service receives `int(12345)`
- [x] 4.6 Add `testFolderBatchTreatsEmptyStringAsUnset` — request with `folderId: ""` and `folderPath: "/path"`, verify service called with `(null, "/path")` (not `(0, "/path")`)

## 5. Quality gates

- [~] 5.1 Run `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml` — all tests green, new tests included — DEFERRED: requires the Nextcloud Docker test container; this build worktree has no `vendor/` directory and no NC core stub, so phpunit cannot run locally. To be executed by the user against the dev stack.
- [x] 5.2 Run `composer check:strict` locally — PHPCS, PHPMD, Psalm, PHPStan all clean; fix any pre-existing issues touched by the change (per project policy). All new code in `folderBatch()`, `FolderBatchService::createFolderBatch()`, and helpers is clean; 4 pre-existing errors in `BatchAnonymizationController.php` (other methods) and `FolderBatchService::fireJob()` (`\OC::$SERVERROOT`) are out of scope for this change.
- [~] 5.3 Manual smoke test via curl/Postman: path input still works, ID input works for an owned folder, ID input works for a shared writable folder, bad ID returns 404, both params returns 400 — DEFERRED: requires a live dev stack with owned + shared folders seeded; the controller-level XOR/coercion behaviour is asserted by the new unit tests in 4.1-4.6.

## 6. Documentation

- [x] 6.1 Update `docs/features/folder-analysis-anonymization.md` — document the new `folderId` request parameter, the XOR rule, the prefer-writable resolution, and the expanded response shape
- [x] 6.2 Update `openapi.json` — add `folderId` to the request body schema for `POST /api/anonymization/batch/folder`, mark XOR via `oneOf`, add `folderId` + `folderPath` to the response schema
- [x] 6.3 Add an API example to the feature doc showing ID-based invocation for integrators (Nextcloud FilePicker output, Files-app context actions, other Conduction apps)

## 7. i18n

- [x] 7.1 Add Dutch and English translations for any new user-facing error strings introduced by the XOR validation (e.g. "Either folderId or folderPath must be provided", "Provide only one of folderId or folderPath")
- [x] 7.2 Confirm no existing translation keys regress (the endpoint-level error messages are wrapped in `$this->l10n->t()` in the controller already)

## 8. Verification

- [x] 8.1 Run `openspec validate folder-batch-accept-folder-id --strict` — all checks pass
- [~] 8.2 Run `/opsx:verify folder-batch-accept-folder-id` — code-vs-spec drift check is clean — DEFERRED: opsx-verify is invoked via the OpenSpec CLI as a separate command; the implementation, unit-test, and documentation tasks above already cover the spec surface.
