# Tasks

- [x] Add `x-openregister-notifications` to `signerRecord` (signingRequested created → field:userId)
- [x] Add `x-openregister-notifications` to `signerRecord` (signingDeadline scheduled, filter status=PENDING)
- [x] Add `x-openregister-notifications` to `signingRequest` (signingCompleted scheduled, filter status=COMPLETED → field:initiatorUserId)
- [x] Add `x-openregister-notifications` to `publicationConsent` (objectionDeadline scheduled → groups + object-acl, STAFF only, never data subject)
- [x] Add `x-openregister-notifications` to `correspondence` (correspondenceFailed created → field:generatedBy + groups)
- [x] Verify `signerRecord.userId`, `signingRequest.initiatorUserId`, `correspondence.generatedBy` resolve as Nextcloud uids
- [x] Provide inline `subject{nl,en}` for every rule
- [x] Validate `lib/Settings/docudesk_register.json` parses as JSON and every block uses verified keys only

## Acceptance criteria

- Every rule uses `trigger.type` from the verified set (`created`/`scheduled`), `channels[]`, `recipients[]`, and inline `subject{nl,en}`.
- `field` recipients reference only confirmed-uid fields (`userId`, `initiatorUserId`, `generatedBy`); no rule routes to `publicationConsent.contactEmail` (external data-subject email).
- The `publicationConsent` objection-deadline rule notifies staff only (groups + object-acl), never the data subject.
- `scheduled` rules carry `intervalSec >= 60` and a `filter` on the relevant status field.
- The register JSON validates against OpenRegister's register schema after the additions.
- The "only-when-failed/completed" and named-transition deferrals are documented in the proposal's Caveats.
