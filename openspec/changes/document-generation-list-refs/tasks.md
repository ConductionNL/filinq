# Tasks: document-generation-list-refs

## 1. Backend

- [x] 1.1 New `lib/Service/ListReferenceResolver.php`: validates + resolves `listRefs` entries against `OCA\OpenRegister\Service\ObjectService::searchObjectsBySlug()` — guardrails (max 10 entries, scalar-only filter values, limit 1-500, `as` pattern + collision check, all fail-fast before any search runs), default `as` = sanitised schema slug + `_list`, per-item search failures collected as soft errors (REQ-DDLR-001, REQ-DDLR-003)

- [x] 1.2 `DataResolverService::resolve()`: new optional `listRefs` parameter, resolved after `dataRefs` and before `adHocData` via `ListReferenceResolver` (lazily constructed, no constructor-injection churn); existing named-argument call sites (`CorrespondenceService`, `DocumentService`) unaffected by the additive parameter (REQ-DDLR-002)

- [x] 1.3 `DocumentService::generateDocument()` / `generatePreview()`: forward `options.listRefs` to `resolve()`; docblocks updated (REQ-DDLR-001)

- [x] 1.4 `DocumentService::generateBulk()` / `generateBulkSync()`: explicitly NOT wired — docblock records why (REQ-DDLR-004)

- [x] 1.5 `DocumentController::generate()` / `preview()`: docblocks document `options.listRefs`; no code change needed beyond docs — `options` was already passed through opaquely to the service layer

## 2. Quality

- [x] 2.1 `@spec` tags on every new/changed method pointing at this change's spec

- [x] 2.2 phpcs / phpstan / psalm / phpmd clean on all 4 changed/new files (run via `docker run --rm -v $PWD:/app -w /app php:8.3-cli php vendor/bin/<tool> ...` — host PHP is 8.2, this repo's composer deps require >=8.3)

## 3. Tests

- [x] 3.1 `tests/unit/Service/ListReferenceResolverTest.php` (new): default/explicit `as`, hyphen-sanitisation, limit/order pass-through as `_limit`/`_order`, all 5 guardrails (max-10, non-scalar filter, limit bounds both directions, `as` pattern, `as` collision — both against reserved keys and between two listRefs), fail-fast-before-any-search, per-item search-failure soft error, empty-listRefs no-op

- [x] 3.2 `tests/unit/Service/DataResolverServiceTest.php` (extended): listRefs alongside dataRefs end-to-end, adHocData-overrides-listRef precedence, listRef `as` colliding with a dataRefs schema key, listRefs-omitted behaves unchanged

- [x] 3.3 `tests/unit/Service/DocumentServiceTest.php` (extended): `generateDocument()` / `generatePreview()` forward `options.listRefs` to `DataResolverService::resolve()` unchanged

- [x] 3.4 `tests/unit/Controller/DocumentControllerTest.php` (extended): `preview()` forwards `options.listRefs` through to `DocumentService::generatePreview()` unchanged

- [x] 3.5 Full `tests/unit` suite run clean (987 tests; only pre-existing, unrelated `PhpWordBackendTest` failure from a missing `ZipArchive` extension in the generic test container — not introduced by this change)

- [x] 3.6 Live verify: `POST /api/documents/generate/preview` with a `listRefs` entry against `spectr-live` / `v-app-competitors` / `filter: {app_id: 6}`, confirmed rendered in a throwaway template's Twig loop, on the docudesk instance actually served on localhost:8080 (if that checkout is this one — see report)

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `dataRefs`/`adHocData` behaviour byte-identical to before this change (additive only)
- No new routes; existing generate/preview routes gain an optional request field
