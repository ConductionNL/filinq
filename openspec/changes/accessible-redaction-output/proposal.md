---
kind: code
---

# Proposal: accessible-redaction-output

## Why

When Dutch anonymisation software redacts a PDF it frequently **strips or
mangles the document's tag structure**, silently breaking screen-reader
accessibility — and since the European Accessibility Act deadline
(**2025-06-28**, R2 C4) that is a live legal defect, not a nice-to-have. This
is the single highest-demand unspecced Filinq gap in the user-wishes
research (**demand_score 5**, the top-ranked item, R3 A/E):

- **Gebruiker Centraal** — *"zwart lakken? Vergeet de toegankelijkheid niet."*
- **DigiToegankelijk kennisbank** — "een toegankelijke pdf met zwartgelakte
  delen."
- **RVIHH** (direct quote, R3 C): *"Sommige anonimiseringssoftware lakt niet
  alleen informatie weg, maar verandert of verwijdert ook de tags in de PDF,
  waardoor deze niet meer toegankelijk is voor screenreaders."*
- Plus the DigiToegankelijk event "Zwartgelakte documenten en digitale
  toegankelijkheid" and documenten-en-toegankelijkheid.nl.

Every Woo-published or citizen-facing redacted PDF is in scope of the Besluit
digitale toegankelijkheid (EN 301 549 / WCAG 2.1 AA) — a **hard procurement
gate**. A redaction that removes PII but leaves an untagged, screen-reader-
hostile PDF fails that gate.

**Redaction is OpenRegister's engine, not Filinq's** (ADR-022). Verified at
HEAD: Filinq's `AnonymizationService` (2237 LOC) drives OR's
`TextExtractionService` + `DocumentProcessingHandler::replaceWords()` (PDF →
`PdfTextReplacer`); Filinq records each run as an `anonymizationLink` object
(verified in `lib/Settings/filinq_register.json`, schema `anonymizationLink`,
register v7.3.0). Tag preservation must therefore be **fixed in the redaction
engine (OR), consumed by Filinq** — not re-implemented as a second
Filinq-local PDF pipeline (the exact ADR-022 failure mode R4 D flags for
`image-redaction`). A parallel agent is speccing the **OR engine half** as the
`tag-preserving-redaction` change in the openregister repo; that change makes
OR's processing result carry a `structurePreservation` block. **This change is
the Filinq LEAF half**: request preservation, surface the outcome, and gate
clearance on it.

This is **distinct from the active `pdfua-accessible-output` change**, which
makes Filinq's *generated* documents (templates → mPDF/LibreOffice output)
tagged/PDF-UA. That change tags documents Filinq *creates*; this change
preserves tags in documents Filinq *redacts*. Different pipeline (generation
vs anonymisation), different engine ownership (Filinq template renderer vs OR
redaction engine), different failure mode (never-tagged vs tags-destroyed). The
boundary is stated explicitly in design.md.

## What Changes

- **Request tag preservation** on anonymisation/redaction jobs: Filinq passes
  a `preserveTags` option (default **ON for PDF**) to OR's document-processing
  engine on every redaction run and folder/batch run.
- **Consume the outcome**: Filinq reads OR's `structurePreservation` block
  (`{requested, preserved, tagCountBefore, tagCountAfter, lossReasons[]}`, the
  contract the OR `tag-preserving-redaction` change delivers) from the
  processing result.
- **Surface it** in the document report and the review UI: a clear
  "accessibility preserved / degraded" state with tag counts and loss reasons.
- **Gate clearance/publication**: when structure was lost, block or warn
  (admin-configurable, **default warn + prominent flag**) before a redacted
  document is cleared for publication — accessibility joins the existing
  prohibition/consent clearance gates.
- **Record the outcome** in the `anonymizationLink` object so the accessibility
  result is auditable per redaction run alongside the replacement counts.
- **veraPDF verification hook** (dependency, presence-gated): when the active
  `verapdf-validation` change is present, Filinq asks it to confirm the
  redacted output is genuinely tagged/valid, turning a self-reported
  `preserved: true` into a validator-backed fact — **if** that change has
  landed; otherwise the self-reported outcome stands (no duplication of the
  veraPDF integration).

## Capabilities

### New Capabilities

- `accessible-redaction-output`: tag-preserving redaction as a Filinq leaf —
  request structure preservation from OR's redaction engine (default on for
  PDF), surface the `structurePreservation` outcome in the report + review UI,
  record it on `anonymizationLink`, and gate publication clearance on preserved
  accessibility (default warn), with an optional veraPDF-backed verification
  when `verapdf-validation` is present.

### Modified Capabilities

<!-- none — the redaction engine and its structurePreservation result are owned
     by OpenRegister's tag-preserving-redaction change (dependency, consumed
     unchanged). The veraPDF integration is owned by the active
     verapdf-validation change (presence-gated dependency). This change adds no
     Filinq-local redaction or validation engine. -->

## Impact

- **Backend**: `AnonymizationService` (and the batch services
  `BatchAnonymizeService`/`FolderBatchService`) pass `preserveTags` to OR's
  processing call and read back `structurePreservation`; a small
  `RedactionAccessibilityService` maps the outcome to a report/clearance state
  and (when present) invokes the `verapdf-validation` verification.
- **Register**: `anonymizationLink` schema in `lib/Settings/filinq_register.json`
  gains a `structurePreservation` sub-object (requested, preserved,
  tagCountBefore, tagCountAfter, lossReasons[], veraPdfVerified?) with a
  register **version bump** + register-i18n tags; one seed run showing a
  preserved outcome.
- **Clearance gate**: the accessibility check is added to the existing
  publication/clearance path (alongside the prohibition gate), config
  `filinq.redaction.accessibility_gate` = `warn` (default) | `block` | `off`.
- **Frontend**: an accessibility state chip + tag-count/loss-reason detail on
  the document report and in the anonymisation review surface (manifest V2
  shell); a prominent flag when degraded. ADR-012 Cn components, NL Design
  tokens.
- **Depends on**:
  - OpenRegister `tag-preserving-redaction` (parallel, openregister repo) — the
    engine that actually preserves tags and emits `structurePreservation`.
    Filinq assumes the block shape as the contract; if a field is absent
    Filinq degrades to "unknown" (fail-safe warn), never crashes.
  - `verapdf-validation` (active) — presence-gated verification hook; consumed,
    not duplicated.
- **Boundary**: distinct from `pdfua-accessible-output` (generated templates) —
  stated in design.md; the two must not both claim redaction output.
- **GDPR/WCAG**: no entity values recorded — only tag counts and loss reasons
  (AVG Art. 5(1)(c) data minimisation); the accessibility outcome is metadata
  about the redaction, not document content.
- **Evidence**: R3 section C (5 gov sources; RVIHH direct quote that software
  strips PDF tags) + R3 E #1 (top-ranked, demand_score 5); EAA live 2025-06-28
  (R2 C4); ADR-022 consume-not-rebuild (R4 D, redaction engine = OR); HEAD
  verification of `AnonymizationService` + `anonymizationLink`.
