---
retrofit_extensions:
  - REQ-TMPL-08
  - REQ-TMPL-09
  - REQ-TMPL-10
  - REQ-TMPL-11
  - REQ-TMPL-12
---

# Template Management — Retrofit Delta

Adds 5 REQs (REQ-TMPL-08 .. REQ-TMPL-12) describing already-shipped versioning, duplication, edit-locking, and shared-helper behavior in `TemplatesController`, `TemplateRequestHandler`, `TemplateService`, and `TemplateVersionService`.

## ADDED Requirements

### REQ-TMPL-08: Template Version Snapshotting and Retrieval

DocuDesk SHALL persist a versioned snapshot of every template each time its content changes and SHALL expose those snapshots via a paginated history endpoint plus a per-version restore endpoint.

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

---

### REQ-TMPL-09: Template Version Diff Retrieval

DocuDesk SHALL return both source and target version objects for client-side diff rendering, leaving the actual diff computation to the consumer.

The diff endpoint is intentionally thin: the server fetches both versions and returns them under `from` / `to` keys; clients (e.g. the DocuDesk template editor UI) compute the visual diff against the two `content` blobs.

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

---

### REQ-TMPL-10: Template Duplication

DocuDesk SHALL provide a single-call template duplication endpoint that creates a new template object with the same content and metadata but a fresh UUID and an empty version history.

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

---

### REQ-TMPL-11: Template Edit Lock Acquire and Release

DocuDesk SHALL provide an opt-in edit-lock mechanism per template, allowing a single user to claim exclusive edit rights for a bounded TTL and to release the lock when finished.

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

---

### REQ-TMPL-12: Shared Template Request Parsing and Error Response Helpers

DocuDesk SHALL centralise list-parameter parsing, body-parameter parsing, and exception-to-JSON conversion for template controllers in a single `TemplateRequestHandler` so every endpoint exposes consistent paging, filtering, and error semantics.

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
