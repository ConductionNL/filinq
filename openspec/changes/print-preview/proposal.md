## Why

DocuDesk generates PDF documents but provides no browser-accessible preview before download. Users must blindly download a PDF to verify layout, typography, and data substitution — there is no intermediate step to inspect rendered output. Adding a print preview endpoint and a Vue component closes this loop: users can review the rendered document in-browser and choose to print or export to PDF/A-3b archival format without leaving the workflow.

## What Changes

- DocuDesk gains a `PrintController` with `preview()` and `downloadPdfA()` API endpoints
- `POST /api/print/preview` returns rendered HTML with print-optimized CSS injected, plus the document title
- `POST /api/print/pdf-a` returns a `DataDownloadResponse` with PDF/A-3b binary (compliance enforced server-side)
- Both endpoints accept `templateId` (stored template lookup via TemplateService) or `template` (inline content), plus a `data` context object
- DocuDesk gains a `PrintPreview.vue` component that displays rendered HTML in a sandboxed iframe, with Print and Download PDF/A buttons
- The component is accessible at `/print-preview/:templateId?` route, enabling deep linking from other views
- NL Design System CSS variables are used throughout the component for theming compatibility

## Capabilities

### New Capabilities

- `print-preview`: Browser-based document preview rendered in a sandboxed iframe, supporting native browser print dialog and PDF/A-3b archival download. Accepts both stored templates (by UUID) and inline Twig/HTML content with arbitrary JSON data context.

## Impact

### Code Changes

- **`lib/Controller/PrintController.php`**: New controller with `preview()` and `downloadPdfA()` endpoints. Template resolution via injected `TemplateService`; rendering via injected `PdfService`.
- **`src/components/PrintPreview.vue`**: New Vue component — fetches preview HTML from `/api/print/preview`, renders in sandboxed iframe, exposes Print and Download PDF/A actions.
- **`src/router/index.js`**: New `/print-preview/:templateId?` route wired to `PrintPreview.vue`.
- **`appinfo/routes.php`**: New routes `print#preview` (`POST /api/print/preview`) and `print#downloadPdfA` (`POST /api/print/pdf-a`).

### API Changes

- **New**: `POST /api/print/preview` — authenticated (`@NoAdminRequired @NoCSRFRequired`), returns `{"html": "...", "title": "..."}`
- **New**: `POST /api/print/pdf-a` — authenticated (`@NoAdminRequired @NoCSRFRequired`), returns `DataDownloadResponse` with PDF/A-3b binary

### Dependencies

- No new dependencies — builds on `PdfService` and `TemplateService` introduced in the `pdf-template-service` change
