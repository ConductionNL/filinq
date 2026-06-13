# Changelog

## Unreleased

### Behavior changes
- **Folder-analysis anonymisation outputs now land in a subfolder.** Redacted files are written to `<source-folder>/anonymised/<original-filename>` instead of `<source-folder>/<base>_anonymized.<ext>`. The subfolder name is tenant-configurable via `docudesk.anonymisation.output_subfolder_name` (default `anonymised`). Single-file anonymisation is unchanged. (`anonymisation-folder-output-folder-layout`)
- **Folder source-discovery excludes `_anonymized`-suffixed files.** Legacy redacted outputs from pre-layout runs are no longer included as sources for re-anonymisation; they are left as-is for operator cleanup. (`anonymisation-folder-output-folder-layout`)
- **Response field `anonymizedFilePath` reflects the subfolder location.** After a successful post-process move, `anonymizedFilePath` in the batch file entry points into the `anonymised/` subfolder. On move failure (permissions, disk error), the path is the legacy location and a `warning` field with `code: "MOVE_FAILED"` is attached to the file entry. (`anonymisation-folder-output-folder-layout`)

### Added

- **`register-i18n` full adoption (W7).** Extended `translatable: true`
  to user-facing string fields across the full register. Register
  bumped to v5.5.0; ten schemas bumped from v1.0.0 → v1.1.0
  (`templateVersion`, `huisstijl`, `base`, `correspondence`,
  `signingRequest`, `signingSession`, `signerRecord`,
  `publicationConsent`, `publicationProhibition`,
  `batchCorrespondenceJob`). New
  `LanguageNegotiationMiddleware` registered via
  `Application::register()` so docudesk requests propagate the
  resolved language onto OR's request-scoped `LanguageService`. New
  `docs/features/i18n.md` documents the language-negotiation
  contract end-to-end. (`register-i18n`)

### Changed

- **`SigningAuditService` now emits via OR audit trail.**
  `SigningAuditService::logEvent()` no longer writes to the private `signingAuditEntry`
  OR schema. All signing events are now routed through
  `AuditTrailMapper::createAuditTrailEntry()` with action types of the form
  `docudesk.signing.{ACTION}` (e.g. `docudesk.signing.SIGNED`). OR's native hash-chained
  audit trail enforces immutability and Archiefwet 1995 compliance natively; the in-app
  `rejectUpdate()` / `rejectDelete()` guards were already removed in a prior release.
  (`migrate-signing-audit-to-or-audit`)

### Deprecated

- **`signingAuditEntry` schema.** The `signingAuditEntry_register` and
  `signingAuditEntry_schema` IAppConfig keys are deprecated as of this release.
  Existing records remain readable (read-only) for one major release. No new events are
  written to the `signingAuditEntry` schema. (`migrate-signing-audit-to-or-audit`)

### Important: Archiefwet 1995 retention configuration required

Deployments MUST configure OR retention for the signing-related register to **≥ 3650 days**
(10 years) to comply with Archiefwet 1995. This is an OR admin UI / `occ` command
configuration — not enforced in application code. See the administration guide for details.
(`migrate-signing-audit-to-or-audit`)

### Added
- **`template` + `dossier` user-facing string fields adopt
  OpenRegister `register-i18n`.** `template.name`, `template.description`,
  `template.content`, `template.category`, `dossier.name`, and
  `dossier.description` are now flagged `"translatable": true` in
  `lib/Settings/docudesk_register.json` (register v5.4.0; template
  schema v1.1.0; dossier schema v1.1.0). OR's `TranslationHandler`
  picks up the flag automatically — simple string values are wrapped
  under the register's default language on save, and the API surface
  respects `Accept-Language` / `?_lang=` via the OR language-negotiation
  middleware. No database migration is required because translations
  live in the existing object JSON column. Templates can now ship in
  Dutch + English variants from the same register record. See
  `openspec/changes/register-i18n/tasks.md`. (`register-i18n`)
