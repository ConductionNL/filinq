# pdf-password-removal Specification (delta)

## Purpose

A password-protected PDF can be unlocked into a copy so filinq can read,
check and anonymise it. The password is typed once and never kept.
Matrix row `in-pdf-password` (filinq), demand
https://github.com/paperless-ngx/paperless-ngx/pull/11656.

## ADDED Requirements

### Requirement: A clerk unlocks a locked PDF into a copy (REQ-PPR-001)

Filinq MUST offer a remove-password action wherever the `pdf-encrypted`
finding is shown. Given the right password, filinq MUST write an unlocked
copy named `<name> (unlocked).pdf` in the same folder and MUST leave the
original unchanged. A wrong password MUST answer 422 with the message "The
password is not correct" and MUST NOT write a file.

Rows: `in-pdf-password` (filinq matrix)

#### Scenario: A clerk unlocks a supplier's PDF from My documents

- GIVEN a clerk on `/my-documents` whose validation result shows "Encrypted PDF" for `offerte.pdf`
- WHEN the clerk chooses "Remove password", types the right password and confirms
- THEN `offerte (unlocked).pdf` appears in the same folder and `offerte.pdf` is unchanged
- @e2e tests/e2e/pdf-password-removal.spec.ts

#### Scenario: A wrong password writes nothing

- GIVEN the same locked PDF
- WHEN the clerk types a wrong password
- THEN the dialog says "The password is not correct" and no new file exists in the folder
- @e2e tests/e2e/pdf-password-removal.spec.ts

### Requirement: The copy is checked again at once (REQ-PPR-002)

After writing the copy filinq MUST run the document validation checks on
it and MUST return their findings with the new file id.

#### Scenario: The copy shows its own findings

- GIVEN a clerk has just unlocked a scanned PDF without a text layer
- WHEN the unlock completes
- THEN the validation result for the copy shows "Missing text layer" and no "Encrypted PDF"
- @e2e tests/e2e/pdf-password-removal.spec.ts

### Requirement: The password is used once and never kept (REQ-PPR-003)

The password MUST reach the decryption tool only through its standard
input. It MUST NOT be written to a log, an exception message, an audit
entry, an OpenRegister object or a process argument. Decryption MUST run
on the server. When the `qpdf` tool is not installed the action MUST
answer 503 with "qpdf is not installed on this server" and the admin
settings page MUST show that status. A document that is final MUST be
refused with 409.

#### Scenario: The admin sees that the tool is missing

- GIVEN a server without qpdf
- WHEN an admin opens the filinq admin settings
- THEN the page says qpdf is not installed, and a clerk's remove-password action answers "qpdf is not installed on this server"
- @e2e tests/e2e/pdf-password-removal.spec.ts

#### Scenario: Nothing records the password

- GIVEN a clerk unlocks a PDF with the password `<PASSWORD>`
- WHEN the request completes
- THEN no log line, audit entry or object property contains that value
- @e2e exclude absence-of-behaviour guard; covered by PHPUnit capturing the logger and the audit writer

### Requirement: An intake document keeps what arrived (REQ-PPR-004)

For an intake document whose file is locked, the intake worklist MUST
offer the same action. After unlocking, the `intakeDocument` MUST point
`file` at the copy and MUST keep the arrived file id in `lockedOriginal`.

#### Scenario: A registrar unlocks a document in the intake worklist

- GIVEN a registrar on `/intake` with a waiting document whose file is a locked PDF
- WHEN the registrar chooses "Remove password" on its row and types the right password
- THEN the row opens the unlocked copy, and the record's `lockedOriginal` names the file that arrived
- @e2e tests/e2e/pdf-password-removal.spec.ts
