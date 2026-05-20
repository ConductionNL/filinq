## Tasks

- [ ] 1. **Deduplication check** — search `openspec/specs/`, `openregister/lib/Service/`, and DocuDesk `lib/Service/` for any existing `document` register loading or schema definitions that overlap with this change; document findings (even if "no overlap found") and confirm no duplicate capability exists before proceeding.

- [ ] 2. **Add `anonymization` schema to `document_register.json`** — under `components.schemas`, add an `anonymization` entry with `slug: "anonymization"`, a descriptive `title` ("Anonymization Result"), `description` ("Stores the entity-replacement output of an anonymization pipeline run"), `hardValidation: false`, and `properties: []`. Follow the existing schema field ordering in the file. Validate the JSON: `jq . lib/Settings/document_register.json > /dev/null`.

- [ ] 3. **Add boot-time import of `document_register.json`** — in the `RegistersLoader` repair step (or equivalent `SettingsService` / `SettingsLoadService` initialisation path), add a `ConfigurationService::importFromApp(Application::APP_ID, $documentRegisterData, '0.0.1', false)` call for `document_register.json`, directly below the existing `docudesk_register.json` import call. Load the file via the same mechanism used for `docudesk_register.json`. Add `@spec` PHPDoc tag: `@spec openspec/changes/document-register/tasks.md#task-3`.

- [ ] 4. **Add `slug` fields to existing seed objects** — each `components.objects` entry in `document_register.json` MUST have a `@self.slug` (or equivalent `slug` field inside the `@self` envelope) for idempotent import matching. Assign: `report-test-ano-original` (original analysis), `anonymization-test-ano-result` (replacement map), `report-test-ano-anonymized` (re-analysis of anonymized file). Validate slugs are unique within the file.

- [ ] 5. **Verify register loads on fresh install** — reset the dev environment (`/clean-env` or equivalent), enable DocuDesk, confirm `RegistersLoader` repair step runs clean (no PHP errors), and verify via `occ openregister:registers:list` that a register with slug `document` appears with four schemas. Confirm `GET /api/registers?_extend=schemas` returns the `document` register with `report`, `template`, `entity`, and `anonymization` schemas.

- [ ] 6. **Verify seed objects are created** — after a fresh install, call `GET /api/objects/report` and confirm at least two report objects exist (`report-test-ano-original` and `report-test-ano-anonymized`). Call `GET /api/objects/anonymization` and confirm `anonymization-test-ano-result` exists with a non-empty `replacements` map.

- [ ] 7. **Verify import idempotency** — run the repair step a second time on the same instance; confirm via `GET /api/objects/report?_limit=100` that only 2 report objects exist (not 4), verifying no duplicates are created.

- [ ] 8. **Unit tests — JSON structure** — add `Tests/Unit/Settings/DocumentRegisterConfigTest.php` asserting: (a) `document_register.json` is valid JSON; (b) register has `slug: "document"` and `version: "0.0.1"`; (c) exactly four schemas are defined with slugs `report`, `template`, `entity`, `anonymization`; (d) all four schemas have `hardValidation: false`; (e) exactly three seed objects exist; (f) all seed objects have `@self.slug` fields set; (g) seed objects 1 and 3 reference schema `"report"`, seed object 2 references schema `"anonymization"`. Run via `phpunit -c phpunit-unit.xml --filter DocumentRegisterConfigTest`.

- [ ] 9. **Unit tests — repair step wiring** — in the existing `RegistersLoaderTest.php` (or equivalent), add a test asserting that `importFromApp()` is called with `document_register.json` data when `run()` executes. Confirm the call appears after (not before) the existing `docudesk_register.json` import call. Run and confirm tests pass with ≥75% coverage on changed lines.

- [ ] 10. **Verify `@spec` traceability** — confirm every modified PHP class and public method in task 3 carries a `@spec openspec/changes/document-register/tasks.md#task-N` PHPDoc tag. Run `composer check:strict` (or equivalent linting) and confirm it stays green after all changes.

- [ ] 11. **Documentation** — write or update `docs/features/document-register.md` describing: the register purpose; four schema slugs and their intended use; the `hardValidation: false` / `properties: []` design rationale; the three seed objects and what they demonstrate (including the NER-on-anonymized-text limitation in seed object 3); the known planned fields (WCAG, language level, retention, GDPR controller); and the MD5 note (cryptographically weak; SHA-256 is the recommended upgrade). Add or update a reference to this doc from `docs/FEATURES.md` if that file exists.

- [ ] 12. **Changelog** — add a `CHANGELOG` entry under `Fixed`: `[DREG-060, DREG-062] document_register.json now loaded on install/repair; anonymization schema added to register`.
