## Context

DocuDesk renders PDFs from Twig/HTML templates, but before this change those templates had no dedicated persistence layer: each consumer app (LarpingApp, OpenCatalogi, Procest, …) either hardcoded template strings or stored them in unstructured configuration. This meant:

- No shared catalogue — operators could not browse available templates or clone an existing one as a starting point.
- No namespace isolation — nothing prevented one app's template from colliding with or overwriting another's.
- No programmatic access contract — consumer apps had no typed service to call; they coupled directly to PDF generation internals.

OpenRegister already supplies the persistence and query infrastructure:
- `ObjectService::saveObject()` / `deleteObject()` / `searchObjectsPaginated()` handle CRUD on any schema.
- Objects carry a UUID, an audit trail, and a `jsonSerialize()` envelope used by the REST layer.
- `ConfigurationService::importFromApp()` (ADR-013) applies schema + seed data on install/upgrade — no new loader code is required.

So the work in this change is not "build a storage engine". It is "define the `template` schema, wire a five-endpoint controller, and expose a typed service that other apps can inject". The non-trivial decisions are: how namespace scoping is enforced, how namespace immutability is handled on PUT, and what the serialization contract looks like across OpenRegister object types.

## Goals / Non-Goals

**Goals:**
- A structured `template` object per Twig/HTML layout, carrying all six schema fields.
- Strict namespace scoping so `GET /api/templates?namespace=larpingapp` returns only LarpingApp templates.
- Namespace immutability enforced silently on PUT — the field is stripped, not rejected, to avoid breaking callers that re-POST the full object.
- A typed `TemplateService` injectable by other Nextcloud apps via the DI container.
- Seed data that demonstrates the service to a new developer and enables automated testing without manual fixture setup.

