# Tasks: office-template-authoring

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 17.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [x] 1.1 Extend `lib/Settings/filinq_register.json`: `template` schema (+`templateType`, `sourceFileId`, `contentHash`, `boundRegister`, `boundSchema`, `mergeFields`, `fieldMap`, `tagReport`), `templateVersion` (+`sourceFileId`, `contentHash`), new `textFragment` and `templateImportJob` schemas; bump templates register to 2.1.0
  - All new properties optional; absent `templateType` reads as `twig`; schema refs by slug (not PascalCase)
  - Built: register 8.52.0, `template` 1.6.0, `templateVersion` 1.2.0. `textFragment` is `searchable: false` until it has a page to deep-link to. Three demo objects each for `textFragment` and `templateImportJob` in `filinq_mock_register.json`.
  - `tests/validate-manifest.js` and register import on boot both pass

- [x] 1.2 Ship seed data: demo office template object + seed DOCX in `tests/sample-documents/`, demo `textFragment`, demo `templateImportJob` (nil-UUID pattern, Demostad flavour per design.md Seed Data)
  - Seed imports cleanly via the existing register-import mechanism
  - Built: `tests/sample-documents/beschikking-parkeervergunning.docx` with the four tags of design.md, and the demo office template `template-beschikking-parkeervergunning` naming its checksum and tags (`sourceFileId: 0` as in design.md: the demo carries no stored file until its source is uploaded); the fragment and import-job demos came with 1.1. `OfficeTemplateSeedTest` keeps the demo and the file in step.

## 2. Backend services

- [x] 2.1 `OfficeTemplateService`: upload intake (multipart), macro/`vbaProject.bin` rejection, size cap + mime sniff, ODT→DOCX normalisation via `LibreOfficeHeadlessBackend`, source storage in app folder + `sourceFileId`/`contentHash`, tag extraction via PhpWord `TemplateProcessor::getVariables()` (REQ-DDOTA-001)
  - HTTP 422 paths for macro/size/mime; `converted: true` flag on ODT normalisation
  - Built: `OfficeSourceGuard`, `MergeTagReader`, `OfficeSourceStore` (app data folder, named by checksum), `OfficeTemplateService::create()`, `LibreOfficeHeadlessBackend::convertDocument()`, and `POST api/templates/office` (`OfficeTemplatesController`). Nothing is stored before every check passes.

- [x] 2.2 Tag validation against bound schema: classify known/fragment/unknown by reading schema properties from OpenRegister; persist `tagReport`; honour `filinq.templates.unknown_tag_severity` (warning default, blocking refuses upload) (REQ-DDOTA-002)

- [x] 2.3 Office render path in `DocumentService::generateDocument()`/`generatePreview()`: fragments pre-pass → `TemplateProcessor` fill (setValue/cloneBlock/cloneRow, `fieldMap` aliasing) → `docx` output or `PdfConversionService::convertToPdf()` for pdf/pdfa; missing-data warnings; `generatedDocument` logging with template type (REQ-DDOTA-003)
  - `huisstijlId` ignored for office templates with a warning
  - Built: `OfficeTemplateFiller` (fragments → `cloneBlock` → `cloneRow` → `setValue`, every value XML-escaped, gaps named in the warnings), `OfficeTemplateRenderer` (stored source → fill → `docx` as-is, `pdf` through `PdfConversionService::convertToPdf()`, `odf`/`html` through `LibreOfficeHeadlessBackend::convertDocument()`), and the office branch in `DocumentService` for generate, bulk and preview. `generatedDocument` 1.7.0 records `templateType` and `fragments`.

- [x] 2.4 `textFragment` CRUD + fragment resolution pre-pass for both office and Twig paths, missing-fragment marker + warning; NO change to the Twig sandbox whitelists (REQ-DDOTA-004)
  - Built: `TextFragmentService` (slug unique per namespace, through `TemplatesRegisterObjects` in the templates register) and the Twig pre-pass `OfficeTemplateRenderer::resolveTwigFragments()`, which turns a fragment's `${field}` into a Twig print and strips any other Twig syntax from it. The HTTP routes are task 3.1.

- [x] 2.5 `TemplateImportService` + background job: ZIP/folder unpack, per-file template+fragment creation, per-file report, skip-and-continue on failure; `templateImportJob` state via ObjectService (REQ-DDOTA-005)
  - Built: `TemplateImportService` (`start()` stores the ZIP in the app data folder or names a Files folder, saves a queued `templateImportJob` and queues `TemplateImportJob`; `run()` creates one office template per DOCX/ODT, with the first folder as category, and one fragment per `fragments/*.txt|html`, and writes a report row per file: imported, failed with its reason, or skipped). The job runs as the user who started the import. `templateImportJob` gains `skipped`, `namespace`, `boundRegister`, `boundSchema`, `archiveFileId`, `sourcePath`, `startedAt`, `finishedAt`.

- [x] 2.6 Lifecycle parity: version snapshot + restore re-pointing `sourceFileId`/`contentHash`, lock gating source re-upload, preview via cascade, duplicate copying the source file (REQ-DDOTA-006/007)
  - Built: `templateVersion` snapshots keep `sourceFileId`/`contentHash` and a restore re-points both; `getTemplateAtVersion()` renders an older version from its own source; `OfficeTemplateService::replaceSource()` saves a new source as a new version through `updateTemplate()`, which enforces the edit lock; a duplicate carries the office fields and points at the same immutable checksum-named source; `GET/POST api/templates/{id}/office/preview` is the cascade's PDF of the filled template.

