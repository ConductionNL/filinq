# Changelog

## Unreleased

### Added
- **`outputFormat` parameter on anonymise and batch anonymise endpoints** (#16).
  Both `POST /api/anonymization/anonymize/{fileId}` and
  `POST /api/anonymization/batch/{batchId}/anonymize` now accept an optional top-level
  `outputFormat` field with values `"pdf"` (default) or `"preserve"`. Any other value
  returns HTTP 400.
- **Tenant-level `default_output_format` config key** (`docudesk.anonymisation.default_output_format`).
  Administrators can override the default output format without requiring per-call changes.
  Per-call value takes precedence over tenant default; both fall back to `"pdf"`.
- **`ConversionFailedException`** — new typed exception in `lib/Exception/` that carries
  a per-backend attempt log for structured HTTP 422 responses when PDF conversion fails.
- **`unredactedEntities[]` on per-document and batch anonymise endpoints.**
  Operators can now pass entities they intend to publish unredacted alongside
  the usual `entities[]`. Each entry requires `entityId`, `entityText`,
  `entityType`, and a non-empty `publicationBases[]`; `contactEmail` and
  `contactAddress` are optional.
- **`createdConsents[]` in the anonymise response.** When `unredactedEntities`
  is supplied, the response gains a `createdConsents[]` field with one entry
  per entity including the resulting `publicationConsent` UUID and status.
- **Batch endpoint returns HTTP 207** when some files have prohibition
  violations on `unredactedEntities[]`; HTTP 422 when all files failed; HTTP
  200 when all succeeded.
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

### Behavior changes
- **Anonymise endpoint now produces PDF/A-3b output by default** (#16).
  Callers that need native-format output (DOCX, ODT, TXT, etc.) must explicitly send
  `outputFormat: "preserve"` or set the tenant default via IAppConfig.
- **Conversion failures return HTTP 422** instead of falling back to native-format output.
  Operators previously getting native-format output for unsupported file types may need to
  install LibreOffice, an Office app (Collabora/OnlyOffice), or send `outputFormat: "preserve"`.
- **Batch endpoint returns HTTP 207 Multi-Status** when some files succeed and some fail
  conversion (previously, all failures were HTTP 422).
- **Anonymise may now respond HTTP 422** when any `unredactedEntities[]` entry
  matches an active `publicationProhibition` rule (any confidence — hard gate).
- **Batch anonymise may now respond HTTP 207** on per-file prohibition
  violations; per-file details in `prohibitedEntries[]` on the file entry.
- Inside publication-clearance flows, the consent service consults the policy layer **before** defaulting to the WOO workflow. Existing `consentStatus` enum is unchanged; the policy-pre-empted distinction lives in `policyMatch` + `notificationStatus: "skipped"`. (`entity-publication-policies`)
- Generic anonymisation flows (file sanitisation prior to email/storage) are unaffected — they do not call `ConsentService::createConsentRequest` and therefore do not consult the policy layer. (`entity-publication-policies`)

### Security / Fixed
- **NativeSigningProvider sessions now persist via OpenRegister** (fixes #287).
  The previous implementation held sessions in a per-request `$sessions` PHP
  array, so `initiateSigning()` wrote one entry that `checkStatus()`,
  `downloadSignedDocument()` and `cancelSigning()` (running in fresh requests)
  could never find — every native signing flow failed with "session not found".
  A new `signingSession` schema is added to the `signing` register (keyed by
  `externalId`); the provider reads/writes via the OR `ObjectService`. The SES
  marker / HMAC embedding in `downloadSignedDocument()` is acknowledged as a
  separate follow-up: until the PDF marker writer ships, the provider returns
  the persisted `signedDocumentPath` (or the original `documentPath`) and logs
  an info-level note flagging that the marker hasn't been embedded yet.
- **`SigningController::listRequests()` no longer masks real failures as empty
  success** (fixes #288). The broad `catch (\Throwable)` previously returned
  an empty list with `notConfigured: true` and logged at WARNING for any
  failure — an OR/DB outage was indistinguishable from "not configured". The
  catch is now narrowed to `\Error` (the genuine missing-method/OR-sidecar-lag
  case) and logs at ERROR; real `\Exception` / runtime infra failures now
  propagate to the framework's 500 handler so they surface in monitoring.
- **Signing audit immutability is now enforced at the OpenRegister storage
  layer** (fixes #289). The `signingAuditEntry` schema in
  `lib/Settings/docudesk_register.json` carries `immutable: true` *and*
  `appendOnly: true`, so OR rejects update/delete against existing audit
  entries regardless of which code path tries it. The misleading dead-code
  `rejectUpdate()` / `rejectDelete()` methods on `SigningAuditService` —
  which were never wired into any mutation path and could give the false
  impression that immutability was enforced in-app — have been removed.

### Changed
- **DocuDesk register configuration version bumped 5.0.0 → 5.1.0** to trigger
  OpenRegister's `imported_config_docudesk_version` gate so the new
  `signingSession` schema and the `appendOnly` flag on `signingAuditEntry`
  are imported on app upgrade. Consumers reading
  `occ config:app:get openregister imported_config_docudesk_version` MUST
  expect `5.1.0` post-upgrade. (#287, #289)
- **DocuDesk register configuration version bumped 4.0.0 → 5.0.0** to trigger OpenRegister's `imported_config_docudesk_version` gate so the new `dossier` register + `base` / `dossier` schemas + the eleven seed objects (6 base + 5 dossier) are imported on app upgrade. Consumers reading the configuration version (e.g. `occ config:app:get openregister imported_config_docudesk_version`) MUST expect `5.0.0` post-upgrade. (`add-dossier-schema`)
- The Consent Management admin page is now **Consent Workflow** and filters to `scope: "document"` records only. Rows whose `policyMatch` is non-null show a "policy" badge. (`entity-publication-policies`)
- The publication-prep anonymisation toggle is now keyed off the **referent type** of `policyMatch`, not `consentStatus`: prohibition → ON+locked, standing consent → OFF+overridable, no match → existing UX. (`entity-publication-policies`)

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

