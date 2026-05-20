## Why

DocuDesk requires a centralised settings subsystem for three distinct configuration concerns: (1) which OpenRegister register and schema to use for consent object storage, (2) what objection period (in days) satisfies the WOO art. 4.4 minimum, and (3) which metadata-enrichment pipelines (language detection, keyword extraction, topic classification) should run on document events. Without explicit admin configuration these concerns are either hardcoded, undiscoverable, or silently defaulted. Administrators need a dedicated section in the Nextcloud admin panel with a supporting REST API so that settings are both discoverable and automatable.

Additionally, DocuDesk must auto-provision its required OpenRegister register and schema on first boot by importing `docudesk_register.json`. Without this, a freshly installed instance has no valid storage target for consent objects, blocking the entire publication-consent workflow.

## Scope

### In Scope
- Dedicated admin settings section in the Nextcloud admin panel (`DocuDeskAdmin` IIconSection + ISettings).
- `Settings.vue` frontend with sections for Consent Settings, Metadata Enrichment, and Data Storage (OpenRegister configuration).
- `SettingsController` REST API: `GET /api/settings` and `POST /api/settings`.
- `SettingsService` with `getAllSettings()`, `updateSettings()`, and OpenRegister helper methods (`isOpenRegisterInstalled()`, `isOpenRegisterEnabled()`, `getObjectService()`, `getConfigurationService()`).
- Auto-initialization on `Application::boot()`: version-gated import of `docudesk_register.json` via `ConfigurationService::importFromApp()`.
- Version-gated re-import logic: skips if stored `configuration_version` equals JSON version, re-imports if JSON version is higher.
- Graceful error handling: initialization failures are logged and do not crash the app; `\TypeError` and `\Exception` from OpenRegister register listing fall back to an empty list.
- WOO objection period configuration (`publication_objection_period_days`, default 28, range 1–365).
- Feature toggles for language detection, keyword extraction, and topic classification.
- App metadata (`appinfo/info.xml`): Nextcloud 28–32, PHP 8.0+, PostgreSQL 10+ / SQLite / MySQL 8.0+.

### Out of Scope
- Consent record creation, retrieval, or lifecycle management — covered by the `consent-management` change.
- Template register and schema configuration — separate concern from consent configuration.
- End-user (non-admin) settings pages.
- Custom database tables — all domain data remains in OpenRegister; settings use `IAppConfig`.
- The metadata-enrichment processing logic itself — covered by the `metadata-enrichment` change.

## Cross-app Dependencies

- **Hard** — `openregister` >= 0.2.10 — required for `ConfigurationService::importFromApp()` and `RegisterService::findAll()`. If not present or below minimum version, initialization is skipped and register selectors show a warning NcNoteCard.
