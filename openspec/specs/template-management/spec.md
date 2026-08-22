---
status: in-progress
retrofit_extensions:
  - REQ-TMPL-08
  - REQ-TMPL-09
  - REQ-TMPL-10
  - REQ-TMPL-11
  - REQ-TMPL-12
---

# Template Management

**Status**: in-progress
**OpenSpec changes**:
- [office-template-authoring](../../changes/office-template-authoring/) _(active)_ — office-file-native templates: `templateType`/source-file data-model extension (REQ-DDOTA-006) and versioning/lock/preview/duplicate parity for office templates (REQ-DDOTA-007) (kind: code)
- [guided-document-wizard](../../changes/guided-document-wizard/) _(active)_ — adds the `wizardDefinition` schema to the templates register (guided-interview definitions fronting a template by `templateId`; the `template`/`templateVersion` data model is unchanged) — full requirements live in the `guided-document-wizard` capability (REQ-DDGDW-*) (kind: code)

## Purpose

<!--
  WHOLE-SPEC `@e2e exclude` REMOVED 2026-08-11 — IT WAS FALSE, AND IT SUPPRESSED
  ALL 45 SCENARIOS IN THIS FILE.

  The removed reason read:

    "template management has no dedicated Filinq UI — templates are managed
     via REST API or TemplateService DI injection from consumer apps; API
     behavior covered by PHPUnit service and controller tests"

  All three parts of the audit rule fail against this repository as it stands:

  (a) THE UI EXISTS. `src/manifest.json` declares page `Templates` at route
      `/templates` (type index) and `TemplateDetail` at `/templates/:id`, plus a
      top-level menu entry `{ id: "Templates", label: "Templates" }`.
      `src/views/templates/TemplateDetail.vue` implements the editor, preview
      and version-history panes.

  (b) RUNNING TESTS ALREADY CONTAIN THE ASSERTIONS — including tests already
      anchored to THIS spec. `tests/e2e/spec-coverage/templates.spec.ts` carries
      `@e2e openspec/specs/template-management/spec.md#create-a-template` and
      `#list-templates-with-namespace-filter`, and
      `tests/e2e/workflows/templates-crud.spec.ts` drives create / read / list /
      update / delete and the required-field rejections end to end.

  (c) THEY RUN IN THIS REPO'S PIPELINE. `.github/workflows/code-quality.yml`
      sets `enable-playwright: true`; the most recent `E2E Tests (Playwright)`
      job (run 31461514843) reported completed/success with 94 passed / 4
      skipped, and all five templates-crud tests are individually green in that
      log.

  A whole-spec waiver whose own spec is the target of anchors inside the passing
  suite is self-refuting. Scenarios that a running test genuinely exercises are
  now anchored; the rest are counted as the open debt they are, rather than
  hidden behind this line.
-->


Provides CRUD operations for reusable Twig/HTML templates stored as OpenRegister objects. Templates are scoped per-app via a `namespace` field, enabling multiple Nextcloud apps to maintain their own template collections through a shared service. Consumer apps access templates via the `TemplateService` (DI container) or the REST API.

## Requirements

### Requirement: Template Data Model (REQ-TMPL-01)

**Priority:** MUST

Templates are stored as OpenRegister objects with defined properties for name, content, namespace, and page configuration.

#### Scenario: Template schema in OpenRegister
- GIVEN Filinq boots and imports filinq_register.json
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
| TMPL-003 | Schema in filinq_register.json, imported on boot | MUST | Implemented |
| TMPL-004 | Schema is searchable | MUST | Implemented |

### Requirement: Template CRUD API (REQ-TMPL-02)

**Priority:** MUST

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

### Requirement: Namespace Enforcement (REQ-TMPL-03)

**Priority:** MUST

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

### Requirement: TemplateService Programmatic Access (REQ-TMPL-04)

**Priority:** MUST

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
| TMPL-036 | Injectable via DI: `OCA\Filinq\Service\TemplateService::class` | MUST | Implemented |

### Requirement: OpenRegister Integration (REQ-TMPL-05)

**Priority:** MUST

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

### Requirement: Search and Pagination (REQ-TMPL-06)

**Priority:** MUST

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

### Requirement: Object Serialization (REQ-TMPL-07)

**Priority:** MUST

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

### Templates Register Schemas

The `templates` register (`lib/Settings/filinq_register.json`) is the single
source of truth for template-related schemas. It contains:

