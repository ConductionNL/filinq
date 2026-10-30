# Tasks: consent-changes-are-recorded-events

- [x] 1.1 Declared moves for `consentStatus` and `notificationStatus`; others refused with 400
- [x] 1.2 `consent_given` and `objection_received` need `evidence`; every change appends to `statusEvents`
- [x] 1.3 Update needs admin, `filinq-woo-officers` or `filinq-policy-admins`; officers read and list every record; the opener still reads their own
- [x] 1.4 The status update and the idempotent re-request save pass `_rbac: false`; schema gains `statusEvents`
- [x] 1.5 ConsentDetail asks for evidence on a consent status change and lists the events
- [x] 1.6 Tests: refused move, missing evidence, event appended, owner without the role refused, officer allowed
