# Design: PDF Generation

## Architecture

### Backend
- `PdfController::render()` provides `POST /api/pdf/render` endpoint
- `PdfService::renderPdf()` is the core service (injectable via DI)
- `TemplateRenderer` renders Twig templates in a sandboxed environment
- mPDF library handles HTML-to-PDF conversion

### Service Interface
```php
PdfService::renderPdf(string $templateContent, array $data = [], array $options = []): string
```
- Stateless: callers provide template content directly (no template lookup)
- Returns PDF binary content
- Options: format (A4/A3/Letter/Legal), orientation (P/L), margin, title

### Security
- Twig sandbox with strict security policy
- No file system access from templates
- Template rendering isolated from PDF generation

## ADR Compliance
- ADR-001: No data storage (stateless service)
- ADR-008: Controller -> PdfService -> TemplateRenderer layering
