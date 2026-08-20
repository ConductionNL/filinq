---
status: draft
---

# Anonymization — Delta for `pdf-only` Output Mode

This delta extends the existing `anonymization` capability (and the `pdf` / `preserve` modes introduced by `anonymise-output-as-pdf-by-default`) with a third `outputFormat` value, `pdf-only`, which converts the anonymised output to PDF and then deletes the native-format intermediate so the un-redactable native copy does not survive. `pdf-only` becomes the new tenant default. `pdf` and `preserve` are unchanged.

## ADDED Requirements

### Requirement: The anonymise endpoint MUST accept `outputFormat: "pdf-only"` and default to it

The anonymise endpoint and `AnonymizationService::anonymizeDocument()` MUST accept `outputFormat` value `"pdf-only"` in addition to `"pdf"` and `"preserve"`. The allowed set MUST be exactly `["pdf-only", "pdf", "preserve"]`. When `outputFormat` is omitted, the endpoint MUST use the tenant default `docudesk.anonymisation.default_output_format`, whose default value MUST be `"pdf-only"`. Any value outside the allowed set MUST be rejected with HTTP 400 whose body cites the three allowed values.

#### Scenario: Default mode is pdf-only
- **GIVEN** an anonymise request with no `outputFormat` specified
- **AND** the tenant default is unset (so it resolves to `pdf-only`)
- **WHEN** the endpoint processes a non-PDF input (e.g. DOCX)
- **THEN** the resolved mode is `pdf-only`
- **AND** the file written to Nextcloud Files is a PDF/A-3b
- **AND** no native-format anonymised intermediate remains in Nextcloud Files

#### Scenario: Invalid value is rejected with the widened allowed set
- **GIVEN** an anonymise request with `outputFormat: "rtf"`
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 400
- **AND** the body cites the allowed values: `"pdf-only"`, `"pdf"`, `"preserve"`

#### Scenario: Explicit pdf keeps the native intermediate
- **GIVEN** an anonymise request with `outputFormat: "pdf"` and a DOCX input
- **WHEN** the conversion succeeds
- **THEN** the converted PDF is written to Nextcloud Files
- **AND** the native-format anonymised intermediate is also kept (today's `pdf` behaviour, unchanged)

#### Scenario: Explicit preserve skips conversion
- **GIVEN** an anonymise request with `outputFormat: "preserve"` and a DOCX input
- **WHEN** the endpoint processes the request
- **THEN** the resulting file is the native DOCX
- **AND** no conversion is attempted and nothing is deleted

### Requirement: In `pdf-only` mode the native intermediate MUST be deleted after a successful conversion

When the resolved mode is `pdf-only` and a conversion actually occurs (the anonymised result is not already a PDF), `AnonymizationService::anonymizeDocument()` MUST, after `PdfConversionService::convertToPdf()` returns successfully, delete the native-format anonymised intermediate that was the conversion's source. The reference to that native node MUST be captured before the converted-PDF node replaces it, so the converted PDF is never the deletion target. The recorded `anonymizationLink.anonymizedFileId` MUST point at the converted PDF (no relation or schema change is required — it already does).

#### Scenario: Successful pdf-only conversion deletes the native intermediate
- **GIVEN** an anonymise request resolved to `pdf-only` with a DOCX input
- **AND** at least one conversion backend is available and capable
- **WHEN** the conversion succeeds
- **THEN** the converted PDF/A-3b is written to Nextcloud Files
- **AND** the native-format anonymised intermediate (the conversion source) is deleted
- **AND** the converted PDF is NOT deleted
- **AND** the `anonymizationLink` relation points at the converted PDF id

### Requirement: The `pdf-only` deletion MUST be best-effort and never fail the run

The native-intermediate deletion MUST mirror the existing conversion-failure rollback pattern: attempt `delete()`, catch `Throwable`, log a PII-free warning, and continue. A deletion failure MUST NOT abort the anonymise run, alter the success response, or change the recorded relation. The run has already succeeded (PDF written, relation recorded) before cleanup runs.

#### Scenario: Deletion failure is swallowed and the run still succeeds
- **GIVEN** an anonymise request resolved to `pdf-only` with a DOCX input
- **AND** the conversion succeeds but deleting the native intermediate throws (e.g. a locked or already-removed node)
- **WHEN** the cleanup runs
- **THEN** the failure is caught and logged at warning level (file id + exception class/message, no PII)
- **AND** the anonymise run still returns its success response with the PDF metadata
- **AND** the recorded `anonymizationLink` is unchanged

### Requirement: `pdf-only` MUST be a no-op delete when the result is already a PDF

When the anonymised result is already a PDF, the conversion gate (guarded by `mime !== "application/pdf"`) is skipped, so no native intermediate is created. In that case `pdf-only` MUST behave identically to `pdf`: the PDF is written, no conversion runs, and there is nothing extra to delete.

#### Scenario: Already-a-PDF input deletes nothing
- **GIVEN** an anonymise request resolved to `pdf-only` whose anonymised result is already `application/pdf`
- **WHEN** the endpoint processes the request
- **THEN** no conversion is attempted
- **AND** no extra deletion occurs (there is no native intermediate)
- **AND** the behaviour is identical to `outputFormat: "pdf"` for the same input

### Requirement: The admin settings UI MUST let the operator choose the default among all three modes

The DocuDesk admin settings panel MUST expose the tenant default `docudesk.anonymisation.default_output_format` as a control that can select any of the three values `pdf-only`, `pdf`, `preserve` (not a two-state toggle). The control MUST load the persisted value, default to `pdf-only` when unset, and persist exactly the selected value without coercing an unrecognised/ third value to one of only two. A boolean toggle is insufficient because it cannot represent or preserve `pdf-only`.

#### Scenario: Admin selects pdf-only and it persists
- **GIVEN** the admin settings panel with the Anonymisation section
- **WHEN** the operator selects the "PDF only" option and saves
- **THEN** `docudesk.anonymisation.default_output_format` is persisted as `pdf-only`
- **AND** re-opening the panel shows "PDF only" selected

#### Scenario: Saving the panel never clobbers pdf-only
- **GIVEN** the persisted default is `pdf-only`
- **WHEN** the operator opens the settings panel and saves without changing the output-format control
- **THEN** the persisted value remains `pdf-only` (it is NOT coerced to `pdf` or `preserve`)