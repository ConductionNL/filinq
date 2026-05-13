## Why

Today, batch / folder / dossier anonymisation writes every redacted file as `<original-name>_anonymized.<ext>` in the **same parent folder** as the original (per OpenRegister's `DocumentProcessingHandler::anonymizeDocument`). For a dossier with 30 files, this leaves the operator with 60 files mixed in one folder — originals interleaved with redacted versions, file count doubled, and naming-based identification (`_anonymized` suffix) the only way to tell which is which.

Three concrete problems:

1. **Operational clutter.** A dossier folder that started as a clean inbox of source documents becomes a mixed bag of source + redacted versions. Listing, sorting, and reasoning about what's in the folder gets harder.
2. **Easy misuse.** "Send the anonymised file to legal" is a common task; with both files in the same folder under similar names, sending the wrong file (the original instead of the redacted version) is a real mistake — and a privacy incident.
3. **Re-anonymisation cascades.** Re-anonymising an already-suffixed file produces `foo_anonymized_anonymized.pdf`. Compounds across runs.

This change introduces a clean, predictable layout for folder/dossier flows: outputs land in a `<dossier-folder>/anonymised/` subfolder, named after the original (no `_anonymized` suffix in the filename — the subfolder name carries that information). Single-file anonymisation keeps its current behaviour (`<file>_anonymized.pdf` in the same folder) — there's no folder-level cleanup story for a one-off file, and adding a subfolder for a single file is operational overhead.

This change also enables the destination convention used by `anonymisation-grondslagen-summary`: the per-dossier summary lives at `<dossier-folder>/anonymised/grondslagen.pdf` alongside the redacted files.

## What Changes

- **MODIFIED:** Folder / batch / dossier anonymisation writes redacted outputs to `<source-folder>/anonymised/<original-filename>` instead of `<source-folder>/<original-base>_anonymized.<ext>`. The subfolder name defaults to `anonymised` (lowercase, Dutch spelling — matches dossier-register convention) and is tenant-configurable via `docudesk.anonymisation.output_subfolder_name` — see the **NEW config** bullet below.
- **MODIFIED:** Within the `anonymised/` subfolder, files keep their original filenames (the `_anonymized` suffix on the filename is dropped — the subfolder name is the signal). Example: `<dossier>/foo.pdf` (original) becomes `<dossier>/anonymised/foo.pdf` (redacted).
- **UNCHANGED:** Single-file anonymisation. A one-off anonymise of a single file (not part of a folder/batch/dossier flow) continues to write `<file>_anonymized.pdf` in the same folder. Operators that want subfolder layout for single files can wrap them in a folder and use the batch flow.
- **MODIFIED:** When `<source-folder>/anonymised/` already exists from a previous run, the batch reuses it. Files in the subfolder are overwritten by filename. **No automatic backup of the previous run** — operators that want history rename or move the subfolder before re-running.
- **MODIFIED:** When the input contains a file that itself has the `_anonymized` suffix (e.g. re-anonymising a file produced by the old layout), the suffix is **stripped** from the destination filename in the new subfolder. `foo_anonymized.pdf` → `<source>/anonymised/foo.pdf`. Avoids the cascading-suffix anti-pattern.
- **MODIFIED:** The grondslagen-summary destination (per `anonymisation-grondslagen-summary`) lands at `<source-folder>/anonymised/grondslagen.pdf`. This change provides the canonical location; that change provides the rendering.
- **NEW config:** `docudesk.anonymisation.output_subfolder_name` — string, default `anonymised`. Tenants that want a different name (e.g. `redacted`, `geanonimiseerd`, `output`) can override. Lowercase, no spaces, valid file-system folder name.
- **DocuDesk-side post-process implementation.** OpenRegister's `anonymizeDocument` continues to write to the source folder with `_anonymized` suffix; DocuDesk's controller / service moves the file into the `anonymised/` subfolder and renames it to drop the suffix after OR returns. No OR change required.
- **NO data migration.** Past anonymisations are not relocated.

### Out of scope

- **Migrating past anonymisations** to the new layout. Operators with cluttered legacy folders can clean up manually.
- **Auto-backup of a previous run's `anonymised/` subfolder.** Overwrite by filename is the v1 behaviour. If a "preserve previous run" feature is wanted later, it's a follow-up.
- **Per-dossier override of the subfolder name.** Tenant-level only in v1.
- **A separate "output folder" sibling (option δ from exploration)** at `<source-folder>_anonymised/`. Rejected during exploration in favour of the subfolder-under-source layout (option β).
- **Single-file anonymisation layout change.** Stays as-is. A future change can revisit if a clear use case emerges.
- **Modifying OR's anonymise endpoint** to accept a `targetFolder` parameter. DocuDesk post-processes after OR returns; no OR change required.

## Capabilities

### New Capabilities

(none — this is a behaviour change to existing capabilities, not a new surface.)

### Modified Capabilities

- `batch-anonymization`: batch-anonymise outputs land in `<source-folder>/anonymised/` instead of mixed with originals. The grondslagen summary (per `anonymisation-grondslagen-summary`) lands in the same subfolder.
- `folder-analysis-anonymization`: same — folder-level orchestration uses the new layout.

## Impact

- **Code (docudesk):**
  - `lib/Service/AnonymizationService.php` — single-file flow unchanged.
  - `lib/Service/BatchAnonymizeService.php` — after each file's anonymisation completes (post Change A's PDF conversion if applicable), move the resulting file from `<source-folder>/<base>_anonymized.<ext>` to `<source-folder>/<configured-subfolder>/<base>.<ext>`, creating the subfolder if needed. Strip the `_anonymized` suffix from the filename.
  - `lib/Service/FolderBatchService.php` — same post-process logic for the folder-driven flow.
  - `lib/BackgroundJob/FolderExtractionJob.php` — apply the layout when the background job writes outputs.
  - New helper `lib/Service/Conversion/OutputLayoutResolver.php` (or extend an existing helper) computing the destination path: `(sourceFolder, originalBaseName) → outputPath`.
  - `lib/Settings/admin/SettingsController.php` (or equivalent) — expose `output_subfolder_name` setting in admin UI; validate as a single non-empty path-safe segment.
- **API contract:** No request payload changes. Response shape unchanged in field set; the `anonymizedFilePath` field now points into the `anonymised/` subfolder. Pre-change clients reading `anonymizedFilePath` continue to work — they just see a different path.
- **Cross-app:**
  - Soft dep on `anonymise-output-as-pdf-by-default` (Change A): if A is in effect (default PDF), the move-and-rename happens against PDF files; otherwise against native-format files. The layout logic is format-agnostic.
  - Hard dep with `anonymisation-grondslagen-summary` (Change B): that change writes the per-dossier summary at the location this change defines. Without this change, B falls back to `<source-folder>/grondslagen.pdf`.
  - No OR-side changes.
- **UX impact:** Frontend file-listing components that previously expected `_anonymized`-suffixed filenames in the source folder need to look in the `anonymised/` subfolder instead. Frontend team needs a heads-up. Existing API responses include `anonymizedFilePath` so clients reading that field don't need logic changes — only clients that derive the path manually do.
- **Privacy / compliance:** Reduces "send the wrong file" risk. The subfolder is a clear visual + structural signal that the contents are redacted-for-distribution.
- **Tests:** Unit tests for path resolution; integration tests for batch anonymise verifying the subfolder layout; UI smoke test with the frontend team.
- **Migration:** None automated. Document the layout change in CHANGELOG; operators with legacy `_anonymized`-suffixed files in source folders can leave them in place or clean up manually.
