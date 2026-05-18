# Changelog

## Unreleased

### Added
- `appendBasisSummary` (optional boolean, default `false`) flag on the per-document
  anonymise endpoint and the batch anonymise endpoint. When `true`, invokes the
  grondslagen summary rendering service after the anonymised file is written.
  PDF mode appends the summary as an extra page; `outputFormat: "preserve"` mode
  writes a separate `<original-base>_anonymized_grondslagen.pdf` alongside the
  anonymised file and returns `summaryFileId` / `summaryFilePath` in the response.
  Summary failure surfaces as a structured `warning` field (HTTP 200) — the
  anonymised file is always preserved. Pre-change clients see no behaviour change.

## 0.1.5 – 2024-09-07
### Added
- First version for the Nextcloud store

### Changed
- Changes in existing functionality for this release:

### Fixed
- Bug fixes for this release:

### Added
- Initial release