| Schema | Owning capability | Purpose |
|--------|-------------------|---------|
| `template` | template-management | The template data model (this spec, REQ-TMPL-01) |
| `templateVersion` | template-management | Versioned template snapshots (REQ-TMPL-08) |
| `textFragment` | office-template-authoring | Reusable text fragments for office templates |
| `templateImportJob` | office-template-authoring | Office-template import job records |
| `wizardDefinition` | guided-document-wizard | Guided-interview definition (ordered questions, skip logic, register-object pickers) attached to exactly one template by `templateId`; does not modify the `template`/`templateVersion` data model — full requirements in the `guided-document-wizard` capability (REQ-DDGDW-*) |

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
- **filinq_register.json**: Template schema definition

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Service/TemplateService.php` -- template CRUD with namespace validation
  - `lib/Service/OpenRegisterResolver.php` -- register/schema resolution and namespace validation
  - `lib/Controller/TemplatesController.php` -- REST API endpoints
  - `lib/Controller/TemplateRequestHandler.php` -- request handling delegation
  - `lib/Settings/filinq_register.json` -- template schema definition
  - `appinfo/routes.php` -- full CRUD routes
- **No dedicated UI**: Templates managed via REST API or TemplateService DI

### Standards & References
- **Twig 3.x**: Template content uses Twig syntax with pdf-generation sandbox
- **HTML5**: Template content format
- **ISO 216**: A4, A3 paper sizes

---

## Retrofit Requirements (REQ-TMPL-08 .. REQ-TMPL-12)

The following REQs were reverse-engineered from already-shipped code on 2026-05-24 via
ghost change `retrofit-2026-05-24-template-management` (archived). They describe
observed behavior — see Notes sections for surfaced ambiguities.

### REQ-TMPL-08: Template Version Snapshotting and Retrieval

Filinq SHALL persist a versioned snapshot of every template each time its content changes and SHALL expose those snapshots via a paginated history endpoint plus a per-version restore endpoint.

Snapshots are stored as OpenRegister objects in the version register/schema returned by `OpenRegisterResolver::getVersionRegisterAndSchema()`. Each snapshot carries `templateId`, an integer `version` number, the captured `content`, `name`, `description`, `format`, `orientation`, the `editor` user id, and an optional `changelog` note. The next version number for a template is computed as `total existing versions + 1`. Restoring a version first writes an auto-snapshot of the current state (with a changelog `"Auto-saved before restore to version <N>"`) and then overwrites the template's content/name/description/format/orientation with the target version's fields via `TemplateService::updateTemplateWithoutVersion()` so the restore itself does not produce a duplicate snapshot.

#### Scenario: Snapshotting creates a numbered version

- **WHEN** `TemplateVersionService::createVersion(templateId, state, editor, changelog?)` is called
- **THEN** an OpenRegister object is saved in the version schema containing the captured fields, `editor`, and `changelog` (empty string if omitted)
- **AND** the object's `version` field equals `getNextVersionNumber(templateId)` (existing total + 1)

#### Scenario: Paginated version history

- **WHEN** `GET /api/templates/{id}/versions?_limit=L&_offset=O` is called
- **THEN** versions are returned ordered by `version` descending, paginated by `L`/`O` (defaults `20` / `0`)
- **AND** the payload is `{results: [...], total: N}`

#### Scenario: Single version lookup

- **WHEN** `TemplateVersionService::getVersion(versionId)` is called for an unknown UUID
- **THEN** an `Exception` with code `404` is thrown

#### Scenario: Restore writes auto-snapshot then overwrites template

- **WHEN** `POST /api/templates/{id}/versions/{versionId}/restore` is called
- **THEN** the current template state is saved as a new version with `changelog = "Auto-saved before restore to version <targetVersion>"`
- **AND** the template's `content`, `name`, `description`, `format`, `orientation` are overwritten with the target version's values via `updateTemplateWithoutVersion()` so no second snapshot is produced
- **AND** the restored template object is returned

#### Notes

- Version numbering uses `total + 1` rather than `max(version) + 1`. If a row is ever hard-deleted from the version schema, the next allocated number will collide with the highest extant number. TODO: revisit if hard-delete is ever supported on the version schema.
- Restore is irreversible at the spec level only in that it cannot be undone in a single call; the user can manually restore the auto-snapshot. No "undo restore" endpoint is exposed.

### REQ-TMPL-09: Template Version Diff Retrieval

Filinq SHALL return both source and target version objects for client-side diff rendering, leaving the actual diff computation to the consumer.

The diff endpoint is intentionally thin: the server fetches both versions and returns them under `from` / `to` keys; clients (e.g. the Filinq template editor UI) compute the visual diff against the two `content` blobs.

#### Scenario: Diff requires both endpoints

- **WHEN** `GET /api/templates/{id}/versions/diff?from=A&to=B` is called with empty `from` or `to`
- **THEN** an `Exception` with code `400` and message `"Both 'from' and 'to' version UUIDs are required"` is raised and returned as a JSON error response

#### Scenario: Diff returns paired version payloads

- **WHEN** `GET /api/templates/{id}/versions/diff?from=A&to=B` is called with valid UUIDs
- **THEN** `TemplateVersionService::getDiff(A, B)` returns `{from: <version A>, to: <version B>}`
- **AND** the response status is 200

#### Scenario: Diff propagates not-found

- **WHEN** either `from` or `to` UUID is unknown
- **THEN** the underlying `getVersion()` 404 propagates and is rendered by `TemplateRequestHandler::buildErrorResponse()` as a 404 JSON error

#### Notes

- The `id` (template UUID) is currently not validated against the two version objects' `templateId`. A client can technically request a diff between versions of two different templates. TODO: add a guard if cross-template diffs become a vector for confusion.

### REQ-TMPL-10: Template Duplication

Filinq SHALL provide a single-call template duplication endpoint that creates a new template object with the same content and metadata but a fresh UUID and an empty version history.

The duplicated template name is the original name with the literal Dutch suffix `" (kopie)"` appended. `namespace`, `format`, `orientation`, `category`, and `tags` are preserved verbatim; `description` and `content` are copied. `lockedBy` / `lockedAt` and version-history snapshots are NOT copied — the duplicate starts unlocked with zero history.

#### Scenario: Duplicate creates a new template with copied content

- **WHEN** `POST /api/templates/{id}/duplicate` is called for an existing template
- **THEN** a new OpenRegister object is saved in the template register/schema with a new UUID
- **AND** the new object's `name` is `"<original name> (kopie)"`
- **AND** `description`, `content`, `namespace`, `format`, `orientation`, `category`, `tags` mirror the original
- **AND** the new template object is returned

#### Scenario: Duplicate does not copy lock or version history

- **GIVEN** the source template is locked by user A and has 3 version snapshots
- **WHEN** the template is duplicated
- **THEN** the duplicate has no `lockedBy` / `lockedAt`
- **AND** `GET /api/templates/{newId}/versions` returns 0 entries

#### Scenario: Duplicate of missing template returns 404

- **WHEN** `POST /api/templates/{id}/duplicate` is called with an unknown id
- **THEN** the underlying `getTemplate()` 404 propagates and is rendered as a 404 JSON error

#### Notes

- The `" (kopie)"` suffix is hard-coded Dutch and is not i18n'd. TODO: thread through `IL10N` when the duplicate endpoint is consumed by a UI that needs localised copy.
- Repeated duplication produces `"X (kopie) (kopie)"` etc.; no de-duplication of the suffix is attempted.

### REQ-TMPL-11: Template Edit Lock Acquire and Release

Filinq SHALL provide an opt-in edit-lock mechanism per template, allowing a single user to claim exclusive edit rights for a bounded TTL and to release the lock when finished.

A lock is represented by two fields on the template object: `lockedBy` (user id) and `lockedAt` (ISO-8601 timestamp). Locks expire after a fixed `LOCK_TIMEOUT_MINUTES = 15` window. Acquire is idempotent for the lock holder (it refreshes the timestamp) and steals an expired lock from any other holder. Release clears both fields and is restricted to the current holder (or anyone if the lock is already expired). Locking does not block reads, updates, or deletes at the persistence layer — it is an advisory cooperative lock that clients should honour.

#### Scenario: Acquire on an unlocked template

- **WHEN** `POST /api/templates/{id}/lock` is called and the template has no `lockedBy`
- **THEN** `lockedBy` is set to the requesting user id and `lockedAt` to `now` (ISO-8601, `DateTime::format('c')`)
- **AND** the updated template is returned with status 200

#### Scenario: Acquire when another user holds a fresh lock

- **GIVEN** the template is locked by user A with `lockedAt` < 15 minutes ago
- **WHEN** user B calls `POST /api/templates/{id}/lock`
- **THEN** an `Exception` with code `409` is thrown
- **AND** the response status is 409 with body `{"error":"Template is locked by another user","lockedBy":"<A>","lockedAt":"<ts>"}`

#### Scenario: Acquire steals an expired lock

- **GIVEN** the template is locked by user A with `lockedAt` more than 15 minutes ago
- **WHEN** user B calls `POST /api/templates/{id}/lock`
- **THEN** the request succeeds and `lockedBy` becomes user B with `lockedAt = now`

#### Scenario: Release by the holder

- **WHEN** `POST /api/templates/{id}/unlock` is called by the current `lockedBy`
- **THEN** `lockedBy` and `lockedAt` are set to `null` and the updated template is returned

#### Scenario: Release rejected for non-holder of an active lock

- **GIVEN** the template is locked by user A with a fresh `lockedAt`
- **WHEN** user B calls `POST /api/templates/{id}/unlock`
- **THEN** an `Exception` with code `403` and message `"Cannot release lock held by another user"` is raised

#### Scenario: Lock-expiry helper

- **WHEN** `TemplateService::isLockExpired(template)` is called
- **THEN** it returns `true` if `lockedAt` is empty, unparseable, or older than `LOCK_TIMEOUT_MINUTES` (15)
- **AND** `false` otherwise

#### Notes

- The 15-minute TTL is a private class constant (`LOCK_TIMEOUT_MINUTES`); not yet configurable per-deploy. TODO: surface as IAppConfig when ops asks for it.
- Locks are advisory only — the underlying `updateTemplate()` and `deleteTemplate()` paths do NOT consult `lockedBy`. A client that ignores the lock can still write. TODO: enforce server-side if observed bypass becomes a problem.
- Acquire/release call `updateTemplateWithoutVersion()`, so lock churn does not pollute the version history.

### REQ-TMPL-12: Shared Template Request Parsing and Error Response Helpers

Filinq SHALL centralise list-parameter parsing, body-parameter parsing, and exception-to-JSON conversion for template controllers in a single `TemplateRequestHandler` so every endpoint exposes consistent paging, filtering, and error semantics.

`parseListParams(IRequest)` reads `namespace`, `_search`, `_limit` (default 20), `_offset` (default 0) and returns `{filters: {namespace?, _search?}, limit: int, offset: int}` — only non-empty filters are included. `parseBodyParams(IRequest, stripKeys?)` returns the request param bag with the framework's `_route` key always removed and any caller-listed keys also stripped (e.g. `id` on update). `buildErrorResponse(Exception, prefix)` logs the exception with the given prefix and returns a `JSONResponse` whose status code is the exception's code when it falls in `[400, 600)` and `500` otherwise; the body is `{"error":"<exception message>"}`.

#### Scenario: List-params parser keeps only non-empty filters

- **WHEN** `parseListParams()` is called with `namespace=larpingapp` and an empty `_search`
- **THEN** the returned `filters` contains `namespace` only (not `_search`)
- **AND** `limit` and `offset` default to `20` and `0` when the request omits them

#### Scenario: Body parser strips framework + caller-listed keys

- **WHEN** `parseBodyParams(request, stripKeys: ['id'])` is called on `PUT /api/templates/{id}`
- **THEN** the returned array omits `_route` and `id`
- **AND** all other body fields pass through unchanged

#### Scenario: Error helper clamps status code

- **WHEN** `buildErrorResponse(new Exception("nope", 418), "x: ")` is called
- **THEN** the response status is `418` and body is `{"error":"nope"}`
- **AND** the logger receives `"x: nope"` with the exception in context

#### Scenario: Error helper defaults to 500

- **WHEN** the exception code is `0` or outside `[400, 600)`
- **THEN** the response status is `500`

#### Notes

- The helper does not normalise field names or coerce types beyond `(int)` casts on limit/offset. A non-numeric `_limit` becomes `0`, which OpenRegister treats as "no limit". TODO: validate and 400 on malformed pagination if it becomes observable.
- `buildErrorResponse` does not include a stack trace in the JSON body even at debug level — only the exception message is exposed to the caller. The full trace is in the log via `context: ['exception' => $exception]`.
