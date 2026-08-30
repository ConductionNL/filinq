# Design: document-sanitization

## Context

Verified at HEAD (worktree `spec/market-gap-wave2-2026-07`, includes the nine
wave-1 changes):

- OR `OfficeDocumentSanitizer` (lib/Service/File/) is public
  (`sanitize(int $fileId): SanitizationResult`, `isSanitizable(string
  $mimeType)`), backed by `DocxSanitizer`/`OdtSanitizer` behind
  `SanitizerInterface`, throwing `SanitizationException`
  (`REASON_ENCRYPTED` caller-correctable). It runs unconditionally inside
  `DocumentProcessingHandler::anonymizeSanitizableDocument()` and fills
  `SanitizationReport` (8 count fields + sentinel). OR's own canonical spec
  `office-document-sanitization` covers the engine.
- `DocumentProcessingHandler::getLastSanitizationReport(): ?SanitizationReport`
  is the read seam. Filinq has **zero** callers — the report never reaches
  a Filinq record, response, or UI.
- OR `PdfMetadataSanitizer` strips a fixed /Info field list and XMP identity
  namespaces, but only inside `replaceWordsInPdfDocument()` (the PDF
  anonymise run). It takes a SAPP `PDFDoc`, not a file — there is no
  standalone PDF sanitisation entry point, and no PDF coverage for
  annotations/comments, embedded files, JavaScript/OpenAction/AA, or
  prior-save incremental remnants.
- Filinq `AnonymizationService::anonymizeDocument()` (~line 404): OR
  `FileService::anonymizeDocument()` → optional grondslagen-summary append →
  `outputFormat: "pdf"` gate through `PdfConversionService::convertToPdf()`
  (LibreOffice cascade). Conversion produces a FRESH PDF whose metadata the
  sanitised source never saw (producer strings, title from source
  properties) — sanitising before conversion is not enough for the final
  artifact.
- Wave-1 integration points: `woo-publicatie-pipeline` REQ-DDWPP-002
  computes readiness verdicts and REQ-DDWPP-005 hands off to OpenCatalogi;
  `document-waarmerk-certification` REQ-DDWMK-002 seals a PDF by appending a
  stamp page and CMS-signing the artifact hash — any byte change after
  sealing (including sanitisation) breaks verification by design.
- CB #155 (`anonymise-pdf-only-output-mode`) is the sibling change killing
  the unsanitised native intermediate; this change handles what is INSIDE
  the artifact that ships.

## Goals / Non-Goals

**Goals:**

- One opt-in sanitization pass usable standalone and as a final step of
  anonymisation output.
- Full hidden-payload scope for PDFs (metadata, annotations, embedded
  files, scripts, prior-save remnants) and the already-shipped office scope.
- A persisted, content-free report of what was removed, surfaced in the UI.
- A sanitized signal for publication hand-off and a correct order with
  sealing.

**Non-Goals:**

- No re-implementation of the office sanitizer — OR owns it (ADR-022); this
  change consumes and surfaces it.
- No content redaction — sanitisation removes *hidden/active* payload;
  visible PII is the anonymisation pipeline's job (and `image-redaction`'s
  for pixels).
- No hard publication gate in this change — the hand-off warning follows the
  `pdfua-accessible-output` D4 single-gating-mechanism decision; blocking
  stays with the wave-1 pipeline's readiness lifecycle.
- No change to `document-waarmerk-certification` semantics — ordering is
  enforced on the Filinq action surface, the seal spec stays untouched.
- No sanitisation of e-mail containers (EML) — `EmlPdfAssemblyService`
  output is generated PDF and rides the PDF pass.

## Decisions

### D1 — Engines stay in OpenRegister; Filinq surfaces, persists, orders

The office engine already lives OR-side; splitting PDF sanitisation into
Filinq would fork the engine family. **Decision:** extend OR's `Sanitizer`
family with a standalone PDF sanitizer (SAPP-based, same stack as
`PdfTextReplacer`/`PdfMetadataSanitizer`) exposed like
`OfficeDocumentSanitizer::sanitize(int $fileId)`, and reuse the office seam
as-is. Filinq ships a thin `DocumentSanitizationService` that routes by
MIME, persists the report, and owns ordering/warnings. Filed as an OR issue +
PR (cross-app dependency). **Degraded behaviour until the PDF seam lands
(fail-flagged, never fail-silent):** office files sanitise fully day one;
a PDF sanitisation request returns/records `sanitizationSkipped` with reason
`pdf_sanitizer_unavailable` — never a success claim from the metadata-only
anonymise-internal path. Rejected: Filinq-side PDF surgery via its own
SAPP/FPDI code — duplicates OR's PDF byte stack and its encrypted-PDF
handling (ADR-011/ADR-022).

