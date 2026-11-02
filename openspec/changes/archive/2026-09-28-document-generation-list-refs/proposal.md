---
kind: code
---

# Proposal: document-generation-list-refs

## Why

Document generation today (`DataResolverService::resolve()`, verified at
HEAD) resolves `dataRefs` — each a single `{register, schema, id}` reference
— into one hydrated object per entry, keyed by schema name in the Twig
context. That covers "this beschikking's aanvrager" but not "this app's
competitor table": there is no way to put an **array** of OpenRegister
objects into the render context. Reports that need a collection (an app's
competitors, a case's related documents, a period's invoices) have to be
pre-flattened into `options.adHocData` by the caller, defeating the point of
server-side resolution (no audit trail of what was queried, no reuse of
OpenRegister's filtering/sorting, and the caller needs read access to the
raw register itself).

OpenRegister's `ObjectService::searchObjectsPaginated()` — the same method
+ `setRegister()`/`setSchema()` context pattern `ObjectsController::index()`
itself uses — already resolves register/schema slugs (both setters accept
slug strings) and, when the resolved schema declares an
`x-openregister-object-source`, delegates live to that provider instead of
the magic table/generic storage. External DBAL-backed virtual registers
(e.g. a `spectr-live` register backed by an external Postgres table) are
searchable through this exact path. (Note: the sibling
`searchObjects()`/`searchObjectsBySlug()` methods do NOT check for an
object-source — they only ever read the magic table / generic object
storage, so they silently return nothing for a DBAL-backed schema; verified
live against `spectr-live` during implementation, see design note below.)
So the resolver data is a couple of method calls away; the data source is
already there.

Priority **should-have**.

## What Changes

- **`listRefs` list-binding**: `POST /api/documents/generate` and
  `POST /api/documents/generate/preview` accept a new `options.listRefs`
  array alongside the existing `dataRefs`. Each entry —
  `{register, schema, filter?, limit?, order?, as?}` — resolves via
  OpenRegister's slug-aware search to an **array** of serialized objects,
  placed in the Twig context under the key `as` (default: the schema slug
  sanitised to a legal Twig identifier, + `'_list'` — e.g. schema
  `v-app-competitors` defaults to `v_app_competitors_list`). Templates loop
  over it directly: `{% for c in competitors %}...{% endfor %}`.
- **Resolution order / precedence**: `listRefs` resolve AFTER `dataRefs` and
  BEFORE `adHocData` — `adHocData` still wins on key conflicts, consistent
  with today's `dataRefs` < `adHocData` precedence, extended to
  `dataRefs` < `listRefs` < `adHocData`.
- **Guardrails** (all violations are HTTP 400, validated up front —
  fail-fast, before any OpenRegister search runs for the request): at most
  10 `listRefs` per request; per-list `limit` defaults to 50 and is capped
  at 500; `filter` values must be scalars (an array/object filter value is
  rejected, not silently dropped); `as` must match
  `/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/` and must not collide with a `dataRefs`
  key or another `listRefs`' key. A per-list OpenRegister search *failure*
  (e.g. an unresolvable slug) is a soft, per-item error — mirroring how
  `dataRefs` failures already behave — and does not abort the other lists.
- **New collaborator**: `ListReferenceResolver` (new class,
  `lib/Service/ListReferenceResolver.php`) owns listRef validation and
  resolution; `DataResolverService::resolve()` delegates to it. Kept as a
  separate class (rather than inlined into `DataResolverService`) to stay
  under the class-complexity budget enforced by `phpmd` and because the two
  concerns — single-object hydration vs. collection search — are
  independently testable.
- **Scope**: wired into `DocumentController::generate` /
  `DocumentController::preview` and their `DocumentService` counterparts
  only. `DocumentService::generateBulk()`'s async path
  (`BatchDocumentJob`) is explicitly **not** wired: the job records
  per-object job progress/status but does not persist per-object rendered
  output anywhere a per-object-resolved list could usefully attach to, so
  `options.listRefs` on the bulk endpoint would silently do nothing for the
  async (>10 objects) branch. Revisit if/when bulk gains a real per-object
  output artifact.

## Impact

- Affected specs: `document-creatie-sjablonen` (extends REQ-DCS-01 data
  resolution and REQ-DCS-07 the generation API; both ADDs, no existing
  requirement is modified).
- Affected code: `lib/Service/DataResolverService.php` (delegates to the new
  resolver; `resolve()` gains an optional `listRefs` parameter — additive,
  existing named-argument call sites unaffected),
  `lib/Service/ListReferenceResolver.php` (new),
  `lib/Service/DocumentService.php` (`generateDocument()` /
  `generatePreview()` forward `options.listRefs`),
  `lib/Controller/DocumentController.php` (docblocks only — `options` was
  already passed through opaquely).
- No register/schema/manifest changes; no new routes (existing
  generate/preview routes gain a new optional request field).

## Design note: searchObjectsBySlug() vs. searchObjectsPaginated()

The first implementation of `ListReferenceResolver::resolveList()` used
`ObjectService::searchObjectsBySlug()` (a slug-aware wrapper the task
guidance suggested). Live verification against the real `spectr-live`
register (`app_id`-filterable `competitors` schema) returned an empty array
every time, with no error. Tracing `searchObjectsBySlug()` →
`searchObjects()` → `QueryHandler::searchObjects()` showed this path never
consults `Schema::getObjectSource()` — it only reads the magic table or
generic object storage, both empty for a purely-external DBAL-backed
schema. Only `ObjectService::searchObjectsPaginated()` checks
`$this->currentSchema` (set via `setRegister()`/`setSchema()`, both of
which accept slug strings directly) and delegates to the object-source
provider via `paginateObjectSource()` when one is configured — the exact
mechanism `ObjectsController::index()` itself uses for the real
`GET /apps/openregister/api/objects/{register}/{schema}` endpoint. Fixed to
use `setRegister()`/`setSchema()` + `searchObjectsPaginated()`; re-verified
live with real rows returned. `tests/stubs/OpenRegisterStubs.php` (the
PHPUnit-mocking stand-in for `OCA\OpenRegister\Service\ObjectService`)
gained `setRegister()`/`setSchema()`/`getRegister()`/`getSchema()` stubs
to make this mockable.
