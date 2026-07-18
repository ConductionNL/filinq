---
kind: code
tracking_issue: https://github.com/ConductionNL/docudesk/issues/315
---

# Proposal: verapdf-validation

## Why

DocuDesk claims archival conformance it never verifies. GitHub #315 (this
change's tracking issue; GitHub is primary — it mirrors the original Codeberg
#182, referenced below for the analysis history) documents both limitations
verified at HEAD:

- **Validation is shallow.** `Pdfa3ConversionService::validateOutput()`
  asserts the `%PDF-` header and the `pdfaid:part`/`pdfaid:conformance` XMP
  markers — proving the file *claims* conformance, not that it *is*
  conformant ("that we claim conformance, not that we are conformant", CB
  #182). The service docblock itself defers: "Full veraPDF-grade content
  validation is out of scope". Wave-1 `pdfua-accessible-output` deliberately
  deferred the same integration ("veraPDF-grade validation is the named
  follow-up", its design D3) — this change is that follow-up for PDF/A.
- **Font embedding is unverified.** `convertExistingPdf()` imports source
  pages as opaque XObjects and "cannot retroactively embed fonts a source
  PDF never embedded" (CB #182): a non-embedded-font PDF wrapped in an A-3
  container passes the marker check and then **fails a strict validator —
  worth knowing before an e-depot rejects a batch**. Nothing at HEAD checks
  font embedding anywhere.
- `DocumentValidationService` has ten check ids after wave 1 (six document
  checks + four `accessibility` heuristics) — none archival. The existing
  checks are deliberately parser-free heuristics; real PDF/A assurance was
  explicitly out of their scope.
- The gap is already operational: `docs/features/eml-pdf-assembly.md` tells
  admins to run `verapdf --validate-profile 3b` **by hand** to check
  DocuDesk's own output.

Archival conformance is a statutory chain: Archiefwet substitution and
e-depot ingest (Nationaal Archief / TMLO-MDTO practice) expect genuinely
valid PDF/A; ZyLAB positions PDF/A burn-in for exactly this market. A
validator-backed verdict turns "we hope the e-depot accepts it" into a
stored, per-document fact.

## What

- **veraPDF as an optional, local validator backend**: the industry
  reference PDF/A validator (PDF Association / Open Preservation
  Foundation) invoked as a local CLI — same optional-binary pattern as
  soffice and Tesseract: probed availability + version, admin-settings
  status display, config-driven path and time budget, never bundled, never
  remote. Absent binary means honest heuristic-only mode, not failure.
- **Real conformance validation** of PDF/A-1b/2b/3b: validate the flavour
  the document claims (or a requested profile), producing a
  machine-readable result (flavour, compliant, failed rules with
  clause/specification references).
- **Font-embedding verification with remediation guidance**: non-embedded
  fonts are reported per font, with guidance that distinguishes documents
  DocuDesk can fix by re-rendering (its own generation paths embed fonts)
  from imported opaque pages it cannot fix retroactively (the CB #182
  limitation — advise re-conversion from the source file).
- **A conformance report stored on the document**: a persisted, re-runnable
  `conformanceReport` OR object (flavour, verdict, failed-rule summary,
  validator version, timestamp) surfaced on the document detail view.
- **Wired into `document-validation-checks`** as a new `archival` check
  category (sibling of wave-1's `accessibility` category, same
  profile/severity mechanism) and **into `pdfa3-conversion`** as post-output
  verification: report-and-warn by default, hard-fail via the existing
  fail-loud exception when strict verification is configured.

## Capabilities

### New Capabilities

- `verapdf-validation`: the validator backend integration — probe/config,
  conformance validation contract, font-embedding verification and
  remediation guidance, persisted conformance reports.

### Modified Capabilities

- `document-validation-checks`: gains the `archival` check category
  (validator-backed check ids riding the existing catalogue/profile/severity
  mechanism, with an honest unavailable-validator finding).
- `pdfa3-conversion`: gains veraPDF output verification (report-only by
  default; strict mode raises the existing typed exception instead of
  returning unverified bytes).

## Impact

- **Backend**: new `VeraPdfService` (probe, CLI invocation, result parsing,
  time budget); `DocumentValidationService` gains the `archival` checks;
  `Pdfa3ConversionService` gains the post-output verification hook; a
  conformance endpoint on the existing validation controller surface.
- **Register JSON** (`lib/Settings/docudesk_register.json`): new
  `conformanceReport` schema (additive).
- **Frontend**: `archival` category in the findings panel (grouping shipped
  with wave 1), conformance report on document detail, remediation guidance
  strings, admin-settings validator status row.
- **Dependencies**: veraPDF is an admin-installed local binary (Java);
  DocuDesk gains **no** composer/npm dependency. Alternatives (Apache
  PDFBox preflight, JHOVE) are rejected in design.md D1.
- **Sibling boundaries**: PDF/UA (Matterhorn) validation stays out of scope
  — wave-1 `pdfua-accessible-output` heuristics are untouched; only the
  category naming is aligned (`archival` beside `accessibility`).
- **No external services**: the validator runs on the server; documents
  never leave the instance.
