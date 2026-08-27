---
status: draft
---

# Batch Anonymization — Delta for Output Folder Layout

This delta extends the existing `batch-anonymization` capability so batch / folder / dossier flows write redacted outputs to a `<source-folder>/anonymised/` subfolder (configurable name) instead of mixing them with originals using the `_anonymized` filename suffix. The subfolder name carries the "redacted" signal; filenames inside the subfolder are clean (no suffix). Single-file anonymisation is unchanged.

## ADDED Requirements

### Requirement: Batch / folder anonymisation MUST write outputs to a `<source-folder>/<subfolder>/` subfolder

After OpenRegister returns the anonymised file (which OR writes to `<source>/<base>_anonymized.<ext>` per its existing behaviour), the batch service MUST move the file to `<source>/<subfolder>/<cleanBase>.<ext>`, where:

- `<subfolder>` is the value of the configuration key `filinq.anonymisation.output_subfolder_name` (default: `anonymised`).
- `<cleanBase>` is the original source file's base name with any trailing `_anonymized` suffix stripped.

The subfolder MUST be created if it does not already exist. The move MUST be a single Nextcloud-API operation (preferably a same-filesystem rename, not a copy + delete).

#### Scenario: Single source file lands in the subfolder

- **GIVEN** a batch with a source file at `<dossier>/foo.pdf`
- **AND** the configured subfolder name is `anonymised` (default)
- **WHEN** the batch anonymise completes
- **THEN** the redacted file is at `<dossier>/anonymised/foo.pdf`
- **AND** no file at `<dossier>/foo_anonymized.pdf` is left behind
- **AND** the original `<dossier>/foo.pdf` is unchanged

#### Scenario: Multiple files all land in the subfolder