- **EML inputs now produce assembled PDF/A-3b documents.** When an `.eml`
  file enters DocuDesk and a downstream operation (anonymise, OCR, sign,
  archive) needs a PDF, the conversion cascade routes it through the new
  `EmlBackend` + `EmlPdfAssemblyService`. The assembled output combines a
  Dutch-language envelope page (Van/Aan/Cc/Onderwerp/Datum + body),
  PDF/A-3 file-attachment annotations carrying every attachment's raw
  bytes, divider pages per attachment, and rendered content pages for
  PDF / image / plain-text / nested-EML attachments (depth-3 cap on
  nested EML). Tenant configuration covers a kill-switch
  (`docudesk.conversion.backends.eml_enabled`), envelope-only mode
  (`docudesk.conversion.eml.append_attachment_pages`), a per-attachment
  render byte cap (`docudesk.conversion.eml.max_attachment_render_size_bytes`,
  default 25 MiB), and configurable Twig template paths. Operators no
  longer need `outputFormat: "preserve"` for EML inputs. Templates are
  NL-only in v1; EN follows under `register-i18n`. See
  `docs/features/eml-pdf-assembly.md`. (`eml-pdf-assembly`)
- **Idempotent `ConsentService::createConsentRequest()`** — keyed on `(documentId, entityKey, scope: "document")`. A second call for the same key updates the existing record rather than creating a duplicate; the caller receives `wasUpdated: true` in the response. Falls back to `entityText` matching when `entityKey` is null (legacy records). `scope: "entity"` standing-consent records are never matched as duplicates. (`consent-create-idempotency-and-notes`)
- **Sentinel-tagged additional-bases serialisation in `publicationConsent.notes`** — `publicationBases[0]` writes to the existing `legalBasis` field (truncated at 500 chars at word boundary); elements `[1..N]` are rendered inside an HTML-comment sentinel region (`<!-- docudesk:additional-publication-bases:begin/end -->`). The sentinel is markdown-invisible, re-submittable (idempotent re-render), and operator-authored content outside the brackets is preserved across re-submits. (`consent-create-idempotency-and-notes`)
- **`PolicyRejectedException`** — new typed exception thrown when `PolicyMatchService` returns a prohibition match during `createConsentRequest`. Carries `ruleUuid` and `ruleName` for operator-facing notification. (`consent-create-idempotency-and-notes`)
- **`ConsentNotesHelper`** — new service encapsulating sentinel region write/strip/truncate logic. (`consent-create-idempotency-and-notes`)

### Behavior changes
- **`createConsentRequest()` is now idempotent**: re-submitting a duplicate `(documentId, entityKey)` pair now updates the existing record (previously would have created a duplicate or raised a 409-style error). Existing operator-authored notes content is preserved across re-submits. The bracketed `<!-- docudesk:additional-publication-bases:* -->` region is auto-managed by the service.
- **`createConsentRequest()` signature** now accepts `extra['publicationBases']`, `extra['entityKey']`, `extra['contactEmail']`, `extra['contactAddress']` in addition to the existing fields. The `ALLOWED_CREATE_FIELDS` whitelist in `ConsentCrudService` is updated accordingly.


- **`prohibitionMatch` per entity on `GET /api/anonymization/batch/{batchId}/entities`.**
  Each consolidated entity now carries a `prohibitionMatch` field: `null` when no publication-prohibition rule matches, or `{ruleId, ruleName, highConfidence}` when a `publicationProhibition` rule matches. `highConfidence` is `true` when the entity's `highestConfidence` is at or above the configured threshold (`docudesk.prohibition.high_confidence_threshold`, default 0.85). The frontend review UI uses this to render prohibition-locked entities without re-running the matcher client-side. (`anonymisation-entity-review-prohibition-hints`)
- **`suggestedBases` per entity on `GET /api/anonymization/batch/{batchId}/entities`.**
  Each consolidated entity now carries a `suggestedBases` field: a deduplicated union of `bases[]` from the dossier(s) the batch's files belong to. Empty array when files are not in any dossier or the dossier has no bases configured. Used to pre-fill the grondslag picker in the review UI. (`anonymisation-entity-review-prohibition-hints`)
