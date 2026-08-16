# Tasks

## 1. Establish the ground truth

- [ ] Record, per schema, whether it has an `authorization` cascade (measured: 1 of 21 does)
- [ ] For each of the 9 gate-7 endpoints, record which schema it touches and whether that schema is open

Acceptance criteria:
- The record distinguishes "open by decision" from "open by omission". Only the second is a defect.
- Confirm multitenancy is still enforced, so the exposure bound in the proposal is measured rather than assumed.

## 2. Decide authorisation per schema

- [ ] For each of the 20 open schemas, declare an `authorization` cascade or record why org-wide readability is correct
- [ ] Bump `info.version` in `lib/Settings/docudesk_register.json`

Acceptance criteria:
- Without the version bump the import is SKIPPED and the change never deploys to any existing install. Verify the cascade is live on a running instance, not just present in the file.
- Verify a locked-down schema still lets its legitimate owner read their own objects. An over-tight cascade is a visible outage; that is the safer failure, but it is still a failure.

## 3. Guard what the cascade cannot cover

- [ ] For each of the 9 endpoints, add a per-object guard OR record that its data is legitimately org-wide
- [ ] Make each guard state which actor it excludes and why the data layer does not already exclude them

Acceptance criteria:
- No guard is added solely to clear the gate. `ConsentCrudService` documents someone already reasoning their way to deleting a real control as "redundant with OpenRegister RBAC" — an unjustified guard is that control with the reasoning removed.
- A refusal must not reveal whether the requested id exists.

## 4. Ratchet

- [ ] Add a test asserting every schema in the register has an authorisation decision
- [ ] Add a test asserting no flagged endpoint relies on a 401 alone

Acceptance criteria:
- The ratchet asserts COVERAGE per schema, not a findings count. A count passes the moment someone adds an exclude; coverage requires a decision.
- Adding a schema with no decision fails on the day it is added.

## 5. Raise it beyond this app

- [ ] Report the finding to the fleet: any consumer app assuming OR's RBAC guards it has the same question

Acceptance criteria:
- DocuDesk is where this was measured, not necessarily where it is worst.
