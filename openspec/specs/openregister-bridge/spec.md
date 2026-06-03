---
status: implemented
retrofit: true
---

# OpenRegister Bridge

## Purpose

@e2e exclude pure backend resolver service — no UI surface; all behavior covered by PHPUnit service tests

Provides a single resolver service that translates DocuDesk `IAppConfig` keys into OpenRegister register/schema slug pairs and validates namespace identifiers used by per-app data partitions. Services that need to read or write OpenRegister objects depend on the resolver instead of reading config keys directly — this keeps the config-translation seam in one place, isolates the IAppConfig naming convention from consumer code, and gives controllers a typed exception to render setup-state UIs.

The resolver is consumed by `TemplateService` and `TemplateVersionService` today; future register-backed features (signing requests, document register, dossier register, consent log) are expected to consume the same translator pattern.

## Requirements

### REQ-001: OpenRegister Configuration and Namespace Resolver

DocuDesk SHALL expose a single resolver service that translates DocuDesk IAppConfig keys into OpenRegister register/schema slug pairs and validates namespace identifiers used by per-app data partitions. The resolver SHALL be the only consumer of the underlying config keys within DocuDesk — services that need to talk to OpenRegister SHALL depend on the resolver, not directly on `SettingsService` or `IAppConfig`.

The class owns three observable behaviors:

1. **Template register/schema lookup** — returns the configured slugs for the primary template store; throws if either is unset.
2. **Template version register/schema lookup** — returns the configured slugs for the version-history store; throws if either is unset.
3. **Namespace validation** — enforces the `/^[a-z0-9]+$/` constraint used by every register-scoping operation (matches REQ-TMPL-03's namespace contract).

Both lookups read `SettingsService::getAllSettings()['configuration']` and surface configuration gaps as `RegisterNotConfiguredException` (a domain exception that callers can catch to render a calm "register not configured yet" empty state instead of a 500). Namespace validation surfaces invalid input as a generic `Exception` with `code = 400`.

#### Scenario: Resolve template register/schema

- **WHEN** `OpenRegisterResolver::getRegisterAndSchema()` is called
- **AND** `SettingsService::getAllSettings()['configuration']` contains both `template_register` and `template_schema`
- **THEN** an array `['register' => <id>, 'schema' => <id>]` is returned

#### Scenario: Missing template register/schema raises RegisterNotConfiguredException

- **WHEN** `getRegisterAndSchema()` is called
- **AND** either `template_register` or `template_schema` is empty / missing
- **THEN** a `RegisterNotConfiguredException` with message `"Template register/schema not configured"` is thrown
- **AND** controllers may catch this exception to render an empty list with `notConfigured: true` rather than a 500 error response

#### Scenario: Resolve template version register/schema

- **WHEN** `OpenRegisterResolver::getVersionRegisterAndSchema()` is called
- **AND** both `templateVersion_register` and `templateVersion_schema` are configured
- **THEN** an array `['register' => <id>, 'schema' => <id>]` is returned

#### Scenario: Missing version register/schema raises RegisterNotConfiguredException

- **WHEN** `getVersionRegisterAndSchema()` is called
- **AND** either `templateVersion_register` or `templateVersion_schema` is empty / missing
- **THEN** a `RegisterNotConfiguredException` with message `"Template version register/schema not configured"` is thrown

#### Scenario: Valid namespace passes

- **WHEN** `OpenRegisterResolver::validateNamespace("larpingapp")` is called
- **THEN** the method returns `true` and no exception is raised

#### Scenario: Invalid namespace raises 400

- **WHEN** `validateNamespace()` is called with a string containing uppercase letters, hyphens, underscores, or any non-`[a-z0-9]` character (e.g. `"my-app"`, `"My App"`, `""`)
- **THEN** an `Exception` with code `400` and message `"Invalid namespace: must be lowercase alphanumeric only"` is thrown

#### Notes

- The resolver does NOT discover register/schema slugs at runtime — it reads what `SettingsService` has cached. If admin settings change at runtime, callers must re-resolve.
- Both lookups raise `RegisterNotConfiguredException` with the same exception class. Callers cannot distinguish "template register missing" from "template schema missing" without parsing the message; that's acceptable because both are admin-side setup gaps with the same remediation (open admin settings, fill in the field). The dedicated exception class lets controllers render an empty-state response instead of a 500.
- The namespace regex matches REQ-TMPL-03 verbatim — if either spec ever loosens the constraint (e.g. to allow hyphens), both must move together. TODO: extract the regex to a shared constant if a second consumer needs it.
- The resolver currently only knows template + template-version registers. New register-backed features (signing requests, document register, dossier register, consent log) will need to either extend this class with new lookups or follow the same pattern in sibling resolvers. The decision is deferred until the second feature lands; this REQ documents the existing contract without prescribing the expansion model.

## Configuration

| Key | Type | Set by | Consumed by |
|-----|------|--------|-------------|
| `configuration.template_register` | string (OR register slug) | Admin settings | `getRegisterAndSchema()` |
| `configuration.template_schema` | string (OR schema slug) | Admin settings | `getRegisterAndSchema()` |
| `configuration.templateVersion_register` | string (OR register slug) | Admin settings | `getVersionRegisterAndSchema()` |
| `configuration.templateVersion_schema` | string (OR schema slug) | Admin settings | `getVersionRegisterAndSchema()` |

## Dependencies

- **SettingsService** — provides the cached configuration via `getAllSettings()['configuration']`
- **OCA\DocuDesk\Exception\RegisterNotConfiguredException** — domain exception thrown when register/schema config is missing

### Current Implementation Status

- **Fully implemented** with file path:
  - `lib/Service/OpenRegisterResolver.php` — single class, 3 public methods

### Consumers

- `lib/Service/TemplateService.php` — calls `getRegisterAndSchema()` + `validateNamespace()`
- `lib/Service/TemplateVersionService.php` — calls `getVersionRegisterAndSchema()`
