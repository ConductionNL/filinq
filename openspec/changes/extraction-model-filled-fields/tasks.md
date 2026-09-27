# Tasks: extraction-model-filled-fields

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Register

- [ ] 1.1 Add `fieldFillProfile` and `fieldSuggestion` to `lib/Settings/filinq_register.json` with the lifecycle, processing activity and authorization cascade of design D1 to D4, seed one inactive `dossier` profile, and bump the register version (REQ-EMF-001). Verify: `occ maintenance:repair` imports them and the seed profile shows on the admin settings.

## 2. Shared model call

- [ ] 2.1 Move `resolveAiManager()` and `runAiTask()` into `lib/Service/Ai/LocalTextModel.php` and `resolveText()` into `lib/Service/DocumentTextSource.php`, and make `FinancialExtractionService` use them (D5). Verify: the existing `FinancialExtractionServiceTest` passes unchanged.

## 3. Service and routes

- [ ] 3.1 Add `lib/Service/SchemaFieldFillService.php`: read the profile and the schema through `SchemaMapper`, build the prompt from the declared fields, check each value with `opis/json-schema` and OpenRegister's formats, attach the passage or `foundInText: false` (D2, D3) (REQ-EMF-001, REQ-EMF-002). Verify: PHPUnit with a stubbed task manager on a valid answer, a malformed date and a value absent from the text.
- [ ] 3.2 Refuse a profile naming a `bsn` field on save (REQ-EMF-001). Verify: PHPUnit and a Newman request answer "A BSN field cannot be filled by a model."
- [ ] 3.3 Add `POST api/extraction/fields` with `#[NoAdminRequired]`, a read check on the file and the record for the session user, 409 without a profile and 503 without a model (REQ-EMF-001, REQ-EMF-002, REQ-EMF-004). Verify: Newman for 201, 409 and 503.
- [ ] 3.4 Add `POST api/extraction/fields/{id}/decide`: write accepted and changed values as the session user, keep existing values unless accepted, record every decision (D4) (REQ-EMF-003). Verify: PHPUnit asserts `ObjectService` is called with the user's rights and only for accepted fields; Newman answers 403 for a user without write access to the record.

## 4. Screens

- [ ] 4.1 Add "Fill fields from document" to the row actions of an assigned document in `IntakeIndex.vue` and `src/dialogs/FieldSuggestionDialog.vue` with accept, change and reject per field (REQ-EMF-003). Verify: Playwright `tests/e2e/schema-field-suggestions.spec.ts` with a stubbed provider.
- [ ] 4.2 Add the model filling section to `Settings.vue`: the provider name, the switch (off by default) and the profiles list and form (REQ-EMF-001, REQ-EMF-004). Verify: Playwright on a server without a provider shows the switch disabled.

## 5. Cross-app, strings, tests and docs

- [ ] 5.1 File the hermiq issue for the free-prompt half of `plt-ai-fill`. Verify: the issue link is in the PR body.
- [ ] 5.2 Dutch and English strings (ADR-005) and `@spec` tags on every new method. Verify: `npm run lint`, `npm run check:l10n` and `composer check:strict` pass.
- [ ] 5.3 PHPUnit at 75% or more on the new classes (ADR-009) and a section in `docs/features/` with a screenshot of the suggestion dialog, saying which provider reads the text (ADR-010). Verify: the coverage report and the docs page.
