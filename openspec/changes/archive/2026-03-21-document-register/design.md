# Design: Document Register

## Architecture

### Data Model
- Defined in `lib/Settings/document_register.json`
- Separate from `docudesk_register.json` (consent-focused)
- Contains 3 schemas: report, template, entity
- All schemas use `properties: []` (empty) with `hardValidation: false`

### Register Structure
- Register slug: `document`, version: `0.0.1`
- **report**: Analysis results (file metadata, entity detection, risk assessment)
- **template**: Document template definitions
- **entity**: Cross-document entity management

### Known Gap
- `document_register.json` is NOT auto-loaded on boot
- Only `docudesk_register.json` is imported via `ConfigurationService::importFromApp()`

## ADR Compliance
- ADR-001: All data stored via OpenRegister
- ADR-011: Uses OpenRegister's existing ConfigurationService
