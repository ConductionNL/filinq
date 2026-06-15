**status: pr-created**

## Context

`EntityRelation.bases` (OR) is the source of truth for which grondslagen were applied to each anonymised entity. Operators need two rendered views:

1. **Per-document summary page**: appended to the anonymised PDF so each document is self-documenting.
2. **Per-dossier summary PDF**: aggregates across every file in a dossier — for handing to legal / compliance, attaching to a Wet open overheid response, or filing in the dossier audit record.

Both views are PDF/A-3b for archival compliance, rendered via the existing `pdf-generation` capability's `PdfService` (Twig + mPDF). No new persistence beyond the rendered PDFs themselves and three new keys on the dossier object's `configuration` JSON.

This change owns the rendering subsystem + the per-dossier endpoint. The sibling `anonymisation-append-basis-summary-flag` exposes the per-document opt-in flag on the anonymise endpoint.

## Goals / Non-Goals

**Goals:**

- A reusable service (`GrondslagenSummaryService`) that takes (anonymised PDF, source file ID) and appends a summary page, OR takes (dossier ID) and renders a fresh per-dossier PDF.
- An on-demand endpoint that regenerates the per-dossier PDF.
- An auto-regen path that fires when `dossier.checkedOn` is updated (configurable per-dossier).
- PDF/A-3b conformance for both outputs.
- Robust failure handling: append/render failures do not corrupt the anonymised file or leave half-written PDFs.

**Non-Goals:**

- The per-document anonymise-endpoint flag (`anonymisation-append-basis-summary-flag`).
- Custom layouts / themes / logos / watermarks.
- Pagination cleverness — mPDF handles page breaks natively.
- EN templates (NL-only v1).

## Decisions

### D1. One service for both surfaces

`GrondslagenSummaryService::appendSummaryToPdf(File $anonymisedFile, int $sourceFileId): File` and `renderDossierSummary(int $dossierId): File`. Shared helpers: `resolveBaseLabels(array $baseUuids)` (placeholder string `⟨grondslag verwijderd: <short-uuid>⟩` for unresolved) and `loadAnonymisedEntitiesForFile(int $fileId)` (queries OR's `EntityRelationMapper`, filters `anonymized = true`).

### D2. Append-to-PDF uses mPDF + FPDI

Render summary via Twig + `PdfService` → temp PDF. Open anonymised PDF via mPDF + FPDI; iterate source pages with `setSourceFile` + `importPage` + `useTemplate`; append a new page with the summary content; output → replace the anonymised file in NC atomically.

**Trade-off:** Re-emitting the source PDF requires it to be PDF/A-3b compatible. Post `anonymise-output-as-pdf-by-default`, anonymised files are PDF/A-3b by default — this is the common case. For `outputFormat: "preserve"`, the fallback is a separate-PDF path (owned by the sibling flag-change).

### D3. Per-dossier destination tracks the folder-layout change

Default: `<dossier-folder>/anonymised/grondslagen.pdf` (per `anonymisation-output-folder-layout`). Fallback while that change isn't applied: `<dossier-folder>/grondslagen.pdf`. The fallback path is migrated to the canonical location once the folder-layout change lands.

### D4. Auto-regen is opt-out per dossier

`dossier.configuration.grondslagen.autoRegenOnReview` (default `true`). On `dossier.checkedOn` update, listener checks the flag and (if true) invokes `renderDossierSummary` synchronously in the same transaction. **Failure isolation:** if regen throws, log + continue — the review MUST succeed even if rendering fails.

### D5. Empty / missing-grondslag handling

Empty dossier → render a near-empty PDF (header + empty tables + zero totals) and save. Past anonymisations without `EntityRelation.bases` → "no grondslag recorded" rows in the summary; no back-fill. Operators reviewing legacy data see the gap explicitly.

### D6. Only entities released to OpenAnonymiser appear in the summary

Filter to `EntityRelation.anonymized = true` AND `bases[]` non-null. Override-released low-confidence prohibition entities (per `anonymisation-prohibition-gate`'s `acknowledgedOverrides`) are NOT listed — the report is "what was redacted under what grondslag", not "what the operator chose not to redact". Override audits are a separate concern.

## Risks / Trade-offs

- **mPDF PDF/A-3b import compatibility** → spike during apply if `setSourceFile` chokes on a specific PDF/A-3b feature. Acceptable fallback: render the summary as a separate file even in PDF mode (degraded UX, no functional gap).
- **Per-dossier regen on large dossiers** → bounded by file count × per-file `EntityRelation` rows. For typical dossiers (≤ 50 files, ≤ 200 entities each) the render is well under a second. For pathological cases (1000+ files), the synchronous path may be slow; future evolution is a background job — not in scope here.
- **Listener fires multiple times for a single review** → confirm by inspection during apply (one `dossier.checkedOn` write = one regen).

## Migration Plan

1. Land `GrondslagenSummaryService` + Twig templates.
2. Land the per-dossier endpoint + dossier `configuration.grondslagen.*` fields.
3. Land the auto-regen listener.

**Rollback:** Disable the listener; remove the endpoint route; the service stays dormant.

## Seed Data

Not applicable. Data lives on `EntityRelation` (OR) + dossier records (`add-dossier-schema`).

## Open Questions

- Should the per-dossier endpoint produce a downloadable file response or return file metadata only? Provisional: return file metadata; frontend GETs the file via the standard NC files API.
