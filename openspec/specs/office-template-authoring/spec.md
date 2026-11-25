# office-template-authoring Specification

## Purpose

Accept real DOCX/ODT office files with `${field}` merge tags as first-class
Filinq templates alongside the existing Twig/HTML type: upload with tag
extraction and validation against a bound register schema, rendering through
the existing PDF conversion cascade, reusable text fragments (bouwstenen),
and bulk import/migration tooling for existing house-style template estates.
The goal is that the communications department owns templates without
developer help.

## Requirements

### Requirement: Office files are accepted as first-class templates (REQ-DDOTA-001)

The app MUST accept a DOCX or ODT upload as a template of `templateType:
office` via `POST /api/templates/office`, storing the source as a Nextcloud
file referenced by `sourceFileId` with a sha256 `contentHash`. Merge tags use
the PhpWord macro syntax `${field}`; the upload MUST extract them and persist
them as `mergeFields`. Unsafe uploads MUST be refused with HTTP 422. Existing
Twig/HTML templates MUST be unaffected: absent `templateType` reads `twig`.

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

#### Scenario: The tag syntax and how tags are read
- GIVEN an office source
- WHEN its tags are extracted
- THEN dotted paths such as `${aanvrager.naam}` and repeating regions `${block}`…`${/block}` are recognised
- AND the tags are read with PhpWord `TemplateProcessor::getVariables()`
- @e2e exclude extraction detail; covered by PHPUnit (tests/unit/Service/OfficeTemplate/MergeTagReaderTest.php::testTheTagsAreReadOnceEachWithoutBlockClosings)

#### Scenario: What counts as an unsafe upload
- GIVEN an upload that is macro-enabled (`.docm`/`.dotm` or containing `vbaProject.bin`), over the configured size cap, or whose sniffed mime type contradicts its extension
- WHEN it is uploaded
- THEN it is refused with HTTP 422 naming the reason
- @e2e exclude refusal matrix; covered by PHPUnit (tests/unit/Service/OfficeTemplate/OfficeSourceGuardTest.php)

#### Scenario: An ODT is converted by the LibreOffice backend
- GIVEN an ODT upload
- WHEN it is stored
- THEN it is normalised to DOCX through the LibreOffice headless backend, the original ODT is retained, and the response flags the conversion
- @e2e exclude conversion path; covered by PHPUnit (tests/unit/Service/OfficeTemplate/OfficeTemplateServiceTest.php::testAnOdtIsConvertedAndKept)

#### Scenario: A template without a type stays Twig
- GIVEN a template object without `templateType`
- WHEN it is read anywhere
- THEN it is treated as `twig`
- @e2e exclude default reading; covered by PHPUnit (tests/unit/Service/OfficeTemplate/OfficeTemplateRendererTest.php::testAbsentTypeReadsAsTwig)

### Requirement: Uploaded tags are validated against the bound register schema (REQ-DDOTA-002)

The app MUST validate extracted merge tags against the template's bound
register schema (`boundRegister`/`boundSchema`, OpenRegister slugs) on every
office upload, classifying each tag as `known`, `fragment` or `unknown` and
persisting the result as `tagReport`. Unknown tags MUST be reported at the
configured severity; a template without a binding MUST never be blocked. The
schema properties MUST be read from OpenRegister, not duplicated in Filinq.

#### Scenario: Unknown tag is reported on upload

- GIVEN a template bound to register `dossier` / schema `dossier`
- AND an uploaded DOCX containing the tag `${aanvraagr.naam}` (typo) which matches no schema property
- WHEN the upload completes under the default `warning` severity
- THEN the template is created and its `tagReport` lists `aanvraagr.naam` as `unknown`
- AND the template detail UI shows the unknown-tag warning
- @e2e tests/e2e/spec-coverage/office-template-authoring.spec.ts

#### Scenario: Blocking severity refuses the upload

- GIVEN `filinq.templates.unknown_tag_severity` set to `blocking`
- AND an upload for a bound template containing an unknown tag
- WHEN the upload is processed
- THEN the response is HTTP 422 listing each unknown tag
- AND no template object is created
- @e2e exclude admin-config permutation of the same validation path; covered by PHPUnit (tests/unit/Service/OfficeTemplateServiceTest.php)

