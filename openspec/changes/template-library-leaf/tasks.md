# Tasks: template-library-leaf

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Scope

- [x] 1.1 Add `scope[]` (`{register, schema}`) to the `template` schema in `lib/Settings/filinq_register.json` and `TemplateService::listForObject()` (REQ-TMPL-13)

## 2. Leaves

- [x] 2.1 Add `lib/Integration/TemplatesLeafProvider.php` (app-local, `list` only) and the `RegisterLeafProvidersEvent` listener (REQ-TMPL-14)
- [x] 2.2 Add `src/integrations/generateDocumentLeaf.js` with widget and tab under id `filinq-generate-document`, plus the PHP descriptor (REQ-TMPL-15)
  - Built as `src/integrations/registerGenerateDocumentLeaf.js` + `CnFilinqGenerateDocumentWidget.vue`, following the app's other leaves (mount render mode, surfaces `detail-page` and `single-entity`, which are the widget and the tab), and `RegisterGenerateDocumentLeafListener` as the PHP half. The widget lists the templates from `GET /api/templates/for-object` (the same rows as the `filinq-templates` provider, through `TemplateLeafRow`) and generates the chosen one through `POST /api/documents/generate` with the host object as the data source, stored as a file (`output.mode: files`). Tests: `TemplatesControllerForObjectTest`, `MountedLeafListenersTest`, `tests/vitest/generateDocumentLeaf.spec.js`, the leaf registration golden.
- [x] 2.3 Seed the generated document with `sourceObject` and place it in the host folder
  - `options.sourceObject` on `POST /api/documents/generate`, read by `GenerationSourceObject`: it must be one of the request's `dataRefs` (else 400), is recorded on the `generatedDocument` entry (schema 1.8.0, register 8.59.0), and a stored document without `output.targetPath` goes into the object's `@self.folder` as the caller sees it in Files; no reachable folder keeps the default folder. The widget sends the host as `sourceObject`. A multi-format request (`options.formats`) is placed but its entries do not carry `sourceObject`. Tests: `GenerationSourceObjectTest`, `DocumentControllerTest`, `tests/vitest/generateDocumentLeaf.spec.js`.

## 3. Quality

- [x] 3.1 PHPUnit for `listForObject()` scope matching and for the provider, inside the container (ADR-009)
- [ ] 3.2 Playwright e2e `tests/e2e/template-leaf.spec.ts`: the widget lists scoped templates and generates one on a host object
- [ ] 3.3 Dutch and English strings; docs `docs/features/templates-on-an-object.md` with screenshots (ADR-010)
- [ ] 3.4 Hand the leaf ids to dossiq for `documents-on-the-case` and the retirement of `DossiqMergeTemplateNode`