- **GIVEN** a batch with three source files (`foo.pdf`, `bar.docx`, `baz.txt`) at `<dossier>/`
- **WHEN** the batch anonymise completes (with Change A's PDF default in effect)
- **THEN** `<dossier>/anonymised/foo.pdf`, `<dossier>/anonymised/bar.pdf`, `<dossier>/anonymised/baz.pdf` exist
- **AND** the originals are unchanged at the dossier root
- **AND** no `_anonymized`-suffixed files exist anywhere

#### Scenario: Subfolder is created if it doesn't exist

- **GIVEN** a dossier folder that does NOT yet contain a `<subfolder>/` directory
- **WHEN** the first batch anonymise runs
- **THEN** the subfolder is created
- **AND** redacted files are written into it

### Requirement: Filenames inside the subfolder MUST drop the `_anonymized` suffix

The destination filename in the subfolder MUST be the source file's base name with any trailing `_anonymized` (case-sensitive) stripped. The extension is preserved. This handles both fresh runs (no suffix on source) and re-anonymisation runs against legacy-layout outputs.

#### Scenario: Fresh source filename copies cleanly

- **GIVEN** a source file `<dossier>/foo.pdf`
- **WHEN** the batch anonymise produces a redacted file
- **THEN** the redacted filename in the subfolder is `foo.pdf` (no suffix added)

#### Scenario: Legacy `_anonymized`-suffixed source filename strips the suffix

- **GIVEN** a source file `<dossier>/foo_anonymized.pdf` (left from a pre-change run)
- **WHEN** the batch anonymise produces a redacted file
- **THEN** the redacted filename in the subfolder is `foo.pdf` (suffix stripped, not `foo_anonymized.pdf` and not `foo_anonymized_anonymized.pdf`)

#### Scenario: `_anonymized` in the middle of a filename is preserved

- **GIVEN** a source file `<dossier>/foo_anonymized_v2.pdf` (suffix is not at the end)
- **WHEN** the batch anonymise runs
- **THEN** the redacted filename in the subfolder is `foo_anonymized_v2.pdf` (no part stripped — the regex matches `_anonymized` only at the end of the base name)

### Requirement: When the subfolder already exists, files MUST be overwritten by destination filename

If `<source>/<subfolder>/` exists from a previous run, the new run MUST reuse it. Files in the subfolder whose name matches a destination MUST be overwritten. Files that don't correspond to any destination in the current run MUST be left untouched (no auto-cleanup).

#### Scenario: Re-run overwrites prior output

- **GIVEN** `<dossier>/anonymised/foo.pdf` from a prior batch run
- **AND** a current batch run that produces a new redacted version of `foo.pdf`
- **WHEN** the current batch completes
- **THEN** the file at `<dossier>/anonymised/foo.pdf` is the current run's output (overwritten)

#### Scenario: Stale files in the subfolder are not auto-removed

- **GIVEN** `<dossier>/anonymised/old-file.pdf` from a prior batch run
- **AND** a current batch run that does NOT include `old-file.pdf` in its source set
- **WHEN** the current batch completes
- **THEN** `<dossier>/anonymised/old-file.pdf` is unchanged
- **AND** no error or warning is raised — operators clean up manually if desired

### Requirement: The subfolder name MUST be tenant-configurable via `filinq.anonymisation.output_subfolder_name`

The configuration key `filinq.anonymisation.output_subfolder_name` MUST be readable via the standard `IAppConfig` pattern. Its default MUST be `anonymised`. The value MUST be validated as a single path segment: non-empty, lowercase letters / digits / hyphen / underscore only, no dots / slashes / spaces. Invalid values MUST be rejected at admin settings save time with a clear error.

#### Scenario: Default is `anonymised`

- **GIVEN** a fresh Filinq install where `output_subfolder_name` is unset
- **WHEN** the batch anonymise reads the config
- **THEN** the resolved subfolder name is `anonymised`

#### Scenario: Tenant can override to a different valid name

- **GIVEN** an admin sets `output_subfolder_name` to `redacted`
- **WHEN** the next batch anonymise runs
- **THEN** outputs land in `<source>/redacted/`
- **AND** any existing `<source>/anonymised/` folders from prior runs remain untouched

#### Scenario: Invalid value is rejected at save time

- **WHEN** an admin attempts to set `output_subfolder_name` to `../traversal`
- **THEN** the admin settings save returns an error
- **AND** the config value is unchanged
- **AND** the validation error message identifies the disallowed character (`/` and the leading dot)

### Requirement: Single-file anonymisation MUST be unchanged

The per-document anonymise endpoint (single source file, not a batch / folder flow) MUST continue to write its output as `<file>_anonymized.<ext>` in the same parent folder as the source. The subfolder layout MUST NOT apply to single-file flows.

#### Scenario: Single-file anonymise stays in source folder with suffix

- **GIVEN** a single source file `<some-folder>/report.pdf`
- **WHEN** the operator triggers a single-file anonymise (not via batch)
- **THEN** the redacted file is at `<some-folder>/report_anonymized.pdf` (existing pre-change behaviour)
- **AND** no `<some-folder>/anonymised/` subfolder is created

### Requirement: The grondslagen summary destination MUST be `<source>/<subfolder>/grondslagen.pdf`

For dossier-level grondslagen summary generation (per the `anonymisation-grondslagen-summary` capability), the destination path MUST be `<source>/<subfolder>/grondslagen.pdf`, using the same configurable subfolder name as redacted files.

#### Scenario: Summary lands in the subfolder

- **GIVEN** a dossier with redacted files in `<dossier>/anonymised/`
- **WHEN** the per-dossier grondslagen summary is regenerated
- **THEN** the summary PDF is at `<dossier>/anonymised/grondslagen.pdf`
- **AND** it sits alongside the redacted files

### Requirement: The change MUST update `anonymizedFilePath` in API responses

The batch and per-file anonymise responses' `anonymizedFilePath` field MUST reflect the new destination (i.e. `<source>/<subfolder>/<cleanBase>.<ext>`). Pre-change clients that read this field MUST continue to work — they receive the new path automatically and follow it.

#### Scenario: Response reports the subfolder location

- **GIVEN** a batch anonymise that successfully wrote to `<dossier>/anonymised/foo.pdf`
- **WHEN** the response is returned
- **THEN** the file's `anonymizedFilePath` field contains `<dossier>/anonymised/foo.pdf`
- **AND** clients reading this field can locate the file directly without computing the path themselves

### Requirement: Move-and-rename failures MUST NOT discard the anonymised file

If the post-process move (or rename) fails (filesystem error, locking conflict, target path exists with a permissions problem), the anonymised file MUST be preserved at OR's original output location (`<source>/<base>_anonymized.<ext>`) and the response MUST include a `warning` indicating the layout could not be applied. The anonymisation itself is treated as successful — the file exists; it's just at the legacy path.

#### Scenario: Filesystem error during move surfaces as a warning

- **GIVEN** a batch anonymise where the post-process move fails (e.g. NC reports a quota error on the destination)
- **WHEN** the response is returned
- **THEN** the response is HTTP 200 (or HTTP 207 multi-status for batch)
- **AND** the file's `anonymizedFilePath` is the legacy path `<source>/<base>_anonymized.<ext>`
- **AND** a `warning` field on that file indicates the layout was not applied
