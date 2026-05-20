## 1. Routes and Controller Registration

- [x] 1.1 Add `print#preview` route to `appinfo/routes.php`: `POST /api/print/preview`
- [x] 1.2 Add `print#downloadPdfA` route to `appinfo/routes.php`: `POST /api/print/pdf-a`

## 2. PrintController — Preview Endpoint (PRINT-001 to PRINT-004)

- [x] 2.1 Create `lib/Controller/PrintController.php` with constructor injecting `TemplateService`, `PdfService`, `IRequest`, and `IUserSession`
- [x] 2.2 Implement `preview()` method annotated `@NoAdminRequired @NoCSRFRequired`
- [x] 2.3 Read `templateId`, `template`, and `data` from the JSON request body
- [x] 2.4 Return 400 `JSONResponse` with `{"message": "Either templateId or template content is required"}` when neither field is present
- [x] 2.5 When `templateId` is provided: resolve the template via `TemplateService::getTemplate($id)` and use `template['name']` as the title; catch not-found exception and return 404 `JSONResponse`
- [x] 2.6 When inline `template` is provided: use the content directly; set title to `"document"`
- [x] 2.7 Render the template using `PdfService` (or Twig directly) with the `data` context to produce HTML
- [x] 2.8 Inject print-optimized CSS into the rendered HTML: `@page { size: A4; margin: 20mm; }`, `page-break-inside: avoid`, body margin normalization, `nav, header, footer, .no-print { display: none !important; }`
- [x] 2.9 Wrap HTML in a complete `<!DOCTYPE html>` document with injected `<style>` block
- [x] 2.10 Return 200 `JSONResponse` with `{"html": "<full HTML string>", "title": "<resolved title>"}`

## 3. PrintController — PDF/A Download Endpoint (PRINT-010 to PRINT-012)

- [x] 3.1 Implement `downloadPdfA()` method on `PrintController` annotated `@NoAdminRequired @NoCSRFRequired`
- [x] 3.2 Read `templateId`, `template`, `data`, and optional `filename` (default `"document.pdf"`) from the JSON request body
- [x] 3.3 Resolve template content using the same logic as `preview()` (templateId → TemplateService, inline → direct use)
- [x] 3.4 Call `PdfService::renderPdf($templateContent, $data, ['pdfa' => true])` — always enforce `pdfa: true` regardless of any options in the request body
- [x] 3.5 Return `DataDownloadResponse` with the PDF binary, resolved filename, and MIME type `application/pdf`

## 4. PrintPreview.vue Component (PRINT-020 to PRINT-025)

- [x] 4.1 Create `src/components/PrintPreview.vue` with SPDX header `<!-- SPDX-License-Identifier: EUPL-1.2 -->`
- [x] 4.2 Add `templateId` as an optional route parameter prop (from `$route.params.templateId`)
- [x] 4.3 On `mounted()`: POST to `/api/print/preview` via `@nextcloud/axios` with `{ templateId, data: {} }` (or `{ template, data }` for inline mode); store returned `html` and `title`
- [x] 4.4 Render returned HTML in a `<iframe :srcdoc="previewHtml" sandbox="allow-scripts allow-same-origin">` element
- [x] 4.5 Implement "Print" button that calls `this.$refs.previewFrame.contentWindow.print()`
- [x] 4.6 Implement "Download PDF/A" button that POSTs to `/api/print/pdf-a` via `@nextcloud/axios` and triggers a browser file download using a temporary anchor element with `URL.createObjectURL`
- [x] 4.7 Use NL Design System CSS custom properties for all theme-sensitive styles in `<style scoped>`; no hardcoded hex colors or pixel values for color or typography
- [x] 4.8 Wrap all `await axios.post()` calls in `try/catch` with user-visible error feedback (do NOT use `window.alert()` — use `NcDialog` or inline error state)

## 5. Vue Router (PRINT-024)

- [x] 5.1 Add `/print-preview/:templateId?` route to `src/router/index.js` pointing to `PrintPreview.vue`

## 6. Code Quality and Compliance

- [x] 6.1 Add `@spec openspec/changes/print-preview/tasks.md` PHPDoc tag to `PrintController.php` class docblock and each public method
- [x] 6.2 Verify all new PHP files have `// SPDX-License-Identifier: EUPL-1.2` after `<?php`
- [x] 6.3 Verify `PrintController` uses constructor injection (`private readonly`) — no `\OC::$server` or static locators
- [x] 6.4 Verify error responses use static messages only — never `$e->getMessage()` in `JSONResponse`
- [x] 6.5 Verify `PrintPreview.vue` imports components from `@conduction/nextcloud-vue` (not `@nextcloud/vue` directly)
- [x] 6.6 Run `npm run build` to verify frontend compiles without errors

## 7. Verification

- [x] 7.1 Test `POST /api/print/preview` with `templateId` — verify HTML contains injected print CSS and title matches template name
- [x] 7.2 Test `POST /api/print/preview` with inline `template` content — verify title is `"document"`
- [x] 7.3 Test `POST /api/print/preview` with neither field — verify 400 response with correct message
- [x] 7.4 Test `POST /api/print/pdf-a` with `templateId` and `filename` — verify PDF/A-3b download with correct filename
- [x] 7.5 Test that passing `"options": {"pdfa": false}` to `/api/print/pdf-a` still produces a PDF/A-3b document
- [x] 7.6 Test `PrintPreview.vue` in browser at `/print-preview/<uuid>` — verify iframe renders, Print button opens dialog, Download PDF/A button triggers download
- [x] 7.7 Test `/print-preview` (no templateId) — verify component mounts without error
- [x] 7.8 Run PHPCS and PHPMD on `lib/Controller/PrintController.php`
