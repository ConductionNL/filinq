# Design: Document Creatie Sjablonen

## Status: Proposed (Not Yet Implemented)

## Architecture (Planned)

### Backend
- `DocumentService` resolves data from OpenRegister objects or external APIs
- Extends existing `TemplateService` (CRUD) and `PdfService` (rendering)
- Supports nested data resolution (max 3 levels deep)
- Bulk generation for multiple recipients
- ODF output via LibreOffice integration

### Data Flow (Planned)
1. Resolve data from OpenRegister (register + schema + UUID)
2. Merge ad-hoc context data (overrides resolved values)
3. Render template with merged data context
4. Generate output (PDF via mPDF, ODF via LibreOffice)
5. Store metadata as report object in document register

### Dependencies
- template-management (CRUD for templates)
- pdf-generation (PDF rendering)
- document-register (audit trail)
- OpenConnector (optional, for external BRP data)

## ADR Compliance
- ADR-001: All data via OpenRegister
- ADR-008: Controller -> DocumentService -> TemplateService/PdfService
