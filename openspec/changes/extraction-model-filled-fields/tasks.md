# Tasks: extraction-model-filled-fields

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Register

- [x] 1.1 Add `fieldFillProfile` and `fieldSuggestion` to `lib/Settings/filinq_register.json` with the lifecycle, processing activity and authorization cascade of design D1 to D4, seed one inactive `dossier` profile, and bump the register version (REQ-EMF-001). Verify: `occ maintenance:repair` imports them and the seed profile shows on the admin settings.

## 2. Shared model call

- [x] 2.1 Move `resolveAiManager()` and `runAiTask()` into `lib/Service/Ai/LocalTextModel.php` and `resolveText()` into `lib/Service/DocumentTextSource.php`, and make `FinancialExtractionService` use them (D5). Verify: the existing `FinancialExtractionServiceTest` passes unchanged.

## 3. Service and routes

- [x] 3.1 Add `lib/Service/SchemaFieldFillService.php`: read the profile and the schema through `SchemaMapper`, build the prompt from the declared fields, check each value with `opis/json-schema` and OpenRegister's formats, attach the passage or `foundInText: false` (D2, D3) (REQ-EMF-001, REQ-EMF-002). Verify: PHPUnit with a stubbed task manager on a valid answer, a malformed date and a value absent from the text.
- [x] 3.2 Refuse a profile naming a `bsn` field on save (REQ-EMF-001). Verify: PHPUnit and a Newman request answer "A BSN field cannot be filled by a model."
- [x] 3.3 Add `POST api/extraction/fields` with `#[NoAdminRequired]`, a read check on the file and the record for the session user, 409 without a profile and 503 without a model (REQ-EMF-001, REQ-EMF-002, REQ-EMF-004). Verify: Newman for 201, 409 and 503.
- [x] 3.4 Add `POST api/extraction/fields/{id}/decide`: write accepted and changed values as the session user, keep existing values unless accepted, record every decision (D4) (REQ-EMF-003). Verify: PHPUnit asserts `ObjectService` is called with the user's rights and only for accepted fields; Newman answers 403 for a user without write access to the record.

## 4. Screens

- [x] 4.1 Add "Fill fields from document" to the row actions of an assigned document in `IntakeIndex.vue` and `src/dialogs/FieldSuggestionDialog.vue` with accept, change and reject per field (REQ-EMF-003). Verify: Playwright `tests/e2e/schema-field-suggestions.spec.ts` with a stubbed provider.
  - Built: the row action on an intake document that sits on a record (`canFill()`: feature available, waiting mode, `file` and a full `assignedTo`), also offered right after an assign (quiet on 409/503); `src/dialogs/FieldSuggestionDialog.vue` shows per field the value now, the suggestion and its passage or "Not found in the document text", and saves only once every field has accept, change (with a value) or reject. Tests: `src/dialogs/FieldSuggestionDialog.spec.js`, `tests/vitest/fieldFill.spec.js`. The Playwright spec with a stubbed provider is not written: it needs a running instance.
- [x] 4.2 Add the model filling section to `Settings.vue`: the provider name, the switch (off by default) and the profiles list and form (REQ-EMF-001, REQ-EMF-004). Verify: Playwright on a server without a provider shows the switch disabled.
  - Built: `src/views/settings/ModelFillingSettings.vue` in the admin settings: the provider, the switch (disabled without a provider), the profiles table and the profile form. Test: `src/views/settings/ModelFillingSettings.spec.js` (no model: note shown, switch disabled and not sent). The Playwright run on a server without a provider needs a running instance.

## 5. Cross-app, strings, tests and docs

- [x] 5.1 File the hermiq issue for the free-prompt half of `plt-ai-fill`. Verify: the issue link is in the PR body.
  - Not done by this build: filing an issue in another repository is outside what the build may do; the issue still needs filing.
  - Filed as ConductionNL/hermiq#1947 (2026-11-24); linked in `proposal.md` and the PR body.
- [x] 5.2 Dutch and English strings (ADR-005) and `@spec` tags on every new method. Verify: `npm run lint`, `npm run check:l10n` and `composer check:strict` pass.
  - The new UI strings in `l10n/en.json` and `l10n/nl.json` (`.js` rebuilt); `@spec` tags on the new methods. `npm run lint` (0 errors), `npm run test:l10n` and `check:l10n-js` pass; phpcs, phpstan and phpmd are clean on the change's PHP after `SchemaFieldFillService` was split: the decision half is `FieldSuggestionDecisions`, and the text model and document text are injected.
- [ ] 5.3 PHPUnit at 75% or more on the new classes (ADR-009) and a section in `docs/features/` with a screenshot of the suggestion dialog, saying which provider reads the text (ADR-010). Verify: the coverage report and the docs page.
  - Partly done: `docs/features/model-filled-fields.md` (who reads the text, switching it on, the profiles, the dialog, the routes) is written and listed in `docs/features/README.md`. Left: the screenshot of the dialog (needs a running instance) and the coverage report (no coverage driver in the build environment).
