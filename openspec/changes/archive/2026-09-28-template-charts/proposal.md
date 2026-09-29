---
kind: code
---

# Proposal: template-charts

## Why

Formal municipal documents increasingly carry data, not just prose:
jaarverslagen, Woo-inventarislijsten, subsidie-beschikkingen with financial
breakdowns, handhavingsrapportages with trend lines. Filinq templates today
can interpolate scalars and loop over collections (Twig sandbox, verified:
`TemplateRenderer` whitelists `for`/`if` and list filters), but they cannot
render a chart, cannot produce a consistently formatted table from an object
collection without hand-written HTML per template, and cannot place an image
from Nextcloud files. Competitors ship all three:

- **Carbone**: "Charts and pivot tables in templates — dynamic visual content
  rendering" (spectr `competitor_features`, competitor theme #9).
- **docxtemplater**: paid modules for images, charts, HTML — chart/image
  insertion is a monetised must-have in that ecosystem.
- **Fluent** (Apryse) advertises the same dynamic-content family.

The config rule is hard: **all processing local, no external chart
services** — which rules out every quickchart/image-charts style API and
makes this a genuine differentiator for an on-premises suite. Priority
**could-have**.

## What Changes

- **Local SVG chart renderer** (`ChartSvgRenderer`): pure-PHP generation of
  `bar`, `line`, and `pie` charts as self-contained SVG — no JavaScript, no
  network, no external binaries. Deterministic output (same data → same SVG),
  palette defaulting to the document's huisstijl `primaryColor` when set.
- **Charts in Twig templates**: a sandbox-whitelisted `chart(type, data,
  options)` function returning the SVG for inline embedding. Data comes from
  the already-resolved template context (bound register data via `dataRefs`
  — including wizard/prefill flows — or inline arrays built with existing
  whitelisted filters like `column`/`map`).
- **Charts in office templates**: a `${chart:key}` placeholder convention
  filled with a **native DOCX chart** via PhpWord's
  `TemplateProcessor::setChart()` + `Element\Chart` (bar/line/pie supported
  natively; survives the LibreOffice cascade to PDF).
- **Table generation from object collections**: a `data_table(collection,
  columns, options)` Twig function producing a consistently styled HTML table
  (column selection, labels, alignment, number/date formatting) and, on the
  office path, declared table-row cloning over a collection (building on
  REQ-DDOTA-003 row cloning).
- **Image placeholders from Nextcloud files**: `nc_image(fileId, options)`
  in Twig (embedded as data URI) and `${image:key}` via
  `TemplateProcessor::setImageValue()` on the office path — resolved as the
  generating user (RBAC ride-along), raster formats only, size-capped.
- **Format behaviour**: SVG-first on the HTML/PDF path (mPDF renders inline
  SVG); for HTML→ODF/DOCX conversions the SVG is rasterised locally (PNG via
  LibreOffice headless) with a generation warning when rasterisation is
  unavailable — never a silent blank.

GDPR note (config rule): charts/tables/images render data already flowing
through the generation context; the only new data access is `nc_image`
reading NC files, which happens as the requesting user under normal file
ACLs. Everything renders locally.

## Capabilities

### New Capabilities

- `template-charts`: dynamic visual content in templates — local SVG chart
  rendering (bar/line/pie) from register-bound or inline data, native DOCX
  charts on the office path, formatted tables from object collections, and
  NC-file image placeholders, across Twig and office templates.

### Modified Capabilities

- `pdf-generation`: the Twig sandbox security policy (REQ-PDF-03/07 family)
  is extended with three whitelisted, side-effect-bounded functions
  (`chart`, `data_table`, `nc_image`); the sandbox's existing guarantees
  (no object methods/properties, autoescape, function whitelist) are
  otherwise unchanged.

## Impact

- **Backend**: new `lib/Service/Charts/ChartSvgRenderer` (pure PHP SVG),
  `TableHtmlRenderer`, `TemplateImageResolver` (NC file → data URI / temp
  path, validation); `TemplateRenderer` sandbox whitelist extension +
  function implementations; office fill path (`OfficeTemplateService` /
  REQ-DDOTA-003 pipeline) gains `chart:`/`image:` placeholder handling
  (`setChart()` / `setImageValue()`); SVG→PNG rasterisation helper on the
  LibreOffice headless backend for the HTML→ODF/DOCX conversions.
- **Register**: none — no schema changes, no new register objects. Chart
  definitions live in template content (declarative in the template, like
  every other template construct).
- **Routes/Frontend**: no new routes; template preview
  (`TemplatePreviewService`, existing preview endpoints) renders
  charts/tables/images as part of normal preview. Docs and template-author
  guidance carry the UI weight.
- **Dependencies**: none new — mPDF (^8.2, inline-SVG capable), PhpWord
  (^1.2, `setChart`/`setImageValue`), LibreOffice headless already shipped.
  ADR-011 check: no OpenRegister `lib/Formats/`/`lib/Service/` utility
  renders charts/SVG (OR owns extraction, not rendering); GD/Imagick are NOT
  assumed present — rasterisation uses the LO backend instead.
- **Sibling boundaries**: no OpenRegister/OpenConnector changes; no Twig-tag
  or template-schema changes (office-template-authoring untouched beyond
  consuming its placeholder pipeline).

## Out of Scope

- Chart types beyond bar/line/pie (no scatter/radar/pivot tables this wave).
- A visual chart designer UI — charts are authored as template expressions /
  placeholders with documented options.
- External chart services or client-side chart JS in generated output
  (forbidden by config; generated documents must be self-contained).
- Interactive charts in HTML output (static SVG only).
- XLSX output or spreadsheet-style pivots (Carbone parity beyond documents).

## Success Criteria

- `openspec validate template-charts --strict` exits 0.
- A Twig template using `chart('bar', …)` on register-bound data renders the
  chart in HTML preview and in the mPDF-produced PDF, with identical data
  either inline or via `dataRefs`.
- An office template with `${chart:key}` produces a DOCX whose chart is a
  native, Word-editable chart object, and a PDF (via the cascade) showing it.
- `data_table()` renders a collection with selected columns and NL
  number/date formatting; `nc_image()`/`${image:key}` place an NC raster
  image, and a file the user cannot read fails with a warning marker, never a
  silent blank or an ACL bypass.
- The Twig sandbox still refuses object method/property access and every
  non-whitelisted function; the three new functions perform no writes.
- `composer check:strict` and the unit suite pass with zero new violations.
