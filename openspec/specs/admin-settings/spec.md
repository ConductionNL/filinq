# Admin Settings

## Purpose

Provides configuration management for DocuDesk, including OpenRegister integration setup, GDPR consent period configuration, and metadata enrichment feature toggles. Settings are exposed both via the Nextcloud Admin Settings panel (under a dedicated DocuDesk section) and via a REST API. On application boot, DocuDesk automatically initializes its OpenRegister configuration by importing the register/schema definitions from `docudesk_register.json`.

## Requirements

### Nextcloud Admin Panel Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-001 | DocuDesk has a dedicated admin section in the Nextcloud admin settings panel | MUST | Implemented |
| SET-002 | The admin section uses the DocuDesk app icon (`app-dark.svg`) | MUST | Implemented |
| SET-003 | The admin section is registered via `DocuDeskAdmin` ISettings and `DocuDeskAdmin` IIconSection | MUST | Implemented |
| SET-004 | Settings are rendered using a Vue component (`Settings.vue`) via the `settings` entry point | MUST | Implemented |

### OpenRegister Integration Configuration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-010 | Check if OpenRegister is installed and meets minimum version requirement (>= 0.2.10) | MUST | Implemented |
| SET-011 | Check if OpenRegister is enabled for the current user | MUST | Implemented |
| SET-012 | Display a warning NcNoteCard when OpenRegister is not installed, with an install button | MUST | Implemented |
| SET-013 | List all available registers with their schemas for selection | MUST | Implemented |
| SET-014 | Configure the publicationConsent object type with a selected register and schema | MUST | Implemented |
| SET-015 | Store register/schema configuration as `publicationConsent_register`, `publicationConsent_schema`, `publicationConsent_source` in IAppConfig | MUST | Implemented |
| SET-016 | Schema listing excludes the `properties` field for cleaner API responses | MUST | Implemented |

### Auto-Initialization

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-020 | On Application::boot(), automatically import configuration from `docudesk_register.json` via ConfigurationService::importFromApp() | MUST | Implemented |
| SET-021 | Configuration import is version-gated: only runs if the JSON version is higher than the stored configuration_version | MUST | Implemented |
| SET-022 | Initialization failures are logged but do not prevent app startup (silent failure) | MUST | Implemented |
| SET-023 | The `docudesk_register.json` file follows OpenAPI 3.0.0 format with `x-openregister` extensions | MUST | Implemented |

### Consent Settings

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-030 | Configure the publication objection period in days (default: 28) | MUST | Implemented |
| SET-031 | Objection period input accepts values from 1 to 365 | MUST | Implemented |
| SET-032 | Display descriptive text referencing WOO minimum 4-week requirement | MUST | Implemented |

### Metadata Enrichment Toggles

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-040 | Toggle language detection on/off (`enable_language_detection`, default: enabled) | MUST | Implemented |
| SET-041 | Toggle keyword extraction on/off (`enable_keyword_extraction`, default: enabled) | MUST | Implemented |
| SET-042 | Toggle topic classification on/off (`enable_topic_classification`, default: enabled) | MUST | Implemented |
| SET-043 | Toggles use NcCheckboxRadioSwitch components with descriptive labels | MUST | Implemented |

### Settings API

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-050 | Retrieve all settings via `GET /api/settings` | MUST | Implemented |
| SET-051 | Update settings via `POST /api/settings` | MUST | Implemented |
| SET-052 | Settings response includes: objectTypes, openRegisters flag, availableRegisters list, configuration object, and individual setting values | MUST | Implemented |
| SET-053 | Array/object values are JSON-encoded before storage in IAppConfig | MUST | Implemented |
| SET-054 | Empty keys are skipped with a warning log during update | MUST | Implemented |

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
  "objectTypes": ["publicationConsent"],
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

## Scenarios

### Initial Setup

```
GIVEN DocuDesk is freshly installed
AND OpenRegister is installed and enabled (>= v0.2.10)
WHEN the app boots for the first time
THEN the Consent Register and PublicationConsent schema are auto-created from docudesk_register.json
AND the configuration_version is updated
```

### Configure Consent Register

