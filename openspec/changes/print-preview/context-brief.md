---
status: reviewed
---

# Print Preview

## Purpose

Provides browser-based print preview and PDF/A download functionality. Users can preview rendered documents in an iframe, trigger the browser's native print dialog, or download a PDF/A-3b archival-compliant document. Supports both stored templates (via TemplateService) and inline template content.

## Requirements

### Print Preview API

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PRINT-001 | `POST /api/print/preview` authenticated endpoint (`@NoAdminRequired @NoCSRFRequired`) returns JSON with rendered HTML and title | MUST | Implemented |
| PRINT-002 | Accepts `templateId` to load stored template via TemplateService, or `template` for inline content, with `data` context | MUST | Implemented |
| PRINT-003 | Returns 400 JSON error if neither `templateId` nor `template` provided | MUST | Implemented |
| PRINT-004 | Rendered HTML includes print-optimized CSS with `@page` size, `page-break-inside: avoid`, margin normalization, nav hiding | MUST | Implemented |

### PDF/A Download API

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PRINT-010 | `POST /api/print/pdf-a` authenticated endpoint returns `DataDownloadResponse` with PDF/A-3b binary | MUST | Implemented |
| PRINT-011 | Accepts `templateId` or `template` with `data` and optional `filename` (default: document.pdf) | MUST | Implemented |
| PRINT-012 | Always forces `pdfa: true` regardless of request body options | MUST | Implemented |

### Vue PrintPreview Component

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PRINT-020 | `PrintPreview.vue` component fetches preview HTML from `/api/print/preview` | MUST | Implemented |
| PRINT-021 | Displays rendered HTML in a sandboxed iframe | MUST | Implemented |
| PRINT-022 | "Print" button triggers `window.print()` on iframe content | MUST | Implemented |
| PRINT-023 | "Download PDF/A" button calls `/api/print/pdf-a` and triggers file download | MUST | Implemented |
| PRINT-024 | Component accessible via `/print-preview/:templateId?` route | MUST | Implemented |
| PRINT-025 | Uses CSS variables for theming compatibility with nldesign | MUST | Implemented |

## Scenarios

### Preview with template ID

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"templateId": "<uuid>", "data": {"title": "Report"}}
THEN the system loads the template from TemplateService
AND renders it with the data context
AND injects print-optimized CSS
AND returns {"html": "<rendered HTML>", "title": "<template name>"}
```

### Preview with inline template

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"template": "<h1>{{ title }}</h1>", "data": {"title": "Report"}}
THEN the system renders the inline template
AND returns {"html": "<rendered HTML>", "title": "document"}
```

### Missing template reference

```
GIVEN an authenticated user
WHEN POST /api/print/preview with neither templateId nor template field
THEN the system returns a 400 JSON error with message "Either templateId or template content is required"
```

### PDF/A download from print controller

```
GIVEN an authenticated user
WHEN POST /api/print/pdf-a with {"templateId": "<uuid>", "data": {"title": "Report"}, "filename": "report.pdf"}
THEN the system loads the template and generates a PDF/A-3b compliant document
AND returns a DataDownloadResponse with filename "report.pdf"
```

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Controller/PrintController.php` -- `preview()` and `downloadPdfA()` endpoints with template resolution
  - `src/components/PrintPreview.vue` -- Vue component with iframe preview, Print, and Download PDF/A buttons
  - `src/router/index.js` -- `/print-preview/:templateId?` route
  - `appinfo/routes.php` -- routes for `print#preview` and `print#downloadPdfA`
