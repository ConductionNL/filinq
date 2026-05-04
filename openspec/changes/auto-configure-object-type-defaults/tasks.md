## 1. Service layer — `applyObjectTypeConfigurationDefaults()`

- [x] 1.1 Add a private method `applyObjectTypeConfigurationDefaults(array $jsonDef): void` to `lib/Service/SettingsInitializer.php`
- [x] 1.2 Inside the helper, derive the schema-slug → register-slug map at runtime by iterating `$jsonDef['components']['registers']` and inverting each `register.schemas[]` list — the map MUST NOT be hardcoded in PHP
- [x] 1.3 Resolve the Register and Schema entities by slug via `RegisterMapper::find($registerSlug)` and `SchemaMapper::find($schemaSlug)`. Both mappers' `find()` accept ID, UUID, or slug (case-insensitive), so this works on both fresh imports and idempotent boots
- [x] 1.4 For each `(schemaSlug, registerSlug)` pair from the derived map, skip with a warning log when either lookup throws `DoesNotExistException`; do not propagate
- [x] 1.5 For each present schema, write IAppConfig keys `{schemaSlug}_source = "openregister"`, `{schemaSlug}_register = (string) registerId`, `{schemaSlug}_schema = (string) schemaId` — but **only when the current value is empty** (`$this->config->getValueString($this->appName, $key, '') === ''`)
- [x] 1.6 When a key already has a non-empty value, log info `Preserving existing override for {key}` and do not write — admin overrides MUST be preserved
- [x] 1.7 Defensive guard: when `$jsonDef['components']['registers']` is empty or missing, log info and return cleanly (no exception)
- [x] 1.8 Wrap the whole helper body in try/catch so any failure inside is logged via `$this->logger->error(...)` and never propagates to `initialize()`

## 2. Wire helper into `SettingsInitializer::initialize()`

- [x] 2.1 Run `applyObjectTypeConfigurationDefaults($settings)` at the end of `initialize()` whenever the OpenRegister bootstrap path completes successfully — both the fresh-import branch (after `$configurationService->importFromApp(...)`) and the version-up-to-date early-return branch
- [x] 2.2 In the up-to-date branch, call the helper before returning so cleared/empty IAppConfig keys re-fill on subsequent boots without re-running the full JSON import
- [x] 2.3 Skip the helper when `importFromApp` itself throws — boot already logs and short-circuits; no auto-default attempt is appropriate when the registers/schemas may not exist yet
- [x] 2.4 No public-API changes to `SettingsInitializer` — the helper stays private; `initialize()` keeps its existing return shape

## 3. Unit tests — `SettingsInitializerTest`

