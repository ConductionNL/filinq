## Tasks

- [ ] 1. **Deduplication check** — search `openspec/specs/` and `openregister/lib/Service/` for any existing `TemplateService`, `template` schema, or `/api/templates` route; document findings (expected: no overlap — template storage is DocuDesk-specific).

- [ ] 2. Add the `templates` register entry under `components.registers` in `lib/Settings/docudesk_register.json` with slug `templates`, Dutch title/description, and `schemas: ["template"]`.

- [ ] 3. Add the `template` schema under `components.schemas.template` in `docudesk_register.json`: required `name` (string, max 255) and `content` (string), optional `description` (string, max 2000), required `namespace` (string, max 64, `facetable: true`), optional `format` (enum: A4/A3/Letter/Legal, default "A4"), optional `orientation` (enum: P/L, default "P"). Include `objectNameField: "name"`, Dutch property titles, and an icon.

- [ ] 4. Validate the updated JSON file: `jq . lib/Settings/docudesk_register.json > /dev/null` and confirm `composer check:strict` passes.

- [ ] 5. Add the five seed template objects from `design.md` under `components.objects` in `docudesk_register.json`, using the `@self` envelope with stable slugs (`seed-template-docudesk-factuur`, `seed-template-docudesk-brief`, `seed-template-larpingapp-karakterblad`, `seed-template-larpingapp-evenement`, `seed-template-opencatalogi-api`). Include at least one A4 portrait, one A4 landscape, and objects from at least two namespaces.

- [ ] 6. Create `lib/Service/OpenRegisterResolver.php`: constructor injects `IAppConfig` and Nextcloud container; expose `getRegisterAndSchema(): array` (reads configured register/schema slugs), `validateNamespace(string $namespace): void` (validates `/^[a-z0-9]+$/`, throws Exception code 400 on failure, throws Exception code 400 "Namespace is required" if empty), and `getObjectService(): ObjectService` (resolves `OCA\OpenRegister\Service\ObjectService` from container, throws `RuntimeException` if not available). Add `@spec` PHPDoc tag pointing to `openspec/changes/template-management/tasks.md#task-6`.

- [ ] 7. Create `lib/Service/TemplateService.php` with six public methods: `getTemplates(array $filters = [], int $limit = 20, int $offset = 0): array`, `getTemplate(string $id): array` (throws Exception code 404 if not found), `createTemplate(array $data): array` (validates name/content/namespace, sets format="A4" and orientation="P" if absent, calls `OpenRegisterResolver::validateNamespace()`), `updateTemplate(string $id, array $data): array` (merges with existing via `array_merge`, silently strips `namespace` from `$data` via `unset($data['namespace'])`), `deleteTemplate(string $id): bool`, `getTemplatesByNamespace(string $namespace): array` (calls `getTemplates(['namespace' => $namespace], 100, 0)`). Serialize results via `jsonSerialize()` if available, else cast to array. Add `@spec` PHPDoc tags.

- [ ] 8. Create `lib/Controller/TemplateRequestHandler.php`: constructor injects `TemplateService` and `IRequest`; expose one method per API action (`index`, `show`, `create`, `update`, `destroy`) that extracts parameters from `$request`, delegates to `TemplateService`, and returns a `JsonResponse`. Wrap service exceptions as appropriate HTTP responses (400 → 400, 404 → 404, other → 500). Add `@spec` PHPDoc tags.

- [ ] 9. Create `lib/Controller/TemplatesController.php` extending `Controller`: annotate with `#[NoAdminRequired]` and `#[NoCSRFRequired]`; constructor injects `TemplateRequestHandler`; five methods: `index()`, `show(string $id)`, `create()`, `update(string $id)`, `destroy(string $id)` — each ≤10 lines, delegating entirely to `TemplateRequestHandler`. Add `@spec` PHPDoc tags.

- [ ] 10. Register all five routes in `appinfo/routes.php` — ensure specific `{id}` routes are declared before any wildcard `{slug}` catch-all routes (per ADR-003):
    ```php
    ['name' => 'templates#index',   'url' => '/api/templates',     'verb' => 'GET'],
    ['name' => 'templates#create',  'url' => '/api/templates',     'verb' => 'POST'],
    ['name' => 'templates#show',    'url' => '/api/templates/{id}','verb' => 'GET'],
    ['name' => 'templates#update',  'url' => '/api/templates/{id}','verb' => 'PUT'],
    ['name' => 'templates#destroy', 'url' => '/api/templates/{id}','verb' => 'DELETE'],
    ```

- [ ] 11. Install and verify: reset the dev environment, enable DocuDesk, confirm `RegistersLoader` runs cleanly, and verify `GET /api/templates` returns all five seed objects with correct namespace distribution (`docudesk` ×2, `larpingapp` ×2, `opencatalogi` ×1).

- [ ] 12. Verify CRUD round-trip via REST: (a) POST a new template with `namespace=larpingapp` and confirm UUID in response; (b) GET it back by UUID; (c) PUT with an updated `content` field and a `namespace` field — confirm `namespace` is unchanged and `content` is updated; (d) DELETE and confirm subsequent GET returns 404.

- [ ] 13. Verify namespace validation: (a) POST with `namespace="my-app"` returns 400 "Invalid namespace"; (b) POST without `namespace` returns 400 "Namespace is required"; (c) POST with `namespace="larpingapp"` (valid) succeeds.

- [ ] 14. Verify pagination and search: (a) confirm `GET /api/templates?_limit=2&_offset=0` returns 2 results and correct `total`; (b) confirm `GET /api/templates?_search=factuur` returns only the "Factuur" seed; (c) confirm `GET /api/templates?namespace=larpingapp&_search=karakter` returns only the karakterblad template.

- [ ] 15. Write `Tests/Unit/Service/TemplateServiceTest.php`: unit tests for (a) `createTemplate` with missing required fields throws 400 Exception; (b) `createTemplate` sets format/orientation defaults; (c) `updateTemplate` silently ignores `namespace`; (d) `updateTemplate` preserves existing fields via `array_merge`; (e) `getTemplate` throws 404 for unknown UUID. Run with `phpunit -c phpunit-unit.xml --filter TemplateServiceTest` and confirm ≥75% coverage on `TemplateService`.

- [ ] 16. Write `Tests/Unit/Service/OpenRegisterResolverTest.php`: test (a) `validateNamespace` passes for "larpingapp", "docudesk", "opencatalogi"; (b) throws 400 for "my-app", "MyApp", "app_id"; (c) throws 400 for empty string; (d) `getObjectService` throws `RuntimeException` when OpenRegister is absent.

- [ ] 17. Add Dutch and English translations for schema title ("Template" / "Template"), property titles (`name` → "Naam" / "Name", `content` → "Inhoud" / "Content", `namespace` → "Naamruimte" / "Namespace", `format` → "Formaat" / "Format", `orientation` → "Oriëntatie" / "Orientation") to DocuDesk's `l10n/` files.

- [ ] 18. Write `docs/features/template-management.md` describing the template schema, the five REST endpoints, `TemplateService` DI usage (with code example for consumer apps), namespace rules, seed data, and the OpenRegister dependency. Reference it from `docs/FEATURES.md` if present.
