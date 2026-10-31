# Legal basis and objection deadline in a decision letter

## Why

Matrix row `gen-legal-basis` (area generate, core): "Include the legal basis
and the decision deadline in a decision letter." Rated partial, state
building, with no open change for the missing half. Decided build on
2026-09-28 in the build-all pass because the row is in the core area.

What is built: a template can print any field of a resolved data reference,
so an author who knows the path can type a `base` (grondslag) field by hand.
The Woo grondslagen legend A to S is seeded in the `base` schema.

What is not: nothing computes the objection deadline of a decision (Awb 6:7,
six weeks from the day after the decision is sent), and nothing offers the
legal basis as a field to insert. `ObjectionDeadlineChecker` is about
publication-consent objections, not about decision letters. The
plain-language rendition already refuses a letter whose bezwaartermijn
statement cannot be resolved, so the missing value is felt there too.

## What changes

- Generation adds `bezwaar` to the template context when the data carries a
  decision date: `bezwaar.termijnWeken`, `bezwaar.uiterlijk` (the last day to
  object) and `bezwaar.vanaf`.
- The merge field dialog offers the legal basis (`grondslag.name`,
  `grondslag.article`) and the objection deadline fields.

## Impact

- `DocumentService` context building, `MergeFieldDialog.vue`.
- App setting `bezwaar_termijn_weken` (default 6).
- No schema change.
