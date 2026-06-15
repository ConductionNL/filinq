# Tasks: admin-settings

## Task 1: Admin Section Registration
- [x] Register `DocuDeskAdmin` ISettings class in `lib/Settings/`
- [x] Register `DocuDeskAdmin` IIconSection class in `lib/Sections/`
- [x] Configure section with DocuDesk app icon (`app-dark.svg`)

## Task 2: Settings API
- [x] Implement `SettingsController::index()` for GET /api/settings
- [x] Implement `SettingsController::create()` for POST /api/settings
- [x] Add admin-only access via Nextcloud auth middleware

## Task 3: Settings Service
- [x] Implement `SettingsService` with `getAllSettings()` and `updateSettings()`
- [x] Add feature toggle loading from `IAppConfig`
- [x] Add OpenRegister version check (>= 0.2.10)

## Task 4: Boot Initialization
- [x] Implement `SettingsInitializer` to import `docudesk_register.json` on boot
- [x] Add `RegisterDiscoveryService` for available registers lookup

## Task 5: Frontend Settings Page
- [x] Create `Settings.vue` with sections for Consent, Metadata Enrichment, Data Storage
- [x] Create `settings.js` entry point
- [x] Add version info and support contact sections

## Task 6: Unit Tests (ADR-009)
- [x] Write `SettingsServiceTest` with coverage for getAllSettings, updateSettings, getObjectService
- [x] Verify empty key handling in updateSettings

## Task 7: Documentation + Screenshots (ADR-010)
- [x] Take screenshot of admin settings page
- [x] Write feature documentation at `docs/features/admin-settings.md`

## Task 8: i18n (ADR-005)
- [x] Add Dutch translations for all settings labels
- [x] Add English translations for all settings labels
