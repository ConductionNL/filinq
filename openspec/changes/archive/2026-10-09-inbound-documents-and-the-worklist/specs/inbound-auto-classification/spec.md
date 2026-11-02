# inbound-auto-classification Specification (delta)

---
status: proposed
---

## Purpose

A document that arrives before anybody knows which case it belongs to
gets a record, its defaults, a suggested type and a suggested party, and
stays inside the access model from the first byte. An attachment reaches
the case with the message it arrived with, and a document taken off a
case lands on a worklist instead of nowhere. Round 4 discovery cluster
44, eleven candidates. Consumed by dossiq, which owns the promote-to-case
act.

## ADDED Requirements

### Requirement: An attachment is a record of its own, linked to its message (REQ-IDW-01)

Filinq MUST create one `intakeDocument` per arriving file, including one
per attachment, each attachment carrying `arrivedWith` referencing the
record of the message it arrived with. Assigning the message MUST offer
to assign its attachments in the same act. Assigning an attachment on its
own MUST be allowed and MUST be recorded on both records.

#### Scenario: An aanvraag with three bijlagen stays together

- GIVEN a message carrying an aanvraag and three attachments
- WHEN it arrives through any channel
- THEN four intake records exist, the three attachments reference the message, and assigning the message offers all four

#### Scenario: One attachment belongs elsewhere

- GIVEN four intake records from one message
- WHEN a clerk assigns one attachment to a different case
- THEN that assignment is recorded on the attachment and noted on the message record

### Requirement: Default metadata is stamped at creation (REQ-IDW-02)

Filinq MUST declare `intakeDefaultRule` objects matching on channel or
sender pattern, each naming the metadata it stamps and its order. The
matching rule's values MUST be written onto the `intakeDocument` at
creation, before any classification runs. The record MUST name the rule
that stamped it. Classification MUST offer changes to a stamped value as
a suggestion and MUST NOT overwrite it silently.

#### Scenario: Nothing arrives unclassified

- GIVEN a default rule stamping documentType "brief" and confidentiality "intern" on the scan channel
- WHEN a scan arrives
- THEN the intake record carries both values and names the rule, before the classifier has run

#### Scenario: The classifier suggests, it does not overwrite

- GIVEN a stamped documentType of "brief" and a classifier suggesting "factuur"
- WHEN the clerk opens the record
- THEN the stamped value stands and the suggestion is offered beside it

#### Scenario: No rule matches

- GIVEN a channel with no matching default rule
- WHEN a document arrives
- THEN the record is created with no stamped defaults and says so, rather than failing

### Requirement: Party details are suggested from the document, never written (REQ-IDW-03)

Filinq MUST read the entity occurrences already detected on the file and
MUST offer a party suggestion carrying its confidence and its source
span. It MUST NOT write a party record without a person accepting the
suggestion. Accept, edit and reject MUST each be recorded as a
correction. The extraction MUST be declared as its own
`x-openregister-processing` activity with purpose, legal basis, data
categories and retention.

#### Scenario: The NAW block is offered, not filed

- GIVEN an incoming letter whose text carries a name and an address
- WHEN the intake record is opened
- THEN a party suggestion is shown with its confidence and the text it came from, and no party record has been written

#### Scenario: A rejection teaches the corpus

- GIVEN a party suggestion a clerk rejects
- WHEN they reject it
- THEN the rejection is recorded as a correction against that sender

#### Scenario: The activity is declared

- GIVEN the register descriptor after this change
- WHEN the processing activity export is produced
- THEN party extraction appears as its own activity with a purpose, a legal basis and a retention reference
- @e2e exclude the export is a register descriptor read; covered by the processing-activity PHPUnit test

### Requirement: A detached document lands on a worklist (REQ-IDW-04)

A document detached from a record MUST return to its `intakeDocument` in
state `detached`, carrying the reason and the user who detached it, and
MUST be created one if it never had it. The intake inbox MUST list
detached documents as their own worklist.

#### Scenario: A document removed from the wrong case

- GIVEN a document filed on the wrong case
- WHEN a handler detaches it with the reason "verkeerde zaak"
- THEN it appears in the detached worklist with that reason and the handler's name

#### Scenario: A document that never had an intake record

- GIVEN a document uploaded straight onto a case
- WHEN it is detached
- THEN an intake record is created for it in state `detached`

### Requirement: Routing and acceptance are declared by the consuming app (REQ-IDW-05)

Filinq MUST store, per record type reference the consumer declares, who
an inbound document routes to and whether an acceptance step is required,
and MUST apply it at arrival. Filinq MUST NOT define the record type
vocabulary itself.

#### Scenario: A bezwaar routes to the jurists

- GIVEN a case type declaring that inbound documents route to the jurist group and require acceptance
- WHEN a document is filed against that type
- THEN it is routed to that group and waits for acceptance

#### Scenario: No declaration, no routing

- GIVEN a record type with no routing declared
- WHEN a document is filed against it
- THEN the document is assigned without routing and without an acceptance step

### Requirement: The inbox is inside the access model and says what it is still reading (REQ-IDW-06)

Every `intakeDocument` MUST be an OpenRegister object from creation,
under a declared authorization cascade. The recognised text of a scan
MUST be written to the document's searchable content through the existing
OCR path, and text recognition MUST run locally. Classification and
extraction MUST run as background jobs, with progress on the intake
record, and the inbox MUST show documents still being read rather than
hiding them.

#### Scenario: A waiting document obeys the same permissions as a filed one

- GIVEN a user without access to the intake group
- WHEN they query the objects API for waiting documents
- THEN the cascade refuses them, exactly as it refuses a filed document
- @e2e exclude authorization cascade; covered by PHPUnit against the objects API with two users

#### Scenario: A scan is found by its words

- GIVEN a scanned letter mentioning "hoorzitting"
- WHEN the OCR has run and a user searches for that word
- THEN the scan is in the results

#### Scenario: A batch that is still being read

- GIVEN two hundred scans arriving at once
- WHEN a clerk opens the inbox
- THEN the records still being classified are listed with their progress
