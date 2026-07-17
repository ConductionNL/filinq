# anonymization-entity-review Specification (delta)

---
status: proposed
---

## Purpose

Extends the consolidated batch entity review with the workbench's policy
pre-application and checked-gate semantics: the consolidated-entities
response gains a `standingConsentMatch` field (sibling of the existing
`prohibitionMatch`), and the batch anonymize commit gains the per-document
checked-gate precondition (see `anonymization-review-workbench`
REQ-DDARW-007/008).

## ADDED Requirements

### Requirement: Consolidated entities carry `standingConsentMatch` (REQ-DDARW-010)

The endpoint `GET /api/anonymization/batch/{batchId}/entities` MUST include
a `standingConsentMatch` field on every entity entry, the standing-consent
sibling of the existing `prohibitionMatch` field:

- `null` — no active standing-consent rule matches the entity, OR
- `{ ruleId, ruleName }` — an active standing-consent rule matched.

The matcher consulted MUST be the same `PolicyMatchService::match()` pass
that produces `prohibitionMatch`; when both kinds match, `prohibitionMatch`
MUST be set and `standingConsentMatch` MUST be `null` (prohibitions win on
conflict, existing matcher behaviour). Entities with a
`standingConsentMatch` MUST be pre-set `included: false` in the response
(overriding WOO-profile pre-selection for that entity), mirroring the
review pre-application. The change MUST be additive: pre-change clients
reading only the existing fields continue to work.

#### Scenario: Standing-consent match pre-excludes a batch entity

- GIVEN an active standing consent for "Gemeente Voorbeeldstad" (ORGANIZATION)
- AND a batch in review status containing that entity with the WOO profile pre-selecting ORGANIZATION as keep
- WHEN the consolidated-entities endpoint is called
- THEN the entity entry has `standingConsentMatch: {ruleId, ruleName}` and `included: false`
- @e2e exclude batch API response-shape contract; covered by PHPUnit (tests/unit/Controller/BatchAnonymizationControllerTest.php) and Newman batch contracts

#### Scenario: No standing-consent rule matches

- GIVEN a batch entity that no standing-consent rule matches
- WHEN the endpoint is called
- THEN the entity entry has `standingConsentMatch: null`
- @e2e exclude batch API response-shape contract; covered by PHPUnit (tests/unit/Controller/BatchAnonymizationControllerTest.php)

#### Scenario: Prohibition wins over standing consent

- GIVEN a batch entity matched by both an active prohibition and an active standing consent
- WHEN the endpoint is called
- THEN `prohibitionMatch` is set and `standingConsentMatch` is `null`
- AND the entity is pre-set `included: true`
- @e2e exclude matcher precedence already unit-covered in PolicyMatchService; asserted by PHPUnit, not UI

### Requirement: Batch anonymize respects the per-document checked gate (REQ-DDARW-011)

`POST /api/anonymization/batch/{batchId}/anonymize` MUST evaluate the
per-document checked gate (`documentReview` objects, gate mode
`docudesk.review.checked_gate`) for every non-error file in the batch
before anonymizing anything. In `enforced` mode (default) the endpoint MUST
return HTTP 409 with `reason: "documents_not_reviewed"` and an
`uncheckedFiles` array (fileId + fileName) when one or more files lack a
valid check, committing no file. In `advisory` mode the endpoint MUST
proceed and include the `checkedGate` verdict in the response. This
precondition runs in addition to — not instead of — the existing
prohibition gate.

#### Scenario: Batch with one unchecked file is refused atomically

- GIVEN gate mode `enforced` and a review-status batch of 3 files with 2 checked and 1 unchecked
- WHEN the batch anonymize endpoint is called with a reviewed entity list
- THEN the system returns HTTP 409 with `reason: "documents_not_reviewed"` and the unchecked file listed
- AND no file in the batch is anonymized
- @e2e exclude batch API gate contract; covered by PHPUnit (tests/unit/Controller/BatchAnonymizationControllerTest.php) and Newman batch contracts

#### Scenario: Fully checked batch proceeds

- GIVEN gate mode `enforced` and a review-status batch whose files are all checked
- WHEN the batch anonymize endpoint is called
- THEN anonymization proceeds exactly as before this change
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts
