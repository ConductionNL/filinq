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
- **Dossier register and `dossier` / `base` schemas in `docudesk_register.json`.** A dossier is a Nextcloud folder (`@self.folder`) whose contents are anonymised under one or more Woo Art. 5 grondslagen; the `base` schema holds the canonical six grondslagen as seed objects (`persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`, `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`, `nationale-veiligheid`). The dossier schema carries `name`, optional `description`, optional `bases[]` (JSON array of strings — each element is the slug of a `base` object in the same register; intentionally NOT a `$ref` array per design D1's v1 trade-off, see `openspec/changes/add-dossier-schema/`), and optional `checkedOn` (date-time review timestamp). Five seed dossiers ship across the three personas (Gemeente Demostad × 2, Conduction × 1, ReisBureau Zonnestraal × 2), including one with empty `bases` + `null` `checkedOn` to exercise the optionality cases. No new PHP code — folder binding and CRUD ride on OpenRegister's existing `@self.folder` pipeline and the generic `/api/objects/{register}` routes. (`add-dossier-schema`)

### Changed
- **DocuDesk register configuration version bumped 4.0.0 → 5.0.0** to trigger OpenRegister's `imported_config_docudesk_version` gate so the new `dossier` register + `base` / `dossier` schemas + the eleven seed objects (6 base + 5 dossier) are imported on app upgrade. Consumers reading the configuration version (e.g. `occ config:app:get openregister imported_config_docudesk_version`) MUST expect `5.0.0` post-upgrade. (`add-dossier-schema`)

## 0.1.5 – 2024-09-07
### Added
- First version for the Nextcloud store

### Changed
- Changes in existing functionality for this release:

### Fixed
- Bug fixes for this release:

### Added
- Initial release

