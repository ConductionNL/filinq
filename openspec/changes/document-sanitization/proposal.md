---
kind: code
---

# Proposal: document-sanitization

## Why

Every document a municipality publishes or discloses carries hidden payload:
author names, track changes, comments, prior-save remnants, embedded objects,
scripts, XMP identity metadata. Woo publication and disclosure make that
payload public. The classic failure is a "redacted" document whose track
changes or document properties still name the case officer or the data
subject — a personal-data breach at publication time (AVG Art. 5(1)(f),
Art. 32).

Verified at HEAD:

- OpenRegister already owns a real office sanitisation engine:
  `OfficeDocumentSanitizer` (public `sanitize(int $fileId)`,
  `isSanitizable(mime)`, DOCX + ODT sanitizers) runs **unconditionally inside
  office anonymisation** and produces a rich `SanitizationReport`
  (`commentsRemoved`, `trackedChangesAccepted/Dropped`,
  `revisionAttributesStripped`, `hyperlinksFlattened`,
  `metadataFieldsScrubbed`, `customXmlPartsDropped`, `fieldCodesStripped`).
  `DocumentProcessingHandler::getLastSanitizationReport()` exposes it — and
  **DocuDesk never calls it** (zero references in lib/ and src/). The report
  of what was removed is computed, then thrown away: the orphaned-capability
  defect class.
- PDF anonymisation sanitises **metadata only** (`PdfMetadataSanitizer`:
  /Info fields + XMP identity namespaces, `[REDACTED]` sentinel) and only as
  part of the PDF text-replacement run. There is no standalone sanitisation
  pass for either format, nothing covers embedded files, JavaScript/
  OpenAction, annotations/comments in PDFs, or prior-save incremental
  remnants, and nothing runs at publication time.
- CB #155 documents the adjacent leak: the `pdf` output mode leaves the
  native intermediate — "a trivially re-editable, **metadata-carrying**,
  un-redacted copy" — next to the published PDF.

Competitors treat this as core: **Adobe Acrobat**'s "Sanitize document"
(remove hidden information: metadata, comments, embedded content, scripts)
is the reference UX, and **Redactable** markets metadata sanitisation as
part of every redaction ("removes hidden metadata" — spectr evidence). The
wave-1 pipeline makes the gap acute: `woo-publicatie-pipeline` hands
artifacts to OpenCatalogi and `document-waarmerk-certification` seals them —
a sanitized **then** sealed document is the intended end state, and today
neither step knows whether hidden data went out with the file.

## What

- A **standalone sanitization pass** for a document: strip hidden metadata
  and active/hidden content from office files (via OpenRegister's existing
  `OfficeDocumentSanitizer`) and PDFs (via an OR-side PDF sanitizer seam
  covering /Info + XMP, comments/annotations, embedded files, JavaScript /
  OpenAction / additional-actions, and prior-save remnants through a full
  re-save), producing a sanitized derivative — opt-in, never a silent
  in-place rewrite.
- A **sanitization report** of exactly what was removed (counts per
  category, never content values), persisted as an OR object and shown in
  the UI — for office anonymisation runs this surfaces the report
  OpenRegister already computes and DocuDesk discards today.
- **Opt-in sanitization of anonymisation output**: a `sanitize` flag on the
  anonymise call applies the outbound pass to the FINAL artifact (after PDF
  conversion and any grondslagen-summary append), so the published PDF is
  clean even though conversion re-introduced producer/source metadata.
- **Publication-readiness wiring**: a per-document sanitized signal that the
  wave-1 Woo hand-off consults (warning on unsanitized hand-off, same
  loose-coupling style as `pdfua-accessible-output` REQ-DDPUA-005), and a
  documented **sanitize-then-seal order** with
  `document-waarmerk-certification` (sanitising after sealing produces an
  unsealed file; the UI enforces the order with a warning).

## Capabilities

### New Capabilities

- `document-sanitization`: standalone sanitization pass (office + PDF),
  category-count sanitization report, publication-readiness signal,
  sanitize-then-seal ordering.

### Modified Capabilities

- `anonymization`: anonymisation runs persist and surface the OR
  sanitization report instead of discarding it, and the anonymise endpoint
  gains the additive opt-in `sanitize` flag for outbound-clean final
  artifacts.

## Impact

- **Backend**: new `SanitizationController` (+ routes) and
  `DocumentSanitizationService` (delegation, report persistence, ordering
  checks); `AnonymizationService` persists the existing OR report and wires
  the opt-in final-artifact pass.
- **Register JSON** (`lib/Settings/docudesk_register.json`): new
  `sanitizationRecord` schema (additive).
- **Frontend**: "Sanitize" document action, report panel, unsanitized
  hand-off warning, seal-order warning.
- **Cross-app dependency (OpenRegister)**: a standalone PDF sanitizer in the
  existing `Sanitizer` family (the office seam is already public at OR
  HEAD); filed as an OR issue + PR with fail-flagged degradation
  (office-only sanitization ships day one).
- **Sibling boundaries**: publication endpoints remain OpenCatalogi's;
  DocuDesk only computes its own hand-off signal
  (`woo-publicatie-pipeline` and `document-waarmerk-certification` specs are
  referenced, not modified).
- **No external services**: sanitisation is local byte/XML surgery, same
  posture as the existing OR sanitizers.
