# office-template-authoring Specification (delta)

---
status: proposed
---

## Purpose

Accept real DOCX/ODT office files with `${field}` merge tags as first-class
DocuDesk templates alongside the existing Twig/HTML type: upload with tag
extraction and validation against a bound register schema, rendering through
the existing PDF conversion cascade, reusable text fragments (bouwstenen),
and bulk import/migration tooling for existing house-style template estates.
The goal is that the communications department owns templates without
developer help.

## ADDED Requirements

### Requirement: Office files are accepted as first-class templates (REQ-DDOTA-001)

The app MUST accept a DOCX or ODT file upload as a template of
`templateType: office` via `POST /api/templates/office` (multipart), storing
the office source as a Nextcloud file referenced from the template object by
`sourceFileId` together with a sha256 `contentHash`. Merge tags MUST use the
PhpWord macro syntax `${field}` (dotted paths such as `${aanvrager.naam}`
allowed; repeating regions via `${block}`…`${/block}`); the upload MUST
extract all tags with PhpWord `TemplateProcessor::getVariables()` and persist
them as `mergeFields` on the template. ODT uploads MUST be normalised to DOCX
via the LibreOffice headless backend at upload time (the original ODT
retained; the response MUST flag the conversion). Uploads MUST be rejected
with HTTP 422 when macro-enabled (`.docm`/`.dotm` or containing
`vbaProject.bin`), when exceeding the configured size cap, or when the
sniffed mime type contradicts the extension. Existing Twig/HTML templates
MUST be unaffected: absent `templateType` MUST read as `twig`.

#### Scenario: Communications officer uploads a DOCX house-style template

- GIVEN an authenticated user with a DOCX containing `${aanvrager.naam}` and `${besluit.datum}`
- WHEN the file is uploaded to `POST /api/templates/office` with `name` and `namespace`
- THEN a template object is created with `templateType: office`, a `sourceFileId`, a `contentHash`, and `mergeFields` containing both tags
- AND the template appears in `GET /api/templates` alongside Twig templates
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: Macro-enabled upload is rejected

- GIVEN a `.docm` file (or a DOCX whose package contains `vbaProject.bin`)
- WHEN it is uploaded as an office template
- THEN the response is HTTP 422 naming the macro rejection reason
- AND no template object and no stored file is created
- @e2e exclude negative-path file forging (crafting a macro container) is not browser-drivable in Playwright; covered by PHPUnit (tests/unit/Service/OfficeTemplateServiceTest.php) against fixture files in tests/sample-documents/

#### Scenario: ODT upload is normalised and flagged

- GIVEN an ODT template containing `${zaaknummer}`
- WHEN it is uploaded
- THEN the canonical source stored is a DOCX with the tag intact and the original ODT is retained
- AND the upload response carries a `converted: true` flag
- @e2e exclude conversion fidelity requires the LibreOffice binary in the test container; covered by PHPUnit integration test gated on soffice availability (tests/unit/Service/OfficeTemplateServiceTest.php)

### Requirement: Uploaded tags are validated against the bound register schema (REQ-DDOTA-002)

The app MUST validate extracted merge tags against the bound register schema.
A template MAY declare `boundRegister` and `boundSchema` (OpenRegister
slugs). On every office upload (create and new-version), the service MUST
classify each extracted tag as `known` (matches a property or dotted
sub-path of the bound schema), `fragment` (`fragment:` prefix), or `unknown`,
and MUST persist the structured result as `tagReport` on the template.
Unknown tags on a bound template MUST be reported at the severity configured
in `docudesk.templates.unknown_tag_severity` (`warning` default, `blocking`
optional); at `blocking` the upload MUST be refused with HTTP 422 listing the
unknown tags. A template without a schema binding MUST never be blocked on
tag validation and its report MUST state that no schema validation was
performed. The schema properties MUST be read from OpenRegister (no property
list duplicated in DocuDesk).

#### Scenario: Unknown tag is reported on upload

- GIVEN a template bound to register `dossier` / schema `dossier`
- AND an uploaded DOCX containing the tag `${aanvraagr.naam}` (typo) which matches no schema property
- WHEN the upload completes under the default `warning` severity
- THEN the template is created and its `tagReport` lists `aanvraagr.naam` as `unknown`
- AND the template detail UI shows the unknown-tag warning
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: Blocking severity refuses the upload

- GIVEN `docudesk.templates.unknown_tag_severity` set to `blocking`
- AND an upload for a bound template containing an unknown tag
- WHEN the upload is processed
- THEN the response is HTTP 422 listing each unknown tag
- AND no template object is created
- @e2e exclude admin-config permutation of the same validation path; covered by PHPUnit (tests/unit/Service/OfficeTemplateServiceTest.php)

### Requirement: Office templates render through the conversion cascade (REQ-DDOTA-003)

