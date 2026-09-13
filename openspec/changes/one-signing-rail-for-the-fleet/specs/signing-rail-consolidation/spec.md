# signing-rail-consolidation Specification (delta)

---
status: proposed
---

## Purpose

One door into filinq's signing rail for every fleet app, and a signing request
that records what noun it signs. Three apps sign things today, filinq ships an
implemented delegated-signing contract, and only one of the three uses it. The
other two each built a private route, and both routes are dark.

## ADDED Requirements

### Requirement: The delegated event is the only door (REQ-DDSRC-001)

A fleet app that asks filinq to sign a document MUST do so by dispatching
`OCA\Filinq\Event\DocumentSigningRequestedEvent`. Filinq MUST NOT document,
support or advertise any other cross-app entry point for raising a signing
request, and a consumer MUST NOT reach `SigningService` through an
integriq Source, a direct HTTP call to `api/signing/requests` on behalf of
another app, or a signing provider adapter of its own.

#### Scenario: A consumer raises a request through the event

- **GIVEN** a fleet app with a document to sign
- **WHEN** it dispatches `DocumentSigningRequestedEvent`
- **THEN** filinq creates the signing request and writes the new id back on
  the event instance
- @e2e exclude In-process event contract with no UI surface; covered by PHPUnit.

#### Scenario: An app that ships its own provider adapter is a defect

- **GIVEN** a fleet app that carries its own signing provider adapter for a
  document filinq can sign
- **WHEN** the fleet signing surface is reviewed
- **THEN** that adapter is recorded as a duplicate rail to be retired
- @e2e exclude Architectural rule verified by review, not at runtime.

### Requirement: A signing request records the noun it signs (REQ-DDSRC-002)

`SigningProvenance` MUST carry the subject noun for every delegated request,
derived from the subject register and schema the consumer already supplies.
The signing request MUST persist it. A request raised by decidiq for board
minutes and a request raised by dossiq for a beschikking MUST be
distinguishable on the record without reading the consumer's code.

#### Scenario: The record says what was signed

- **GIVEN** dossiq raises a signing request for a beschikking
- **WHEN** the signing request is read
- **THEN** it names dossiq as the source app and the beschikking as the
  subject noun
- @e2e exclude Backend record shape; covered by PHPUnit.

#### Scenario: Two consumers signing different nouns are told apart

- **GIVEN** decidiq raised a request for board minutes and dossiq raised one
  for a beschikking
- **WHEN** both signing requests are listed
- **THEN** each names its own source app and its own subject noun
- @e2e exclude Backend record shape; covered by PHPUnit.

### Requirement: An unattributed delegated request is refused (REQ-DDSRC-003)

Filinq MUST refuse a delegated signing request whose subject register, subject
schema or subject id is missing, with a message naming what is absent. It MUST
NOT create a signing request that cannot say what it signs. A request raised
inside filinq itself carries no provenance and is unaffected.

#### Scenario: A request with no subject is refused

- **GIVEN** a consumer dispatches a request with no subject id
- **WHEN** filinq's listener handles it
- **THEN** the request is refused with a message naming the missing subject id
- **AND** no signing request is created
- @e2e exclude Backend refusal path; covered by PHPUnit.

#### Scenario: A filinq-internal request is unaffected

- **GIVEN** a user raises a signing request inside filinq
- **WHEN** the request is created
- **THEN** it succeeds with no provenance, as it does today
- @e2e exclude Existing internal path; covered by PHPUnit.

### Requirement: An admin can see which apps use the rail (REQ-DDSRC-004)

Filinq's signing settings surface MUST list the source apps that have raised
signing requests on the instance, with a count for each. An instance where a
consumer app is installed and has raised none is not an error, and the
surface MUST NOT claim otherwise.

#### Scenario: The surface names the consumers

- **GIVEN** dossiq and shillinq have both raised signing requests
- **WHEN** an admin opens the signing settings
- **THEN** both apps are listed with their request counts
- e2e: tests/e2e/spec-coverage/one-signing-rail-for-the-fleet.spec.ts

#### Scenario: No requests is not an error

- **GIVEN** no delegated signing request has been raised
- **WHEN** an admin opens the signing settings
- **THEN** the surface says no app has raised one, and shows no error
- e2e: tests/e2e/spec-coverage/one-signing-rail-for-the-fleet.spec.ts
