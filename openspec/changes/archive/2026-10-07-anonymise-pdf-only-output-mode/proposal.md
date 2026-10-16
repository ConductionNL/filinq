---
kind: code
---

## Why

The completed `anonymise-output-as-pdf-by-default` change made `pdf` the default `outputFormat`: when the anonymised result is not already a PDF, Filinq converts it to PDF/A-3b via the `pdf-conversion` cascade and writes that PDF to Nextcloud Files. But the conversion step (`PdfConversionService::convertToPdf()`) returns a brand-new PDF node and leaves the native-format intermediate untouched. The result is that `pdf` mode silently leaves an orphaned native anonymised file (e.g. `report_anonymized.docx`) on disk next to the PDF.

That orphaned native file is the exact privacy hole anonymise-to-PDF was meant to close: a DOCX/ODT/RTF is trivially re-editable and carries metadata (track-changes, author, comments). Leaving it behind hands the operator (and anyone with folder access) a fully un-redactable copy of the redacted document. The `anonymizationLink` relation already points at the PDF, so the native file is not even referenced — it is pure leftover.

This change adds a third `outputFormat` value, `pdf-only`, that converts to PDF **and** removes the native intermediate, and makes it the new default. `pdf` (keep both) and `preserve` (native only) remain available for the legitimate cases that need them.

## What Changes

- **NEW value:** `outputFormat: "pdf-only"` — convert the anonymised output to PDF (same cascade as `pdf`) and then, after a successful conversion, best-effort delete the native-format intermediate so only the PDF remains.
- **MODIFIED — new default:** `pdf-only` replaces `pdf` as the tenant default (`filinq.anonymisation.default_output_format`) and as the `anonymizeDocument()` service-method default param. **BREAKING (behavioural):** existing installs that don't pass `outputFormat` and that relied on the leftover native `.docx`/`.odt` next to the PDF will silently stop getting it after upgrade. No DB migration — this is a config/behaviour default flip, handled via a release note (see Impact).
- **MODIFIED — enum widened in both controllers:** `VALID_OUTPUT_FORMATS` becomes `['pdf-only', 'pdf', 'preserve']` in `AnonymizationController` and `BatchAnonymizationController`. Invalid values still return HTTP 400.
- **No-op edge case:** when the anonymised result is already a PDF, the conversion gate is skipped (the `mime !== 'application/pdf'` guard), so there is no native intermediate to delete — `pdf-only` behaves identically to `pdf` in that case. No special-casing.
- **Best-effort deletion:** the native-intermediate delete mirrors the existing conversion-failure rollback (try / `delete()` / catch `Throwable` / log warning). A cleanup failure MUST NOT fail the anonymise run — the PDF is already written and the run has succeeded.
- **No relation / schema change:** the `anonymizationLink` object (register `document`, schema `anonymizationLink`) already stores the PDF id in `anonymizedFileId`. No OpenRegister schema, register, or seed-data change is introduced.

This extends the completed `anonymise-output-as-pdf-by-default` change (it adds the cleanup step that change's `pdf` mode deliberately did not perform). That change is completed/archived and has no open tracking issue, so this proposal narrates the relationship rather than referencing an issue number.

## Capabilities

### New Capabilities

<!-- None. -->

### Modified Capabilities

- `anonymization`: the anonymise endpoint and `AnonymizationService::anonymizeDocument()` gain a third `outputFormat` value `pdf-only` (convert to PDF and delete the native intermediate), which becomes the new default; `pdf` and `preserve` retain their current semantics.
- `batch-anonymization`: the batch anonymise endpoint accepts `pdf-only` (same three-value enum) and applies the same per-file delete-after-convert semantics.

## Impact

- **Code (filinq):**
  - `lib/Controller/AnonymizationController.php` — `VALID_OUTPUT_FORMATS` → `['pdf-only', 'pdf', 'preserve']`.
  - `lib/Controller/BatchAnonymizationController.php` — `VALID_OUTPUT_FORMATS` → `['pdf-only', 'pdf', 'preserve']`.
  - `lib/Service/SettingsService.php` — tenant default `filinq.anonymisation.default_output_format` flips from `pdf` to `pdf-only`.
  - `lib/Service/AnonymizationService.php::anonymizeDocument()` — default param `$outputFormat` flips from `'pdf'` to `'pdf-only'`; the PDF-conversion gate captures the pre-conversion native node and, after a successful `convertToPdf()` when mode is `pdf-only`, best-effort deletes it.
- **API contract:** anonymise + batch anonymise endpoints accept a new `outputFormat` value `pdf-only`; the new default is `pdf-only`. Success/422 response shapes are unchanged. Allowed values cited in the 400 body widen to include `pdf-only`.
- **Privacy / compliance:** removes the un-redacted native leftover that today survives a default `pdf` anonymise — closes the re-editability / metadata-leak hole for the default flow. Privacy-positive default.
- **Migration:** No DB migration. The default flip is a behaviour change — document it in the CHANGELOG under "Behavior changes" and in a release note: callers that need the native file kept alongside the PDF must now explicitly send `outputFormat: "pdf"`; callers that need native-only must send `outputFormat: "preserve"`. Rollback is configuration-only — set `filinq.anonymisation.default_output_format = pdf` to restore the keep-both default.
- **Tests:** unit coverage for the new delete-after-convert path (success deletes intermediate; delete failure is swallowed and logged, run still succeeds; already-a-PDF no-op leaves nothing to delete; `pdf` and `preserve` paths unchanged) and for the widened enum / new default resolution in both controllers.