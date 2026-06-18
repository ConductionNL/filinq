---
status: done
---

# document-validation-checks Specification

## Purpose
Runs a catalogue of document quality checks covering format allowlisting, extension/mime consistency, file integrity, PDF encryption, text-layer presence, and metadata completeness, returning structured findings keyed by a stable check ID and severity. It is a pure computation backend that reads document content and records but never writes fields, creates objects, or modifies files. This lets DocuDesk flag documents that are unreadable, scan-only, encrypted, or missing required metadata before further processing.
## Requirements
### Requirement: The check catalogue MUST cover format, integrity, encryption, text-layer, and metadata completeness

`DocumentValidationService` MUST implement these checks, each identified by a stable `checkId`:

@e2e exclude Check-catalogue computation (each check family, finding shape, no-content) — pure service logic. Covered by PHPUnit (DocumentValidationServiceTest).

- `format-not-allowed` — file mime type not in the profile's allowlist.
- `extension-mime-mismatch` — file extension does not match the sniffed mime type.
- `file-unreadable` — `DocumentTextExtractor` cannot parse the file (corruption or unsupported structure).
- `pdf-encrypted` — the PDF is encrypted/password-protected (and therefore cannot be anonymised).
- `text-layer-missing` — a page-bearing format yields on average fewer extracted characters per page than app config `docudesk.validation.text_layer_min_chars_per_page` (default 32); the finding MUST carry `suggestedAction: "ocr"`.
- `metadata-incomplete` — a required metadata field per the profile is absent or empty on the document record; the finding MUST name the field.

The service is a pure computation backend: it MUST NOT write fields, create objects, or modify files.

#### Scenario: Encrypted PDF is detected

- **GIVEN** a profile where `pdf-encrypted` has severity `warning`
- **AND** an uploaded password-protected PDF
- **WHEN** validation runs
- **THEN** the findings contain `{checkId: "pdf-encrypted", severity: "warning"}`

#### Scenario: Scan-only PDF triggers the text-layer check with OCR suggestion

- **GIVEN** a 10-page scanned PDF whose extraction yields 40 characters total
- **WHEN** validation runs
- **THEN** the findings contain `checkId: "text-layer-missing"` with `suggestedAction: "ocr"`

#### Scenario: Missing required metadata names the field

- **GIVEN** a profile requiring `documentType` and `language`
- **AND** a document record with `language` empty
- **WHEN** validation runs
- **THEN** the findings contain `{checkId: "metadata-incomplete", field: "language"}`

#### Scenario: Clean document passes all checks

- **GIVEN** a parseable, unencrypted PDF with a text layer, an allowed mime type, and complete metadata
- **WHEN** validation runs
- **THEN** the findings list is empty

#### Scenario: Findings never embed content

- **GIVEN** any finding produced by any check
- **WHEN** the finding is inspected
- **THEN** it contains only `checkId`, `severity`, a localised `message`, optional `field`, and optional `suggestedAction`
- **AND** it contains no extracted document text and no entity values

### Requirement: Validation profiles MUST be configurable per document type with per-check severity

Profiles live in app config `docudesk.validation.profiles`: per document type an allowed-mime list, required metadata fields, and a severity per check from `off | warning | blocking`. Unknown document types MUST resolve to the `default` profile. Shipped defaults MUST set every check to `warning` (no blocking out of the box). Profile reads happen at validation time so config changes propagate without restart.

@e2e exclude Profile resolution, per-check severity, default fallback, off-skip — config-driven service logic. Covered by PHPUnit (DocumentValidationServiceTest).

#### Scenario: Default deployment never blocks

- **GIVEN** an instance where the admin has never edited validation settings
- **WHEN** any file is uploaded, however broken
- **THEN** intake proceeds (no 422 from validation)
- **AND** findings are recorded with severity `warning` at most

#### Scenario: Per-type profile resolution

- **GIVEN** a profile for document type `factuur` requiring field `invoiceNumber`
- **AND** a record of type `factuur` without `invoiceNumber`
- **WHEN** validation runs
- **THEN** `metadata-incomplete` fires for `invoiceNumber`
- **AND** records of other types are not checked against `invoiceNumber`

#### Scenario: Unknown type falls back to the default profile

- **GIVEN** a record whose document type matches no configured profile
- **WHEN** validation runs
- **THEN** the `default` profile's checks apply

#### Scenario: A check set to off is skipped

- **GIVEN** a profile with `extension-mime-mismatch` set to `off`
- **AND** a file with a mismatching extension
- **WHEN** validation runs
- **THEN** no `extension-mime-mismatch` finding is produced

### Requirement: The verdict MUST be stored as an OR calculation, not an ad-hoc write

