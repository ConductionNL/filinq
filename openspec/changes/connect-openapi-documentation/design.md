# Design: connect-openapi-documentation

Kind: code. Install the extractor the script already names, annotate the
controllers, check coverage and drift, and publish the result.

## Context

Read at development `2088cc1f`.

- `appinfo/routes.php`: 162 route entries. The file returns
  `OCA\OpenRegister\AppHost\Routes::standard($extra)` when OpenRegister's
  class exists (:300-302) and otherwise builds the same list itself:
  canonical routes, then `$extra`, then the SPA catch-all (:304-346).
- 42 controllers in `lib/Controller/`, all extending `Controller` or
  `ApiController`, none `OCSController`. They answer `JSONResponse`,
  `DataDownloadResponse` and template responses. No method carries an
  `#[OpenAPI]` attribute and there is no `lib/ResponseDefinitions.php`.
- `composer.json:44` `"openapi": "generate-spec"`; `bamarni/composer-bin-plugin`
  is required (:70) and `post-install-cmd` runs `composer bin all install`
  (:18-21). There is no `vendor-bin/` directory and no
  `nextcloud/openapi-extractor` in `composer.lock`.
- `openapi.json` (OpenAPI 3.0.3) has 2 paths and 1 component schema,
  `OCSMeta`.
- `docs/docusaurus.config.js:81-87` feeds `static/oas/filinq-api.yaml` to
  redocusaurus at `/api`. `docs/api/openapi.md` links the YAML, describes
  "Folders" and "Tags" endpoints filinq does not have, and shows a bearer
  header.
- `.github/workflows/code-quality.yml` calls the shared
  `ConductionNL/.github` quality workflow (:146).
- OpenRegister serves an OpenAPI document per register at
  `GET /api/registers/{id}/oas` (`ro-openregister appinfo/routes.php:1670`).
- The Nextcloud developer manual's OpenAPI tutorial prefers OCS endpoints,
  calls other methods legacy, and names `#[OpenAPI(scope: ...)]` as the way
  a non-OCS route enters the document. The extractor's README advises
  installing it through the composer bin plugin.

New: `vendor-bin/openapi-extractor/`, `lib/ResponseDefinitions.php`,
`OpenApiCoverageTest`, the drift job.

## Goals / Non-goals

Goals: every filinq route documented or deliberately excluded; the
document generated from code; drift caught in CI; the docs site shows the
real API.

Non-goals: OCS migration, SDKs, an external API gateway, re-documenting
OpenRegister objects.

## Decisions

### D1. Generate with the extractor the repo already names

Install `nextcloud/openapi-extractor` in `vendor-bin/openapi-extractor/`
through the bin plugin, and point the `openapi` script at
`vendor-bin/openapi-extractor/vendor/bin/generate-spec`. Alternative
considered: keep a hand-written file. Rejected on the evidence of the
current one: 12 documented paths, none real. Alternative two: a script of
our own that reads `routes.php`. Rejected: it would not read the
parameter and response types the extractor reads from docblocks.

### D2. Attributes decide what is in, and the reason for what is out

Each method meant for callers carries `#[OpenAPI(scope:
OpenAPI::SCOPE_DEFAULT)]`, the admin routes `SCOPE_ADMINISTRATION`. The
SPA page routes, the catch-all and the portal receiver routes that only
the portal calls carry `SCOPE_IGNORE` with a comment giving the reason.
Alternative considered: move to OCS controllers so the extractor picks
routes up by default. Rejected: it changes every path and the answer
envelope that shillinq, humaniq and the frontend rely on.

### D3. Types come from one definitions file

Shared answer shapes (an error, a generated document, an extraction, a
signing request) are psalm types in `lib/ResponseDefinitions.php`, and
each method's `@return` names its status codes and shape. A method whose
answer the extractor cannot type, such as a streamed file, is documented
with its content type and no schema.

### D4. A coverage test that can fail

`tests/unit/OpenApiCoverageTest.php` loads `routes.php` through its
fallback branch, lists every verb and path, and fails on any route that is
neither in `openapi.json` nor marked `SCOPE_IGNORE`. It also fails when it
reads fewer than 150 routes, so an empty read cannot pass. Alternative
considered: trust the generated file. Rejected: a method without an
attribute drops out of the document silently.

### D5. CI regenerates and diffs

A job in `.github/workflows/code-quality.yml` runs `composer openapi` and
`git diff --exit-code openapi.json`. A controller change without a
regenerated file fails with the diff in the log.

### D6. The docs render the generated file

The docs build copies `openapi.json` into `docs/static/oas/filinq-api.json`
and redocusaurus points at it. `docs/static/oas/filinq-api.yaml` is
deleted. `docs/api/openapi.md` is rewritten: authentication with a
Nextcloud app password, the link to `/api`, and the link to OpenRegister's
register document for stored records.

## Declarative-vs-imperative decision (ADR-031)

Not applicable: no lifecycle, aggregation, derived field, notification,
relation or widget is touched.

## Risks / trade-offs

- The extractor reads `routes.php` without OpenRegister loaded. The file's
  fallback branch returns the same list; D4 proves it by count.
- The extractor rejects a docblock it cannot parse, which stops the job.
  The first run fixes all 42 controllers in one pass; later drift is one
  method at a time.
- Annotating 42 controllers is a large diff. It touches docblocks and
  attributes only, no behaviour, and lands in one pass per controller
  group so each review stays readable.

## Open questions

- Should the published document leave out routes that only filinq's own
  frontend calls? This change documents them; they are reachable with an
  app password all the same.
