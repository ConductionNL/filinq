---
kind: code
depends_on: []
---

# Proposal: connect-openapi-documentation

Matrix row `con-api` in filinq's `openspec/parity/capabilities.json`,
rated partial, built.state built. Written in the OpenSpec pass of 27
September 2026.

## Why

An integrator at a municipality wants to start an anonymisation run from
their own system. Every filinq function has a route, and almost none is
documented, and what is documented does not exist.

- `appinfo/routes.php` holds 162 route entries (`grep -c "'name' =>"`)
  over 42 controllers.
- `openapi.json` at the repository root documents two paths:
  `/api/anonymization/batch/folder` and `/ocs/v2.php/apps/dsonextcloud/api`,
  the second a leftover from another app's template. The release job only
  bumps its version (commit `94fbafff` touches `appinfo/info.xml` and
  `openapi.json` and nothing else).
- `composer.json:44` has a script `"openapi": "generate-spec"`, the command
  of nextcloud/openapi-extractor. The package is in neither
  `composer.json` nor `composer.lock`, and there is no `vendor-bin/`, so
  `composer openapi` fails. The tooling was named and never installed.
- The docs site renders `docs/static/oas/filinq-api.yaml` at `/api`
  (`docs/docusaurus.config.js:85`). That file is hand-written and lists 12
  paths. None of them exists in `appinfo/routes.php`: `/documents`,
  `/privacy/files`, `/parsing/logs`, `/reports`, `/anonymization/logs` and
  `/anonymization/deanonymize` all have zero matches.

An integrator who follows the published documentation calls endpoints
that answer 404.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `con-api` | Reach every document function through a documented REST API. | partial: the routes exist and are reachable; `openapi.json` documents 2 paths, one of them foreign, and the docs site publishes 12 paths that do not exist |

The missing half this change specifies: an OpenAPI document that covers
every route, published with the app, where today `openapi.json` documents
2 of about 150 routes.

### Competitors rated yes

- ValidSign (docs-only, read 2026-09-26): "REST API with Java and .NET
  SDKs covering transactions, templates, documents, senders". Evidence:
  https://www.validsign.eu/resources/open-api and
  https://developers.validsign.eu/docs/sdk/sdk-and-rest-documentation
- Paperless-ngx (source read at v3.2.1): "all functions sit behind a REST
  API with an OpenAPI schema and browsable view", `docs/api.md:1-5`
  "fully-documented REST API". Evidence:
  https://github.com/paperless-ngx/paperless-ngx (`src/paperless/urls.py:273-292`)
- Decos JOIN (docs-only, recorded 2026-04-10): "REST API Platform: Modern
  REST APIs for integration with third-party systems". Evidence:
  `intelligence.competitor_features#25502`

## What changes

- `openapi.json` is generated from the controllers with
  nextcloud/openapi-extractor, installed the way the script already
  expects. Nobody writes it by hand again.
- Every route is either in the document or excluded with a written reason,
  such as the page routes of the app itself. A check fails on a route that
  is neither.
- CI fails when the committed `openapi.json` no longer matches the code.
- The docs site renders the generated document at `/api`. The hand-written
  YAML is deleted. The docs page explains how to authenticate with an app
  password and points to OpenRegister's own OpenAPI document for the
  records filinq stores there, instead of describing them twice.

## Capabilities

### New capabilities

- `openapi-documentation`: a generated, complete and checked OpenAPI
  document for filinq's own routes, published with the app and on the docs
  site.

### Modified capabilities

None.

## Impact

- `vendor-bin/openapi-extractor/composer.json` (new) and `composer.json`
  (the `openapi` script runs the installed binary).
- `lib/Controller/*.php`: `#[OpenAPI]` attributes and typed return
  docblocks on 42 controllers; new `lib/ResponseDefinitions.php` with the
  shared response shapes.
- `openapi.json`: regenerated.
- `.github/workflows/code-quality.yml`: a job that regenerates and diffs.
- New `tests/unit/OpenApiCoverageTest.php`: every route documented or
  excluded.
- `docs/docusaurus.config.js`, `docs/api/openapi.md`; delete
  `docs/static/oas/filinq-api.yaml`.

## Out of scope

- Moving routes to OCS controllers. The paths and answers stay as they
  are; consumers such as shillinq and humaniq call them today.
- Client SDKs.
- An externally authenticated API for callers without a Nextcloud account.
  ADR-091 puts that surface with integriq.
- Documenting OpenRegister's object API. OpenRegister publishes it per
  register at `GET /apps/openregister/api/registers/{id}/oas`.
