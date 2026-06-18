---
status: done
---

# Capability — docudesk-or-adoption

@e2e exclude Backend OR-abstraction adoption: lifecycle/archival/calculation/notification schema annotations, OR object persistence, manifest version pin, OCR admin-config, Accept-Language and tenant-scope composable wiring — no navigable UI assertion. Covered by PHPUnit (schema validation, lifecycle/retention) and Vitest (composable wiring).

## Purpose

DocuDesk adopts OpenRegister's shared abstractions instead of bespoke per-app plumbing: lifecycle/archival/calculation/notification schema annotations, OR object persistence and Background Jobs, manifest version pinning, admin-config for tenant-tunable values, and Accept-Language / tenant-scope composable wiring. Custom code that duplicates these OR primitives is removed; docudesk-specific value-add (NLP/PII detection algorithms) is retained.

## Requirements

### Requirement: Lifecycle annotation backs all docudesk status fields

Every docudesk schema that today carries a `status` string field SHALL declare an
`x-openregister-lifecycle` annotation defining its state set, transition graph, and
per-transition authorization. Inline `'status' => '<literal>'` writes in service classes
SHALL be replaced by lifecycle transition API calls. The on-wire status value SHALL remain
identical to the current value to preserve API compatibility.

#### Scenario: BatchCorrespondenceJob lifecycle transition

- **GIVEN** the batch-correspondence schema declares states
  `pending`, `processing`, `success`, `error`, `completed` in
  `x-openregister-lifecycle.states`
- **WHEN** `BatchCorrespondenceJob::run()` reaches a previously-inline
  `'status' => 'processing'` write at line 111
- **THEN** the job SHALL invoke `lifecycleService->transitionTo($object, 'processing')`
- **AND** the resulting object on the wire SHALL still serialize `"status": "processing"`
- **AND** the transition SHALL be recorded in the OR audit trail.

#### Scenario: Signing-request pending state

- **GIVEN** the signing-request schema declares states
  `pending`, `signed`, `rejected`, `expired`
- **WHEN** `NativeSigningProvider::initiate()` (line 105) or
  `ValidSignProvider::initiate()` (line 114) creates a signing request
- **THEN** the provider SHALL invoke `lifecycleService->transitionTo($req, 'pending')`
  rather than `setStatus('pending')`.

#### Scenario: Lifecycle authorization is enforced

- **GIVEN** a transition is declared as `requiresRole: admin` in the lifecycle annotation
- **WHEN** a non-admin user attempts the transition via the API
- **THEN** the request SHALL be rejected with HTTP 403
- **AND** no `'status'` write SHALL reach the database.

### Requirement: Archival annotation declares Archiefwet retention per schema

Every docudesk schema that stores records subject to the Archiefwet 1995 SHALL declare
`x-openregister-archival.retention` as an ISO-8601 duration. The current
`// Archiefwet 1995 minimum 10-year retention` comment in `SigningAuditService.php`
SHALL be removed in favour of the annotation.

#### Scenario: Signing-audit retention is machine-readable

- **GIVEN** the signing-audit schema declares `x-openregister-archival.retention: P10Y`
- **WHEN** OR's archival background job runs
- **THEN** signing-audit rows older than 10 years SHALL be eligible for archival
- **AND** the comment at `lib/Service/SigningAuditService.php:7` SHALL no longer exist.

#### Scenario: Per-schema retention varies by category

- **GIVEN** the report, template, entity, batch-correspondence, and
  anonymization-result schemas each declare their own retention duration
- **WHEN** an auditor inspects the manifest
- **THEN** each retention value SHALL trace to a specific Archiefwet selectielijst category
  cited in the schema's description.

### Requirement: Calculation annotation backs computed fields

Anonymization-confidence, OCR-confidence, redaction-coverage, entity-density, classification, language-detection, and summarization outputs SHALL be declared as `x-openregister-calculations` annotations on their respective schemas. They SHALL NOT be populated by ad-hoc writes in service classes.

#### Scenario: Anonymization confidence is a calculation

- **GIVEN** the anonymization-result schema declares
  `x-openregister-calculations.anonymization_confidence`
- **WHEN** the anonymization pipeline finishes a run
- **THEN** the confidence value SHALL be derived from the calculation expression, not
  written directly by `AnonymizationService`.

### Requirement: Notification annotation backs lifecycle-driven alerts

Sign-request issued, sign-request completed, batch-correspondence finished, and anonymization-failed notifications SHALL be declared as `x-openregister-notifications` triggers keyed on lifecycle transitions. Direct `notificationManager->notify()` calls in docudesk service classes SHALL be removed.