- **`BasesResolverService`** — new service that resolves the union of Woo Art. 5 grondslagen bases from dossier(s) for a batch's files, supporting folder-based batches, upload batches, multi-dossier batches, and orphan files. (`anonymisation-entity-review-prohibition-hints`)
- **`PolicyMatchService::matchProhibition()`** — new convenience method that wraps the existing `match()` call and returns only prohibition matches in the shape expected by the entity-review and extract surfaces. (`anonymisation-entity-review-prohibition-hints`)
- **PDF-by-default output on the anonymise endpoints.** After OpenRegister returns an anonymised file in its native format, DocuDesk now converts the result to PDF (PDF/A-3b where feasible) before writing back to Nextcloud Files. The conversion is driven by a new `PdfConversionService` cascade:
  1. `OfficeAppBackend` — Collabora, OnlyOffice, or Euro Office via Nextcloud's `OCP\Files\Conversion\IConversionManager` (NC 31+). Single API for all three Office app integrations.
  2. `PhpWordBackend` — DOC, DOCX, ODT, RTF, HTML via PhpOffice\PhpWord + mPDF. Spreadsheet and presentation formats are explicitly out of scope.
  3. `MpdfBackend` — HTML and TXT direct via mPDF, reusing the print-preview PDF/A-3b configuration.
  4. `EmlBackend` — stubbed; activates once OpenRegister ships `message/rfc822` text extraction.
  First success wins; total failure throws `ConversionFailedException` whose attempt records surface in the HTTP 422 response body. (`anonymise-output-as-pdf-by-default`)
- **Per-call `outputFormat` request field** on `POST /api/anonymization/anonymize/{fileId}` and `POST /api/anonymization/batchAnonymize/{batchId}`. Accepts `"pdf"` (default) or `"preserve"`. Per-call value overrides the tenant default. (`anonymise-output-as-pdf-by-default`)
- **Admin setting "Always export anonymised documents as PDF"** in the new Anonymisation section of the DocuDesk settings panel. Backed by the `docudesk.anonymisation.default_output_format` `IAppConfig` key. Switching off flips the tenant default to `"preserve"`; callers can still override per-request. (`anonymise-output-as-pdf-by-default`)
- **`phpoffice/phpword ^1.2`** added to `composer.json` as the engine for the in-process PhpWord conversion backend. Reuses the existing `mpdf/mpdf` dependency for PDF/A-3b emission. (`anonymise-output-as-pdf-by-default`)
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
- **Anonymise may now respond HTTP 422** when any `unredactedEntities[]` entry
  matches an active `publicationProhibition` rule (any confidence — hard gate).
- **Batch anonymise may now respond HTTP 207** on per-file prohibition
  violations; per-file details in `prohibitedEntries[]` on the file entry.
- **The anonymise endpoint now returns PDF/A-3b output by default.** Callers that need the legacy native-format behaviour must send `outputFormat: "preserve"` on the request body. Conversion failures return HTTP 422 with a structured `conversionAttempts` array — operators that previously got native-format output for unsupported types may need to install a supported Office app integration (Collabora, OnlyOffice, or Euro Office) or send `outputFormat: "preserve"`. The un-converted anonymised intermediate is best-effort rolled back on conversion failure so the operator never sees a half-finished mixed-format result. Spreadsheet and presentation formats (XLSX, ODS, PPTX, ODP) are NOT supported by the no-Office-app fallback tier — they will return 422 unless an Office app is configured. (`anonymise-output-as-pdf-by-default`)
- **Batch anonymise responses now carry per-file `conversionAttempts`** on the file's batch entry when conversion fails for that file. The batch continues with the next file rather than aborting. (`anonymise-output-as-pdf-by-default`)
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

### Behavior changes
- **Batch anonymisation outputs now land in a `<source>/anonymised/` subfolder**
  (closes #36). Previous layout (`<source>/<base>_anonymized.<ext>`) is replaced
  for batch / folder flows only. The `_anonymized` suffix is dropped from the
  destination filename; the subfolder is the signal. Single-file anonymisation
  is unchanged. The subfolder name is tenant-configurable via
  `docudesk.anonymisation.output_subfolder_name` (default `anonymised`).
- **Batch source-discovery now excludes `_anonymized`-suffixed files.** Legacy
  outputs in a source folder are not re-anonymised by an automated batch run.
  Use the per-file anonymise endpoint for files that happen to end in
  `_anonymized`.
- **`anonymizedFilePath` in API responses reflects the new subfolder location.**
  Pre-change clients reading this field work without code changes.

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

