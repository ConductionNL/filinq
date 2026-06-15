## Why

The folder batch anonymization endpoint (`POST /api/anonymization/batch/folder`) currently only accepts a folder path. Paths are fragile: they break when folders are renamed or moved, they are awkward to derive from Nextcloud's native FilePicker (which returns node IDs), and they are an unnatural fit for integrations triggered from a context where the folder ID is already known (e.g. the Files app, Files sidebar actions, or other Conduction apps that reference nodes by ID). Adding folder ID as an alternative input makes the API rename-proof, picker-friendly, and usable from external callers without forcing them to reconstruct paths.

## What Changes

- Accept an optional `folderId` (integer) on `POST /api/anonymization/batch/folder` as an alternative to `folderPath`.
- Require exactly one of `folderId` or `folderPath`. Reject requests that provide neither (400) or both (400) — explicit over implicit.
- Resolve folder ID via `IRootFolder::getUserFolder($userId)->getById($folderId)`. When multiple nodes are returned (same file ID surfaces through multiple mounts in the user's tree), prefer a node with write permission, then fall back to any readable node.
- Store both `folderId` and `folderPath` on the batch regardless of which input was used. The path is a snapshot captured at batch creation time; the ID is canonical and rename-proof.
- Return both `folderId` and `folderPath` in the response, regardless of which input was used. Consistent response shape lets callers use the ID on subsequent runs without reconstructing it.
- Preserve existing path-based callers: the path input path remains fully supported and behaves identically.
- No frontend changes in this change. The existing `FolderAnonymizationView.vue` text-input UX is untouched; a future change can add a picker that uses the new ID input.

## Capabilities

### New Capabilities
<!-- None - this modifies an existing (in-progress) capability. -->

### Modified Capabilities
- `folder-batch-analysis`: extend the folder batch initiation requirement to accept `folderId` as an alternative to `folderPath`, and to return both identifiers in the response.

## Impact

- **Controller**: `docudesk/lib/Controller/BatchAnonymizationController.php::folderBatch()` — read and validate `folderId`/`folderPath` params (XOR).
- **Service**: `docudesk/lib/Service/FolderBatchService.php::createFolderBatch()` — signature becomes `(?int $folderId, ?string $folderPath)`. Add ID-based node resolution with the prefer-writable rule. Resolve and stash both identifiers on the batch.
- **Route**: `docudesk/appinfo/routes.php` — unchanged (same endpoint, additive body param).
- **Tests**: `docudesk/tests/unit/Controller/BatchAnonymizationControllerFolderTest.php` and `docudesk/tests/unit/Service/FolderBatchServiceTest.php` — add ID-happy-path, ID-not-found, ID-not-a-folder, ID-empty-folder, ID-too-many-files, prefer-writable-when-multiple-nodes, and XOR-validation cases.
- **Response shape**: now always includes `folderId` and `folderPath` alongside `batchId`, `fileCount`, `files`. Existing path-based callers receive a superset — no breaking change.
- **Frontend**: unchanged. `FolderAnonymizationView.vue` and `folderAnonymization.js` store continue to send `{ folderPath }` and work as before.
- **Scope explicitly excluded**: frontend picker integration, Files app action registration, recursive folder scanning, fuzzy cross-document entity matching.
