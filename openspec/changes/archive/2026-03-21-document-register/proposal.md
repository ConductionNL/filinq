# Document Register

## Problem
Defines the data model for the `document` register used by DocuDesk to store document analysis results. This register is loaded from `lib/Settings/document_register.json` (separate from the consent-focused `docudesk_register.json`) and contains three schemas: `report` (analysis results), `template` (document templates), and `entity` (cross-document entity management). Pre-seeded sample objects demonstrate the anonymization pipeline's output format. Note: all three schemas have `properties: []` (empty) and `hardValidation: false`, meaning field definitions exist only on the sample objects as ad-hoc data, not as schema-enforced property definitions.

## Proposed Solution
Implement Document Register following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the document-register specification.

## Success Criteria
- Register creation from JSON
- Separate from consent register
- Register not auto-loaded on boot
- Create report for analyzed document
- Report with critical risk level
