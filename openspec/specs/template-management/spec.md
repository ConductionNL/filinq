---
status: implemented
---

# Template Management

## Purpose

Provides CRUD operations for reusable Twig/HTML templates stored as OpenRegister objects. Templates are scoped per-app via a `namespace` field, enabling multiple Nextcloud apps to maintain their own template collections through a shared service. Consumer apps access templates via the `TemplateService` (DI container) or the REST API.

## Requirements

### Requirement: Template Data Model

**ID:** REQ-TMPL-01
**Priority:** Must

Templates are stored as OpenRegister objects with defined properties for name, content, namespace, and page configuration.

#### Scenario: Template schema in OpenRegister
- GIVEN DocuDesk boots and imports docudesk_register.json
- WHEN the template schema is created
- THEN it contains properties: name, description, content, namespace, format, orientation
- AND the schema is searchable

#### Scenario: Required fields validation
- GIVEN a request to create a template
- WHEN `name`, `content`, or `namespace` is missing
- THEN a 400 error is returned with the missing field name

#### Scenario: Page format defaults
- GIVEN a template created without format or orientation
- WHEN the template is saved
- THEN format defaults to "A4" and orientation defaults to "P" (portrait)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-001 | Templates stored as OpenRegister objects in template schema | MUST | Implemented |
| TMPL-002 | Properties: name (required), description, content (required, HTML), namespace (required, facetable), format (A4/A3/Letter/Legal), orientation (P/L) | MUST | Implemented |
| TMPL-003 | Schema in docudesk_register.json, imported on boot | MUST | Implemented |
| TMPL-004 | Schema is searchable | MUST | Implemented |

### Requirement: Template CRUD API

**ID:** REQ-TMPL-02
**Priority:** Must

Full CRUD operations for templates via REST API with pagination and filtering support.

#### Scenario: List templates with namespace filter
- GIVEN templates exist with namespaces "larpingapp" and "opencatalogi"
- WHEN GET /api/templates?namespace=larpingapp is called
- THEN only templates with namespace=larpingapp are returned
- AND the response includes results array and total count

#### Scenario: Create a template
- GIVEN an authenticated user
- WHEN POST /api/templates is called with name, content, and namespace
- THEN the template is created with a generated UUID
- AND the full template object is returned

#### Scenario: Get single template
- GIVEN a template with UUID "abc-123" exists
- WHEN GET /api/templates/abc-123 is called
- THEN the template object is returned with all fields

#### Scenario: Update a template
- GIVEN a template with UUID "abc-123" exists
- WHEN PUT /api/templates/abc-123 is called with updated content
- THEN the template content is updated
- AND the namespace remains unchanged (immutable)

#### Scenario: Delete a template
- GIVEN a template with UUID "abc-123" exists
- WHEN DELETE /api/templates/abc-123 is called
- THEN the template is deleted from OpenRegister
- AND subsequent GET requests return 404

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-010 | `GET /api/templates` with namespace, _search, _limit, _offset params | MUST | Implemented |
| TMPL-011 | `POST /api/templates` to create | MUST | Implemented |
| TMPL-012 | `GET /api/templates/{id}` to get by UUID | MUST | Implemented |
| TMPL-013 | `PUT /api/templates/{id}` to update | MUST | Implemented |
| TMPL-014 | `DELETE /api/templates/{id}` to delete | MUST | Implemented |
| TMPL-015 | All endpoints require authentication (@NoAdminRequired @NoCSRFRequired) | MUST | Implemented |

### Requirement: Namespace Enforcement

**ID:** REQ-TMPL-03
**Priority:** Must

Templates are scoped to app namespaces with strict validation and immutability after creation.

#### Scenario: Valid namespace
- GIVEN a template creation request with namespace "larpingapp"
- WHEN the namespace is validated
- THEN it passes (lowercase alphanumeric only)

