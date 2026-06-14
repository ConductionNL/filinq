## Why

The per-document anonymise endpoint needs a small opt-in field — `appendBasisSummary: true | false` — that triggers the summary-rendering subsystem owned by `anonymisation-grondslagen-summary-rendering`. Split as its own change so the `anonymization` capability delta is reviewable independently of the new capability that backs it.

## What Changes

- **MODIFIED:** `anonymization` capability — the per-document anonymise endpoint payload accepts an optional top-level `appendBasisSummary` boolean (default `false`). When `true`, after the anonymised file has been written, the controller invokes the summary-append flow from `anonymisation-grondslagen-summary-rendering`.
- **MODIFIED:** batch anonymise endpoint honours the same flag for every file in the batch.
- **MODIFIED:** Preserve-mode (non-PDF) flow: when `outputFormat: "preserve"` AND `appendBasisSummary: true`, the summary is saved as a separate `<original-base>_anonymized_grondslagen.pdf` alongside the anonymised file (response carries both file references).
- **NEW response field:** `warning` field added only on summary failure — anonymised file is preserved as-is, HTTP 200 returned, structured warning describes the failure. Pre-change callers that don't set the flag see no behaviour change and no new fields.

## Capabilities

### Modified Capabilities

- `anonymization`

## Cross-app Dependencies

- **Hard** — `docudesk:anonymisation-grondslagen-summary-rendering` — provides `GrondslagenSummaryService` invoked when the flag is set.

## Impact

- **Code (docudesk):** `lib/Controller/AnonymizationController.php`, `lib/Controller/BatchAnonymizationController.php`, `lib/Service/AnonymizationService.php`, `lib/Service/BatchAnonymizeService.php` — wire up the flag, call the summary service, surface the warning shape.
- **API contract:** payload adds optional `appendBasisSummary` (additive, non-breaking); response adds optional `warning` on summary failure (HTTP 200, anonymised file preserved).
- **Migration:** None.