- [x] 2.7 Office rows of multi-format output (moved from multi-format-output, built 2026-09-29): `docx` of an office template is the filled source DOCX; `html` via a DOCX-input sibling of `LibreOfficeHeadlessBackend::convertHtml()` behind the matrix; `FormatMatrixService::forTemplate()` gates office `html` on LibreOffice; `MultiFormatOutputProducer` starts from the filled DOCX for an office template (REQ-DDMFO-007 and the office scenarios in specs/document-creatie-sjablonen)
  - Built: `LibreOfficeHeadlessBackend::convertDocument()` takes `html` as an output (same lock, temp dir and timeout); `OfficeTemplateRenderer::convert()` turns the one filled DOCX into each format; `MultiFormatOutputProducer` fills once through `DocumentService::fillOfficeTemplate()` and converts per format; `FormatMatrixService::forTemplate()` offers office `docx` without LibreOffice and gates office `html` on it (a forced office `html` without it fails 503 from the converter).

## 3. Routes & controller

- [x] 3.1 Routes + `TemplatesController` actions: `POST api/templates/office`, `POST api/templates/{id}/office` (new source version), `POST api/templates/import`, `GET api/templates/import/{jobId}`, fragment CRUD routes; explicit auth attributes on every method; error shaping via `TemplateRequestHandler`
  - Every route registered in `appinfo/routes.php`; gate route-reachability/route-auth pass
  - Built in `OfficeTemplatesController` (`newSource`, `source`, `preview`, `fieldMap`), `TemplateImportsController` and `TextFragmentsController` (`/api/text-fragments`), each method `@NoAdminRequired` with OpenRegister's schema authorisation deciding the rest; documented in `openapi.json`.

## 4. Frontend (ADR-012)

- [x] 4.1 `TemplateIndex.vue`: type column/filter, office-upload entry, fragments tab (`CnDataTable`), import-wizard entry; dialogs as separate components in `src/modals/`
  - Built as manifest configuration: the Templates index (a `type:index` page since Phase 8, `TemplateIndex.vue` is no longer routed) gains the `templateType` column (facetable) and an "Import Word templates" primary action; a `TextFragments` index page lists and edits `textFragment` objects; the `TemplateImport` page holds the upload entry, which opens `src/modals/OfficeTemplateUploadModal.vue`.

- [x] 4.2 `TemplateDetail.vue`: office panels — source download, tag report with unknown-tag warnings, field-mapping editor, cascade preview; NL Design System tokens only (ADR-003)
  - Built: a "Word file" tab on office templates (`src/components/templates/OfficeTemplatePanel.vue`): download, PDF preview through the cascade, new-version upload with changelog, the tag report with unknown-tag warnings, and `FieldMapEditor.vue`; Nextcloud CSS variables only.

- [x] 4.3 Import wizard: ZIP upload, job progress, per-file report, interactive unknown-tag → schema-property mapping persisted to `fieldMap`
  - Built: `src/views/templates/TemplateImport.vue` (ZIP or Files folder, job progress polled from `GET api/templates/import/{jobId}`, per-file report, "Map tags" per template with unknown tags, saved through `PUT api/templates/{id}/field-map`); client in `src/services/officeTemplates.js` with `tests/vitest/officeTemplates.spec.js`.

## 5. Quality, i18n, docs

- [x] 5.1 Unit tests (≥75% coverage on new code) incl. register-drift pins for the new schema fields; run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
  - Coverage measured 2026-11-20 with pcov over the full unit suite (8398 tests, 0 failures), statements of the lib files carrying this change's `@spec` tag: 1490 of 1690, 88.2% over 23 files. Register-drift pins: `OfficeTemplateSeedTest::testTheOfficeDataModelIsPinned()`. Fixtures on disk: `tests/sample-documents/beschikking-parkeervergunning.docx` (valid) and `tests/sample-documents/office/` `macro-letter.docx`, `corrupt-letter.docx`, `letter.odt`, pinned through the source guard by `OfficeSampleFilesTest`.
  - Fixtures: valid DOCX, macro DOCX, corrupt DOCX, ODT in `tests/sample-documents/`

- [x] 5.2 Playwright e2e `tests/e2e/spec-coverage/office-template-authoring.spec.ts` for the parity scenarios (kept in that file rather than extending `templates.spec.ts`) — written, runs in nightly (not verified against a live instance); the Postgres (8080) and nldesign-theme passes follow in the nightly

- [x] 5.3 i18n: all new UI strings with English source keys + NL translations (ADR-005)
  - Built: every new UI and schema string in `l10n/en.json` and `l10n/nl.json`, `.js` rebuilt with `npm run l10n:build`.

- [ ] 5.4 Docs in `docs/features/` (office templates, fragments, bulk import) with Playwright MCP screenshots (ADR-010); `openspec validate office-template-authoring --strict` passes

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `composer check:strict` green; hydra gates (spdx, route-auth, spec-coverage, manifest-validation 28/30/51/52) pass
- End-to-end verified against OpenRegister on the Postgres dev instance, not SQLite
