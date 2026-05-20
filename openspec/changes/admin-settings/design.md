## Context

DocuDesk is a Nextcloud app for document management with WOO publication consent and metadata enrichment. Its settings subsystem is the bootstrap layer: it provisions OpenRegister data structures on first boot and exposes all runtime-configurable parameters to administrators via both a UI and REST API.

All persistent configuration uses Nextcloud's `IAppConfig` key-value store (table `oc_appconfig`) — no custom database tables (ADR-001). The OpenRegister register/schema definition is shipped as `lib/Settings/docudesk_register.json` (OpenAPI 3.0.0 + `x-openregister` extensions) and imported idempotently on each boot via `ConfigurationService::importFromApp()`.

This change introduces no new OpenRegister schemas of its own; it uses `IAppConfig` exclusively for configuration storage. No seed data section is required (settings/permissions change — ADR-001 exception).

## Goals / Non-Goals

**Goals**
- Provide a discoverable admin UI for all DocuDesk configuration knobs.
- Auto-provision OpenRegister data structures on first install and on every version upgrade where the JSON version advances.
- Expose settings via REST API for automated deployment and integration testing.
- Fail gracefully when OpenRegister is unavailable or throws internal errors.

**Non-Goals**
- Implementing consent record CRUD (separate `consent-management` change).
- Implementing metadata enrichment processing (separate `metadata-enrichment` change).
- Multi-tenant or per-user settings — all settings are instance-wide admin configuration.

## Decisions

1. **Two registration classes, two namespaces.** Nextcloud requires `ISettings` (form template) and `IIconSection` (nav section) to be separate classes. `OCA\DocuDesk\Settings\DocuDeskAdmin` provides the form; `OCA\DocuDesk\Sections\DocuDeskAdmin` provides the section with `app-dark.svg`. Same class name, different namespaces.

2. **`IAppConfig` for all settings.** Consent period, feature toggles, register/schema IDs, and `configuration_version` all live in `oc_appconfig`. No custom tables. Arrays/objects are JSON-encoded before storage.

3. **Lazy service resolution via container.** `getObjectService()` and `getConfigurationService()` resolve OpenRegister services from the PSR `ContainerInterface` on first call and throw `\RuntimeException` if OpenRegister is absent. This avoids DI failures at app load time when OpenRegister is not installed.

4. **Version-gated boot import.** `initialize()` compares the JSON file's `version` field against the stored `configuration_version` in `IAppConfig`. Only re-imports if `version_compare(jsonVersion, storedVersion) > 0`. This avoids unnecessary writes on every request and preserves admin-configured register/schema selections across app upgrades.

5. **Silent failure on boot.** If `initialize()` throws (e.g. OpenRegister not installed, JSON file missing or invalid), the error is logged at `error` level and the app continues booting normally. The settings page still renders; the register dropdowns show an empty state.

6. **Double catch in `getAllSettings()`.** OpenRegister's `RegisterService::findAll()` can throw `\TypeError` when schema `properties` is null. Both `\TypeError` and generic `\Exception` are caught, logged as warnings with file/line context, and the response returns `availableRegisters: []`. The settings page remains fully functional.

7. **Schema listing excludes `properties` field.** Register listing strips `properties` from each schema object to reduce response size and avoid the `\TypeError` trigger on null properties.

8. **WOO default of 28 days.** The default objection period satisfies WOO art. 4.4 minimum (4 weeks). The UI accepts 1–365 but displays descriptive text warning when a value below 28 may violate WOO requirements. Admins are informed but not blocked — they retain full control.

## Architecture

