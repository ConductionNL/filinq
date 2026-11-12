# batch-anonymization Specification (delta)

## Purpose

Anonymisation profiles per document type.

## ADDED Requirements

### Requirement: An anonymisation profile is kept per document type (REQ-SCR-013)

An administrator MUST be able to save several anonymisation profiles, each naming which entity types are anonymised and which are kept, and to link a profile to a document type. A review of a document of that type MUST start from its profile. A document type without a profile MUST use the default profile.

Rows: `red-profiles` (filinq matrix)

#### Scenario: An invoice uses the invoice profile

- GIVEN a profile for invoices that keeps organisations and anonymises bank account numbers
- WHEN a reviewer opens an invoice in review
- THEN organisations start unchecked and bank account numbers start checked
- @e2e exclude specification only; the change that builds it adds the Playwright test
