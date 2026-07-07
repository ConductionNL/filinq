# Proposal: document-versions-detail-tab

## Why

Document version history is one of the highest-signal capabilities in the DMS/records category:
government tenders repeatedly demand version control, and the Conduction competitive intelligence
DB flags `dms.version-control` as a **core** capability whose spec layer is **docudesk** — yet it
is unbuilt. **ADR-001** (information architecture) already reserves a **`Versies`** tab in the
`Document detail` tab family (`Inhoud · Metadata · OCR-tekst · Entiteiten · Anonimisatie ·
Redactie · Handtekeningen · Versies · Audit`), but that tab does not exist.

Verified against HEAD:
- The document/report record is OR File-Attachment metadata over a **Nextcloud file**, so the file
  already has native Nextcloud version history via `files_versions`.
- `DocumentComparisonService` already reads those versions (`IVersionManager::getVersionsForFile`,
  `read`) to diff two versions, and `ComparisonController` exposes the compare endpoint. So the
  version *plumbing* is already consumed on the backend.
- But **no UI enumerates a document's versions**: `getVersionsForFile` appears nowhere in `src/`,
  there is no `Versies` tab, and the only version UI (`ConfirmRestoreVersionDialog.vue`) is wired
  solely to **template** version restore (`TemplateDetail.vue` / `TemplateVersionService`), not to
  document file versions. A case-worker cannot see, open, restore, or compare a document's prior
  versions from DocuDesk — they must know a `versionTimestamp` out-of-band to use compare.

This change delivers the declared `Versies` tab as a **thin consumer of Nextcloud's own
`files_versions`** — no new storage, no new versioning engine — reusing the existing comparison
service for the "compare with previous" action. This respects ADR-022 (consume the platform,
don't reimplement) and ADR-001 (surface as a detail tab, not a new top-level menu).

## What Changes

- **NEW capability `document-versions`:** the `Document detail` surface gains a **`Versies`** tab
  that lists the Nextcloud file versions of the document (newest first) with timestamp, author,
  and size, read via `IVersionManager::getVersionsForFile`. Degrades gracefully to an informative
  notice when `files_versions` is disabled on the instance (mirroring the comparison flow's
  `versions-unavailable` handling).
- **View / download a version:** each listed version can be opened/downloaded.
- **Restore a version:** a user with edit rights can restore a prior version via
  `IVersionManager::rollback`; the current state is preserved as a new version first (reusing the
  existing `ConfirmRestoreVersionDialog` copy and confirm pattern).
- **Compare from the list:** a version row offers "compare with current" / "compare with previous",
  handing the `fileId` + `versionTimestamp` pair to the **existing** `DocumentComparisonService` /
  `ComparisonController` — no new diff engine.
- **Backend:** a thin read endpoint listing versions for a document (delegating to
  `IVersionManager`), guarded per-object like DocuDesk's other document endpoints. No new schema,
  table, or storage.

### Out of scope

- Any new versioning/storage mechanism — Nextcloud `files_versions` is the single source of truth.
- Template versioning (`TemplateVersionService`) — unchanged; this is document *file* versions.
- The contacts/activity/shares leaf tabs (`document-detail-leaf-widgets`, already active) — a
  different set of tabs on the same detail surface.
- Retention/disposition of versions (Archiefwet retention is an OpenRegister capability, not a
  docudesk one).
- Cross-version signing/anonymisation semantics beyond surfacing + compare.

## Capabilities

### Added Capabilities

- `document-versions` — the `Versies` detail tab: list / view / restore / compare Nextcloud file
  versions of a document, consuming `files_versions`.

## Affected Projects

- [x] Project: `docudesk` — all implementation work is in this repo.
- Reference: `docudesk/openspec/architecture/adr-001-information-architecture.md` (the `Versies` tab).
- Reference: `docudesk/openspec/specs/document-comparison/spec.md` + `DocumentComparisonService`
  (reused for the compare action).
- Reference: `hydra/openspec/architecture/adr-022-apps-consume-or-abstractions.md` (consume the
  platform's versioning, don't reimplement).

## Success Criteria

- `openspec validate document-versions-detail-tab --strict` exits 0.
- The `Document detail` page shows a `Versies` tab listing the document's Nextcloud file versions
  (newest first) with timestamp/author/size, and an informative notice when `files_versions` is
  disabled.
- A version can be opened/downloaded; a version can be restored (with the current state saved as a
  new version first); a version can be handed to the existing compare flow.
- No new storage, schema, or versioning engine is introduced — only `files_versions` is consumed.
- Strings ship EN source + NL translations; the tab uses `@conduction/nextcloud-vue` components
  (ADR-012) and NL Design System tokens (ADR-003).
