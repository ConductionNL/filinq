# document-register Specification (delta)

## Purpose

Sorting of the flat file list, documents that wait for a person, and the audit trail of a document.

## ADDED Requirements

### Requirement: The flat file list sorts on any column (REQ-SCR-005)

The flat list of every file on a case MUST let the user sort on each of its columns, ascending and descending, and MUST keep the chosen order while the user stays on the case.

Rows: `wk-case-list` (filinq matrix)

#### Scenario: Sorting by date

- GIVEN a case whose flat file list holds thirty files
- WHEN the handler sorts on the date column
- THEN the newest file is first
- AND sorting on it again puts the oldest first
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: My documents also lists what waits for me (REQ-SCR-006)

Beside the documents a person created, filinq MUST offer a list of the documents that wait for that person: a pending signature, an intake document assigned to them, and a document due for review that they own.

Rows: `wk-mine` (filinq matrix)

#### Scenario: Three kinds of waiting work

- GIVEN a user with one pending signature, one assigned intake document and one document due for review
- WHEN they open My documents and choose Waiting for me
- THEN all three are listed, each saying why it waits
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: Every action on a document is in its audit trail (REQ-SCR-007)

A document detail MUST show an audit trail of every action on the document, with the actor and the moment: registered, changed, new version, downloaded, shared, signed, anonymised, made final and published. The trail MUST be read from the OpenRegister audit trail, and filinq MUST NOT keep a second store.

Rows: `rec-audit` (filinq matrix)

#### Scenario: The trail of a besluit

- GIVEN a besluit that was registered, signed and downloaded
- WHEN the handler opens its audit trail
- THEN the three actions are listed in time order, each with its actor and moment
- @e2e exclude specification only; the change that builds it adds the Playwright test
