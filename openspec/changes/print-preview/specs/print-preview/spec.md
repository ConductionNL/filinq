## Requirements

### PRINT-001: Print preview authenticated endpoint

The system SHALL expose `POST /api/print/preview` as an authenticated endpoint annotated `@NoAdminRequired @NoCSRFRequired`. The endpoint SHALL return a JSON response containing the rendered HTML string and the document title.

#### Scenario: Preview returns HTML and title

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"templateId": "<uuid>", "data": {"title": "Report"}}
THEN the response is 200 JSON with {"html": "<rendered HTML string>", "title": "<template name>"}
```

#### Scenario: Unauthenticated request is rejected

```
GIVEN an unauthenticated request
WHEN POST /api/print/preview with any body
THEN Nextcloud middleware returns 401
```

---

### PRINT-002: Template resolution — templateId and inline template

The system SHALL accept `templateId` (UUID) to load a stored template via `TemplateService`, OR `template` (string) for inline Twig/HTML content. Both forms SHALL accept an optional `data` object as the rendering context.

#### Scenario: Preview with templateId

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"templateId": "<uuid>", "data": {"title": "Report"}}
THEN the system loads the template from TemplateService
AND renders it with the data context
AND injects print-optimized CSS
AND returns {"html": "<rendered HTML>", "title": "<template name>"}
```

