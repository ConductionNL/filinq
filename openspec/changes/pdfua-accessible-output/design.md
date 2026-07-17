# Design: pdfua-accessible-output

## Context

Verified current state (HEAD of this worktree):

- `PdfService::renderPdf()` renders Twig→HTML→**mPDF**; `pdfa: true`
  configures mPDF `PDFA`/`PDFAversion '3-B'`. mPDF cannot emit structure
  tags — its output is untagged by construction; the shipped
  `pdf-generation` spec already records "PDF accessibility (tagged PDF) not
  currently enforced".
- `LibreOfficeHeadlessBackend::convert()` (the cascade's workhorse) already
  exports `pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2` — so
  cascade output is *tagged* today, but carries no PDF/UA identifier, no
  enforced document language, and tag quality is whatever the source
  provides.
- `DocumentValidationService` (pure computation, ADR-031 calculation
  pattern) has 6 check ids with per-profile severities
  (`off|warning|blocking`), heuristic parser-free content checks
  (`/Encrypt` trailer scan, `Tj/TJ` operator counting), verdict aggregation,
  and an on-demand `POST /api/validation/validate` endpoint. Findings never
  embed content.
- Validation UI: `src/modals/ValidationResultModal.vue`, verdict chips per
  the `document-validation-checks` spec; profile editor is a spec'd
  follow-up.
- `TemplatePreviewService::preview()` renders Twig→HTML for the template
  editor.
- Publication hand-off: DocuDesk prepares documents; publication endpoints
  live in OpenCatalogi/OpenWoo (research boundary). The in-flight
  `woo-publicatie-pipeline` change owns the pipeline itself; this change
  only supplies the per-document accessibility signal it (and any future
  publisher) consults.

Statutory frame: Besluit digitale toegankelijkheid overheid → EN 301 549
§10 (documents) → WCAG 2.1 AA; PDF/UA-1 = ISO 14289-1. DocuDesk targets
**PDF/UA-1 for generated documents** and honest reporting where a backend
cannot reach it.

## Goals / Non-Goals

**Goals:**

- Generated documents (templates → PDF) can be produced tagged with correct
  language, title, and structural semantics — PDF/UA-1 as the target
  profile.
- Accessibility problems are *visible*: per-document findings in the
  existing validation surface, and a warning at publication hand-off.
- Template authors get lint feedback (alt text, heading order, language) in
  preview, where fixing is cheap.
- Honesty over box-ticking: a path that cannot produce tagged output says
  so; DocuDesk never labels an untagged PDF accessible.

**Non-Goals:**

- Not a full PDF/UA *validator*: Matterhorn-protocol/veraPDF-grade checking
  (31 checkpoints, 136 failure conditions) is out of scope; our checks are
  presence heuristics, and the docs say so. (External veraPDF integration is
  a potential follow-up, same track as the CB #182 PDF/A validation issue.)
- No remediation of *ingested* third-party PDFs (no auto-tagging of scans —
  that is an OCR + authoring problem).
- No change to anonymization outputs' pipeline (they inherit cascade tagging
  but their sources are scans/uploads; certifying their accessibility would
  be false).
- No claim of certified conformance — the UI wording is "accessibility
  checks passed", never "PDF/UA certified".

## Decisions

### D1 — Accessible generation routes through LibreOffice, not mPDF

**Chosen:** an `accessible: true` option on the generation path
(`DocumentService` options → `pdfOptions`). When set:

- **Office templates** (with `office-template-authoring` in place) and any
  path already using `PdfConversionService`: extend
  `LibreOfficeHeadlessBackend` with an accessible-export mode adding
  `PDFUACompliance=true` (and keeping `UseTaggedPDF=true`) to the filter
  options, driven by a per-call option threaded through
  `PdfConversionService::convertToPdf($source, $opts)` (the `$opts`
  parameter exists and is currently unused — verified).
- **Twig/HTML templates**: the rendered HTML is written to a temp `.html`
  file and converted via the same LO accessible mode (LO's HTML import
  preserves headings/alt/lang), **instead of** mPDF.

**Rejected alternatives:**

- *Make mPDF emit tagged PDF*: mPDF has no structure-tree support; forking
  it is out of the question.
- *Switch PDF engine to a tagged-capable HTML engine (WeasyPrint/Prince)*:
  new runtime (Python/commercial) for something LO already does; violates
  the reuse instinct and adds an ExApp for v1.
- *Post-process tagging*: auto-tagging untagged PDFs is exactly the
  low-quality box-ticking this change avoids.

Consequence (accepted): accessible rendering requires the soffice binary;
when LO is unavailable the generation MUST fail the `accessible` request
with a structured error (attempt records exist in
`ConversionFailedException`) rather than silently falling back to untagged
mPDF output. `SelectPdfVersion` interplay: PDF/A-2+ and PDF/UA can coexist;
the accessible mode keeps PDF/A when both are requested and the docs note
LO's conformance limits.

### D2 — Language and title are mandatory inputs to accessible output

PDF/UA requires a document language and title. The accessible path MUST set
`Lang` (from an explicit option, the template's language variant
(register-i18n), or the instance default locale — in that precedence) and
the document title metadata (existing `title` option / template name). A
missing resolvable language is a generation-time error for `accessible:
true`, not a silent default to nothing.

### D3 — Validation: four heuristic, parser-free accessibility checks

New check ids in `DocumentValidationService`, same catalogue/profile/
severity machinery (no new mechanism):

| checkId | Heuristic (PDF byte-level, cheap, no new deps) |
|---|---|
| `pdf-not-tagged` | no `/StructTreeRoot` reference, or `/MarkInfo` without `/Marked true` |
| `pdf-language-missing` | catalog carries no `/Lang` entry |
| `pdf-title-missing` | no XMP `dc:title`/Info `/Title` with non-empty value |
| `pdfua-identifier-missing` | XMP lacks `pdfuaid:part` (only meaningful when tagging is present; suppressed when `pdf-not-tagged` already fired) |

Style-matched to the existing `isPdfEncrypted` (`/Encrypt` scan) and
`textLayerMissing` (operator counting) heuristics: string/regex scans over
the PDF bytes, findings carry `checkId`/`severity`/`message`/`params`,
never content. All four default to `warning` in every shipped profile
(consistent with "default deployment never blocks") and are grouped as
category `accessibility` (a new optional `category` key on findings so the
UI can group — additive, existing findings default to `document`).
`aggregate()` is untouched.

**Rejected:** bundling veraPDF (JVM dependency; belongs to a dedicated
validator integration), and DOM-level PDF parsing (a real PDF parser dep for
marginal heuristic gain).

### D4 — Publication-readiness is a signal DocuDesk computes, a gate the pipeline consults

This change adds a `publicationReadiness` helper on the validation surface:
"has open `accessibility`-category findings" → the UI shows a warning on
publication-facing actions (the Woo hand-off action, when the
`woo-publicatie-pipeline` change lands, plus today's print/publish-adjacent
surfaces). The design deliberately does NOT hard-block hand-off from within
this change: severity escalation to `blocking` via the existing profile
mechanism is the admin's instrument (then the standard 422 gate of
`document-validation-checks` REQ applies). This keeps a single
gating mechanism, avoids a second policy system, and respects the sibling
boundary (OpenCatalogi/OpenWoo own publication endpoints).

