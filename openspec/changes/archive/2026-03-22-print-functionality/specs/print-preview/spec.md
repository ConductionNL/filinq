## ADDED Requirements

### Requirement: Print preview API endpoint
The system SHALL provide a `POST /api/print/preview` endpoint that accepts a template ID or template content with data context, renders the HTML via `TemplateRenderer`, and returns the rendered HTML string with print-optimized CSS injected. The endpoint MUST require authentication (`@NoAdminRequired @NoCSRFRequired`).

#### Scenario: Preview with template ID
- **WHEN** an authenticated user sends `POST /api/print/preview` with `{"templateId": "<uuid>", "data": {"title": "Report"}}`
- **THEN** the system loads the template from `TemplateService`, renders it with the data context, injects print-optimized CSS, and returns a JSON response with `{"html": "<rendered HTML>", "title": "<template name>"}`

#### Scenario: Preview with inline template content
- **WHEN** an authenticated user sends `POST /api/print/preview` with `{"template": "<h1>{{ title }}</h1>", "data": {"title": "Report"}}`
- **THEN** the system renders the inline template with the data context, injects print-optimized CSS, and returns a JSON response with `{"html": "<rendered HTML>", "title": "document"}`

#### Scenario: Missing template reference
- **WHEN** an authenticated user sends `POST /api/print/preview` with neither `templateId` nor `template` field
- **THEN** the system returns a 400 JSON error response with message "Either templateId or template content is required"

### Requirement: Print-optimized CSS injection
The system SHALL inject a `<style>` block with `@media print` rules into preview HTML output. The print CSS MUST include: page margin normalization, `page-break-inside: avoid` on tables and figures, hidden navigation elements, and `@page` size matching the template format (A4/A3/Letter/Legal).

#### Scenario: A4 portrait template gets print CSS
- **WHEN** a template with format "A4" and orientation "P" is rendered for print preview
- **THEN** the injected CSS includes `@page { size: A4 portrait; }` and standard print optimization rules

#### Scenario: Landscape template gets correct page size
- **WHEN** a template with format "A4" and orientation "L" is rendered for print preview
- **THEN** the injected CSS includes `@page { size: A4 landscape; }`

### Requirement: PrintController handles preview and PDF/A download
The system SHALL provide a `PrintController` with two endpoints: `preview` (returns rendered HTML) and `downloadPdfA` (returns PDF/A binary). Both endpoints MUST delegate to `PdfService` and `TemplateRenderer` respectively.

#### Scenario: PDF/A download from print controller
- **WHEN** an authenticated user sends `POST /api/print/pdf-a` with `{"templateId": "<uuid>", "data": {"title": "Report"}, "filename": "report.pdf"}`
- **THEN** the system loads the template, renders it, generates a PDF/A-3b compliant document via `PdfService` with `pdfa: true`, and returns a `DataDownloadResponse` with the PDF binary

#### Scenario: PDF/A download with inline template
- **WHEN** an authenticated user sends `POST /api/print/pdf-a` with `{"template": "<h1>{{ title }}</h1>", "data": {"title": "Report"}}`
- **THEN** the system renders the inline template and returns a PDF/A-3b compliant PDF with default filename "document.pdf"

### Requirement: Vue PrintPreview component
The system SHALL provide a `PrintPreview.vue` component that fetches rendered HTML from the preview endpoint, displays it in an iframe, and offers two actions: "Print" (triggers `window.print()` on the iframe) and "Download PDF/A" (calls the PDF/A download endpoint).

#### Scenario: User opens print preview
- **WHEN** a user navigates to the print preview for a template with data
- **THEN** the component loads the rendered HTML, displays it in a sandboxed iframe, and shows "Print" and "Download PDF/A" buttons

#### Scenario: User clicks Print button
- **WHEN** the user clicks the "Print" button in the print preview
- **THEN** the browser's native print dialog opens with the iframe content

#### Scenario: User clicks Download PDF/A button
- **WHEN** the user clicks the "Download PDF/A" button
- **THEN** the system calls `POST /api/print/pdf-a` and triggers a file download of the resulting PDF
