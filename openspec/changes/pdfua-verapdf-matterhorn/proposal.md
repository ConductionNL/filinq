---
kind: code
depends_on: [pdfua-accessible-output, verapdf-validation]
---

# Proposal: pdfua-verapdf-matterhorn

## Why

Wave-1 `pdfua-accessible-output` made accessibility *visible* but only
*heuristically*: `DocumentValidationService` gained parser-free presence
checks (`pdf-not-tagged`, `pdf-language-missing`, `pdf-title-missing`,
`pdfua-identifier-missing`) that answer "does this PDF look tagged?" — not
"does this PDF actually conform to PDF/UA-1?". Its own spec is explicit that
these are "accessibility presence heuristics, not certified PDF/UA
(Matterhorn/veraPDF-grade) validation" (REQ-DDPUA-003), and its Impact
section defers veraPDF-grade validation to a separate change. This is that
change.

The heuristic floor cannot detect the failures that actually fail an audit: a
PDF can carry `/StructTreeRoot`, `/Marked true`, `/Lang`, a title and
`pdfuaid:part` and still violate PDF/UA-1 (ISO 14289-1) — untagged content
inside the structure tree, figures without alternative descriptions, headings
that don't map to real structure elements, tables without proper header
associations, an incomplete role map. The **Matterhorn Protocol** (the PDF
Association's PDF/UA test suite: 31 checkpoints, 136 failure conditions) is
the accepted way to test conformance, and **veraPDF** — which Filinq is
already adopting for PDF/A in `verapdf-validation` — implements it as its
`ua1` validation flavour.

Statutory frame unchanged: the Besluit digitale toegankelijkheid overheid
(EU Directive 2016/2102 → EN 301 549 → WCAG 2.1 AA) makes accessible PDFs a
hard procurement gate for every document a Dutch government body publishes
under Woo or sends to a citizen. A heuristic "looks tagged" verdict is not
evidence; a Matterhorn verdict is.

Verified at merged HEAD (`development`): `DocumentValidationService` ships six
content checks (`format-not-allowed`, `extension-mime-mismatch`,
`file-unreadable`, `pdf-encrypted`, `text-layer-missing`,
`metadata-incomplete`) — the wave-1 `accessibility` category and the
`verapdf-validation` `archival` category are both spec'd but not yet applied,
and no `VeraPdfService` exists yet. This change is a follow-up that lands on
top of both: it reuses (does not duplicate) the optional veraPDF binary
integration that `verapdf-validation` introduces, and it upgrades the wave-1
accessibility heuristics with validator truth when that binary is present.

## What Changes

- **PDF/UA-1 (Matterhorn) validation through the shared veraPDF backend**: the
  `VeraPdfService` integration introduced by `verapdf-validation` (probed,
  admin-installed local CLI binary, `filinq.verapdf.*` config, honest
  degradation) is EXTENDED with a PDF/UA validation path (veraPDF `ua1`
  flavour). No second binary, no second probe, no second admin status row —
  one validator integration serves both PDF/A (archival) and PDF/UA
  (accessibility). This change does not re-specify that backend; it consumes
  and extends it.
- **Validator-backed accessibility checks** in `DocumentValidationService`:
  new check ids (`pdfua-conformance-failed`, `accessibility-validator-unavailable`)
  in the existing `accessibility` category (wave-1 REQ-DDPUA-003), riding the
  same profile/severity/verdict mechanism, validator-presence-gated and
  per-profile opt-in (default `off`) — exactly the pattern
  `verapdf-validation` established for its `archival` checks, so heuristic and
  validator findings coexist and the UI labels the source.
- **A persisted, re-runnable accessibility conformance report** per document
  (`accessibilityConformanceReport`, keyed by `fileId`): the PDF/UA flavour,
  the verdict, the failed Matterhorn checkpoints/clauses (references only,
  never document content), the validator version. Sibling of
  `verapdf-validation`'s `conformanceReport` (PDF/A); the two are kept
  separate so a PDF/A report is not overwritten by a PDF/UA run and vice
  versa.
- **Honest remediation guidance** derived from the failure shape: documents
  produced by Filinq's own tagged-output path advise regeneration through
  Filinq with the accessible option; imported/uploaded PDFs advise
  re-authoring from an accessible source and state honestly that Filinq
  does not retag imported pages. The report and UI MUST NOT claim PDF/UA
  certification — they report a conformance verdict with references.
- **Local processing only**: veraPDF runs on the instance; document bytes
  never leave it — same posture as the PDF/A integration and the wave-1
  heuristics.

## Capabilities

### New Capabilities

- `pdfua-verapdf-matterhorn`: validator-backed PDF/UA-1 (Matterhorn)
  conformance validation via the shared veraPDF backend, a persisted
  accessibility conformance report, and honest remediation guidance —
  upgrading the wave-1 heuristic accessibility surface from "looks tagged" to
  "verified conformant".

### Modified Capabilities

- `document-validation-checks`: gains the validator-backed accessibility
  check ids in the existing `accessibility` category (additive; no change to
  the profiles-defaults requirement, which `verapdf-validation` already
  amends for validator-backed checks).

## Impact

- **Backend**: extend `VeraPdfService` (from `verapdf-validation`) with a
  `validateUa()` path (veraPDF `--flavour ua1`); new
  `accessibility`-category validator checks in
  `lib/Service/DocumentValidationService.php`; a new accessibility
  conformance endpoint + report persistence.
- **Register JSON** (`lib/Settings/filinq_register.json`): new
  `accessibilityConformanceReport` schema (additive, union-merge).
- **Frontend**: the `accessibility` findings group (wave-1) gains
  validator-backed findings labelled as such; a PDF/UA conformance card on
  document detail (flavour, verdict, failed checkpoints, guidance); no new
  admin status row (reuses the veraPDF row from `verapdf-validation`).
- **Config**: PDF/UA checks ride the existing
  `filinq.validation.profiles` per-check severity mechanism and the
  existing `filinq.verapdf.*` binary config; validator-backed checks
  default `off` (admin opt-in), consistent with `archival` checks.
- **Relationship to `verapdf-validation`**: hard build dependency on its
  `VeraPdfService` and admin/probe plumbing (shared integration). Modelled as
  a sibling because the veraPDF backend is co-owned; `depends_on` is declared
  on `pdfua-accessible-output` (whose heuristics this upgrades) — see design
  for the sequencing note.
- **Sibling boundaries**: publication endpoints remain OpenCatalogi/OpenWoo's;
  Filinq only reports conformance and gates its own hand-off readiness via
  the wave-1 publication-readiness signal (REQ-DDPUA-005, referenced not
  modified).
- **No new dependencies**: no second binary; veraPDF is the same Java CLI
  `verapdf-validation` already integrates.
