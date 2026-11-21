# Tasks: office-charts-and-images

## 1. Office fillers (after office-template-authoring 2.3)

- [x] 1.1 `${chart:key}` native DOCX chart via `TemplateProcessor::setChart()` in the office pre-pass, marker plus warning on bad context (REQ-DDTCH-003)
  - PHPUnit: a filled fixture DOCX contains `word/charts/chart1.xml`; `testSetChartCapabilityPinned`
  - Built as `OCA\Filinq\Service\Office\OfficeChartFiller::fill()`, tested in `tests/unit/Service/OfficeTemplateChartTest.php`. The call from the office render path is one line added with office-template-authoring 2.3, which is not on development yet.
- [x] 1.2 `${image:key}` via `TemplateImageResolver` and `TemplateProcessor::setImageValue()` with declared dimensions (REQ-DDTCH-008)
  - PHPUnit: readable PNG lands in `word/media/`; unreadable id leaves the marker and a warning
  - Built as `OCA\Filinq\Service\Office\OfficeImageFiller::fill()`, tested in `tests/unit/Service/OfficeTemplateImageTest.php`; same wiring note as 1.1.
- [x] 1.3 Fixture `tests/sample-documents/chart-template.docx` with `${chart:bezwaren}`, `${image:logo}` and a cloned-row table block

## 2. Docs and e2e

- [x] 2.1 Office section in `docs/features/template-charts.md`: the two placeholders and the row-cloning collection-table recipe
- [ ] 2.2 Extend `tests/e2e/spec-coverage/template-charts.spec.ts` with an office generation that downloads the DOCX and checks the chart part
  - Left open: an office generation needs the office render path (office-template-authoring 2.3), which is not on development, so an e2e generation cannot run yet.
