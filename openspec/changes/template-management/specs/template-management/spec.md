## ADDED Requirements

### Requirement: Template schema registered in OpenRegister on app boot

The system SHALL define a `template` schema in `lib/Settings/docudesk_register.json` under `components.schemas.template`. The schema SHALL include properties: `name` (string, required, max 255 chars), `description` (string, optional, max 2000 chars), `content` (string, required, Twig/HTML), `namespace` (string, required, max 64 chars, lowercase alphanumeric, facetable), `format` (string enum: A4/A3/Letter/Legal, default "A4"), `orientation` (string enum: P/L, default "P"). The schema SHALL be applied to OpenRegister on app install/upgrade via `ConfigurationService::importFromApp()` — no new loader code is required.

#### Scenario: Schema is created on fresh install

- **WHEN** DocuDesk is installed on a Nextcloud instance that has OpenRegister enabled
- **THEN** `RegistersLoader` creates the `template` schema with all six properties
- **AND** the schema is available via `GET /api/objects?register=templates`

#### Scenario: Schema is idempotent on upgrade

- **WHEN** DocuDesk is upgraded on an instance that already has the `template` schema
- **THEN** the schema is not duplicated; the loader upserts by slug
- **AND** existing template objects are preserved

#### Scenario: Template created without required fields returns 400

- **GIVEN** an authenticated user
- **WHEN** `POST /api/templates` is called without `name`, `content`, or `namespace`
- **THEN** a 400 JSON response is returned identifying the missing required field
- **AND** no object is persisted in OpenRegister

#### Scenario: Template created with format/orientation defaults

- **GIVEN** a template creation request that omits `format` and `orientation`
- **WHEN** the template is saved via `TemplateService::createTemplate()`
- **THEN** `format` defaults to "A4" and `orientation` defaults to "P"
- **AND** the response includes these default values

### Requirement: Full CRUD REST API for templates

