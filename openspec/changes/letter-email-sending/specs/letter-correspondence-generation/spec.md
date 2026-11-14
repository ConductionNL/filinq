# letter-correspondence-generation Specification (delta)

## ADDED Requirements

### Requirement: A composed letter can be sent by email (REQ-LCS-01)

Filinq SHALL offer `POST /api/correspondence/send` with a template id, a recipient id and an optional case reference. It SHALL compose the letter with the email body generation of this spec and send it through Nextcloud's mailer to the email address on the recipient object. The subject SHALL be the template's subject line, or its name when it has none. When the caller asks for it, the PDF of the same letter SHALL be attached. The caller SHALL be able to read the recipient object, or the send is refused with 404. The send SHALL be logged as a correspondence object with `status` `sent`, `sentAt`, `sentTo` and `sentBy`.

#### Scenario: An officer sends a letter to a citizen

- **GIVEN** an approved template "Uitnodiging gesprek" and a recipient with email address `j.jansen@example.nl`
- **WHEN** the officer sends the letter by email
- **THEN** one message goes to `j.jansen@example.nl` with the composed body
- **AND** the correspondence object has status `sent` with the address, the time and the officer

#### Scenario: The PDF goes along

- **GIVEN** the same template and recipient
- **WHEN** the officer sends the letter with the PDF attached
- **THEN** the message carries the PDF of the same letter

### Requirement: A send that cannot happen says why and records nothing as sent (REQ-LCS-02)

Filinq SHALL refuse the send with 422 and a message naming the cause when the recipient has no email address or the instance has no working mail server. A mailer error during the send SHALL record the correspondence object with `status` `failed` and the error, and the answer SHALL say the letter was not sent. The answer SHALL NOT contain server internals.

#### Scenario: The recipient has no email address

- **GIVEN** a recipient without an email address
- **WHEN** the officer sends a letter by email
- **THEN** the answer is 422 with "Deze ontvanger heeft geen e-mailadres" and no message is sent

#### Scenario: The mail server fails

- **GIVEN** a mail server that refuses the message
- **WHEN** the officer sends a letter by email
- **THEN** the correspondence object has status `failed` with the reason and the page says the letter was not sent
- @e2e exclude needs a failing mail server; covered by PHPUnit on CorrespondenceService with a mocked IMailer
