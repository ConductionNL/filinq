# Template Management

## Problem
Provides CRUD operations for reusable Twig/HTML templates stored as OpenRegister objects. Templates are scoped per-app via a `namespace` field, enabling multiple Nextcloud apps to maintain their own template collections through a shared service. Consumer apps access templates via the `TemplateService` (DI container) or the REST API.

## Proposed Solution
Implement Template Management following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the template-management specification.

## Success Criteria
- Template schema in OpenRegister
- Required fields validation
- Page format defaults
- List templates with namespace filter
- Create a template
