## Context

OpenRegister's `DocumentProcessingHandler::anonymizeDocument` writes redacted files as `<original>_anonymized.<ext>` in the source folder. For batch flows, this is operationally noisy. The fix is a DocuDesk-side post-process — move + rename the file after OR returns — into a `<source>/<configured-subfolder>/` location with a clean filename.

This change covers the **batch anonymise** surface. The folder-analysis surface uses the same helper + config + conventions and is specced in `anonymisation-folder-output-folder-layout`.

## Goals / Non-Goals

**Goals:**

- Batch outputs land in `<source>/anonymised/<original-filename>` by default.
- Tenant-configurable subfolder name.
- Strip legacy `_anonymized` suffix from the destination filename to avoid cascading suffixes.
- Exclude `_anonymized`-suffixed source files from automated batch source-discovery (legacy outputs should not be silently re-anonymised).
- Single-file flow unchanged (regression-guarded).

**Non-Goals:**

- Migrate past anonymisations.
- Auto-backup of a previous run's subfolder.
- Per-dossier subfolder override.
- OR-side changes — the post-process is entirely DocuDesk-side.

## Decisions

### D1. Subfolder-under-source layout (option β)

Rejected during exploration: a sibling layout `<source-folder>_anonymised/` (option δ) — pollutes the parent folder hierarchy, awkward when the source is the root of a dossier. The chosen layout keeps the redacted output co-located with its source.

### D2. Helper centralises path computation

`OutputLayoutResolver::resolveBatchDestination(Folder $sourceFolder, string $sourceBaseName, string $extension): string` — one entry point, used by every batch/folder/job call site. Strips trailing `_anonymized` (regex `s/_anonymized$//`). Validates the configured subfolder at read time (regex `/^[a-z0-9_-]+$/`); invalid value falls back to the default with a warning.

### D3. Move failure preserves the file

If the post-OR move/rename fails (permissions, name collision, anything else), the file is preserved at OR's legacy output path; the per-file result carries a `warning`; the failure is logged with source path + target path + error message. The anonymised file is NOT discarded.

### D4. Reuse the subfolder; overwrite by filename

A second run of the same batch reuses the existing `<source>/anonymised/` and overwrites by filename. No automatic backup of the previous run. Operators that want history rename or move the subfolder before re-running.

### D5. Source-discovery excludes `_anonymized` files

Legacy outputs from the pre-change layout end with `_anonymized`. Source-discovery filters them out. Documented edge case: a source file intended for anonymisation that happens to end in `_anonymized` (very rare) — rename the source first, or use the per-file anonymise endpoint which doesn't apply the filter.

## Risks / Trade-offs

- **Frontend file-listing components that derive paths manually** → coordinate with the frontend team. Reading `anonymizedFilePath` from API responses is the supported pattern; manual derivation needs removal.
- **Tenant changes the subfolder name post-deploy** → existing `<old-name>/` folders are not renamed automatically; surface a non-blocking warning at config-change time.
- **Permissions-blocked subfolder** → preserved-at-legacy-path fallback with warning; documented as the only failure mode.

## Migration Plan

1. Ship the helper + config key + admin UI.
2. Wire the batch service to post-process via the helper.
3. Document in CHANGELOG; coordinate with frontend.

**Rollback:** Disable the post-process step (early-return); outputs land at the legacy path as today.

## Seed Data

Not applicable.

## Open Questions

- Should the helper expose an idempotent "ensure subfolder exists" entry point, or does each call site create the subfolder lazily? Provisional: helper exposes; call sites delegate.
