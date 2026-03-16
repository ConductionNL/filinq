---
status: reviewed
---

# Template Management

## Purpose

Provides CRUD operations for reusable Twig/HTML templates stored as OpenRegister objects. Templates are scoped per-app via a `namespace` field, enabling multiple Nextcloud apps to maintain their own template collections through a shared service. Consumer apps access templates via the `TemplateService` (DI container) or the REST API.

## Requirements

### Template Data Model

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-001 | Templates stored as OpenRegister objects in `template` schema within DocuDesk register | MUST | Implemented |
| TMPL-002 | Schema properties: `name` (string, required), `description` (string), `content` (string, required, HTML format), `namespace` (string, required, facetable), `format` (string, enum A4/A3/Letter/Legal, default A4), `orientation` (string, enum P/L, default P) | MUST | Implemented |
| TMPL-003 | Schema added to `docudesk_register.json` and imported via `ConfigurationService::importFromApp()` on boot | MUST | Implemented |
| TMPL-004 | Schema is searchable | MUST | Implemented |

### Template CRUD API

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-010 | `GET /api/templates` — list templates with `namespace`, `_search`, `_limit`, `_offset` query params | MUST | Implemented |
| TMPL-011 | `POST /api/templates` — create a new template | MUST | Implemented |
| TMPL-012 | `GET /api/templates/{id}` — get single template by UUID | MUST | Implemented |
| TMPL-013 | `PUT /api/templates/{id}` — update a template | MUST | Implemented |
| TMPL-014 | `DELETE /api/templates/{id}` — delete a template | MUST | Implemented |
| TMPL-015 | All endpoints require authentication (`@NoAdminRequired @NoCSRFRequired`) | MUST | Implemented |

### Namespace Enforcement

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-020 | Namespace validated as lowercase alphanumeric only (regex `/^[a-z0-9]+$/`) | MUST | Implemented |
| TMPL-021 | Namespace is required on create | MUST | Implemented |
| TMPL-022 | Namespace is immutable on update (silently ignored if provided) | MUST | Implemented |
| TMPL-023 | Invalid namespace returns 400 error | MUST | Implemented |

### TemplateService (Programmatic Access)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-030 | `getTemplates(array $filters, int $limit, int $offset): array` — paginated list | MUST | Implemented |
| TMPL-031 | `getTemplate(string $id): array` — single template by UUID, throws 404 if not found | MUST | Implemented |
| TMPL-032 | `createTemplate(array $data): array` — create with validation (name, content, namespace required) | MUST | Implemented |
| TMPL-033 | `updateTemplate(string $id, array $data): array` — update with namespace immutability | MUST | Implemented |
| TMPL-034 | `deleteTemplate(string $id): bool` — delete by UUID | MUST | Implemented |
| TMPL-035 | `getTemplatesByNamespace(string $namespace): array` — convenience method for app-scoped listing | MUST | Implemented |
| TMPL-036 | Injectable via DI: `OCA\DocuDesk\Service\TemplateService::class` | MUST | Implemented |

## Data Model

### Template Schema

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| name | string | Yes | — | Human-readable template name (max 255 chars) |
| description | string | No | — | Template purpose description (max 2000 chars) |
| content | string (HTML) | Yes | — | Twig/HTML template content for PDF rendering |
| namespace | string | Yes | — | App identifier that owns this template (max 64 chars, lowercase alphanumeric) |
| format | string (enum) | No | A4 | Default page format: A4, A3, Letter, Legal |
| orientation | string (enum) | No | P | Default page orientation: P (portrait), L (landscape) |

## Scenarios

### List templates filtered by namespace

```
GIVEN templates exist with namespace "larpingapp" and "opencatalogi"
WHEN GET /api/templates?namespace=larpingapp
THEN only templates with namespace=larpingapp are returned
AND response includes results array and total count
```

### Create a template

```
GIVEN an authenticated user
WHEN POST /api/templates with {"name": "Character Sheet", "content": "<h1>{{ name }}</h1>", "namespace": "larpingapp"}
THEN the template is created with a generated UUID
AND the full template object is returned
```

### Namespace immutable on update

```
GIVEN template "abc-123" exists with namespace "larpingapp"
WHEN PUT /api/templates/abc-123 with {"namespace": "other", "name": "Updated"}
THEN the name is updated but namespace remains "larpingapp"
```

### Consumer app fetches templates via DI

```
GIVEN LarpingApp resolves TemplateService from Nextcloud's DI container
WHEN it calls getTemplatesByNamespace('larpingapp')
THEN it receives an array of template objects scoped to LarpingApp
```

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/TemplateService.php` -- core template CRUD: `getTemplates()`, `getTemplate()`, `createTemplate()`, `updateTemplate()`, `deleteTemplate()`, `getTemplatesByNamespace()`, namespace validation
  - `lib/Controller/TemplatesController.php` -- REST API: `index()`, `create()`, `show()`, `update()`, `destroy()`
  - `lib/Settings/docudesk_register.json` -- contains the `template` schema definition with properties (name, description, content, namespace, format, orientation)
  - `appinfo/routes.php` -- full CRUD routes: GET/POST `/api/templates`, GET/PUT/DELETE `/api/templates/{id}`
- **Not yet implemented**: Nothing -- all requirements (TMPL-001 through TMPL-036) are fully implemented
- **No dedicated UI**: There is no Vue component for template management in `src/views/`. Templates are managed entirely via the REST API or programmatic access through `TemplateService`.

### Standards & References
- **Twig 3.x**: Templates use Twig syntax with the same sandbox security policy as `pdf-generation` spec
- **HTML5**: Template content format is HTML, rendered via Twig then converted to PDF
- **ISO 216**: Page format options (A4, A3) follow international paper size standard
- **ANSI/ASME Y14.1**: Letter and Legal format options follow American paper size standards

### Specificity Assessment
- **Specific enough**: Yes, this spec is well-defined and fully implemented.
- **Missing/Ambiguous**: No UI for template management is documented or implemented. The namespace validation regex (`/^[a-z0-9]+$/`) excludes hyphens and underscores which may be needed. No template preview functionality is specified.
- **Open questions**:
  1. Should a template management UI be added to DocuDesk's admin or main interface?
  2. Should template content validation (valid Twig syntax) be performed on create/update?
  3. Should namespace allow hyphens/underscores (e.g., `my-app` or `my_app`)?
  4. How does this interact with the `document-creatie-sjablonen` spec's template versioning requirement?
