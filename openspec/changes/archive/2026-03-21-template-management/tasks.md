# Tasks: template-management

## Task 1: Template CRUD API
- [x] Implement `GET /api/templates` with namespace filter and pagination
- [x] Implement `POST /api/templates` with validation
- [x] Implement `GET /api/templates/{id}`
- [x] Implement `PUT /api/templates/{id}` with namespace immutability
- [x] Implement `DELETE /api/templates/{id}`

## Task 2: Template Service
- [x] Implement `TemplateService` with getTemplates, createTemplate, updateTemplate, deleteTemplate
- [x] Implement namespace validation (alphanumeric + hyphens only)
- [x] Implement `getTemplatesByNamespace()` convenience method

## Task 3: Request Handler
- [x] Extract `TemplateRequestHandler` for request parsing
- [x] Implement `parseListParams()` for namespace, search, limit, offset
- [x] Implement `parseBodyParams()` for create/update

## Task 4: Unit Tests (ADR-009)
- [x] Write `TemplateServiceTest` with comprehensive CRUD tests
- [x] Test validation errors (missing namespace, name, content)
- [x] Test namespace validation (invalid characters)
- [x] Test OpenRegister not available error

## Task 5: Documentation (ADR-010)
- [x] Write feature documentation at `docs/features/template-management.md`

## Task 6: i18n (ADR-005)
- [x] No user-facing strings (API-only service)
