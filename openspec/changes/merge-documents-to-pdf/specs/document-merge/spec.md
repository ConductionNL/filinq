# document-merge Specification (delta)

---
status: proposed
---

## Purpose

A handler selects documents on an object and gets one ordered PDF/A-3b
with a cover page and bookmarks. Filinq converts, merges and stores the
result; the consuming app offers the action through a leaf (ADR-066) and
finds the file in its own folder. Requested by the dossiq competitor
analysis, register row 4.14.

## ADDED Requirements

### Requirement: A merge is a job object with a lifecycle (REQ-DMG-01)

Filinq MUST declare a `mergeJob` schema in the `document` register with
`inputs[]` (ordered `{fileId, label}`), `options`, `requestedBy`,
`hostObject`, `resultFileId`, `pageCount`, `progress`, `lastError` and a
declarative `x-openregister-lifecycle` on `status` with initial `queued`
and terminal states `done` and `failed`.

#### Scenario: Register import creates the schema

- GIVEN a Nextcloud instance with filinq and OpenRegister
- WHEN `ConfigurationService::importFromApp()` runs on boot
- THEN the `mergeJob` schema exists in the `document` register with the lifecycle above
- @e2e exclude register import runs at boot with no UI surface; covered by the register-import PHPUnit test

### Requirement: The merge converts, orders, bookmarks and stores (REQ-DMG-02)

`DocumentMergeService::merge()` MUST convert every input through
`PdfConversionService`, concatenate the results in `inputs[]` order, prepend
the cover page when `coverTemplateRef` is set, add one bookmark per input
when `bookmarks` is true, write the result to `options.targetFolder` (or
beside the first input) as PDF/A-3b, and set `resultFileId` and
`pageCount`. A conversion failure MUST fail the job with the cascade
report in `lastError` and MUST write no partial file.

#### Scenario: Three files become one PDF in the chosen order

- GIVEN a DOCX, a PNG and a PDF the user may read, listed in that order
- WHEN the user merges them with bookmarks on
- THEN one PDF/A-3b file exists in the target folder whose pages follow that order, with three outline entries, and the job is `done` with `pageCount` equal to the sum of the pages
- @e2e exclude page order and outline are read from the PDF bytes; covered by PHPUnit on `DocumentMergeService::merge()` with fixtures

#### Scenario: An input nobody can convert fails the job cleanly

- GIVEN an input whose type no backend supports
- WHEN the merge runs
- THEN the job is `failed`, `lastError` names the file and the backends tried, and no result file exists
- @e2e exclude failure path; covered by PHPUnit with the cascade report fixture

### Requirement: Read and write checks run server-side (REQ-DMG-03)

`POST /apps/filinq/api/merge` MUST refuse with 403 when the caller lacks
read on any input or write on the target folder, and MUST create no job.
Above `mergeSyncThresholdPages` the merge MUST run as a queued job under
the same checks, and `GET /apps/filinq/api/merge/{id}` MUST report
`progress`.

#### Scenario: A file the user may not read is refused

- GIVEN a selection that includes a file shared read-only to someone else
- WHEN the user submits the merge
- THEN the response is 403 and no `mergeJob` exists
- @e2e exclude authorization guard; covered by PHPUnit on the controller and service

#### Scenario: A large batch is queued and reports progress

- GIVEN a selection of 200 pages and a threshold of 50
- WHEN the user submits the merge
- THEN the response carries a `mergeJob` id in `queued`, and progress reads above 0 once the job has started
- @e2e exclude background job execution; covered by PHPUnit on `MergeDocumentsJob`

### Requirement: Merge to PDF is a bulk-action leaf (REQ-DMG-04)

Filinq MUST register a leaf with id `filinq-merge-to-pdf` on both halves:
a `LeafDescriptor` of kind `render-surface` through
`RegisterLeafProvidersEvent`, and a JS `registerIntegration()` under the
same id with a `bulkAction` and a `widget`. The action dialog MUST let the
user order the inputs, pick a cover template, toggle bookmarks and name
the result. The leaf MUST NOT invoke any action in the consuming app
(ADR-066 decision 2); the result reaches the consumer as a file in the
folder it named.

#### Scenario: dossiq merges the documents of a case

- GIVEN filinq and dossiq are installed and dossiq's Files tab places `filinq-merge-to-pdf` as a bulk action
- WHEN a handler selects three documents, orders them, and confirms
- THEN the merged PDF appears in the case folder and the Files tab lists it
- e2e: `tests/e2e/merge-to-pdf.spec.ts`

#### Scenario: Descriptor and JS registration agree

- GIVEN the leaf is registered
- WHEN gate-24 (integration parity) inspects the app
- THEN the descriptor and the JS registration share the id and both `bulkAction` and `widget` exist
- @e2e exclude parity is checked mechanically by gate-24, not in a browser
