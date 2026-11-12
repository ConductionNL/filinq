# portal-contribution Specification (delta)

## Purpose

The documents of a citizen's own request in the portal.

## ADDED Requirements

### Requirement: A citizen reads and downloads the documents of their own request in the portal (REQ-SCR-016)

Filinq's portal contribution MUST serve to a verified data subject the documents released to them on the records where they are the requesting party, and MUST let them download each one. Documents that were not released, and documents of other parties, MUST be absent.

Rows: `sh-portal` (filinq matrix)

#### Scenario: A citizen downloads their decision

- GIVEN a request whose besluit was released to the requester and whose internal advice was not
- WHEN the requester opens the request in the portal
- THEN the besluit is listed and downloads
- AND the internal advice is not listed
- @e2e exclude specification only; the change that builds it adds the Playwright test
