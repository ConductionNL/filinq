# Design: Template Management

## Architecture

### Backend
- `TemplatesController` provides full CRUD: index, show, create, update, destroy
- `TemplateService` handles business logic and OpenRegister operations
- `TemplateRequestHandler` parses request params and builds error responses
- `OpenRegisterResolver` resolves register/schema config and validates namespaces

### Data Model
- Templates stored as OpenRegister objects in template schema
- Properties: name (required), description, content (required, HTML), namespace (required, facetable), format (A4/A3/Letter/Legal), orientation (P/L)
- Namespace is immutable after creation
- Schema imported from `docudesk_register.json` on boot

### Scoping
- Templates scoped per-app via `namespace` field
- Multiple Nextcloud apps can maintain their own template collections
- Consumer apps access via `TemplateService` DI or REST API

## ADR Compliance
- ADR-001: All data via OpenRegister ObjectService
- ADR-008: Controller -> TemplateService -> OpenRegisterResolver layering