#### Scenario: Sign-request notification fires on lifecycle transition

- **GIVEN** the signing-request schema declares
  `x-openregister-notifications` keyed on the `pending → signed` transition
- **WHEN** a signing provider transitions a request to `signed`
- **THEN** the notification SHALL fire automatically
- **AND** no direct `notificationManager->notify()` call SHALL exist in
  `lib/Service/Signing/`.

### Requirement: Document-register schemas use full JSON-schema validation

The report, template, and entity schemas in `openspec/specs/document-register/spec.md` SHALL declare full JSON-schema with `required`, `properties`, and `additionalProperties: false`. The current `properties: []` declarations SHALL be removed.

#### Scenario: Report schema validates strictly

- **GIVEN** the rewritten report schema declares `additionalProperties: false`
- **WHEN** a controller writes a row with an unknown field
- **THEN** OR's validator SHALL reject the write
- **AND** the controller SHALL NOT bypass validation by writing through a raw mapper.

### Requirement: Anonymization consumes OR primitives

`openspec/specs/anonymization/spec.md` SHALL declare consumption of OR File Attachments
and OR `TextExtractionService` for input handling. The custom file-upload + entity-extraction
pipeline SHALL be removed.

#### Scenario: Anonymization input via OR File Attachments

- **GIVEN** the anonymization spec declares OR File Attachments as the input source
- **WHEN** a user uploads a file for anonymization
- **THEN** the file SHALL be persisted as an OR file attachment, not by docudesk-specific
  storage code
- **AND** virus-scan and mime-validation hooks SHALL be inherited from OR.

### Requirement: Batch-anonymization delegates to OR Background Jobs

`openspec/specs/batch-anonymization/spec.md` SHALL declare per-file state as a child object
with its own lifecycle annotation. The current `ICache`-backed status tracking SHALL be
removed.

#### Scenario: Batch run creates per-file child objects

- **GIVEN** a batch-anonymization run is initiated
- **WHEN** the batch processor enumerates input files
- **THEN** each file SHALL produce a per-file child object with lifecycle states
  `pending → processing → success | error`
- **AND** no batch state SHALL be stored in `ICache`.

### Requirement: Tenant-tunable values move to admin-config

Hardcoded constants flagged in `.claude/audit-2026-05-03/04-hardcoded.md` SHALL be migrated
to admin-config keys. Default values SHALL equal the current hardcoded values.

#### Scenario: OCR languages are admin-config

- **GIVEN** an admin sets `docudesk.ocr.default_languages = 'nld+eng+fra'`
- **WHEN** `OcrService::run()` invokes Tesseract
- **THEN** Tesseract SHALL be invoked with `nld+eng+fra`
- **AND** the constant `DEFAULT_LANGUAGES` SHALL no longer exist in
  `lib/Service/OcrService.php`.

#### Scenario: Defaults preserve current behavior

- **GIVEN** a fresh docudesk install with no admin-config overrides
- **WHEN** any service reads a value migrated under Phase 7
- **THEN** the value SHALL equal the constant value listed in the audit report.

### Requirement: docudesk declares its manifest

docudesk SHALL ship `openspec/manifest.yaml` declaring `tier: 2`, `dependencies:
["openregister"]`, the consumed shared specs, and the minimum OR version. The
`MIN_OPENREGISTER_VERSION` constant in `SettingsService.php:61` SHALL be removed.

#### Scenario: Manifest version pin replaces source constant

- **GIVEN** `openspec/manifest.yaml` declares `dependencies.openregister.minVersion`
- **WHEN** a developer searches the docudesk codebase for `MIN_OPENREGISTER_VERSION`
- **THEN** no matches SHALL exist in `lib/`.

### Requirement: docudesk consumes shared multi-tenancy + i18n specs

docudesk SHALL consume the nc-vue `multi-tenancy-context` spec and the OR
`i18n-source-of-truth` and `i18n-api-language-negotiation` specs. It SHALL NOT
re-implement tenant scoping or translation infrastructure.

#### Scenario: Tenant scope is read from nc-vue composable

- **GIVEN** the nc-vue `multi-tenancy-context` composable is available
- **WHEN** a docudesk frontend store needs the current tenant
- **THEN** it SHALL read from `useTenantContext()` rather than computing tenant from
  user/route state.

#### Scenario: API respects Accept-Language

- **GIVEN** a client sends `Accept-Language: nl-NL` to a docudesk read endpoint
- **WHEN** the response includes a translatable field declared in i18n-source-of-truth
- **THEN** the field SHALL return the Dutch translation.
