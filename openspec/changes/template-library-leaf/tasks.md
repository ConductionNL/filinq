# Tasks: template-library-leaf

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Scope

- [ ] 1.1 Add `scope[]` (`{register, schema}`) to the `template` schema in `lib/Settings/filinq_register.json` and `TemplateService::listForObject()` (REQ-TMPL-13)

## 2. Leaves

- [ ] 2.1 Add `lib/Integration/TemplatesLeafProvider.php` (app-local, `list` only) and the `RegisterLeafProvidersEvent` listener (REQ-TMPL-14)
- [ ] 2.2 Add `src/integrations/generateDocumentLeaf.js` with widget and tab under id `filinq-generate-document`, plus the PHP descriptor (REQ-TMPL-15)
- [ ] 2.3 Seed the generated document with `sourceObject` and place it in the host folder

## 3. Quality

- [ ] 3.1 PHPUnit for `listForObject()` scope matching and for the provider, inside the container (ADR-009)
- [ ] 3.2 Playwright e2e `tests/e2e/template-leaf.spec.ts`: the widget lists scoped templates and generates one on a host object
- [ ] 3.3 Dutch and English strings; docs `docs/features/templates-on-an-object.md` with screenshots (ADR-010)
- [ ] 3.4 Hand the leaf ids to dossiq for `documents-on-the-case` and the retirement of `DossiqMergeTemplateNode`
