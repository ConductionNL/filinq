## Context

The anonymisation pipeline today produces redacted files using OpenRegister's `DocumentProcessingHandler::anonymizeDocument`, which writes the result as `<original-base>_anonymized.<ext>` in the same parent folder as the source. For single-file flows that's tolerable. For batch / folder / dossier flows it's a mess: the source folder fills up with paired files (original + redacted), naming-based identification is the only signal, and re-runs cascade suffixes.

The fix is structural: redacted outputs of folder-driven anonymisation belong in their own subfolder under the source. The subfolder name is the signal — the filename inside doesn't need a suffix. This:

- Halves visible file count in the source folder (originals only).
- Makes "which files are redacted?" a one-glance question (look in `anonymised/`).
- Cleans up the re-anonymisation cascade — re-running just overwrites the existing subfolder contents.
- Provides a canonical home for the per-dossier grondslagen summary (`anonymisation-grondslagen-summary` Change B writes its PDF here).

The implementation choice is whether OR's anonymise endpoint should accept a `targetFolder` parameter, or DocuDesk should post-process by moving the file after OR returns. The latter avoids any OR-side change and keeps OR a clean primitive. Performance cost is negligible (one filesystem move + one rename per file). We do that.

Single-file anonymisation stays unchanged — adding a subfolder for a one-off file creates more confusion than it removes (operators don't expect a subfolder to materialise from a single drag-and-drop), and there's no folder-level cleanup story to motivate the change. Operators that want subfolder layout for an ad-hoc file wrap it in a folder and use the batch flow.

## Goals / Non-Goals

**Goals:**

- Folder / batch / dossier flows write redacted outputs to `<source-folder>/anonymised/<original-filename>`. The subfolder name is configurable (default `anonymised`).
- The destination filename inside the subfolder is the original base name, NOT suffixed with `_anonymized`. The subfolder provides the signal; redundant suffixing is dropped.
- Re-anonymisation of an already-suffixed file (`foo_anonymized.pdf`) produces a clean filename in the subfolder (`<source>/anonymised/foo.pdf`), stripping the legacy suffix.
- Implementation lives in DocuDesk; no OR-side change.
- The grondslagen summary (per Change B) lands at the canonical `<source-folder>/anonymised/grondslagen.pdf`.
- Backwards-compatible API: `anonymizedFilePath` in responses points to the new location automatically.

**Non-Goals:**

- Migrate past anonymisations into the new layout. Manual cleanup if desired.
- Per-dossier override of the subfolder name. Tenant-level config only in v1.
- Backup / preserve the previous run's `anonymised/` subfolder before overwriting. Operators rename / move themselves if they want history.
- Restructure single-file anonymisation. Stays as-is.
- Add a `targetFolder` parameter to OR's anonymise endpoint. Post-process in DocuDesk.

## Decisions

### D1. Subfolder under source, not sibling folder

Layout β (subfolder) chosen over layout δ (sibling folder named `<source>_anonymised/`):

- **Subfolder under source** — the dossier folder owns its redacted outputs as a subfolder, intuitive for "this dossier has these documents and these redactions".
- **Sibling folder** — separates source from output more cleanly, but creates two top-level folders that aren't obviously related. Worse for navigation.

The subfolder approach also matches the implicit mental model in the dossier-register design (the dossier IS the folder; redactions live inside it).

### D2. Filename inside the subfolder is the original base name

Example: source file `<dossier>/foo.pdf` → redacted at `<dossier>/anonymised/foo.pdf`.

The `_anonymized` suffix on the filename is dropped because the subfolder name carries that information. Two reasons to drop it:

- Cleaner — no double-marking ("anonymised" said twice in the path).
- Avoids the cascading-suffix problem when re-anonymising files that already came out of an old-layout run.

When the input file already has the `_anonymized` suffix (legacy file from a pre-change run), the suffix is stripped from the destination filename. `foo_anonymized.pdf` → `<source>/anonymised/foo.pdf`. The post-process logic uses a regex `s/_anonymized$//` on the base name when computing the destination.

### D3. DocuDesk post-process (move + rename) — no OR change

OR's `DocumentProcessingHandler::anonymizeDocument` continues to write `<source>/<base>_anonymized.<ext>`. DocuDesk's batch service catches the resulting file node and moves it:

```
   1. OR returns File node at <source>/<base>_anonymized.<ext>
   2. DocuDesk computes destination: <source>/<subfolder>/<cleanBase>.<ext>
        - subfolder name from config (default 'anonymised')
        - cleanBase = strip trailing '_anonymized' from base if present
   3. DocuDesk creates <source>/<subfolder>/ if it doesn't exist
   4. DocuDesk moves the file to the destination via Nextcloud's IRootFolder API
   5. DocuDesk reports the new path in the response (anonymizedFilePath field)
```

**Rationale:** OR stays a clean primitive. DocuDesk owns the layout convention. If a future caller of OR doesn't want subfolder layout, they don't get it (DocuDesk's wrapper is the layout-applier).

**Alternative considered:** Add a `targetFolder` parameter to OR's anonymise endpoint. Cleaner in a sense (the file lands at the right place in one step), but requires an OR-side change for a feature that's specific to DocuDesk's UX preference. Rejected.

**Trade-off:** One extra filesystem operation per file (the move). Negligible — file moves on the same filesystem are metadata-only on most NC backends.

### D4. Conflict policy: subfolder exists → overwrite by filename

When `<source>/<subfolder>/` already exists from a previous run:

- The new run reuses the subfolder.
- Files in it are overwritten by destination filename (a fresh `foo.pdf` replaces the previous `foo.pdf`).
- Files in the subfolder that don't correspond to any source file in this run are LEFT UNTOUCHED. Operators that want to clean up stale outputs do so manually before re-running.

**Rationale:** the most common case for re-running anonymise is "I added a base / changed a setting and want the same set of files re-redacted". Overwrite-by-filename is the right behaviour. Wholesale-clear of the subfolder before each run could surprise operators who manually edited a redaction in the subfolder.

**Trade-off:** stale outputs accumulate if the source set shrinks between runs. Operators must clean up. Minor; documented in CHANGELOG.

### D5. Subfolder name is tenant-configurable, default `anonymised`

The default `anonymised` matches the dossier-register Dutch convention. Tenants can override:

| Config key | Default | Validation |
|---|---|---|
| `docudesk.anonymisation.output_subfolder_name` | `anonymised` | Single path segment; lowercase letters, digits, hyphen, underscore only; no spaces, no dots, no slashes; non-empty |

Validation lives in the admin settings save handler. Invalid values are rejected with a clear error. Once changed, future runs use the new name; existing `anonymised/` subfolders remain on disk (operators rename / migrate themselves if wanted).

**Rationale:** Tenant choice for cosmetic / cultural reasons (e.g. a Belgian instance might prefer `geanonimiseerd`; an English-speaking one `redacted`). The constraint set keeps it filesystem-safe.

### D6. The grondslagen summary lives in the same subfolder

The per-dossier summary PDF (per `anonymisation-grondslagen-summary`) lands at `<source>/<subfolder>/grondslagen.pdf`. This change defines the canonical location; Change B does the rendering.

**Why one filename, not per-run timestamp?** The summary is the latest snapshot; old summaries don't accumulate. Audit history lives in the OR audit log, not as files on disk.

### D7. Single-file flow unchanged

Single-file anonymisation (`POST /api/anonymization/anonymize/{fileId}` with one file) keeps writing `<file>_anonymized.<ext>` in the same folder as the source. Reasons:

- A subfolder for a single file is operational overhead (NC creates an unwanted folder, operator has to navigate into it).
- There's no folder-level cleanup story for a one-off — the operator is dealing with one input and one output.
- Operators that want subfolder layout can wrap the file in a folder and use the batch flow.

The single-file flow's `_anonymized` suffix on the filename remains the only naming signal.

## Risks / Trade-offs

- **[Frontend file-listing logic that derives the redacted path from the source path]** → Mitigation: the API response continues to include `anonymizedFilePath`, so clients that read that field follow automatically. Clients that compute paths client-side need an update — coordinate with the frontend team.
- **[Operators expecting redactions in the source folder]** → Mitigation: CHANGELOG entry, frontend UX cue (e.g. "Anonymised outputs are saved in the `anonymised/` subfolder"), demo/screenshot in the changelog.
- **[Stale outputs in the subfolder after source set shrinks]** → Mitigation per D4: documented; operators clean up manually.
- **[Tenant changes the subfolder name; existing `anonymised/` folders are left orphaned]** → Mitigation: change is rare; admin UI warns when changing the name post-deploy that existing folders are not renamed.
- **[Filename collision when two source files share the same name across subfolders]** → Edge case (the original layout has the same risk). Mitigation: the source layout dictates uniqueness; if two sources share `foo.pdf` (in subfolders of the same dossier), they collapse to one destination. Operators expect this — out of scope to handle.
- **[`output_subfolder_name` validation]** → Mitigation: strict single-segment, lowercase, hyphen/underscore-only. Rejects path traversal and special characters.

## Migration Plan

1. Land the post-process logic in `BatchAnonymizeService`, `FolderBatchService`, and `FolderExtractionJob`.
2. Add the admin setting + validation.
3. Update relevant tests (unit + integration) for new path expectations.
4. Coordinate with the frontend team — the file-listing UI needs to read from the new subfolder.
5. Release. Operators see new outputs in the subfolder; legacy outputs stay in source folders untouched.

**Rollback:** Disable the post-process — anonymise reverts to writing `<base>_anonymized.<ext>` in the source folder. New outputs land mixed with sources again. The subfolder logic is a thin DocuDesk-side post-process; rolling it back is a few lines of code.

## Seed Data

Not applicable — this change introduces no new schemas, registers, or seed objects. Behaviour change to file layout only.

## Open Questions

- **Should the post-process be transactional?** If the move-and-rename fails partway through (filesystem error), do we leave the file in source folder with `_anonymized` suffix, or attempt a rollback / retry? Provisional: leave at source with suffix, log the move failure, return success with the `_anonymized` path in the response. Operator can move manually. Resolve at apply time.
- **Frontend coordination** — when does the frontend team need the new layout in their development environment? Coordinate timing of merge / release.
- **What happens to outputs that fail conversion in Change A** when the layout is in effect? They never make it to the post-process. The 422-on-conversion-failure path (Change A) is unaffected by this change.
