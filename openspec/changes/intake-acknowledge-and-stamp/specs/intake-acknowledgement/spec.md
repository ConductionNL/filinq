# intake-acknowledgement Specification (delta)

## Purpose

When a document arrives, filinq can tell the sender it arrived and can
put a visible received stamp on a copy of it. The intake default rule
decides both, per channel and sender. Matrix rows `in-ack` and `in-stamp`
(filinq).

## ADDED Requirements

### Requirement: A matching rule acknowledges receipt to the sender (REQ-IAS-001)

When an arriving document matches an active intake default rule with
`acknowledgeWith` set, filinq MUST render an acknowledgement from that
template with the subject, the received date and the registration number
when one exists, and MUST send it to the sender after the intake document
is saved. A failure to acknowledge MUST NOT refuse or undo the arrival. The
outcome MUST be written to the document's `acknowledgement`.

Rows: `in-ack` (filinq matrix)

#### Scenario: A mailed letter is acknowledged

- GIVEN an active rule for channel `mail` with the template "Ontvangstbevestiging"
- WHEN a message from `j.devries@example.nl` arrives in the intake inbox
- THEN `j.devries@example.nl` receives the acknowledgement naming the subject and the received date, and the registrar sees "Acknowledged" on its row in `/intake`
- @e2e tests/e2e/intake-acknowledgement.spec.ts

#### Scenario: A failed send does not lose the letter

- GIVEN the same rule and a server whose mail is not configured
- WHEN the message arrives
- THEN the intake document exists with status received, and its row shows the acknowledgement as failed with the reason
- @e2e exclude mail transport failure is not drivable from the browser; covered by PHPUnit with a failing send unit

### Requirement: A sender who refused contact is not written to (REQ-IAS-002)

When the sender is a party carrying a refuse-send indicator, filinq MUST
NOT send the acknowledgement and MUST record `refused` with the
indicator's reason. When the sender cannot be reached by e-mail, filinq
MUST store the acknowledgement as a letter for the outgoing post and MUST
record `queued-as-letter`.

#### Scenario: A paper letter gets a letter back

- GIVEN an active rule for channel `scan` with an acknowledgement template
- WHEN a scanned letter from a sender without an e-mail address arrives
- THEN the registrar sees "Queued as letter" on the row, and the letter is in the outgoing post
- @e2e tests/e2e/intake-acknowledgement.spec.ts

### Requirement: A received stamp goes on a copy, never on what arrived (REQ-IAS-003)

When an arriving PDF matches an active rule with `stampReceived: true`,
filinq MUST write a rendition with "Ontvangen", the received date and the
registration number when one exists on the first page, MUST store its file
id in `stampedRendition`, and MUST leave the arrived file byte for byte
unchanged. A file that is not a PDF MUST NOT be stamped.

Rows: `in-stamp` (filinq matrix), featureRequest https://github.com/paperless-ngx/paperless-ngx/discussions/3744

#### Scenario: A registrar prints a stamped scan

- GIVEN an active rule with the stamp switched on and a scan that arrives on 14 October
- WHEN the registrar chooses "Open stamped copy" on its row in `/intake`
- THEN the first page shows "Ontvangen" and 14 October, and the arrived scan still has its original checksum
- @e2e tests/e2e/intake-acknowledgement.spec.ts

### Requirement: The worklist shows both outcomes (REQ-IAS-004)

The intake worklist MUST show per document whether it was acknowledged
(sent, queued as letter, refused, failed or none) and MUST offer to open
the stamped copy when one exists.

#### Scenario: A registrar sees who was not told

- GIVEN three waiting documents, one acknowledged, one refused and one without a rule
- WHEN the registrar opens `/intake`
- THEN the acknowledged column reads "Sent", "Refused" and empty for the three rows
- @e2e tests/e2e/intake-acknowledgement.spec.ts
