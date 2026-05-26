---
status: implemented
---

# Admin Settings

## Purpose

Provides configuration management for DocuDesk, including OpenRegister integration setup, GDPR consent period configuration, and metadata enrichment feature toggles. Settings are exposed both via the Nextcloud Admin Settings panel (under a dedicated DocuDesk section) and via a REST API. On application boot, DocuDesk automatically initializes its OpenRegister configuration by importing the register/schema definitions from `docudesk_register.json`.

## Requirements

### Requirement: Nextcloud Admin Panel Integration (REQ-SET-01)

**Priority:** Must

DocuDesk registers a dedicated section in the Nextcloud admin settings panel with its own icon, accessible only to administrators.

#### Scenario: Admin opens DocuDesk settings section
- GIVEN an administrator is logged into Nextcloud
- WHEN they navigate to Admin Settings
- THEN a "DocuDesk" section appears in the left navigation
- AND it uses the DocuDesk app icon (`app-dark.svg`)
- AND clicking it renders the DocuDesk settings page

#### Scenario: Non-admin cannot access settings
- GIVEN a regular user is logged into Nextcloud
- WHEN they navigate to Admin Settings
- THEN the DocuDesk section is not visible
- AND direct URL access to the settings page returns a 403 error

#### Scenario: Settings page renders Vue component
- GIVEN an administrator opens the DocuDesk settings section
- WHEN the settings page loads
- THEN the `Settings.vue` component is rendered via the `settings` entry point
- AND all configuration sections are displayed in NcSettingsSection blocks

#### Scenario: Settings section registration
@e2e exclude pure PHP class registration — verified by Nextcloud admin panel presence (covered by admin-opens-docudesk-settings-section)
- GIVEN DocuDesk is installed and enabled
- WHEN Nextcloud loads admin sections
- THEN `OCA\DocuDesk\Settings\DocuDeskAdmin` (ISettings) provides the form template
- AND `OCA\DocuDesk\Sections\DocuDeskAdmin` (IIconSection) provides the section with icon

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-001 | DocuDesk has a dedicated admin section in the Nextcloud admin settings panel | MUST | Implemented |
| SET-002 | The admin section uses the DocuDesk app icon (`app-dark.svg`) | MUST | Implemented |
| SET-003 | The admin section is registered via `OCA\DocuDesk\Settings\DocuDeskAdmin` (ISettings) and `OCA\DocuDesk\Sections\DocuDeskAdmin` (IIconSection) -- two separate classes in different namespaces | MUST | Implemented |
| SET-004 | Settings are rendered using a Vue component (`Settings.vue`) via the `settings` entry point | MUST | Implemented |

### Requirement: OpenRegister Integration Configuration (REQ-SET-02)

**Priority:** Must

Administrators can configure which OpenRegister register and schema to use for consent object storage, with validation and discovery of available registers.

#### Scenario: Configure consent register and schema
- GIVEN an administrator opens the DocuDesk admin settings
- AND OpenRegister is installed and enabled (>= v0.2.10)
- WHEN they select a register from the available registers dropdown
- AND select a schema from the schemas within that register
- AND click Save
- THEN the register and schema IDs are stored in IAppConfig as `publicationConsent_register` and `publicationConsent_schema`
- AND consent endpoints use the newly configured register/schema

#### Scenario: OpenRegister version check fails
@e2e exclude pure backend version-gate logic; tested by PHPUnit; no reproducible UI state in this env (OR meets version)
- GIVEN OpenRegister is installed but version is 0.2.9 (below minimum 0.2.10)
- WHEN the settings service checks OpenRegister availability
- THEN `isOpenRegisterInstalled()` returns false
- AND the settings page shows register configuration as unavailable

#### Scenario: OpenRegister not installed
- GIVEN OpenRegister is not installed
- WHEN an administrator opens DocuDesk admin settings
- THEN a warning NcNoteCard is displayed: "Open Registers is not installed"
- AND an "Install Open Registers" button is shown linking to the Nextcloud app store
- AND register/schema selectors are not displayed

