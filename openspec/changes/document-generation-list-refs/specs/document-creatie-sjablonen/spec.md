# document-creatie-sjablonen Specification (delta)

---
status: proposed
---

## Purpose

Extend data resolution (REQ-DCS-01) and the generation API (REQ-DCS-07) with
`options.listRefs`: a collection-shaped counterpart to `dataRefs` that
resolves an array of OpenRegister objects — via the standard slug-aware
search path, including external DBAL-backed virtual registers — into the
Twig render context under a named key, so templates can loop over a table
(e.g. an app's competitors) instead of only hydrating single objects.
Existing `dataRefs` / `adHocData` behaviour is unchanged; this delta only
ADDs.

## ADDED Requirements

### Requirement: listRefs resolve collections into the Twig context (REQ-DDLR-001)

`DataResolverService::resolve()` MUST accept an optional `listRefs`
parameter: an array of `{register, schema, filter?, limit?, order?, as?}`
entries. Each entry MUST resolve via
`OCA\OpenRegister\Service\ObjectService::setRegister()` /
`::setSchema()` (both slug-aware) followed by `::searchObjectsPaginated()`
— the register/schema-context pattern that also reaches a schema's
`x-openregister-object-source` provider when one is configured (unlike
the sibling `searchObjects()`/`searchObjectsBySlug()` methods, which never
consult the object-source and return nothing for a DBAL-backed schema) —
to an array of the matching objects (each serialized via `jsonSerialize()`
where available), placed in the merged data context under the key `as`.
When `as` is omitted it MUST default to the schema slug converted to a
legal Twig identifier (every character outside `[a-zA-Z0-9_]` replaced
with `_`, a leading digit prefixed with `_`) with `_list` appended — e.g.
schema `v-app-competitors` defaults to `v_app_competitors_list`. `filter`
entries (if present) MUST be passed through as top-level search filter
keys; `limit` MUST be forwarded as the search's `_limit`; `order` (if an
array) MUST be forwarded as `_order`.

#### Scenario: Resolve a DBAL-backed collection with an explicit `as` key

- GIVEN a virtual register `spectr-live` with schema `v-app-competitors`, reachable via OpenRegister's DBAL search path
- WHEN a `listRefs` entry `{register: "spectr-live", schema: "v-app-competitors", filter: {app_id: 6}, limit: 5, as: "competitors"}` is resolved
- THEN the Twig context contains a `competitors` key
- AND it is an array of at most 5 objects, each matching `app_id: 6`
- @e2e tests/e2e/spec-coverage/document-generation-list-refs.spec.ts

#### Scenario: Default `as` key sanitises a hyphenated schema slug

- GIVEN a `listRefs` entry with `schema: "v-app-competitors"` and no `as`
- WHEN the listRef is resolved
- THEN the resolved array appears under the context key `v_app_competitors_list`
- AND `{% for c in v_app_competitors_list %}` is valid, renderable Twig
- @e2e tests/e2e/spec-coverage/document-generation-list-refs.spec.ts

#### Scenario: A listRef search failure is a soft, per-item error

- GIVEN two `listRefs` entries, one referencing an unresolvable schema slug
- WHEN `resolve()` runs
- THEN the listRef with the valid slug still resolves its array under its `as` key
- AND the failing listRef's `as` key is present with an empty array
- AND the failure is reported in `errors` (mirroring how `dataRefs` failures are reported), not thrown
- @e2e exclude fault-injection (an unresolvable slug at request time) is not browser-drivable — covered by PHPUnit (tests/unit/Service/ListReferenceResolverTest.php::testSearchFailureIsSoftError)

### Requirement: listRefs precedence and resolution order (REQ-DDLR-002)

`resolve()` MUST resolve `listRefs` AFTER `dataRefs` and BEFORE `adHocData`.
`adHocData` MUST take precedence over a `listRefs`-resolved key on conflict,
consistent with `adHocData`'s existing precedence over `dataRefs`
(REQ-DCS-01). The combined precedence order is: `dataRefs` < `listRefs` <
`adHocData`.

