---
kind: code
---

# Proposal: pdfua-accessible-output

## Why

Accessible PDF output is a **statutory requirement** for Dutch government —
the Besluit digitale toegankelijkheid overheid (implementing EU Directive
2016/2102) mandates EN 301 549 conformance, which for PDFs means tagged,
accessible documents (PDF/UA-1, ISO 14289-1 / WCAG 2.1 AA). It is a hard
procurement gate (Forum Standaardisatie / DigiToegankelijk), Xential
actively positions "WCAG templates" as a differentiator
(research-competitors.md), and user-wishes #15 flags it as a fully
**unspecced gap**: "hard procurement gate … 0 issues". The NC-ecosystem scan
(research-nc-ecosystem.md, gap 4) confirms **zero coverage**: no Nextcloud
app produces or validates PDF/UA — Filinq already ships PDF/A-3
(`pdfa3-conversion`, `render-pdfa`), so accessibility is the missing half of
its archival/compliance output story.

Every document Filinq generates for a citizen or publishes under Woo is in
scope of the Besluit. Verified current state: the mPDF path
(`PdfService::renderPdf`) produces **untagged** PDFs (the existing
`pdf-generation` spec itself notes "PDF accessibility (tagged PDF) not
currently enforced"); the LibreOffice headless backend already exports with
`UseTaggedPDF=true` (verified in `LibreOfficeHeadlessBackend`, filter string
`pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2`) but without
PDF/UA identification or language metadata; and
`DocumentValidationService` has six check ids, none touching accessibility.

## What Changes

- **Tagged, accessible PDF generation (PDF/UA-1 target)** from the template
  rendering pipeline: an `accessible` output option routes rendering through
  a backend capable of tagged output (LibreOffice headless with
  `PDFUACompliance` + `UseTaggedPDF`), sets document language and title
  metadata, and preserves semantic structure (heading hierarchy, alt text,
  table headers, reading order) from the template HTML/DOCX. The mPDF path
  cannot produce tagged PDFs and MUST honestly report non-conformance
  instead of silently emitting untagged output.
- **Accessibility as a new check category in `DocumentValidationService`**:
  new heuristic, parser-free check ids (`pdf-not-tagged`,
  `pdf-language-missing`, `pdf-title-missing`, `pdfua-identifier-missing`)
  reporting PDF/UA/WCAG findings per document through the existing
  profile/severity/verdict mechanism.
- **Accessibility status surfaced** in the validation UI (verdict chip +
  findings panel already spec'd by `document-validation-checks`) and as a
  **publication-readiness warning**: a document with open accessibility
  findings warns before Woo publication hand-off.
- **Template-level accessibility lint** in template preview: missing alt
  text, heading-order jumps, missing language — caught at authoring time,
  not at the published document.

## Capabilities

### New Capabilities

- `pdfua-accessible-output`: the cross-cutting accessibility surface —
  accessibility status in UI and publication-readiness, template-level
  accessibility lint in preview, and the statutory conformance framing
  (Besluit digitale toegankelijkheid / EN 301 549).

### Modified Capabilities

- `pdf-generation`: gains the accessible/tagged output requirement (backend
  routing, language/title metadata, honest conformance reporting on the
  mPDF path).
- `document-validation-checks`: gains the accessibility check category
  (new check ids in the existing catalogue/profile/severity mechanism).

## Impact

- **Backend**: `PdfService`/`DocumentService` output-option plumbing; a
  PDF/UA-capable export path on `LibreOfficeHeadlessBackend` (filter options
  extension); new accessibility checks in
  `lib/Service/DocumentValidationService.php`; template lint in
  `TemplatePreviewService`.
- **Frontend**: findings already render via `ValidationResultModal` /
  validation UI; adds the accessibility category, the publication-readiness
  warning, and lint results in template preview (ADR-012 components).
- **Config**: accessibility checks ride the existing
  `filinq.validation.profiles` severity mechanism (default `warning`,
  admin-escalatable to `blocking`).
- **No new dependencies**: heuristics are parser-free (same style as the
  existing `isPdfEncrypted`/`textLayerMissing` checks); veraPDF-grade
  external validation is explicitly out of scope (tracked separately, CB
  #182 pattern).
- **Sibling boundaries**: publication endpoints remain
  OpenCatalogi/OpenWoo's; Filinq only gates its own hand-off readiness.
