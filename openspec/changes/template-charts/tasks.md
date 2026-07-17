# Tasks: template-charts

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Rendering services

- [ ] 1.1 `lib/Service/Charts/ChartSvgRenderer`: pure-PHP bar/line/pie SVG (data shape, options, huisstijl-seeded palette with accessible fallback, deterministic output, `max_points` cap, escaped text nodes) (REQ-DDTCH-001)
  - Snapshot unit tests pin the emitted SVG subset (shapes/paths/text/solid fills only)

- [ ] 1.2 `TableHtmlRenderer`: collection + `[{key, label, align?, format?}]` columns → styled HTML table; `text|number|date|currency` formatting via explicit options (not environment locale); every cell escaped; localised empty-state row (REQ-DDTCH-004)

- [ ] 1.3 `TemplateImageResolver`: NC file resolution as the generating user via `IRootFolder`, raster-only mime whitelist (png/jpeg/gif/webp — SVG files rejected), `docudesk.templates.max_image_bytes` cap (default 5 MB), data-URI and temp-path outputs, `[image unavailable: reason]` marker + warning on any failure (REQ-DDTCH-006)

## 2. Twig path

- [ ] 2.1 Extend `TemplateRenderer::ALLOWED_FUNCTIONS` with exactly `chart`, `data_table`, `nc_image` and register the implementations (`is_safe: ['html']`, no writes, no network); extend — do not relax — the sandbox refusal tests (REQ-DDTCH-002/005)
  - Non-whitelisted functions and object method/property access still refused; all existing REQ-PDF-03/07 pins green

- [ ] 2.2 Error/warning channel: invalid chart type/data → inline `[chart error: reason]` marker plus a `DocumentService` generation warning (DCS-014 style); same pattern for table/image failures (REQ-DDTCH-002/006)

## 3. Office path

- [ ] 3.1 `${chart:key}` in the office fill pipeline (REQ-DDOTA-003 pre-pass slot): context `charts.key = {type, data, options}` → `PhpWord\Element\Chart` + `TemplateProcessor::setChart()` native DOCX chart; marker + warning on malformed/unsupported input; capability pin test asserting `setChart` exists on the shipped PhpWord (REQ-DDTCH-003)

- [ ] 3.2 `${image:key}` via `TemplateImageResolver` temp path + `TemplateProcessor::setImageValue()` with declared dimensions; document the row-cloning collection-table recipe (no new office table mechanism) (REQ-DDTCH-004/006)

## 4. Format fallbacks

- [ ] 4.1 HTML→odf/docx conversions: substitute chart SVG with PNG rasterised locally via `LibreOfficeHeadlessBackend` (`--convert-to png`, existing serialization lock) before conversion; marker + per-format warning when rasterisation is unavailable — never a silent gap (REQ-DDTCH-007)

## 5. Quality, i18n, docs

- [ ] 5.1 Unit tests (≥75% coverage on new code): renderer snapshots (all three types), palette fallback, point/size caps, table formatting/escaping, image ACL/mime/size failures, sandbox whitelist exactness, office placeholder fills, raster fallback; run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
  - Fixtures per design.md Seed Data (`chart-template.docx`, chart JSON + SVG snapshots)

- [ ] 5.2 Ship the seed Twig demo template ("Demostad handhavingsrapportage") and `tests/sample-documents/chart-template.docx`; both render via preview on a clean install

- [ ] 5.3 Playwright e2e `tests/e2e/spec-coverage/template-charts.spec.ts`: Twig template with bar+pie chart and data_table over seeded Demostad dossier data → HTML preview shows the SVG, PDF download succeeds; office chart template → generated DOCX/PDF; unreadable image shows the marker; verify on Postgres (8080), test with nldesign theme enabled

- [ ] 5.4 i18n (English source keys + NL translations for markers/empty states, ADR-005); template-author docs in `docs/features/template-charts.md` (function reference, office placeholders, data shapes, format-fallback table) with Playwright MCP screenshots (ADR-010); `openspec validate template-charts --strict` passes

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `composer check:strict` green; hydra gates (spdx, spec-coverage, manifest-validation) pass — no route changes expected
- No external chart service, no client-side chart JS, no GD/Imagick dependency introduced
- End-to-end verified against OpenRegister on the Postgres dev instance, not SQLite
