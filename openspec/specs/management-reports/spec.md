---
status: done
---

# Management reports

## Purpose

Filinq gives managers and functional administrators three read-only reports
over its own register: signing, anonymisation and the documents it produced.
Each report counts and groups the objects filinq already stores, so it needs
no controller of its own. The pages are declared in `src/manifest.json` and
drawn by nextcloud-vue's report and dashboard page types, which read
OpenRegister's aggregation endpoint. This spec was written after the fact, on
7 October 2026, from the code at development `b9e834e6`; the pages were added
in commit 0711d705 (3 September 2026). Matrix rows `op-reports` and
`det-overview` (filinq).

## Requirements

### Requirement: A Reports page lists the three reports (REQ-MRP-001)

Filinq SHALL offer a Reports entry in the settings part of the navigation
(`src/manifest.json:240`, `ReportsMenu`) that opens `/reports`, a page of
type `reports` (`src/manifest.json:681`). The page MUST show one card per
report, grouped under "Signing" and "Documents": Signing, Anonymisation and
Documents produced, each with a one-line description, and each card MUST open
its report.

#### Scenario: A manager finds the reports

- GIVEN a user in filinq
- WHEN the user opens Reports from the settings part of the navigation
- THEN `/reports` shows the cards Signing, Anonymisation and Documents produced
- AND choosing a card opens that report
- @e2e tests/e2e/app-chrome.spec.ts

### Requirement: The signing report shows where signing requests stand (REQ-MRP-002)

The page `/reports/signing` (`src/manifest.json:721`) SHALL show the number
of signing requests awaiting signature, part-signed and completed (status
`PENDING`, `IN_PROGRESS` and `COMPLETED` on `signingRequest`), donut charts of
signing requests by status and by assurance level, a donut of signer records
by status, and the eight most recent signing audit entries. Each count MUST
come from OpenRegister's aggregation over filinq's register, and each filter
MUST use the schema's own enum spelling, because the aggregation does not
fold case.

#### Scenario: The signing report counts real requests

- GIVEN signing requests in each status
- WHEN the user opens `/reports/signing`
- THEN the three counts and the charts show those requests, not empty cards
- @e2e tests/e2e/app-chrome.spec.ts

### Requirement: The anonymisation report shows what was anonymised across the organisation (REQ-MRP-003)

The page `/reports/anonymization` (`src/manifest.json:958`) SHALL show,
across every anonymisation batch the reader may see, the number waiting for
review, completed and failed (status `review`, `completed` and `error` on
`anonymizationBatch`), donut charts by status and by source (uploaded or from
a folder), and the eight most recent batches. It reports batches that went
through the pipeline; it MUST NOT be read as a scan of documents that were
never anonymised.

#### Scenario: The anonymisation report counts real batches

- GIVEN anonymisation batches in review, completed and failed
- WHEN the user opens `/reports/anonymization`
- THEN the counts and charts show those batches
- @e2e tests/e2e/app-chrome.spec.ts

### Requirement: The produced-documents report shows what the templates generated (REQ-MRP-004)

The page `/reports/documents` (`src/manifest.json:1167`) SHALL show the
number of documents generated and failed (`generatedDocument` status
`generated` and `failed`), the number of letters generated (`correspondence`
status `generated`), donut charts of documents by format and of letters by
format and by recipient type, and the eight most recent generated documents.
Both schemas MUST sit on the one page, so one visit answers what was
produced and what went out.

#### Scenario: The produced-documents report opens

- GIVEN a user on `/reports`
- WHEN the user opens Documents produced
- THEN `/reports/documents` opens with the title "Documents produced"
- @e2e tests/e2e/app-chrome.spec.ts

### Requirement: Every report links to the list it counts (REQ-MRP-005)

Each count on a report SHALL link to the page that lists the objects it
counts: signing counts to Signing requests, anonymisation counts to
Anonymisation, document counts to Templates and letter counts to
Correspondence. A report MUST NOT offer an edit of its own.

#### Scenario: A count opens its list

- GIVEN the signing report
- WHEN the user chooses the "Awaiting signature" count
- THEN filinq opens Signing requests
- @e2e exclude the link is a declared widget route drawn by nextcloud-vue; app-chrome.spec.ts covers that the reports render
