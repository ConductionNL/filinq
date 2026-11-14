# document-webhooks Specification (delta)

## Purpose

An outside system hears about a document when something happens to it:
signed, anonymised, generated or registered. An admin sets up the
receiver from filinq. OpenRegister does the delivery. Matrix row
`con-webhook` (filinq).

## ADDED Requirements

### Requirement: Filinq raises four document events whatever path wrote the record (REQ-CDW-001)

Filinq MUST dispatch `DocumentSignedEvent` when a `signingRequest` takes
the `complete` transition, `DocumentAnonymisedEvent` when an
`anonymizationLink` is created, `DocumentGeneratedEvent` when a
`generatedDocument` is created with status `generated`, and
`DocumentRegisteredEvent` when a `documentRegistration` is created. Each
MUST be raised once per such write, whether the write came from filinq's
screens, OpenRegister's API, the portal or an agent. A failed generation
MUST NOT raise `DocumentGeneratedEvent`.

Rows: `con-webhook` (filinq matrix)

#### Scenario: A signed contract reaches the records system

- GIVEN an admin has set up a receiver for "Document signed"
- WHEN the last external signer signs a contract and the request completes
- THEN the receiver gets one `document.signed` delivery naming the signed file and the signing request
- @e2e tests/e2e/document-webhooks.spec.ts

#### Scenario: A write through the API raises the same event

- GIVEN the same receiver
- WHEN an integration completes a signing request through OpenRegister's objects API instead of filinq's screen
- THEN the receiver gets the same `document.signed` delivery
- @e2e exclude API-only path; covered by Newman in task 1.3

#### Scenario: A failed generation stays quiet

- GIVEN a receiver for "Document generated"
- WHEN a clerk generates a letter and the generation fails
- THEN no `document.generated` delivery is made
- @e2e exclude absence-of-behaviour guard; covered by PHPUnit in task 1.2

### Requirement: The event names the document and never carries its content (REQ-CDW-002)

Every event MUST carry `event`, `occurredAt`, `document.fileId`,
`document.name`, `record.register`, `record.schema`, `record.id` and
`actor`, plus the fields listed for that event in the design. It MUST NOT
carry file bytes, detected entities, signer e-mail addresses or IP
addresses.

Rows: `con-webhook` (filinq matrix)

#### Scenario: An anonymised copy is announced by reference

- GIVEN a receiver for "Document anonymised"
- WHEN a Woo coordinator anonymises a document
- THEN the delivery names the source file id, the anonymised file id and the verification verdict, and holds no entity found in the text
- @e2e exclude payload shape; covered by PHPUnit envelope fixtures in task 1.1

### Requirement: An admin sets up a receiver from filinq's settings (REQ-CDW-003)

Filinq's admin settings page MUST show a "Document webhooks" section that
lists the webhooks subscribed to a filinq document event. An admin MUST be
able to add one with a name, a URL, one or more of the four events and a
secret; send a test; see the result of the last delivery; and delete it.
The section MUST refuse to save without a secret. It MUST use
OpenRegister's webhook API and MUST NOT add a filinq controller for it.

Rows: `con-webhook` (filinq matrix)

#### Scenario: An admin adds a receiver and tests it

- GIVEN an admin on filinq's admin settings page
- WHEN the admin adds "Records system" with a URL, ticks "Document signed" and "Document registered", types a secret and sends a test
- THEN the section lists the receiver with both events and shows that the test arrived
- @e2e tests/e2e/document-webhooks.spec.ts

#### Scenario: No secret, no receiver

- GIVEN the same form
- WHEN the admin leaves the secret empty and saves
- THEN the form says "Type a secret so the receiver can check each delivery" and nothing is saved
- @e2e tests/e2e/document-webhooks.spec.ts

### Requirement: Delivery is OpenRegister's (REQ-CDW-004)

Filinq MUST hand its events to OpenRegister's webhook delivery and MUST NOT
send an HTTP request, queue a delivery or keep a delivery log of its own.
When OpenRegister's event catalogue lists no filinq document event, the
section MUST say "Delivery is not available on this server" and MUST still
list the saved receivers.

Rows: `con-webhook` (filinq matrix)

#### Scenario: An older OpenRegister says so

- GIVEN an OpenRegister without app-declared webhook events
- WHEN an admin opens the "Document webhooks" section
- THEN the section says "Delivery is not available on this server"
- @e2e tests/e2e/document-webhooks.spec.ts
