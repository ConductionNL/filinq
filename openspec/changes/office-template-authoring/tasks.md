# Tasks: office-template-authoring

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [ ] 1.1 Extend `lib/Settings/docudesk_register.json`: `template` schema (+`templateType`, `sourceFileId`, `contentHash`, `boundRegister`, `boundSchema`, `mergeFields`, `fieldMap`, `tagReport`), `templateVersion` (+`sourceFileId`, `contentHash`), new `textFragment` and `templateImportJob` schemas; bump templates register to 2.1.0
  - All new properties optional; absent `templateType` reads as `twig`; schema refs by slug (not PascalCase)
  - `tests/validate-manifest.js` and register import on boot both pass

- [ ] 1.2 Ship seed data: demo office template object + seed DOCX in `tests/sample-documents/`, demo `textFragment`, demo `templateImportJob` (nil-UUID pattern, Demostad flavour per design.md Seed Data)
  - Seed imports cleanly via the existing register-import mechanism

## 2. Backend services

- [ ] 2.1 `OfficeTemplateService`: upload intake (multipart), macro/`vbaProject.bin` rejection, size cap + mime sniff, ODT→DOCX normalisation via `LibreOfficeHeadlessBackend`, source storage in app folder + `sourceFileId`/`contentHash`, tag extraction via PhpWord `TemplateProcessor::getVariables()` (REQ-DDOTA-001)
  - HTTP 422 paths for macro/size/mime; `converted: true` flag on ODT normalisation

- [ ] 2.2 Tag validation against bound schema: classify known/fragment/unknown by reading schema properties from OpenRegister; persist `tagReport`; honour `docudesk.templates.unknown_tag_severity` (warning default, blocking refuses upload) (REQ-DDOTA-002)

- [ ] 2.3 Office render path in `DocumentService::generateDocument()`/`generatePreview()`: fragments pre-pass → `TemplateProcessor` fill (setValue/cloneBlock/cloneRow, `fieldMap` aliasing) → `docx` output or `PdfConversionService::convertToPdf()` for pdf/pdfa; missing-data warnings; `generatedDocument` logging with template type (REQ-DDOTA-003)
  - `huisstijlId` ignored for office templates with a warning

- [ ] 2.4 `textFragment` CRUD + fragment resolution pre-pass for both office and Twig paths, missing-fragment marker + warning; NO change to the Twig sandbox whitelists (REQ-DDOTA-004)

- [ ] 2.5 `TemplateImportService` + background job: ZIP/folder unpack, per-file template+fragment creation, per-file report, skip-and-continue on failure; `templateImportJob` state via ObjectService (REQ-DDOTA-005)

- [ ] 2.6 Lifecycle parity: version snapshot + restore re-pointing `sourceFileId`/`contentHash`, lock gating source re-upload, preview via cascade, duplicate copying the source file (REQ-DDOTA-006/007)

## 3. Routes & controller

- [ ] 3.1 Routes + `TemplatesController` actions: `POST api/templates/office`, `POST api/templates/{id}/office` (new source version), `POST api/templates/import`, `GET api/templates/import/{jobId}`, fragment CRUD routes; explicit auth attributes on every method; error shaping via `TemplateRequestHandler`
  - Every route registered in `appinfo/routes.php`; gate route-reachability/route-auth pass

## 4. Frontend (ADR-012)

- [ ] 4.1 `TemplateIndex.vue`: type column/filter, office-upload entry, fragments tab (`CnDataTable`), import-wizard entry; dialogs as separate components in `src/modals/`

- [ ] 4.2 `TemplateDetail.vue`: office panels — source download, tag report with unknown-tag warnings, field-mapping editor, cascade preview; NL Design System tokens only (ADR-003)

- [ ] 4.3 Import wizard: ZIP upload, job progress, per-file report, interactive unknown-tag → schema-property mapping persisted to `fieldMap`

## 5. Quality, i18n, docs

- [ ] 5.1 Unit tests (≥75% coverage on new code) incl. register-drift pins for the new schema fields; run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
  - Fixtures: valid DOCX, macro DOCX, corrupt DOCX, ODT in `tests/sample-documents/`

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/office-template-authoring.spec.ts` + extend `templates.spec.ts` for the parity scenarios; verify on Postgres (8080), test with nldesign theme enabled

- [ ] 5.3 i18n: all new UI strings with English source keys + NL translations (ADR-005)

- [ ] 5.4 Docs in `docs/features/` (office templates, fragments, bulk import) with Playwright MCP screenshots (ADR-010); `openspec validate office-template-authoring --strict` passes

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `composer check:strict` green; hydra gates (spdx, route-auth, spec-coverage, manifest-validation 28/30/51/52) pass
- End-to-end verified against OpenRegister on the Postgres dev instance, not SQLite
