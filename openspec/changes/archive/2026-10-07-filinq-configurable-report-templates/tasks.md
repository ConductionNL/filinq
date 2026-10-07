## 1. Schema and slug resolution

- [x] 1.1 Add `slug` and `tenantId` (both optional string) to the `template` schema in `lib/Settings/filinq_register.json`; verify with `php -l` and `php vendor/bin/phpunit --filter TemplateServiceTest`
- [x] 1.2 Add seed data entries from design.md's Seed Data section to `_registers.json`; verify the file is valid JSON (`php -r "json_decode(file_get_contents('_registers.json')) !== null || exit(1);"` or equivalent)
- [x] 1.3 Add `TemplateService::resolveBySlug(namespace, slug, ?tenantId)`: tenant-specific match first, namespace-wide fallback, 404 exception on no match; verify with new `tests/unit/Service/TemplateServiceSlugResolutionTest.php` covering the three scenarios in specs/template-management/spec.md (REQ-TMPL-13)
- [x] 1.4 Reject duplicate `(namespace, tenantId, slug)` on template create/update with a 400 naming the conflict; verify with a unit test on `TemplateService`

## 2. Render endpoint

- [x] 2.1 Add `ReportRenderService::render(templateSlug, ?tenantId, data, options)` composing `TemplateService::resolveBySlug()` with the existing `DocumentRenderPipeline`/`DocumentService` render-and-store path, returning `{documentRef, templateSlug, renderedAt}`; verify with `tests/unit/Service/ReportRenderServiceTest.php`
- [x] 2.2 Add `outputQuality` option (`screen` default, `print`) routing through the existing `Pdfa3ConversionService` before storage when `print`; verify with a unit test asserting the conversion service is invoked only for `print`
- [x] 2.3 Add `ReportRenderController::render()` for `POST /api/v1/documents/render`: validates `templateSlug` required (400 if missing), calls `ReportRenderService::render()`, maps not-found to 404 and other failures to their exception code; verify with `tests/unit/Controller/ReportRenderControllerTest.php`
- [x] 2.4 Register the route in `appinfo/routes.php` (`api/v1/documents/render`, POST, `#[NoAdminRequired]`); verify `openapi.json`/route list includes it and `vendor/bin/phpcs --standard=phpcs.xml appinfo/routes.php` is clean on the touched lines

## 3. Batch render and ZIP

- [x] 3.1 Add `ReportRenderService::renderBatch(templateSlug, ?tenantId, items, options)`: renders each item via the single-render path, collects successes into a `ZipArchive`, collects per-item failures without aborting the batch, stores the ZIP, returns `{documentRef, count, failures}`; verify with `tests/unit/Service/ReportRenderServiceTest.php` covering the all-success, partial-failure, and empty-items cases
- [x] 3.2 Add `ReportRenderController::renderBatch()` for `POST /api/v1/documents/render/batch` (400 on empty/missing `items`); verify with `tests/unit/Controller/ReportRenderControllerTest.php`
- [x] 3.3 Register the batch route in `appinfo/routes.php` (`api/v1/documents/render/batch`, POST, `#[NoAdminRequired]`); verify the route resolves in a controller test hitting the container

## 4. Spec coverage and wiring

- [x] 4.1 Add `@spec` tags on every new/changed public method pointing at `openspec/changes/filinq-configurable-report-templates/specs/...` per ADR-020; verify `git grep -n "@spec openspec/changes/filinq-configurable-report-templates" lib/` lists every new class/method
- [x] 4.2 Run the diff-scoped gate suite (`php -l`, `vendor/bin/phpcs`, `vendor/bin/phpstan analyse` on touched files, `vendor/bin/phpunit --filter` for touched classes) and fix all NEW findings on touched lines
