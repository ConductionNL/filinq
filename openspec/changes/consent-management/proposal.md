## Why

DocuDesk already ships a consent management module that tracks GDPR publication consent for persons and organizations detected in documents before WOO publication. The module is functional — the `PublicationConsent` schema, `ConsentService`, `ConsentController`, and frontend views all exist — but three production-blocking gaps were identified during code review:

1. **Security — RBAC and multitenancy disabled.** All ObjectService calls in `ConsentService` and `ConsentController` pass `_rbac: false` and `_multitenancy: false`. In a multi-tenant Nextcloud deployment this allows any authenticated user to view and modify consent records owned by other tenants.
2. **Missing creation API.** `ConsentService::createConsentRequest()` exists but there is no `POST /api/consents` REST endpoint. Consent records can only be created by calling PHP directly, making the feature unusable from the UI.
3. **Duplicated infrastructure.** `ConsentService` and `ObjectionDeadlineChecker` each contain a private `getObjectService()` that replicates the `getInstalledApps()` + `container->get()` pattern already centralised in `SettingsService`. The objection period is also read directly from `IAppConfig` in `ConsentService`, duplicating the same key read in `SettingsService::getAllSettings()`.

This change fixes all three gaps and captures the full consent management feature — lifecycle, API, UI, WOO compliance — in canonical spec format so downstream changes can reference it.

## What Changes

- **FIX** `ConsentService` and `ConsentController` — flip `_rbac` and `_multitenancy` to `true` on all ObjectService calls (resolves CONS-044, CONS-045, CONS-046).
- **ADD** `POST /api/consents` endpoint in `ConsentController` exposing `ConsentService::createConsentRequest()` via the REST API (resolves CONS-047, CONS-048, CONS-050).
- **REFACTOR** `ConsentService::getObjectService()` and `ObjectionDeadlineChecker::getObjectService()` — delegate to `SettingsService::getObjectService()` instead of duplicating the resolution pattern (resolves CONS-053, CONS-054).
- **REFACTOR** `ConsentService` — read objection period via `SettingsService::getAllSettings()` instead of reading directly from `IAppConfig` (resolves CONS-051, CONS-052).
- **SPEC** Canonicalise all ten consent management requirements in spec format so downstream changes (`anonymisation-prohibition-gate`, `entity-publication-policies`) can cite this change.

## Capabilities

### Modified Capabilities

- `consent-management` — bug-fixes and gap-fills on the existing consent management implementation; no schema changes.

## Cross-app Dependencies

- **Hard** — `openregister:ObjectService` — all `PublicationConsent` records are stored in and retrieved from OpenRegister.
- **Soft** — `entity-publication-policies` — that change creates `publicationProhibition` records and may invoke `ConsentService::createConsentRequest()` to pre-populate consent records. No code dependency here; only a data dependency that requires the `POST /api/consents` endpoint added by this change.

## Impact

- `lib/Controller/ConsentController.php` — add `POST /api/consents`; enable RBAC/multitenancy on `index()` and `show()`.
- `lib/Service/ConsentService.php` — delegate `getObjectService()` to `SettingsService`; read objection period from `SettingsService::getAllSettings()`.
- `lib/Service/ObjectionDeadlineChecker.php` — delegate `getObjectService()` to `SettingsService`.
- `lib/Service/ConsentCrudService.php` — propagate RBAC/multitenancy flags through to ObjectService.
- `appinfo/routes.php` — register `POST /api/consents`.
- `tests/unit/Controller/ConsentControllerTest.php` — add POST endpoint coverage; update RBAC tests.
- `tests/unit/Service/ConsentServiceTest.php` — update ObjectService resolution and config reading tests.