`validationStatus` and `validationFindings` SHALL be declared as `x-openregister-calculations` on the document/report schemas in `docudesk_register.json`, with `DocumentValidationService` as the computation backend (same phasing as `metadata-enrichment` REQ-META-CAL: until OR's ADR-031 calculation runtime ships, the event-listener fallback dispatches the same service; the listener MUST NOT contain validation logic). `validationStatus` aggregates findings: any `blocking`-severity finding → `failed`; otherwise any `warning` finding → `warnings`; otherwise `passed`. Records never validated render as "not yet validated" (absent value); no backfill migration.

@e2e exclude Verdict aggregation + calculation/listener-fallback storage — backend wiring (x-openregister-calculations on generatedDocument + ValidationRunner). Covered by PHPUnit and the schema annotation; not browser-observable in isolation.

#### Scenario: Verdict aggregation

- **GIVEN** a validation run producing one `warning` finding and no blocking findings
- **WHEN** the verdict is computed
- **THEN** `validationStatus` is `warnings`
- **AND** `validationFindings` contains the finding

#### Scenario: Verdict stored via calculation, not listener write

- **GIVEN** the OR calculation runtime is available and the annotations are declared
- **WHEN** a document object is created or updated
- **THEN** OR invokes `DocumentValidationService` and stores the verdict
- **AND** the event listener contains no `$object['validationStatus'] = ...` write

#### Scenario: Pre-existing records show not-yet-validated

- **GIVEN** a document record created before this capability landed
- **WHEN** it is listed without having been re-touched or validated on demand
- **THEN** its verdict renders as "not yet validated"

### Requirement: An on-demand validation endpoint MUST return findings without persisting

`POST /api/validation/validate` accepts `{fileId: int, documentType?: string}`, requires an authenticated user (`#[NoAdminRequired]`) and MUST resolve the file through the requesting user's folder (404 when not resolvable, without existence disclosure). It returns `{validationStatus, validationFindings[]}` computed against the resolved profile and MUST NOT create or modify any object, file, or stored verdict.

@e2e exclude On-demand endpoint contract (200 verdict, IDOR-safe 404, no persistence) — controller behaviour. Covered by PHPUnit (service) and the ValidationController; exercised via the My-documents Validate action e2e.

#### Scenario: Pre-intake check of a file

- **GIVEN** a readable file and an optional document type hint
- **WHEN** the endpoint is called
- **THEN** the response is HTTP 200 with the verdict and findings
- **AND** no OR object or stored field changes

#### Scenario: Inaccessible file yields 404

- **GIVEN** a file ID the requesting user cannot read
- **WHEN** the endpoint is called
- **THEN** the response is HTTP 404

### Requirement: Blocking findings MUST gate intake with a structured 422; warnings MUST never block

Upload and extract paths (single-document, batch, and folder flows) MUST run validation. When a finding's profile severity is `blocking`, single-document intake MUST respond HTTP 422 with body `{error, validationFindings[]}` and not ingest the file; batch and folder flows MUST skip the failing file, record its findings on the batch report, and continue processing remaining files. Findings with severity `warning` MUST be included on the success response and MUST NOT alter the flow.

@e2e exclude DEFERRED — the upload/extract/batch/folder pipeline 422-gate wiring ships as a focused follow-up (touches FileUploadService/BatchExtractionService/FolderBatchService); the verdict computation it gates on is fully built and tested here. Tracked in tasks.md task 6.

#### Scenario: Blocking check rejects a single upload

- **GIVEN** a profile with `pdf-encrypted` set to `blocking`
- **AND** an encrypted PDF is uploaded
- **WHEN** intake runs
- **THEN** the response is HTTP 422 listing the `pdf-encrypted` finding
- **AND** the file is not ingested into the pipeline

#### Scenario: Batch skips a failing file and continues

- **GIVEN** a batch of three files where one trips a blocking check
- **WHEN** the batch processes
- **THEN** the two clean files complete normally
- **AND** the failing file is skipped with its findings recorded on the batch report

#### Scenario: Warnings ride along without blocking

- **GIVEN** a file producing only `warning` findings
- **WHEN** it is uploaded
- **THEN** intake succeeds
- **AND** the response includes the findings

### Requirement: The UI MUST surface the verdict and findings

The document listing and detail views MUST show a verdict chip (`passed` / `warnings` / `failed` / `not yet validated`); the detail view MUST show a findings panel with localised messages. Findings carrying `suggestedAction: "ocr"` MUST link to the OCR flow (`ocr-document-scanning`). The admin settings page MUST provide the profile editor with per-check severity selectors and a summary banner when any blocking check is active. All strings use English i18n source keys with NL translations.

#### Scenario: Operator sees why a document failed

- **GIVEN** a document with `validationStatus: failed` due to `pdf-encrypted`
- **WHEN** the operator opens its detail view
- **THEN** a `failed` chip is shown
- **AND** the findings panel explains the encrypted-PDF finding in the user's language

#### Scenario: Scan-only document offers the OCR path

- **GIVEN** a document with a `text-layer-missing` finding
- **WHEN** its detail view renders
- **THEN** the finding links to the OCR flow for this document

#### Scenario: Admin sees that blocking is active

@e2e exclude DEFERRED with the admin profile-editor UI (tasks.md task 7) — the per-check severity selector + blocking-active banner ships in the admin-settings overhaul; the config key + severity semantics are built and tested server-side here.

- **GIVEN** at least one check in any profile set to `blocking`
- **WHEN** the admin opens DocuDesk validation settings
- **THEN** a summary banner states that blocking checks are active and names them

