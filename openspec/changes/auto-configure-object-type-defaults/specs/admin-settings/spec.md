## MODIFIED Requirements

### Requirement: OpenRegister Integration Configuration
Administrators SHALL be able to configure which OpenRegister register and schema is used for each DocuDesk object type. On fresh installs, the system SHALL populate sensible defaults automatically from `docudesk_register.json` so consent and other object-type endpoints work without any admin interaction. Administrator overrides MUST be preserved across reboots and version bumps via per-key empty-check gating: every `setValueString` call inside `applyObjectTypeConfigurationDefaults` MUST be guarded by `getValueString(..., '') === ''`. The auto-default helper SHALL be invoked on every successful `SettingsInitializer::initialize()` — both the fresh-import branch and the version-up-to-date branch — and MUST handle missing slugs and unexpected exceptions gracefully without ever propagating a failure to app boot.

#### Scenario: Defaults are populated automatically on fresh install
- **GIVEN** OpenRegister is installed and enabled (>= v0.2.10)
- **AND** no admin has previously configured DocuDesk
- **AND** the IAppConfig keys `publicationConsent_register` and `publicationConsent_schema` are empty
- **WHEN** the DocuDesk app boots and `SettingsInitializer::initialize()` runs
- **THEN** `applyObjectTypeConfigurationDefaults($settings)` is invoked
- **AND** `publicationConsent_source` is set to `"openregister"`
- **AND** `publicationConsent_register` is set to the integer ID of the `consent` register (resolved via `RegisterMapper::find('consent')`)
- **AND** `publicationConsent_schema` is set to the integer ID of the `publicationConsent` schema (resolved via `SchemaMapper::find('publicationConsent')`)
- **AND** `ConsentCrudService::getConsentConfig()` returns a non-null array with both IDs without any admin interaction with the settings UI

#### Scenario: Auto-default covers every schema declared in `docudesk_register.json`
- **GIVEN** OpenRegister is installed and enabled
- **AND** no admin has previously configured DocuDesk
- **WHEN** `SettingsInitializer::initialize()` runs
- **THEN** every schema declared in `docudesk_register.json`'s `components.registers[*].schemas[]` (currently `publicationConsent`, `signingRequest`, `signerRecord`, `signingAuditEntry`, `template`, `correspondence`, `huisstijl`) has its `{schemaSlug}_source`, `{schemaSlug}_register`, and `{schemaSlug}_schema` IAppConfig keys populated
- **AND** each schema's register is resolved by inverting the JSON's `register.schemas[]` listing — the schema → register map is derived at runtime, never hardcoded in PHP

#### Scenario: Administrator overrides are preserved on reboot
- **GIVEN** an administrator has set `publicationConsent_register` to a custom value (e.g., a non-default register ID)
- **AND** that value is non-empty in IAppConfig
- **WHEN** `SettingsInitializer::initialize()` runs on a subsequent boot
- **THEN** `publicationConsent_register` remains at the administrator's value
- **AND** the auto-default helper logs an info message indicating the override is preserved
- **AND** the auto-default helper does NOT call `setValueString` for that key

#### Scenario: Per-key gating preserves partial overrides
- **GIVEN** an administrator has set `publicationConsent_register` but `publicationConsent_schema` is still empty
- **WHEN** `SettingsInitializer::initialize()` runs
- **THEN** `publicationConsent_register` remains at the administrator's value
- **AND** `publicationConsent_schema` is auto-populated with the integer ID of the `publicationConsent` schema
- **AND** `publicationConsent_source` is auto-populated only if it was empty

#### Scenario: Missing schema or register slug is silently skipped
- **GIVEN** `RegisterMapper::find($slug)` or `SchemaMapper::find($slug)` throws `DoesNotExistException` for some schema slug declared in `docudesk_register.json`
- **WHEN** the auto-default helper runs
- **THEN** that schema's IAppConfig keys are not written
- **AND** a warning is logged identifying the missing slug
- **AND** no exception is raised
- **AND** processing continues for the remaining schemas

#### Scenario: Auto-default runs in the version-up-to-date branch
- **GIVEN** DocuDesk has been initialized previously and `configuration_version` already matches the JSON version
- **AND** an admin has manually cleared `publicationConsent_register` via `occ config:app:set ... ""`
- **WHEN** the app boots and `SettingsInitializer::initialize()` runs
- **THEN** the JSON re-import is correctly skipped (version-gated)
- **AND** the auto-default helper is still invoked before returning
- **AND** the cleared key is re-populated with the auto-default integer ID

<!--
SET-### table additions for the canonical spec (folded by /opsx:sync after merge):
- SET-017: auto-default helper seeds {schemaSlug}_source/_register/_schema on every successful initialize()
- SET-018: per-key empty-check gating preserves admin overrides
- SET-019: graceful failure handling never blocks app boot
-->

