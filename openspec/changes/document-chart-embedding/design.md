## Context

Every edit so far rewrote a byte range inside one existing part. A chart does not
fit that shape: it needs a new part, a content-type declaration, a relationship, and
a reference from the body — four writes that must agree.

The failure mode changes with it. A wrong span rewrite yields a document with a
wrong paragraph, which a person can see and fix. A wrong multi-part write yields a
document the suite refuses to open, and the user cannot recover it themselves.

## Goals / Non-Goals

**Goals:**
- A real chart — selectable, resizable, restyleable in the suite — not an image.
- Package consistency guaranteed by construction and by test.
- Verification by a suite actually rendering it, not by a call returning success.

**Non-Goals:**
- An embedded workbook (see D2).
- ODF charts. A different construction; refused by name.
- Scatter, area, radar, combo. Three types cover the reporting cases; each new type
  is more axis and grouping detail to get exactly right.

## Decisions

### D1 — Native DrawingML, not a rendered image

An image is portable and trivial. It is also a picture of a graph: it cannot be
restyled to match a document's theme, does not scale as vector art, and cannot be
corrected without regenerating it upstream.

The cost is that this is the most fragile thing in the editing surface. That cost is
paid down by D3.

### D2 — Cached values, no embedded workbook

A chart may reference an embedded `.xlsx` so a user can click "Edit data". It may
also carry values inline in `<c:numCache>` / `<c:strCache>`, which is what suites
actually render from.

Only the caches are written. The consequence is stated rather than hidden: the chart
renders and behaves as a chart, but "Edit data" has no worksheet to open. Minting a
valid workbook is a second package format nested inside this one, and a subtly wrong
one produces the refuses-to-open failure that D3 exists to catch.

### D3 — Verified by a suite rendering it, and the control run first

The conversion returning success is **not** evidence: a suite that silently skips an
unparseable part still emits a PDF.

So the same document was rendered with and without the chart:

```
without   25,793 byte PDF
with      51,777 byte PDF
```

The delta is the chart drawn as vector content. Without the control run, "ONLYOFFICE
converted it" would have been a claim about nothing — the same shape as the vacuous
`docx → docx` round-trip in `office-suite-portability`, which grew a file by 222
bytes of ZIP overhead and looked like a rewrite.

### D4 — The relationship id is scanned, never fixed

On a real PhpWord document the next free id was **`rId7`**. A hard-coded `rId1`
would have silently replaced an existing relationship — and the damage would appear
as a missing image or broken header somewhere else in the document, far from the
chart.

### D5 — Append before the `sectPr`

A `w:sectPr` must be the last child of `w:body`. A paragraph after it is invalid and
Word opens the document with a repair prompt — a failure that looks like corruption
rather than like a misplaced chart.

## Seed Data (ADR-001)

**None.** No OpenRegister schemas are introduced or modified.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Chart construction | **Imperative** | XML and ZIP part authoring. No schema, no derived value, no lifecycle. |
| Tool exposure | **Declarative** | `#[McpTool]` per ADR-063. |

## Risks / Trade-offs

**This is the most fragile thing in the editing surface**, and the fragility is
binary: the document opens or it does not. Mitigated by asserting the cross-part
agreements directly, and by a suite-rendering check with a control.

**No embedded workbook is a real limitation**, not a detail. A user who clicks
"Edit data" finds nothing. Named in the tool description so the model can tell the
user, rather than discovered by the user.

**Three chart types is a narrow surface.** Deliberate: each additional type is more
axis, grouping and marker detail, and each is another way to produce a file that
will not open.
