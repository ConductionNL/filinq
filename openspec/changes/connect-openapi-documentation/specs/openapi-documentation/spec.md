# openapi-documentation Specification (delta)

## Purpose

filinq publishes a generated OpenAPI document that covers every route it
serves, checked in CI and shown on the docs site. Matrix row `con-api`
(filinq).

## ADDED Requirements

### Requirement: The OpenAPI document is generated from the code (REQ-COD-001)

`openapi.json` at the repository root MUST be produced by
nextcloud/openapi-extractor from the controllers' attributes and
docblocks. After `composer install`, `composer openapi` MUST regenerate it
without error. The document MUST NOT contain a path filinq does not serve.

Rows: `con-api` (filinq matrix)

#### Scenario: A developer regenerates the document

- GIVEN a fresh clone after `composer install`
- WHEN a developer runs `composer openapi`
- THEN `openapi.json` is rewritten, `git diff` shows no change, and it has no `/ocs/v2.php/apps/dsonextcloud/api` path
- @e2e exclude build tooling, no browser surface; covered by the CI job of REQ-COD-003

### Requirement: Every route is documented or excluded with a reason (REQ-COD-002)

Every verb and path in `appinfo/routes.php` MUST either appear in
`openapi.json` or be marked `#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]`
with a comment giving the reason. A unit test MUST list the routes, MUST
fail on a route that is neither, and MUST fail when it reads fewer than
150 routes.

Rows: `con-api` (filinq matrix)

#### Scenario: A new route without documentation fails the build

- GIVEN a developer who adds `POST api/documents/{id}/stamp` with no `#[OpenAPI]` attribute
- WHEN the unit tests run
- THEN `OpenApiCoverageTest` fails and names `POST /api/documents/{id}/stamp`
- @e2e exclude a build check; covered by `tests/unit/OpenApiCoverageTest.php`

### Requirement: CI fails when the document is stale (REQ-COD-003)

The code quality workflow MUST regenerate `openapi.json` and MUST fail
when it differs from the committed file, showing the difference.

Rows: `con-api` (filinq matrix)

#### Scenario: A changed parameter without a regenerated file

- GIVEN a pull request that renames a request parameter of `POST api/extraction/financial` and does not regenerate `openapi.json`
- WHEN the code quality workflow runs
- THEN the OpenAPI job fails and its log shows the changed parameter
- @e2e exclude a CI job; verified once by a deliberate stale commit on the branch before merge

### Requirement: The docs site shows the generated document (REQ-COD-004)

The docs site MUST render the generated `openapi.json` at `/api` and MUST
NOT publish a hand-written OpenAPI file. The API docs page MUST explain
authentication with a Nextcloud app password and MUST link OpenRegister's
per-register OpenAPI document for the records filinq stores there.

Rows: `con-api` (filinq matrix)

#### Scenario: An integrator reads the API docs

- GIVEN an integrator on the filinq docs site
- WHEN the integrator opens `/api`
- THEN `POST /api/extraction/financial` and `POST /api/documents/generate` are listed with their parameters, and `/privacy/files` is not
- @e2e exclude docs site, not the app; covered by the documentation workflow build and a link check on `/api`
