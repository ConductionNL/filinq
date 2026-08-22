# Design: template-charts

> **Descope note (this implementation wave):** D3 (office path) and the
> `nc_image()` half of D2 are not built — D3 depends on the unbuilt
> `office-template-authoring` REQ-DDOTA-003 pre-pass slot, and `nc_image()`
> is not trivial enough to fit the reduced HTML/PDF-only scope this wave
> covers (`chart()` + `data_table()` only). D5's raster fallback is
> likewise descoped since it only applies to the (out-of-scope) office/odf
> conversion paths. See `tasks.md` for the per-task breakdown.

## Context

Verified current state (HEAD of this worktree):

- `TemplateRenderer` runs Twig in a `SecurityPolicy` sandbox with explicit
  whitelists: functions `range, cycle, date, max, min`; filters incl.
  `column, batch, number_format, map`; tags incl. `for, if, set`. No object
  methods/properties are callable. Adding a function = extending
  `ALLOWED_FUNCTIONS` + registering the implementation — the mechanism this
  change uses.
- `PdfService::renderPdf()` → mPDF `^8.2` (`composer.json`). mPDF 8 renders
  inline `<svg>` in `WriteHTML()` (the codebase already feeds it inline SVG:
  `PdfService::buildCropMarksHtml()` injects four inline SVG crop marks —
  the SVG-through-mPDF path is proven in this repo).
- PhpWord `^1.2` (`composer.json`): `TemplateProcessor` provides
  `setChart()` (replaces a macro with an `Element\Chart` — native
  WordprocessingML chart part; supported types include pie, bar, column,
  line) and `setImageValue()` (raster image insertion). Wave-1
  `office-template-authoring` (REQ-DDOTA-003) already routes office fills
  through `TemplateProcessor` with a fragments pre-pass — the
  `chart:`/`image:` placeholder families slot into that same pipeline.
- `DocumentService::convertToOdf()` / the shared HTML→DOCX conversion
  (multi-format-output D3) go through LibreOffice's HTML import, whose
  inline-SVG fidelity is poor — hence the raster fallback (D5).
- `huisstijl` schema carries `primaryColor` — the default palette seed.
- `LibreOfficeHeadlessBackend` exists and is the sanctioned local converter;
  `soffice --convert-to png` rasterises SVG locally.

Constraint (openspec/config.yaml): **no external chart services — local
processing only**; ADR-001 (no new tables — this change stores nothing);
ADR-011 (reuse: mPDF/PhpWord/LO, no new rendering deps).

## Goals / Non-Goals

**Goals:**

- Charts (bar/line/pie), formatted tables, and NC-file images in generated
  documents, on both template types, fully local.
- Deterministic, reproducible rendering (audit: same template version + same
  data = same document).
- Zero weakening of the Twig sandbox's security posture.
- Honest degradation: a chart that cannot render produces a visible warning
  marker + generation warning, never a silent gap.

**Non-Goals:**

- No chart designer UI, no interactive/JS charts, no external services.
- No scatter/radar/pivot; no XLSX.
- No new schemas/registers; no changes to template CRUD/versioning.
- No GD/Imagick dependency.

## Decisions

### D1 — Chart engine: pure-PHP SVG builder, one renderer for every path

`ChartSvgRenderer::render(string $type, array $data, array $options): string`
builds SVG by string/DOM assembly in PHP — axes, ticks, bars/lines/slices,
legend, labels. `$data` is `{labels: [...], series: [{name, values: [...]}]}`
(single series for `pie`). Options: `title`, `width`, `height`, `palette`,
`showLegend`, `valueFormat`. Palette defaults to a shade ramp seeded from the
active huisstijl `primaryColor` (when the generation carries a `huisstijlId`)
with an accessible fixed fallback. Deterministic: no randomness, no
timestamps, stable element ordering — unit tests snapshot the SVG.

