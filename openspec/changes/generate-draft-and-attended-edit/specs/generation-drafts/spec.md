# generation-drafts Specification (delta)

## Purpose

A generation can stop before it is stored. The clerk edits the rendered
document, or leaves it and comes back, and finishes it into a stored and
logged document through the same path as a direct generation. Matrix rows
`gen-attended` and `gen-draft` (filinq).

## ADDED Requirements

### Requirement: A clerk opens a generation for editing before it is stored (REQ-GDA-001)

Filinq MUST offer "Open for editing" beside "Generate letter" on
`/correspondence` and on the review step of the wizard. The action MUST
call `POST api/documents/drafts`, which MUST resolve the data and render
the document to HTML through `DocumentService::generatePreview()`, save a
`documentDraft` object in stage `editing` owned by the session user, and
answer with its id. It MUST NOT file a document, and MUST NOT write a
`generatedDocument` or `correspondence` record.

Rows: `gen-attended` (filinq matrix)

#### Scenario: A clerk opens a letter for editing

- GIVEN a clerk on `/correspondence` with the template "Algemene Correspondentiebrief" and one recipient filled in
- WHEN the clerk chooses "Open for editing"
- THEN `/drafts/<id>` opens with the letter text, the recipient's name merged in, and no file is downloaded
- AND the correspondence log shows no new entry
- @e2e tests/e2e/generation-drafts.spec.ts

### Requirement: Finishing files the edited text through the direct path (REQ-GDA-002)

`POST api/documents/drafts/{id}/finish` MUST produce the output from the
draft's stored HTML without rendering it through Twig again, MUST sanitise
the HTML on the server before production, and MUST file and log it through
the same code as a direct generation: a `correspondence` record for a
correspondence draft, a `generatedDocument` record otherwise. The record
MUST carry `draftId` and `editedBy` (the session user). The draft MUST move
to stage `finished` and MUST NOT accept further edits.

Rows: `gen-attended` (filinq matrix)

#### Scenario: A clerk changes a sentence and finishes

- GIVEN a clerk's draft letter on `/drafts/<id>`
- WHEN the clerk changes the closing sentence, saves, and chooses "Finish"
- THEN the downloaded PDF contains the changed sentence
- AND the new correspondence entry names the clerk as editor and links the draft
- @e2e tests/e2e/generation-drafts.spec.ts

#### Scenario: Typed braces stay text

- GIVEN a clerk's draft
- WHEN the clerk types `{{ naam }}` into the text and finishes
- THEN the produced document shows the characters `{{ naam }}` and no merged value
- @e2e exclude a rendering rule on bytes; covered by PHPUnit on `DocumentService::generateFromHtml()` asserting Twig is not called

### Requirement: A clerk leaves a draft and resumes it later (REQ-GDA-003)

Filinq MUST list the session user's drafts in stage `answering` or
`editing` on `/drafts`, newest change first. Opening an `editing` draft
MUST show its saved HTML. The wizard MUST offer "Save and finish later" on
every step; it MUST save the answers so far and the current step on a
draft in stage `answering`, and reopening that draft MUST continue the
wizard at the saved step with the saved answers. Finishing a wizard draft
MUST pass the answers as `options.wizardContext` so the wizard's own
validation and record apply.

Rows: `gen-draft` (filinq matrix)

#### Scenario: A clerk comes back to a letter the next day

- GIVEN a clerk who saved a draft letter yesterday and logged out
- WHEN the clerk opens `/drafts`
- THEN the draft is listed with its template name and last change, and opening it shows yesterday's text
- @e2e tests/e2e/generation-drafts.spec.ts

#### Scenario: A wizard run resumes at the question it stopped at

- GIVEN a clerk who answered three of five wizard questions and chose "Save and finish later"
- WHEN the clerk opens the draft from `/drafts`
- THEN the wizard opens at question four with the first three answers filled in
- @e2e tests/e2e/generation-drafts.spec.ts

### Requirement: A draft is private and short-lived (REQ-GDA-004)

`documentDraft` MUST declare `authorization.scope: private`, so only its
owner and an administrator can read, change or delete it. Every save MUST
set `expiresAt` to 30 days later, and a daily background job MUST delete
drafts past `expiresAt`. The owner MUST be able to discard a draft at any
time. The schema MUST NOT carry an `x-openregister-archival` annotation.
The draft is personal data prepared for a document (AVG art. 5(1)(e),
art. 25) and MUST declare an `x-openregister-processing` activity naming
that purpose and the 30-day retention.

Rows: `gen-draft` (filinq matrix)

#### Scenario: A colleague cannot open a clerk's draft

- GIVEN a draft owned by clerk A
- WHEN clerk B opens `/drafts/<id>` of that draft
- THEN the page says the draft was not found, and `/drafts` for clerk B does not list it
- @e2e tests/e2e/generation-drafts.spec.ts

#### Scenario: An untouched draft disappears after 30 days

- GIVEN a draft whose last change was 31 days ago
- WHEN the daily expiry job runs
- THEN the draft no longer exists and no file was created from it
- @e2e exclude time-based background job; covered by PHPUnit on `DocumentDraftExpiryJob` with a fixed clock

### Requirement: A template with a plain-language version is not drafted (REQ-GDA-005)

When the template declares `plainLanguage`, `POST api/documents/drafts`
MUST answer 409 with "This template has a plain-language version. Generate
it directly." and MUST NOT save a draft, and the pages MUST NOT show
"Open for editing" for that template.

Rows: `gen-attended` (filinq matrix)

#### Scenario: The action is absent for a template with a plain version

- GIVEN a template that declares a plain-language counterpart
- WHEN a clerk selects it on `/correspondence`
- THEN "Open for editing" is not shown and "Generate letter" still works
- @e2e tests/e2e/generation-drafts.spec.ts