#### Scenario: Schema listing excludes properties
@e2e exclude API response shape — backend serialization verified by PHPUnit; not observable in UI (properties column never rendered)
- GIVEN OpenRegister is installed with multiple registers containing schemas
- WHEN the settings page fetches available registers
- THEN each schema in the response excludes the `properties` field for cleaner display
- AND only essential schema metadata (id, name, slug) is included

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-010 | Check if OpenRegister is installed and meets minimum version requirement (>= 0.2.10) | MUST | Implemented |
| SET-011 | Check if OpenRegister is enabled for the current user | MUST | Implemented |
| SET-012 | Display a warning NcNoteCard when OpenRegister is not installed, with an install button | MUST | Implemented |
| SET-013 | List all available registers with their schemas for selection | MUST | Implemented |
| SET-014 | Configure the publicationConsent object type with a selected register and schema | MUST | Implemented |
| SET-015 | Store register/schema configuration as `publicationConsent_register`, `publicationConsent_schema`, `publicationConsent_source` in IAppConfig | MUST | Implemented |
| SET-016 | Schema listing excludes the `properties` field for cleaner API responses | MUST | Implemented |

### Requirement: Auto-Initialization on Boot (REQ-SET-03)

**Priority:** Must

On application boot, DocuDesk automatically imports its register/schema definitions from a versioned JSON file, ensuring the required data structures exist in OpenRegister.

#### Scenario: First boot auto-initialization
@e2e exclude pure backend initialization flow — Application::boot() logic verified by PHPUnit; not observable in UI
- GIVEN DocuDesk is freshly installed
- AND OpenRegister is installed and enabled (>= v0.2.10)
- WHEN the app boots for the first time
- THEN `Application::boot()` calls `SettingsService::initialize()`
- AND the Consent Register and PublicationConsent schema are auto-created from `docudesk_register.json`
- AND the `configuration_version` is updated to the version in the JSON file

#### Scenario: Version-gated configuration import
@e2e exclude pure backend version-gate on initialization — verified by PHPUnit; not UI-observable
- GIVEN DocuDesk has been initialized with configuration_version "0.0.1"
- AND the `docudesk_register.json` file has been updated to version "0.0.2"
- WHEN the app boots
- THEN the configuration is re-imported because the JSON version is higher
- AND `configuration_version` is updated to "0.0.2"

#### Scenario: Same version skips re-import
@e2e exclude pure backend idempotency guard — verified by PHPUnit; not UI-observable
- GIVEN DocuDesk has been initialized with configuration_version "0.0.1"
- AND the `docudesk_register.json` file has version "0.0.1"
- WHEN the app boots
- THEN the configuration import is skipped (versions match)
- AND no unnecessary writes to OpenRegister occur

#### Scenario: Initialization failure does not crash app
@e2e exclude backend silent-fail behavior — verified by PHPUnit; not observable in UI without forced failure injection
- GIVEN DocuDesk is booting
- AND OpenRegister's ConfigurationService throws an exception during import
- WHEN Application::boot() calls initialize()
- THEN the error is logged
- AND the app continues to start normally (silent failure)
- AND the settings page still renders

#### Scenario: JSON file validation
@e2e exclude pure backend file-validation chain — verified by PHPUnit unit tests
- GIVEN the `docudesk_register.json` file exists in `lib/Settings/`
- WHEN SettingsService reads the file during initialization
- THEN file existence, readability, JSON validity, and version presence are all validated
- AND descriptive RuntimeException messages are thrown for each validation failure

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-020 | On Application::boot(), automatically import configuration from `docudesk_register.json` via ConfigurationService::importFromApp() | MUST | Implemented |
| SET-021 | Configuration import is version-gated: only runs if the JSON version is higher than the stored configuration_version | MUST | Implemented |
| SET-022 | Initialization failures are logged but do not prevent app startup (silent failure) | MUST | Implemented |
| SET-023 | The `docudesk_register.json` file follows OpenAPI 3.0.0 format with `x-openregister` extensions | MUST | Implemented |

### Requirement: WOO Consent Period Configuration (REQ-SET-04)

**Priority:** Must

Administrators can configure the publication objection period per WOO requirements, with validation ensuring compliance with the minimum 4-week objection period.