### Backend
- `lib/Settings/DocuDeskAdmin.php` — `ISettings`; renders `settings` template
- `lib/Sections/DocuDeskAdmin.php` — `IIconSection`; admin section with `app-dark.svg`
- `lib/Controller/SettingsController.php` — REST controller; `GET/POST /api/settings`
- `lib/Service/SettingsService.php` — `initialize()`, `getAllSettings()`, `updateSettings()`, helper methods
- `lib/Service/SettingsInitializer.php` — initialization logic; imports `docudesk_register.json` via `ConfigurationService`
- `lib/Service/RegisterDiscoveryService.php` — fetches and filters registers/schemas from OpenRegister; strips `properties`
- `lib/Settings/docudesk_register.json` — OpenAPI 3.0.0 register definition with `x-openregister` extensions
- `lib/AppInfo/Application.php` — `boot()` calls `SettingsService::initialize()`
- `appinfo/routes.php` — `settings#index` (GET) and `settings#create` (POST)

### Frontend
- `src/views/settings/Settings.vue` — admin settings page; four `NcSettingsSection` blocks
- `src/settings.js` — Webpack entry point registered via `info.xml` admin settings

### Data Flow
Settings are stored in `oc_appconfig` under the `docudesk` app scope. `GET /api/settings` reads all keys via `IAppConfig` and merges them with live register data from OpenRegister. `POST /api/settings` iterates submitted key-value pairs, JSON-encodes arrays, skips empty keys with a warning log, and calls `IAppConfig::setValueString()` for each valid entry.

### Settings API Response Structure

```json
{
  "objectTypes": ["publicationConsent", "template"],
  "openRegisters": true,
  "availableRegisters": [...],
  "configuration": {
    "publicationConsent_source": "openregister",
    "publicationConsent_schema": "...",
    "publicationConsent_register": "..."
  },
  "publication_objection_period_days": 28,
  "enable_language_detection": true,
  "enable_keyword_extraction": true,
  "enable_topic_classification": true
}
```

### Configuration Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `publicationConsent_register` | string | `""` | OpenRegister register ID for consent objects |
| `publicationConsent_schema` | string | `""` | OpenRegister schema ID for consent objects |
| `publicationConsent_source` | string | `"openregister"` | Data source (always "openregister") |
| `publication_objection_period_days` | string (int) | `"28"` | WOO objection period in days |
| `enable_language_detection` | string (bool) | `"1"` | Enable language detection enrichment |
| `enable_keyword_extraction` | string (bool) | `"1"` | Enable keyword extraction enrichment |
| `enable_topic_classification` | string (bool) | `"1"` | Enable topic classification enrichment |
| `configuration_version` | string | `"0.0.0"` | Current imported configuration schema version |

## Reuse Analysis

- **`IAppConfig`**: Nextcloud built-in for key-value config storage — no custom table required.
- **`IAppManager`**: Nextcloud built-in used for checking OpenRegister installation and enablement.
- **`ConfigurationService::importFromApp()`**: OpenRegister built-in for idempotent JSON import with version comparison.
- **`RegisterService::findAll()`**: OpenRegister built-in for register/schema listing.
- **`ObjectService`**: OpenRegister built-in used by `ConsentController` via `SettingsService::getObjectService()`.
- No custom CRUD, no custom search, no custom file handling — all delegated to platform services.
- No overlap identified with existing DocuDesk services at time of implementation.

## Risks and Mitigations

- **Risk:** OpenRegister version mismatch silently disables consent storage. **Mitigation:** `isOpenRegisterInstalled()` enforces explicit version floor (0.2.10); settings page shows `NcNoteCard` warning and install button when not available.
- **Risk:** `docudesk_register.json` missing after deploy. **Mitigation:** `initialize()` validates file existence, readability, JSON validity, and version presence; throws descriptive `RuntimeException` per failure mode, caught by `boot()`.
- **Risk:** Admin sets objection period below WOO minimum. **Mitigation:** UI accepts 1–365 but displays descriptive text warning about the 4-week WOO art. 4.4 requirement — admin makes an informed choice rather than being blocked.
- **Risk:** OpenRegister internal `TypeError` crashes settings page. **Mitigation:** Double catch (`\TypeError` + `\Exception`) in `getAllSettings()` falls back to empty register list without surfacing an error to the admin.

## Open Questions

- Should the WOO objection period below 28 days be a hard validation error rather than an informational warning? Deferred — current interpretation is that administrators must remain in control; the UI informs but does not block.
