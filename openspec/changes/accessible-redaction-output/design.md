# Design: accessible-redaction-output

## Context

Verified at HEAD (Filinq `development`, branch
`spec/market-gap-wave3-2026-07`):

- **Redaction is OR's engine, Filinq is the leaf.** `AnonymizationService`
  (2237 LOC) drives OR's `TextExtractionService` + `DocumentProcessingHandler`
  (`replaceWords()` → `PdfTextReplacer` for PDF). ADR-022 and R4 D are explicit:
  the anonymisation/redaction engine is OpenRegister's; Filinq owns detection
  heuristics + orchestration + UI, not the PDF-rewriting engine.
- **Every run is recorded as `anonymizationLink`.** Verified in
  `lib/Settings/filinq_register.json` (schema `anonymizationLink`, register
  v7.3.0): it pairs `sourceFileId ↔ anonymizedFileId` with run metadata and
  real replacement counts (closed GH #286). It is the natural home for a
  per-run accessibility outcome.
- **The engine half is a parallel OR change.** OpenRegister's
  `tag-preserving-redaction` change (openregister repo, drafted in parallel)
  makes the redaction engine preserve the PDF tag tree and emit a
  `structurePreservation` block on the processing result. **This Filinq
  change assumes that contract** and does not implement tag rewriting.
- **`verapdf-validation` is an active Filinq change** that wires veraPDF as an
  optional local validator (verified: `openspec/changes/verapdf-validation/`).
  It is the right home for "is this output genuinely tagged/valid?"; this change
  consumes it when present, never re-integrates veraPDF.
- **`pdfua-accessible-output` is a separate active change** for *generated*
  documents (template → tagged PDF-UA output). Different pipeline; see Boundary.

## Assumed contract (from OR `tag-preserving-redaction`)

Filinq consumes this block on OR's processing result — the interface Filinq
codes against:

```jsonc
"structurePreservation": {
  "requested": true,            // Filinq asked for tag preservation
  "preserved": true,            // engine kept the tag structure
  "tagCountBefore": 128,        // structural tags in the source
  "tagCountAfter": 128,         // structural tags in the redacted output
  "lossReasons": []             // e.g. ["source-untagged","raster-fallback","engine-unsupported-format"]
}
```

**Fail-safe consumption**: if the block or a field is absent (older OR, engine
without the change), Filinq treats preservation as **unknown** and applies the
degraded path (warn + flag), never a crash and never a false "preserved".

## Goals / Non-Goals

**Goals:**

- Ask OR's redaction engine to preserve tags on every PDF redaction (default
  on), and honestly consume the outcome.
- Make the accessibility outcome **visible** (report + review UI) and
  **auditable** (on `anonymizationLink`).
- **Gate clearance** so a tag-destroyed redaction cannot be silently published
  under Woo — default warn (never a hard block by surprise), admin-escalatable
  to block.
- Upgrade a self-reported "preserved" to a validator-backed fact when
  `verapdf-validation` is present.

**Non-Goals:**

- No Filinq-local PDF tag-rewriting engine — that is OR's
  `tag-preserving-redaction` (ADR-022; the second-engine anti-pattern R4 D
  flags).
- No veraPDF integration of Filinq's own — that is `verapdf-validation`
  (consumed, presence-gated).
- No tagging of *generated* documents — that is `pdfua-accessible-output`
  (Boundary below).
- No image/raster redaction accessibility (raster output is inherently
  untagged; Filinq reports it as a `lossReason`, does not try to fix it).

## Decisions

### D1 — Request preservation (default ON for PDF)

`AnonymizationService` and the batch/folder services pass `preserveTags: true`
to OR's processing call for PDF inputs (default on; admin can default it off via
`filinq.redaction.preserve_tags_default`, but on is the shipped default
because the legal gate is on by default). Non-PDF formats that cannot carry PDF
tags pass `preserveTags` through unchanged and rely on the engine to report an
appropriate `lossReason` (e.g. format-unsupported). The option is additive to
the existing anonymise call; no signature break.

### D2 — Consume + map the outcome (`RedactionAccessibilityService`)

A thin `RedactionAccessibilityService` reads `structurePreservation` from the
processing result and maps it to a leaf-level state:

| State | Condition |
|---|---|
| `preserved` | `preserved === true` (and, if `verapdf-validation` present, veraPDF confirms tagged/valid — D4) |
| `degraded` | `requested === true` but `preserved === false` |
| `not-applicable` | source was untagged / raster / unsupported (`lossReasons` explains) |
| `unknown` | block/field absent (fail-safe → treated as `degraded` for the gate) |

The service owns no PDF parsing — it interprets the engine's block and calls the
veraPDF hook. This is a clean unit seam (phpunit can prove the mapping without a
live NC instance).

### D3 — Record on `anonymizationLink` + surface it

- **Record**: the mapped outcome (`structurePreservation` sub-object:
  requested, preserved, tagCountBefore/After, lossReasons, veraPdfVerified?) is
  written onto the run's `anonymizationLink` object, alongside the existing
  replacement counts — no entity values (Art. 5(1)(c)).