**Rejected alternatives:** external chart APIs (config-forbidden);
headless-browser rendering of a JS chart lib (heavyweight new runtime for a
could-have); GD/Imagick drawing (raster-first loses print quality and adds an
extension dependency mPDF does not require). SVG is resolution-independent —
right for print-grade PDF.

### D2 — Twig surface: three whitelisted functions, sandbox posture unchanged

Extend `TemplateRenderer::ALLOWED_FUNCTIONS` with exactly `chart`,
`data_table`, `nc_image`, implemented as `TwigFunction`s marked
`is_safe: ['html']` (they return app-generated markup; data values inside
are escaped by the renderers themselves):

- `chart(type, data, options = {})` → `ChartSvgRenderer` SVG string; invalid
  type/shape → inline `[chart error: reason]` marker + a generation warning
  (`DocumentService` warnings channel, DCS-014 style) — visible, never fatal,
  never blank.
- `data_table(collection, columns, options = {})` → `TableHtmlRenderer`:
  `columns` as `[{key, label, align?, format?}]` with `format` ∈
  `text|number|date|currency` using NL locale conventions; escapes every cell
  value; empty collection renders a localised empty-state row.
- `nc_image(fileId, options = {})` → `TemplateImageResolver`: resolves the NC
  file **as the generating user** via `IRootFolder` user folder (no
  app-privileged read), validates mime against a raster whitelist
  (png/jpeg/gif/webp), enforces `filinq.templates.max_image_bytes`
  (default 5 MB), returns `<img src="data:...">`. Unreadable/oversized/
  non-raster → `[image unavailable: reason]` marker + warning. SVG *files*
  are rejected (user-supplied SVG is a script/entity vector; app-generated
  chart SVG is trusted because we build it).

Everything else in the sandbox (tags, filters, no-method policy, autoescape)
is untouched; the functions perform no writes and no network I/O.

### D3 — Office path: native DOCX charts via `setChart`, images via `setImageValue`

Office templates use placeholder families inside the existing REQ-DDOTA-003
fill pipeline (same pre-pass slot as `${fragment:...}`):

- `${chart:key}` — the generation context must carry `charts.key = {type,
  data, options}` (from resolved register data or `adHocData`; the wizard can
  assemble it). The fill step builds a `PhpWord\Element\Chart` and calls
  `TemplateProcessor::setChart()` → a **native** chart part in the DOCX
  (editable in Word, converted faithfully by the LO cascade to PDF).
  Unsupported type or malformed data → the placeholder is replaced with the
  visible error marker text + warning.
- `${image:key}` — context `images.key = fileId`; `TemplateImageResolver`
  fetches to a temp path (same validation as D2) and
  `TemplateProcessor::setImageValue()` inserts it with declared dimensions.
- Tables: office authors use REQ-DDOTA-003's native row cloning
  (`${block}`/row macros) — no new office table mechanism is invented; the
  docs show the collection-table recipe.

Rejected: rasterising the SVG and inserting it as a picture for DOCX —
loses Word-editability, which is the office path's whole value; kept only as
the conversion fallback (D5).

### D4 — Data sources: the resolved context is the single source

Charts/tables consume the same merged context every other template construct
sees (`DataResolverService` output + `adHocData`, DCS-005 precedence). "Bound
register data" therefore needs no new query surface: a template binds data
via `dataRefs` exactly as today, and inline arrays are built with existing
whitelisted filters (`column`, `map`, `merge`). This keeps chart data
subject to the same RBAC and the same audit (`generatedDocument.dataRefs`)
as the rest of the document — no separate chart-data fetch to secure.

### D5 — Format matrix behaviour: SVG-first, local raster fallback, honest warnings

| Output | Twig path | Office path |
|---|---|---|
| `html` | inline SVG (self-contained) | n/a |
| `pdf` | inline SVG through mPDF (proven path) | native chart through LO cascade |
| `odf` / `docx` (from HTML) | SVG rasterised to PNG via LibreOffice headless (`soffice --convert-to png`, serialization lock respected), `<img>` substituted before conversion | native chart (docx passthrough); LO converts for odf |