#### Scenario: Invalid namespace with special characters
- GIVEN a template creation request with namespace "my-app"
- WHEN the namespace is validated against `/^[a-z0-9]+$/`
- THEN a 400 error is returned with "Invalid namespace"

#### Scenario: Namespace immutable on update
- GIVEN template "abc-123" exists with namespace "larpingapp"
- WHEN PUT /api/templates/abc-123 is called with `{"namespace": "other", "name": "Updated"}`
- THEN the name is updated but namespace remains "larpingapp"
- AND the namespace field is silently stripped from update data

#### Scenario: Namespace required on create
- GIVEN a template creation request without namespace
- WHEN createTemplate() is called
- THEN a 400 error is returned with "Namespace is required"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-020 | Namespace validated as lowercase alphanumeric (`/^[a-z0-9]+$/`) | MUST | Implemented |
| TMPL-021 | Namespace required on create | MUST | Implemented |
| TMPL-022 | Namespace immutable on update (silently ignored) | MUST | Implemented |
| TMPL-023 | Invalid namespace returns 400 error | MUST | Implemented |

### Requirement: TemplateService Programmatic Access

**ID:** REQ-TMPL-04
**Priority:** Must

TemplateService is injectable via DI, enabling other Nextcloud apps to manage templates programmatically.

#### Scenario: Consumer app fetches templates
- GIVEN LarpingApp resolves TemplateService from the Nextcloud DI container
- WHEN it calls `getTemplatesByNamespace('larpingapp')`
- THEN it receives an array of template objects scoped to its namespace
- AND up to 100 templates are returned

#### Scenario: Get template with not-found handling
- GIVEN TemplateService::getTemplate() is called with a non-existent UUID
- WHEN the object is not found in OpenRegister
- THEN an Exception with code 404 is thrown

#### Scenario: Create template with validation
- GIVEN TemplateService::createTemplate() is called
- WHEN name, content, or namespace is missing
- THEN an Exception with code 400 is thrown with the missing field name

#### Scenario: Update template preserves existing data
- GIVEN a template exists with name, content, namespace, and description
- WHEN updateTemplate() is called with only a new name
- THEN the name is updated
- AND content, namespace, and description are preserved via array_merge

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-030 | `getTemplates(filters, limit, offset)` with pagination | MUST | Implemented |
| TMPL-031 | `getTemplate(id)` throws 404 if not found | MUST | Implemented |
| TMPL-032 | `createTemplate(data)` validates name, content, namespace | MUST | Implemented |
| TMPL-033 | `updateTemplate(id, data)` with namespace immutability | MUST | Implemented |
| TMPL-034 | `deleteTemplate(id)` by UUID | MUST | Implemented |
| TMPL-035 | `getTemplatesByNamespace(namespace)` convenience method | MUST | Implemented |
| TMPL-036 | Injectable via DI: `OCA\DocuDesk\Service\TemplateService::class` | MUST | Implemented |

### Requirement: OpenRegister Integration

**ID:** REQ-TMPL-05
**Priority:** Must

TemplateService resolves register and schema configuration via OpenRegisterResolver and uses ObjectService for all data operations.

#### Scenario: Register/schema resolution
- GIVEN TemplateService needs to perform a CRUD operation
- WHEN `OpenRegisterResolver::getRegisterAndSchema()` is called
- THEN the configured template register and schema IDs are returned
- AND these are used for all ObjectService calls

#### Scenario: OpenRegister unavailable
- GIVEN OpenRegister is not installed
- WHEN TemplateService attempts to resolve ObjectService
- THEN a RuntimeException is thrown
- AND the error is propagated to the caller

#### Scenario: Namespace validation delegation
- GIVEN a namespace is provided during template creation
- WHEN `OpenRegisterResolver::validateNamespace()` is called
- THEN the namespace is validated against the allowed pattern
- AND invalid namespaces throw an Exception

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-040 | OpenRegisterResolver resolves register and schema IDs | MUST | Implemented |
| TMPL-041 | ObjectService resolved via container with OpenRegister availability check | MUST | Implemented |
| TMPL-042 | Namespace validation delegated to OpenRegisterResolver | MUST | Implemented |

