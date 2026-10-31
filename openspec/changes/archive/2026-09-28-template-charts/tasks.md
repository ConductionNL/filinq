# Tasks: template-charts

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

> **Scope (28 Sep 2026, build-all).** The Twig path is complete: `chart()`,
> `data_table()`, `nc_image()`, the ODT and DOCX raster fallback, translated
> markers and the e2e spec. The office path (3.1, 3.2 and the row-cloning
> recipe) moved to the `office-charts-and-images` change, because it fills
> templates inside the office pipeline that office-template-authoring 2.3 has
> not built yet.

## 1. Rendering services

- [x] 1.1 `lib/Service/Charts/ChartSvgRenderer`: pure-PHP bar/line/pie SVG (data shape, options, huisstijl-seeded palette with accessible fallback, deterministic output, `max_points` cap, escaped text nodes) (REQ-DDTCH-001)
  - Snapshot/determinism, escaping, palette-fallback, and cap unit tests in `tests/unit/Service/Charts/ChartSvgRendererTest.php` (21 tests). Horizontal-bar orientation and donut are options on `bar`/`pie` respectively (not separate types), per the proposal's bar/line/pie scope.

- [x] 1.2 `TableHtmlRenderer`: collection + `[{key, label, align?, format?}]` columns → styled HTML table; `text|number|date|currency` formatting via explicit options (not environment locale); every cell escaped; localised empty-state row (REQ-DDTCH-004)
  - `tests/unit/Service/Charts/TableHtmlRendererTest.php` (9 tests)

- [x] 1.3 `TemplateImageResolver` (`nc_image()`): reads as the signed-in user through the user folder, raster by content sniffing, size cap `templates.max_image_bytes`, one reason for missing and forbidden (REQ-DDTCH-006)
  - `tests/unit/Service/Charts/TemplateImageResolverTest.php` (7 tests); `TemplateRendererTest::testNcImage*` (3 tests)

## 2. Twig path

- [x] 2.1 Extend `TemplateRenderer::ALLOWED_FUNCTIONS` with `chart`, `data_table` and register the implementations (`is_safe: ['html']`, no writes, no network); extend — do not relax — the sandbox refusal tests (REQ-DDTCH-002/005). `nc_image` intentionally NOT added to the whitelist this wave (descoped, see above).
  - Non-whitelisted functions and object method/property access still refused; existing REQ-PDF-03/07 pins green; `testWhitelistIsExact`/`testObjectMethodCallsStillRefused` in `tests/unit/Service/TemplateRendererTest.php`

- [x] 2.2 Error/warning channel: invalid chart type/data → inline `[chart error: reason]` marker plus a `TemplateRenderer::getLastRenderWarnings()` → `DocumentService` generation-warnings channel; same pattern for `data_table` (empty-state row, never an exception) (REQ-DDTCH-002/006). Additional guardrail (beyond spec): max 20 `chart()` calls per document, degrading extra calls to a placeholder.

## 3. Office path

- [x] 3.1 `${chart:key}` native DOCX chart: moved to `office-charts-and-images` task 1.1 (needs the office render path)

- [x] 3.2 `${image:key}` office image placeholder: moved to `office-charts-and-images` task 1.2

## 4. Format fallbacks

- [x] 4.1 HTML to ODT (documents) and HTML to DOCX (letters) SVG raster fallback: `SvgRasterizer` with the shared soffice lock and a private profile; marker plus warning naming the format on failure (REQ-DDTCH-007)
  - `tests/unit/Service/Charts/SvgRasterizerTest.php` (5 tests), `DocumentRenderPipelineOdfTest` (2), `CorrespondenceServiceTest::testDocxOutputRasterizesChartsBeforeConversion`; one run against the real soffice turned a pie chart into a PNG

## 5. Quality, i18n, docs

- [x] 5.1 Unit tests (renderer determinism/escaping/empty/malformed data, sandbox whitelist exactness, `TemplateRenderer` render test with `chart()`+`data_table()` in template content): 43 new tests across `ChartSvgRendererTest` (21), `TableHtmlRendererTest` (9), `TemplateRendererTest` additions (13); full suite 1090/1090 green in the `nextcloud:34.0.0-apache` container (host PHP 8.2 too old for this app's `php: ^8.3`)

- [x] 5.2 No seed demo template: the e2e spec builds its own content through the preview endpoint, which is what an author does
- [x] 5.3 Playwright e2e `tests/e2e/spec-coverage/template-charts.spec.ts` (5 tests over the template preview endpoint: bar chart, malformed data marker, formatted table, readable image, unreachable image)

- [x] 5.4 i18n: every marker and the empty-table row go through IL10N, in en, nl, de, es, fr and it; author docs in `docs/features/template-charts.md`

## Quality checklist

- `composer check:strict` green for the HTML/PDF-path code (lint/phpcs/phpmd/psalm/phpstan/phpunit all clean or pre-existing-baselined); hydra gates (spdx, spec-coverage) satisfied for new code — no route changes
- No external chart service, no client-side chart JS, no GD/Imagick dependency introduced
- Live-verified against the filinq documents API on the served instance (see PR) using OpenRegister `spectr-live` data, not synthetic-only fixtures
