# publication-consent Specification (delta)

---
status: proposed
---

## Purpose

Adds a machine-readable **consent-clearance signal** per document to the
existing publication-consent capability, for consumption by the
woo-publicatie-pipeline readiness gate. This is an additive concern: the
app-owned consent boundary, the consent CRUD surface and the configurable WOO
objection window (`ObjectionDeadlineChecker`,
`publication_objection_period_days`, default 28 days) are unchanged.

## ADDED Requirements

### Requirement: Document consent-clearance signal (REQ-DDWPP-020)

`ConsentService` MUST expose a read-only clearance query
`isDocumentConsentClear(string $documentId): array` returning a boolean
verdict plus per-record reasons. A document is consent-clear if and only if
every `publicationConsent` record for it satisfies one of: `consentStatus =
consent_given`; `consentStatus = anonymized`; or `consentStatus =
no_response` with `objectionDeadline` in the past (WOO active-disclosure
objection window elapsed). A document is NOT clear while any record has
`consentStatus` `pending` or `objection_received`, or `publicationDecision =
reject`. A document with zero consent records is clear (no affected entities
require consent). The query MUST NOT modify any consent record and MUST NOT
alter how `objectionDeadline` is computed.

#### Scenario: All consents terminal and permitting

- GIVEN a document with two consent records: one `consent_given` and one `no_response` whose `objectionDeadline` was yesterday
- WHEN the clearance query runs
- THEN the verdict is clear
- @e2e exclude pure read-only query consumed by the pipeline UI — covered exhaustively by PHPUnit table tests (tests/unit/Service/ConsentServiceTest.php); the consuming surface is covered by REQ-DDWPP-002 scenarios

#### Scenario: Unresolved objection blocks clearance

- GIVEN a document with a consent record in `objection_received` and `publicationDecision` `pending`
- WHEN the clearance query runs
- THEN the verdict is not clear and the reasons name that record's UUID and status
- @e2e exclude pure read-only query — covered by PHPUnit (tests/unit/Service/ConsentServiceTest.php); UI consumption covered under REQ-DDWPP-002

#### Scenario: Rejection decision blocks clearance regardless of status

- GIVEN a document with a consent record whose `publicationDecision` is `reject`
- WHEN the clearance query runs
- THEN the verdict is not clear
- @e2e exclude pure read-only query — covered by PHPUnit (tests/unit/Service/ConsentServiceTest.php)

#### Scenario: Objection window computation is untouched

- GIVEN the clearance query implementation
- WHEN it evaluates `no_response` records
- THEN it compares against the stored `objectionDeadline` computed by `ObjectionDeadlineChecker` and introduces no alternative deadline computation
- @e2e exclude architectural boundary assertion — covered by PHPUnit and code review, not a browser flow