#### Scenario: Adjust objection period to 42 days
- GIVEN an administrator opens the DocuDesk admin settings
- WHEN they change the objection period to 42 days
- AND click Save All Settings
- THEN the `publication_objection_period_days` is updated to "42"
- AND new consent records will use a 42-day objection deadline

#### Scenario: Objection period below minimum
- GIVEN an administrator opens the DocuDesk admin settings
- WHEN they attempt to set the objection period to 14 days (below WOO minimum of 28)
- THEN the input accepts the value (1-365 range) but descriptive text warns about WOO 4-week minimum
- AND the administrator is informed this may violate WOO requirements

#### Scenario: Default objection period
@e2e exclude consent record creation is not UI-accessible (no POST /api/consents endpoint); backend default verified by PHPUnit
- GIVEN DocuDesk is freshly installed with no custom settings
- WHEN a consent record is created
- THEN the default objection period of 28 days (4 weeks) is used
- AND this satisfies the WOO minimum requirement

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-030 | Configure the publication objection period in days (default: 28) | MUST | Implemented |
| SET-031 | Objection period input accepts values from 1 to 365 | MUST | Implemented |
| SET-032 | Display descriptive text referencing WOO minimum 4-week requirement | MUST | Implemented |

### Requirement: Metadata Enrichment Feature Toggles (REQ-SET-05)

**Priority:** Must

Administrators can independently toggle language detection, keyword extraction, and topic classification features on or off.

#### Scenario: Disable keyword extraction
- GIVEN an administrator opens the DocuDesk admin settings
- WHEN they toggle off keyword extraction
- AND click Save All Settings
- THEN `enable_keyword_extraction` is set to "0"
- AND the event listener skips keyword extraction on new/updated objects
- AND language detection and topic classification continue to run

#### Scenario: All enrichment features enabled by default
- GIVEN DocuDesk is freshly installed
- WHEN an administrator opens the settings page
- THEN all three toggles (language detection, keyword extraction, topic classification) are enabled
- AND each toggle uses an NcCheckboxRadioSwitch component with descriptive label

#### Scenario: Disable all enrichment features
@e2e exclude event-listener behavior is not observable in UI — backend side-effect verified by PHPUnit; UI part (saving toggles) covered by disable-keyword-extraction test
- GIVEN an administrator toggles off all three enrichment features
- AND clicks Save All Settings
- WHEN an ObjectCreatedEvent fires in OpenRegister
- THEN the event listener checks settings and finds all features disabled
- AND no metadata enrichment processing occurs

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-040 | Toggle language detection on/off (`enable_language_detection`, default: enabled) | MUST | Implemented |
| SET-041 | Toggle keyword extraction on/off (`enable_keyword_extraction`, default: enabled) | MUST | Implemented |
| SET-042 | Toggle topic classification on/off (`enable_topic_classification`, default: enabled) | MUST | Implemented |
| SET-043 | Toggles use NcCheckboxRadioSwitch components with descriptive labels | MUST | Implemented |

### Requirement: Settings REST API (REQ-SET-06)

**Priority:** Must

Settings can be retrieved and updated programmatically via REST API endpoints.

#### Scenario: Retrieve all settings
@e2e exclude raw API response shape — SettingsController JSON structure verified by PHPUnit; UI displays parsed values (covered by other settings UI tests)
- GIVEN DocuDesk is configured with consent register and enrichment toggles
- WHEN GET /api/settings is called
- THEN the response includes objectTypes, openRegisters flag, availableRegisters, configuration object, and feature toggle values
- AND the response format matches the documented structure

#### Scenario: Update settings via API
@e2e exclude direct REST API call — backend IAppConfig update verified by PHPUnit; UI save flow covered by adjust-objection-period test
- GIVEN an authenticated administrator
- WHEN POST /api/settings is called with `{"publication_objection_period_days": "42"}`
- THEN the setting is updated in IAppConfig
- AND the response includes the updated value
- AND a log entry confirms the update

#### Scenario: Array values are JSON-encoded
@e2e exclude backend serialization detail — verified by PHPUnit; no UI surface for array settings
- GIVEN an authenticated administrator
- WHEN POST /api/settings is called with an array value `{"custom_list": ["a", "b"]}`
- THEN the array is JSON-encoded before storage in IAppConfig
- AND retrieving the setting returns the JSON string

