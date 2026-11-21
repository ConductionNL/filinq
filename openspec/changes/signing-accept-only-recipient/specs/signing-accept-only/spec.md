# signing-accept-only Specification (delta)

## Purpose

A recipient on a signing request can be asked to accept the document
instead of signing it. The acceptance counts towards completion and is
recorded as an acceptance, never as a signature. Matrix row
`sig-accept-only` (filinq), demand
https://kb.validsign.eu/nl/release-notes/release-notes-2026-09-23.

## ADDED Requirements

### Requirement: A recipient is a signer or accept-only (REQ-SAO-001)

Every `signerRecord` MUST carry a `role` of `sign` or `accept`, and a
record without a role MUST read as `sign`. The initiator MUST be able to
set the role per recipient on the new request form, in the `signers` of
`POST api/signing/requests`, and in the signer list of
`DocumentSigningRequestedEvent`. A request without any `sign` recipient
MUST be refused with "A signing request needs at least one signer".

Rows: `sig-accept-only` (filinq matrix)

#### Scenario: An employee asks one party to sign and another to accept

- GIVEN an employee on the new signing request form with a lease
- WHEN the employee adds the tenant as "Sign" and the housing officer as "Accept only" and creates the request
- THEN the detail page lists the tenant as signer and the officer as accept-only
- @e2e tests/e2e/signing-accept-only.spec.ts

#### Scenario: Nobody to sign

- GIVEN the same form
- WHEN the employee adds only accept-only recipients
- THEN the request is refused with "A signing request needs at least one signer"
- @e2e tests/e2e/signing-accept-only.spec.ts

### Requirement: An accept-only recipient accepts and never signs (REQ-SAO-002)

Filinq MUST offer `accept` for a recipient whose role is `accept`. It MUST
write `ACCEPTED`, `acceptedAt` and an audit entry `ACCEPTED`, and MUST NOT
write `signatureData`. Signing MUST be refused for an accept-only
recipient, and accepting MUST be refused for a signer. Declining MUST stay
open to both. Accepting MUST NOT require a signing mandate.

Rows: `sig-accept-only` (filinq matrix)

#### Scenario: The housing officer accepts from the signing folder

- GIVEN the housing officer is an accept-only recipient on a pending lease
- WHEN she opens the signing folder and chooses "Accept" on the lease
- THEN the row disappears from her folder and the audit trail reads "Accepted" with her name
- @e2e tests/e2e/signing-accept-only.spec.ts

#### Scenario: An accept-only recipient cannot sign through the API

- GIVEN the same officer
- WHEN a call to `POST api/signing/requests/{id}/sign` is made as her
- THEN the answer is 422 "This document asks you to accept it, not to sign it" and her record stays `PENDING`
- @e2e exclude API refusal; covered by PHPUnit in task 2.2 and Newman in task 2.4

### Requirement: The request completes on signatures and acceptances (REQ-SAO-003)

A request MUST complete when every `sign` recipient is `SIGNED` and every
`accept` recipient is `ACCEPTED`. In sequential mode the recipients' order
MUST hold across both roles. The signed artifact's evidence and
`SigningConcludedEvent` MUST list accept-only recipients as accepted and
MUST NOT list them as signers.

Rows: `sig-accept-only` (filinq matrix)

#### Scenario: A signature and an acceptance complete the lease

- GIVEN a lease with the tenant as signer and the officer as accept-only, in parallel mode
- WHEN the tenant signs and the officer accepts
- THEN the request reads completed, and the verification page lists the tenant as signer and the officer as accepted
- @e2e tests/e2e/signing-accept-only.spec.ts

#### Scenario: Order holds across roles

- GIVEN a sequential request with the tenant first and the officer second
- WHEN the officer tries to accept before the tenant has signed
- THEN the acceptance is refused and the officer's record stays `PENDING`
- @e2e exclude ordering guard; covered by PHPUnit in task 2.3

### Requirement: An external accept-only recipient accepts on the portal (REQ-SAO-004)

Filinq's `signer` portal contribution MUST declare an `accept` row action
and action beside `sign` and `decline` on the `signerSigningRequests`
collection, at `minTrust: substantial`, pointing at the instance-local
endpoint `/apps/filinq/api/portal/signing/accept`. The receiver's accept
act MUST verify the portal assertion and the invited-recipient scope the
same way as the sign act, MUST call `SigningService::accept()` with the
verified actor, and MUST answer 422 when the recipient's role is `sign`.

Rows: `sig-accept-only` (filinq matrix)

#### Scenario: An external recipient accepts on the portal

- GIVEN an external signer invited as accept-only, logged in on the portal at substantial assurance
- WHEN the recipient chooses "Accept" on the document row
- THEN the recipient's record reads `ACCEPTED` and the audit entry names the portal route
- @e2e exclude cross-app portal path; needs portaliq's half, covered by PHPUnit on the receiver in task 3.1 until then

#### Scenario: A signer cannot accept instead

- GIVEN an external signer whose role is `sign`
- WHEN the signer's portal sends the accept act
- THEN the receiver answers 422 and nothing is written
- @e2e exclude receiver refusal; covered by PHPUnit in task 3.1