Document generation MUST render office templates through the existing
conversion cascade. `DocumentService::generateDocument()` (and the
corresponding `documents/generate`, `preview`, and `bulk` endpoints) MUST support
`templateType: office`: resolve data through the existing
`DataResolverService` contract, fill tags with PhpWord `TemplateProcessor`
(`setValue` for scalars — XML-escaped, `cloneBlock`/`cloneRow` for arrays),
and produce output as `docx` (the filled file) or `pdf`/`pdfa` by converting
the filled DOCX through `PdfConversionService::convertToPdf()` (the existing
backend cascade). Tags with no value in the resolved data MUST render as
empty with a generation warning naming the tag. Filling MUST execute no
template-authored logic and MUST make no external network call. Each office
generation MUST log a `generatedDocument` object recording the template type.

#### Scenario: Office template generates a PDF via the cascade

- GIVEN an office template with tags `${aanvrager.naam}` and `${besluit.datum}` and a dataRef resolving both
- WHEN `POST /api/documents/generate` is called with `format: "pdf"`
- THEN the filled DOCX is converted by the conversion cascade and a PDF is returned
- AND a `generatedDocument` object is logged with the template id, version, and type
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: Missing data yields a warning, not silent loss

- GIVEN a generation whose resolved data lacks `besluit.datum`
- WHEN the document is generated
- THEN the output renders the tag position as empty
- AND the response `warnings` name `besluit.datum`
- @e2e exclude warning-payload assertion on the generation API; covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php)

### Requirement: Reusable text fragments (bouwstenen) resolve in templates (REQ-DDOTA-004)

The app MUST provide CRUD for `textFragment` objects (name, unique
per-namespace `slug`, `content`, `namespace`, `category`, `tags`,
`language`) stored in the templates register via OpenRegister. A template —
office or Twig — MUST be able to reference a fragment as `${fragment:slug}`;
rendering MUST resolve fragment references in a pre-processing pass before
field filling (fragments MAY themselves contain `${field}` tags, one level
deep — no recursive fragment nesting). A missing fragment MUST render a
visible `[ontbrekende bouwsteen: <slug>]` marker and produce a generation
warning, never silent omission. The Twig sandbox whitelist MUST NOT be
extended for fragment support.

#### Scenario: Fragment content is rendered into a generated document

- GIVEN a fragment `ondertekening-burgemeester` and an office template referencing `${fragment:ondertekening-burgemeester}`
- WHEN a document is generated
- THEN the fragment's content appears at the reference position
- AND `${field}` tags inside the fragment are filled from the same data context
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: Missing fragment is visible and warned

- GIVEN a template referencing `${fragment:bestaat-niet}`
- WHEN a document is generated
- THEN the output contains `[ontbrekende bouwsteen: bestaat-niet]`
- AND the generation response carries a warning naming the slug
- @e2e exclude negative-path rendering assertion; covered by PHPUnit (tests/unit/Service/OfficeTemplateServiceTest.php)

### Requirement: Bulk import migrates existing template estates (REQ-DDOTA-005)

The app MUST provide `POST /api/templates/import` accepting a ZIP upload (or
a Files-app folder path) of DOCX/ODT templates and optional text fragments,
processed as an asynchronous job whose state is persisted as a
`templateImportJob` object via OpenRegister (never in memory). The job MUST
create one office template per office file (running tag
extraction/validation per REQ-DDOTA-001/002), import `fragments/` entries as
`textFragment` objects, and record a per-file report (created id, tag
counts, unknown tags, failures — failures skip the file and continue). Job
progress MUST be queryable via `GET /api/templates/import/{jobId}`. The UI
MUST provide an interactive mapping step where the operator maps unknown
tags to bound-schema properties, persisted as `fieldMap` on the template and
applied as a tag→property aliasing layer at fill time. The flow MUST handle
estates of hundreds of files (Den Helder scale: 486 templates + 433
fragments).

#### Scenario: ZIP of house-style templates imports with a report

- GIVEN a ZIP containing three DOCX templates and a `fragments/` folder with two text fragments
- WHEN it is posted to `POST /api/templates/import`
- THEN a `templateImportJob` is created and progresses to `completed`
- AND three office templates and two fragments exist, each report row naming its tags and unknown tags
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: Operator maps an unknown tag interactively

- GIVEN an imported template whose `tagReport` lists `naam_aanvrager` as unknown against bound schema `dossier`
- WHEN the operator maps `naam_aanvrager` to the schema property `aanvrager.naam` in the import wizard
- THEN the mapping is stored in the template's `fieldMap`
- AND a subsequent generation fills `${naam_aanvrager}` from `aanvrager.naam`
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: A corrupt file does not abort the import

- GIVEN a ZIP where one of three files is not a valid DOCX
- WHEN the import job runs
- THEN the two valid files import normally
- AND the report marks the corrupt file as failed with a reason
- @e2e exclude corrupt-fixture negative path; covered by PHPUnit (tests/unit/Service/TemplateImportServiceTest.php)
