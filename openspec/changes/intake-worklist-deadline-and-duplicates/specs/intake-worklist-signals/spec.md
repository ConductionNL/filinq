# intake-worklist-signals Specification (delta)

## Purpose

The intake worklist tells a registrar what is late and what arrived
before. A waiting document carries a handling deadline, and a document whose
content matches an earlier one is marked as a possible duplicate. Matrix
rows `in-deadline` and `in-duplicate` (filinq).

## ADDED Requirements

### Requirement: Every waiting document has a handling deadline (REQ-IWS-001)

At arrival filinq MUST set `handlingDeadline` from the matched intake
default rule's `handlingDays`, counted in working days from `receivedAt`,
or from the admin default when no rule matches. A registrar MAY change the
deadline and MUST give a reason, which filinq MUST keep on the document.

Rows: `in-deadline` (filinq matrix)

#### Scenario: A letter gets ten working days

- GIVEN an active rule for channel `mail` with `handlingDays` 10
- WHEN a message arrives on Monday 5 October
- THEN its row in `/intake` shows the deadline Monday 19 October and "10 days left" counted in working days
- @e2e tests/e2e/intake-worklist-signals.spec.ts

#### Scenario: A registrar moves a deadline

- GIVEN a waiting document due tomorrow
- WHEN the registrar chooses "Change deadline", picks a date next week and types "waiting for the attachment"
- THEN the row shows the new deadline and the document keeps the reason
- @e2e tests/e2e/intake-worklist-signals.spec.ts

### Requirement: The worklist shows what is overdue (REQ-IWS-002)

The worklist MUST offer an Overdue mode that lists every document whose
deadline has passed and which is not handled. A document MUST count as
handled when it is rejected, or when its rule's `handledWhen` is met:
`assigned` when it is assigned to a record, `answered` when an outbound
registration answers its inbound registration.

#### Scenario: A team lead sees the late post

- GIVEN one document five days past its deadline and still waiting, and one past its deadline but assigned under a rule with `handledWhen` assigned
- WHEN the team lead opens `/intake` and chooses Overdue
- THEN only the first document is listed, reading "5 days overdue"
- @e2e tests/e2e/intake-worklist-signals.spec.ts

#### Scenario: An assigned letter stays open until it is answered

- GIVEN a rule with `handledWhen` answered and an assigned document past its deadline with no outbound registration answering it
- WHEN the team lead opens the Overdue mode
- THEN the document is listed, and it leaves the list once an outgoing letter is registered as its answer
- @e2e exclude the answer is registered by the post register of another change; covered by PHPUnit on `IntakeRepository::findOverdue()`

### Requirement: An arrival whose content came before is marked, never refused (REQ-IWS-003)

At arrival filinq MUST compute the SHA-256 of the file into `contentHash`.
When an earlier intake document that is not rejected has the same hash,
filinq MUST set `duplicateOf` to that document. The arrival MUST NOT be
refused. The existing `sourceRef` re-delivery skip MUST run first and stay
unchanged.

Rows: `in-duplicate` (filinq matrix)

#### Scenario: The same letter by scan and by mail

- GIVEN a scanned letter waiting in the inbox
- WHEN the same PDF arrives by mail under another source reference
- THEN both documents are waiting, and the second one's row shows "Possible duplicate" linking to the first
- @e2e tests/e2e/intake-worklist-signals.spec.ts

### Requirement: A registrar resolves a duplicate in one step (REQ-IWS-004)

For a document with `duplicateOf`, the worklist MUST offer "Reject as
duplicate", which rejects it through the existing reject transition with a
reason naming the first document.

#### Scenario: A registrar rejects the second copy

- GIVEN a waiting document marked as a possible duplicate of "Bezwaar parkeervergunning"
- WHEN the registrar chooses "Reject as duplicate"
- THEN the document leaves the waiting list and its reject reason reads "Duplicate of Bezwaar parkeervergunning"
- @e2e tests/e2e/intake-worklist-signals.spec.ts