The system SHALL expose five REST endpoints under `/api/templates`, all requiring authentication (`@NoAdminRequired @NoCSRFRequired`). Responses SHALL use `application/json` and SHALL include appropriate HTTP status codes. Pagination responses SHALL include a `results` array and a `total` count.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/templates` | List templates; supports `namespace`, `_search`, `_limit`, `_offset` |
| POST | `/api/templates` | Create a new template |
| GET | `/api/templates/{id}` | Get a single template by UUID |
| PUT | `/api/templates/{id}` | Update a template |
| DELETE | `/api/templates/{id}` | Delete a template |

#### Scenario: List templates with namespace filter

- **GIVEN** templates exist with namespaces "larpingapp" and "opencatalogi"
- **WHEN** `GET /api/templates?namespace=larpingapp` is called by an authenticated user
- **THEN** only templates with `namespace=larpingapp` are returned
- **AND** the response body contains a `results` array and a `total` count

#### Scenario: Create a template returns full object with UUID

- **GIVEN** an authenticated user
- **WHEN** `POST /api/templates` is called with `{"name": "Karakterblad", "content": "<h1>{{ naam }}</h1>", "namespace": "larpingapp"}`
- **THEN** a 200 response is returned containing the full template object
- **AND** the object includes a generated UUID

#### Scenario: Get single template by UUID

- **GIVEN** a template with UUID "abc-123" exists in OpenRegister
- **WHEN** `GET /api/templates/abc-123` is called by an authenticated user
- **THEN** the response contains the full template object with all six fields

#### Scenario: Get non-existent template returns 404

- **GIVEN** no template exists with UUID "does-not-exist"
- **WHEN** `GET /api/templates/does-not-exist` is called
- **THEN** a 404 JSON response is returned
- **AND** the response body contains an `error` field

#### Scenario: Update template returns updated object

- **GIVEN** a template with UUID "abc-123" exists
- **WHEN** `PUT /api/templates/abc-123` is called with `{"content": "<h1>{{ naam }} (v2)</h1>"}`
- **THEN** a 200 response is returned containing the updated template object
- **AND** the `content` field reflects the new value

#### Scenario: Delete template removes it from OpenRegister

- **GIVEN** a template with UUID "abc-123" exists
- **WHEN** `DELETE /api/templates/abc-123` is called by an authenticated user
- **THEN** a 200 response is returned
- **AND** a subsequent `GET /api/templates/abc-123` returns 404

### Requirement: Namespace enforced as lowercase alphanumeric, immutable after creation

The system SHALL validate `namespace` against the pattern `/^[a-z0-9]+$/` on every create operation. Namespace SHALL be required on create. On update (PUT), a `namespace` field in the request body SHALL be silently stripped — the stored namespace is preserved and the response returns the unchanged namespace value. Invalid namespaces SHALL return 400.

#### Scenario: Valid namespace is accepted

- **GIVEN** a template creation request with `namespace: "larpingapp"`
- **WHEN** `OpenRegisterResolver::validateNamespace()` is invoked
- **THEN** validation passes and the template is created

#### Scenario: Namespace with hyphens or special characters is rejected

- **GIVEN** a template creation request with `namespace: "my-app"`
- **WHEN** `TemplateService::createTemplate()` validates the namespace
- **THEN** a 400 response is returned with message "Invalid namespace"
- **AND** no object is persisted

#### Scenario: Namespace is required on create

- **GIVEN** a template creation request that omits `namespace`
- **WHEN** `TemplateService::createTemplate()` is called
- **THEN** an Exception with HTTP code 400 is thrown with message "Namespace is required"

#### Scenario: Namespace is immutable on update

- **GIVEN** a template "abc-123" exists with `namespace: "larpingapp"`
- **WHEN** `PUT /api/templates/abc-123` is called with `{"namespace": "other", "name": "Updated"}`
- **THEN** the response shows `namespace: "larpingapp"` (unchanged) and `name: "Updated"`
- **AND** the namespace field in the payload is silently discarded

### Requirement: TemplateService injectable via Nextcloud DI container

The system SHALL provide `OCA\DocuDesk\Service\TemplateService` as a DI-injectable PHP class. It SHALL expose six public methods: `getTemplates(filters, limit, offset)`, `getTemplate(id)`, `createTemplate(data)`, `updateTemplate(id, data)`, `deleteTemplate(id)`, and `getTemplatesByNamespace(namespace)`. Consumer apps in the same Nextcloud instance SHALL be able to resolve the service via `\OCP\Server::get(OCA\DocuDesk\Service\TemplateService::class)`.

#### Scenario: Consumer app fetches templates by namespace via DI

- **GIVEN** LarpingApp resolves `TemplateService` from the Nextcloud DI container
- **WHEN** it calls `getTemplatesByNamespace('larpingapp')`
- **THEN** it receives an array of template objects where every item has `namespace = "larpingapp"`
- **AND** up to 100 templates are returned (default limit)

#### Scenario: getTemplate throws 404 for unknown UUID

- **GIVEN** no template exists with UUID "unknown-uuid"
- **WHEN** `TemplateService::getTemplate('unknown-uuid')` is called
- **THEN** an Exception with HTTP code 404 is thrown

#### Scenario: createTemplate validates all required fields

- **GIVEN** a data array missing the `content` field
- **WHEN** `TemplateService::createTemplate(['name' => 'Test', 'namespace' => 'larpingapp'])` is called
- **THEN** an Exception with HTTP code 400 is thrown identifying `content` as the missing field

#### Scenario: updateTemplate preserves fields not included in the update payload

- **GIVEN** a template exists with `name`, `content`, `namespace`, and `description` set
- **WHEN** `TemplateService::updateTemplate('abc-123', ['name' => 'New Name'])` is called
- **THEN** only `name` is changed
- **AND** `content`, `namespace`, and `description` are preserved via `array_merge` with existing data

### Requirement: OpenRegisterResolver resolves register/schema config and validates namespace

The system SHALL provide `OCA\DocuDesk\Service\OpenRegisterResolver` that: (a) resolves the configured register slug and schema slug for templates from DocuDesk app configuration, (b) validates namespace strings against `/^[a-z0-9]+$/`, and (c) throws a `RuntimeException` when OpenRegister is not available.

#### Scenario: Register and schema IDs are resolved for CRUD operations

- **GIVEN** `TemplateService` is about to call `ObjectService`
- **WHEN** `OpenRegisterResolver::getRegisterAndSchema()` is called
- **THEN** it returns the configured template register slug and schema slug
- **AND** these values are used in all subsequent `ObjectService` calls

#### Scenario: RuntimeException when OpenRegister is unavailable

- **GIVEN** OpenRegister is not installed on the Nextcloud instance
- **WHEN** any `TemplateService` method attempts to resolve `ObjectService`
- **THEN** a `RuntimeException` is thrown with a message indicating OpenRegister is unavailable
- **AND** the error propagates to the caller (controller returns 500 via Nextcloud's exception handler)

#### Scenario: Namespace validation delegated to resolver

- **GIVEN** a namespace value is supplied in a create request
- **WHEN** `OpenRegisterResolver::validateNamespace()` is called with the value
- **THEN** valid values (matching `/^[a-z0-9]+$/`) pass without error
- **AND** invalid values throw an Exception that results in a 400 response

### Requirement: Paginated listing with search and namespace filter

The template list endpoint SHALL support `_limit` (default 20), `_offset` (default 0), `_search` (full-text), and `namespace` (exact match) query parameters. Results SHALL be delivered via `ObjectService::searchObjectsPaginated()`. The response SHALL always include `results` (array) and `total` (integer).

#### Scenario: Paginated results with limit and offset

- **GIVEN** 50 templates exist in namespace "larpingapp"
- **WHEN** `GET /api/templates?namespace=larpingapp&_limit=10&_offset=20` is called
- **THEN** exactly 10 templates are returned starting from position 20
- **AND** the `total` field is 50

#### Scenario: Full-text search filters results

- **GIVEN** templates named "Factuur" and "Brief" exist
- **WHEN** `GET /api/templates?_search=factuur` is called
- **THEN** only the template named "Factuur" is returned
- **AND** "Brief" is not present in the results

#### Scenario: Combined namespace filter and full-text search

- **GIVEN** templates exist in namespaces "larpingapp" and "docudesk", some named "Karakterblad"
- **WHEN** `GET /api/templates?namespace=larpingapp&_search=karakter` is called
- **THEN** only "larpingapp" templates matching "karakter" are returned
- **AND** no "docudesk" templates appear in the results

### Requirement: Consistent object serialization in API responses

Template objects returned from OpenRegister SHALL be serialized consistently. Objects that expose a `jsonSerialize()` method SHALL be serialized via that method. Objects returned as plain arrays SHALL be cast and returned as-is. No `instanceof`-based branching is needed in the controller.

#### Scenario: OpenRegister object serialized via jsonSerialize

- **GIVEN** `ObjectService` returns an object implementing `jsonSerialize()`
- **WHEN** `TemplateService` processes the result
- **THEN** `jsonSerialize()` is called on the object
- **AND** the result is an associative array included in the response

#### Scenario: Paginated search response includes results and total

- **GIVEN** a search that matches 3 of 10 templates
- **WHEN** `searchObjectsPaginated()` returns results
- **THEN** the API response includes `results` (array of 3 template objects) and `total` (3)

### Requirement: Seed templates installed on fresh install

The `templates` schema SHALL ship with 3–5 seed template objects covering at least two distinct namespaces, per ADR-016. Seed objects SHALL demonstrate all six schema fields, both page formats, and both orientations. Seed data SHALL be applied idempotently via `ConfigurationService::importFromApp()`.

#### Scenario: Seed templates visible after install

- **WHEN** DocuDesk is installed and `RegistersLoader` completes
- **THEN** `GET /api/templates` returns at least three seed templates
- **AND** seed templates have stable slugs matching the design.md seed list

#### Scenario: Seed import is idempotent

- **WHEN** DocuDesk is upgraded on an instance that already has the seed templates installed
- **THEN** no duplicate seed templates are created
- **AND** `GET /api/templates` still returns the same set of seed slugs
