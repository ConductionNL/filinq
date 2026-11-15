---
status: done
---

# Getting-started tour

## Purpose

A first-time user gets a short guided tour of filinq that ends with a
template and a signing request they made themselves. The tour is declared in
the `walkthrough` block of `src/manifest.json` (`:1506`) and played by
nextcloud-vue's walkthrough (`CnWalkthrough`, mounted by `CnAppRoot`), which
remembers per user how far they got. This spec was written after the fact, on
7 October 2026, from the code at development `b9e834e6`; the tour came in with
PR #144 (26 June 2026). Matrix row `op-tour` (filinq).

## Requirements

### Requirement: The tour starts on a user's first visit (REQ-GST-001)

Filinq SHALL declare one tour, `filinq:getting-started`, with trigger
`first-visit`, so it opens the first time a user opens the app. Completion
MUST be stored per user under the config key `walkthrough_completed_version`,
so a user who finished or dismissed it does not see it again until a step
newer than their version is added.

#### Scenario: A new user is welcomed

- GIVEN a user who has never opened filinq
- WHEN the user opens filinq
- THEN the tour opens on the Dashboard with "Welcome to Filinq"
- @e2e exclude the e2e setup suppresses the walkthrough for every run (tests/e2e/global-setup.ts:246); no spec drives it yet

#### Scenario: A user who finished the tour does not see it again

- GIVEN a user who completed the tour
- WHEN the user opens filinq again
- THEN no tour opens
- @e2e exclude the e2e setup suppresses the walkthrough for every run; no spec drives it yet

### Requirement: The tour has the user make a template and a signing request (REQ-GST-002)

The tour SHALL lead the user to Templates and wait until they create a
template, then to Signing requests and wait until they create a signing
request. Each of those steps MUST advance on the object being created in
filinq's register (`template`, `signingRequest`), not on a click, and MUST
also allow the user to move on without creating it.

#### Scenario: Creating a template moves the tour on

- GIVEN the tour on the step "Click New and save a template"
- WHEN the user saves a new template
- THEN the tour moves to the step that opens Signing requests
- @e2e exclude the e2e setup suppresses the walkthrough for every run; no spec drives it yet

### Requirement: The tour shows where automation lives and where to read on (REQ-GST-003)

After the two records, the tour SHALL point at Flows as an optional step that
asks the user to build nothing, and MUST end by pointing at the
Documentation entry.

#### Scenario: The tour ends at the documentation

- GIVEN the tour after the signing-request step
- WHEN the user moves on
- THEN the tour offers Flows as optional and closes on "Your first template is ready", pointing at Documentation
- @e2e exclude the e2e setup suppresses the walkthrough for every run; no spec drives it yet