### Requirement: Search and Pagination

**ID:** REQ-TMPL-06
**Priority:** Must

Template listing supports search, filtering, and pagination via OpenRegister's query builder.

#### Scenario: Paginated listing
- GIVEN 50 templates exist
- WHEN GET /api/templates?_limit=10&_offset=20 is called
- THEN 10 templates are returned starting from offset 20
- AND the total count is 50

#### Scenario: Search by text
- GIVEN templates with names "Invoice Template" and "Character Sheet"
- WHEN GET /api/templates?_search=invoice is called
- THEN only "Invoice Template" is returned

#### Scenario: Combined filter and search
- GIVEN templates in namespaces "larpingapp" and "procest"
- WHEN GET /api/templates?namespace=larpingapp&_search=character is called
- THEN only larpingapp templates matching "character" are returned

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-050 | Paginated results with _limit and _offset params | MUST | Implemented |
| TMPL-051 | Text search via _search param | MUST | Implemented |
| TMPL-052 | Combined namespace filter and search | MUST | Implemented |

### Requirement: Object Serialization

**ID:** REQ-TMPL-07
**Priority:** Must

Template objects from OpenRegister are consistently serialized for API responses.

#### Scenario: JSON serialization of OpenRegister objects
- GIVEN an OpenRegister object is returned from a query
- WHEN the object has a jsonSerialize() method
- THEN jsonSerialize() is called for consistent output
- AND the result is an associative array

#### Scenario: Non-object results
- GIVEN ObjectService returns a raw array instead of an object
- WHEN the template service processes the result
- THEN the array is cast and returned as-is
- AND no error occurs

#### Scenario: Search results serialization
- GIVEN a paginated search returns multiple objects
- WHEN searchObjectsPaginated() returns results
- THEN the response includes `results` array and `total` count

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| TMPL-060 | Objects with jsonSerialize() are serialized via that method | MUST | Implemented |
| TMPL-061 | Non-object results are cast to array | MUST | Implemented |
| TMPL-062 | Paginated results include `results` array and `total` count | MUST | Implemented |

## Data Model

### Template Schema

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| name | string | Yes | -- | Human-readable template name (max 255 chars) |
| description | string | No | -- | Template purpose description (max 2000 chars) |
| content | string (HTML) | Yes | -- | Twig/HTML template content for PDF rendering |
| namespace | string | Yes | -- | App identifier (max 64 chars, lowercase alphanumeric) |
| format | string (enum) | No | A4 | Page format: A4, A3, Letter, Legal |
| orientation | string (enum) | No | P | Page orientation: P (portrait), L (landscape) |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/templates` | List templates with filters |
| POST | `/api/templates` | Create a template |
| GET | `/api/templates/{id}` | Get single template |
| PUT | `/api/templates/{id}` | Update a template |
| DELETE | `/api/templates/{id}` | Delete a template |

## Dependencies

- **OpenRegister ObjectService**: CRUD operations on template objects
- **OpenRegisterResolver**: Register/schema configuration and namespace validation
- **TemplatesController**: REST API layer
- **docudesk_register.json**: Template schema definition

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/TemplateService.php` -- template CRUD with namespace validation
  - `lib/Service/OpenRegisterResolver.php` -- register/schema resolution and namespace validation
  - `lib/Controller/TemplatesController.php` -- REST API endpoints
  - `lib/Controller/TemplateRequestHandler.php` -- request handling delegation
  - `lib/Settings/docudesk_register.json` -- template schema definition
  - `appinfo/routes.php` -- full CRUD routes
- **No dedicated UI**: Templates managed via REST API or TemplateService DI

### Standards & References
- **Twig 3.x**: Template content uses Twig syntax with pdf-generation sandbox
- **HTML5**: Template content format
- **ISO 216**: A4, A3 paper sizes
