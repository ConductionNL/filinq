# template-management Specification (delta)

---
status: proposed
---

## Purpose

Extend the existing template-management capability (Twig/HTML templates as
OpenRegister objects with versioning, diff/restore, locking, duplication, and
preview — REQ-TMPL-01..12) so the same data model and lifecycle features
cover `office` templates introduced by the `office-template-authoring`
change. Existing Twig/HTML requirements are unchanged; this delta only ADDs
requirements.

## ADDED Requirements

### Requirement: Template data model carries the office-template extension (REQ-DDOTA-006)

The `template` schema in `lib/Settings/filinq_register.json` MUST be
extended (templates register version `2.0.0` → `2.1.0`, additive only) with:
`templateType` (enum `twig` | `office`, default `twig`), `sourceFileId`
(integer, NC file id of the office source), `contentHash` (string, sha256 of
the source), `boundRegister` / `boundSchema` (strings, OpenRegister slugs),
`mergeFields` (array of extracted tag names), `fieldMap` (object, tag →
schema-property aliases), and `tagReport` (object, last validation result).
The `templateVersion` schema MUST gain `sourceFileId` and `contentHash` so a
version snapshot pins the exact office revision. All new properties MUST be
optional so every existing template object remains valid, and an absent
`templateType` MUST be interpreted as `twig` everywhere. For `office`
templates, `content` holds the extracted text projection of the source (for
search and diff display), never the binary.

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

### Requirement: Versioning, locking, preview, and duplication work for office templates (REQ-DDOTA-007)

The existing template lifecycle features MUST apply to `office` templates
with identical contracts:

- **Versioning**: each new office source upload for an existing template
  MUST create a `templateVersion` snapshot (via `TemplateVersionService`)
  whose `sourceFileId`/`contentHash` reference an immutable copy of the
  prior source; restore MUST re-point the template to the target version's
  source and text projection using the existing auto-snapshot-then-restore
  behaviour (REQ-TMPL-08).
- **Diff**: `GET /api/templates/{id}/versions/diff` MUST return the two
  versions' text projections for client-side diff (binary sources are not
  diffed).
- **Locking**: the advisory lock (REQ-TMPL-11) MUST gate office source
  re-upload exactly as it gates content updates.
- **Preview**: `POST /api/templates/{id}/preview` for an office template
  MUST fill the tags with provided (or seed) data and return a rendered
  preview produced through the conversion cascade.
- **Duplication**: `POST /api/templates/{id}/duplicate` MUST copy the office
  source to a new file, preserving `templateType`, `mergeFields`,
  `fieldMap`, and schema binding, with the existing " (kopie)" naming and no
  version history (REQ-TMPL-10).

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