When rasterisation is unavailable (no LO) on an HTML→odf/docx job, the chart
degrades to the visible error marker + a generation warning naming the
format — never silently dropped. (With multi-format-output installed, those
formats are typically unavailable then anyway; the interplay is documented,
not depended on.)

### Declarative vs imperative (ADR-031)

Chart/table/image *definitions* are declarative template content (versioned
with the template, diffable, auditable). Imperative code is confined to
rendering (SVG assembly, PhpWord element construction, file resolution) —
the standard imperative exception for external-binary/file assembly, same
family as the existing generation services. No OR behaviour annotations are
added or needed; no register JSON edit at all.

## OpenRegister usage (ADR-001)

| Operation | OR service |
|---|---|
| Chart/table data | existing `DataResolverService` → OR `ObjectService` (no new fetch path) |
| Images | Nextcloud `IRootFolder` (user files, ACL-checked) — not OR objects |
| Audit | existing `generatedDocument` logging (dataRefs already recorded); warnings array carries chart/image degradations |

No custom tables, no schema changes, no register bump — this change is
code-only against existing data.

## Seed Data

No new schemas — no register seed objects. Shipped fixtures instead:

- `tests/sample-documents/chart-template.docx` — office template with
  `${chart:bezwaren}`, `${image:logo}`, and a cloned-row table block
  (Demostad flavour, pairs with Wave-1's seed office template).
- A seed Twig demo template (namespace `filinq`, "Demostad
  handhavingsrapportage") whose content exercises `chart('bar', …)`,
  `chart('pie', …)`, `data_table(...)` over the seeded Demostad dossier
  collection, and `nc_image(...)`.
- `tests/fixtures/charts/*.json` + expected-SVG snapshots for the renderer's
  deterministic-output tests (nil-UUID/synthetic values only).

## Security Considerations

- **Sandbox posture unchanged**: three pure functions added to the whitelist;
  no tags, no filters, no object access, no writes, no network. The
  sandbox-refusal tests (REQ-PDF-03) are extended, not relaxed.
- **`nc_image` cannot bypass ACLs**: resolution runs as the generating user
  through the user folder; an unreadable file yields a marker, and the error
  reason does not disclose file existence details beyond "unavailable".
- **No user-supplied SVG**: chart SVG is exclusively app-generated;
  user-controlled values entering SVG/HTML/DOCX are escaped
  (`htmlspecialchars` in SVG text nodes / table cells; PhpWord XML-escaping
  via its element API). Raster-only image whitelist blocks SVG/EPS vectors.
- **Resource bounds**: image size cap; chart series/point caps
  (`filinq.charts.max_points`, default 1000) so a template cannot OOM mPDF
  with a million-point series; rasterisation goes through the serialized LO
  lock like every other soffice call.
- GDPR: chart/table data is generation-context data already covered by the
  existing audit trail; no new storage.

## Risks / Trade-offs

- [PhpWord `setChart` styling is basic (colors/legends less rich than the
  SVG renderer)] → accepted: office-path priority is *native editability*;
  authors needing pixel-perfect charts use Twig templates. Documented in the
  author guide; a `TemplateProcessor::setChart` capability pin in the unit
  suite catches a future PhpWord regression.
- [mPDF SVG support has gaps (no gradients/filters)] → renderer emits a
  conservative SVG subset (shapes, paths, text, solid fills) — snapshot
  fixtures double as the compatibility contract; crop-marks precedent shows
  the subset renders.
- [LO HTML-import drops inline SVG on odf/docx conversion] → D5 raster
  fallback + honest warning; never silent.
- [Palette from huisstijl may collide with readability] → ramp generation
  enforces WCAG-AA-ish contrast against white and falls back to the fixed
  accessible palette when the seed color is unusable (nldesign lesson:
  never trust a single token for contrast).
- [Determinism vs locale formatting] → NL formatting applied via explicit
  `format` options, not environment locale, so snapshots are stable across
  containers.
