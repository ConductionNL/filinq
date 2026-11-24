# document-signing Specification (delta)

## Purpose

Signing reminders, notifications, delegated authority and phone verification.

## ADDED Requirements

### Requirement: A pending signer is reminded until they sign (REQ-SCR-001)

Filinq MUST remind every signer whose record is still pending, on the interval the request declares, until that signer signs or declines or the request ends. Each reminder MUST name the document and the deadline.

Rows: `sig-reminders` (filinq matrix)

#### Scenario: A signer who has not signed gets a reminder

- GIVEN a request with a daily reminder and two signers, A pending and B signed
- WHEN the reminder job runs
- THEN A receives a reminder naming the document and the deadline
- AND B receives nothing
- @e2e exclude specification only; the change that builds it adds the Playwright test

#### Scenario: A cancelled request stops reminding

- GIVEN a request with a daily reminder that was cancelled yesterday
- WHEN the reminder job runs
- THEN no signer of that request receives a reminder
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: A signer is told a document waits, and the initiator is told it is complete (REQ-SCR-002)

Filinq MUST send a Nextcloud notification to a signer when a document starts waiting for their signature, and MUST send one to the initiator when the last signer has signed and the request is complete.

Rows: `sig-notify` (filinq matrix)

#### Scenario: The initiator hears the request is done

- GIVEN a request with signers A and B, where A has signed
- WHEN B signs
- THEN the initiator receives a notification that the request is complete
- AND the notification links to the signed document
- @e2e exclude specification only; the change that builds it adds the Playwright test

#### Scenario: A signer hears a document waits

- GIVEN a parallel request for signer A
- WHEN the request is sent
- THEN A receives a notification that a document waits for their signature
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: A signer delegates signing authority for a period and for named document types (REQ-SCR-003)

A signer MUST be able to delegate signing authority to a named colleague, with a start date, an end date and the document types it covers. Inside that period the delegate MUST see the matching documents in their signing folder and MAY sign them. Outside the period, or for other types, filinq MUST refuse the delegate. The signed record MUST name the delegate and the person they signed for.

Rows: `sig-mandate` (filinq matrix)

#### Scenario: A delegate signs during the holiday

- GIVEN a wethouder who delegated besluiten to a colleague from 1 to 14 July
- WHEN the colleague opens the signing folder on 5 July
- THEN the pending besluiten of the wethouder are listed
- AND a besluit the colleague signs records both names
- @e2e exclude specification only; the change that builds it adds the Playwright test

#### Scenario: The delegation has ended

- GIVEN the same delegation
- WHEN the colleague tries to sign a besluit of the wethouder on 15 July
- THEN the signature is refused, naming the ended delegation
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: A signer can be verified with a code sent to their phone (REQ-SCR-004)

A signing request MUST be able to require a phone code. Filinq MUST then send a one-time code to the phone number recorded for the signer, and MUST accept the signature only after the right code is entered before it expires. A code MUST work once. The identity evidence MUST record that a phone code was used, and MUST NOT keep the code.

Rows: `sig-sms` (filinq matrix)

#### Scenario: The right code lets the signer sign

- GIVEN a request that requires a phone code for an outside signer
- WHEN the signer opens the request and enters the code they received by text message
- THEN the signature is accepted
- AND the evidence names the phone code as the means
- @e2e exclude specification only; the change that builds it adds the Playwright test

#### Scenario: A wrong code stops the signature

- GIVEN the same request
- WHEN the signer enters a wrong code
- THEN the signature is refused and the request still waits for them
- @e2e exclude specification only; the change that builds it adds the Playwright test