#### Scenario: Empty keys are skipped
@e2e exclude backend input-sanitization — verified by PHPUnit; not observable via UI
- GIVEN an authenticated administrator
- WHEN POST /api/settings is called with `{"": "some_value"}`
- THEN the empty key is skipped with a warning log
- AND no error is returned

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-050 | Retrieve all settings via `GET /api/settings` | MUST | Implemented |
| SET-051 | Update settings via `POST /api/settings` | MUST | Implemented |
| SET-052 | Settings response includes: objectTypes, openRegisters flag, availableRegisters list, configuration object, and individual setting values | MUST | Implemented |
| SET-053 | Array/object values are JSON-encoded before storage in IAppConfig | MUST | Implemented |
| SET-054 | Empty keys are skipped with a warning log during update | MUST | Implemented |

### Requirement: SettingsService Public Helper Methods (REQ-SET-07)

**Priority:** Must

SettingsService exposes reusable public methods for OpenRegister service resolution and availability checking, used by other DocuDesk services and controllers.

#### Scenario: ConsentController uses getObjectService
@e2e exclude internal DI wiring — SettingsService helper method verified by PHPUnit; no direct UI surface
- GIVEN ConsentController needs to query consent records
- WHEN it calls `$this->settingsService->getObjectService()`
- THEN the ObjectService is lazily resolved from the container
- AND the controller can query consent records directly

#### Scenario: OpenRegister not available throws RuntimeException
@e2e exclude backend exception propagation — verified by PHPUnit; no reproducible UI state in this env
- GIVEN OpenRegister is not installed
- WHEN any service calls `getObjectService()` or `getConfigurationService()`
- THEN a `\RuntimeException` is thrown with descriptive message
- AND the caller can handle the exception gracefully

#### Scenario: Initialize checks both installed and enabled
@e2e exclude backend initialization guards — verified by PHPUnit; not directly observable in UI
- GIVEN DocuDesk is booting
- WHEN `initialize()` is called
- THEN it first checks `isOpenRegisterInstalled()` for version >= 0.2.10
- AND then checks `isOpenRegisterEnabled()` for current user
- AND proceeds only if both return true

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-060 | `isOpenRegisterInstalled()` checks installation status and optional version minimum (default 0.2.10) | MUST | Implemented |
| SET-061 | `isOpenRegisterEnabled()` checks if OpenRegister is enabled for the current user | MUST | Implemented |
| SET-062 | `getObjectService()` provides public access to the OpenRegister ObjectService via lazy resolution | MUST | Implemented |
| SET-063 | `getConfigurationService()` provides public access to the OpenRegister ConfigurationService via lazy resolution | MUST | Implemented |
| SET-064 | Both service getters throw `\RuntimeException` when OpenRegister is not available | MUST | Implemented |

### Requirement: App Metadata and Compatibility (REQ-SET-08)

**Priority:** Must

DocuDesk declares platform compatibility and app identity in its `appinfo/info.xml`.

#### Scenario: Database compatibility verification
@e2e exclude app metadata in info.xml — platform compatibility verified at install time by Nextcloud; not UI-observable
- GIVEN DocuDesk is installed on a PostgreSQL 10+ server
- WHEN the app is enabled
- THEN the app functions correctly with PostgreSQL as the primary database
- AND SQLite and MySQL 8.0+ are also supported

#### Scenario: PHP version check
@e2e exclude app metadata in info.xml — PHP version gate enforced by Nextcloud at install time; not UI-testable
- GIVEN a server running PHP 7.4
- WHEN attempting to install DocuDesk
- THEN the installation fails because PHP 8.0+ with 64-bit integer support is required

#### Scenario: Nextcloud version compatibility
@e2e exclude app metadata in info.xml — NC version compatibility enforced by app marketplace; not UI-testable
- GIVEN Nextcloud version 27 is running
- WHEN attempting to enable DocuDesk
- THEN the app cannot be enabled because the minimum required version is Nextcloud 28
- AND the maximum supported version is Nextcloud 32

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-070 | App supports PostgreSQL 10+, SQLite, and MySQL 8.0+ | MUST | Implemented |
| SET-071 | App requires PHP 8.0+ with 64-bit integer support | MUST | Implemented |
| SET-072 | App is compatible with Nextcloud versions 28 through 32 | MUST | Implemented |

