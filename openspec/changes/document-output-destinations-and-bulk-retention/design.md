## Context

Two independently-reported gaps turned out to share one root cause and one
fix:

1. `POST /apps/docudesk/api/documents/generate` only ever returns bytes —
   nothing lands in Nextcloud Files.
2. `BatchDocumentJob` (the >10-object async path of `generate/bulk`)
   generates every document and discards the content; only per-object
   success/failure is recorded.

Both need the same primitive: write a generated binary into a specific
user's Files, safely, given nothing but a `userId` string (no live session
in the background-job case).

## Goals / Non-Goals

**Goals**

- `options.output.mode` on single generate: `return` (default, unchanged),
  `files`, `both`.
- Async bulk (`generate/bulk` >10 objects) actually persists its output.
- Zero behavioural change to any caller that doesn't pass `options.output`.
- One small, reusable storage primitive shared by both features.

**Non-Goals**

- Correspondence (`CorrespondenceService`/`CorrespondenceController`) — see
  D7.
- Wiring `listRefs` through bulk generation — unrelated to this change's
  goal (output persistence), tracked as a "maybe later" idea only.
- Sharing/permissions on the stored file beyond Nextcloud's normal
  per-user Files ACLs — the file lands in the *requesting/initiating
  user's* own Files; there is no cross-user sharing story here.
- A UI for browsing generated documents — out of scope; the file is a
  normal Files entry, browsable via the existing Files app.

## Decisions

### D1. `mode: 'return'` (or `options.output` omitted) is byte-identical to today

`DocumentController::buildDocumentResponse()` computes `$mode =
$result['output']['mode'] ?? 'return'`. When `$mode === 'return'`, the
method takes exactly the code path it takes today: same
`DataDownloadResponse`/`JSONResponse` construction, no added headers, no
added keys. `DocumentService::generateDocument()` skips its entire storage
block (`if ($mode !== 'return')`) — zero extra service calls, zero extra
warnings. This is the regression guard for every existing caller,
including the spectr "Generate report" button, which never sets
`options.output` and must see identical bytes after this change.

