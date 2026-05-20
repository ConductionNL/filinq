## Why

DocuDesk generates PDFs from documents, but today there is no structured way for Nextcloud apps to store and reuse the Twig/HTML page layouts that drive those PDFs. Template content lives as ad-hoc strings hardcoded per-consumer, making it impossible to:

- Share templates across users of the same app without duplicating logic.
- Expose a catalogue of available layouts so operators can browse, preview, and adjust templates without touching source code.
- Allow multiple Nextcloud apps (LarpingApp, OpenCatalogi, Procest, etc.) to maintain their own template collections side-by-side through a shared service, without namespace collisions.

Template data needs a first-class persistence layer — OpenRegister objects — and a typed service (`TemplateService`) that other apps can inject and consume.

## What Changes

- Add a **template schema** to `lib/Settings/docudesk_register.json` alongside the existing `document`, `consent`, `signing`, and `dossier` schemas.
- The schema defines six properties: `name` (required), `description`, `content` (required, Twig/HTML), `namespace` (required, lowercase alphanumeric, facetable), `format` (enum: A4/A3/Letter/Legal, default A4), `orientation` (enum: P/L, default P).
- Expose a five-method **REST API** at `/api/templates` (`GET`, `POST`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}`) via `TemplatesController` and `TemplateRequestHandler`.
- Provide **`TemplateService`** — a DI-injectable PHP service with six public methods covering full CRUD plus `getTemplatesByNamespace(namespace)`. Other Nextcloud apps consume it via `\OCP\Server::get(OCA\DocuDesk\Service\TemplateService::class)`.
- Provide **`OpenRegisterResolver`** — a service layer that resolves register/schema IDs from DocuDesk's configuration and validates the `namespace` field against `/^[a-z0-9]+$/`.
- Namespace is **immutable after creation**: PUT requests that include a `namespace` field have it silently stripped before the update is applied.
- Seed the template schema with 3–5 realistic Dutch template objects covering common app consumers.

Not in scope:
- A dedicated UI for browsing or editing templates (templates are managed via REST API or `TemplateService`; a UI panel is a deliberate follow-up once the data model is stable).
- Template versioning or rollback.
- Access-control per template (all authenticated users of the Nextcloud instance can read templates; per-namespace RBAC is a follow-up).

## Capabilities

### New Capabilities

- `template-management`: Defines the DocuDesk template schema, the five-endpoint REST API, `TemplateService` (DI-injectable CRUD), `OpenRegisterResolver` (register/schema resolution + namespace validation), and the seed template objects. Covers namespace immutability, pagination, search, and object serialization.

### Modified Capabilities

None. The template schema is a pure addition to `docudesk_register.json`. No existing DocuDesk spec (`anonymization`, `document-register`, `dossier-register`, etc.) defines template storage, so no existing requirement changes.

## Impact

**Affected code (DocuDesk):**
- `lib/Settings/docudesk_register.json` — add the `templates` register/schema entry and seed objects (per ADR-013 loadable-template envelope).
- `lib/Service/TemplateService.php` — new service: CRUD methods + namespace injection/immutability logic.
- `lib/Service/OpenRegisterResolver.php` — new service: resolves configured register/schema IDs, validates namespace pattern, throws `RuntimeException` when OpenRegister is unavailable.
- `lib/Controller/TemplatesController.php` — new controller: five OCS routes, `@NoAdminRequired @NoCSRFRequired`, delegates to `TemplateRequestHandler`.
- `lib/Controller/TemplateRequestHandler.php` — new handler: parses request params, builds `JsonResponse` wrappers, calls `TemplateService`.
- `appinfo/routes.php` — full CRUD route registrations for `/api/templates`.

**Affected code (OpenRegister):** None — all CRUD goes through `ObjectService` (existing API).

**Affected downstream apps:**
- Any Nextcloud app that calls `TemplateService::createTemplate()` or the REST API benefits from the new service. Existing consumers that hard-coded template HTML are not broken; migration is opt-in.

**APIs / dependencies:**
- New routes: `GET /api/templates`, `POST /api/templates`, `GET /api/templates/{id}`, `PUT /api/templates/{id}`, `DELETE /api/templates/{id}`.
- Dependency on `OCA\OpenRegister\Service\ObjectService` — resolved via DI, guarded by a `RuntimeException` if OpenRegister is not installed.

**Data / migrations:**
- Running DocuDesk's `RegistersLoader` repair step applies the new schema and seed objects.
- No custom database migration; everything lives in OpenRegister's existing object table.

**Architectural alignment:**
- ADR-001 (Data Layer): templates stored as OpenRegister objects; `TemplateService` uses `ObjectService` exclusively — no custom Entity/Mapper.
- ADR-002 (API): five REST endpoints follow `GET`/`POST`/`PUT`/`DELETE` conventions; all return `application/json`.
- ADR-003 (Backend): Controller → Handler → Service layering; `TemplateRequestHandler` keeps controller methods ≤10 lines.
- ADR-005 (Security): `@NoAdminRequired @NoCSRFRequired` on all template endpoints; namespace pattern validated server-side.
- ADR-013 (Loadable Templates): schema + seed ships via `docudesk_register.json` envelope, applied idempotently by `RegistersLoader`.
- ADR-016 (Seed Data): 3–5 seed objects covering municipality, LarpingApp, and OpenCatalogi personas.
