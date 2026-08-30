# Design: pdfua-verapdf-matterhorn

## Context

Verified at merged HEAD (`development`, includes the nine wave-1 changes and
Robert's PR #314):

- `DocumentValidationService` owns a stable check-id catalogue, per-profile
  severities (`off|warning|blocking`), parser-free byte heuristics, verdict
  aggregation, and an on-demand endpoint. At HEAD it has six checks; the
  `accessibility` category (`pdf-not-tagged`, `pdf-language-missing`,
  `pdf-title-missing`, `pdfua-identifier-missing`) is spec'd by wave-1
  `pdfua-accessible-output` (REQ-DDPUA-003) but not yet applied, and the
  `category` finding key it introduces (default `document`) is the extension
  point this change reuses.
- `verapdf-validation` (spec'd, not yet applied) introduces `VeraPdfService`
  — veraPDF as an optional, probed, local CLI binary (`filinq.verapdf.*`
  config; admin-settings status row next to soffice/Tesseract), a validate
  contract that returns rule-level results, a `conformanceReport` OR object,
  and the `archival` check category with the validator-presence-gated,
  per-profile-opt-in (`off` default) pattern. That change also MODIFIES the
  profiles-defaults requirement to make validator-backed checks default off.
- veraPDF validates PDF/UA-1 natively via its `ua1` flavour (the Matterhorn
  Protocol test suite); the same binary and JSON output format `VeraPdfService`
  already parses for PDF/A carries PDF/UA results.
- Wave-1 heuristics answer "looks tagged"; the Matterhorn Protocol answers
  "conforms" — the two are complementary, not redundant (a heuristic pass can
  precede a Matterhorn fail).

## Goals / Non-Goals

**Goals:**

- Real PDF/UA-1 conformance verdicts, locally computed via the shared veraPDF
  binary, stored per document and reachable through the existing validation
  surface.
- Upgrade the wave-1 accessibility heuristics from presence-guessing to
  validator truth when the binary is present, without regressing the
  always-available heuristic floor when it is absent.
- Honest, actionable remediation guidance; never a certification claim.

**Non-Goals:**

- No second validator binary or probe — this change consumes
  `verapdf-validation`'s `VeraPdfService`; it does not re-specify the backend
  integration, config, or admin status row.
- No PDF/UA *remediation engine* — this change verifies and guides;
  retagging/re-authoring stays with the generation paths and the source
  document. No auto-retag of imported pages (the PDF/A font-embedding
  limitation has an accessibility analogue).
- No re-modelling of the wave-1 heuristic checks — they stay exactly as
  `pdfua-accessible-output` specs them; this change ADDS validator-backed
  siblings alongside them.
- No PDF/UA-2 target in v1 — PDF/UA-1 (ISO 14289-1) is what EN 301 549 and
  Dutch procurement demand.

## Decisions

### D1 — Extend the shared veraPDF backend, do not fork it

**Decision:** the PDF/UA path is a method on the same `VeraPdfService`
`verapdf-validation` introduces — e.g. `validateUa(File $file): array` that
invokes the same probed binary with `--flavour ua1` and parses the same JSON
result shape (flavour, `compliant`, failed checks with
specification/clause/test references, validator version). It reuses the same
`filinq.verapdf.binary_path`, `filinq.verapdf.max_seconds`,
`filinq.verapdf.enabled` config, the same availability probe, and the same
admin-settings status row. No new binary, no new config namespace, no second
probe.

Rejected: a separate PDF/UA validator service — duplicates the probe, the
config, the degradation logic and the admin surface for the same tool;
divergence risk on every veraPDF version bump.

**Sequencing note:** the shipped `depends_on` is `pdfua-accessible-output`
(this change upgrades its heuristics and reuses its `category` key). There is
ALSO a hard build dependency on `verapdf-validation` for `VeraPdfService` and
the probe/admin plumbing; the two changes are co-owned siblings on the
veraPDF backend and are expected to apply together. Kept out of `depends_on`
per the build-phase decision (D4), documented here so the apply order is not
lost. See Open Questions.

### D2 — Validator-backed checks in the existing `accessibility` category

PDF/UA is accessibility, so validator-backed PDF/UA checks belong in wave-1's
`accessibility` category (unlike PDF/A, which `verapdf-validation` put in a
new `archival` category because PDF/A is not accessibility). **Decision:** add
check ids to `DocumentValidationService` under `category: "accessibility"`:

| checkId | Backed by |
|---|---|
| `pdfua-conformance-failed` | veraPDF `ua1` verdict is non-compliant; finding params carry the flavour, the failed-checkpoint count and the top Matterhorn clause references |
| `accessibility-validator-unavailable` | a validator-backed accessibility check is enabled in the resolved profile but veraPDF is absent/disabled — an explicit "not validated" finding (severity `warning`, non-escalatable), never a silent skip |

These run ONLY on PDFs, ONLY when the resolved profile enables them
(per-profile opt-in; a JVM start per document is the cost), and default `off`
in shipped profiles. The wave-1 heuristic checks keep their own ids and stay
default `warning` — the heuristic floor is always on; the validator is the
opt-in truth. This is additive to the check catalogue and does NOT re-MODIFY
the profiles-defaults requirement (already amended by `verapdf-validation` to
cover validator-backed checks) — avoiding a double-MODIFY of the same base
requirement across two in-flight changes.

### D3 — A separate `accessibilityConformanceReport`, not a shared report

`verapdf-validation` persists a `conformanceReport` keyed by `fileId` for
PDF/A. **Decision:** PDF/UA verdicts persist to a SEPARATE
`accessibilityConformanceReport` OR object, also keyed by `fileId`
(re-running updates it): `flavour` (`ua1`), `compliant`, `failedCheckCount`,
`failedChecks` (bounded list of `{clause, testNumber, checkpoint,
description-ref}` — references only, never document content),
`validatorVersion`, `validatedAt`, `trigger` (`manual` | `validation` |
`generation`). Two reports because a document can be simultaneously PDF/A
conformant and PDF/UA non-conformant (or vice versa); one shared object would
have each run clobber the other's verdict. Content-free by construction
(clause references and checkpoint numbers only — AVG Art. 5(1)(c)); it is
accessibility-audit evidence. Endpoint:
`POST /api/validation/accessibility/{fileId}` (IDOR-safe user-folder
resolution, mirrors the existing on-demand validation endpoint but persists).

Rejected: extending `verapdf-validation`'s `conformanceReport` with a
`standard` discriminator — cross-change schema coupling to an unarchived
sibling, and it re-introduces the clobber problem for re-runs.

### D4 — Remediation guidance from the failure shape

Guidance strings (i18n EN/NL) keyed by failure class, attached to the report
and the accessibility findings panel:

- Structure/tagging failures on a document produced by Filinq's own
  accessible/tagged output path (`pdfua-accessible-output` REQ-DDPUA-001/002
  LibreOffice `PDFUACompliance` route) → "regenerate through Filinq with the
  accessible output option" (its path emits tagged structure).
- Failures on a document produced by the untagged mPDF path or on an
  imported/uploaded PDF → "Filinq does not retag imported pages; re-author
  from an accessible source (tagged DOCX/template) and regenerate" — honest
  limitation, the accessibility analogue of the PDF/A font-embedding trap.
- Per-checkpoint failures → the Matterhorn clause/checkpoint reference plus
  the generic regenerate-accessible advice.

No auto-remediation in v1 (Non-Goals). The report and UI state a conformance
verdict; they never assert "PDF/UA certified".

### D5 — Heuristic-vs-validator hierarchy: floor and truth, labelled

When the validator is unavailable, the wave-1 heuristics are the only
accessibility signal and the UI presents them as heuristic ("looks tagged").
When the validator is available and its checks are enabled, the Matterhorn
verdict is the authoritative accessibility conformance for the document; the
UI labels validator findings as validator-backed (distinct from heuristic
findings, exactly as `verapdf-validation` distinguishes `archival` from
`document`/`accessibility`). A heuristic pass with a validator fail is
reported as a validator fail — the stronger, correct verdict wins in the
surfaced conformance state, while both findings remain visible with their
source. The heuristic floor never regresses; it is never removed.

### D6 — Declarative vs imperative (ADR-031), OR usage (ADR-001), frontend (ADR-012)

`accessibilityConformanceReport` is a **declarative** register addition
(single-state evidence record, no lifecycle annotations). Validator
invocation, the check leg and the endpoint are **imperative** —
external-binary document processing, the established ADR-031 exception (same
category as soffice/Tesseract/veraPDF-for-PDF/A). Persistence via
ObjectService/AppHost (no custom tables); OR services otherwise untouched.
ADR-011: no new parsing utilities beyond reusing the veraPDF JSON reader that
`verapdf-validation` adds. Frontend: validator findings in the existing
`accessibility` category-grouped panel; a PDF/UA conformance card on document
detail; NC CSS variables (ADR-003); strings EN-keyed with NL translations
(ADR-005). All new UI itself satisfies WCAG 2.1 AA.

## Seed Data

```json
// accessibilityConformanceReport — a conformant generated besluit
{ "fileId": 816001,
  "flavour": "ua1",
  "compliant": true,
  "failedCheckCount": 0,
  "failedChecks": [],
  "validatorVersion": "veraPDF 1.26.2",
  "validatedAt": "2026-07-18T09:20:00Z",
  "trigger": "generation" }

// accessibilityConformanceReport — an imported PDF failing Matterhorn checkpoints
{ "fileId": 816002,
  "flavour": "ua1",
  "compliant": false,
  "failedCheckCount": 3,
  "failedChecks": [
    { "clause": "7.1", "testNumber": "01-006", "checkpoint": "Real content not tagged" },
    { "clause": "7.3", "testNumber": "09-004", "checkpoint": "Figure without alternative description" } ],
  "validatorVersion": "veraPDF 1.26.2",
  "validatedAt": "2026-07-18T09:22:10Z",
  "trigger": "manual" }
```

Test fixtures (committed under `tests/sample-documents/`, generated content,
no personal data): a genuinely PDF/UA-1-conformant tagged PDF, and a PDF that
passes the wave-1 heuristics (has `/StructTreeRoot`, `/Lang`, title,
`pdfuaid:part`) but fails Matterhorn checkpoints (untagged content, a figure
without alt) — the fixture that proves heuristics ≠ conformance.

## Risks / Trade-offs

- [Depends on an unapplied sibling (`verapdf-validation`) for the backend] →
  the changes are co-owned and apply together; the check leg and report are
  independently testable against a `VeraPdfService` fake pinned to the
  documented `ua1` result shape; verified against merged HEAD at apply time.
- [JVM cost per validation] → per-profile opt-in checks (D2), on-demand
  endpoint, the existing `filinq.verapdf.max_seconds` budget, no default-on
  upload validation.
- [Two conformance reports per file (PDF/A + PDF/UA)] → deliberate (D3);
  each is small, content-free, keyed independently; the document-detail view
  shows both cards.
- [Heuristic vs validator disagreement confuses operators] → D5 labels the
  source and surfaces the stronger verdict; docs explain the hierarchy.
- [Matterhorn verdicts are strict — many existing PDFs will fail] →
  validator-backed checks default `off` (opt-in); guidance (D4) is honest
  about imported-page limits so a fail is actionable, not a mystery.

## Migration Plan

1. Register JSON: add `accessibilityConformanceReport` (additive, union-merge).
2. Extend `VeraPdfService` with `validateUa()` (inert without the binary /
   without `verapdf-validation` applied).
3. `accessibility` validator checks + accessibility conformance endpoint +
   document-detail card.
4. Wire the validator verdict into the surfaced accessibility conformance
   state (D5), heuristics remaining the floor.
5. Rollback: disable via `filinq.verapdf.enabled` or leave the archival/
   accessibility validator checks `off` — everything degrades to the wave-1
   heuristic behaviour; reports remain as inert evidence; no data migration.

## Open Questions

- Should `depends_on` also list `verapdf-validation`? The build-phase
  decision (D4) fixed `depends_on: [pdfua-accessible-output]`; the hard
  backend dependency on `verapdf-validation` is documented here and the two
  are expected to apply together. Flagged for the apply orchestrator to
  confirm the apply order (VeraPdfService must exist first).
- Should generation of an `accessible`-option document auto-validate PDF/UA
  when veraPDF is present (trigger `generation`)? Provisional: yes via the
  same hook once the tagged-output path is live; scenario-gated so it never
  errors the base generation flow when the validator is absent.
