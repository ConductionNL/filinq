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

## Amendment 2026-10-05: check every PDF attached to a publication, and gate on the verdict (Woo rows 15.3 and 15.7)

Woo capability rows 15.3, "A PDF is checked for accessibility when it is
uploaded", and 15.7, "The published document is in an accessible format, such
as PDF/UA". Our column reads `no` for both. 15.3: "no PDF accessibility check
on upload. openregister extracts text for search, which does not test tagging
or reading order". 15.7: "nothing checks or converts the published file's
format. opencatalogi lib/Service/QualityService.php scores DCAT metadata
quality, not document accessibility, and 15.3 is no for the same reason".
The gap register names the missing halves. 15.3: "Run the PDF/UA check
automatically when a PDF is attached to a publication (not only on filinq's
own upload), and show the verdict to the officer." 15.7: "Gate publication on
a passing PDF/UA verdict (or warn), and offer conversion to an accessible PDF
where filinq generated the document." Build plan: amend this change, wave 1,
size M.

Ruben's decision **D5** of 2026-10-05 for 15.7: the spec wins for imported
files. REQ-DDPUM-003 keeps refusing auto remediation of an imported PDF, and
generated documents get PDF/UA. So 15.7 tops out at `partial`: an imported
PDF that fails is reported and gated, never repaired. This amendment says so
in its requirements and does not soften REQ-DDPUM-003.

What `development` has today, read at f0fa284c: `lib/Service/VeraPdf/`
(`VeraPdfService`, `ConformanceService::checkFile(File $file, string $trigger)`,
`ConformanceGuidance`) from the archived `verapdf-validation`, so the shared
backend this change waits on exists. The only callers of `checkFile()` are the
validation run (`ArchivalChecks`) and the manual endpoint
(`ConformanceController`). Nothing runs a check when a file is attached to an
OpenRegister object. `PublicationReadiness::evaluate()` adds a reason for a
redacted copy that lost its structure, and none for a failed PDF/UA verdict.

What this amendment adds, on top of tasks 1 to 5 (none of which is built):

1. A check on attach. When a PDF is created or replaced inside OpenRegister's
   object folders (`Open Registers/...`), filinq queues a one-shot job that
   runs the PDF/UA check on it, whatever app attached it. The upload request
   does no validation work.
2. The verdict where the officer works. The job writes one of three system
   tags on the file: `pdfua-conform`, `pdfua-niet-conform` or
   `pdfua-niet-gecontroleerd`. OpenRegister returns a file's system tags as
   its `labels` (`FileFormattingHandler`), so opencatalogi's attachment list
   shows the verdict without opencatalogi code. The report itself stays in
   filinq's `accessibilityConformanceReport`.
3. A gate. `PublicationReadiness::evaluate()` adds a readiness reason for any
   PDF of the record without a `pdfua-conform` verdict. By default it warns,
   as REQ-DDPUA-005 says. An administrator can make it block, through the
   existing profile severity for `pdfua-conformance-failed`.
4. An accessible copy for what filinq generated. For a PDF filinq generated
   from a template, an action regenerates it through the tagged output path
   (`pdfua-accessible-output`), validates the new copy with `ua1`, and offers
   it only when it passes. An imported PDF gets no such action (D5).

Fail closed:

- A check that could not run (no veraPDF binary, a timeout, unparseable
  output) tags `pdfua-niet-gecontroleerd`, never `pdfua-conform`, and counts
  as not passed for the gate.
- A regenerated copy that still fails is discarded and the original stays.
  Nothing is labelled accessible on hope.

App absent:

- OpenRegister absent: there are no object folders; nothing is checked on
  attach. filinq's own upload check is unchanged.
- opencatalogi absent: the tags still sit on the files. Nothing else
  changes.
- veraPDF absent: every attached PDF is tagged `pdfua-niet-gecontroleerd`.

Rows: 15.3 becomes `yes` once this is built. 15.7 becomes `partial` and says
why: generated documents can be made PDF/UA, imported ones are gated and
reported but never converted (D5).

Wave 1. Implements decision D5 for 15.7. No dependency on another planned
change.