### D2 — PDF sanitization scope: remove, don't mask

The PDF pass MUST cover, in one full re-save (which by construction drops
prior-save incremental remnants and orphaned objects):

| Payload | Action |
|---|---|
| /Info dictionary identity fields + XMP identity namespaces | strip (existing `PdfMetadataSanitizer` field/namespace lists) |
| Comments/annotations (except form fields being flattened) | remove |
| Embedded files (`/EmbeddedFiles`, `/Filespec`) | remove — with a documented exception for declared PDF/A-3 archival attachments (see Open Questions) |
| JavaScript (`/JavaScript` name tree, `/JS`), `/OpenAction`, `/AA` | remove |
| Prior-save remnants / incremental updates | eliminated by full re-serialisation |

The sanitized derivative is a NEW file (`<name>_sanitized.<ext>` beside the
source, anonymise-convention); sanitisation never silently rewrites the
source. Encrypted PDFs fail closed with the caller-correctable reason
(mirrors `SanitizationException::REASON_ENCRYPTED` and the
`pdf-encrypted` validation check).

### D3 — Report persistence: a new `sanitizationRecord` OR object

The report needs to outlive the run (publication signal, audit, UI), for
BOTH standalone runs and anonymise-embedded runs. `anonymizationLink` is the
wrong home (standalone sanitisation has no anonymisation run).
**Decision:** new `sanitizationRecord` schema in
`lib/Settings/filinq_register.json`: `fileId`, `sanitizedFileId`,
`trigger` (`manual` | `anonymisation` | `publication`), `engine`,
`report` (the category-count object, mirroring OR's `SanitizationReport`
JSON shape plus the PDF categories), `sanitizedAt`, `sanitizedBy`. Counts
and category names only — never removed content, author names, or comment
text (AVG Art. 5(1)(c) data minimisation: the report proves cleaning
happened without re-storing what was cleaned). GDPR note: the record is
processing evidence under Art. 5(2) accountability, same framing as
`anonymizationLink`.

### D4 — Anonymisation wiring: surface always, sanitize-final opt-in

Two distinct legs:

1. **Surface (always):** office anonymisation already sanitises; the run's
   `SanitizationReport` (via `getLastSanitizationReport()`) is persisted as
   a `sanitizationRecord` with `trigger: anonymisation` and shown with the
   run result. This is pure consumption of an existing, currently-orphaned
   OR capability — no behaviour change to anonymisation itself.
2. **Final-artifact pass (opt-in):** the anonymise endpoint gains an
   additive `sanitize: true` flag. When set, the outbound pass (D1/D2) runs
   on the FINAL artifact — after the `outputFormat` conversion gate and any
   grondslagen-summary append — because LibreOffice conversion mints fresh
   metadata the earlier sanitisation never saw. Default `false` preserves
   pre-change behaviour byte-for-byte (additive and non-breaking, same
   contract style as `outputFormat`/`appendBasisSummary`). Tenant default
   via `filinq.sanitization.default` app config, mirroring the
   `outputFormat` tenant-default mechanism.

### D5 — Publication readiness and seal ordering

- **Readiness signal:** a document is "sanitized" when a
  `sanitizationRecord` exists whose `sanitizedFileId` matches the artifact
  being handed off. The wave-1 Woo hand-off UI consults the signal and
  warns on unsanitized hand-off; it does NOT hard-block (single gating
  mechanism stays the pipeline's readiness lifecycle, `pdfua` D4
  precedent). The signal is computed Filinq-side; the
  `woo-publicatie-pipeline` spec is not modified.
- **Order with sealing:** sanitize → seal, never the reverse. Sealing
  (REQ-DDWMK-002) hashes and CMS-signs the artifact; any later byte change
  — including sanitisation — makes verification fail (correctly). The
  sanitize action on an already-sealed artifact MUST warn that the output
  will be unsealed and the seal must be re-applied; the seal action surfaces
  the sanitized signal so operators seal the clean file. The target end
  state for publication is **sanitized + sealed**.

### D6 — Declarative vs imperative (ADR-031)

`sanitizationRecord` is a **declarative** register addition (no lifecycle
annotation — single-state evidence record, like `anonymizationLink`). The
pass itself, report persistence, and ordering checks are **imperative** —
document processing via engine invocation, a valid ADR-031 exception
category. No aggregations/calculations/notifications dialects are involved.