- **Surface**: the document report and the anonymisation review surface show an
  accessibility state chip (preserved/degraded/not-applicable) with tag counts
  and human-readable loss reasons; a **prominent flag** when degraded.

### D4 — veraPDF verification hook (presence-gated)

When `verapdf-validation` is present, `RedactionAccessibilityService` asks it to
validate the redacted output (tagged / PDF-UA-relevant checks). Result:

- veraPDF confirms → `veraPdfVerified: true`; a self-reported `preserved` is now
  validator-backed.
- veraPDF contradicts (`preserved` claimed but output not actually valid) →
  downgrade to `degraded` and flag — the validator wins.
- `verapdf-validation` absent → `veraPdfVerified` omitted; the engine's
  self-reported outcome stands (no duplication, no crash).

This is a dependency, not duplication: Filinq never invokes veraPDF directly;
it calls the `verapdf-validation` capability if that change has shipped.

### D5 — Clearance gate (default warn)

Accessibility joins the existing publication/clearance checks (next to the
prohibition/consent gates). Config
`filinq.redaction.accessibility_gate`:

- `warn` (**default**) — clearance proceeds but the degraded state is surfaced
  as a prominent flag on the clearance decision and recorded.
- `block` — a `degraded`/`unknown` outcome blocks clearance until an operator
  overrides with a reason (recorded).
- `off` — no gate (the outcome is still recorded + surfaced).

Default `warn` (not `block`) so the change never silently breaks an existing
publication flow; municipalities that treat WCAG as hard can escalate to
`block`.

## Boundary vs `pdfua-accessible-output` (explicit)

| | `pdfua-accessible-output` (active) | `accessible-redaction-output` (this change) |
|---|---|---|
| Pipeline | document **generation** (template → PDF) | document **anonymisation/redaction** |
| Engine | Filinq template renderer (mPDF / LibreOffice headless) | OpenRegister redaction engine (`tag-preserving-redaction`) |
| Failure fixed | output was **never tagged** | tags **destroyed by redaction** |
| Artifact | a newly generated citizen/Woo document | a redacted copy of an existing document |

The two are complementary halves of Filinq's accessible-output story and MUST
NOT both claim the redaction path. This change owns redaction output only;
`pdfua-accessible-output` owns generated output only.

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| Redaction + tag preservation | OR `DocumentProcessingHandler` (via `AnonymizationService`), consuming the `tag-preserving-redaction` engine change |
| Outcome record | OR ObjectService `saveObject()` on `anonymizationLink` |
| veraPDF verification | `verapdf-validation` capability (presence-gated), never invoked directly |

## Declarative vs imperative

- **Declarative**: the `structurePreservation` sub-object on the
  `anonymizationLink` schema + register-i18n tags; the config keys; the report
  chip.
- **Imperative (justified)**: passing `preserveTags`, mapping the engine block,
  the veraPDF hook call, the clearance gate decision.

## Seed Data

Extend one existing `anonymizationLink` seed with a preserved outcome so the
report renders non-empty:

```json
"structurePreservation": {
  "requested": true,
  "preserved": true,
  "tagCountBefore": 96,
  "tagCountAfter": 96,
  "lossReasons": []
}
```

## Security / Compliance Considerations

- No entity values recorded — only tag counts + loss reasons (AVG Art.
  5(1)(c)).
- Fail-safe: absent/partial block → `unknown` → warn+flag, never a false
  "preserved" and never a crash.
- The gate defaults to `warn` so it cannot silently harden an existing flow;
  `block` overrides are reason-recorded.
- The accessibility outcome is metadata about the redaction, not document
  content; nothing new is exposed publicly.

## Risks / Trade-offs

- [Depends on the OR engine change to actually preserve tags] → this leaf is
  honest regardless: without the engine change the outcome reports `unknown`/
  `degraded` (warn), correctly telling operators accessibility is unverified —
  it never claims a preservation that did not happen.
- [Default-on preservation may change redaction output/perf] → the engine owns
  the cost; Filinq only requests it; admin can default it off.
- [Warn-by-default lets a degraded doc through] → accepted: a hard block by
  surprise is worse; the flag is prominent and recorded, and `block` is one
  setting away.
- [veraPDF absent → self-reported only] → acceptable; the outcome is labelled
  "engine-reported, not validator-verified" until `verapdf-validation` lands.

## Migration Plan

Additive: `structurePreservation` sub-object on `anonymizationLink` (version
bump, seed), `preserveTags` passthrough, a mapping service, a config-driven
clearance check, report/review UI. No existing schema field changes; no data
migration. Rollback = stop passing `preserveTags` + remove the gate; recorded
outcomes remain readable.

## Open Questions

- Exact field names in OR's `structurePreservation` block — Filinq codes to
  the assumed contract and tolerates absence (fail-safe); reconcile with the OR
  `tag-preserving-redaction` change before apply.
- Whether `not-applicable` (source untagged) should count as a WCAG failure for
  the gate — provisional: no (you cannot preserve tags a source never had), but
  surface it so operators know the source itself is inaccessible.
- Whether the veraPDF check should run inline or as a background job for large
  batches — provisional: background for folder/batch runs, inline for single
  documents.