- [x] 3.1 Create `tests/unit/Service/SettingsInitializerTest.php` with `declare(strict_types=1)`, namespace `Unit\Service`, extending `PHPUnit\Framework\TestCase`
- [x] 3.2 Add a fixture helper that builds mocked `Schema` / `Register` entities exposing `getId()` via `addMethods(['getId'])` (NextCloud Entity magic method needs explicit registration). The helper also stubs `RegisterMapper::find($slug)` / `SchemaMapper::find($slug)` to return those entities (or throw `DoesNotExistException` on demand)
- [x] 3.3 Add a fixture helper that returns a pared-down JSON-definition array with `components.registers` and `components.schemas` matching `lib/Settings/docudesk_register.json`'s structure (one register with one schema is enough for most tests)
- [x] 3.4 `testFreshInstallWritesAllThreeKeysPerSchema` — all IAppConfig values empty, helper is called, expect `setValueString` for `_source`, `_register`, `_schema` of every schema in the import result
- [x] 3.5 `testPreservesExistingOverride` — `publicationConsent_register` returns `"99"`; helper does NOT call `setValueString` for that key, but DOES write the schema and source keys
- [x] 3.6 `testPerKeyGatingAllowsPartialOverride` — `_register` set, `_schema` empty for the same schema; only `_schema` is written, `_register` is preserved
- [x] 3.7 `testMissingSchemaIsSkippedWithWarning` — JSON declares two schemas, `SchemaMapper::find()` throws `DoesNotExistException` for one; the missing schema's keys are NOT written, a warning is logged, no exception propagates
- [x] 3.8 `testMissingRegisterIsSkippedWithWarning` — same pattern but `RegisterMapper::find()` is the one throwing
- [x] 3.9 `testEmptyComponentsRegistersInJsonNoOps` — `components.registers` is `[]` (or absent); helper logs info and returns; no `setValueString` calls made
- [x] 3.10 `testHelperFailureDoesNotPropagate` — force the helper into an exceptional path (e.g., make `getSlug()` throw); confirm `initialize()` (or the helper's caller) catches and logs without rethrowing
- [x] 3.11 Run via `docker exec master-nextcloud-1 sh -c 'cd /var/www/html/apps-extra/docudesk && ./vendor/bin/phpunit -c phpunit-unit.xml --filter SettingsInitializerTest'` and confirm green

## 4. Integration test — `ConsentCrudServiceTest::getConsentConfig`

- [x] 4.1 Add `testGetConsentConfigReturnsAutoPopulatedValuesAfterInit` — using a fixture where IAppConfig has been populated by the auto-default helper (registers/schemas slugs from `docudesk_register.json`), confirm `getConsentConfig()` returns `['register' => '<id>', 'schema' => '<id>']` (non-null) without any admin-side write
- [x] 4.2 Add or update `testGetConsentConfigReturnsNullWhenKeysCleared` — when both keys are empty, `getConsentConfig()` returns `null`. Documents that the auto-default helper is the only path populating these keys post-fix
- [x] 4.3 Run the targeted suite (`--filter ConsentCrudServiceTest`) and confirm green

## 5. Spec — update canonical `admin-settings`

- [x] 5.1 Edit `openspec/specs/admin-settings/spec.md`: extend REQ-SET-02's body text to mention auto-default behaviour and override preservation
- [x] 5.2 Add scenario "Defaults are populated automatically on fresh install" under REQ-SET-02
- [x] 5.3 Add scenario "Administrator overrides are preserved on reboot" under REQ-SET-02
- [x] 5.4 Add scenario "Auto-default covers every schema declared in `docudesk_register.json`"
- [x] 5.5 Add five new requirements to the SET-### table: SET-005 (auto-populate on fresh install), SET-006 (covers all schemas, derived from JSON), SET-007 (per-key empty-check gating), SET-008 (graceful failure), SET-009 (re-runs on every successful boot)
- [x] 5.6 Validate via `openspec validate auto-configure-object-type-defaults --strict`
- [ ] 5.7 The change-folder delta at `openspec/changes/auto-configure-object-type-defaults/specs/admin-settings/spec.md` will be folded into the canonical spec via `/opsx:sync` after verification — no manual sync in this task

## 6. Documentation

- [x] 6.1 Locate the canonical consent-management docs file (likely `docs/features/consent-management.md` per ADR-010 conventions; create if absent)
- [x] 6.2 Add a "Default register/schema configuration" section explaining: the keys `publicationConsent_register` / `publicationConsent_schema` are auto-populated from `docudesk_register.json` on app boot; admins do not need to configure them on a fresh install
- [x] 6.3 Document the override path: admins MAY change the values via the existing settings UI; overrides persist across reboots and version bumps
- [ ] 6.4 Add a Playwright-MCP screenshot of the auto-populated settings page (per ADR-010); deferred to manual capture if Playwright env is unavailable in this session

## 7. Quality gates

- [x] 7.1 Run `composer check:strict` from the docudesk dir — all PHPCS / PHPMD / Psalm / PHPStan checks pass on changed files; fix any pre-existing issues touched by the change per project policy
- [x] 7.2 Run `docker exec master-nextcloud-1 sh -c 'cd /var/www/html/apps-extra/docudesk && ./vendor/bin/phpunit -c phpunit.xml'` — full unit suite green, including the new `SettingsInitializerTest` and updated `ConsentCrudServiceTest` (226 tests / 446 assertions / 0 failures)

## 8. Manual verification

- [ ] 8.1 On a clean docker-compose env (or after clearing the IAppConfig keys), enable DocuDesk + OpenRegister; confirm `GET /apps/docudesk/api/consent` (or equivalent list endpoint) returns 200 without any settings-UI interaction
- [ ] 8.2 Run `occ config:app:get docudesk publicationConsent_register` and `... publicationConsent_schema` — both return non-empty integer IDs
- [ ] 8.3 Manually set `publicationConsent_register` to a different valid integer via `occ config:app:set ...`, restart the container, and confirm the override is preserved (key is not reverted to the auto-default)
