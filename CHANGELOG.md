# Changelog

## Unreleased

### Added
- **Dossier register and `dossier` / `base` schemas in `docudesk_register.json`.** A dossier is a Nextcloud folder (`@self.folder`) whose contents are anonymised under one or more Woo Art. 5 grondslagen; the `base` schema holds the canonical six grondslagen as seed objects (`persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`, `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`, `nationale-veiligheid`). The dossier schema carries `name`, optional `description`, optional `bases[]` (JSON array of strings — each element is the slug of a `base` object in the same register; intentionally NOT a `$ref` array per design D1's v1 trade-off, see `openspec/changes/add-dossier-schema/`), and optional `checkedOn` (date-time review timestamp). Five seed dossiers ship across the three personas (Gemeente Demostad × 2, Conduction × 1, ReisBureau Zonnestraal × 2), including one with empty `bases` + `null` `checkedOn` to exercise the optionality cases. No new PHP code — folder binding and CRUD ride on OpenRegister's existing `@self.folder` pipeline and the generic `/api/objects/{register}` routes. (`add-dossier-schema`)
- `publicationProhibition` schema for entity-level deny rules in the publication-clearance flow (court orders, minor protection, undercover officers, AVG categorical exemptions). Includes seed data covering four representative scenarios. (`entity-publication-policies`)
- `scope` discriminator on `publicationConsent` plus the entity-scope field set (`matchRules`, `validFrom`, `validUntil`, `active`, `consentMethod`, `consentDocument`, `consentScope`) — enables "standing publication consent" records that pre-empt the per-document workflow. (`entity-publication-policies`)
- `policyMatch` field on `publicationConsent` linking a `scope: "document"` record back to the prohibition or standing consent that drove its outcome. (`entity-publication-policies`)
- New **Standing Publication Consents** admin page (`scope: "entity"` records — list, edit, expire, revoke). (`entity-publication-policies`)
- New **Publication Prohibitions** admin page (CRUD for `publicationProhibition` records). (`entity-publication-policies`)
- Detection-time `PolicyMatchService` matches detected entities against active rules with O(1) lookup; conflict resolution is deterministic ("prohibition wins", lexicographic UUID tie-break). (`entity-publication-policies`)
- Retroactive force-resolve: creating or widening a prohibition force-resolves matching in-flight `scope: "document"` records to `anonymized`. Standing-consent creation is **not** retroactive (future detections only). (`entity-publication-policies`)
- Service-level RBAC gate: writes to standing consents require membership in `docudesk-standing-consent-admins`. Writes to prohibitions require admin role or membership in `docudesk-prohibition-admins`. (`entity-publication-policies`)
- `appendBasisSummary` flag on the per-document anonymise endpoint. When `true`, a Twig-rendered per-document grondslagen summary page is appended to the resulting anonymised PDF (or saved as a separate `<base>_grondslagen.pdf` file when the output isn't a PDF). Summary content: each redacted entity with its Woo Art. 5 grondslagen (resolved against the `base` schema), entity type, replacement placeholder, and footer totals. Same flag is honoured on the batch anonymise endpoint per file. (`anonymisation-grondslagen-summary`)
- New endpoint `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf` that regenerates a per-dossier summary PDF aggregating every anonymised file under the dossier's folder. Output is PDF/A-3b at `<dossier-folder>/grondslagen.pdf`. (`anonymisation-grondslagen-summary`)
- Auto-regeneration of the per-dossier summary on `dossier.checkedOn` updates. Opt-out per dossier via `configuration.grondslagen.autoRegenOnReview: false`. (`anonymisation-grondslagen-summary`)
- Dossier object's `configuration.grondslagen.{fileId, lastGeneratedAt}` populated after a successful render so the dossier UI can badge the report as fresh. (`anonymisation-grondslagen-summary`)

### Changed
- **DocuDesk register configuration version bumped 4.0.0 → 5.0.0** to trigger OpenRegister's `imported_config_docudesk_version` gate so the new `dossier` register + `base` / `dossier` schemas + the eleven seed objects (6 base + 5 dossier) are imported on app upgrade. Consumers reading the configuration version (e.g. `occ config:app:get openregister imported_config_docudesk_version`) MUST expect `5.0.0` post-upgrade. (`add-dossier-schema`)
- The Consent Management admin page is now **Consent Workflow** and filters to `scope: "document"` records only. Rows whose `policyMatch` is non-null show a "policy" badge. (`entity-publication-policies`)
- The publication-prep anonymisation toggle is now keyed off the **referent type** of `policyMatch`, not `consentStatus`: prohibition → ON+locked, standing consent → OFF+overridable, no match → existing UX. (`entity-publication-policies`)

### Behavior changes
- Inside publication-clearance flows, the consent service consults the policy layer **before** defaulting to the WOO workflow. Existing `consentStatus` enum is unchanged; the policy-pre-empted distinction lives in `policyMatch` + `notificationStatus: "skipped"`. (`entity-publication-policies`)
- Generic anonymisation flows (file sanitisation prior to email/storage) are unaffected — they do not call `ConsentService::createConsentRequest` and therefore do not consult the policy layer. (`entity-publication-policies`)

### Cross-change dependencies (Wave 4a)
- Hard: `openregister:entity-relation-grondslagen` (Wave 1.3) provides `EntityRelation.bases` and the `findAnonymisedEntitiesWithBasesForFile` read method this feature consumes.
- Hard: `docudesk:add-dossier-schema` (Wave 1.1) provides the `dossier` register, the `base` schema, and the free-form `configuration` field used for freshness metadata.
- Soft: `docudesk:anonymise-output-as-pdf-by-default` (Wave 4b) — when PDF is the default output, the per-document append always lands in-place. Until 4b ships, native-format outputs use the separate-PDF fallback path.
- Soft: `docudesk:anonymisation-output-folder-layout` (Wave 2) — once shipped, summary destinations move into `<source-folder>/anonymised/`. Until then, the flat in-folder path is used.

### Limitations (Wave 4a)
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

