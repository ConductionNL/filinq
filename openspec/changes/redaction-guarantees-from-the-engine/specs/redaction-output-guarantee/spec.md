# redaction-output-guarantee Specification (delta)

---
status: proposed
---

## Purpose

filinq consumes OpenRegister's redaction guarantees and retires its own
copies, keeping its review workbench on top. Decision D2. Supports Woo rows
4.5, 4.27, 18.1, 18.2 and 18.3.

## MODIFIED Requirements

### Requirement: The written copy cannot be read back (REQ-RWB-01)

filinq SHALL NOT verify a redacted copy itself. Every redacted copy filinq asks
for SHALL be written by OpenRegister's `FileService::anonymizeDocument()`,
whose `verification` (REQ-RRS-001) filinq SHALL store on the file's
`anonymizationLink` as `verificationVerdict`, `verificationOutputMode`,
`verificationRoutes`, `verificationLeakRoutes` and `verifiedAt`. A missing
`verification` SHALL be stored as `unverifiable`. Publication readiness SHALL
accept only `clean`. When the installed OpenRegister does not return
`verification`, filinq SHALL refuse to write a redacted copy and name the
OpenRegister release it needs.

#### Scenario: OpenRegister's verdict is the one filinq shows
- GIVEN OpenRegister returns `verification.verdict` `leaking` with route `incremental_update`
- WHEN filinq's batch anonymisation stores the run
- THEN the link reads `leaking` with that route, and the record is not ready for publication
- @e2e exclude needs a leaking fixture through the engine; covered by PHPUnit `EngineVerdictRecorderTest::testTheEngineVerdictIsStored` with OpenRegister's real response shape

#### Scenario: an older OpenRegister is refused, not worked around
- GIVEN an OpenRegister whose anonymise result has no `verification`
- WHEN a reviewer commits an anonymisation in filinq
- THEN no redacted copy is written, and the message names the OpenRegister release needed
- @e2e exclude needs an older OpenRegister; covered by PHPUnit `EngineCapabilityTest::testWithoutVerificationNothingIsWritten`

#### Scenario: filinq's own verifier is gone
- GIVEN the source tree
- WHEN the test suite runs
- THEN `EngineOnlyTest::testFilinqHasNoVerifierOfItsOwn` finds no `RedactionIrreversibilityVerifier` or `RedactionLeakRoute` class under `lib/`
- @e2e exclude a source scan; covered by PHPUnit

### Requirement: Nothing is written until a person has checked it (REQ-RWB-02)

filinq SHALL write every include or skip in the workbench as OpenRegister's
`decision` (`redact` or `release`) through `PATCH /api/entity-relations/{id}`,
and "mark as reviewed" as OpenRegister's review mark through
`POST /api/files/{fileId}/anonymisation/review-mark`. OpenRegister SHALL be the
only enforcement (REQ-RRS-002). filinq's `documentReview` SHALL remain the
workbench's record and SHALL NOT be a gate. filinq SHALL surface
OpenRegister's 409 refusal with its message on the single and the batch path.
filinq's setting `filinq.review.checked_gate` SHALL show and link to
OpenRegister's `anonymisation.requireReview`.

#### Scenario: an unchecked document is refused by the engine, and filinq says why
- GIVEN `anonymisation.requireReview` on and a document with an undecided detection
- WHEN the reviewer starts a batch anonymisation in filinq
- THEN OpenRegister refuses with 409, filinq shows its message for that document, and no copy is written
- @e2e tests/e2e/spec-coverage/redaction-guarantees-from-the-engine.spec.ts

#### Scenario: marking reviewed in the workbench reaches the engine
- GIVEN every detection decided in the workbench
- WHEN the reviewer marks the document as reviewed
- THEN OpenRegister holds a review mark naming the reviewer for the current detection run
- @e2e exclude a cross-app write; covered by PHPUnit `WorkbenchEngineBridgeTest::testMarkReviewedPostsTheEngineMark`

## ADDED Requirements

### Requirement: Term lists and the Woo profile live in the engine (REQ-RWB-05)

A repair step SHALL move every filinq prohibition and standing consent that
is a term rule into OpenRegister's always-redact and never-redact lists
(`openregister/redaction-policy-as-data`), keeping its scope (organisation or
one request), and SHALL report every rule it cannot express there. A rule it
cannot move SHALL stay in filinq and stay enforced. After the repair,
`PolicyMatchService` SHALL read OpenRegister's lists for term rules. filinq's
stored Woo profile SHALL be created once as an OpenRegister named policy
profile, and `WooProfileService` SHALL read that profile. The repair SHALL be
idempotent.

#### Scenario: an always-redact term moves to the engine
- GIVEN a filinq prohibition "always redact Demostad Vastgoed BV" for the organisation
- WHEN the repair runs and opencatalogi then redacts a document naming it
- THEN OpenRegister's always-redact list holds the term, and opencatalogi's redaction removes it too
- @e2e exclude a cross-app path; covered by PHPUnit `MovePoliciesToTheEngineTest::testAnOrganisationTermIsMoved` and an OpenRegister-side test in its own change

#### Scenario: a rule the engine cannot hold stays and is reported
- GIVEN a filinq rule the engine's list cannot express
- WHEN the repair runs
- THEN the rule stays in filinq, is still applied, and the report names it
- @e2e exclude a repair step; covered by PHPUnit `MovePoliciesToTheEngineTest::testAnInexpressibleRuleStays`
