# Design — multi-format-editing-tools

Editing beyond text documents: spreadsheets, presentations, and whatever else the
installed suite turns out to accept.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Per-type codecs | **Imperative** — `Editing/Codec/*` | Byte manipulation of ODF/OOXML packages. Not expressible declaratively. |
| WOPI session, locking, version precondition | **Imperative, REUSED** | Inherited unchanged from `document-editing-tools`. None of it is type-specific, and re-implementing it per type is how the two paths drift apart. |
| Supported-type matrix | **Declarative** — a versioned declaration, re-measured per suite/version | A table of measured facts. Making it declarative is what makes it visibly stale when the suite changes. |
| Record of what was produced | **Declarative** — existing `generatedDocument` | Unchanged. |

No `x-openregister-{lifecycle,aggregations,calculations,notifications,relations,widgets}`
block is added or modified.

## One session, three codecs

The layers `document-editing-tools` built are type-agnostic and are reused as-is:

```
CheckFileInfo → Lock → GetFile → [ codec ] → Version recheck → PutFile → Unlock
                                     ↑
              TextCodec | SpreadsheetCodec | PresentationCodec
```

Only the bracketed step varies. This matters more than it sounds: the lock
discipline and the `Version` precondition are the controls that make an in-place
write safe, and a per-type editing path that re-implemented them would eventually
re-implement one of them wrongly.

## Addressing, per type

**Text** — block anchors, with the `w14:paraId` question that
`document-editing-tools` Phase 0 answers.

**Spreadsheet** — `Sheet!Cell`. This is the pleasant surprise of the change: a
spreadsheet has no anchor problem. A cell address *is* a durable identity, and a
human inserting a row above shifts the addresses of everything below in a way both
the file format and the user's mental model already agree on. Nothing needs
measuring.

**Presentation** — slide id + shape id. Object ids are stable; **slide ORDER is
not**. So an edit addresses a slide by id, never by "slide 4" — an agent told to
fix the third slide must resolve that to an id at read time, and a reorder between
read and write is caught by the same `Version` precondition that catches any other
concurrent change.

## The formula hazard

This is the substance of the change.

A cell holds either a literal or a formula, and a written literal replaces a
formula with no visible difference. The sheet keeps rendering a number; it is
simply a number that has stopped being computed. Worse, the loss propagates: every
cell depending on it recalculates against the frozen literal, so a single
careless write can shift a whole model while every individual cell still *looks*
right.

There is no equivalent in text editing, and it is not caught by any of the
controls `document-editing-tools` established — the lock held, the version matched,
the tag was applied, the trace recorded the file id. All of it green, and the
budget is now wrong.

So:

- **Writing a literal into a formula cell is refused** unless the caller sets an
  explicit `replaceFormula` intent for that cell. Not a global flag — per cell, so
  a bulk write cannot quietly carry the permission along.
- **Accepted writes report what recalculated.** The response names the cells whose
  computed value changed as a consequence. "What else moved?" is the question a
  spreadsheet edit raises, and an answer no other surface provides.
- **A write that would produce an error value** (`#REF!`, `#DIV/0!`, `#VALUE!`) in
  any dependent cell is reported, because an agent has no way to notice it and a
  human reading a summary would not either.

## The type matrix is measured

The suites differ, and any table written here would be wrong by the next release:

| | Collabora (LibreOffice lineage) | Euro-Office (ONLYOFFICE lineage) | LibreOffice desktop |
|---|---|---|---|
| Text / sheet / slides | ODF + OOXML | OOXML-native, ODF supported | all, but **no server seam** |
| Legacy `.doc`/`.xls`/`.ppt` | strong | weaker | strong |
| Draw (`.odg`) | yes | own diagram editor, different model | yes |
| PDF | annotation / forms | dedicated PDF editor | — |

Rather than encode that, the system **probes**: for each candidate type it asks the
installed suite, through the same `CheckFileInfo` / `IConversionManager` seams
ADR-087 already mandates, whether the type is actually editable here — and
publishes the answer with the suite name and version it was measured against.

The rule that follows is the one that keeps us honest: **an unprobed type is
unsupported.** Not "probably fine because LibreOffice can open it".

## ADR-087 §4 in practice

Draw is the concrete case. If `.odg` editing works on Collabora and not on
Euro-Office, then:

- the capability is offered where it works and resolves **absent, visibly**, where
  it does not;
- no workflow, template or downstream feature may require it;
- the spec says so, rather than leaving a Collabora-only capability to be
  discovered by a Euro-Office tenant at the point of failure.

## Refusals

Extending the standing refusals in `document-editing-tools`, all still in force:

- **No macro-bearing formats.** `.xlsm`, `.docm`, `.pptm` are refused on sight.
  Writing into a file carrying VBA is a code-execution vector in document clothing.
- **No formula overwrite without per-cell intent.**
- **No PDF content editing.** Annotation and form-fill only — a PDF is a final-form
  artefact, and silently rewriting its text produces something forgery-shaped.
- **No database formats.** `.odb` is a database, not a document; it has no
  meaningful "edit a cell" semantics and its backing store is not a package.
- **No type inferred from extension.** Type is determined from content/MIME, since
  a `.xlsx` that is actually a zip of something else is exactly the input that
  should be refused rather than parsed.

## Verification

- Phase 0 is the **type-support probe**, run per suite, and its output is the
  matrix. Until it runs, the supported set is empty rather than assumed.
- The formula guard is proven with a **control pair**: a write to a formula cell
  without intent is refused, and the same write with per-cell intent succeeds and
  reports the recalculated cells. A guard nobody has watched refuse is a guard
  nobody has tested.
- Round-trip fidelity per type, on a file carrying the things a rebuild silently
  drops: a spreadsheet with named ranges, conditional formatting and a pivot table;
  a presentation with speaker notes, transitions and an embedded chart.
- Portability: each type exercised against Collabora **and** Euro-Office. Any type
  that cannot be exercised against both is published as suite-specific rather than
  claimed as supported.

## Seed data

None. No OpenRegister schema is introduced or modified.

## DEFERRED_QUESTIONS

1. **Should CSV be editable, or convert-only?** CSV has no formulas, styles or
   sheets, so "editing" it is line rewriting with none of this machinery.
   Provisional decision: convert-only, not editable. Affects: the type matrix.
2. **Does a presentation edit need to address speaker notes separately from slide
   content?** They are different audiences on the same object. Provisional
   decision: yes, as a distinct addressable region, since an agent asked to draft
   talking points should never touch what is on screen. Affects: the presentation
   addressing model.
3. **How should a bulk spreadsheet write be bounded?** A single call could rewrite
   ten thousand cells. Provisional decision: a per-call cell cap, refusing rather
   than truncating. Affects: the tool's input schema and a spec requirement.
