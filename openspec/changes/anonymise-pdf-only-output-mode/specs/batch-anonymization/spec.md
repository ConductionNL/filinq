---
status: draft
---

# Batch Anonymization — Delta for `pdf-only` Output Mode

This delta extends the existing `batch-anonymization` capability so the batch anonymise endpoint accepts the new `outputFormat` value `pdf-only` (same three-value enum as the single-file endpoint) and applies the same per-file delete-after-convert semantics. The batch path delegates per-file anonymisation to `AnonymizationService::anonymizeDocument()`, so the `pdf-only` behaviour and its best-effort cleanup are inherited per file.

## ADDED Requirements

### Requirement: The batch anonymise endpoint MUST accept `outputFormat: "pdf-only"` and default to it

The batch anonymise endpoint MUST accept `outputFormat` value `"pdf-only"` in addition to `"pdf"` and `"preserve"`. `BatchAnonymizationController::VALID_OUTPUT_FORMATS` MUST be exactly `["pdf-only", "pdf", "preserve"]`. When `outputFormat` is omitted, the batch resolves the tenant default `docudesk.anonymisation.default_output_format`, whose default value MUST be `"pdf-only"`. Any value outside the allowed set MUST be rejected with HTTP 400 citing the three allowed values.

#### Scenario: Batch default mode is pdf-only
- **GIVEN** a batch anonymise request with no `outputFormat`
- **AND** the tenant default resolves to `pdf-only`
- **WHEN** the batch processes non-PDF inputs
- **THEN** each resolved mode is `pdf-only`
- **AND** each converted output is a PDF/A-3b with no native intermediate left behind

#### Scenario: Batch rejects an invalid value
- **GIVEN** a batch anonymise request with `outputFormat: "rtf"`
- **WHEN** the endpoint processes the request
- **THEN** the response is HTTP 400
- **AND** the body cites the allowed values: `"pdf-only"`, `"pdf"`, `"preserve"`

### Requirement: Batch `pdf-only` MUST apply per-file delete-after-convert semantics

When the resolved mode is `pdf-only`, every file in the batch that is converted MUST have its native-format intermediate deleted after a successful conversion, per the single-file `pdf-only` requirement. Per-file deletion MUST be best-effort: a cleanup failure for one file MUST NOT fail that file's outcome nor abort the rest of the batch. Already-converted files (and their deletions) are independent across the batch.

#### Scenario: Batch pdf-only deletes each native intermediate
- **GIVEN** a batch with DOCX and ODT inputs resolved to `pdf-only`
- **AND** conversion backends are available for both
- **WHEN** the batch completes
- **THEN** both files are written as PDF/A-3b
- **AND** neither native-format intermediate remains in Nextcloud Files
- **AND** each `anonymizationLink` relation points at its converted PDF

#### Scenario: One file's cleanup failure does not affect the others
- **GIVEN** a batch resolved to `pdf-only` where one file's native-intermediate delete throws after a successful conversion
- **WHEN** the batch completes
- **THEN** the affected file still reports a successful outcome with its PDF metadata
- **AND** the delete failure is logged at warning level
- **AND** the remaining files are processed and cleaned up normally