#### Scenario: How tags are classified and how severity applies
- GIVEN a bound template
- WHEN its tags are classified
- THEN `known` matches a property or dotted sub-path of the bound schema, `fragment` has the `fragment:` prefix, and anything else is `unknown`
- AND `filinq.templates.unknown_tag_severity` decides the reaction: `warning` (default) stores the report, `blocking` refuses the upload with HTTP 422 listing the unknown tags
- AND a template without a binding gets a report stating that no schema validation was performed
- @e2e exclude classification rules; covered by PHPUnit (tests/unit/Service/OfficeTemplate/MergeTagClassifierTest.php)

### Requirement: Office templates render through the conversion cascade (REQ-DDOTA-003)

Document generation MUST render office templates through the existing
conversion cascade, on the generate, preview and bulk routes: data resolves
through `DataResolverService`, tags are filled with PhpWord, and the output
is the filled DOCX or a PDF/PDF-A converted from it. A tag without a value
MUST render empty with a warning naming it. Filling MUST run no
template-authored logic and make no external network call, and each
generation MUST log a `generatedDocument` with the template type.

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

#### Scenario: How tags are filled
- GIVEN an office template and resolved data
- WHEN it is filled
- THEN scalars are written with `setValue` and XML-escaped, and arrays repeat with `cloneBlock`/`cloneRow`
- AND PDF and PDF/A come from `PdfConversionService::convertToPdf()` on the filled DOCX
- @e2e exclude filling detail; covered by PHPUnit (tests/unit/Service/OfficeTemplate/OfficeTemplateFillerTest.php, tests/unit/Service/OfficeTemplate/OfficeTemplateRendererTest.php::testPdfGoesThroughTheCascadeAndCleansUp)

### Requirement: Reusable text fragments (bouwstenen) resolve in templates (REQ-DDOTA-004)

The app MUST provide CRUD for `textFragment` objects in the templates
register, each with a slug unique per namespace. Any template, office or
Twig, MUST be able to reference a fragment as `${fragment:slug}`, resolved
before field filling. A missing fragment MUST render a visible
`[ontbrekende bouwsteen: <slug>]` marker and a generation warning. The Twig
sandbox whitelist MUST NOT be extended for fragment support.

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

#### Scenario: Fragment fields and nesting
- GIVEN a fragment with name, slug, content, namespace and optionally category, tags and language
- WHEN a template that references it is rendered
- THEN `${field}` tags inside the fragment are filled from the same data, one level deep
- AND a fragment that references another fragment is not expanded (no recursive nesting)
- @e2e exclude resolution rules; covered by PHPUnit (tests/unit/Service/OfficeTemplate/OfficeTemplateFillerTest.php::testFragmentsAreFilledAndAMissingOneIsMarked, tests/unit/Service/OfficeTemplate/TextFragmentServiceTest.php)

### Requirement: Bulk import migrates existing template estates (REQ-DDOTA-005)

The app MUST provide `POST /api/templates/import` for a ZIP or a Files-app
folder of DOCX/ODT templates and optional fragments, run as an asynchronous
job persisted as a `templateImportJob` (never in memory) and queryable via
`GET /api/templates/import/{jobId}`. The UI MUST offer a step that maps
unknown tags to schema properties, stored as `fieldMap` and applied at fill
time. The flow MUST handle estates of hundreds of files.

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

#### Scenario: What the job creates and reports
- GIVEN an import job
- WHEN it runs
- THEN each office file becomes an office template with tag extraction and validation per REQ-DDOTA-001/002, and each `fragments/` entry becomes a `textFragment`
- AND a per-file report records the created id, tag counts, unknown tags and failures; a failing file is skipped and the job continues
- @e2e exclude job detail; covered by PHPUnit (tests/unit/Service/OfficeTemplate/TemplateImportServiceTest.php::testARunImportsTemplatesAndFragmentsAndReportsEachFile)
