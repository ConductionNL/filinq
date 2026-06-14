# folder-analysis-anonymization Specification

## Purpose
TBD - created by archiving change anonymisation-folder-output-folder-layout. Update Purpose after archive.
## Requirements
### Requirement: Folder-driven anonymisation MUST write outputs to the configured subfolder

The folder analysis flow (orchestrating extraction + anonymisation across all files in a target folder) MUST emit redacted outputs at `<source-folder>/<subfolder>/<cleanBase>.<ext>`, using the same `docudesk.anonymisation.output_subfolder_name` configuration key as the batch flow (default `anonymised`). The same `_anonymized` suffix-stripping rules from the `batch-anonymization` delta apply.

#### Scenario: Folder analysis flow uses the same layout as batch

- **GIVEN** a target folder `<dossier>` containing source files
- **WHEN** the folder analysis triggers anonymisation across the folder
- **THEN** redacted files are written to `<dossier>/anonymised/<cleanBase>.<ext>`
- **AND** the layout matches what the batch flow produces

#### Scenario: Background anonymisation jobs honour the layout

- **GIVEN** the background `FolderExtractionJob` (or equivalent scheduled work) processing a folder
- **WHEN** the job's anonymisation step runs
- **THEN** outputs land in the configured subfolder
- **AND** the output paths recorded in any persisted job state reflect the subfolder location

### Requirement: The folder flow MUST coexist cleanly with pre-change outputs

When a target folder contains legacy `_anonymized`-suffixed redactions from a pre-change run, the folder flow MUST NOT treat those legacy files as new sources to re-redact. Re-running the folder flow MUST produce a clean subfolder containing only the current anonymised version of each true source file. Legacy files in the source folder are left as-is for operator cleanup.

#### Scenario: Legacy `_anonymized`-suffixed files in source are not re-anonymised

- **GIVEN** a folder containing `foo.pdf` and `foo_anonymized.pdf` (the latter from a prior pre-change run)
- **WHEN** the folder analysis runs
- **THEN** only `foo.pdf` is treated as a source for anonymisation (the source-discovery logic excludes legacy `_anonymized`-suffixed files)
- **AND** the new redaction is written to `<folder>/anonymised/foo.pdf`
- **AND** `<folder>/foo_anonymized.pdf` is unchanged (operator deletes manually if desired)

#### Scenario: Source-discovery filter is consistent across batch and folder flows

- **GIVEN** the same folder structure
- **WHEN** processed via the batch anonymise endpoint OR the folder analysis flow
- **THEN** both flows discover the same set of sources (excluding legacy `_anonymized`-suffixed files)
- **AND** both flows produce the same destinations in the subfolder

