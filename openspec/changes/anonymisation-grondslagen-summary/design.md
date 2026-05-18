## Context

The grondslag-tracking pipeline has three stages: **record** (which entities were redacted under which legal bases — handled by `entity-relation-grondslagen` on the OR side, with `EntityRelation.bases` populated by DocuDesk's anonymise call via `anonymisation-grondslagen-and-prohibition-gate`), **store** (the bases live on `EntityRelation` rows in OR), and **display** (this change — render a human-readable summary that travels with the anonymised artifact).

The display side has two surfaces:

- **Per-document summary**: travels with the redacted document itself. An anonymised PDF that arrives without context is hard to audit; with a summary page appended, the document is self-documenting. Operators opt in per anonymise call.
- **Per-dossier summary**: a stand-alone deliverable for compliance reviewers. Aggregates across every file in a dossier (folder) to give a "what was redacted across this entire dossier, and why" view. Generated on demand (operator clicks "regenerate") and automatically when the dossier's review timestamp (`checkedOn`, owned by `add-dossier-schema`) is updated.

Both surfaces render through the existing `pdf-generation` capability — a sandboxed Twig environment + mPDF for HTML→PDF/A-3b. We do NOT introduce a parallel rendering subsystem.

The data flows:

```
   per-document summary:
   AnonymizationController            (catches appendBasisSummary flag)
     └─► AnonymizationService         (orchestrates: anonymise → convert (Change A) → append summary)
           └─► GrondslagenSummaryService::appendSummaryToPdf(file, fileId)
                 ├─► EntityRelationMapper::findEntitiesForFile($fileId)   → EntityRelation rows
                 ├─► resolve bases[] UUIDs → base.name via dossier register
                 ├─► render Twig template summary_per_doc.twig → HTML → PdfService::renderPdf → PDF (the summary page only)
                 └─► merge: open the anonymised PDF via mPDF setSourceFile + use FPDI to import; append the summary page; write back

   per-dossier summary:
   DossierController POST /grondslagen-pdf  OR  on dossier.checkedOn write
     └─► GrondslagenSummaryService::renderDossierSummary($dossierId)
           ├─► load all files under @self.folder for the dossier
           ├─► for each file: EntityRelationMapper::findEntitiesForFile → EntityRelation rows
           ├─► aggregate bases × files × entities
           ├─► render Twig template summary_per_dossier.twig → HTML → PdfService::renderPdf → PDF
           └─► save to <dossier-folder>/anonymised/grondslagen.pdf (or <dossier-folder>/grondslagen.pdf pre-Change-C)
                 update dossier.configuration.grondslagen.{fileId, lastGeneratedAt}
```

Both paths are synchronous in v1. A typical dossier has tens of files; rendering takes seconds.

## Goals / Non-Goals

**Goals:**

- Render a per-document summary page from `EntityRelation.bases` data and append it to the anonymised PDF. The page is content-faithful, deterministic, and PDF/A-3b-compliant.
- Render a per-dossier summary PDF that aggregates across all files in the dossier. Generated on demand or on `checkedOn` update.
- Reuse `pdf-generation`'s `PdfService` and Twig sandbox — no parallel rendering infrastructure.
- Resolve base UUIDs to human-readable names via DocuDesk's `dossier` register (`base` schema). Display NL labels in v1.
- Make the per-doc summary opt-in per-call only (no dossier-level or tenant-level default in v1 — documented as a future evolution direction).
- Keep the change additive: callers that don't set `appendBasisSummary` see identical pre-change behaviour.

**Non-Goals:**

- Render summaries automatically on every anonymise call. Per-call opt-in only.
- Display override audits (entities released via `acknowledgedOverrides`). The summary is "what was redacted under what grondslag" — clean. Override audits live in the prohibition-gate capability's audit channel.
- Custom layouts, themes, logos, watermarks. Content-faithful Twig templates only.
- EN translations. NL-only v1; EN waits on `register-i18n` (in flight).
- Re-render summaries against historical anonymisations whose `EntityRelation.bases` is null. Past data without bases shows up as "no grondslag recorded" rows.
- Backfill `EntityRelation.bases` for past anonymisations. Out of scope.
- Re-conversion / regeneration of past anonymised files to add summary pages. Out of scope.
- Async rendering. v1 is synchronous; if dossiers grow large, follow-up.

## Decisions

### D1. Reuse `pdf-generation`'s `PdfService` — no parallel renderer

The existing `PdfService` (Twig sandbox + mPDF + PDF/A-3b config, used by `print-preview`) is the right vehicle. The summary feature adds Twig template files and a service method that wires them into `PdfService::renderPdf()`. No new mPDF config, no separate Twig environment.

**Rationale:** Same renderer, same security policy, same PDF/A profile. Bug fixes propagate. ADR-012 (use existing components) extended to the rendering stack.

**Trade-off:** `PdfService` is currently tuned for the print-preview use case (full-page documents). The basis-summary template is a single-page table; it should render without changes, but a small spike at apply time confirms the sandbox doesn't block any directives the template needs.

### D2. mPDF + FPDI for the per-doc append

mPDF (already a DocuDesk dep, used by `pdf-generation`) ships with `setasign/fpdi` as its PDF-import backend. The append flow:

1. Render summary HTML via `PdfService::renderPdf()` → in-memory PDF binary (one page).
2. Open the anonymised PDF via mPDF (creating a new mPDF instance).
3. `$mpdf->setSourceFile($anonymisedPdfPath)` to register pages.
4. Iterate pages: `$mpdf->useTemplate($mpdf->importPage($n))`. Add a page break.
5. Write the summary HTML on the new page (or import the rendered summary PDF page the same way).
6. Output to the same path (or a temp path then rename).

mPDF's PDF-import path is reasonably mature; the pattern is "import every page of source, then append our content". No new dependency.

**Trade-off:** mPDF's import does not preserve all PDF features (form fields, embedded JavaScript, certain metadata). For PDF/A-3b inputs this is fine (PDF/A-3b restricts the feature set). For arbitrary input PDFs we accept some metadata loss in exchange for the predictability of mPDF rendering.

**Alternative considered:** PHP libraries like `setasign/fpdi-pdf-parser` (commercial) for cleaner pass-through. Rejected — paid; mPDF + FPDI free version covers our case.

### D3. Per-doc summary location depends on output format

Two flows:

| `outputFormat` | Behaviour when `appendBasisSummary: true` |
|---|---|
| `pdf` (default, post Change A) | Append summary page to the anonymised PDF. Result: one PDF file, last page is the summary. |
| `preserve` | Cannot append to a non-PDF file. Save the summary as a SEPARATE PDF alongside: `foo_anonymized.docx` + `foo_anonymized_grondslagen.pdf`. |

The two-file case is the only edge where the summary diverges from the anonymised document. Operators choosing `outputFormat: "preserve"` are deliberately keeping native format; the summary-as-separate-file is a coherent consequence.

### D4. Per-dossier summary location: `<dossier-folder>/anonymised/grondslagen.pdf`

Per Change C (`anonymisation-output-folder-layout`), redacted files for a dossier go in a `<dossier-folder>/anonymised/` subfolder. The summary lives alongside them: `<dossier-folder>/anonymised/grondslagen.pdf`.

**Until Change C lands**, the implementation falls back to `<dossier-folder>/grondslagen.pdf` (same folder as everything else). When Change C ships, the summary file is migrated to the subfolder. The dossier object's `configuration.grondslagen.fileId` reference makes the move transparent to consumers.

### D5. Auto-regen on `checkedOn` update

The `dossier` schema (per `add-dossier-schema`) carries `checkedOn` (datetime; updated when a privacy officer reviews the dossier). When this field is updated:

1. The mutation listener for the dossier register catches the write.
2. If `dossier.configuration.grondslagen.autoRegenOnReview` is not `false` (default true), invoke `GrondslagenSummaryService::renderDossierSummary($dossierId)`.
3. The regen runs synchronously as part of the dossier update transaction. If it fails, the error is logged but the dossier update succeeds (we don't block the review on a rendering failure).

**Rationale:** The review is the natural moment to produce the deliverable. Tying regen to it makes the report a first-class artifact of the review.

**Trade-off:** A `checkedOn` update with thousands of files takes a measurable amount of time. Acceptable for the typical case (tens of files); large-dossier installs can disable auto-regen and use the on-demand endpoint instead.

### D6. Bases vocabulary and label resolution

Resolve `EntityRelation.bases` UUIDs to human-readable names by querying DocuDesk's `dossier` register's `base` schema. The summary template displays:

- `base.name` — short label (e.g. "persoonsgegevens").
- Optional in v1: `base.description` (full Wet open overheid gloss). Whether to show the description on the summary is a template-level choice; v1 shows name only and includes a link / footnote pattern for the description (deferred — v1 omits).

**For unresolvable UUIDs** (rare — happens if a tenant deleted a custom base after anonymisation): show `"⟨grondslag verwijderd: <short-uuid>⟩"`. The audit trail still records the UUID; the summary degrades gracefully.

**For entities with `bases: null`**: show `"⟨geen grondslag vastgelegd⟩"`. Happens for past anonymisations before `entity-relation-grondslagen` landed; we don't backfill.

### D7. Per-doc opt-in only via `appendBasisSummary` flag

No dossier-level setting, no tenant-level default in v1. Every per-doc anonymise call decides explicitly. Future evolution may add a dossier `grondslagen.appendByDefault` and a tenant default, in which case the resolution order would be call → dossier → tenant — but that's a follow-up. **Documented as future evolution in the proposal so reviewers see the intent.**

**Rationale:** Start simple. The frontend team is building the review UI on top of these endpoints; pushing per-call control into the UI keeps the operator in charge. If the call-flag pattern proves too noisy in practice, we add the dossier default; if THAT proves too rigid, we add the tenant default. Each step is reversible.

### D8. Anonymised entities only — no override-audit content

The summary lists entities where `EntityRelation.anonymized = true` AND `EntityRelation.bases IS NOT NULL`. Entities released via `acknowledgedOverrides` (the prohibition gate's low-confidence-override mechanism) are NOT shown.

**Rationale:** The summary's purpose is "what was redacted under what grondslag" — the structured legal-basis record. Override audits are a separate, complementary record (handled by the prohibition-gate's audit channel) and would muddle this report.

### D9. Synchronous rendering in v1

Both the per-doc append and the per-dossier regen are synchronous. Typical sizes:

- Per-doc append: a single-page summary + small mPDF import. Sub-second on typical hardware.
- Per-dossier regen: depends on file count. 50 files takes a few seconds; 500 files takes ~30 seconds. The on-demand endpoint blocks for that long.

**Async rendering** (queue + callback) is a follow-up if/when dossier sizes grow large enough to make synchronous regen infeasible. v1 keeps the simpler model.

### D10. Dossier configuration fields

The dossier object's `configuration` JSON gains:

- `configuration.grondslagen.fileId` — Nextcloud File ID of the latest summary PDF.
- `configuration.grondslagen.lastGeneratedAt` — ISO-8601 datetime of the most recent regen.
- `configuration.grondslagen.autoRegenOnReview` — boolean, default `true`. When `false`, the `checkedOn` listener does NOT regen automatically.

The dossier UI reads these to render badges ("up to date" / "regenerating now" / "stale — regenerate?") and to provide the on-demand regen button.

**Rationale:** Storing these in `configuration` (already a free-form JSON field on the dossier object per `add-dossier-schema`) avoids adding new top-level fields. Future fields can be added without schema migrations.

## Risks / Trade-offs

- **[mPDF import fidelity]** → Mitigation per D2: accepted trade-off; PDF/A-3b inputs (the common case post Change A) are well-handled. Edge cases logged with a degraded-output warning.
- **[Synchronous regen on large dossiers]** → Mitigation: per-dossier `autoRegenOnReview` flag lets large-dossier installs disable auto-regen. Async is a follow-up.
- **[Base UUIDs unresolvable]** → Mitigation per D6: degraded label, audit UUID retained. Tenants warned via the `add-dossier-schema` immutability guidance.
- **[Templates ship NL-only]** → Mitigation: documented limitation; EN follows `register-i18n`. NL is the primary use case for the foreseeable future.
- **[Past anonymisations without bases]** → Mitigation per D6: graceful degradation with clear "no grondslag recorded" labels. No backfill, no migration.
- **[`checkedOn` listener and dossier write transaction]** → Mitigation per D5: regen failure is logged; dossier update succeeds. Worst case: stale summary, fixable with the on-demand endpoint.
- **[Cross-change ordering]** → This change has hard deps on `entity-relation-grondslagen` and `add-dossier-schema`, soft deps on `anonymise-output-as-pdf-by-default` and `anonymisation-output-folder-layout`. If apply runs out of order, the summary feature is a no-op (no bases to render) until prerequisites land. Documented in the proposal.
- **[Summary content drift]** → If the template wording is updated post-deploy, past summaries already on disk are not regenerated. Mitigation: regen is idempotent and operator-driven; large-scale re-render of historical summaries is out of scope.

## Migration Plan

1. Land `GrondslagenSummaryService` + Twig templates.
2. Land controller integration for the per-doc append.
3. Land the per-dossier endpoint + `checkedOn` listener.
4. Add the dossier `configuration.grondslagen.{fileId, lastGeneratedAt, autoRegenOnReview}` fields (no schema migration — `configuration` is a free-form JSON field).
5. Release. Operators see the new flag on per-doc anonymise; the new endpoint is available; the auto-regen kicks in on the next dossier review.

**Rollback:** disable the new code paths (a per-app feature flag if wanted, or just don't pass the flag / call the endpoint). Rendered summaries already on disk are harmless. No rollback for the dossier `configuration` fields needed (additive, ignored if the code that reads them is reverted).

## Seed Data

Not applicable — this change introduces no new schemas. It renders existing data into PDFs.

## Open Questions

- **mPDF + FPDI verified version compatibility** — confirm at apply time that the bundled FPDI in `mpdf/mpdf ^8.2` covers all the `setSourceFile` / `importPage` paths we need. If not, follow-up with a small dependency bump.
- **`base.description` on the summary template** — v1 omits it (showing name only). Worth revisiting after first deployment; if reviewers ask for the full Wet open overheid gloss inline, add it. Could be a template-level toggle: `?showDescriptions=true`.
- **Listener implementation for `checkedOn`** — the design assumes OpenRegister fires an object-changed event for dossier writes. If not, alternative: poll-based regen on first read of a stale `lastGeneratedAt` (lazy regen). Resolve at apply time by checking what events OR emits.
- **Locale resolution** — read user's NC locale; if `nl_NL`, render NL; otherwise still render NL with a footer note "EN translation forthcoming". Acceptable for v1.
