## Why

Once `entity-relation-grondslagen` (OpenRegister) lands, every anonymised entity carries its legal bases (grondslagen) on the `EntityRelation` row. That data is currently invisible to operators and auditors — they can see the anonymised file but not the structured "we redacted these entities under these grondslagen" record that compliance reporting and Wet open overheid retention practice require.

This change closes that loop with two opt-in rendering features built on top of the existing `pdf-generation` Twig + mPDF subsystem:

1. **Per-document summary**: a final page appended to the anonymised PDF listing every redacted entity with its bases. Operator opts in per anonymise call. The page makes each anonymised document self-documenting — when read in isolation, the reader can see what was redacted and why.
2. **Per-dossier summary PDF**: a separate PDF that aggregates the per-document data across every file in a dossier. Useful for handing to legal / compliance reviewers, attaching to a Wet open overheid response, or filing in the dossier's audit record. Generated on demand and re-generated automatically when the dossier's `checkedOn` review timestamp is updated.

Both features only render — no new persistence, no new schemas. The data flows from `EntityRelation.bases` (OR) through the consent register's `base` schema (DocuDesk, owned by `add-dossier-schema`) to a Twig template, with a fixed PDF/A-3b output.

## What Changes

- **NEW:** `appendBasisSummary: true | false` field on the per-document anonymise endpoint payload, default `false`. When `true`, after the anonymised file has been converted to PDF (via Change A's `pdf-conversion`), DocuDesk renders a Twig basis-summary page and appends it to the anonymised PDF using mPDF's PDF-import path.
- **NEW:** When the operator anonymises with `outputFormat: "preserve"` (Change A's opt-out, native-format output) AND `appendBasisSummary: true`, the summary cannot be appended to a non-PDF file; it is saved as a separate PDF alongside the anonymised file (e.g. `foo_anonymized.docx` + `foo_anonymized_grondslagen.pdf`). Documented as the only two-file case.
- **NEW:** Endpoint `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf` generates / regenerates the per-dossier summary PDF on demand. The PDF is saved at `<dossier-folder>/anonymised/grondslagen.pdf` (per Change C's layout). Until Change C lands, the implementation falls back to `<dossier-folder>/grondslagen.pdf` and is migrated when Change C ships.
- **NEW:** When a `dossier`'s `checkedOn` timestamp is updated, the same per-dossier summary regen runs automatically as part of the review flow. Operators get a fresh report on every review.
- **NEW:** A small Twig template set ships with the change: `summary_per_doc.twig` and `summary_per_dossier.twig`. NL-only in v1; EN follows when `register-i18n` (in flight) lands.
- **NEW:** Dossier object gains a `grondslagen.lastGeneratedAt` (datetime) and `grondslagen.fileId` (FileNode reference) under its `configuration` JSON — populated by the regen path. The dossier UI reads these to badge the report as fresh / stale.
- **NO new schemas, no migrations, no DB changes.** All persistence reuses existing `EntityRelation.bases` (OR), the `base` schema (DocuDesk via `add-dossier-schema`), the dossier object's `configuration` field, and Nextcloud Files for the rendered PDFs.

### Content of the summary

Per-document page:

- Header: filename, anonymisation timestamp, operator user, anonymisation tool ("OpenAnonymiser via OpenRegister").
- Table per anonymised entity: entity text, type (PERSON/ORGANIZATION), replaced-with placeholder, bases (resolved to `base.name` from the `dossier` register).
- Footer: count of entities × count of distinct bases.

Per-dossier PDF (separate document):

- Header: dossier name, dossier description, `checkedOn` timestamp, generated-at timestamp.
- Table per document: filename, anonymised entity count, bases used (with counts).
- Table per basis: basis name, document count, total entities anonymised under that basis.
- Footer: aggregate totals.

The summary lists ONLY entities whose `EntityRelation.anonymized` is true and whose `bases[]` is non-null. Entities released via `acknowledgedOverrides` (Change `anonymisation-grondslagen-and-prohibition-gate`) are NOT listed — the report is "what was redacted under what grondslag", not "what the operator chose not to redact". Override audits are a separate concern.

### Out of scope

- **Generating summaries automatically on every per-document anonymise** — opt-in only via the per-call flag for v1. Tenant default and dossier default may be added later (noted as future evolution).
- **Custom layouts / themes / logos / watermarks** — content-faithful Twig template only.
- **Multi-page summaries that paginate at content level (e.g. table breaks)** — mPDF handles page breaks natively; no special pagination logic.
- **EN translations of the templates** — NL-only v1. EN follows `register-i18n`.
- **Summary regen on individual file anonymise within a dossier** — tied only to dossier review (`checkedOn`); per-file anonymise does not trigger dossier-summary regen. Operators regenerate via the on-demand endpoint if they want freshness mid-dossier.
- **Re-running the summary against historical anonymisations whose `EntityRelation.bases` is null** — past data without bases shows up as "no grondslag recorded" rows; we do not back-fill.
- **Separate per-document audit listing for `acknowledgedOverrides`** — out of scope. The override mechanism's own audit log (in `anonymisation-grondslagen-and-prohibition-gate`) covers that.

## Capabilities

### New Capabilities

- `anonymisation-grondslagen-summary`: the per-document summary append, the per-dossier summary endpoint + auto-regen, the Twig templates, and the configuration fields on the dossier object.

### Modified Capabilities

- `anonymization`: the per-document anonymise endpoint accepts an optional `appendBasisSummary` field; when true (and `outputFormat: "pdf"`), the response file is the anonymised PDF with a summary page appended.

## Cross-app Dependencies

- **Hard** — `openregister:entity-relation-grondslagen` — provides `EntityRelation.bases` persistence. Without it, this change's summary template has no data to render.
- **Hard** — `docudesk:add-dossier-schema` — provides the `base` schema (`bases[]` references) and the dossier `configuration.grondslagen` field used to record `fileId` / `lastGeneratedAt`.
- **Soft** — `docudesk:anonymise-output-as-pdf-by-default` — the per-document append works against PDF output. Until that lands, `outputFormat: "preserve"` paths emit a separate summary PDF instead of an appended page.
- **Soft** — `docudesk:anonymisation-output-folder-layout` — provides the `<dossier-folder>/anonymised/` destination. Without it, summaries land at `<dossier-folder>/grondslagen.pdf` and are relocated when the folder-layout change lands.

Each row MUST be tracked as a `Depends on` link from this change's GitHub issue to the target's tracking issue once both issues exist.

## Impact

- **Code (docudesk):**
  - `lib/Service/GrondslagenSummaryService.php` — NEW. Renders the per-document and per-dossier summaries, resolves base UUIDs to base.name via the dossier register, queries `EntityRelation` rows.
  - `lib/Controller/AnonymizationController.php` — accept `appendBasisSummary`; orchestrate append after Change A's conversion.
  - `lib/Controller/DossierController.php` (or extend an existing dossier controller) — endpoint `POST .../dossier/{id}/grondslagen-pdf`; hook on dossier `checkedOn` update to trigger regen.
  - `lib/Resources/templates/grondslagen/summary_per_doc.twig` and `summary_per_dossier.twig` — NEW. Twig templates rendered via the existing `PdfService`.
  - `lib/Service/AnonymizationService.php` and `BatchAnonymizeService.php` — call into `GrondslagenSummaryService::appendSummaryToPdf()` after Change A's conversion when the flag is set; in `outputFormat: "preserve"` mode, save a separate summary PDF instead.
- **API contract:**
  - Per-document anonymise endpoint: payload gains `appendBasisSummary: bool` (default false). Additive. The response is unchanged in shape (file metadata) — the bytes of the resulting file include the appended page.
  - New endpoint: `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf`. Returns the file metadata of the regenerated summary PDF.
  - Auto-regen on `checkedOn` updates is internal — no new endpoint shape.
- **Cross-app:**
  - Hard dep on `entity-relation-grondslagen` (OR) for `EntityRelation.bases` to exist.
  - Hard dep on `add-dossier-schema` (DocuDesk) for the `base` schema and the dossier object shape (specifically the `configuration` field where `grondslagen.lastGeneratedAt` and `grondslagen.fileId` are stored).
  - Soft dep on `anonymise-output-as-pdf-by-default` (DocuDesk, Change A) — the per-document append works against PDF output. When `outputFormat: "preserve"`, append falls back to a separate PDF file.
  - Soft dep on `anonymisation-output-folder-layout` (DocuDesk, Change C) for the `<dossier-folder>/anonymised/` subfolder. Until Change C lands, summaries land at `<dossier-folder>/grondslagen.pdf`; Change C migrates them.
  - Reuses the existing `pdf-generation` capability's `PdfService` for Twig + mPDF rendering — no new mPDF config, no new Twig environment.
- **Privacy / compliance:** Strengthens audit traceability (per-redaction grondslag is now visible alongside the redacted document). PDF/A-3b output for archival compliance.
- **Performance:** Per-document append: one Twig render + one mPDF write + one PDF-import-and-merge per anonymisation when the flag is set. Negligible for typical sizes; documented as a per-call cost. Per-dossier regen: one Twig render + one mPDF write per regen; runs on demand or on `checkedOn`.
- **Migration:** None. New behaviour applies only when the flag is set. Past anonymisations without bases produce "no grondslag recorded" rows in the per-dossier summary.
- **Tests:** Unit tests for `GrondslagenSummaryService` (template rendering, base resolution, empty-bases handling). Integration tests for both endpoints + the auto-regen on `checkedOn`.
