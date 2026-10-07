## MODIFIED Requirements

### Requirement: Template Data Model (REQ-TMPL-01)

**Priority:** MUST

Templates are stored as OpenRegister objects with defined properties for name, content, namespace, page configuration, and an optional stable slug for lookup by external callers.

#### Scenario: Template schema in OpenRegister
- GIVEN Filinq boots and imports filinq_register.json
- WHEN the template schema is created
- THEN it contains properties: name, description, content, namespace, format, orientation, slug, tenantId
- AND the schema is searchable

#### Scenario: Required fields validation
- GIVEN a request to create a template
- WHEN `name`, `content`, or `namespace` is missing
- THEN a 400 error is returned with the missing field name

#### Scenario: Page format defaults
- GIVEN a template created without format or orientation
- WHEN the template is saved
- THEN `format` defaults to `A4` and `orientation` defaults to `P`

#### Scenario: Slug is optional and namespace-scoped
- GIVEN two templates in different namespaces both set `slug: "report-card"`
- WHEN both are saved
- THEN both saves succeed (slug uniqueness is per namespace, not global)

#### Scenario: Duplicate slug within the same namespace and tenant is rejected
- GIVEN a template with `namespace: "learniq"`, `tenantId: "school-a"`, `slug: "report-card"` already exists
- WHEN a second template is created with the same `namespace`, `tenantId`, and `slug`
- THEN a 400 error is returned naming the conflicting slug

## ADDED Requirements

### Requirement: Template slug resolution (REQ-TMPL-13)

**Priority:** MUST

Filinq SHALL resolve a template by `namespace` + `slug`, optionally narrowed by `tenantId`, falling back from a tenant-specific match to a namespace-wide match (no `tenantId` set) when no tenant-specific template exists.

#### Scenario: Resolve a tenant-specific template
- GIVEN a template with `namespace: "learniq"`, `slug: "report-card"`, `tenantId: "school-a"`
- AND a second template with `namespace: "learniq"`, `slug: "report-card"`, no `tenantId`
- WHEN resolution is requested for `namespace: "learniq"`, `slug: "report-card"`, `tenantId: "school-a"`
- THEN the tenant-specific template is returned

#### Scenario: Fall back to the namespace-wide default
- GIVEN only a template with `namespace: "learniq"`, `slug: "report-card"`, no `tenantId` exists
- WHEN resolution is requested for `namespace: "learniq"`, `slug: "report-card"`, `tenantId: "school-b"`
- THEN the namespace-wide template is returned

#### Scenario: No match at all
- GIVEN no template exists with the given `namespace` and `slug` in either form
- WHEN resolution is requested
- THEN a not-found error (code 404) is raised
