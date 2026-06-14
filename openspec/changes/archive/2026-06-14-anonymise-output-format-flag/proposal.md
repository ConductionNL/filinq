## Why

Anonymisation is a privacy operation. Today the anonymise endpoint emits whatever format OpenAnonymiser returns — DOCX in, DOCX out — which carries two real privacy risks: easy un-redaction (DOCX is trivially editable, `[PERSON_1]` can be deleted and the original re-typed) and metadata leakage (track-changes, comments, author metadata). PDF/A-3b output flattens the document and sheds the un-redaction affordances.

This change makes PDF the **default** output format for the anonymise endpoint via a new payload field `outputFormat: "pdf" | "preserve"` (default `"pdf"`). Conversion itself is delegated to the `pdf-conversion` capability (sibling change `pdf-conversion-service`). Callers needing native-format output explicitly send `outputFormat: "preserve"`.

This is the prerequisite for `anonymisation-grondslagen-summary-rendering` — once the output is reliably PDF, the basis-summary append is straightforward.

## What Changes

- **MODIFIED:** `anonymization` capability — the anonymise endpoint accepts an optional top-level `outputFormat: "pdf" | "preserve"`, default `"pdf"`. When `"pdf"`, after OR returns the anonymised file in its native format, DocuDesk invokes `PdfConversionService::convertToPdf` and replaces the file in NC. When `"preserve"`, pre-change behaviour applies.
- **MODIFIED:** Tenant default — `docudesk.anonymisation.default_output_format` (`pdf` | `preserve`, default `pdf`); per-call value overrides tenant default.
- **MODIFIED:** Conversion failures surface as **HTTP 422** with a structured body (`conversionError`); the anonymised native intermediate is rolled back (deleted from NC) so callers never see a partially-anonymised mixed-format result.
- **MODIFIED:** Batch endpoint honours the same flag with per-file outcomes — HTTP 207 multi-status when some succeed + some fail; HTTP 422 when none succeed.
- **NO breaking change for callers that don't set `outputFormat`** — they get the new PDF default. Callers needing the old behaviour explicitly send `outputFormat: "preserve"`.

### Out of scope

- The conversion service + backends themselves — `pdf-conversion-service`.
- Plain (non-PDF/A) output mode.
- Input normalisation.
- Layout customisation.
- Migration of past anonymised files.

## Capabilities

### Modified Capabilities

- `anonymization`

## Cross-app Dependencies

- **Hard** — `docudesk:pdf-conversion-service` — provides `PdfConversionService` invoked when `outputFormat: "pdf"`.

## Impact

- **Code (docudesk):** `lib/Controller/AnonymizationController.php`, `lib/Controller/BatchAnonymizationController.php`, `lib/Service/AnonymizationService.php`, `lib/Service/BatchAnonymizeService.php`, admin settings exposure of `default_output_format`.
- **API contract:** payload gains optional `outputFormat: "pdf" | "preserve"` (default `"pdf"`); response shape unchanged on success (file written to NC is now PDF when default applies); new possible HTTP 422 with `conversionError` body; batch surface may return HTTP 207.
- **Privacy / compliance:** strengthens "redacted artifact is hard to un-redact". PDF/A-3b is archival-compliant (Wet open overheid retention friendly). Default behaviour change is a privacy-positive default.
- **Migration:** None — additive default. Operators needing native-format output add `outputFormat: "preserve"`.
