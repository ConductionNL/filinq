---
kind: code
depends_on: []
---

# Proposal: consent-changes-are-recorded-events

## Why

F2 S5 of the filinq security audit. A consent record's status was set by hand:
the detail page PUT `consentStatus`, `notificationStatus` and
`publicationDecision`, and the server let the record's owner (whoever opened
the consent request) set "consent_given" or "delivered" with no transition, no
evidence and no trace beyond OpenRegister's generic audit row. Because the
creator owned the record, they could also PATCH any field straight through the
object API. And the consent officers the schema notifies could not open other
users' records at all.

## What changes

- A status change is a recorded event. Each change of `consentStatus` or
  `notificationStatus` must be a declared move, and is appended to the record's
  `statusEvents` list with who, when, from, to and the evidence given.
- Recording that consent was given, or that an objection was received, needs
  evidence (a short note on how it reached the organisation).
- Only consent officers (`filinq-woo-officers`), policy admins and admins may
  change a consent record. They may also open and list every consent record.
  The person who opened the request can still read their own.
- Every server write on these paths runs past OpenRegister's cascade, after
  the controller's own check: the status update, and the idempotent re-request
  that updates an existing record another user may have opened.
- The detail page asks for the evidence when the consent status changes and
  lists the recorded events.

## Out of scope

- Standing consents keep their own flow (`PolicyCrudService`, standing-consent
  admins).
- The person who opens a consent request still owns the record, so they can
  still PATCH it through OpenRegister's object API. Handing consent records to
  the system needs every reader to stop relying on the owner (the objection
  deadline job, publication readiness, the anonymise runner, two listeners and
  the portal), and that is its own change.
