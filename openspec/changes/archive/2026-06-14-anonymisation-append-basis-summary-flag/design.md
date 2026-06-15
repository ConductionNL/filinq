status: pr-created

## Context

The summary-rendering subsystem (Twig templates, `GrondslagenSummaryService`, per-dossier endpoint, auto-regen listener) is specced + built in `anonymisation-grondslagen-summary-rendering`. This change is the small `anonymization` delta that exposes the per-document opt-in flag and orchestrates the call into the rendering service.

## Goals / Non-Goals

**Goals:**

- Add `appendBasisSummary` (optional boolean, default `false`) to the per-document anonymise endpoint payload.
- Honour the same flag on the batch anonymise endpoint (applies to every file in the batch).
- Cleanly handle the `outputFormat: "preserve"` case: render the summary as a separate `<original-base>_anonymized_grondslagen.pdf` (the rendering service knows how — this change only wires the call).
- Treat summary failure as a warning, not an error: anonymised file preserved, HTTP 200, structured `warning` field.

**Non-Goals:**

- The rendering itself — owned by `anonymisation-grondslagen-summary-rendering`.
- A standalone "render summary against an existing anonymised file" endpoint (future evolution; not v1).
- Server-side default for the flag (per-tenant default, per-dossier default) — future evolution; v1 is per-call opt-in.

## Decisions

### D1. Flag is per-call, default false

Opt-in by the operator on each call. Frontend may set it default-on per-tenant later; v1 keeps the server contract conservative.

### D2. Failure surfaces as a warning, not an error

If the summary service throws (e.g. mPDF import failure, base resolution timeout), the anonymised file MUST be preserved as-is in NC and the endpoint MUST return HTTP 200 with a structured `warning` field. The operator can re-anonymise to retry (no standalone summary-render endpoint in v1).

**Trade-off:** A silent failure (no warning at all) would be worse; an error response (4xx/5xx) would discard the anonymised file from the operator's view of "the call succeeded". Warning hits the right balance.

### D3. Batch behaviour mirrors per-file

`appendBasisSummary: true` on a batch applies to every file; per-file failures surface as per-file warnings; the batch overall succeeds even if individual summaries fail.

### D4. Files with no `EntityRelation.bases` still get a summary page

The page lists their anonymised entities with `⟨geen grondslag vastgelegd⟩` placeholder. Operators see the gap explicitly rather than silently getting no summary.

## Risks / Trade-offs

- **Ordering of summary append vs preserve-mode** → the preserve-mode separate-PDF path differs from the PDF-append path. The rendering service owns the discrimination; this change just calls and passes through.
- **Pre-change clients reading `warning`** → they ignore unknown fields; non-breaking.

## Migration Plan

1. Land the rendering service (`anonymisation-grondslagen-summary-rendering`) first or in parallel.
2. Land the flag + orchestration here.

**Rollback:** Default the flag-handler to a no-op (ignore the field). Anonymised file path unchanged.

## Seed Data

Not applicable.

## Open Questions

- Should batch responses include a per-file array of warnings or a single aggregated warning? Provisional: per-file array (mirrors how batch already reports per-file results).
