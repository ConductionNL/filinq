# Changelog

## Unreleased

### Added
- **Dossier register and `dossier` / `base` schemas in `docudesk_register.json`.** A dossier is a Nextcloud folder (`@self.folder`) whose contents are anonymised under one or more Woo Art. 5 grondslagen; the `base` schema holds the canonical six grondslagen as seed objects (`persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`, `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`, `nationale-veiligheid`). The dossier schema carries `name`, optional `description`, optional `bases[]` (`$ref` array to `base` objects), and optional `checkedOn` (date-time review timestamp). Five seed dossiers ship across the three personas (Gemeente Demostad × 2, Conduction × 1, ReisBureau Zonnestraal × 2), including one with empty `bases` + `null` `checkedOn` to exercise the optionality cases. No new PHP code — folder binding and CRUD ride on OpenRegister's existing `@self.folder` pipeline and the generic `/api/objects/{register}` routes. (`add-dossier-schema`)

## 0.1.5 – 2024-09-07
### Added
- First version for the Nextcloud store

### Changed
- Changes in existing functionality for this release:

### Fixed
- Bug fixes for this release:

### Added
- Initial release

