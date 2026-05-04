## Why

DocuDesk's consent endpoints today require an admin to manually pick a register and a schema in the settings UI. The IAppConfig keys `publicationConsent_register` and `publicationConsent_schema` default to empty strings, so until an admin saves them via the form, `ConsentCrudService::getConsentConfig()` returns `null` and consent operations break.

That settings UI flow depends on OpenRegister's `_extend` resolution to populate the "register → schemas" cascading dropdown. The OpenRegister change that fixes `_extend` has been postponed beyond the current beta cycle, which leaves DocuDesk consent unusable out of the box on a fresh install — the registers and schemas have been imported (the JSON definitions exist), but no IAppConfig keys point at them.

The fix is to remove the UI from the critical path: derive the register and schema IDs from the same `docudesk_register.json` import that creates them, write them straight to IAppConfig at install/upgrade time, and only fall back to the admin form when an admin wants to override the defaults. This mirrors what `opencatalogi/lib/Service/SettingsService.php::updateObjectTypeConfiguration()` already does for that app.

## What Changes

- Capture the result returned by `ConfigurationService::importFromApp(...)` in `SettingsInitializer::initialize()` (currently discarded) and feed it to a new helper that writes the per-object-type IAppConfig keys.
- Build the schema-slug → register-slug map by parsing `lib/Settings/docudesk_register.json`'s `components.registers[*].schemas[]` lists, so the mapping stays in sync with the JSON without a hardcoded PHP table.
- For each schema in the import result, look up the matching register in the import result by slug and write three IAppConfig keys per schema:
  - `{schemaSlug}_source` = `'openregister'`
  - `{schemaSlug}_register` = the integer register ID
  - `{schemaSlug}_schema` = the integer schema ID
- Apply each write **only when the existing IAppConfig value is empty**. Admin overrides are preserved on re-import or version bump — the auto-config never clobbers a manually configured value. This is a deliberate divergence from opencatalogi's unconditional overwrite, justified by the regulatory sensitivity of consent storage routing.
- Default coverage on a fresh install:
  - `publicationConsent` → register `consent`
  - `signingRequest`, `signerRecord`, `signingAuditEntry` → register `signing`
  - `template` → register `templates`
  - `correspondence`, `huisstijl` → register `document`
- The settings UI continues to work unchanged. After this change, admins who do not need to override see correct values pre-populated; admins who do override still go through the existing form.

## Capabilities

### New Capabilities
<!-- None — modifies an existing capability. -->

### Modified Capabilities
- `admin-settings`: extend `REQ-SET-02` (OpenRegister Integration Configuration) with auto-default behaviour — IAppConfig keys are seeded from the JSON import when empty, with admin overrides preserved.

## Impact

- **Service**: `docudesk/lib/Service/SettingsInitializer.php::initialize()` — capture `importFromApp(...)` return value, parse the JSON for the schema→register mapping, and call a new private `applyObjectTypeConfigurationDefaults(array $importResult, array $jsonDef)` helper. The helper writes IAppConfig keys only when the current value is empty (`getValueString(..., '') === ''`).
- **Service**: `docudesk/lib/Service/ConsentCrudService.php::getConsentConfig()` — unchanged in code, but its `null` return path becomes effectively dead code in default installs after this change. Document the behaviour, do not remove it (still needed when the import fails or the admin clears the override).
- **Settings JSON**: `docudesk/lib/Settings/docudesk_register.json` — no schema changes. The schema→register mapping is read from the existing `components.registers[*].schemas[]` entries.
- **Tests**: `docudesk/tests/unit/Service/SettingsInitializerTest.php` (new file or extend existing) — cover happy path (all keys written when empty), preservation (existing keys not clobbered), partial state (some keys empty, others set — only the empty ones are written), and missing-from-result (schema absent from import result is silently skipped, no exception). `docudesk/tests/unit/Service/ConsentCrudServiceTest.php` — add a test confirming `getConsentConfig()` returns the auto-populated values when admin has not overridden.
- **Documentation**: `docs/features/consent-management.md` (or wherever consent admin docs live) — note that `publicationConsent_register` and `publicationConsent_schema` are auto-populated on install and only need manual configuration to override the default.
- **Scope explicitly excluded**:
  - No changes to the admin UI, dropdowns, or the `_extend` query parameter handling. The UI dependency on `_extend` remains; this change just removes the UI from the install-time critical path.
  - No changes to OpenRegister's import return shape. We rely on the existing `['registers' => [Register, ...], 'schemas' => [Schema, ...]]` contract.
  - No removal of the deprecated empty-string defaults in `RegisterDiscoveryService::loadObjectTypeConfiguration()`. That method still returns the IAppConfig values; the change just guarantees those values are non-empty after a successful boot.
