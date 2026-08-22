# template-charts Specification (delta)

---
status: proposed
---

## Purpose

Dynamic visual content in generated documents, rendered fully locally (no
external chart services, per config): bar/line/pie charts from register-bound
or inline data, formatted tables from object collections, and Nextcloud-file
image placeholders — on both the Twig path (app-generated inline SVG through
mPDF) and the office path (native DOCX charts via PhpWord `setChart`,
images via `setImageValue`). The Twig sandbox extension itself is specified
as a `pdf-generation` delta (REQ-DDTCH-005, this change). Evidence: Carbone
charts/pivots, Fluent, docxtemplater chart module (competitor theme #9).

## ADDED Requirements

### Requirement: A local, deterministic SVG chart renderer (REQ-DDTCH-001)

The app MUST provide a chart renderer that produces `bar`, `line`, and `pie`
charts as self-contained SVG using pure local PHP — no JavaScript, no network
access, no external chart service, and no GD/Imagick dependency. Input is
`{labels, series}` data with options for title, dimensions, palette, legend,
and value formatting. Rendering MUST be deterministic (identical input yields
byte-identical SVG), MUST escape every data-derived text node, MUST enforce a
configurable point cap (`filinq.charts.max_points`, default 1000), and MUST
emit a conservative SVG subset (shapes, paths, text, solid fills — no
scripts, gradients, filters, or external references). The default palette
MUST derive from the active huisstijl `primaryColor` when a `huisstijlId` is
in effect, falling back to a fixed accessible palette when the seed color
cannot yield sufficient contrast.

#### Scenario: Same data renders byte-identical SVG

- GIVEN a bar-chart data fixture
- WHEN the renderer is invoked twice (and across container restarts)
- THEN both outputs are byte-identical and match the committed snapshot
- @e2e exclude deterministic-output pin with no UI surface — covered by PHPUnit snapshot tests (tests/unit/Service/Charts/ChartSvgRendererTest.php)

#### Scenario: Data-derived labels cannot inject markup

- GIVEN a series whose label contains `</svg><script>` content
- WHEN the chart is rendered
- THEN the label appears escaped as literal text and the SVG contains no script element
- @e2e exclude injection pin; covered by PHPUnit (tests/unit/Service/Charts/ChartSvgRendererTest.php::testLabelsEscaped)

### Requirement: Charts render in Twig templates from context data (REQ-DDTCH-002)

Twig templates MUST be able to render charts via a sandboxed `chart(type,
data, options)` function whose data comes from the standard resolved template
context — register-bound data resolved through the existing `dataRefs`
mechanism or inline arrays built with existing whitelisted filters — with no
separate chart-data fetch path. The returned inline SVG MUST appear in HTML
output and preview and MUST render in PDF output through mPDF. An invalid
chart type or malformed data MUST produce a visible inline `[chart error:
reason]` marker plus an entry in the generation warnings — never a fatal
error, never a silent blank.

#### Scenario: Bar chart from register-bound data in preview and PDF

- GIVEN the seeded Twig demo template calling `chart('bar', …)` over resolved Demostad dossier data
- WHEN the template is previewed and then generated as PDF
- THEN the HTML preview contains the chart SVG
- AND the generated PDF renders the same chart
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Malformed chart data degrades visibly

- GIVEN a template calling `chart('bar', data)` where `data` lacks `series`
- WHEN the document is generated
- THEN the output shows `[chart error: …]` at the chart position
- AND the generation response's warnings name the failure
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

### Requirement: Office templates get native DOCX charts (REQ-DDTCH-003)

Office templates MUST support a `${chart:key}` placeholder family, filled in
the existing office fill pipeline (office-template-authoring REQ-DDOTA-003
pre-pass slot): the generation context entry `charts.key = {type, data,
options}` is rendered as a **native** WordprocessingML chart via PhpWord's
`TemplateProcessor::setChart()` for the supported types (bar, line, pie), so
the produced DOCX contains a Word-editable chart object and the LibreOffice
cascade converts it into PDF output. Malformed or unsupported chart context
MUST replace the placeholder with the visible error-marker text plus a
generation warning. The unit suite MUST pin that `TemplateProcessor::setChart`
exists on the shipped PhpWord version, so a dependency regression breaks the
build instead of the feature.

#### Scenario: Office chart is native and survives the cascade

- GIVEN the fixture office template with `${chart:bezwaren}` and a valid chart context
- WHEN DOCX and PDF outputs are generated
- THEN the DOCX contains a native chart part (editable in Word/LibreOffice)
- AND the PDF produced through the conversion cascade displays the chart
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: PhpWord chart capability is pinned

- GIVEN the shipped composer dependencies
- WHEN the capability pin test runs
- THEN `\PhpOffice\PhpWord\TemplateProcessor::setChart` exists and accepts an `Element\Chart` for each supported type
- @e2e exclude dependency-surface pin with no UI — covered by PHPUnit (tests/unit/Service/OfficeTemplateChartTest.php::testSetChartCapabilityPinned)

### Requirement: Formatted tables from object collections (REQ-DDTCH-004)

Twig templates MUST be able to render an object collection as a consistently
formatted table via a sandboxed `data_table(collection, columns, options)`
function, where `columns` selects and orders fields as `{key, label, align?,
format?}` with formatting options `text`, `number`, `date`, and `currency`
applied via explicit options (NL conventions, independent of environment
locale). Every cell value MUST be escaped; an empty collection MUST render a
localised empty-state row rather than nothing. On the office path, collection
tables reuse REQ-DDOTA-003's native row cloning — no new office table
mechanism is introduced, and the author documentation MUST include the
row-cloning collection-table recipe.

#### Scenario: Collection renders with selected, formatted columns

- GIVEN a resolved collection of Demostad dossier objects and a three-column definition with a `date` and a `currency` format
- WHEN `data_table(...)` renders
- THEN the table shows only the selected columns in order, with NL-formatted date and currency values and escaped cell content
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Empty collection shows an empty state

- GIVEN an empty collection
- WHEN the table renders
- THEN a single localised empty-state row appears instead of an empty or absent table
- @e2e exclude formatting pin; covered by PHPUnit (tests/unit/Service/Charts/TableHtmlRendererTest.php::testEmptyState)

### Requirement: Image placeholders resolve from Nextcloud files under the caller's ACLs (REQ-DDTCH-006)

Templates MUST be able to place images from Nextcloud files: `nc_image(fileId,
options)` in Twig (embedded as a data URI) and `${image:key}` on the office
path (inserted via `TemplateProcessor::setImageValue()` with declared
dimensions). Resolution MUST run as the generating user through the user
folder — an image placeholder MUST NOT read any file the requesting user
cannot read. Only raster formats (png, jpeg, gif, webp) are accepted —
user-supplied SVG files MUST be rejected — and a configurable size cap
(`filinq.templates.max_image_bytes`, default 5 MB) MUST be enforced. Any
resolution failure (unreadable, missing, oversized, wrong type) MUST render a
visible `[image unavailable: reason]` marker plus a generation warning —
never a silent blank and never an ACL bypass.

#### Scenario: Readable raster image is embedded on both paths

- GIVEN a PNG the generating user can read
- WHEN a Twig template with `nc_image(...)` and an office template with `${image:logo}` are generated
- THEN both outputs contain the image at the placeholder position
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Unreadable file degrades to a marker, not a bypass

- GIVEN a file id the generating user has no access to
- WHEN the document is generated
- THEN the output shows `[image unavailable: …]` at the position and a warning is reported
- AND no byte of the file's content appears in any output
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

### Requirement: Chart output degrades honestly across output formats (REQ-DDTCH-007)

Chart rendering MUST be SVG-first on the HTML/PDF path and MUST NOT silently
lose charts on conversions that cannot carry inline SVG: for HTML→ODF and
HTML→DOCX conversions the chart SVG is rasterised to PNG locally via the
LibreOffice headless backend (under the existing soffice serialization lock)
and substituted before conversion; when rasterisation is unavailable, the
chart position MUST show the visible error marker and the generation warnings
MUST name the affected format. Office-path native charts are unaffected (the
chart is part of the DOCX itself). No output format may ever drop a chart
without a reported warning.

#### Scenario: ODF output of a Twig chart template carries a rasterised chart

- GIVEN a Twig template with a chart and an instance with a working LibreOffice backend
- WHEN `odf` output is generated
- THEN the ODT shows the chart as an embedded PNG at the chart position
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Missing rasteriser produces a warning, not a silent gap

- GIVEN an HTML→DOCX conversion where PNG rasterisation is unavailable
- WHEN the job runs
- THEN the chart position carries the visible marker and the warnings name `docx`
- @e2e exclude backend fault-injection not browser-drivable — covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php::testChartRasterFallbackWarning)