#### Scenario: Preview with inline template

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"template": "<h1>{{ title }}</h1>", "data": {"title": "Report"}}
THEN the system renders the inline template with the data context
AND returns {"html": "<rendered HTML>", "title": "document"}
```

#### Scenario: Preview with data omitted

```
GIVEN an authenticated user
WHEN POST /api/print/preview with {"template": "<h1>Hallo</h1>"} and no data field
THEN the system renders the template with an empty data context
AND returns {"html": "<rendered HTML>", "title": "document"}
```

---

### PRINT-003: Missing template reference returns 400

The system SHALL return a 400 JSON error response when a preview request contains neither `templateId` nor `template`.

#### Scenario: Missing template reference

```
GIVEN an authenticated user
WHEN POST /api/print/preview with neither templateId nor template field
THEN the system returns 400 JSON {"message": "Either templateId or template content is required"}
```

---

### PRINT-004: Print-optimized CSS injection

The system SHALL inject print-optimized CSS into the rendered HTML before returning it. The injected CSS SHALL include:

- `@page` size declaration with A4 dimensions and 20mm margins
- `page-break-inside: avoid` on all elements
- Margin normalization on `body`
- `display: none` on `nav`, `header`, `footer`, and `.no-print` elements

#### Scenario: Preview HTML includes print CSS

```
GIVEN an authenticated user
WHEN POST /api/print/preview with a valid template and data
THEN the returned HTML contains an injected <style> block with @page, page-break-inside, and nav hiding rules
AND the HTML is a complete <!DOCTYPE html> document
```

---

### PRINT-010: PDF/A download authenticated endpoint

The system SHALL expose `POST /api/print/pdf-a` as an authenticated endpoint annotated `@NoAdminRequired @NoCSRFRequired`. The endpoint SHALL return a `DataDownloadResponse` containing a PDF/A-3b binary.

#### Scenario: PDF/A download returns binary

```
GIVEN an authenticated user
WHEN POST /api/print/pdf-a with {"templateId": "<uuid>", "data": {"title": "Report"}, "filename": "report.pdf"}
THEN the system loads the template and generates a PDF/A-3b compliant document
AND returns a DataDownloadResponse with filename "report.pdf" and MIME type application/pdf
```

---

### PRINT-011: PDF/A template and filename resolution

The system SHALL accept `templateId` or `template` with `data` for PDF/A generation, identical to the preview endpoint. An optional `filename` parameter SHALL set the download filename; the default SHALL be `document.pdf`.

#### Scenario: PDF/A download with default filename

```
GIVEN an authenticated user
WHEN POST /api/print/pdf-a with {"template": "<h1>{{ name }}</h1>", "data": {"name": "Verslag"}}
THEN the response is a DataDownloadResponse with filename "document.pdf"
```

#### Scenario: PDF/A download with custom filename

```
GIVEN an authenticated user
WHEN POST /api/print/pdf-a with {"templateId": "<uuid>", "data": {}, "filename": "vergunning-2026.pdf"}
THEN the response is a DataDownloadResponse with filename "vergunning-2026.pdf"
```

---

### PRINT-012: PDF/A compliance enforced unconditionally

The system SHALL always pass `pdfa: true` to `PdfService` when generating PDF/A output, regardless of any options present in the request body.

#### Scenario: Request body cannot disable PDF/A

```
GIVEN an authenticated user
WHEN POST /api/print/pdf-a with {"template": "<p>test</p>", "data": {}, "options": {"pdfa": false}}
THEN the generated document is still PDF/A-3b compliant (pdfa: true is enforced server-side)
```

---

### PRINT-020: PrintPreview.vue fetches preview HTML

The `PrintPreview.vue` component SHALL fetch rendered HTML from `POST /api/print/preview` on mount. It SHALL pass `templateId` (from route parameter) and/or a `data` prop to the endpoint. The component SHALL use `@nextcloud/axios` for the API call.

#### Scenario: Component loads preview on mount

```
GIVEN the /print-preview/:templateId? route is active
WHEN the component mounts with a templateId route parameter
THEN it POSTs to /api/print/preview with {"templateId": "<param>", "data": {}}
AND stores the returned HTML for rendering
```

---

### PRINT-021: Rendered HTML displayed in sandboxed iframe

The `PrintPreview.vue` component SHALL render the fetched HTML in a sandboxed `<iframe>` using the `srcdoc` attribute. The iframe SHALL use `sandbox="allow-scripts allow-same-origin"` to enable `window.print()` while preventing external resource loading.

#### Scenario: Preview HTML rendered in iframe

```
GIVEN the component has fetched HTML from /api/print/preview
WHEN the component renders
THEN a sandboxed <iframe srcdoc="..."> element displays the rendered HTML
AND no external resources are loaded from the iframe
```

---

### PRINT-022: Print button triggers browser print dialog

The `PrintPreview.vue` component SHALL expose a "Print" button that calls `window.print()` on the iframe's `contentWindow`. The print dialog SHALL render the iframe content using the injected print-optimized CSS.

#### Scenario: Print button triggers iframe print

```
GIVEN the iframe is loaded with rendered HTML
WHEN the user clicks the Print button
THEN contentWindow.print() is called on the iframe
AND the browser print dialog opens with the rendered document
```

---

### PRINT-023: Download PDF/A button triggers file download

The `PrintPreview.vue` component SHALL expose a "Download PDF/A" button that POSTs to `/api/print/pdf-a` and triggers a file download in the browser using the `filename` from the response headers.

#### Scenario: Download PDF/A button triggers download

```
GIVEN the component is active with a templateId
WHEN the user clicks the Download PDF/A button
THEN the component POSTs to /api/print/pdf-a with the current templateId and data
AND the browser downloads the PDF/A-3b file with the expected filename
```

---

### PRINT-024: Component accessible via route

The `PrintPreview.vue` component SHALL be accessible at `/print-preview/:templateId?` in the Vue router (`src/router/index.js`). The `templateId` parameter SHALL be optional, enabling both parameterized and parameterless access.

#### Scenario: Route with templateId parameter

```
GIVEN the Vue app is running
WHEN the user navigates to /print-preview/bb000001-0001-0001-0001-000000000001
THEN PrintPreview.vue mounts with templateId = "bb000001-0001-0001-0001-000000000001"
AND the component fetches the preview for that template
```

#### Scenario: Route without templateId parameter

```
GIVEN the Vue app is running
WHEN the user navigates to /print-preview (no templateId)
THEN PrintPreview.vue mounts with templateId = undefined
AND the component renders in a state that accepts inline template input or shows an empty state
```

---

### PRINT-025: NL Design System CSS variables for theming

The `PrintPreview.vue` component SHALL use CSS custom properties from the NL Design System token set for all colors, spacing, and typography in the component chrome (toolbar, buttons, loading states). It SHALL NOT use hardcoded hex colors or pixel values for theme-sensitive properties.

#### Scenario: Component respects active NL Design theme

```
GIVEN the nldesign app has activated the Rijkshuisstijl token set
WHEN PrintPreview.vue renders
THEN the component toolbar and buttons use the active theme's color tokens
AND no hardcoded colors override the token values
```

#### Scenario: Component style blocks are scoped

```
GIVEN PrintPreview.vue defines a <style> block
WHEN the component renders alongside other components
THEN all styles are scoped to the component and do not leak to parent or sibling elements
```
