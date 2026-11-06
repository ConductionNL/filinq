# signing-field-validation Specification (delta)

## Purpose

A signer fills in a text field while signing, and filinq checks the value
on the server before the signature is written. A Dutch IBAN is the first
check. The checks are OpenRegister formats. Matrix row
`sig-field-validation` (filinq), demand
https://kb.validsign.eu/nl/release-notes/release-notes-2026-09-23.

## ADDED Requirements

### Requirement: A placed text field declares its label, whether it is required, and its check (REQ-SFV-001)

A `text` placement in `signingRequest.fieldPlacements` MUST accept
`label`, `required` and `validation`, where `validation` is `iban-nl` or
`iban`. Other placement types MUST ignore the three. The placement editor
MUST let the initiator set them.

Rows: `sig-field-validation` (filinq matrix)

#### Scenario: An employee asks for a bank account

- GIVEN an employee placing fields on a subsidy agreement
- WHEN the employee places a text field for the applicant, labels it "Account for the payment", marks it required and picks "Dutch IBAN"
- THEN the saved request carries that placement with `required` true and `validation` `iban-nl`
- @e2e tests/e2e/signing-field-validation.spec.ts

### Requirement: The check is an OpenRegister format (REQ-SFV-002)

Filinq MUST check a value through OpenRegister's format of the same name
(`iban`, with country `NL` for `iban-nl`) and MUST NOT carry a checksum of
its own for it. When OpenRegister has no such format, creating a request
that uses the check MUST be refused with "This check is not available on
this server".

Rows: `sig-field-validation` (filinq matrix)

#### Scenario: An older OpenRegister

- GIVEN an OpenRegister without the `iban` format
- WHEN an employee creates a request with a Dutch IBAN field
- THEN the request is refused with "This check is not available on this server"
- @e2e exclude depends on the OpenRegister version; covered by PHPUnit with a missing format in task 2.1

#### Scenario: A foreign IBAN under the Dutch check

- GIVEN a field with the Dutch IBAN check
- WHEN a valid German IBAN is typed
- THEN the value is refused as not a Dutch IBAN
- @e2e exclude format branch; covered by PHPUnit in task 2.1

### Requirement: A wrong value stops the signature (REQ-SFV-003)

Signing MUST check every field placed for the signer before anything is
written. An empty required field or a value that fails its check MUST
answer 422 with a message on that field and MUST leave the signer record,
the request and the audit trail unchanged. The same check MUST apply to
the app route and to the portal sign act. A request with fields for the
signer MUST NOT be signed as part of a signing folder selection.

Rows: `sig-field-validation` (filinq matrix)

#### Scenario: A typing error is caught

- GIVEN an employee who is a signer, with the subsidy agreement open in the "Fill in and sign" dialog
- WHEN the employee types `NL91 ABNA 0417 1643 01` and chooses "Sign"
- THEN the field says "Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00" and the request still waits for the signature
- @e2e tests/e2e/signing-field-validation.spec.ts

#### Scenario: Not in a folder selection

- GIVEN the same request selected with two others in the signing folder
- WHEN the employee signs the selection
- THEN the two others are signed and this one says "This document has fields to fill in. Open it to sign."
- @e2e tests/e2e/signing-field-validation.spec.ts

### Requirement: The value is stored and sealed with the signature (REQ-SFV-004)

On a valid sign act filinq MUST store each value on
`signerRecord.fieldValues`, normalised (an IBAN in capitals without
spaces), in the same save as `SIGNED`. The native provider MUST draw each
value in its field block before the seal, so changing it afterwards fails
verification. `fieldPlacements` MUST NOT change once the request has left
`DRAFT`. Values MUST NOT appear in a log line, an exception message or
audit metadata, and MUST be readable through OpenRegister's objects API
only by those allowed to read signer records (AVG art. 5(1)(c) and art. 30).

Rows: `sig-field-validation` (filinq matrix)

#### Scenario: The account is on the signed agreement

- GIVEN the employee typed a valid IBAN and signed
- WHEN an admin in the signing admins group reads the signer record through the API and opens the signed file
- THEN the record holds `NL91ABNA0417164300` and the signed page shows it in the field block
- @e2e tests/e2e/signing-field-validation.spec.ts

#### Scenario: An altered value is caught

- GIVEN the signed agreement
- WHEN the bank account in the field block is altered in the file and it is verified
- THEN verification reports it as tampered
- @e2e exclude byte surgery on the artifact; covered by PHPUnit in task 2.4