### Requirement: External Documentation URLs (REQ-SET-09)

**Priority:** Must

DocuDesk references external documentation for users, administrators, and developers.

#### Scenario: User accesses documentation link
- GIVEN a user is on the DocuDesk settings page
- WHEN they click the documentation link
- THEN they are directed to `https://docudesk.app`
- AND comprehensive user, admin, and developer documentation is available

#### Scenario: Roadmap access
@e2e exclude info.xml metadata link — not surfaced in the Settings.vue UI; verified by static analysis
- GIVEN a user wants to check the DocuDesk roadmap
- WHEN they access the roadmap URL from info.xml
- THEN they are directed to the GitHub Projects board
- AND can see planned features and current progress

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-073 | External docs are hosted at conduction.gitbook.io/docudesk-nextcloud (in info.xml); Settings.vue uses `https://docudesk.app` as doc-url | MUST | Implemented |
| SET-074 | All doc types (user, admin, developer) in info.xml use the same Gitbook URL | MUST | Implemented |
| SET-075 | Roadmap is tracked at GitHub Projects | MUST | Implemented |

### Requirement: Configuration File Resolution and Validation (REQ-SET-10)

**Priority:** Must

SettingsService resolves and validates the configuration JSON file with a strict validation chain.

#### Scenario: Successful configuration file loading
@e2e exclude internal file resolution path — backend file-load logic verified by PHPUnit
- GIVEN the `docudesk_register.json` file exists at `lib/Settings/docudesk_register.json`
- WHEN SettingsService reads the file during initialization
- THEN the file is resolved via relative path `__DIR__.'/../Settings/docudesk_register.json'`
- AND the JSON content is parsed and version is extracted

#### Scenario: Missing configuration file
@e2e exclude backend exception path — RuntimeException on missing file verified by PHPUnit
- GIVEN the `docudesk_register.json` file is missing from `lib/Settings/`
- WHEN SettingsService attempts to load the file
- THEN a RuntimeException is thrown with "Configuration file not found" message
- AND the initialization fails gracefully without crashing the app

#### Scenario: Invalid JSON in configuration file
@e2e exclude backend exception path — RuntimeException on invalid JSON verified by PHPUnit
- GIVEN the `docudesk_register.json` file contains invalid JSON
- WHEN SettingsService attempts to parse the file
- THEN a RuntimeException is thrown with "Invalid JSON" message
- AND the error includes details about the parse failure

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-076 | Settings file path is resolved relative to SettingsService via `__DIR__.'/../Settings/docudesk_register.json'` | MUST | Implemented |
| SET-077 | File existence, readability, JSON validity, and version presence are all validated with descriptive RuntimeException messages | MUST | Implemented |

### Requirement: TypeError Catch Fallback for OpenRegister (REQ-SET-11)

**Priority:** Must

Settings retrieval gracefully handles TypeErrors from OpenRegister internals to prevent crashes.

#### Scenario: OpenRegister throws TypeError during register listing
@e2e exclude backend catch-block behavior — TypeError recovery logic verified by PHPUnit; not reproducible in UI
- GIVEN OpenRegister has inconsistent data (e.g., null schema properties)
- WHEN `getAllSettings()` calls `RegisterService::findAll()`
- AND a TypeError is thrown internally
- THEN the error is caught and logged as a warning
- AND `availableRegisters` is set to an empty array
- AND the settings page remains functional

#### Scenario: OpenRegister throws generic Exception during register listing
@e2e exclude backend catch-block behavior — generic Exception recovery verified by PHPUnit; not reproducible in UI
- GIVEN OpenRegister's `findAll()` throws a generic Exception
- WHEN `getAllSettings()` processes the call
- THEN the error is caught and logged as a warning with different diagnostic message
- AND `availableRegisters` is set to an empty array
- AND the settings page remains functional

