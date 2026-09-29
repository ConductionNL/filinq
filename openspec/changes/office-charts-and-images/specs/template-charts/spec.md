# template-charts Specification (delta)

## ADDED Requirements

### Requirement: Office templates get native DOCX charts (REQ-DDTCH-003)

Office templates MUST support a `${chart:key}` placeholder family, filled in
the office fill pipeline (office-template-authoring REQ-DDOTA-003 pre-pass
slot): the generation context entry `charts.key = {type, data, options}` is
rendered as a native WordprocessingML chart via PhpWord's
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
- THEN the DOCX contains a native chart part, editable in Word and LibreOffice
- AND the PDF produced through the conversion cascade displays the chart
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: PhpWord chart capability is pinned

- GIVEN the shipped composer dependencies
- WHEN the capability pin test runs
- THEN `\PhpOffice\PhpWord\TemplateProcessor::setChart` exists and accepts an `Element\Chart` for each supported type
- @e2e exclude dependency-surface pin with no UI, covered by PHPUnit (tests/unit/Service/OfficeTemplateChartTest.php::testSetChartCapabilityPinned)

### Requirement: Office image placeholders read as the generating user (REQ-DDTCH-008)

Office templates MUST support `${image:key}`: the context entry `images.key`
names a Nextcloud file id, resolved through `TemplateImageResolver` with the
same rules as `nc_image()` (the generating user's folder only, raster only by
content, size cap), and inserted with `TemplateProcessor::setImageValue()` at
the declared dimensions. A failure MUST leave the `[image unavailable: …]`
marker text and a generation warning at the placeholder.

#### Scenario: Readable image lands in the DOCX

- GIVEN an office template with `${image:logo}` and a PNG the generating user can read
- WHEN DOCX output is generated
- THEN the DOCX carries the image at the placeholder position
- @e2e tests/e2e/spec-coverage/template-charts.spec.ts

#### Scenario: Unreadable image is a marker in the DOCX

- GIVEN an office template with `${image:logo}` pointing at a file the user cannot read
- WHEN DOCX output is generated
- THEN the placeholder position reads `[image unavailable: not found or no access]` and a warning is reported
- @e2e exclude ACL fault-injection on a binary output, covered by PHPUnit (tests/unit/Service/OfficeTemplateImageTest.php)

### Requirement: Office collection tables use row cloning (REQ-DDTCH-009)

On the office path, collection tables reuse REQ-DDOTA-003's native row cloning;
no new office table mechanism is introduced. The author documentation MUST
include the row-cloning collection-table recipe.

#### Scenario: The docs show the recipe

- GIVEN an author reading `docs/features/template-charts.md`
- WHEN they look for tables in office templates
- THEN the page shows a `${block}` row-cloning example over a collection
- @e2e exclude documentation content with no app surface