#### Scenario: adHocData overrides a listRef's key

- GIVEN a `listRefs` entry resolves under the key `competitors`
- AND `options.adHocData` also supplies a `competitors` key
- WHEN `resolve()` runs
- THEN the Twig context's `competitors` value is the `adHocData` value, not the listRef's resolved array
- @e2e exclude precedence-ordering pin; covered by PHPUnit (tests/unit/Service/DataResolverServiceTest.php::testAdHocDataOverridesListRef)

### Requirement: listRefs guardrails reject malformed requests with HTTP 400 (REQ-DDLR-003)

Guardrail violations MUST be validated for every `listRefs` entry BEFORE any
OpenRegister search runs for the request (fail-fast: a malformed entry
aborts the whole request, not just itself), and MUST surface as HTTP 400
through `DocumentController`'s existing exception-to-status mapping
(`Exception` code 400). The guardrails are: (a) at most 10 `listRefs`
entries per request; (b) each entry's `filter` values MUST be scalars (or
null) — an array/object filter value is rejected, not silently coerced or
dropped; (c) each entry's `limit`, if present, MUST be an integer between 1
and 500 inclusive; (d) each entry's resolved `as` key MUST match
`/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/`; (e) no two resolved `as` keys within one
request — across `dataRefs` schema keys and all `listRefs` — may collide.

#### Scenario: More than 10 listRefs is rejected

- GIVEN a request with 11 `listRefs` entries
- WHEN `POST /api/documents/generate/preview` is called
- THEN the response is HTTP 400
- AND no OpenRegister search runs for any of the 11 entries
- @e2e exclude guardrail boundary; covered by PHPUnit (tests/unit/Service/ListReferenceResolverTest.php::testTooManyListRefsRejected, ::testGuardrailViolationAbortsBeforeAnySearch)

#### Scenario: A non-scalar filter value is rejected

- GIVEN a `listRefs` entry with `filter: {nested: {not: "scalar"}}`
- WHEN the request is validated
- THEN the response is HTTP 400 and identifies the offending filter key
- @e2e exclude guardrail boundary; covered by PHPUnit (tests/unit/Service/ListReferenceResolverTest.php::testNonScalarFilterValueRejected)

#### Scenario: `as` colliding with a dataRefs key is rejected

- GIVEN a `dataRefs` entry resolves under the schema key `persoon`
- AND a `listRefs` entry explicitly sets `as: "persoon"`
- WHEN the request is validated
- THEN the response is HTTP 400
- @e2e exclude guardrail boundary; covered by PHPUnit (tests/unit/Service/DataResolverServiceTest.php::testListRefAsKeyCollidesWithDataRefKey, tests/unit/Service/ListReferenceResolverTest.php::testAsKeyCollisionWithReservedKeyRejected, ::testAsKeyCollisionBetweenTwoListRefsRejected)

### Requirement: listRefs are not wired into bulk generation (REQ-DDLR-004)

`DocumentService::generateBulk()` MUST NOT accept or resolve
`options.listRefs`, on either the synchronous or the async
`BatchDocumentJob` path. This is an explicit scope exclusion, not an
oversight: the async job
persists per-object job status/progress but no per-object rendered output
artifact, so a per-object-resolved list would have nowhere durable to
attach to on the majority (>10 objects) branch, and silently doing nothing
is worse than not offering it.

#### Scenario: listRefs on a bulk request has no effect

- GIVEN a `POST /api/documents/generate/bulk` request includes `options.listRefs`
- WHEN bulk generation runs (sync or async)
- THEN `options.listRefs` is not read or resolved by `generateBulk()`
- AND this is documented on `DocumentService::generateBulk()`'s docblock, not silently dropped without explanation
- @e2e exclude scope-exclusion pin, not a behaviour to browser-test; covered by code review of DocumentService::generateBulk() docblock
