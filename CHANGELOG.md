# Changelog

## Unreleased

### Added
- `appendBasisSummary` flag on the per-document anonymise endpoint. When `true`, a Twig-rendered per-document grondslagen summary page is appended to the resulting anonymised PDF (or saved as a separate `<base>_grondslagen.pdf` file when the output isn't a PDF). Summary content: each redacted entity with its Woo Art. 5 grondslagen (resolved against the `base` schema), entity type, replacement placeholder, and footer totals. Same flag is honoured on the batch anonymise endpoint per file.
- New endpoint `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf` that regenerates a per-dossier summary PDF aggregating every anonymised file under the dossier's folder. Output is PDF/A-3b at `<dossier-folder>/grondslagen.pdf`.
- Auto-regeneration of the per-dossier summary on `dossier.checkedOn` updates. Opt-out per dossier via `configuration.grondslagen.autoRegenOnReview: false`.
- Dossier object's `configuration.grondslagen.{fileId, lastGeneratedAt}` populated after a successful render so the dossier UI can badge the report as fresh.

### Cross-change dependencies
- Hard: `openregister:entity-relation-grondslagen` (Wave 1.3) provides `EntityRelation.bases` and the `findAnonymisedEntitiesWithBasesForFile` read method this feature consumes.
- Hard: `docudesk:add-dossier-schema` (Wave 1.1) provides the `dossier` register, the `base` schema, and the free-form `configuration` field used for freshness metadata.
- Soft: `docudesk:anonymise-output-as-pdf-by-default` (Wave 4b) — when PDF is the default output, the per-document append always lands in-place. Until 4b ships, native-format outputs use the separate-PDF fallback path.
- Soft: `docudesk:anonymisation-output-folder-layout` (Wave 2) — once shipped, summary destinations move into `<source-folder>/anonymised/`. Until then, the flat in-folder path is used.

### Limitations
- Templates are NL-only in v1. EN follows when `register-i18n` lands.
- Per-document append is not strictly PDF/A — FPDI's PDF merge doesn't enforce the source's PDF/A conformance. The per-dossier output IS PDF/A-3b (pure mPDF render).

## 0.1.5 – 2024-09-07
### Added
- First version for the Nextcloud store

### Changed
- Changes in existing functionality for this release:

### Fixed
- Bug fixes for this release:

### Added
- Initial release