```
GIVEN an administrator opens the DocuDesk admin settings
WHEN they select a register and schema for publicationConsent
AND click Save
THEN the register and schema IDs are stored in IAppConfig
AND consent endpoints use the configured register/schema
```

### Adjust Objection Period

```
GIVEN an administrator opens the DocuDesk admin settings
WHEN they change the objection period to 42 days
AND click Save All Settings
THEN the publication_objection_period_days is updated to "42"
AND new consent records will use a 42-day objection deadline
```

### Disable Enrichment Feature

```
GIVEN an administrator opens the DocuDesk admin settings
WHEN they toggle off keyword extraction
AND click Save All Settings
THEN enable_keyword_extraction is set to "0"
AND the event listener skips keyword extraction on new/updated objects
```

### OpenRegister Not Installed

```
GIVEN OpenRegister is not installed
WHEN an administrator opens DocuDesk admin settings
THEN a warning note card is displayed: "Open Registers is not installed"
AND an "Install Open Registers" button is shown linking to the app store
AND register/schema selectors are not displayed
```

## Internal Implementation Details

### SettingsService Public Helper Methods (Gap 4)

SettingsService exposes 4 public helper methods that serve as reusable APIs for other services and controllers:

| Method | Return Type | Purpose |
|--------|-------------|---------|
| `isOpenRegisterInstalled(?string $minVersion)` | `bool` | Checks if OpenRegister is installed and optionally meets a minimum version (default: `0.2.10`). Uses `IAppManager::isInstalled()` + `version_compare()`. |
| `isOpenRegisterEnabled()` | `bool` | Checks if OpenRegister is enabled for the current user via `IAppManager::isEnabledForUser()`. |
| `getObjectService()` | `?ObjectService` | Resolves ObjectService from the container. Uses `getInstalledApps()` + `container->get()` pattern. Throws `\RuntimeException` if unavailable. Return type is nullable but it always either returns or throws. |
| `getConfigurationService()` | `?ConfigurationService` | Resolves ConfigurationService from the container. Same pattern as getObjectService(). |

**Usage by other components**:
- `ConsentController::index()` and `show()` call `$this->settingsService->getObjectService()` to query consent records directly
- `ConsentController` uses `$this->settingsService->getAllSettings()` to get register/schema config
- The `initialize()` method calls both `isOpenRegisterInstalled()` and `isOpenRegisterEnabled()` during boot

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-060 | `isOpenRegisterInstalled()` checks installation status and optional version minimum (default 0.2.10) | MUST | Implemented |
| SET-061 | `isOpenRegisterEnabled()` checks if OpenRegister is enabled for the current user | MUST | Implemented |
| SET-062 | `getObjectService()` provides public access to the OpenRegister ObjectService via lazy resolution | MUST | Implemented |
| SET-063 | `getConfigurationService()` provides public access to the OpenRegister ConfigurationService via lazy resolution | MUST | Implemented |
| SET-064 | Both service getters throw `\RuntimeException` when OpenRegister is not available | MUST | Implemented |

### App Metadata and Compatibility (Gap 20)

DocuDesk's `appinfo/info.xml` declares the following platform compatibility:

**Database Compatibility**:

| Database | Minimum Version | Notes |
|----------|----------------|-------|
| PostgreSQL | 10+ | Primary target, required for pgvector in OpenRegister |
| SQLite | Any | Supported for development/testing |
| MySQL | 8.0+ | Supported |

**PHP Requirements**:
- PHP 8.0+ with 64-bit integer support (`min-int-size="64"`)

**Nextcloud Compatibility**:
- Minimum: Nextcloud 28
- Maximum: Nextcloud 32

**App Identity**:
- ID: `docudesk`
- Namespace: `DocuDesk`
- Category: `organization`
- License: EUPL-1.2
- Current version: `0.0.32-unstable.2`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-070 | App supports PostgreSQL 10+, SQLite, and MySQL 8.0+ | MUST | Implemented |
| SET-071 | App requires PHP 8.0+ with 64-bit integer support | MUST | Implemented |
| SET-072 | App is compatible with Nextcloud versions 28 through 32 | MUST | Implemented |

### External Documentation URLs (Gap 21)

DocuDesk references external documentation hosted on Conduction's Gitbook:

