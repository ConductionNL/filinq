status: pr-created

## Context

The `pdf-conversion` capability (sibling change `pdf-conversion-service`) gives DocuDesk a reusable file-to-PDF service with PDF/A-3b output. This change exposes that service through the anonymise endpoint: a small payload field flips between PDF (the new privacy-positive default) and preserve (the old behaviour). Conversion failures roll back the un-converted intermediate so callers never see a half-anonymised mixed-format result.

## Goals / Non-Goals

**Goals:**

- Add `outputFormat: "pdf" | "preserve"` (default `"pdf"`) to the per-document and batch anonymise endpoints.
- Tenant default via `docudesk.anonymisation.default_output_format`; per-call value overrides.
- Atomic file replacement on conversion success (write PDF, delete native, update extension).
- Rollback on conversion failure (delete the un-converted anonymised intermediate); convert exception → HTTP 422 with `conversionError` body.
- Batch endpoint per-file outcomes — HTTP 207 mixed; HTTP 422 all-fail.

**Non-Goals:**

- The conversion service / backends / cascade — `pdf-conversion-service`.
- Plain (non-PDF/A) output.
- Per-call PDF/A vs plain choice.
- Input format normalisation.

## Decisions

### D1. PDF is the new default

Privacy-positive default. Callers needing the old behaviour explicitly opt out via `outputFormat: "preserve"`. Documented behaviour change in CHANGELOG.

### D2. Tenant default overrides + per-call wins

Effective `outputFormat`: per-call value if present, else `IAppConfig` tenant default (`docudesk.anonymisation.default_output_format`), else `"pdf"`. Lets a tenant flip the default at install level without forcing per-call changes.

### D3. Atomic file replacement on success

Write the PDF first, then delete the native intermediate, then update the file extension. If any step throws, leave both in place and surface as a warning (anonymised file still recoverable). Real failure mode is "conversion threw" — handled by D4.

### D4. Rollback on conversion failure

On `ConversionFailedException`, delete the un-converted anonymised intermediate from NC, convert the exception into HTTP 422 with the documented `conversionError` body (carrying `getAttempts()` from the exception so the operator sees which backends were tried and why each was unavailable). NEVER leave a partially-anonymised mixed-format result.

### D5. Validation: only `"pdf"` and `"preserve"` accepted

Any other value → HTTP 400. Both forms also accepted lowercase only (no `"PDF"`, `"Preserve"`). Strict enum keeps the contract tight.

### D6. Batch endpoint per-file outcomes

Per-file conversion; per-file outcomes returned. HTTP 207 multi-status when some files succeeded and some failed; HTTP 422 when none succeeded. Mirrors how batch already reports per-file results.

## Risks / Trade-offs

- **Behaviour change for existing callers** → migrate via `outputFormat: "preserve"` explicit opt-out; CHANGELOG flags loudly.
- **Conversion latency added to anonymise path** → especially LibreOffice headless. Tenants with large batches favour Office app first (per `pdf-conversion-service` config).
- **422 instead of degraded native-format output** → operators that previously got native-format output for unsupported types now get 422 unless they install LibreOffice / Office app or send `outputFormat: "preserve"`. Documented.

## Migration Plan

1. Ensure `pdf-conversion-service` has shipped (or land alongside).
2. Add `outputFormat` validation + tenant default resolution to controllers.
3. Wire `AnonymizationService` + `BatchAnonymizeService` to invoke the conversion + atomic replacement + rollback paths.
4. CHANGELOG + admin UI for `default_output_format`.

**Rollback:** Flip the tenant default to `preserve` (single config key) and the endpoint behaves as pre-change for callers that don't pass `outputFormat`.

## Seed Data

Not applicable.

## Open Questions

- Should we expose a separate response field indicating which backend won the conversion (for operator diagnostics)? Provisional: not in v1 — keep the response shape minimal; backend choice is in server logs.
