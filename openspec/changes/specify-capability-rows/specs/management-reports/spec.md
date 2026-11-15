# management-reports Specification (delta)

## Purpose

A report of which documents hold personal data.

## ADDED Requirements

### Requirement: The personal data report lists which documents hold personal data (REQ-SCR-010)

The reports MUST include a personal data report that lists, across the organisation, the documents the user may read in which detection found personal data. Each row MUST show the entity types and their counts and whether an anonymised copy exists. The report MUST NOT show the detected values.

Rows: `det-overview` (filinq matrix)

#### Scenario: Two documents hold personal data

- GIVEN two documents with detected names and a BSN, one of them anonymised, and one document without findings
- WHEN a privacy officer opens the personal data report
- THEN the two documents are listed with their entity types and counts
- AND only the unanonymised one is marked as without an anonymised copy
- AND no name or BSN is shown
- @e2e exclude specification only; the change that builds it adds the Playwright test
