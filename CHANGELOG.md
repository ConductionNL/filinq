# Changelog

## Unreleased

### Added
- **Prohibition guard + standing-consent auto-skip in the anonymise flow.** Standing-consent matches are auto-skipped at analysis (`skip_anonymization=true` on the relation); prohibition matches surface as a read-only `prohibitionMatch` (`{ruleId, ruleName, highConfidence}`) on the extract response. A new guarded endpoint `PATCH /api/anonymization/relations/{id}` records skip/include decisions and rejects skipping a prohibition-matched entity with **HTTP 422** — absolute at/above `docudesk.prohibition.high_confidence_threshold` (default `0.85`; `force` cannot release), otherwise releasable with `force`. The 422 body carries `threshold` plus per-entity `confidence` and `absolute` so the UI decides whether to offer `force`. The anonymise flow keeps a defence-in-depth backstop for the absolute tier, and the review UI hard-locks the skip toggle for absolute prohibition matches. Lightweight compute-at-guard design; no OpenRegister schema change. (`anonymise-prohibition-consent-guard`)
- **PDF-by-default output on the anonymise endpoints.** After OpenRegister returns an anonymised file in its native format, DocuDesk now converts the result to PDF (PDF/A-3b where feasible) before writing back to Nextcloud Files. The conversion is driven by a new `PdfConversionService` cascade:
  1. `OfficeAppBackend` — Collabora, OnlyOffice, or Euro Office via Nextcloud's `OCP\Files\Conversion\IConversionManager` (NC 31+). Single API for all three Office app integrations.
  2. `PhpWordBackend` — DOC, DOCX, ODT, RTF, HTML via PhpOffice\PhpWord + mPDF. Spreadsheet and presentation formats are explicitly out of scope.
  3. `MpdfBackend` — HTML and TXT direct via mPDF, reusing the print-preview PDF/A-3b configuration.
  4. `EmlBackend` — stubbed; activates once OpenRegister ships `message/rfc822` text extraction.
  First success wins; total failure throws `ConversionFailedException` whose attempt records surface in the HTTP 422 response body. (`anonymise-output-as-pdf-by-default`)
- **Per-call `outputFormat` request field** on `POST /api/anonymization/anonymize/{fileId}` and `POST /api/anonymization/batchAnonymize/{batchId}`. Accepts `"pdf"` (default) or `"preserve"`. Per-call value overrides the tenant default. (`anonymise-output-as-pdf-by-default`)
- **Admin setting "Always export anonymised documents as PDF"** in the new Anonymisation section of the DocuDesk settings panel. Backed by the `docudesk.anonymisation.default_output_format` `IAppConfig` key. Switching off flips the tenant default to `"preserve"`; callers can still override per-request. (`anonymise-output-as-pdf-by-default`)
- **`phpoffice/phpword ^1.2`** added to `composer.json` as the engine for the in-process PhpWord conversion backend. Reuses the existing `mpdf/mpdf` dependency for PDF/A-3b emission. (`anonymise-output-as-pdf-by-default`)
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
- **New `outputFormat: "pdf-only"` value** on the anonymise (`POST /api/anonymization/anonymize/{fileId}`) and batch anonymise (`POST /api/anonymization/batchAnonymize/{batchId}`) endpoints. Converts the anonymised output to PDF (same cascade as `pdf`) and then best-effort deletes the native-format anonymised intermediate so only the PDF remains. A cleanup-delete failure is logged at warning level and never fails the run. The `anonymizationLink` relation already references the PDF, so there is no relation/schema change. (`anonymise-pdf-only-output-mode`)

### Changed
- **DocuDesk register configuration version bumped 4.0.0 → 5.0.0** to trigger OpenRegister's `imported_config_docudesk_version` gate so the new `dossier` register + `base` / `dossier` schemas + the eleven seed objects (6 base + 5 dossier) are imported on app upgrade. Consumers reading the configuration version (e.g. `occ config:app:get openregister imported_config_docudesk_version`) MUST expect `5.0.0` post-upgrade. (`add-dossier-schema`)
- The Consent Management admin page is now **Consent Workflow** and filters to `scope: "document"` records only. Rows whose `policyMatch` is non-null show a "policy" badge. (`entity-publication-policies`)
- The publication-prep anonymisation toggle is now keyed off the **referent type** of `policyMatch`, not `consentStatus`: prohibition → ON+locked, standing consent → OFF+overridable, no match → existing UX. (`entity-publication-policies`)

### Behavior changes
- **The anonymise endpoint now returns PDF/A-3b output by default.** Callers that need the legacy native-format behaviour must send `outputFormat: "preserve"` on the request body. Conversion failures return HTTP 422 with a structured `conversionAttempts` array — operators that previously got native-format output for unsupported types may need to install a supported Office app integration (Collabora, OnlyOffice, or Euro Office) or send `outputFormat: "preserve"`. The un-converted anonymised intermediate is best-effort rolled back on conversion failure so the operator never sees a half-finished mixed-format result. Spreadsheet and presentation formats (XLSX, ODS, PPTX, ODP) are NOT supported by the no-Office-app fallback tier — they will return 422 unless an Office app is configured. (`anonymise-output-as-pdf-by-default`)
- **Batch anonymise responses now carry per-file `conversionAttempts`** on the file's batch entry when conversion fails for that file. The batch continues with the next file rather than aborting. (`anonymise-output-as-pdf-by-default`)
- **The default anonymise `outputFormat` is now `pdf-only` (was `pdf`).** By default the native-format anonymised intermediate is deleted after a successful PDF conversion, closing the privacy hole where a re-editable, metadata-carrying native copy of the redacted document was left on disk next to the PDF. Callers that need the native file kept **alongside** the PDF must now explicitly send `outputFormat: "pdf"`; native-only callers send `outputFormat: "preserve"`. When the anonymised result is already a PDF no intermediate is created, so `pdf-only` is observably identical to `pdf`. Rollback is configuration-only — set `docudesk.anonymisation.default_output_format = pdf` to restore the keep-both default. No DB migration. (`anonymise-pdf-only-output-mode`)
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