#### Scenario: Settings page resilient to OpenRegister failures
- GIVEN OpenRegister is installed but experiencing internal errors
- WHEN an administrator opens the DocuDesk settings page
- THEN the page loads successfully
- AND register selection dropdowns are empty but no error is shown to the user
- AND other settings (consent period, enrichment toggles) remain fully functional

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-078 | `getAllSettings()` catches `\TypeError` from OpenRegister internals and falls back to empty register list | MUST | Implemented |
| SET-079 | `getAllSettings()` catches generic `Exception` from `findAll()` and falls back to empty register list | MUST | Implemented |
| SET-080 | Both catch blocks log warnings with exception details (message, file, line) for diagnostics | MUST | Implemented |
| SET-081 | The settings page remains functional even when OpenRegister throws internal errors | MUST | Implemented |

## Data Model

### Settings Configuration Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| publicationConsent_register | string | "" | OpenRegister register ID for consent objects |
| publicationConsent_schema | string | "" | OpenRegister schema ID for consent objects |
| publicationConsent_source | string | "openregister" | Data source (always "openregister") |
| publication_objection_period_days | string (integer) | "28" | Objection period in days |
| enable_language_detection | string (boolean) | "1" | Enable language detection |
| enable_keyword_extraction | string (boolean) | "1" | Enable keyword extraction |
| enable_topic_classification | string (boolean) | "1" | Enable topic classification |
| configuration_version | string | "0.0.0" | Current configuration schema version |

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

## User Interface

### Admin Settings Page (`Settings.vue`)

The settings page is divided into four NcSettingsSection blocks:

1. **DocuDesk header**: App name and description with documentation link
2. **Consent Settings**: Objection period input (number, 1-365) with WOO compliance description
3. **Metadata Enrichment**: Three toggle switches (language detection, keyword extraction, topic classification) with descriptive text
4. **Data Storage**: OpenRegister integration configuration
   - NcNoteCard warning when OpenRegister is not installed
   - Install button linking to Nextcloud app store
   - Per object type (publicationConsent): register selector, schema selector, save button
5. **Save All Settings button**: Saves all settings (consent + enrichment + register config) in one request

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/settings` | Retrieve all settings |
| POST | `/api/settings` | Update settings |

## Dependencies

- **Nextcloud IAppConfig**: Key-value configuration storage
- **Nextcloud ISettings / IIconSection**: Admin panel integration
- **OpenRegister RegisterService**: Listing available registers with schemas (injected via constructor)
- **OpenRegister ConfigurationService**: Auto-import of register/schema definitions from JSON (resolved via container)
- **OpenRegister ObjectService**: Used by SettingsController for consent queries (resolved via container)
- **Nextcloud IAppManager**: Checking OpenRegister installation and enablement status
- **PSR ContainerInterface**: Lazy service resolution for OpenRegister services

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/SettingsService.php` -- core settings service with `initialize()`, `loadSettings()`, `getAllSettings()`, helper methods
  - `lib/Service/SettingsInitializer.php` -- initialization logic extracted from SettingsService
  - `lib/Service/RegisterDiscoveryService.php` -- register discovery and configuration loading
  - `lib/Controller/SettingsController.php` -- REST API controller for `GET /api/settings` and `POST /api/settings`
  - `lib/Settings/DocuDeskAdmin.php` -- Nextcloud admin settings panel (ISettings), renders `settings` template
  - `lib/Sections/DocuDeskAdmin.php` -- Nextcloud admin section icon (IIconSection), uses `app-dark.svg`
  - `lib/Settings/docudesk_register.json` -- OpenAPI 3.0.0 register definition with `x-openregister` extensions
  - `src/views/settings/Settings.vue` -- Vue admin settings UI with consent period, enrichment toggles, register config
  - `src/settings.js` -- Webpack entry point for the admin settings page
  - `lib/AppInfo/Application.php` -- `boot()` calls `SettingsService::initialize()` for auto-import
  - `appinfo/routes.php` -- routes for `settings#index` (GET) and `settings#create` (POST)

### Standards & References
- **WOO (Wet open overheid)**: Referenced in the consent objection period setting (minimum 4-week period per WOO art. 4.4)
- **GDPR/AVG**: Consent management settings relate to GDPR data protection requirements
- **Nextcloud ISettings/IIconSection API**: Standard Nextcloud admin panel integration pattern
- **OpenAPI 3.0.0**: The `docudesk_register.json` follows OpenAPI format with custom `x-openregister` extensions
