# template-charts Specification (delta)

---
status: done
---

## Purpose

Charts, formatted tables and images from Nextcloud files in documents generated
from Twig templates, rendered fully locally (no external chart services, per
config): bar, line and pie charts from register-bound or inline data as
app-generated inline SVG through mPDF, tables from object collections, and
images an author can open themselves. The Twig sandbox extension itself is
specified as a `pdf-generation` delta (REQ-DDTCH-005, this change). The office
path (native DOCX charts, `${image:key}` placeholders and the row-cloning table
recipe) moved to the `office-charts-and-images` change, because it fills
templates through the office pipeline that `office-template-authoring` has not
built yet. Evidence: Carbone charts and pivots, Fluent, docxtemplater chart
module (competitor theme #9).

## ADDED Requirements

### Requirement: A local, deterministic SVG chart renderer (REQ-DDTCH-001)

The app MUST provide a chart renderer that produces `bar`, `line`, and `pie`
charts as self-contained SVG using pure local PHP: no JavaScript, no network
access, no external chart service, and no GD/Imagick dependency. Input is
`{labels, series}` data with options for title, dimensions, palette, legend,
and value formatting. Rendering MUST be deterministic (identical input yields
byte-identical SVG), MUST escape every data-derived text node, MUST enforce a
configurable point cap (`filinq.charts.max_points`, default 1000), and MUST
emit a conservative SVG subset (shapes, paths, text, solid fills; no scripts,
gradients, filters, or external references). The default palette MUST derive
from the active huisstijl `primaryColor` when a `huisstijlId` is in effect,
falling back to a fixed accessible palette when the seed color cannot yield
sufficient contrast.

#### Scenario: Same data renders byte-identical SVG

- GIVEN a bar-chart data fixture
- WHEN the renderer is invoked twice (and across container restarts)
- THEN both outputs are byte-identical and match the committed snapshot
- @e2e exclude deterministic-output pin with no UI surface, covered by PHPUnit snapshot tests (tests/unit/Service/Charts/ChartSvgRendererTest.php)

#### Scenario: Data-derived labels cannot inject markup

- GIVEN a series whose label contains `</svg><script>` content
- WHEN the chart is rendered
- THEN the label appears escaped as literal text and the SVG contains no script element
- @e2e exclude injection pin, covered by PHPUnit (tests/unit/Service/Charts/ChartSvgRendererTest.php::testLabelsEscaped)

### Requirement: Charts render in Twig templates from context data (REQ-DDTCH-002)

Twig templates MUST be able to render charts via a sandboxed `chart(type,
data, options)` function whose data comes from the standard resolved template
context: register-bound data resolved through the existing `dataRefs`
mechanism, or inline arrays built with existing whitelisted filters, with no
separate chart-data fetch path. The returned inline SVG MUST appear in HTML
output and preview and MUST render in PDF output through mPDF. An invalid
chart type or malformed data MUST produce a visible inline `[chart error:
reason]` marker plus an entry in the generation warnings, never a fatal
error and never a silent blank. The marker text MUST be translated into the
generating user's language when a translation exists.

#### Scenario: Bar chart from register-bound data in preview and PDF

- GIVEN a Twig template calling `chart('bar', …)` over dossier data in its context
- WHEN an author previews the template
- THEN the HTML preview contains the chart SVG
- AND a PDF generated from it renders the same chart through mPDF
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Malformed chart data degrades visibly

- GIVEN a template calling `chart('bar', data)` where `data` lacks `series`
- WHEN the document is generated
- THEN the output shows `[chart error: …]` at the chart position
- AND the generation response's warnings name the failure
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: A Dutch author reads the marker in Dutch

- GIVEN a user whose language is Dutch
- WHEN a chart in their template has no data
- THEN the marker reads `grafiekfout: geen gegevens`
- @e2e exclude translation pin, covered by PHPUnit (tests/unit/Service/TemplateRendererTest.php::testMarkersAndEmptyRowAreTranslated)

### Requirement: Formatted tables from object collections (REQ-DDTCH-004)

Twig templates MUST be able to render an object collection as a consistently
formatted table via a sandboxed `data_table(collection, columns, options)`
function, where `columns` selects and orders fields as `{key, label, align?,
format?}` with formatting options `text`, `number`, `date`, and `currency`
applied via explicit options (NL conventions, independent of environment
locale). Every cell value MUST be escaped; an empty collection MUST render a
localised empty-state row rather than nothing.

#### Scenario: Collection renders with selected, formatted columns

- GIVEN a resolved collection of dossier objects and a three-column definition with a `date` and a `currency` format
- WHEN `data_table(...)` renders
- THEN the table shows only the selected columns in order, with NL-formatted date and currency values and escaped cell content
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Empty collection shows an empty state

- GIVEN an empty collection
- WHEN the table renders
- THEN a single localised empty-state row appears instead of an empty or absent table
- @e2e exclude formatting pin, covered by PHPUnit (tests/unit/Service/Charts/TableHtmlRendererTest.php::testEmptyState and tests/unit/Service/TemplateRendererTest.php::testMarkersAndEmptyRowAreTranslated)

### Requirement: Image placeholders resolve from Nextcloud files under the caller's ACLs (REQ-DDTCH-006)

Twig templates MUST be able to place images from Nextcloud files with
`nc_image(fileId, options)`, embedded as a data URI, with `alt`, `width` and
`height` options. Resolution MUST run as the generating user through the user
folder: an image placeholder MUST NOT read any file the requesting user
cannot read. Only raster formats (png, jpeg, gif, webp), recognised from the
file's content rather than its name, are accepted; user-supplied SVG files
MUST be rejected; and a configurable size cap
(`filinq.templates.max_image_bytes`, default 5 MB) MUST be enforced before
the file is read. Any resolution failure (unreadable, missing, oversized,
wrong type) MUST render a visible `[image unavailable: reason]` marker plus a
generation warning, never a silent blank and never an ACL bypass. A missing
file and a file the user may not read MUST give the same reason, so the
marker does not reveal whether someone else's file exists.

#### Scenario: Readable raster image is embedded

- GIVEN a PNG the generating user can read
- WHEN a Twig template with `nc_image(...)` is previewed or generated
- THEN the output contains the image at the placeholder position as a data URI
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Unreadable file degrades to a marker, not a bypass

- GIVEN a file id the generating user has no access to
- WHEN the document is generated
- THEN the output shows `[image unavailable: …]` at the position and a warning is reported
- AND no byte of the file's content appears in any output
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: An SVG renamed to png is refused

- GIVEN a file named `logo.png` whose content is SVG
- WHEN a template embeds it with `nc_image(...)`
- THEN the output shows `[image unavailable: not a raster image]`
- @e2e exclude content-sniffing pin, covered by PHPUnit (tests/unit/Service/Charts/TemplateImageResolverTest.php::testAnSvgFileIsRejectedEvenWhenNamedPng)

### Requirement: Chart output degrades honestly across output formats (REQ-DDTCH-007)

Chart rendering MUST be SVG-first on the HTML and PDF path and MUST NOT
silently lose charts on conversions that cannot carry inline SVG: before a
document is converted from HTML to ODT, and before a letter is converted
from HTML to DOCX, every inline chart SVG is rasterised to PNG locally with
the LibreOffice headless binary (under the same host-wide lock the PDF
conversion backend holds) and substituted at its position. When
rasterisation fails or the converter is busy, the chart position MUST show
the visible error marker and the generation warnings MUST name the affected
format. No output format may ever drop a chart without a reported warning.

#### Scenario: ODF output of a Twig chart template carries a rasterised chart

- GIVEN a Twig template with a chart and an instance with a working LibreOffice backend
- WHEN `odf` output is generated
- THEN the ODT shows the chart as an embedded PNG at the chart position
- @e2e exclude binary ODT inspection with no browser surface, covered by PHPUnit (tests/unit/Service/Charts/SvgRasterizerTest.php::testAChartBecomesAnEmbeddedPngAtItsPosition and tests/unit/Service/DocumentRenderPipelineOdfTest.php::testOdfOutputRasterizesAndReportsWarnings)

#### Scenario: Missing rasteriser produces a warning, not a silent gap

- GIVEN an HTML to DOCX conversion where PNG rasterisation fails
- WHEN the job runs
- THEN the chart position carries the visible marker and the warnings name `docx`
- @e2e exclude backend fault-injection not browser-drivable, covered by PHPUnit (tests/unit/Service/Charts/SvgRasterizerTest.php::testAFailedConversionLeavesAMarkerAndAWarningNamingTheFormat)
