## Requirements

### Requirement: Template data model in OpenRegister
The system SHALL store templates as OpenRegister objects in a new `template` schema within DocuDesk's register. The schema SHALL define the following properties:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Human-readable template name |
| description | string | No | Template purpose description |
| content | string | Yes | Twig/HTML template content for PDF rendering |
| namespace | string | Yes | App identifier that owns this template (e.g., `larpingapp`, `opencatalogi`) |
| format | string | No | Default page format (A4, A3, Letter). Defaults to A4. |
| orientation | string | No | Default page orientation (P, L). Defaults to P. |

The schema SHALL be added to `docudesk_register.json` and imported via `ConfigurationService::importFromApp()` on `Application::boot()`.

#### Scenario: Schema imported on app boot
- **WHEN** DocuDesk boots in Nextcloud
- **THEN** the `template` schema exists in the DocuDesk register and is available for CRUD operations via OpenRegister's ObjectService

#### Scenario: Template created with required fields
- **WHEN** a template is created with `name`, `content`, and `namespace`
- **THEN** the template object is persisted in OpenRegister with a generated UUID

#### Scenario: Template created without namespace
- **WHEN** a template creation request omits the `namespace` field
- **THEN** the request SHALL be rejected with a validation error

### Requirement: Template CRUD API endpoints
The system SHALL expose the following REST endpoints for template management:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/templates` | List templates, supports `namespace` filter, `_search`, `_limit`, `_offset` |
| POST | `/api/templates` | Create a new template |
| GET | `/api/templates/{id}` | Get a single template by ID |
| PUT | `/api/templates/{id}` | Update a template |
| DELETE | `/api/templates/{id}` | Delete a template |

All endpoints SHALL require authentication (`@NoCSRFRequired` for API access).

#### Scenario: List templates filtered by namespace
- **WHEN** an authenticated user sends `GET /api/templates?namespace=larpingapp`
- **THEN** the response contains only templates with `namespace=larpingapp`, paginated with `results` array and `total` count

#### Scenario: List all templates without filter
- **WHEN** an authenticated user sends `GET /api/templates` without namespace filter
- **THEN** the response contains all templates the user has access to

#### Scenario: Create a template
- **WHEN** an authenticated user POSTs `{"name": "Character Sheet", "content": "<h1>{{ character.name }}</h1>", "namespace": "larpingapp"}` to `/api/templates`
- **THEN** the template is created and the response contains the full template object with generated UUID

#### Scenario: Update a template
- **WHEN** an authenticated user PUTs `{"content": "<h1>{{ character.name }} - Updated</h1>"}` to `/api/templates/{id}`
- **THEN** the template content is updated and the response contains the updated template object

#### Scenario: Delete a template
- **WHEN** an authenticated user sends `DELETE /api/templates/{id}`
- **THEN** the template is deleted and a 200 response with `{"success": true}` is returned

#### Scenario: Get non-existent template
- **WHEN** an authenticated user sends `GET /api/templates/{nonExistentId}`
- **THEN** the response is a 404 JSONResponse with `{"error": "Template not found"}`

### Requirement: Namespace enforcement
The system SHALL validate the `namespace` field on create and update operations. The namespace MUST be a valid Nextcloud app ID (lowercase alphanumeric, no spaces). On create, the namespace is required. On update, the namespace cannot be changed.

#### Scenario: Create with invalid namespace
- **WHEN** a template creation request has `namespace` containing spaces or special characters
- **THEN** the request SHALL be rejected with a 400 error

#### Scenario: Attempt to change namespace on update
- **WHEN** an update request includes a different `namespace` than the existing template
- **THEN** the namespace field SHALL be ignored (not updated)

### Requirement: TemplateService for programmatic access
The system SHALL provide a `TemplateService` injectable via DI container that wraps OpenRegister CRUD operations for templates. Methods:

- `getTemplates(array $filters = [], int $limit = 20, int $offset = 0): array` — List templates with optional filters
- `getTemplate(string $id): array` — Get single template by UUID
- `createTemplate(array $data): array` — Create a new template
- `updateTemplate(string $id, array $data): array` — Update an existing template
- `deleteTemplate(string $id): bool` — Delete a template
- `getTemplatesByNamespace(string $namespace): array` — Convenience method to list all templates for an app

Consumer apps SHALL use this service via DI: `\OCP\Server::get(OCA\DocuDesk\Service\TemplateService::class)`.

#### Scenario: LarpingApp fetches templates via DI
- **WHEN** LarpingApp resolves `TemplateService` from the DI container and calls `getTemplatesByNamespace('larpingapp')`
- **THEN** it receives an array of template objects scoped to LarpingApp

#### Scenario: DocuDesk not installed
- **WHEN** a consumer app tries to resolve `TemplateService` from the DI container but DocuDesk is not installed
- **THEN** the DI container throws a resolution exception that the consumer app MUST catch and handle gracefully
