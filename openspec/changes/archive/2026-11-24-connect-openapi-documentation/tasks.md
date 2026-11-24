# Tasks: connect-openapi-documentation

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Tooling

- [x] 1.1 Add `vendor-bin/openapi-extractor/composer.json` requiring `nextcloud/openapi-extractor`, and point the `openapi` script in `composer.json` at its `generate-spec` (D1) (REQ-COD-001). Verify: `composer install && composer openapi` exits 0 on a fresh clone.
  - Not built (2026-11-17). Since this change was written, `openapi.json` became a maintained document of 229 paths (feat(api) 4a93b3dd, api-contract spec: `/api/v1/` paths, problem details, `API-Version` and `Deprecation` headers, tags), served at `GET /api/v1/openapi.json` and kept in step with `appinfo/routes.php` both ways by `OpenApiDocumentTest`. nextcloud/openapi-extractor 1.9.1 also stops on `<licence>EUPL-1.2</licence>` (it accepts only Nextcloud's own licences), and it documents a non-OCS route only once every method carries `#[OpenAPI]` and a typed `@return`. Switching to it now would replace the richer document with a thinner one; that is a decision for the api-contract spec, not this change.
  - Superseded (Decision, 24 Nov 2026): the document stays maintained, not generated; see the Decision in the delta spec. REQ-COD-001 now asks for the two-way match with the routes, which `OpenApiDocumentTest` pins.

## 2. Controllers

- [x] 2.1 Add `lib/ResponseDefinitions.php` with the shared answer shapes (D3) (REQ-COD-001). Verify: `composer psalm` accepts the types.
  - Superseded (Decision, 24 Nov 2026): no extractor, so no response definitions to feed it; the shapes live in `openapi.json` components.
- [x] 2.2 Annotate the document, template, correspondence and generation controllers with `#[OpenAPI]` scopes and typed `@return` docblocks (D2, D3) (REQ-COD-002). Verify: `composer openapi` lists their routes.
  - Superseded (Decision, 24 Nov 2026): every route is documented in `openapi.json`; no `#[OpenAPI]` attributes needed.
- [x] 2.3 Annotate the anonymisation, consent, policy and dossier controllers (D2, D3) (REQ-COD-002). Verify: `composer openapi` lists their routes.
  - Superseded (Decision, 24 Nov 2026): as 2.2.
- [x] 2.4 Annotate the signing, intake, extraction and remaining controllers, marking page, catch-all and portal-only routes `SCOPE_IGNORE` with a reason (D2) (REQ-COD-002). Verify: `composer openapi` exits 0 and `openapi.json` has no `dsonextcloud` path.
  - 2.1 to 2.4 not built: they exist to feed the extractor of 1.1. Every route is already documented in `openapi.json` and pinned by `OpenApiDocumentTest`; no `dsonextcloud` path remains (checked by `OpenApiCoverageTest`).
  - Superseded (Decision, 24 Nov 2026): as 2.2; no `dsonextcloud` path remains (`OpenApiCoverageTest`).

## 3. Checks

- [x] 3.1 Add `tests/unit/OpenApiCoverageTest.php`: every route documented or ignored, and at least 150 routes read (D4) (REQ-COD-002). Verify: the test fails on a deliberately unannotated route and passes after annotating it.
  - Built 2026-11-17 next to `OpenApiDocumentTest`, which already fails on a route that is neither documented nor a standard protocol path (and on a documented path without a route). `OpenApiCoverageTest` adds the 150-route floor on `routes.php` and on the document, no foreign path, and the docs copy equal to `openapi.json`; control: removing one path from the docs copy fails it.
- [x] 3.2 Add the regenerate-and-diff job to `.github/workflows/code-quality.yml` (D5) (REQ-COD-003). Verify: one deliberate stale commit on the branch turns the job red, the next commit turns it green; both runs are linked in the PR body.
  - Not built: CI is not run on this branch, and without the extractor there is no generator to regenerate and diff. Route drift is caught by the unit tests above.
  - Superseded (Decision, 24 Nov 2026): there is no generator to diff; REQ-COD-003 now rests on the PHPUnit job of `code-quality.yml` (`enable-phpunit: true`), which runs `OpenApiDocumentTest` and `OpenApiCoverageTest` on every pull request.

## 4. Docs

- [x] 4.1 Point redocusaurus at the generated file copied to `docs/static/oas/filinq-api.json`, delete `docs/static/oas/filinq-api.yaml` (D6) (REQ-COD-004). Verify: the docs build passes and `/api` lists `POST /api/documents/generate`.
- [x] 4.2 Rewrite `docs/api/openapi.md`: app password authentication, the `/api` link, OpenRegister's register document, and a screenshot of `/api` (ADR-010) (REQ-COD-004). Verify: the docs page renders and its links resolve.
  - Text rewritten 2026-11-17; the screenshot of `/api` is not taken (needs the built site). `npm run prebuild`/`prestart` in `docs/` copy `openapi.json` into `docs/static/oas/filinq-api.json`.
- [x] 4.3 `@spec` tags on the new test and definitions file, and a CHANGELOG line saying the old YAML listed paths that never existed (this change adds no user-facing strings, so ADR-005 needs none). Verify: `composer check:strict` and `npm run lint` pass.
  - No definitions file exists (2.1 not built); the test carries the tag. phpcs and the two OpenAPI tests pass; the full suite is run before push.
