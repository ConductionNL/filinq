# document-creatie-sjablonen Specification (delta)

## Purpose

The office half of multi-format output, moved here from `multi-format-output`
when that change was built (2026-09-29): office templates did not exist yet,
so their DOCX passthrough, their HTML via DOCX to HTML and their row in the
format matrix land with the office render path this change builds. The
shared pieces already exist: `Conversion\HtmlToOfficeConverter`,
`LibreOfficeHeadlessBackend::convertHtml()` (add a DOCX-input sibling with `html` as a target),
`FormatMatrixService::forTemplate()` (narrow it by `templateType`) and
`MultiFormatOutputProducer`.

## ADDED Requirements

### Requirement: Office templates produce HTML output via LibreOffice DOCX→HTML (REQ-DDMFO-007)

The generation path MUST accept `html` for `office` templates, produced from
the filled DOCX by the shared local LibreOffice DOCX→HTML converter, which
MUST reuse the cascade's soffice lock, temp-dir hygiene and timeouts (one
soffice invocation pattern in the app). Office `html` MUST be gated on
LibreOffice availability in the matrix; without it a forced office `html`
generation MUST fail with HTTP 503 and the matrix reason. `twig` `html`
output MUST stay the existing render passthrough.

#### Scenario: Office template delivers HTML via DOCX→HTML

- GIVEN a seeded office template on an instance with a working LibreOffice backend
- WHEN `POST /api/documents/generate` is called for that template with format `html`
- THEN the output is HTML derived from the filled DOCX and contains the resolved data
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts

#### Scenario: Office html fails 503 when LibreOffice is unavailable

- GIVEN a seeded office template on an instance without a working LibreOffice backend
- WHEN an `html` generation is forced for that template
- THEN the request fails HTTP 503 with the same reason the matrix reports and no other-format file is produced
- @e2e exclude requires an instance-level LibreOffice teardown — covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php::testOfficeHtmlRequiresLibreOffice)

#### Scenario: Office template delivers its filled source as editable DOCX

- GIVEN a seeded office template and a generation with format `docx`
- WHEN the output is downloaded and opened
- THEN it is the filled DOCX (merge tags replaced with resolved data) and is editable
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts

#### Scenario: Template matrix reflects the template type

- GIVEN an office template on an instance with a working LibreOffice backend
- WHEN `GET /api/templates/{id}/formats` is fetched
- THEN `docx` is offered as the editable passthrough format, `html` is offered (produced via DOCX→HTML), and `pdf`/`odf` are offered
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts

#### Scenario: Office html is LibreOffice-gated in the matrix

- GIVEN an office template on an instance without a working LibreOffice backend
- WHEN `GET /api/templates/{id}/formats` is fetched
- THEN `html`, `docx`, and `odf` are reported unavailable with a reason and only `pdf` is available
- @e2e exclude requires an instance-level LibreOffice teardown — covered by PHPUnit (tests/unit/Service/FormatMatrixServiceTest.php::testOfficeHtmlGatedOnLibreOffice)

#### Scenario: The converter command
- GIVEN the shared converter
- WHEN it converts a filled DOCX to HTML
- THEN it runs `soffice --headless --convert-to html` under the cascade's serialization lock
- @e2e exclude converter wiring; covered by PHPUnit (tests/unit/Service/MultiFormatOfficeOutputTest.php::testTheMatrixGatesOfficeHtmlOnLibreOffice, tests/unit/Service/Conversion/HtmlToOfficeConverterTest.php::testWithoutLibreOfficeItIsA503WithTheMatrixReasonAndSofficeNeverRuns)

