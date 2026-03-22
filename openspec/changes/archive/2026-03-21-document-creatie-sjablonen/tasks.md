# Tasks: document-creatie-sjablonen

## Status: Proposed (Not Yet Implemented)

## Task 1: Data Resolution Service
- [ ] Implement `DocumentService::resolveData()` from OpenRegister objects
- [ ] Support nested resolution (max 3 levels: zaak -> persoon -> adres)
- [ ] Support ad-hoc context data merge (overrides resolved values)
- [ ] Handle resolution failures with descriptive per-field errors

## Task 2: Document Generation API
- [ ] Implement `POST /api/documents/generate` endpoint
- [ ] Accept template ID, data references, and context
- [ ] Return generated document (PDF or ODF)

## Task 3: Bulk Generation
- [ ] Implement bulk generation for multiple recipients
- [ ] Support progress tracking for long-running operations

## Task 4: ODF Output
- [ ] Integrate LibreOffice for ODF document generation

## Task 5: Unit Tests (ADR-009)
- [ ] Test data resolution from OpenRegister
- [ ] Test nested resolution with depth limit
- [ ] Test ad-hoc data merge precedence

## Task 6: Documentation + Screenshots (ADR-010)
- [ ] Write feature documentation at `docs/features/document-creatie-sjablonen.md`

## Task 7: i18n (ADR-005)
- [ ] Add Dutch translations for document generation UI
- [ ] Add English translations for document generation UI
