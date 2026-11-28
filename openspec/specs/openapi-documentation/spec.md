---
status: implemented
---

# openapi-documentation Specification

**OpenSpec changes**:
- [connect-openapi-documentation](../../changes/archive/2026-11-24-connect-openapi-documentation/) _(archived 2026-11-24)_

## Purpose

filinq publishes one OpenAPI document that covers every route it serves,
kept in step with the routes by tests that run in CI, and shown on the docs
site. Matrix row `con-api` (filinq).

Decision (24 Nov 2026): the document is maintained, not generated.
nextcloud/openapi-extractor 1.9.1 stops on filinq's `EUPL-1.2` licence and
documents a non-OCS route only once every method carries `#[OpenAPI]` and a
typed `@return`; the maintained `openapi.json` (the `api-contract` spec:
`/api/v1/` paths, problem details, version headers) already documents every
route and is richer than what the extractor would produce. The checks below
pin it to the code instead.

## Requirements

### Requirement: One OpenAPI document matches the routes both ways (REQ-COD-001)

`openapi.json` at the repository root MUST document every versioned route in
`appinfo/routes.php`, and MUST NOT document a path filinq does not serve.
Operations served by OpenRegister's AppHost MUST be marked
`x-provided-by: openregister`; endpoints of an external standard MUST live in
`openapi.standards.json` under their unversioned base. `OpenApiDocumentTest`
MUST fail on a documented path without a route and on a versioned route that
is not documented.

Rows: `con-api` (filinq matrix)

#### Scenario: A route added without documentation fails the build

- GIVEN a developer who adds `POST api/documents/{id}/stamp` to `appinfo/routes.php` and not to `openapi.json`
- WHEN the unit tests run
- THEN `OpenApiDocumentTest::testTheDocumentMatchesTheRoutes` fails and names `POST /api/v1/documents/{id}/stamp`
- @e2e exclude a build check; covered by `tests/unit/Middleware/Support/OpenApiDocumentTest.php`

#### Scenario: A documented path without a route fails the build

- GIVEN `openapi.json` documents `/ocs/v2.php/apps/dsonextcloud/api`
- WHEN the unit tests run
- THEN the tests fail and name the foreign path
- @e2e exclude a build check; covered by `tests/unit/OpenApiCoverageTest.php`

### Requirement: The route table and the document are read in full (REQ-COD-002)

A unit test MUST fail when it reads fewer than 150 routes from
`appinfo/routes.php` or fewer than 150 operations from `openapi.json`, so a
parsing fault cannot pass the coverage check on an empty list.

Rows: `con-api` (filinq matrix)

#### Scenario: A broken route reader does not pass silently

- GIVEN a change that makes the route reader return nothing
- WHEN the unit tests run
- THEN `OpenApiCoverageTest::testTheRoutesAreReadInFull` fails
- @e2e exclude a build check; covered by `tests/unit/OpenApiCoverageTest.php`

### Requirement: CI fails when the document is stale (REQ-COD-003)

The code quality workflow MUST run the unit tests of REQ-COD-001 and
REQ-COD-002 on every pull request to `development`, so a route change without
the matching document change fails the PHPUnit job.

Rows: `con-api` (filinq matrix)

#### Scenario: A removed route without an updated document

- GIVEN a pull request that removes `GET api/publications/{id}/download` from `appinfo/routes.php` and leaves it in `openapi.json`
- WHEN the code quality workflow runs
- THEN the PHPUnit job fails on `OpenApiDocumentTest` and names the path
- @e2e exclude a CI job; covered by the unit test the job runs

### Requirement: The docs site shows the same document (REQ-COD-004)

The docs site MUST render `openapi.json`, copied to
`docs/static/oas/filinq-api.json`, at `/api`, and MUST NOT publish a
hand-written second OpenAPI file. A unit test MUST fail when the docs copy
differs from `openapi.json` in anything but the release version. The API docs
page MUST explain authentication with a Nextcloud app password and MUST link
OpenRegister's per-register OpenAPI document for the records filinq stores
there.

Rows: `con-api` (filinq matrix)

#### Scenario: An integrator reads the API docs

- GIVEN an integrator on the filinq docs site
- WHEN the integrator opens `/api`
- THEN `POST /documents/generate` is listed with its parameters, and `/privacy/files` is not
- @e2e exclude docs site, not the app; covered by `OpenApiCoverageTest::testTheDocsSiteRendersTheSameDocument` and the documentation workflow build