The one deliberate, additive side effect: `logGeneratedDocument()`'s
persisted `generatedDocument` audit row now always carries `fileId: null`
and `filePath: null` keys when nothing was stored (previously these keys
didn't exist on the row at all). This is invisible to a binary download
(the audit row isn't part of the response body) and only visible in the
`html`-format JSON response's `metadata` object, which gains two `null`
keys. Treated as acceptable: additive keys on a JSON object are not a
breaking change for any reasonable consumer, and the alternative
(conditionally omitting the keys) would make the audit trail's shape
depend on whether storage was attempted, which is worse for anyone
querying the `generatedDocument` register directly.

### D2. `Folder::getNonExistingName()` for dedupe, not a bespoke counter

`FileUploadService::resolveUniqueFileName()` already exists in this
codebase and reimplements a `_1`, `_2`... suffix scheme. The task
requirement is explicit: mirror Nextcloud's own convention
(`name (2).ext`), and prefer the platform helper if one exists. One does:
`OCP\Files\Folder::getNonExistingName($name)` — the exact method
`\OC_Helper::buildNotExistingFileNameForView()` implements and that
Nextcloud's own upload/rename flows use. `DocumentStorageService` uses it
directly rather than duplicating `FileUploadService`'s bespoke scheme
(kept as-is; out of scope to refactor its unrelated call sites).

### D3. New `DocumentStorageService`, not folded into `DocumentService`

Single responsibility: validate a `targetPath`, resolve/create the
destination folder (segment-by-segment, idempotent — `Folder::newFolder()`
is not documented as recursive, so each path segment is checked with
`nodeExists()`/`get()` and created only if missing), dedupe the filename,
write the file, return `{fileId, path, name, size}`. No OpenRegister
dependency, no Twig/PDF dependency — only `IRootFolder`. This mirrors the
existing `FileUploadService` shape (same app, same pattern) but takes an
explicit `userId` parameter instead of pulling it from `IUserSession`,
because `BatchDocumentJob` has no session to pull from — it received a
captured `userId` string at dispatch time. `IRootFolder::getUserFolder($id)`
works from a bare user id with no active session (confirmed against core:
`getUserFolder()` only requires the id to resolve an `IUser` internally),
which is exactly what makes background-job storage possible at all.

### D4. Fail-open for `both`, hard-fail for `files` and for validation errors

Two axes, not one:

- **`targetPath` validation** (malformed path: absolute, `..`, bad
  charset) is a **caller input error**. It always throws before any
  storage is attempted, in every mode (`files` and `both` alike) — coded
  `400`.
- **Storage execution failure** (quota exceeded, permission denied,
  anything thrown by `Folder::newFile()`/`newFolder()` after validation
  passed) is a **system condition** — coded `507`. For `mode: 'files'`
  this is the whole point of the request, so it is a hard failure,
  propagated to the controller as a `507`-status JSON error. For `mode:
  'both'`, the request also asked for the binary — that part can still
  succeed. `DocumentService::generateDocument()` catches only
  `code === 507` exceptions from the storage call in `both` mode,
  downgrades them to a `warnings[]` entry ("Document generated but could
  not be stored in Files: ..."), and returns the binary normally. A `400`
  from the same call is re-thrown even in `both` mode — a malformed
  `targetPath` is not something retrying the binary-only path should paper
  over silently.

This is deliberately asymmetric from the "storage failures → 507/400-class
error" framing in the task brief: 400s (bad input) never fail open; only
507s (transient/environmental storage conditions) do, and only for `both`.

### D5. Async bulk: `files` only, rejected up front

`DocumentService::generateBulk()` checks `count($objectIds) >
SYNC_BATCH_LIMIT` before it decides sync vs. async, exactly as today. The
new check sits right there: if async and `options.output.mode !== 'files'`
(covers omitted, `'return'`, and `'both'`), throw `Exception(..., 400)`
with a message naming the exact fix (`Set options.output.mode="files"`).
This reuses the controller's existing `Exception`-code-to-HTTP-status
mapping — no controller change needed for this rule itself.

`'both'` is rejected, not silently downgraded to `'files'`, because
silently dropping half of what the caller asked for is worse than telling
them synchronously that it's not possible: once the job is queued, the
original HTTP request has already returned its `202 Accepted` + `jobId` —
there is no response left to attach a binary to.

### D6. Per-job folder: computed once, not per-object

`BatchDocumentJob::run()` needs a base `targetPath` before its per-object
loop starts. Rather than give the job a new `TemplateService` dependency
(more DI surface, another mock in every existing job test), a new public
method on the service it already depends on:
`DocumentService::buildOutputTargetPath(string $templateId, ?string
$explicitTargetPath, ?array $template = null)`. Single-generate
(`generateDocument()`) already has the template loaded and passes it in
directly (no extra lookup). The job has no template loaded, so it omits
`$template` and the method fetches it once, before the per-object loop —
one lookup for the whole job, not one per object (the whole batch shares
one `templateId`, so the namespace is invariant across the batch). The
job then appends `/<jobId>` to whatever `buildOutputTargetPath()` returns
and forwards that fixed path to every `generateDocument()` call for the
batch via `options['output']['targetPath']`.

### D7. Correspondence: descoped, not "cheap enough"

`CorrespondenceService` (`lib/Service/CorrespondenceService.php`) does not
depend on, call, or share code with `DocumentService` — it independently
re-implements template loading, huisstijl, rendering, and register logging
(verified at HEAD: no `DocumentService` reference anywhere in the class).
Wiring `options.output` there is not "extending shared plumbing", it is
building this entire change a second time against a parallel
implementation. Explicitly out of scope. If correspondence output
persistence becomes a real requirement, it should be its own change (and
is a reasonable trigger to ask whether the two services should finally be
unified — a bigger, separate architectural conversation this change does
not attempt to resolve).

## Risks / Trade-offs

- **Storage cost is invisible to the caller until they hit a quota.** No
  new mitigation added beyond surfacing the failure per D4 — pre-flight
  quota checking is out of scope.
- **A 200-document async job now creates 200 real Files entries** under
  `<targetPath>/<jobId>/`. No auto-cleanup is added; the caller (or the
  Files app's normal trash/retention) is the only lifecycle mechanism.
  Documented, not solved, here.
- **`targetPath` charset restriction** may reject legitimate folder names
  with characters outside `[A-Za-z0-9 _.-]` per path segment. Chosen
  deliberately conservative (reject-by-default) over trying to enumerate
  every unsafe character; can be loosened later if a real folder-naming
  need surfaces.

## Migration Plan

Purely additive — no data migration. `docudesk_register.json`'s
`generatedDocument` schema gains two nullable properties; existing rows
are valid as-is (`hardValidation: false`). No route changes. Rollback is
"stop passing `options.output`" — every existing caller already does that.

## Open Questions

None outstanding; `listRefs`-in-bulk and correspondence are logged above as
explicit non-goals rather than open questions.
