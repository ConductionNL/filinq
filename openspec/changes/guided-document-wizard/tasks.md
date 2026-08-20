# Tasks: guided-document-wizard

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 15.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [ ] 1.1 Extend `lib/Settings/docudesk_register.json`: new `wizardDefinition` schema in the `templates` register (name, description, namespace, templateId, active, questions[] per design.md D2); `generatedDocument` gains optional `wizardContext` object; bump templates register (additive on top of Wave-1 `2.1.0` → `2.2.0`) and document register (`2.2.0` → `2.3.0`). Apply order pinned: this change FIRST (document → `2.3.0`), `multi-format-output` SECOND (document `2.3.0` → `2.4.0`)
  - All new properties optional except `wizardDefinition` required `name`/`templateId`/`questions`; schema refs by slug
  - `tests/validate-manifest.js` and register import on boot both pass

- [ ] 1.2 Ship seed data: demo `wizardDefinition` (nil-UUID `…000201`, Demostad flavour, attached to the Wave-1 seed template) per design.md Seed Data
  - Seed imports cleanly via the existing register-import mechanism

## 2. Backend services

- [ ] 2.1 `WizardService` CRUD via `ObjectService`/`OpenRegisterResolver`: save-time definition validation (unique question keys, conditions reference earlier questions only, choice/registerObject completeness, `mapsTo` warn-on-unknown against the template's bound schema), one-active-wizard-per-template (409), template-lock respect (423) (REQ-DDGDW-001/002)

- [ ] 2.2 Condition semantics + `validateAnswers()`: forward-pass visibility evaluation (`equals`/`notEquals`/`answered`), fail-safe visible on malformed condition, required/choice/date/registerObject answer validation with per-question 422 errors (REQ-DDGDW-003/005)

- [ ] 2.3 `translateAnswers()`: registerObject answers → `dataRefs`, scalar answers → `adHocData` at `mapsTo` (dot-notation), raw answers → `wizardContext`; DCS-005 precedence pinned by tests (REQ-DDGDW-005)

- [ ] 2.4 Hook in `DocumentService::generateDocument()`: when `options.wizardContext` present — validate against the active wizard (422 on failure), then record `wizardContext` (wizardId, wizard OR version, answers) on the `generatedDocument` object; requests without wizardContext byte-identical to today (REQ-DDGDW-005/008)

- [ ] 2.5 Prefill: `POST /api/wizards/{id}/prefill` resolving the entry object via `DataResolverService` and returning `{answers, unresolved}` per design.md D5 (REQ-DDGDW-006)

## 3. Routes & controller

- [ ] 3.1 `WizardController` + routes: wizard CRUD under `api/wizards`, `GET api/templates/{id}/wizard`, `POST api/wizards/{id}/prefill`; explicit auth attributes on every method; error shaping consistent with `DocumentController`
  - Every route registered in `appinfo/routes.php`; route-reachability/route-auth gates pass

## 4. Frontend (ADR-012)

- [ ] 4.1 Wizard authoring panel on `TemplateDetail.vue`: ordered question list with reorder, per-question form (type-specific fields), condition builder limited to earlier questions; dialogs in `src/modals/`; NcSelect always with `inputLabel`

- [ ] 4.2 `src/views/wizard/WizardRunner.vue`: stepwise runner with progress, back navigation, live skip-logic, register-object picker via shared `useObjectStore`, review step (visible Q/A + override indicators), submit through `POST /api/documents/generate`, existing download handling; fully keyboard-operable (REQ-DDGDW-004/007)

- [ ] 4.3 Entry points: "Generate with wizard" on template index/detail (only when an active wizard exists) and object-driven entry applying prefill (REQ-DDGDW-006)

## 5. Quality, i18n, docs

- [ ] 5.1 Unit tests (≥75% coverage on new code): definition validation, condition matrix incl. fail-safe malformed case, answer translation + precedence, generate-hook 422 paths, prefill mapping, register-drift pins for `wizardDefinition`/`wizardContext`; run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/guided-document-wizard.spec.ts`: author wizard → run with skip logic both branches → generated download; twig + office template parity run; object entry point with prefill; verify on Postgres (8080) with OpenRegister, test with nldesign theme enabled

- [ ] 5.3 i18n: all new UI strings with English source keys + NL translations (ADR-005)

- [ ] 5.4 Docs in `docs/features/guided-document-wizard.md` (authoring, running, prefill, privacy note on stored answers) with Playwright MCP screenshots (ADR-010); `openspec validate guided-document-wizard --strict` passes

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `composer check:strict` green; hydra gates (spdx, route-auth, spec-coverage, manifest-validation 28/30/51/52) pass
- Answer validation is server-authoritative; client skip logic is UX only
- End-to-end verified against OpenRegister on the Postgres dev instance, not SQLite
