## Why

Batch anonymisation today writes every redacted file as `<original-name>_anonymized.<ext>` in the same parent folder as the original. For a 30-file batch, the operator ends up with 60 files mixed in one folder — originals interleaved with redacted versions, naming-based identification (`_anonymized` suffix) as the only signal. This causes (a) operational clutter, (b) easy misuse — "send the anonymised file to legal" is a common task and sending the wrong file is a real privacy incident, and (c) re-anonymisation cascades producing `foo_anonymized_anonymized.pdf`.

This change introduces the new layout for the **batch anonymise** surface: outputs land in `<source-folder>/anonymised/<original-filename>`, with the subfolder name carrying the "redacted" signal and the filename staying clean. Single-file anonymisation is unchanged. The folder-analysis surface is covered by the sibling change `anonymisation-folder-output-folder-layout` (same `OutputLayoutResolver`, same config key, same conventions — split so each capability delta is reviewable independently).

## What Changes

- **MODIFIED:** `batch-anonymization` capability — batch outputs land in `<source-folder>/<configured-subfolder>/<original-filename>` instead of `<source-folder>/<base>_anonymized.<ext>`. The `_anonymized` suffix on the filename is dropped; the subfolder is the signal.
- **MODIFIED:** When the input contains a file ending in `_anonymized` (legacy output being re-anonymised), strip the suffix from the destination filename. `foo_anonymized.pdf` → `<source>/anonymised/foo.pdf`. Avoids cascading suffixes.
- **MODIFIED:** Source-discovery filter excludes files whose base name ends with `_anonymized` (legacy outputs that should not be re-anonymised by an automated batch).
- **NEW config:** `filinq.anonymisation.output_subfolder_name` — string, default `anonymised`. Tenant-configurable. Validation: `/^[a-z0-9_-]+$/`, non-empty single path segment.
- **NEW helper** `lib/Service/Conversion/OutputLayoutResolver.php` — computes destination: `(sourceFolder, originalBaseName, extension) → outputPath`; reads the config; validates and falls back with a warning if invalid.
- **Filinq-side post-process** — OR's `anonymizeDocument` continues to write to the source folder with `_anonymized` suffix; Filinq's batch service moves + renames after OR returns. No OR change required.
- **NO data migration.** Past anonymisations are not relocated.

### Out of scope

- The folder-analysis surface (`folder-analysis-anonymization`) — covered by `anonymisation-folder-output-folder-layout`.
- Migrating past anonymisations to the new layout.
- Auto-backup of a previous run's `anonymised/` subfolder.
- Per-dossier override of the subfolder name (tenant-level only in v1).
- Single-file anonymisation layout change (unchanged in v1).
- Modifying OR's anonymise endpoint to accept a `targetFolder` parameter.

## Capabilities

### Modified Capabilities

- `batch-anonymization`

## Cross-app Dependencies

- **Soft** — `filinq:anonymise-output-as-pdf-by-default` — if PDF is the default output, the move-and-rename runs against PDFs; otherwise native-format files. The layout logic is format-agnostic.
- **Soft** — `filinq:anonymisation-folder-output-folder-layout` — applies the same logic to the folder-analysis flow.
- **Hard** — `filinq:anonymisation-grondslagen-summary-rendering` — that change writes the per-dossier summary at `<source-folder>/anonymised/grondslagen.pdf` (the location defined here).

## Impact

- **Code (filinq):** `lib/Service/BatchAnonymizeService.php` (post-process move), new helper `lib/Service/Conversion/OutputLayoutResolver.php`, `lib/Settings/admin/SettingsController.php` (admin UI for the config key).
- **API contract:** request payload unchanged; response field set unchanged — `anonymizedFilePath` now points into the subfolder; clients reading the field don't need code changes.
- **UX impact:** frontend file-listing components that previously expected `_anonymized`-suffixed filenames in the source folder must look in the subfolder; coordinate with frontend team. Clients that derive paths manually (rather than reading `anonymizedFilePath`) need updates.
- **Privacy / compliance:** reduces "send the wrong file" risk — the subfolder is a clear structural signal that the contents are redacted-for-distribution.
- **Migration:** None automated. Document in CHANGELOG; operators can clean up legacy `_anonymized`-suffixed files manually.
