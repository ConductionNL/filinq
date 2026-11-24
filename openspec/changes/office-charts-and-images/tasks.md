# Tasks: office-charts-and-images

## 1. Office fillers (after office-template-authoring 2.3)

- [x] 1.1 `${chart:key}` native DOCX chart via `TemplateProcessor::setChart()` in the office pre-pass, marker plus warning on bad context (REQ-DDTCH-003)
  - PHPUnit: a filled fixture DOCX contains `word/charts/chart1.xml`; `testSetChartCapabilityPinned`
  - Built as `OCA\Filinq\Service\Office\OfficeChartFiller::fill()`, tested in `tests/unit/Service/OfficeTemplateChartTest.php`. Wired (2026-11-21): `OfficeTemplateFiller::fill()` places the charts (data entry `charts`) and images (data entry `images`) before the plain tags; pinned by `tests/unit/Service/OfficeTemplate/OfficeTemplateFillerChartsTest.php`.
- [x] 1.2 `${image:key}` via `TemplateImageResolver` and `TemplateProcessor::setImageValue()` with declared dimensions (REQ-DDTCH-008)
  - PHPUnit: readable PNG lands in `word/media/`; unreadable id leaves the marker and a warning
  - Built as `OCA\Filinq\Service\Office\OfficeImageFiller::fill()`, tested in `tests/unit/Service/OfficeTemplateImageTest.php`; wired with 1.1.
- [x] 1.3 Fixture `tests/sample-documents/chart-template.docx` with `${chart:bezwaren}`, `${image:logo}` and a cloned-row table block

## 2. Docs and e2e

- [x] 2.1 Office section in `docs/features/template-charts.md`: the two placeholders and the row-cloning collection-table recipe
- [ ] 2.2 Extend `tests/e2e/spec-coverage/template-charts.spec.ts` with an office generation that downloads the DOCX and checks the chart part
  - Written (2026-11-21), not run: the office generation in `tests/e2e/spec-coverage/template-charts.spec.ts` uploads `chart-template.docx`, generates a DOCX with a chart and a readable image, and checks `word/charts/chart1.xml`, `word/media/` and the filled tags. Stays open until it has run against a live instance.
