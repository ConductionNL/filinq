# Tasks: pdf-generation

## Task 1: PDF Service
- [x] Implement `PdfService::renderPdf()` with Twig rendering and mPDF conversion
- [x] Support page format options (A4, A3, Letter, Legal)
- [x] Support orientation options (Portrait, Landscape)
- [x] Handle custom margins and title metadata

## Task 2: Template Renderer
- [x] Implement `TemplateRenderer` with Twig sandbox
- [x] Configure strict security policy
- [x] Handle invalid Twig syntax with descriptive errors

## Task 3: PDF API Endpoint
- [x] Implement `POST /api/pdf/render`
- [x] Accept template, data, options, filename in JSON body
- [x] Return `DataDownloadResponse` with application/pdf content type

## Task 4: Unit Tests (ADR-009)
- [x] Test PDF rendering with template and data
- [x] Test static HTML without data
- [x] Test invalid Twig syntax error handling

## Task 5: Documentation (ADR-010)
- [x] Write feature documentation at `docs/features/pdf-generation.md`

## Task 6: i18n (ADR-005)
- [x] No user-facing strings (API-only service)
