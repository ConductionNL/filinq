---
status: reviewed
---

# Print Preview

## Purpose

Provides browser-based print preview and PDF/A download functionality. Users can preview rendered documents in an iframe, trigger the browser's native print dialog, or download a PDF/A-3b archival-compliant document. Supports both stored templates (via TemplateService) and inline template content.

@e2e exclude backend print/PDF-A API plus a thin Vue preview component — server-side rendering and PDF/A generation verified by PHPUnit/API tests; the inline-template preview path is additionally covered by tests/e2e/spec-coverage/print-preview.spec.ts.

## Requirements

### Requirement: Print Preview API (REQ-PRINT-01)

**Priority:** MUST

The `POST /api/print/preview` authenticated endpoint SHALL return JSON with rendered HTML and title, accepting either a stored `templateId` or inline `template` content with a `data` context, and SHALL inject print-optimized CSS.

#### Scenario: Preview with template ID
- GIVEN an authenticated user
- WHEN POST /api/print/preview with `{"templateId": "<uuid>", "data": {"title": "Report"}}`
- THEN the system SHALL load the template from TemplateService
- AND render it with the data context
- AND inject print-optimized CSS (`@page` size, `page-break-inside: avoid`, margin normalization, nav hiding)
- AND return `{"html": "<rendered HTML>", "title": "<template name>"}`

#### Scenario: Preview with inline template
- GIVEN an authenticated user
- WHEN POST /api/print/preview with `{"template": "<h1>{{ title }}</h1>", "data": {"title": "Report"}}`
- THEN the system SHALL render the inline template
- AND return `{"html": "<rendered HTML>", "title": "document"}`

#### Scenario: Missing template reference
- GIVEN an authenticated user
- WHEN POST /api/print/preview with neither templateId nor template field
- THEN the system SHALL return a 400 JSON error with message "Either templateId or template content is required"

### Requirement: PDF/A Download API (REQ-PRINT-02)

**Priority:** MUST

The `POST /api/print/pdf-a` authenticated endpoint SHALL return a `DataDownloadResponse` with a PDF/A-3b binary, accepting `templateId` or `template` with `data` and an optional `filename`, and SHALL always force `pdfa: true`.

#### Scenario: PDF/A download from print controller
- GIVEN an authenticated user
- WHEN POST /api/print/pdf-a with `{"templateId": "<uuid>", "data": {"title": "Report"}, "filename": "report.pdf"}`
- THEN the system SHALL load the template and generate a PDF/A-3b compliant document
- AND return a DataDownloadResponse with filename "report.pdf"

#### Scenario: PDF/A flag forced
- GIVEN a request body that omits or disables the pdfa option
- WHEN POST /api/print/pdf-a is handled
- THEN the system SHALL force `pdfa: true` regardless of the request body options

### Requirement: Vue PrintPreview Component (REQ-PRINT-03)

**Priority:** MUST

The `PrintPreview.vue` component SHALL fetch preview HTML from `/api/print/preview`, display it in a sandboxed iframe, and provide Print and Download PDF/A actions, accessible via the `/print-preview/:templateId?` route and themed via CSS variables.

#### Scenario: Render preview and trigger print
- GIVEN the PrintPreview component is mounted with a template
- WHEN it fetches preview HTML from `/api/print/preview`
- THEN the rendered HTML SHALL be displayed in a sandboxed iframe
- AND the "Print" button SHALL trigger `window.print()` on the iframe content

#### Scenario: Download PDF/A from component
- GIVEN the PrintPreview component is displayed
- WHEN the user clicks "Download PDF/A"
- THEN the component SHALL call `/api/print/pdf-a`
- AND trigger a file download

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PRINT-001 | `POST /api/print/preview` authenticated endpoint (`@NoAdminRequired @NoCSRFRequired`) returns JSON with rendered HTML and title | MUST | Implemented |
| PRINT-002 | Accepts `templateId` to load stored template via TemplateService, or `template` for inline content, with `data` context | MUST | Implemented |
| PRINT-003 | Returns 400 JSON error if neither `templateId` nor `template` provided | MUST | Implemented |
| PRINT-004 | Rendered HTML includes print-optimized CSS with `@page` size, `page-break-inside: avoid`, margin normalization, nav hiding | MUST | Implemented |
| PRINT-010 | `POST /api/print/pdf-a` authenticated endpoint returns `DataDownloadResponse` with PDF/A-3b binary | MUST | Implemented |
| PRINT-011 | Accepts `templateId` or `template` with `data` and optional `filename` (default: document.pdf) | MUST | Implemented |
| PRINT-012 | Always forces `pdfa: true` regardless of request body options | MUST | Implemented |
| PRINT-020 | `PrintPreview.vue` component fetches preview HTML from `/api/print/preview` | MUST | Implemented |
| PRINT-021 | Displays rendered HTML in a sandboxed iframe | MUST | Implemented |
| PRINT-022 | "Print" button triggers `window.print()` on iframe content | MUST | Implemented |
| PRINT-023 | "Download PDF/A" button calls `/api/print/pdf-a` and triggers file download | MUST | Implemented |
| PRINT-024 | Component accessible via `/print-preview/:templateId?` route | MUST | Implemented |
| PRINT-025 | Uses CSS variables for theming compatibility with nldesign | MUST | Implemented |

## Current Implementation Status

- **Fully implemented** with file paths:
  - `lib/Controller/PrintController.php` -- `preview()` and `downloadPdfA()` endpoints with template resolution
  - `src/components/PrintPreview.vue` -- Vue component with iframe preview, Print, and Download PDF/A buttons
  - `src/router/index.js` -- `/print-preview/:templateId?` route
  - `appinfo/routes.php` -- routes for `print#preview` and `print#downloadPdfA`
