# woo-request-workflow Specification (delta)

---
status: proposed
---

## Purpose

Filinq drafts the Woo decision letter (besluit) and the inventory
(inventarislijst) from organisation-edited templates, filled from the request
and its assessments that the caller passes. Re-scoped on 2026-10-05 by
decision D1: dossiq owns the Woo request, its term, its documents and their
assessment, and calls filinq for these two documents. Woo row 7.9.

The original requirements REQ-DDWRW-001 to 005, 008 and 009 were never built
and are withdrawn by D1. The proposal's re-scope table says where each one now
lives.

## ADDED Requirements

### Requirement: Filinq seeds two organisation-editable Woo templates (REQ-DDWRW-006)

Filinq SHALL seed a `woo-besluit` and a `woo-inventarislijst` template in its
template library, each with that stable slug and the declared data contract
`woo-decision`. An organisation SHALL be able to edit both in the template
editor. A seed run on install or upgrade SHALL create a missing template and
SHALL NOT overwrite a template that exists, edited or not. The generated
document SHALL name the template version it came from, as
`generated-document-names-its-template-version` does for every template.

#### Scenario: an organisation edits the decision letter
- GIVEN a fresh install with the seeded `woo-besluit` template
- WHEN a functional administrator changes its closing paragraph and saves, and the app is upgraded
- THEN the template still holds the edited paragraph, and the next letter is rendered from the edited version and names it
- @e2e exclude a seed idempotency claim; covered by PHPUnit `WooTemplateSeedTest::testAnEditedTemplateSurvivesAnUpgrade`

### Requirement: The Woo decision data contract is checked before anything is rendered (REQ-DDWRW-010)

When a generation request resolves to a template whose data contract is
`woo-decision`, filinq SHALL read `data.wooDecision` with the keys
`reference` (string), `subject` (string), `receivedAt` (date),
`decisionDate` (date), `decisionKind` (`disclose`, `partially-disclose`,
`withhold` or `not-held`), `organisation` (name), and `documents`: a list of
`{inventoryNumber, title, date, assessment, groundCodes, remark}` with
`assessment` one of `disclose`, `partially-disclose`, `withhold`. Filinq SHALL
refuse the request, through `setError()` on the event and with no file
written, when a required key is missing, when a document with assessment
`partially-disclose` or `withhold` has no ground code, when two documents share
an inventory number, or when a ground code does not resolve (REQ-DDWRW-011).
The error SHALL name every problem found, not only the first.

#### Scenario: a withheld document without a ground is refused
- GIVEN a `wooDecision` whose document 3 has assessment `withhold` and no ground code
- WHEN dossiq dispatches the generation for `woo-besluit`
- THEN `isHandled()` is false, `getError()` names document 3 and the missing ground, and no file exists
- @e2e exclude an in-process command; covered by PHPUnit `WooDecisionContextBuilderTest::testAWithheldDocumentNeedsAGround`

#### Scenario: every problem is named at once
- GIVEN a `wooDecision` without `decisionDate` and with two documents numbered 4
- WHEN the generation is dispatched
- THEN `getError()` names the missing `decisionDate` and the duplicate number 4
- @e2e exclude an in-process command; covered by PHPUnit `WooDecisionContextBuilderTest::testAllProblemsAreReported`

### Requirement: Grounds are printed by label and article, never as a bare code (REQ-DDWRW-011)

Filinq SHALL resolve each ground code through the grounds resolver of
`grondslagen-read-from-dossiq` (dossiq's `WooRefusalGrounds::byCode()`, or the
read-only snapshot when dossiq is absent) and pass the templates each ground
as `{code, article, label}`. A code that resolves to nothing, or to a retired
ground, SHALL refuse the request. A retired ground SHALL be accepted only when
the request sets `allowRetiredGrounds: true`, which a caller uses to redraft
an old decision.

#### Scenario: a ground is printed in words
- GIVEN a partly disclosed document citing ground code `5.1.2.e`
- WHEN the decision letter is rendered
- THEN the letter cites article 5.1, second paragraph, under e, with its label, and the code alone appears nowhere without its label
- @e2e exclude needs dossiq's list; covered by PHPUnit `WooDecisionContextBuilderTest::testGroundsAreResolvedToLabels` with the real `WooRefusalGrounds` return shape

#### Scenario: an unknown ground is refused
- GIVEN a ground code `5.9.9` the list does not hold
- WHEN the generation is dispatched
- THEN it is refused naming `5.9.9`
- @e2e exclude an in-process command; covered by PHPUnit `WooDecisionContextBuilderTest::testAnUnknownGroundIsRefused`

### Requirement: The decision letter and the inventory agree (REQ-DDWRW-007)

The `woo-besluit` template SHALL render the reference, the subject, the
received and decision dates, the decision kind, and per assessment the
documents it covers by inventory number with their grounds. The
`woo-inventarislijst` template SHALL render one row per document in the
caller's order with inventory number, title, date, assessment and grounds.
Both SHALL take inventory numbers from the request and never renumber. A
document with assessment `partially-disclose` SHALL never be shown as
disclosed. Filinq SHALL NOT assemble a disclosure package; dossiq holds the
documents and assembles it.

#### Scenario: dossiq drafts a partial disclosure decision
- GIVEN a Woo case in dossiq with three assessed documents: 1 disclose, 2 partially disclose on 5.1.2.e, 3 withhold on 5.1.2.e and 5.2.1
- WHEN dossiq requests `woo-besluit` and then `woo-inventarislijst` with the same `wooDecision`
- THEN both files are stored, the letter names documents 2 and 3 with their grounds in words, and the inventory lists 1, 2 and 3 with the same numbers and assessments
- e2e: `tests/e2e/workflows/woo-request-workflow.spec.ts`

#### Scenario: filinq absent
- GIVEN filinq not installed
- WHEN dossiq dispatches the generation
- THEN the event comes back neither handled nor refused, and dossiq records that no letter was drafted
- @e2e exclude an app-absent path on the caller's side; covered on dossiq's side by its own test of `FilinqTemplateEngineAdapter`
