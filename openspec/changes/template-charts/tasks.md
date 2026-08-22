# Tasks: template-charts

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

> **Scope decision (management, this wave):** implement the HTML/PDF path
> only — `chart()` (pure-PHP SVG) + `data_table()`. The native-DOCX/PhpWord
> office path (3.1/3.2) is **descoped**: it depends on the unbuilt
> `office-template-authoring` change (REQ-DDOTA-003 pre-pass slot does not
> exist yet), so there is nothing to hook `setChart()`/`setImageValue()`
> into. `nc_image()` (1.3) is **descoped**: it is not trivial (NC
> `IRootFolder` resolution, per-user ACL enforcement, mime/size validation)
> so it does not meet the "only if trivial" bar and is left for a follow-up
> wave alongside the office path. The SVG→PNG raster fallback (4.1) is
> **descoped** for the same reason — it only matters for HTML→odf/docx
> conversions, which are out of scope this wave (HTML/PDF only).

## 1. Rendering services

- [x] 1.1 `lib/Service/Charts/ChartSvgRenderer`: pure-PHP bar/line/pie SVG (data shape, options, huisstijl-seeded palette with accessible fallback, deterministic output, `max_points` cap, escaped text nodes) (REQ-DDTCH-001)
  - Snapshot/determinism, escaping, palette-fallback, and cap unit tests in `tests/unit/Service/Charts/ChartSvgRendererTest.php` (21 tests). Horizontal-bar orientation and donut are options on `bar`/`pie` respectively (not separate types), per the proposal's bar/line/pie scope.

- [x] 1.2 `TableHtmlRenderer`: collection + `[{key, label, align?, format?}]` columns → styled HTML table; `text|number|date|currency` formatting via explicit options (not environment locale); every cell escaped; localised empty-state row (REQ-DDTCH-004)
  - `tests/unit/Service/Charts/TableHtmlRendererTest.php` (9 tests)

- [ ] 1.3 `TemplateImageResolver` (`nc_image()`) — **descoped this wave**, see scope note above (REQ-DDTCH-006)

## 2. Twig path

- [x] 2.1 Extend `TemplateRenderer::ALLOWED_FUNCTIONS` with `chart`, `data_table` and register the implementations (`is_safe: ['html']`, no writes, no network); extend — do not relax — the sandbox refusal tests (REQ-DDTCH-002/005). `nc_image` intentionally NOT added to the whitelist this wave (descoped, see above).
  - Non-whitelisted functions and object method/property access still refused; existing REQ-PDF-03/07 pins green; `testWhitelistIsExact`/`testObjectMethodCallsStillRefused` in `tests/unit/Service/TemplateRendererTest.php`

- [x] 2.2 Error/warning channel: invalid chart type/data → inline `[chart error: reason]` marker plus a `TemplateRenderer::getLastRenderWarnings()` → `DocumentService` generation-warnings channel; same pattern for `data_table` (empty-state row, never an exception) (REQ-DDTCH-002/006). Additional guardrail (beyond spec): max 20 `chart()` calls per document, degrading extra calls to a placeholder.

## 3. Office path

- [ ] 3.1 `${chart:key}` native DOCX chart — **descoped this wave**, see scope note above (REQ-DDTCH-003)

- [ ] 3.2 `${image:key}` office image placeholder — **descoped this wave**, see scope note above (REQ-DDTCH-004/006)

## 4. Format fallbacks

- [ ] 4.1 HTML→odf/docx SVG raster fallback — **descoped this wave**, see scope note above (REQ-DDTCH-007)

## 5. Quality, i18n, docs

- [x] 5.1 Unit tests (renderer determinism/escaping/empty/malformed data, sandbox whitelist exactness, `TemplateRenderer` render test with `chart()`+`data_table()` in template content): 43 new tests across `ChartSvgRendererTest` (21), `TableHtmlRendererTest` (9), `TemplateRendererTest` additions (13); full suite 1090/1090 green in the `nextcloud:34.0.0-apache` container (host PHP 8.2 too old for this app's `php: ^8.3`)

- [~] 5.2 No standalone seed Twig demo template/docx fixture shipped this wave; the equivalent live proof is the chart-enriched `spectr-app-report` production template (competitors horizontal-bar + TAM/SAM/SOM chart), live-verified via preview + full PDF generation against register `spectr-live` (see report/PR)

- [ ] 5.3 Playwright e2e spec — **not written this wave** (time-boxed); live-verified instead via the documents API (`generate/preview` HTML + `generate` PDF) against real `spectr-live` data, per the task's own live-verify instructions. Follow-up: add `tests/e2e/spec-coverage/template-charts.spec.ts` alongside the office-path wave.

- [ ] 5.4 i18n — chart/table marker strings (`chart error: ...`, `data_table`'s NL empty-state default) are hardcoded, not wired through NC's ADR-005 translation catalogue; `docs/features/template-charts.md` author docs not written this wave. Follow-up alongside the office-path wave.

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `composer check:strict` green for the HTML/PDF-path code (lint/phpcs/phpmd/psalm/phpstan/phpunit all clean or pre-existing-baselined); hydra gates (spdx, spec-coverage) satisfied for new code — no route changes
- No external chart service, no client-side chart JS, no GD/Imagick dependency introduced
- Live-verified against the filinq documents API on the served instance (see PR) using OpenRegister `spectr-live` data, not synthetic-only fixtures
