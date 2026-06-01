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

### Behavior changes
- **Anonymise endpoint now produces PDF/A-3b output by default** (#16).
  Callers that need native-format output (DOCX, ODT, TXT, etc.) must explicitly send
  `outputFormat: "preserve"` or set the tenant default via IAppConfig.
- **Conversion failures return HTTP 422** instead of falling back to native-format output.
  Operators previously getting native-format output for unsupported file types may need to
  install LibreOffice, an Office app (Collabora/OnlyOffice), or send `outputFormat: "preserve"`.
- **Batch endpoint returns HTTP 207 Multi-Status** when some files succeed and some fail
  conversion (previously, all failures were HTTP 422).

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

