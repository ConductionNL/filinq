# Design: document-versions-detail-tab

## Context

Verified against HEAD (`origin/development`):

- The document/report record is **OR File-Attachment metadata over a Nextcloud file**
  (`document-register` spec: "the original `report` schema is replaced by OR File Attachment
  metadata"). The file therefore already has Nextcloud version history.
- `DocumentComparisonService::readVersionContent()` already resolves
  `OCA\Files_Versions\Versions\IVersionManager` lazily, calls `getVersionsForFile($user, $file)`
  and `read($version)`, and degrades to a `422 versions-unavailable` when `files_versions` is
  disabled. `ComparisonController::compare()` accepts `{left:{fileId,versionTimestamp?},
  right:{fileId,versionTimestamp?}}`.
- No frontend consumes versions: `getVersionsForFile` is absent from `src/`, there is no `Versies`
  tab, and `ConfirmRestoreVersionDialog.vue` is bound only to template versions in
  `TemplateDetail.vue`.

So the backend already knows how to talk to `files_versions`; the gap is (a) a list endpoint and
(b) the `Versies` detail tab UI, plus a restore path.

## Goals / Non-goals

- **Goal:** surface, open, restore, and compare a document's Nextcloud file versions from the
  `Document detail` `Versies` tab, consuming `files_versions`.
- **Non-goal:** any new versioning/storage engine. Nextcloud owns versions; DocuDesk reads them.
- **Non-goal:** template versioning (already exists) or version retention (OpenRegister's domain).

## Decisions

### Decision 1 — new capability, not a `document-register` modification

`document-register` is already being modified by the active `document-detail-leaf-widgets` change.
Each `Document detail` tab in ADR-001 is its own capability (`ocr-document-scanning`,
`document-comparison`, `metadata-enrichment`, …). Versions get the same treatment: a new
`document-versions` capability that maps to the `Versies` tab. This avoids two concurrent deltas on
one spec file and matches the established one-tab-one-capability pattern.

### Decision 2 — reuse the existing `IVersionManager` integration and comparison flow

The list endpoint delegates to the same `IVersionManager::getVersionsForFile` the comparison
service already uses (extracted to a small shared reader if convenient), returning
`{timestamp, author, size, label}` per version. "Compare" hands `fileId` + `versionTimestamp` to
the existing `ComparisonController` — no new diff engine (ADR-011: reuse before building). Restore
uses `IVersionManager::rollback`.

### Decision 3 — graceful degradation identical to comparison

When `files_versions` is disabled the tab renders an informative notice (reusing the
`versions-unavailable` message already localised in `ComparisonController::mapReasonToMessage`),
not an error. The tab is still shown (so the IA is stable per ADR-001), just empty-with-notice.

### Decision 4 — UI via `@conduction/nextcloud-vue`, restore-confirm reuses existing copy

The version list uses `CnDataTable` (ADR-012) with per-row actions (open, download, restore,
compare). Restore reuses the `ConfirmRestoreVersionDialog` copy ("Restore to version {n}? The
current state will be saved as a new version first.") so template and document restore read
consistently; the dialog is generalised or a sibling `dialogs/` component follows the same
NcDialog-in-its-own-file rule (modal-isolation gate).

### Decision 5 — authorization

The list/restore endpoints are guarded per-object exactly like DocuDesk's other document endpoints
(the user must be able to read/write the underlying Nextcloud file). Restore requires write access;
listing/compare requires read. No `#[NoAdminRequired]` endpoint operates on an arbitrary file id
without the file-permission check (no-admin-IDOR gate).

## Risks

- **`files_versions` optional.** Handled by the graceful-degradation notice (Decision 3).
- **Large version histories.** The list endpoint paginates (limit/offset) like other DocuDesk
  list endpoints; the table lazy-renders.
- **Binary/office documents.** Open/download/restore work for any type; "compare" is only offered
  for text-extractable types (the comparison service already gates `isTextExtractable`), so the
  compare action is hidden/disabled for non-extractable versions rather than erroring.
