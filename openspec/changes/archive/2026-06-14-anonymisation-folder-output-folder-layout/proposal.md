## Why

The folder-analysis anonymisation flow (`folder-analysis-anonymization` capability) shares the same operational-clutter / easy-misuse / re-anonymisation-cascade problems as the batch flow: writing `<original>_anonymized.<ext>` next to the original mixes redacted and source files in one place. This change applies the new layout — `<source-folder>/anonymised/<original-filename>` — to the folder-analysis surface, reusing the `OutputLayoutResolver` + config key introduced by the sibling change `anonymisation-batch-output-folder-layout`.

## What Changes

- **MODIFIED:** `folder-analysis-anonymization` capability — folder-driven outputs (synchronous + background-job) land in `<source-folder>/<configured-subfolder>/<original-filename>` instead of `<source-folder>/<base>_anonymized.<ext>`. Same suffix-stripping rules as the batch surface.
- **MODIFIED:** Source-discovery in the folder-analysis flow excludes files whose base name ends with `_anonymized`.
- **REUSES:** `OutputLayoutResolver` and the `docudesk.anonymisation.output_subfolder_name` config key from `anonymisation-batch-output-folder-layout`. No new helper, no new config.
- **DocuDesk-side post-process** — `lib/Service/FolderBatchService.php` and `lib/BackgroundJob/FolderExtractionJob.php` perform the move-and-rename after OR returns. No OR change required.

### Out of scope

- The batch anonymise surface (`batch-anonymization`) — covered by `anonymisation-batch-output-folder-layout`.
- Migrating past anonymisations.
- Single-file flow (unchanged).

## Capabilities

### Modified Capabilities

- `folder-analysis-anonymization`

## Cross-app Dependencies

- **Hard** — `docudesk:anonymisation-batch-output-folder-layout` — provides `OutputLayoutResolver` + the config key.
- **Soft** — `docudesk:anonymise-output-as-pdf-by-default` — layout logic is format-agnostic.

## Impact

- **Code (docudesk):** `lib/Service/FolderBatchService.php` (post-process move), `lib/BackgroundJob/FolderExtractionJob.php` (same post-process for the background job).
- **API contract:** request payload unchanged; response field `anonymizedFilePath` now points into the subfolder.
- **UX impact:** same as the batch sibling — frontend file-listing components must read `anonymizedFilePath` from API responses rather than deriving paths.
- **Migration:** None automated.
