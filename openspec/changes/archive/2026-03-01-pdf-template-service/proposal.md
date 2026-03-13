## Why

PDF generation and template management are currently embedded in LarpingApp's `CharacterService` and `CharactersController`, tightly coupled to character-specific logic. This means no other Nextcloud app can generate PDFs without duplicating the mPDF + Twig stack. DocuDesk is the document processing app in the ecosystem — moving PDF rendering here creates a shared, reusable service that any app (LarpingApp, OpenCatalogi, Procest, Pipelinq) can call for PDF generation. This also consolidates document-related dependencies (mPDF, Twig) into the app that already owns document workflows.

## What Changes

- DocuDesk gains a `PdfService` that accepts HTML (or a Twig template + data context) and returns PDF binary content
- DocuDesk gains a `TemplateService` for CRUD operations on reusable Twig templates, stored as OpenRegister objects
- DocuDesk exposes API endpoints for PDF generation and template management
- DocuDesk adds mPDF and Twig as composer dependencies
- **BREAKING**: LarpingApp's `CharacterService.createCharacterPdf()` is replaced with a call to DocuDesk's PDF API
- **BREAKING**: LarpingApp's template CRUD (via generic ObjectService) moves to DocuDesk — templates are no longer LarpingApp objects
- LarpingApp's `CharactersController.downloadPdf()` is refactored to call DocuDesk's API instead of rendering locally
- LarpingApp removes mPDF and Twig from its own composer dependencies

## Capabilities

### New Capabilities
- `pdf-generation`: Shared PDF rendering service — accepts Twig template + data context or raw HTML, returns PDF binary via mPDF. Includes API endpoint for inter-app calls.
- `template-management`: CRUD for reusable Twig templates stored as OpenRegister objects. Templates are scoped per-app via a namespace/category field so multiple apps can maintain their own templates.

### Modified Capabilities
- `anonymization` (DocuDesk): No requirement changes — anonymization already produces documents, PDF export is additive.
- `pdf-export` (LarpingApp, external): LarpingApp's pdf-export spec changes from local mPDF rendering to consuming DocuDesk's API. Template storage moves from LarpingApp's ObjectService to DocuDesk's TemplateService.
- `character-management` (LarpingApp, external): Character PDF download flow changes — the controller calls DocuDesk instead of CharacterService for PDF generation.

## Impact

### Code Changes
- **DocuDesk**: New `PdfService`, `TemplateService`, `PdfController`, `TemplatesController`. New OpenRegister schema for templates in `docudesk_register.json`. New composer dependencies (mPDF, Twig).
- **LarpingApp**: Refactor `CharactersController.downloadPdf()` and remove `CharacterService.createCharacterPdf()`. Remove mPDF + Twig composer dependencies. Remove template entity type from ObjectService routing. Update frontend PDF download modal to work with DocuDesk's template API.

### API Changes
- **DocuDesk new endpoints**: `POST /api/pdf/render` (generate PDF from template + data), template CRUD endpoints
- **LarpingApp changed endpoint**: `GET /characters/{id}/download/{template}` still exists but delegates to DocuDesk internally
- **LarpingApp removed endpoints**: Template CRUD via `/api/objects/template` moves to DocuDesk

### Dependencies
- DocuDesk becomes a dependency for LarpingApp (soft dependency — PDF export degrades gracefully if DocuDesk is not installed)
- mPDF (v8.2+) and Twig (v3.18+) move from LarpingApp to DocuDesk
- Inter-app communication via Nextcloud's `OCP\Http\Client\IClientService` or direct service injection via DI container
