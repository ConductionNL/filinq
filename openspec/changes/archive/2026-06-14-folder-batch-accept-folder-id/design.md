## Context

The folder batch anonymization endpoint `POST /api/anonymization/batch/folder` currently accepts only `folderPath`. The resolution flow in `FolderBatchService::createFolderBatch()` is a single `$userFolder->get($folderPath)` call, after which the node is validated (is Folder, not empty, within max-files) and a batch is created. The batch stores `folderPath` for display/logging.

Three drivers push toward also accepting `folderId`:

1. **Rename/move resilience** — path-keyed references break when a user renames the folder or moves it; the file ID survives.
2. **Native Nextcloud FilePicker** — `@nextcloud/dialogs` returns node IDs; path derivation is extra work.
3. **External callers** — future Files-app context actions and other Conduction apps already work with node IDs.

The change is intentionally scoped to the backend. The existing text-input UX in `FolderAnonymizationView.vue` stays untouched; a future change can layer a picker on top.

Relevant constraints from the codebase:

- `IRootFolder::getUserFolder($userId)->getById(int $id)` returns `Node[]` — not a single node. The same backing file can surface in multiple mounts within one user's tree (personal storage + group folder + share), each appearing as a separate Node with different paths and potentially different permissions.
- The existing path flow wraps `NotFoundException` as a 404 and "not a folder" as a 400. The ID flow must preserve the same error contract.
- Controllers already follow the ADR-008 layering (controller validates input shape, service owns the domain logic).

## Goals / Non-Goals

**Goals:**

- Accept `folderId` (int) as an alternative to `folderPath` (string) on the folder-batch endpoint.
- Enforce exactly one of the two as input (XOR) — reject both neither-provided and both-provided with 400.
- Resolve ID → Node using a deterministic "prefer writable, fall back to readable" rule when multiple mounts exist.
- Store and return both `folderId` and `folderPath` on every batch, regardless of which input was used. Canonical ID + snapshot path.
- Preserve the existing path-input behavior and response contract as a superset — no breaking changes.

**Non-Goals:**

- Frontend picker integration (deferred; this backend change is the reference implementation for it).
- Files app action registration / Files sidebar integration (separate future change).
- Recursive folder scanning (explicitly deferred — current behavior remains flat).
- Fuzzy cross-document entity matching (explicitly deferred).
- Changing how anonymized files are written back or how the background job consumes the batch.

## Decisions

### 1. Two discrete params with XOR validation (not a single polymorphic param)

Accept `{ folderId?: int, folderPath?: string }`. Exactly one MUST be provided. Reject both "neither provided" and "both provided" with HTTP 400.

**Why over a single polymorphic `folder` string:** A single param that treats numeric strings as IDs and others as paths is ambiguous — a user could legitimately name a folder `"42"`. Explicit fields make the caller's intent unambiguous and keep validation simple. The XOR contract is also easier to document and to test.

**Why over two separate endpoints:** A second endpoint (`/batch/folder-by-id`) would duplicate the entire controller/service wiring for what is effectively a different input method with the same downstream logic. One endpoint, one service method, two input paths is the smaller and clearer design.

**Why reject "both provided" rather than silently preferring one:** If a caller sends both, they likely hold stale information in one of them. Silent preference masks that bug. Explicit rejection surfaces it at the boundary.

### 2. `getById` resolution: prefer writable, fall back to readable, else 404

When `$userFolder->getById($folderId)` returns multiple nodes, iterate the list and pick:

1. The first node where `($node->getPermissions() & Constants::PERMISSION_UPDATE) === PERMISSION_UPDATE` (writable).
2. If none are writable, the first node in the returned array (readable — `getById` already filters to nodes accessible by this user).
3. If the returned array is empty, throw the same "Folder not found" 404 the path flow produces.

**Why prefer writable:** Anonymized output files are written back into the same folder (`DocumentProcessingHandler::anonymizeDocument()` saves siblings with `_anonymized` suffix per `folder-batch-analysis` spec). If we pick a read-only mount when a writable one exists, the batch will look healthy until the anonymization step fails mid-run. Picking writable up front fails fast — during batch creation — where the error surface is cleanest.

**Why fall back to readable rather than 400:** Extraction/analysis can still succeed on read-only folders; only the anonymization write-back would fail. A read-only batch is a degraded but valid use case (user browses detected entities without producing anonymized copies). We let it proceed and let the anonymization step surface the write failure if the user reaches it.

**Alternative considered:** Require the caller to disambiguate. Rejected — callers typically don't know or care which mount they hit; the service is better positioned to pick.

### 3. Store both `folderId` and `folderPath` on the batch; return both in the response

