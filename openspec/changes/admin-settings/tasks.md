# Tasks: admin-settings

## Task 1: Admin Section Registration
- [x] Create `lib/Sections/DocuDeskAdmin.php` implementing `IIconSection` with `app-dark.svg`
- [x] Create `lib/Settings/DocuDeskAdmin.php` implementing `ISettings` rendering the `settings` template
- [x] Register both classes in `lib/AppInfo/Application.php` via DI container

## Task 2: Settings REST API
- [x] Create `lib/Controller/SettingsController.php` with `index()` for `GET /api/settings`
- [x] Implement `create()` for `POST /api/settings` with JSON-encoding of array values
- [x] Add `settings#index` and `settings#create` routes to `appinfo/routes.php`
- [x] Enforce admin-only access via Nextcloud auth middleware

## Task 3: SettingsService Core
- [x] Create `lib/Service/SettingsService.php` with `getAllSettings()` and `updateSettings()`
- [x] Add `isOpenRegisterInstalled()` with version floor check (>= 0.2.10)
- [x] Add `isOpenRegisterEnabled()` using `IAppManager`
- [x] Add `getObjectService()` and `getConfigurationService()` with lazy container resolution
- [x] Implement `\TypeError` and `\Exception` catch in `getAllSettings()` with warning log + empty fallback
- [x] Skip empty keys in `updateSettings()` with warning log

## Task 4: Settings Initialization
- [x] Create `lib/Service/SettingsInitializer.php` extracting initialization logic
- [x] Implement version-gated import: compare JSON version vs stored `configuration_version`
- [x] Add JSON file validation chain: existence → readability → JSON validity → version presence
- [x] Create `lib/Service/RegisterDiscoveryService.php` to fetch and filter registers/schemas (strip `properties`)
- [x] Wire `Application::boot()` to call `SettingsService::initialize()` with silent failure (log + continue)

## Task 5: Register Definition File
- [x] Create `lib/Settings/docudesk_register.json` in OpenAPI 3.0.0 format with `x-openregister` extensions
- [x] Define Consent Register and PublicationConsent schema
- [x] Set initial `version` field in the JSON file

## Task 6: Frontend Settings Page
- [x] Create `src/views/settings/Settings.vue` with four `NcSettingsSection` blocks:
  - DocuDesk header with doc link
  - Consent Settings (objection period input, 1–365)
  - Metadata Enrichment (three `NcCheckboxRadioSwitch` toggles)
  - Data Storage (OpenRegister register/schema selectors, `NcNoteCard` warning, install button)
- [x] Add "Save All Settings" button wiring all sections to `POST /api/settings`
- [x] Create `src/settings.js` Webpack entry point
- [x] Register `settings.js` as admin settings entry in `appinfo/info.xml`

## Task 7: Unit Tests (ADR-008)
- [x] Write `SettingsServiceTest` covering: `getAllSettings()`, `updateSettings()`, `getObjectService()`, empty key handling, `TypeError` catch, `Exception` catch
- [x] Write `SettingsControllerTest` covering: index response structure, create with array value, create with empty key
- [x] Run tests inside container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

## Task 8: Deduplication Check
- [x] Searched `openspec/specs/` and `openregister/lib/Service/` for overlap with `ObjectService`, `RegisterService`, `ConfigurationService` — no duplicate settings management found in OpenRegister
- [x] Confirmed `IAppConfig` is correct platform primitive for app-level settings (not OpenRegister)

## Task 9: i18n (ADR-005)
- [x] Add Dutch translations for all settings labels and descriptions in `l10n/nl.js` and `l10n/nl.json`
- [x] Add English translations for all settings labels and descriptions in `l10n/en_GB.js` and `l10n/en_GB.json`

## Task 10: Documentation + Screenshots (ADR-009)
- [x] Take screenshot of admin settings page (DocuDesk section visible in nav)
- [x] Take screenshot showing OpenRegister not-installed warning state
- [x] Write feature documentation at `docs/features/admin-settings.md`

## Quality Gates
- [ ] `composer check:strict` passes
- [ ] No new PHPCS/PHPMD/PHPStan warnings in touched files
- [ ] Admin section visible only to Nextcloud admins (403 verified for regular users)
- [ ] Boot initialization runs in < 100ms on warm subsequent boots (version match → skip)
- [ ] Settings page loads with empty register list when OpenRegister throws (regression test)
