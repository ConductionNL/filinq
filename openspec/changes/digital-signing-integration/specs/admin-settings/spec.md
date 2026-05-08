## ADDED Requirements

### Requirement: Signing provider configuration
The admin settings page SHALL include a "Digital Signing" section that allows administrators to configure the signing provider, provider-specific settings, default signature level, and request expiry period.

#### Scenario: Configure native signing provider
- **WHEN** an administrator selects "Native (built-in)" as the signing provider
- **THEN** no additional configuration fields are shown
- **AND** the `signing_provider` setting is stored as "native" in IAppConfig

#### Scenario: Configure ValidSign provider
- **WHEN** an administrator selects "ValidSign" as the signing provider
- **THEN** configuration fields for OpenConnector source reference are shown
- **AND** the `signing_provider` is stored as "validsign"
- **AND** the `signing_provider_config` stores the OpenConnector source ID as JSON

#### Scenario: Configure default signature level
- **WHEN** an administrator selects a default signature level (SES, AdES, or QES)
- **THEN** the `signing_default_level` setting is stored in IAppConfig
- **AND** new signing requests default to this level unless overridden

#### Scenario: Configure signing request expiry
- **WHEN** an administrator sets the signing request expiry to 14 days
- **THEN** the `signing_request_expiry_days` setting is stored as "14" in IAppConfig
- **AND** new signing requests use this as the default deadline

### Requirement: Signing settings API
The existing settings API SHALL include signing configuration in its response and accept signing settings in updates.

#### Scenario: Settings response includes signing configuration
- **WHEN** a GET request is made to `/api/settings`
- **THEN** the response includes: `signing_provider`, `signing_default_level`, `signing_request_expiry_days`, and `signing_enabled` (boolean)

#### Scenario: Update signing settings
- **WHEN** a POST request is made to `/api/settings` with signing configuration fields
- **THEN** the signing settings are stored in IAppConfig
- **AND** the response confirms the updated values

### Requirement: Signing settings data model
The system SHALL store signing configuration using the following IAppConfig keys.

#### Scenario: Signing configuration keys
- **WHEN** signing settings are saved
- **THEN** the following keys are stored in IAppConfig:
  - `signing_provider` (string, default: "native") -- active signing provider
  - `signing_provider_config` (JSON string, default: "{}") -- provider-specific configuration
  - `signing_default_level` (string, default: "SES") -- default eIDAS signature level
  - `signing_request_expiry_days` (string, default: "30") -- default request expiry in days
  - `signing_enabled` (string boolean, default: "0") -- global signing feature toggle