### D5 — Template lint in preview: HTML-level checks at authoring time

`TemplatePreviewService` gains a lint pass over the rendered preview HTML
(and, for office templates, over the DOCX text/XML projection):

- `img` without non-empty `alt` (or DOCX drawing without `wp:docPr` descr),
- heading-order jump (`h1 → h3` without `h2`; DOCX Heading styles
  analogous),
- no language resolvable (template has no language variant and no explicit
  lang),
- `table` without `th` header cells (HTML path only).

Lint results return alongside the preview response and render as a
non-blocking checklist in the template editor. Lint NEVER blocks saving —
it is authoring guidance (the enforcement point stays document validation).

### D6 — Declarative vs imperative (ADR-031)

Imperative with justification: PDF rendering/conversion is document
generation via an external binary (listed valid exception), and the new
checks extend `DocumentValidationService`, which is already the ADR-031
**calculation** backend invoked by OR's calculation runtime / listener
fallback — the accessibility findings flow into `validationStatus`/
`validationFindings` exactly as existing checks do, with **no new storage
mechanism and no schema change** (`docudesk_register.json` untouched; the
verdict fields are already spec'd by `document-validation-checks`). No
lifecycle/notification annotations are added.

### D7 — Frontend (ADR-012)

- `ValidationResultModal` / findings panel: group by `category`, an
  `accessibility` section with localised messages; verdict chip unchanged.
- Publication-facing actions: warning banner ("dit document heeft openstaande
  toegankelijkheidsbevindingen") with a link to the findings.
- Template editor preview: lint checklist panel (`CnDataTable`-style list),
  non-blocking.
- Admin: the accessibility checks appear automatically in the (spec'd)
  profile editor since they ride the same profile config; a short
  DigiToegankelijk note is added to the validation settings section.
- All strings EN-source + NL; nldesign-theme tested (the accessibility
  feature itself must be accessible — WCAG AA on all new UI).

## OpenRegister usage (ADR-001)

No schema or register changes. Findings/verdicts persist through the
already-specified `document-validation-checks` calculation path (OR
calculation runtime / event-listener fallback invoking the same service);
on-demand endpoint stays non-persisting. Template lint is ephemeral
(preview response only). This change is code-only against
`lib/Service/DocumentValidationService.php`, `lib/Service/Conversion/`,
`PdfService`/`DocumentService`, `TemplatePreviewService`, and UI.

## Seed Data

No new schemas → no new seed objects. Test fixtures (committed under
`tests/sample-documents/`, generated content, no personal data):

- `tagged-pdfua.pdf` — minimal tagged PDF with `/StructTreeRoot`,
  `/Lang (nl-NL)`, title, and `pdfuaid:part 1` XMP (generated by LO from a
  seed ODT at fixture-build time; committed as a stable binary).
- `untagged.pdf` — mPDF-produced equivalent (fires `pdf-not-tagged`).
- `tagged-no-lang.pdf` — tagged but `/Lang` stripped.
- Seed template with a deliberate lint trap (image without alt + `h1→h3`
  jump) for the preview-lint tests, Demostad-flavoured content
  ("Besluit parkeervergunning Demostad").

## Risks / Trade-offs

- [LO's PDF/UA export quality depends on source semantics] → that is exactly
  what D5 lint + D2 mandatory language address at the controllable end (our
  templates); the docs state that conformance depends on template quality,
  and the checks report what actually shipped.
- [Heuristic checks can false-negative (tag soup passes)] → documented
  honestly: checks are presence heuristics, category label is
  "accessibility checks", not "PDF/UA certified"; veraPDF-grade validation
  is the named follow-up.
- [`accessible: true` fails when soffice is absent] → deliberate (D1) —
  fail-closed beats silently inaccessible output; the error carries the
  cascade attempt records so admins see why.
- [Twig-template HTML→LO import fidelity vs mPDF rendering differences] →
  accessible mode is opt-in per generation; visual diffs surface in
  preview, which uses the same path when `accessible` is requested.
- [Publication warning depends on a pipeline that is itself in-flight] →
  the signal is computed locally and also surfaces on existing validation
  UI; the pipeline consumes it when it lands (loose coupling by design,
  D4).

## Migration Plan

1. Validation checks land first (pure additive service change — findings
   appear on next validation, old records stay "not yet validated" per the
   existing spec).
2. LO accessible-export mode + generation option.
3. UI grouping, publication warning, template lint.
4. Rollback = revert release; no data migration in either direction (no
   schema changes).

## Open Questions

- Default language precedence when a template has multiple language
  variants and the call passes none (provisional: the variant actually
  rendered wins; error only when nothing is resolvable).
- Whether anonymization PDF outputs should *report* (not enforce)
  accessibility findings by default (provisional: yes — they run through
  validation like any document; scan-sourced files will warn, which is the
  truthful state).
