# multi-format-output Specification (delta)

---
status: proposed
---

## Purpose

Format-capability honesty around document generation: a per-instance and
per-template output-format matrix computed from live conversion-backend
availability, surfaced to the generation and correspondence flows so clerks
choose from what is actually producible. The multi-format render contract
itself extends `document-creatie-sjablonen` (REQ-DDMFO-001/003/006/007, this
change) and the backend introspection extends `pdf-conversion`
(REQ-DDMFO-005, this change). Evidence: Docmosis/Carbone/Fluent simultaneous
renders (competitor theme #5); municipalities exchange editable DOCX
inter-municipally.

## ADDED Requirements

### Requirement: Format availability is reported as a capability matrix (REQ-DDMFO-002)

The app MUST expose a format-capability matrix at `GET
/api/documents/formats` (instance level) and `GET /api/templates/{id}/formats`
(template level), reporting for each output format `{available: bool,
reason?: string}`. The matrix MUST be computed from live conversion-backend
availability: the LibreOffice-dependent formats `odf`, `docx`, and — for
`office` templates — `html` (produced via DOCX→HTML, REQ-DDMFO-007) are
available iff a capable LibreOffice backend reports available. At template
level the instance matrix MUST be intersected with the formats the template's
`templateType` supports; every output format (`pdf`/`odf`/`docx`/`html`) MUST
be reachable for both `twig` and `office` templates subject to backend
availability (`html` for a `twig` template is an always-available passthrough,
`html` for an `office` template is LibreOffice-gated). Matrix responses MUST
be authenticated, read-only, and non-cacheable (`Cache-Control: no-store`).
Generating a format the matrix reports unavailable MUST fail with HTTP 503
carrying the **same** reason string the matrix reports — the app MUST NOT
silently substitute another format.

#### Scenario: LibreOffice absence disables docx and odf with a reason

- GIVEN an instance without a working LibreOffice backend
- WHEN the instance matrix is fetched and a `docx` generation is forced anyway
- THEN the matrix reports `docx` and `odf` unavailable with a reason, `pdf` and `html` available
- AND the forced request fails HTTP 503 with that same reason and no file of any other format is produced
- @e2e exclude requires an instance-level LibreOffice teardown — covered by PHPUnit with a stubbed capability report (tests/unit/Service/FormatMatrixServiceTest.php::testMatrixAndFailureShareReason)

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

### Requirement: Generation and correspondence flows drive format choice from the matrix (REQ-DDMFO-004)

The correspondence view and the document-generation review surfaces MUST
populate their output-format choices from the format-capability matrix
instead of hardcoded client-side lists (including the guided-wizard review
step when that change is installed). Unavailable formats MUST be rendered disabled with the
matrix's reason (translated), not hidden and not submittable. The
`letter-correspondence-generation` capability's own requirements (valid
formats, default format, register logging) are NOT modified by this change —
only how the existing choice list is populated (relationship documented in
design.md).

#### Scenario: Correspondence view disables an unavailable format

- GIVEN the correspondence view on an instance whose matrix reports `docx` unavailable
- WHEN the clerk opens the output-format selector
- THEN `docx` is visible but disabled with the reported reason, and cannot be submitted
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts

#### Scenario: Available formats come from the API, not the bundle

- GIVEN an instance whose matrix offers `pdf`, `html`, `odf`, and `docx`
- WHEN the correspondence view and the generation review surface render their format choices
- THEN every offered format matches the matrix response for the selected template
- AND no client-side hardcoded format list remains in the correspondence view
- @e2e tests/e2e/spec-coverage/multi-format-output.spec.ts
