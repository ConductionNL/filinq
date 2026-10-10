## Purpose

Provides a stable, slug-addressed render contract that other Conduction apps call to turn an ad-hoc data payload into a stored, school-styled PDF without needing to know filinq's internal template UUIDs, registers, or schemas.

## ADDED Requirements

### Requirement: Shared-secret authentication (REQ-RRA-00)

**Priority:** MUST

Both render endpoints SHALL authenticate the caller via a static bearer token compared against an admin-configured shared secret, since the caller is another app (a server-to-server call), not an interactive Nextcloud session.

#### Scenario: Missing or mismatched token is rejected
- **WHEN** a request omits the `Authorization: Bearer` header, or the token does not match the configured `filinq.report_render_api_token`
- **THEN** the response status is 401

#### Scenario: No token configured refuses every call
- **WHEN** `filinq.report_render_api_token` is unset or empty
- **THEN** every request is rejected with 401 (the endpoint fails closed, never open, when unconfigured)

### Requirement: Single-document render by template slug (REQ-RRA-01)

**Priority:** MUST

`POST /api/v1/documents/render` SHALL resolve a template by `templateSlug` (optionally scoped by `tenantId`), render it against the request's ad-hoc `data` payload through the existing huisstijl/PDF pipeline, store the result, and return a JSON reference to it.

#### Scenario: Render with a tenant-specific template
- **WHEN** `POST /api/v1/documents/render` is called with `{templateSlug: "report-card", tenantId: "school-a", data: {...}}` and a template with that slug and tenant exists
- **THEN** that tenant-specific template is used for rendering

#### Scenario: Render falls back to the namespace-wide default
- **WHEN** the request names a `tenantId` for which no tenant-specific template exists with the given slug
- **THEN** the namespace-wide template carrying that slug (no `tenantId`) is used instead

#### Scenario: Unknown slug returns 404
- **WHEN** no template exists with the given `templateSlug` in any resolution order
- **THEN** the response status is 404 with a JSON error body

#### Scenario: Successful render returns a document reference
- **WHEN** rendering succeeds
- **THEN** the response status is 200 with body `{documentRef, templateSlug, renderedAt}`
- **AND** `documentRef` identifies a stored, retrievable document

#### Scenario: Missing templateSlug is rejected
- **WHEN** the request body omits `templateSlug`
- **THEN** the response status is 400 with a JSON error naming the missing field

#### Scenario: No storage user available is rejected
- **WHEN** the request omits `userId` and no `filinq.report_render_storage_user` default is configured
- **THEN** the response status is 400 with a JSON error naming the missing field

### Requirement: Print-quality output option (REQ-RRA-02)

**Priority:** MUST

The render endpoint SHALL accept an `outputQuality` option (`screen` default, `print`) that, when `print`, produces archival/print-shop-grade PDF output instead of the default screen-quality render.

#### Scenario: Default quality is screen
- **WHEN** `outputQuality` is omitted
- **THEN** the document is rendered at the existing default screen quality

#### Scenario: Print quality routes through PDF/A-3 conversion
- **WHEN** `outputQuality: "print"` is set
- **THEN** the rendered PDF is converted to PDF/A-3 archival quality before being stored
- **AND** the stored document reference reflects the converted (not the pre-conversion) file

### Requirement: Batch render and ZIP (REQ-RRA-03)

**Priority:** MUST

`POST /api/v1/documents/render/batch` SHALL render a list of data payloads against one shared `templateSlug`, package the resulting documents into a single ZIP archive, store it, and return a reference to the archive.

#### Scenario: Batch render produces one ZIP
- **WHEN** `POST /api/v1/documents/render/batch` is called with `{templateSlug: "report-card", tenantId: "school-a", items: [{data:{...}}, {data:{...}}]}`
- **THEN** each item is rendered independently against the resolved template
- **AND** all rendered documents are packaged into one ZIP archive
- **AND** the response is `{documentRef, count: 2}` where `documentRef` identifies the ZIP

#### Scenario: One item's render failure does not abort the batch
- **WHEN** one item in `items` fails to render (e.g. malformed data)
- **THEN** the remaining items are still rendered and included in the ZIP
- **AND** the response includes the failed item's index and error message alongside `documentRef`

#### Scenario: Empty items array is rejected
- **WHEN** `items` is missing or empty
- **THEN** the response status is 400 with a JSON error
