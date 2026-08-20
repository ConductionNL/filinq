# document-creatie-sjablonen Specification (delta)

---
status: proposed
---

## Purpose

Extend output-format support (REQ-DCS-03) and the generation API/audit
(REQ-DCS-07, DCS-072): `docx` becomes a first-class, genuinely editable
generation format; `html` becomes an output format for `office` templates too
(LibreOffice DOCX→HTML — full format parity, REQ-DDMFO-007); `options.formats`
produces multiple outputs from a single render pass; and the
`generatedDocument` audit object records every produced output. Existing
single-format behaviour is unchanged; this delta only ADDs.

## ADDED Requirements

### Requirement: One render request produces multiple formats from a single render pass (REQ-DDMFO-001)

`POST /api/documents/generate` MUST accept `options.formats` (array of at
least one valid output format, deduplicated) and produce every requested
format from a **single** render pass: the template is rendered/filled exactly
once to its canonical intermediate (rendered HTML for `twig` templates, the
filled DOCX for `office` templates), and each requested format is converted
from that intermediate. Outputs MUST be written as Nextcloud files to the
generated-documents output folder and returned as a JSON manifest with one
entry per format (`format`, `fileId`, `fileName`, `downloadUrl`, `size`,
`status`, optional `error`) plus the shared generation warnings. A failure of
the render step MUST abort the job; a failure of an individual format
conversion MUST NOT abort the other formats (that entry reports `status:
failed` with its error). Supplying both `options.format` and
`options.formats` MUST be refused with HTTP 400, and requests using the
existing single `options.format` MUST behave byte-identically to before this
change.

#### Scenario: PDF and DOCX from one render

- GIVEN a seeded Twig template and resolved Demostad dossier data
- WHEN `POST /api/documents/generate` is called with `options.formats: ["pdf", "docx"]`
- THEN the response is a JSON manifest with two entries, both `status: generated`
- AND both referenced files exist in the output folder and derive from the same rendered HTML
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts

#### Scenario: One failing conversion does not sink the job

- GIVEN a multi-format request `["pdf", "odf"]` on an instance where the ODF conversion fails
- WHEN the job runs
- THEN the manifest reports the `pdf` entry `generated` and the `odf` entry `failed` with an error message
- @e2e exclude backend fault-injection (disabling soffice mid-job) is not browser-drivable — covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php::testPartialFormatFailure)

### Requirement: Editable DOCX is a first-class document-generation format (REQ-DDMFO-003)

The document generation path MUST accept `docx` as an output format: for
`twig` templates via the shared local HTML→DOCX LibreOffice converter
(promoted from the correspondence implementation — exactly one DOCX
conversion implementation exists in the app), and for `office` templates as
the filled source DOCX itself (native-fidelity editable passthrough, per
office-template-authoring REQ-DDOTA-003). The produced file MUST be genuine
editable WordprocessingML (openable and editable in Word/LibreOffice), never
a renamed or wrapped PDF. Generating `docx` while no capable backend is
available MUST fail with HTTP 503 (no silent substitution), and the
correspondence path's existing `docx` behaviour MUST be preserved by the
extraction.

#### Scenario: Office template delivers its filled source as editable DOCX

- GIVEN a seeded office template and a generation with format `docx`
- WHEN the output is downloaded and opened
- THEN it is the filled DOCX (merge tags replaced with resolved data) and is editable
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts

#### Scenario: Correspondence DOCX behaviour survives the extraction

- GIVEN the existing correspondence generation tests
- WHEN `CorrespondenceService` produces `docx` output through the shared converter
- THEN the produced content and error behaviour match the pre-extraction implementation
- @e2e exclude refactor-equivalence pin; covered by the existing PHPUnit correspondence suite (tests/unit/Service/CorrespondenceServiceTest.php)

### Requirement: Office templates produce HTML output via LibreOffice DOCX→HTML (REQ-DDMFO-007)

The document generation path MUST accept `html` as an output format for
`office` templates (full format parity with `twig` templates, which already
produce `html` as a render passthrough). Office HTML MUST be produced from the
filled DOCX intermediate via a shared local LibreOffice DOCX→HTML converter
(`soffice --headless --convert-to html`), which MUST reuse the cascade's
soffice serialization lock, temp-dir hygiene, and timeout discipline (exactly
one soffice invocation pattern in the app). Because it depends on LibreOffice,
office `html` MUST be gated on LibreOffice-backend availability in the matrix
(REQ-DDMFO-002) and, when no capable backend is available, a forced office
`html` generation MUST fail with HTTP 503 carrying the matrix reason (no
silent substitution). `twig` `html` output MUST remain the existing render
passthrough, unchanged.

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

### Requirement: Every produced output is recorded on the generation audit object (REQ-DDMFO-006)

Multi-format jobs MUST write exactly one `generatedDocument` object per
render carrying an `outputs` array with one `{format, fileId, status,
error?}` entry per requested format (including failed ones), with the scalar
`format` property set to the first requested format for consumers of the
existing field. The `generatedDocument` schema MUST gain `docx` in its
`format` enum and the optional `outputs` property
(`lib/Settings/docudesk_register.json`, additive document-register bump).
Single-format generations MUST keep their existing audit shape with no
`outputs` property.

#### Scenario: Audit object lists all outputs including failures

- GIVEN a multi-format job where `pdf` succeeded and `odf` failed
- WHEN the logged `generatedDocument` object is fetched
- THEN `outputs` contains both entries with their statuses and the `odf` error
- AND `format` equals the first requested format
- @e2e exclude audit-shape assertion; covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php::testMultiFormatAuditOutputs)