### D7 — OpenRegister usage (ADR-001) and frontend (ADR-012)

OR services consumed: `OfficeDocumentSanitizer` (existing, office pass),
the new PDF sanitizer seam (D1), `DocumentProcessingHandler::
getLastSanitizationReport()` (anonymise-run report), ObjectService/AppHost
persistence for `sanitizationRecord` (no custom tables). ADR-011: no new
parsing/validation utilities — PDF byte work stays OR-side. Frontend:
"Sanitize" action in MyDocuments/file viewer (same action-menu pattern as
Run OCR), report panel modal in `src/modals/` (modal-isolation gate),
warnings via NC components; NL Design System tokens (ADR-003); strings
EN-keyed with NL translations (ADR-005).

## Seed Data

```json
// sanitizationRecord — a Demostad besluit sanitized before Woo publication
{ "fileId": 813001,
  "sanitizedFileId": 813442,
  "trigger": "publication",
  "engine": "OfficeDocumentSanitizer",
  "report": {
    "commentsRemoved": 4,
    "trackedChangesAccepted": 11,
    "trackedChangesDropped": 2,
    "revisionAttributesStripped": 57,
    "hyperlinksFlattened": 3,
    "metadataFieldsScrubbed": 6,
    "customXmlPartsDropped": 1,
    "fieldCodesStripped": 2 },
  "sanitizedAt": "2026-07-17T09:30:00Z",
  "sanitizedBy": "w.devries" }

// sanitizationRecord — PDF pass on an anonymised output (sanitize: true)
{ "fileId": 813442,
  "sanitizedFileId": 813443,
  "trigger": "anonymisation",
  "engine": "PdfSanitizer",
  "report": {
    "metadataFieldsScrubbed": 5,
    "xmpNamespacesStripped": 3,
    "annotationsRemoved": 1,
    "embeddedFilesRemoved": 0,
    "scriptsRemoved": 0,
    "resaved": true },
  "sanitizedAt": "2026-07-17T09:31:12Z",
  "sanitizedBy": "w.devries" }
```

Seed task: one DOCX sample with comments + track changes + author metadata
("Concept besluit Demostad", tests/sample-documents) so the report panel
shows non-zero counts on a clean install.

## Risks / Trade-offs

- [OR PDF sanitizer seam may lag] → D1 office-first shipping with
  fail-flagged PDF degradation; the Filinq surface (records, report panel,
  anonymise-report persistence, warnings) is independently shippable and
  live-testable with office files.
- [Sanitisation can break intended behaviour (form fields, legitimate
  attachments)] → the pass is opt-in and produces a derivative; the report
  names every removal so the operator can re-run without publication; the
  PDF/A-3 attachment exception is an explicit open question, not a silent
  choice.
- [Report drift between OR's `SanitizationReport` shape and the persisted
  record] → the record stores OR's `jsonSerialize()` output verbatim under
  `report`; a unit test pins the field set against OR HEAD (register-drift
  pin pattern from `portal-contribution`).
- [Operators sanitize after sealing and "lose" the seal] → D5 ordering
  warning on both actions; docs describe sanitize→seal as the publication
  recipe.
- [Double sanitisation (office pass, then convert, then PDF pass) seems
  redundant] → deliberate: conversion mints new metadata; the final pass is
  the one that guarantees the shipped bytes (scenario-tested on the
  converted artifact).

## Migration Plan

1. Register JSON: add `sanitizationRecord` (additive, union-merge).
2. Persist + surface the anonymise-run office report (pure consumption; no
   behaviour change).
3. Standalone sanitize action + records + report panel (office day one).
4. OR issue + PR for the PDF sanitizer; flip PDF degradation off when live;
   enable the `sanitize` final-artifact flag end-to-end.
5. Rollback: the action and flag are opt-in; without them behaviour is
   pre-change. Records are additive evidence; no data migration to unwind.

## Open Questions

- PDF/A-3 archival attachments (`pdfa3-conversion` embeds MDTO sidecars by
  design): should the PDF pass preserve declared `/AFRelationship` archival
  attachments by default, or strip all and require re-embedding?
  Provisional: preserve attachments carrying `/AFRelationship` `Source`/
  `Data` when the document identifies as PDF/A-3, remove everything else;
  the report lists preserved attachments by name.
- Should `trigger: publication` runs be recorded on the publication record
  (wave-1 `publicationRecord`) as well? Provisional: no — the pipeline reads
  the signal; duplicating evidence rows invites drift.
