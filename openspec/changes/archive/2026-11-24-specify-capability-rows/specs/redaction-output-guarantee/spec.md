# redaction-output-guarantee Specification (delta)

## Purpose

A redaction certificate that names the ground for each removal.

## ADDED Requirements

### Requirement: A redaction certificate names what was removed and on which ground (REQ-SCR-012)

For each anonymised document filinq MUST be able to produce a certificate that lists, per entity type, how many values were removed and the legal ground recorded for them, with the moment, the backend and the person who checked it. The certificate MUST NOT contain the removed values.

Rows: `red-certificate` (filinq matrix)

#### Scenario: The certificate of a Woo document

- GIVEN a document in which three names were removed on the ground of privacy
- WHEN the handler requests its certificate
- THEN the certificate lists the type person, the count three and the ground
- AND none of the three names appears in it
- @e2e exclude specification only; the change that builds it adds the Playwright test
