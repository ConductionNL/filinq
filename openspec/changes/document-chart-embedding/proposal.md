---
kind: code
depends_on:
  - document-rich-editing
---

## Why

An agent could change a document's words, style, layout and metadata. It could not
put a chart in one — and a report that argues from numbers usually needs to show
them.

This is the first thing in the editing surface that **adds package parts** rather
than rewriting one, and that changes the failure mode. A wrong span rewrite produces
a document with a wrong paragraph. A wrong multi-part write produces a document the
suite **refuses to open at all**. Five things must agree:

1. `word/charts/chartN.xml` — the chart definition
2. `[Content_Types].xml` — an Override declaring its content type
3. `word/_rels/document.xml.rels` — a Relationship with a fresh id
4. `word/document.xml` — a `<w:drawing>` referencing that id
5. the id in (3) and (4) must be **the same**, and must not collide with an existing one

## What Changes

- **`ChartCodec`** building bar, line and pie charts as native DrawingML.
- **`addDocumentChart`** tool. Document tools: 5 → **6**.
- `EditSessionService::embedChartForAgent()`, running through the same session as a
  text edit — same lock, same version precondition, same `Agent authored` tag.

## Verified against a live suite, not asserted

Rendering the same document through ONLYOFFICE with and without the chart:

```
without chart   25,793 byte PDF
with chart      51,777 byte PDF
```

The conversion returning success was **not** treated as evidence — a PDF is produced
either way if the chart part is silently skipped. The 26 KB delta is the chart drawn
as vector content.

The relationship id assigned on a real PhpWord document was **`rId7`**, because that
document already had six relationships. A hard-coded `rId1` would have silently
replaced a real one.

## Stated limits

- **No embedded workbook.** Values live in the chart's own `<c:numCache>` /
  `<c:strCache>`, which is what suites render from. The chart draws, selects,
  resizes and restyles correctly — but "Edit data" has no worksheet to open.
  Minting a valid `.xlsx` is a second package format inside this one, and a subtly
  wrong one produces exactly the refuses-to-open failure above.
- **OOXML only.** An ODF chart is an embedded object with its own directory and a
  `META-INF/manifest.xml` entry — a different construction, not a translation.
  Refused by name.

## Capabilities

### New Capabilities
- `document-chart-embedding`: how a chart is added to a document, and what is refused.

## Impact

- **Code**: new `lib/Service/Editing/ChartCodec.php`; `EditSessionService` gains a
  method; `DocumentAgentService` gains a tool.
- **Depends on** `document-rich-editing` for `PackagePartIo`.