On batch creation, populate both fields on the batch state regardless of input method:

```php
$batch['folderId']   = $node->getId();
$batch['folderPath'] = $userFolder->getRelativePath($node->getPath());
$batch['sourceType'] = 'folder';  // unchanged
```

Return `folderId` and `folderPath` alongside the existing `batchId, fileCount, files` in the response.

**Why store both:** The ID is canonical (survives renames); the path is the human-readable snapshot for logs, progress displays, and error messages. Dropping the path would degrade the UX; dropping the ID would give up the whole point of the change.

**Why return both regardless of input:** Response shape consistency. Path-based callers get a free upgrade path — capture the ID once, switch to ID-based calls later. Clients don't branch on input method to parse the response.

**Path resolution for ID input:** Use `$userFolder->getRelativePath($node->getPath())` — this strips the user's root prefix (e.g. `/alice/files/Shared/Cases` → `/Shared/Cases`) consistently with how the path-input flow treats paths. Keeps both input methods producing identical path strings on the batch.

### 4. Service signature: optional nullable params, not overloads

```php
public function createFolderBatch(
    ?int $folderId = null,
    ?string $folderPath = null
): array
```

Validation inside the service: exactly one non-null, else `Exception('Either folderId or folderPath must be provided', 400)` or `Exception('Provide only one of folderId or folderPath', 400)`. The controller performs the same check for faster rejection, but the service validates defensively — it's a public API.

**Why not two methods (`createFolderBatchById` / `createFolderBatchByPath`):** The resolution fork is one `if/else`; everything after it (enumerate → validate → create batch → queue job) is shared. Two methods would force the controller to branch, duplicating the input validation there.

### 5. Controller input handling: read params, XOR-validate, pass through

The controller reads both params from the request, validates XOR at the boundary, and delegates to the service. Integer coercion for `folderId` uses `(int)` on the raw param; empty string or missing param treats as null.

```php
$folderIdRaw   = $this->request->getParam('folderId');
$folderPathRaw = $this->request->getParam('folderPath', '');

$folderId   = ($folderIdRaw !== null && $folderIdRaw !== '') ? (int)$folderIdRaw : null;
$folderPath = $folderPathRaw !== '' ? $folderPathRaw : null;

if ($folderId === null && $folderPath === null) { return 400; }
if ($folderId !== null && $folderPath !== null) { return 400; }
```

**Why explicit coercion:** Nextcloud request params are strings. `(int)"abc" === 0` would silently accept garbage as folder ID 0. We reject empty/null but don't try to validate numericness beyond coercion — if a non-numeric ID gets through and coerces to 0, `getById(0)` will return an empty array and trigger the normal 404 path. Acceptable.

### 6. Logging updates

`FolderBatchService.php` currently logs `{ batchId, folderPath, fileCount }` on success. Extend to log `{ batchId, folderId, folderPath, fileCount, inputMethod: 'id' | 'path' }`. The `inputMethod` field makes it trivial to monitor adoption of the ID input over time.

## Risks / Trade-offs

- **Risk: `getById` returns the "wrong" mount when multiple exist.** For example, a folder shared with the user (writable via share) and also reachable via a group folder mount (read-only for this user). Our rule picks the writable share mount, which is the right choice for anonymization write-back — but the path stored on the batch will be the share path, not the group folder path. → **Mitigation:** acceptable — both paths resolve to the same content. Document in the spec scenarios that the canonical path reflects the mount chosen at resolution time.

- **Risk: Silent coercion of non-numeric `folderId` to 0.** A typo like `folderId: "abc"` becomes `(int)"abc" === 0`, then `getById(0)` returns `[]`, then we 404. → **Mitigation:** the 404 is an acceptable failure mode. Not worth adding stricter numeric validation for this edge case; the error message is the same as a legitimate not-found.

- **Risk: Callers send both fields by mistake during migration (e.g. UI picker fills both).** → **Mitigation:** we reject with 400 explicitly. Surfaces the bug at the boundary rather than picking one silently.

- **Risk: Existing path-based callers break due to response shape change.** → **Mitigation:** the response is a strict superset — old fields unchanged, new fields added. Clients that ignore unknown fields (the common case) are unaffected.

- **Trade-off: XOR validation happens twice (controller + service defensive check).** → Accepted — defensive validation in the service keeps the service usable from contexts other than this controller (e.g. future CLI commands or background jobs that might use it).

- **Trade-off: No frontend exercise of the new input in this change.** → Accepted and intentional. The frontend team takes this as a reference when they add the picker in a follow-up change. Backend tests cover both input paths end-to-end.