**Non-Goals:**
- A dedicated Admin or User settings UI for template management (REST API + DI is the access contract; UI is a follow-up).
- Template versioning, rollback, or diff views.
- Per-namespace or per-template RBAC (all authenticated users can read/write all templates in this version).
- Twig sandbox configuration — template content is stored as raw HTML/Twig; the sandbox is the concern of the PDF renderer that consumes the template, not this service.
- Export/import of template collections (handled by OpenRegister's generic import/export if needed).

## Decisions

### D1. Templates stored as OpenRegister objects, not a custom table

Templates are persisted via `ObjectService` against the `template` schema in `docudesk_register.json`. No custom `Entity` / `Mapper` / database table is introduced.

**Rationale:** ADR-001 is explicit — all domain data goes through OpenRegister objects. Custom tables would bypass the platform's audit trail, search, relation, and multi-tenancy plumbing. OpenRegister's `searchObjectsPaginated()` already provides namespace filtering, full-text search, and pagination — nothing custom is needed.

**Alternative considered:** A dedicated `oc_docudesk_templates` table. Rejected: duplicates infrastructure, misses audit trail, and diverges from every other DocuDesk schema.

### D2. Namespace validated as `/^[a-z0-9]+$/` and delegated to `OpenRegisterResolver`

Namespace validation — pattern check + required-on-create enforcement — lives in `OpenRegisterResolver::validateNamespace()`, called by `TemplateService` before every create operation.

**Rationale:** The resolver is already the authority on register/schema IDs. Centralising namespace validation there means a single, tested method is reused by any future operation that needs it (e.g., bulk import). The pattern `/^[a-z0-9]+$/` matches Nextcloud app IDs, which is the intended namespace unit — one app, one namespace token.

**Alternative considered:** Validate in the controller. Rejected: ADR-003 requires controllers to be thin; validation is business logic and belongs in the service layer.

### D3. Namespace immutability enforced by silently stripping on PUT

When a PUT request includes a `namespace` field, `TemplateService::updateTemplate()` removes the key from the update payload via `unset()` before calling `ObjectService`. The caller receives back the unchanged namespace in the response — no error is raised.

**Rationale:** Consumer apps frequently re-POST the full object they received from a prior GET. Returning a 400 error when the namespace hasn't actually changed would break these callers unnecessarily. Silent strip is the same pattern used by many OpenRegister schema fields that are immutable after creation (e.g., `@self.register`). The intent is documented in the schema's `description` field so UI builders know not to present the namespace as editable.

**Alternative considered:** Return 400 "Namespace cannot be changed". Rejected: too strict for the GET-then-PUT idiom and would break every consumer that round-trips the full object.

### D4. `TemplateService` resolved from DI, not static

`TemplateService` is injected via constructor DI. `OpenRegisterResolver` (and through it, `ObjectService`) is resolved lazily through the Nextcloud service container. If OpenRegister is not installed, `OpenRegisterResolver` throws a `RuntimeException` — the caller (controller or consumer app) is responsible for catching it.

**Rationale:** ADR-003 mandates constructor injection with `private readonly`. Static service locators (`\OC::$server`) are forbidden. Lazy resolution of `ObjectService` (rather than constructor injection) lets DocuDesk boot cleanly even if OpenRegister is not yet enabled — the `RuntimeException` is only thrown when a template operation is actually attempted.

### D5. Object serialization via `jsonSerialize()` with array fallback

When `ObjectService` returns an object with `jsonSerialize()`, the template service calls it. When the return value is a plain array (possible in some OpenRegister versions), the service casts it directly. Pagination responses always include `results` (array) and `total` (int).

**Rationale:** OpenRegister returns different types depending on version and query method. A uniform serialization path that handles both cases avoids `instanceof` checks scattered through the controller.

### D6. `TemplateRequestHandler` as thin delegation layer between controller and service

`TemplatesController` delegates to `TemplateRequestHandler`, which extracts request parameters, calls `TemplateService`, and wraps the result in a `JsonResponse`. The controller methods are ≤10 lines (per ADR-003).

**Rationale:** Separating param extraction and response wrapping from controller routing keeps both classes testable in isolation. `TemplateRequestHandler` can be unit-tested without a full Nextcloud bootstrap.

## Risks / Trade-offs

**[No per-namespace access control]** — Any authenticated Nextcloud user can read, create, update, or delete templates in any namespace.  
→ Mitigation: Acceptable for v1. DocuDesk is a single-tenant app in most deployments; templates are not sensitive data. Per-namespace RBAC is a follow-up that can use OpenRegister's `PropertyRbacHandler` when needed.

**[Content injection via template HTML]** — Template `content` is stored as raw Twig/HTML. A malicious operator could inject Twig expressions that execute on render.  
→ Mitigation: Responsibility of the PDF renderer (Twig sandbox). `TemplateService` stores and retrieves the content verbatim — it does not render. The security surface is the renderer, not this service.

**[OpenRegister unavailable at request time]** — If OpenRegister is disabled after install, every template endpoint throws a `RuntimeException` that propagates as a 500.  
→ Mitigation: Acceptable; DocuDesk declares OpenRegister as a required dependency in `appinfo/info.xml`. The `RuntimeException` is a hard failure, not a silent data-loss scenario.

**[Namespace drift post-creation]** — Since namespace is silently stripped on PUT, a template cannot be re-namespaced by the API. An operator who wants to move templates must delete and re-create them.  
→ Mitigation: Accepted trade-off of D3. A future "copy template to namespace" endpoint can be added without breaking the immutability contract.

## Seed Data

Per ADR-016, seed objects cover three personas: a Dutch municipality (`docudesk`), a LarpingApp consumer (`larpingapp`), and an OpenCatalogi consumer (`opencatalogi`). The seed set exercises all six schema fields and demonstrates both page formats and both orientations.

### `templates` schema — seed objects

**Seed 1 — Gemeente Demostad, factuur (municipality, `docudesk`)**

```json
{
  "@self": {
    "register": "templates",
    "schema": "template",
    "slug": "seed-template-docudesk-factuur"
  },
  "name": "Factuur",
  "description": "Standaard factuursjabloon voor uitgaande rekeningen van de gemeente. Bevat factuurnummer, datum, bedragen en BTW-specificatie.",
  "content": "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Factuur {{ factuur.nummer }}</title></head><body><h1>Factuur</h1><p>Factuurnummer: <strong>{{ factuur.nummer }}</strong></p><p>Datum: {{ factuur.datum | date('d-m-Y') }}</p><p>Aan: {{ ontvanger.naam }}, {{ ontvanger.adres }}</p><table><thead><tr><th>Omschrijving</th><th>Bedrag</th></tr></thead><tbody>{% for regel in regels %}<tr><td>{{ regel.omschrijving }}</td><td>&euro; {{ regel.bedrag | number_format(2, ',', '.') }}</td></tr>{% endfor %}</tbody></table><p>Totaal incl. BTW: <strong>&euro; {{ factuur.totaal | number_format(2, ',', '.') }}</strong></p></body></html>",
  "namespace": "docudesk",
  "format": "A4",
  "orientation": "P"
}
```

**Seed 2 — Gemeente Demostad, formele brief (municipality, `docudesk`)**

```json
{
  "@self": {
    "register": "templates",
    "schema": "template",
    "slug": "seed-template-docudesk-brief"
  },
  "name": "Formele brief",
  "description": "Huisstijlbrief voor uitgaande correspondentie van de gemeente. Bevat logo, adresregel, aanhef en ondertekening.",
  "content": "<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body><p><strong>Gemeente Demostad</strong><br>Raadhuisplein 1, 1234AB Demostad</p><p>Datum: {{ datum | date('d F Y') }}</p><p>Betreft: {{ onderwerp }}</p><p>Geachte {{ aanhef }},</p><p>{{ inhoud }}</p><p>Met vriendelijke groet,</p><p>{{ ondertekenaar.naam }}<br>{{ ondertekenaar.functie }}<br>Gemeente Demostad</p></body></html>",
  "namespace": "docudesk",
  "format": "A4",
  "orientation": "P"
}
```

**Seed 3 — LarpingApp, karakterblad (larpingapp, A4 portrait)**

```json
{
  "@self": {
    "register": "templates",
    "schema": "template",
    "slug": "seed-template-larpingapp-karakterblad"
  },
  "name": "Karakterblad",
  "description": "Afdrukbaar karakterblad voor LARP-spelers. Toont naam, klasse, vaardigheden en achtergrondverhaal.",
  "content": "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Karakterblad — {{ karakter.naam }}</title></head><body><h1>{{ karakter.naam }}</h1><p><strong>Klasse:</strong> {{ karakter.klasse }} &mdash; <strong>Niveau:</strong> {{ karakter.niveau }}</p><h2>Vaardigheden</h2><ul>{% for vaardigheid in karakter.vaardigheden %}<li>{{ vaardigheid }}</li>{% endfor %}</ul><h2>Achtergrond</h2><p>{{ karakter.achtergrond }}</p></body></html>",
  "namespace": "larpingapp",
  "format": "A4",
  "orientation": "P"
}
```

**Seed 4 — LarpingApp, evenementenprogramma (larpingapp, A4 landscape)**

```json
{
  "@self": {
    "register": "templates",
    "schema": "template",
    "slug": "seed-template-larpingapp-evenement"
  },
  "name": "Evenementenprogramma",
  "description": "Landschapsprogramma voor LARP-evenementen. Bevat tijdlijn, locaties en speciale regels.",
  "content": "<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body><h1>{{ evenement.naam }}</h1><p>{{ evenement.datum | date('d-m-Y') }} &mdash; {{ evenement.locatie }}</p><table><thead><tr><th>Tijd</th><th>Activiteit</th><th>Locatie</th></tr></thead><tbody>{% for item in programma %}<tr><td>{{ item.tijd }}</td><td>{{ item.activiteit }}</td><td>{{ item.locatie }}</td></tr>{% endfor %}</tbody></table></body></html>",
  "namespace": "larpingapp",
  "format": "A4",
  "orientation": "L"
}
```

**Seed 5 — OpenCatalogi, API-beschrijving (opencatalogi, A4 portrait)**

```json
{
  "@self": {
    "register": "templates",
    "schema": "template",
    "slug": "seed-template-opencatalogi-api"
  },
  "name": "API-beschrijving",
  "description": "Exporteerbaar overzichtsblad voor een API-component in de open catalogus. Toont contactpersoon, licentie en endpoints.",
  "content": "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>{{ component.naam }}</title></head><body><h1>{{ component.naam }}</h1><p><strong>Versie:</strong> {{ component.versie }} &mdash; <strong>Licentie:</strong> {{ component.licentie }}</p><p>{{ component.beschrijving }}</p><h2>Contactpersoon</h2><p>{{ component.contact.naam }} &mdash; <a href=\"mailto:{{ component.contact.email }}\">{{ component.contact.email }}</a></p><h2>Endpoints</h2><ul>{% for endpoint in component.endpoints %}<li><code>{{ endpoint.methode }} {{ endpoint.pad }}</code> — {{ endpoint.omschrijving }}</li>{% endfor %}</ul></body></html>",
  "namespace": "opencatalogi",
  "format": "A4",
  "orientation": "P"
}
```

## Reuse Analysis

Per ADR-012 (Deduplication Check):

| Existing OpenRegister / platform capability | How this change uses it |
|---|---|
| `ObjectService::saveObject()` | Template create and update |
| `ObjectService::deleteObject()` | Template delete |
| `ObjectService::searchObjectsPaginated()` | Template list with namespace filter, `_search`, `_limit`, `_offset` |
| `ObjectService::getObject()` | Template get-by-UUID |
| `ConfigurationService::importFromApp()` + ADR-013 envelope | Schema + seed import on install/upgrade |
| `AuditTrailMapper` (OpenRegister) | Change tracking on every template update — no DocuDesk code needed |
| `jsonSerialize()` on OpenRegister objects | Consistent serialization in API responses |

No custom search, pagination, import, or audit logic is introduced. `OpenRegisterResolver` is the only new infrastructure class, and it is a thin adapter (resolve config IDs + validate pattern) — it does not duplicate any OpenRegister service.

## Migration Plan

1. Add the `templates` register/schema entry and five seed objects to `lib/Settings/docudesk_register.json` (new `components.registers` entry + `components.schemas.template` + `components.objects[]`).
2. Add `TemplateService`, `OpenRegisterResolver`, `TemplatesController`, `TemplateRequestHandler`, and route entries.
3. On `occ app:upgrade docudesk` (or fresh install), `ConfigurationService::importFromApp()` creates the new schema and upserts the five seed objects by slug. Idempotent — re-running on a live instance does not duplicate seeds.
4. Rollback: revert `docudesk_register.json` changes and remove the four new PHP files + route entries. Since the template schema has no inbound references from other schemas, rollback is local to this change.

## Open Questions

- **Per-namespace read isolation** — should `GET /api/templates` without a namespace filter return all templates from all namespaces, or only those belonging to the calling user's app? Current implementation returns all. If multi-tenant isolation is needed, it should be a follow-up using OpenRegister's `_multitenancy` filter.
- **Template preview / rendering** — consumer apps call `TemplateService::getTemplate()` and then pass the `content` field to a Twig renderer. Should DocuDesk expose a `POST /api/templates/{id}/render` endpoint that accepts variable bindings and returns rendered HTML? Deferred; the rendering concern belongs to the PDF-generation feature, not template management.
