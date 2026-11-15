# anonymization Specification (delta)

## Purpose

Detection of business and financial identifiers.

## ADDED Requirements

### Requirement: Business and financial identifiers are detected beside personal data (REQ-SCR-011)

Detection MUST also find business and financial identifiers: Chamber of Commerce (KvK) numbers, VAT numbers, bank account numbers and case numbers.

Rows: `det-business` (filinq matrix)

#### Scenario: A KvK and a VAT number are found

- GIVEN an invoice carrying a KvK number and a VAT number
- WHEN detection runs on it
- THEN both are listed as findings with their own type
- AND they can be anonymised like personal data
- @e2e exclude specification only; the change that builds it adds the Playwright test
