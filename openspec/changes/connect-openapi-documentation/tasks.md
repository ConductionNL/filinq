# Tasks: connect-openapi-documentation

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Tooling

- [ ] 1.1 Add `vendor-bin/openapi-extractor/composer.json` requiring `nextcloud/openapi-extractor`, and point the `openapi` script in `composer.json` at its `generate-spec` (D1) (REQ-COD-001). Verify: `composer install && composer openapi` exits 0 on a fresh clone.

## 2. Controllers

- [ ] 2.1 Add `lib/ResponseDefinitions.php` with the shared answer shapes (D3) (REQ-COD-001). Verify: `composer psalm` accepts the types.
- [ ] 2.2 Annotate the document, template, correspondence and generation controllers with `#[OpenAPI]` scopes and typed `@return` docblocks (D2, D3) (REQ-COD-002). Verify: `composer openapi` lists their routes.
- [ ] 2.3 Annotate the anonymisation, consent, policy and dossier controllers (D2, D3) (REQ-COD-002). Verify: `composer openapi` lists their routes.
- [ ] 2.4 Annotate the signing, intake, extraction and remaining controllers, marking page, catch-all and portal-only routes `SCOPE_IGNORE` with a reason (D2) (REQ-COD-002). Verify: `composer openapi` exits 0 and `openapi.json` has no `dsonextcloud` path.

## 3. Checks

- [ ] 3.1 Add `tests/unit/OpenApiCoverageTest.php`: every route documented or ignored, and at least 150 routes read (D4) (REQ-COD-002). Verify: the test fails on a deliberately unannotated route and passes after annotating it.
- [ ] 3.2 Add the regenerate-and-diff job to `.github/workflows/code-quality.yml` (D5) (REQ-COD-003). Verify: one deliberate stale commit on the branch turns the job red, the next commit turns it green; both runs are linked in the PR body.

## 4. Docs

- [ ] 4.1 Point redocusaurus at the generated file copied to `docs/static/oas/filinq-api.json`, delete `docs/static/oas/filinq-api.yaml` (D6) (REQ-COD-004). Verify: the docs build passes and `/api` lists `POST /api/documents/generate`.
- [ ] 4.2 Rewrite `docs/api/openapi.md`: app password authentication, the `/api` link, OpenRegister's register document, and a screenshot of `/api` (ADR-010) (REQ-COD-004). Verify: the docs page renders and its links resolve.
- [ ] 4.3 `@spec` tags on the new test and definitions file, and a CHANGELOG line saying the old YAML listed paths that never existed (this change adds no user-facing strings, so ADR-005 needs none). Verify: `composer check:strict` and `npm run lint` pass.
