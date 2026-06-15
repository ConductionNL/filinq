# digital-signing-integration Specification (delta)

---
status: proposed
---

## Purpose

Route signer/initiator notifications through the OR email integration leaf's comms surface
rather than ad-hoc inline notifications, so each notification is registry-tracked and visible on
the document's comms surface. References the email leaf (`integration-email`), ADR-019, and
ADR-022.

## ADDED Requirements

### Requirement: Signer and Initiator Notifications Route Through the Email Leaf

The system SHALL deliver signer "your turn to sign" notifications (sequential or parallel) and
the initiator "declined" notification via NC Mail/notification and SHALL link them to the
signing-request OR object through the email integration leaf, so they appear on the document's
comms surface. The signer ordering (sequential vs parallel) is governed by OR's
approval-workflow (`migrate-signing-to-or-approval-workflow`); this requirement governs only the
notification surface. No bespoke per-app notifier SHALL maintain a duplicate comms-state table
for these notifications.

#### Scenario: Sequential signer notification linked via the email leaf

- GIVEN a sequential signing request with signers A, B, C
- WHEN it becomes signer A's turn
- THEN signer A SHALL be notified via NC Mail/notification
- AND the message SHALL be linked to the signing-request object via the email leaf's link-table
- AND the notification SHALL appear on the document/signing detail comms surface (`CnEmailTab`)
- AND signers B and C SHALL NOT be notified until A completes

#### Scenario: Decline notifies the initiator via the email leaf

- GIVEN a signer declines a signing request
- WHEN the decline is recorded
- THEN the initiator SHALL be notified with the decline reason via NC Mail/notification
- AND that message SHALL be linked to the signing-request object via the email leaf

#### Scenario: External QTSP-sent emails are out of scope

- GIVEN an external signing provider (e.g. ValidSign) that sends its own "sign here" email
- WHEN the document is dispatched to that provider
- THEN that provider-sent email SHALL remain the provider's responsibility
- AND it SHALL NOT be required to route through the email leaf
