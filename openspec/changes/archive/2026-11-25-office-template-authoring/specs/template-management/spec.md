# template-management Specification (delta)

## Purpose

Extend the existing template-management capability (Twig/HTML templates as
OpenRegister objects with versioning, diff/restore, locking, duplication, and
preview — REQ-TMPL-01..12) so the same data model and lifecycle features
cover `office` templates introduced by the `office-template-authoring`
change. Existing Twig/HTML requirements are unchanged; this delta only ADDs
requirements.

## ADDED Requirements

### Requirement: Template data model carries the office-template extension (REQ-DDOTA-006)

The `template` schema MUST be extended additively (templates register
`2.0.0` → `2.1.0`) with the office-template properties, and `templateVersion`
MUST gain `sourceFileId` and `contentHash` so a snapshot pins the exact
office revision. All new properties MUST be optional; an absent
`templateType` MUST read as `twig` everywhere. For `office` templates,
`content` holds the extracted text projection of the source, never the
binary.

#### Scenario: Existing Twig templates are untouched by the schema bump

- GIVEN a template object created before this change (no `templateType`)
- WHEN the register import runs on boot and the template is listed and rendered
- THEN the object validates against the extended schema
- AND it behaves as `templateType: twig` in every code path
- @e2e exclude schema-migration compatibility is not browser-observable; covered by PHPUnit register-drift tests (tests/unit/Service/TemplateServiceTest.php) and tests/validate-manifest.js

#### Scenario: Office template object carries source reference and hash

- GIVEN an uploaded office template
- WHEN its object is fetched via `GET /api/templates/{id}`
- THEN it contains `templateType: office`, an integer `sourceFileId`, a `contentHash`, and its `mergeFields`
- @e2e tests/e2e/spec-coverage/templates.spec.ts

#### Scenario: The new properties and their shapes
- GIVEN the shipped `template` schema
- WHEN its properties are read
- THEN it has `templateType` (enum `twig` | `office`, default `twig`), `sourceFileId` (integer), `contentHash` (sha256 string), `boundRegister`/`boundSchema` (slugs), `mergeFields` (array of tag names), `fieldMap` (object, tag → property) and `tagReport` (object)
- @e2e exclude schema shape; covered by PHPUnit (tests/unit/Settings/OfficeTemplateSeedTest.php::testTheOfficeDataModelIsPinned)

### Requirement: Versioning, locking, preview, and duplication work for office templates (REQ-DDOTA-007)

The existing template lifecycle MUST apply to `office` templates with
identical contracts: versioning and restore, diff, advisory locking, preview
and duplication, each as the scenarios below state.

#### Scenario: New source upload creates a version and restore brings it back

- GIVEN an office template with source revision A
- WHEN revision B is uploaded and then version A is restored via `POST /api/templates/{id}/versions/{versionId}/restore`
- THEN a snapshot of A existed before B took effect, an auto-snapshot of B is written on restore
- AND the template's `sourceFileId`/`contentHash` again reference revision A's content
- @e2e tests/e2e/spec-coverage/templates.spec.ts

#### Scenario: Office template preview renders through the cascade

- GIVEN an office template with `${aanvrager.naam}` and preview data `{"aanvrager": {"naam": "A. de Vries"}}`
- WHEN the preview endpoint is called
- THEN the returned preview shows "A. de Vries" at the tag position
- @e2e tests/e2e/spec-coverage/templates.spec.ts

#### Scenario: Duplicate copies the source file

- GIVEN an office template
- WHEN it is duplicated
- THEN the duplicate references a NEW `sourceFileId` whose content hash equals the original's `contentHash`
- AND the duplicate has no version history and no lock
- @e2e exclude file-copy integrity assertion; covered by PHPUnit (tests/unit/Service/TemplateServiceTest.php)

#### Scenario: Versioning, diff and locking
- GIVEN an office template
- WHEN a new source is uploaded
- THEN a `templateVersion` snapshot is created whose `sourceFileId`/`contentHash` reference an immutable copy of the prior source, and restore re-points the template with the existing auto-snapshot-then-restore behaviour (REQ-TMPL-08)
- AND `GET /api/templates/{id}/versions/diff` returns the two text projections (binary sources are not diffed)
- AND the advisory lock (REQ-TMPL-11) gates a source re-upload exactly as it gates content updates
- @e2e exclude lifecycle detail; covered by PHPUnit (tests/unit/Service/TemplateVersionServiceTest.php::testAnOfficeVersionKeepsAndRestoresItsSource, ::testGetDiffReturnsBothVersions, tests/unit/Controller/OfficeTemplatesControllerTest.php::testALockedTemplateRefusesANewSource)

#### Scenario: Preview and duplication
- GIVEN an office template
- WHEN `POST /api/templates/{id}/preview` is called, or the template is duplicated
- THEN the preview fills the tags with the provided (or seed) data and renders through the conversion cascade
- AND a duplicate copies the source to a new file, keeps `templateType`, `mergeFields`, `fieldMap` and the schema binding, is named with " (kopie)" and has no version history (REQ-TMPL-10)
- @e2e exclude covered by PHPUnit (tests/unit/Controller/OfficeTemplatesControllerTest.php::testThePreviewIsTheFilledPdf, tests/unit/Service/TemplateServiceLockTest.php::testDuplicateSavesAFreshCopy)