| Documentation Type | URL |
|-------------------|-----|
| User docs | `https://conduction.gitbook.io/docudesk-nextcloud/` |
| Admin docs | `https://conduction.gitbook.io/docudesk-nextcloud/` |
| Developer docs | `https://conduction.gitbook.io/docudesk-nextcloud/` |
| Installation requirements | `https://conduction.gitbook.io/docudesk-nextcloud/installatie` |
| Roadmap | `https://github.com/orgs/docudesk/projects/1/views/2` |
| Bug reports | `https://github.com/docudesk/.github/issues/new/choose` |
| Feature requests | `https://github.com/docudesk/.github/issues/new/choose` |

All three documentation types (user, admin, developer) point to the same URL. The installation-specific docs are referenced in the app description.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-073 | External docs are hosted at conduction.gitbook.io/docudesk-nextcloud | MUST | Implemented |
| SET-074 | All doc types (user, admin, developer) use the same Gitbook URL | MUST | Implemented |
| SET-075 | Roadmap is tracked at GitHub Projects | MUST | Implemented |

### File Path Resolution for docudesk_register.json (Gap 22)

SettingsService resolves the configuration JSON file using a relative path from the PHP file location:

```php
$settingsFilePath = __DIR__.'/../Settings/docudesk_register.json';
```

**Resolution**: Since `SettingsService.php` is in `lib/Service/`, this resolves to `lib/Settings/docudesk_register.json`.

**Validation chain**:
1. `file_exists($settingsFilePath)` -- throws RuntimeException if file not found
2. `file_get_contents($settingsFilePath)` -- throws RuntimeException if read fails
3. `json_decode($jsonContent, true)` -- throws RuntimeException if JSON is invalid
4. `isset($settings['info']['version'])` -- throws RuntimeException if version is missing

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-076 | Settings file path is resolved relative to SettingsService via `__DIR__.'/../Settings/docudesk_register.json'` | MUST | Implemented |
| SET-077 | File existence, readability, JSON validity, and version presence are all validated with descriptive RuntimeException messages | MUST | Implemented |

### TypeError Catch Fallback (Gap 23)

`SettingsService::getAllSettings()` has a specific `\TypeError` catch block when calling `RegisterService::findAll()`:

```php
try {
    $rawRegisters = $this->registerService->findAll(...);
    // ... process registers
} catch (\TypeError $e) {
    $this->logger->warning(
        'OpenRegister internal error - using empty registers list',
        ['exception' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
    );
    $data['availableRegisters'] = [];
} catch (Exception $e) {
    $this->logger->warning(
        'OpenRegister findAll() failed - using empty registers list',
        ['exception' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
    );
    $data['availableRegisters'] = [];
}
```

**Why TypeError is caught separately**: OpenRegister's internal code may throw TypeErrors when its own data model has inconsistencies (e.g., a schema with null properties being serialized, or a register with unexpected field types). By catching TypeError explicitly, DocuDesk gracefully degrades to an empty register list instead of crashing the entire settings page.

**Both catch blocks** result in the same fallback behavior: set `availableRegisters` to an empty array and log a warning. The separate catches provide different log messages for diagnostic purposes.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| SET-078 | `getAllSettings()` catches `\TypeError` from OpenRegister internals and falls back to empty register list | MUST | Implemented |
| SET-079 | `getAllSettings()` catches generic `Exception` from `findAll()` and falls back to empty register list | MUST | Implemented |
| SET-080 | Both catch blocks log warnings with exception details (message, file, line) for diagnostics | MUST | Implemented |
| SET-081 | The settings page remains functional even when OpenRegister throws internal errors | MUST | Implemented |

## Dependencies

- **Nextcloud IAppConfig**: Key-value configuration storage
- **Nextcloud ISettings / IIconSection**: Admin panel integration
- **OpenRegister RegisterService**: Listing available registers with schemas (injected via constructor)
- **OpenRegister ConfigurationService**: Auto-import of register/schema definitions from JSON (resolved via container)
- **OpenRegister ObjectService**: Used by SettingsController for consent queries (resolved via container)
- **Nextcloud IAppManager**: Checking OpenRegister installation and enablement status
- **PSR ContainerInterface**: Lazy service resolution for OpenRegister services
