## Context

`docudesk/lib/Settings/docudesk_register.json` defines four registers (`consent`, `signing`, `templates`, `document`) and seven schemas spread across them. When DocuDesk boots, `Application::boot()` calls `SettingsService::initialize()` → `SettingsInitializer::initialize()` → `OpenRegister\ConfigurationService::importFromApp(...)`, which creates or updates the registers and schemas in OpenRegister and returns their entities:

```
$result = $configurationService->importFromApp(...);
// $result === [
//   'registers' => [Register, Register, …],   // all four registers
//   'schemas'   => [Schema,   Schema,   …],   // all seven schemas
//   'objects'   => [ObjectEntity, …],         // any seed objects
// ]
```

Today this `$result` is **discarded**. The IAppConfig keys consumed downstream (`publicationConsent_register`, `publicationConsent_schema`, etc.) keep their default of `''` until an admin saves the settings form.

`opencatalogi/lib/Service/SettingsService.php::updateObjectTypeConfiguration()` already implements the same auto-default pattern for that app, but with a single hard-coded register slug (`'publication'`) shared by all its object types. DocuDesk has multiple registers, so a single hard-coded register slug will not work — the mapping must be per-schema.

## Flow (after this change)

```
Application::boot()
        │
        ▼
SettingsService::initialize()
        │
        ▼
SettingsInitializer::initialize()
        │
        ├──► loadSettings()                          ── reads docudesk_register.json
        │
        ├──► importFromApp(appId='docudesk', ...)
        │              │
        │              ▼
        │       OpenRegister creates/updates registers + schemas, returns
        │       { registers: [Register, …], schemas: [Schema, …], objects: […] }
        │
        ├──► applyObjectTypeConfigurationDefaults($jsonDef)   ◄── NEW
        │              │
        │              │  1. Parse $jsonDef['components']['registers'] to build:
        │              │       schemaSlug → registerSlug map
        │              │
        │              │  2. For each (schemaSlug, registerSlug) pair:
        │              │       Look up Schema via SchemaMapper::find($schemaSlug)
        │              │       Look up Register via RegisterMapper::find($registerSlug)
        │              │       Both mappers' find() accepts ID, UUID, or slug
        │              │       and works regardless of whether importFromApp
        │              │       actually wrote anything in this boot.
        │              │
        │              │       On DoesNotExistException for either: log warning,
        │              │       skip this schema, continue to next.
        │              │
        │              │       For each of {_source, _register, _schema}:
        │              │         If IAppConfig value is empty:
        │              │           setValueString(...)
        │              │         Else:
        │              │           leave as-is + log info "preserving override"
        │
        ▼
SettingsInitializer returns; settings UI shows pre-populated values; consent works.
```

## Schema → Register Map (derived from JSON)

| Schema slug | Register slug | Source of truth |
|---|---|---|
| `publicationConsent` | `consent` | `docudesk_register.json` `components.registers.consent.schemas[]` |
| `signingRequest` | `signing` | same |
| `signerRecord` | `signing` | same |
| `signingAuditEntry` | `signing` | same |
| `template` | `templates` | same |
| `correspondence` | `document` | same |
| `huisstijl` | `document` | same |

The map is **not** hardcoded in PHP. The helper iterates `$jsonDef['components']['registers']` and inverts each `register.schemas[]` list into `schemaSlug → registerSlug` entries. Adding a new register/schema pair to the JSON automatically extends the auto-config behaviour without touching PHP.

## Idempotency policy: preserve admin overrides

Each `setValueString` is gated by an empty-check:

```php
if ($this->config->getValueString($this->appName, $key, '') === '') {
    $this->config->setValueString($this->appName, $key, $newValue);
} else {
    $this->logger->info(
        "Preserving existing override for {$key}",
        ['app' => $this->appName, 'key' => $key]
    );
}
```

**Why:** consent storage routing has regulatory implications (GDPR, WOO). An admin who deliberately points consent at a non-default register must not have that pointer silently overwritten on every boot or every version bump. opencatalogi takes the opposite stance (unconditional overwrite). DocuDesk diverges here on purpose.

**Trade-off:** if a register is renamed or its ID changes (e.g., the `consent` register is dropped and recreated under a different ID), the IAppConfig key still points at the old ID and the admin must clear it manually to re-trigger the auto-default. Acceptable, because such renames are rare and operator-driven.

## Failure handling

- `importFromApp` itself fails → existing try/catch in `initialize()` logs and returns; auto-default helper is never called. Pre-existing behaviour, no change.
- `importFromApp` succeeds but the result is missing an expected schema or register slug → the helper logs a warning per missing slug and skips that entry. No exception. The admin can still manually configure later via the existing form.
- The JSON definition has no `components.registers` (defensive) → the helper logs an info message and returns. No exception, no writes.

## Service location

The new helper lives in `SettingsInitializer` because:
- `SettingsInitializer` is already the owner of the JSON-import flow and already holds the `IAppConfig` dependency.
- Splitting it into a separate class (`ObjectTypeConfigurator` or similar) would add a class for what is essentially one method called from one place.
- If the helper grows beyond ~80 lines or accumulates additional responsibilities, extracting it later is mechanical.

## Test strategy

Unit tests live in `docudesk/tests/unit/Service/SettingsInitializerTest.php` (new file). Covered cases:

1. **Happy path**: All seven IAppConfig keys (`publicationConsent_register/_schema`, `signingRequest_register/_schema`, …) are empty → all get written with the correct register/schema IDs from the import result.
2. **Override preservation**: `publicationConsent_register` is pre-set to `"99"` → that key is left at `"99"` after `initialize()`. The other six keys are still written.
3. **Partial pre-set**: `publicationConsent_register` is pre-set but `publicationConsent_schema` is empty → the register key is preserved, the schema key is written. Confirms per-key (not per-schema) gating.
4. **Missing-from-import-result**: import result contains only `publicationConsent` (no `template`) → only `publicationConsent` keys are written; `template_register/_schema` keys remain empty. No exception.
5. **Empty `components.registers` in JSON**: helper logs info, makes no writes, returns cleanly.
6. **Source key**: `publicationConsent_source` is set to `'openregister'` on a fresh install. Confirms that the source key is included in the auto-default sweep, not just register/schema.

`docudesk/tests/unit/Service/ConsentCrudServiceTest.php` gets one new test: after the auto-default has run on a fresh install, `getConsentConfig()` returns a non-null array with the expected register/schema IDs.

Tests run inside the Nextcloud container per project ADR.

## Open questions

- Should the helper also re-write the keys when the IAppConfig value is the **stale ID** of a no-longer-imported register (e.g., admin set it to a register that has since been removed from the JSON)? **Decision: no, out of scope.** That edge case is rare, would require querying OpenRegister to verify the stored ID still resolves, and adds boot-time DB calls. Document it as a known limitation.
- Should this same helper run when `importFromApp` returns a `'no-update-needed'` result (version is up-to-date)? **Decision: yes** — the helper must run on every boot when the import-or-skip path completes successfully, because IAppConfig keys may have been cleared or the app may have been freshly installed against an already-imported configuration. Implementation note: because the no-update branch may return empty `registers`/`schemas` arrays from `importFromApp`, the helper resolves slug → Register/Schema via `RegisterMapper::find($slug)` / `SchemaMapper::find($slug)` directly. Both mappers' `find()` methods already accept an ID, UUID, or slug (case-insensitive), so this works on both fresh imports and idempotent boots.
